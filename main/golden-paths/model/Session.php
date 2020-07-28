<?php

namespace Stradella\GPaths;
use Exception;

require_once(dirname(__DIR__) . '/model/Node.php');
require_once(dirname(__DIR__) . '/model/Flow.php');

if ( ! class_exists( 'Gpaths_Session' ) )
{
    /**
     * Class Node
     * @package Stradella\GPaths
     * @property int $id
     * @property string $title
     * @property string $cookieId
     * @property Flow $flow
     * @property Node $currentNode
     */
    class Session extends GpathsBaseObject implements ModelElementInterface
    {
        private $dataStore;

        public function __construct (DataStoreInterface $dataStore)
        {
            $this->dataStore = $dataStore;
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function createInDataStore(bool $childCaller = false) //:?string
        {
            if(isset($this->id)) {
                throw new Exception("Session::create : id already set");
            }
            if(!isset($this->title)) {
                throw new Exception('title not set');
            }
            elseif(!isset($this->flow)) {
                throw new Exception("Session::create : flow not set");
            }

            if(!isset($this->currentNode)) {
                $this->updateCurrentNodeInDataStore($this->flow->getStartNode()); //start at beginning if not set
            }

            $sql = "INSERT INTO {$this->dataStore->sessionsTableName} (title, cookie_id, flow_id, current_node_id) VALUES (
                    :title, :cookieId, :flowId, :currentNodeId)";
            $cond = array(
                ':title'=>$this->title,
                ':cookieId'=>$this->cookieId,
                ':flowId'=>$this->flow->id,
                'currentNodeId'=>$this->currentNode->id
            );
            $this->dataStore->select($sql, $cond);

            $this->refreshFromDataStore();

            $this->startOver();

            return 'success';
        }

        public static function populateDefaultSessionChoices(DataStoreInterface $dataStore, Session $session, Node $root)
        {
           $root = NodeFactory::getById($dataStore, $root->id);
           $customPrice = null;

           if($root->nodeType->title == 'ManualNode') {
               $customPrice = $root->defaultPrice; //this default is used to prepopulate price field
           }

           $defaultChoice = SessionChoice::initObject($dataStore, $session, $root, true, $customPrice, null);
           $defaultChoice->createInDataStore();

           foreach($root->getChildren() as $child) {
               $session->populateDefaultSessionChoices($dataStore, $session, $child);
           }
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param string $cookie_id
         * @param string $flow_title
         * @param string $session_title
         * @return Session
         * @throws Exception
         */
        public static function getNewOrExisting(DataStoreInterface $dataStore, string $cookie_id, string $flow_title, string $session_title='unnamed session') :Session
        {
            if(!Flow::checkExistsByTitle($dataStore, $flow_title)) {
                throw new Exception("flow doesnt exist");
            }

            $session = new Session($dataStore);
            $session->title = $session_title;
            $flow = Flow::getExistingByTitle($dataStore, $flow_title);
            $session->flow=$flow;
            $session->cookieId=$cookie_id;

            if(!$session->existsInDataStore()) {
                $session->title = $session_title;
                $session->createInDataStore();
            }

            return $session;
        }

        public static function getExistingById(DataStoreInterface $dataStore, int $id) :Session
        {
            $session = new Session($dataStore);
            $session->id=$id;
            return $session;
        }

        /**
         * @return bool
         * @throws Exception
         */
        public function existsInDataStore() :bool
        {
            if(isset($this->id)) {
                return true;
            }

            if(($this->flow->id == null) || !isset($this->cookieId) || !isset($this->title)) {
                throw new Exception('session::getExists called without setting required object params');
            }

            $sql = "SELECT * FROM {$this->dataStore->sessionsTableName}
                    WHERE 
                    cookie_id = :cookieId AND 
                    flow_id = :flowId AND 
                    title = :title 
                    LIMIT 1";
            $cond = array(
                ':cookieId'=>$this->cookieId,
                ':flowId'=>$this->flow->id,
                ':title'=>$this->title
            );
            $result = $this->dataStore->select($sql, $cond);

            if(!($result) || count( $result) < 1) {
                //$this->dataStore->logToDb("info", ' didnt find existing session for title ' . $this->title . ' ' . $sql);
                return false;
            }

            $this->id = $result[0]['id'];

            return true;
        }

        /**
         *
         * @param array $sessionChoices
         * contains user choices for current node. Array length > 1 reserved for listNodes, which create choices for all their children in one operation.
         *      elements must have values 'id', 'skip'
         *      elements may have values 'customPrice', 'quantity'
         *      next node is determined by session's currentnode and $sessionChoices[0]->getSkipped.
         *      session's currentNode must == sessionChoices[0]
         * @throws Exception
         */
        public function handleUserChoice(array $sessionChoices) //:void
        {
            if(!isset($sessionChoices[0])) {
                throw new Exception("handleUserChoice called with empty param");
            }
            elseif($sessionChoices[0]->node->id != $this->getCurrentNode()->id) {
                throw new Exception("handleUserChoice called with choice[0] != current node");
            }
            elseif(null === $sessionChoices[0]->skipped) {
                throw new Exception("handleUserChoice called with choice[0] w no skip arg");
            }

            //Save out the user's choices
            foreach ($sessionChoices as $choice) {
                $choice->session = $this;
                $choice->createInDataStore();
            }

            //update current node for user's session
            $this->advanceUsersCurrentNode($sessionChoices[0]->skipped);
        }

        /**
         * provides backcompat for some tests
         *
         * @param bool $skip
         * @throws Exception
         */
        public function handleUserChoiceBool(bool $skip) //:void
        {
            $this->handleUserChoice(array(SessionChoice::initObject($this->dataStore, $this, $this->getCurrentNode(), $skip)));
            return;
        }

        /**
         * @return bool
         * @throws Exception
         */
        public function getSessionComplete() :bool
        {
            return is_null($this->getCurrentNode());
        }

        /**
         * @throws Exception
         */
        public function setSessionComplete() //:void
        {
            $this->updateCurrentNodeInDataStore(null);
        }

        /**
         * advanceUsersCurrentNode
         * never update choices from here, we may call this multiple times for a single user choice
         *
         * @param bool $skip
         *
         * @return void
         * @throws Exception
         */
        private function advanceUsersCurrentNode(bool $skip) //:void
        {
            $current_node = $this->getCurrentNode();
            $parent = $current_node->parent->getParent();
            //for the purposes of "advancing" the user's current node a ListNode has no children (since we never visit them directly)
            if(strcmp($current_node->nodeType->title, 'ListNode') != 0) {
                $children = $current_node->getChildren();
            }

            if(!isset($current_node)) {
                $this->setSessionComplete();
                //error_log( 'advance state 1 : completing session - current null');
            }
            //if we're skipping current and current has no parent then we're done with flow
            //we never skip if we're visiting children, so this will only be true at end
            elseif($skip && !isset($parent)) {
                $this->setSessionComplete();
                //error_log( 'advance state 1 : completing session - skip w/ null parent');
            }
            // if has no parent and didn't skip (Start scenario) OR
            // if accepted and has children
            //  =  child[0]
            elseif((!$skip && !empty($children) && count($children) > 0)) {
                $this->updateCurrentNodeInDataStore($children[0]);
            }
            elseif(!isset($parent)) {
                error_log( 'advance state: somethings gone wrong, parent is null but we didnt go to a child');
                throw new Exception("advanceUsersCurrentNode error: childless root");
            }
            //elseif accepted AND parent.exclusive
            // = parent, advance again
            elseif(!$skip && $parent->childrenAreExclusive) {
                //error_log( ("advance state 3: setting next node = parent, then recall"));
                $this->updateCurrentNodeInDataStore($parent);
                $this->advanceUsersCurrentNode(true);
            }
            else {
                //elseif parent has unvisited children
                // = parent's next child
                $next_child = $parent->getNextChild($this->currentNode);

                if(isset($next_child)) {
                    //error_log( ("advance state 4: setting next node = parent's next child"));
                    $this->updateCurrentNodeInDataStore($next_child);
                }
                //else (no children, no siblings)
                // = parent, advance again
                else {
                    //error_log( ("advance state 5: setting next node = parent, then recall"));
                    $this->updateCurrentNodeInDataStore($parent);
                    $this->advanceUsersCurrentNode(true);
                }
            }
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function refreshFromDataStore(bool $childCaller = false) //: ?string
        {
            $sql = null;
            $cond = null;

            if(isset($this->id)) {
                $sql = "SELECT * FROM {$this->dataStore->sessionsTableName} WHERE id = :id";
                $cond = array(':id'=>$this->id);
            }
            elseif(isset($this->title) && isset($this->cookieId) && isset($this->flow)) {
                $sql = "SELECT * FROM {$this->dataStore->sessionsTableName} 
                        WHERE title = :title AND 
                        cookie_id = :cookieId  AND  
                        flow_id = :flowId";
                $cond = array(
                    ':title'=>$this->title,
                    ':cookieId'=>$this->cookieId,
                    ':flowId'=>$this->flow->id
                );
            }
            //TODO: remove this if we want to support multiple instances of same flow for given user (and we add a picker)
            elseif(isset($this->cookieId) && isset($this->flow)) {
                $sql = "SELECT * FROM {$this->dataStore->sessionsTableName} 
                        WHERE 
                        cookie_id = :cookieId AND 
                        flow_id = :flowId 
                        LIMIT 1";
                $cond = array(
                    ':cookieId'=>$this->cookieId,
                    ':flowId'=>$this->flow->id
                );
            }
            else {
                throw new Exception("Session:refresh called without setting id or (title AND cookie_id AND flow_id");
            }

            $result = $this->dataStore->select($sql, $cond);

            if(empty($result)) {
                throw new Exception('Session:refresh : session not found: sql: ' . $sql);
            }

            $this->title = $result[0]['title'];
            $this->cookieId = $result[0]['cookie_id'];
            $this->id = $result[0]['id'];
            if(is_null($result[0]['current_node_id']))
                $this->currentNode = null;
            else
                $this->currentNode = NodeFactory::getById($this->dataStore, $result[0]['current_node_id']);

            $this->flow = new Flow($this->dataStore);
            $this->flow->id=$result[0]['flow_id'];

            return 'success';
        }

        /**
         * @param Node|null $current_node
         * @throws Exception
         */
        public function updateCurrentNodeInDataStore($current_node) //:void
        {
            if(isset($current_node))
                $this->currentNode = NodeFactory::getById($this->dataStore, $current_node->id);
            else
                $this->currentNode = null;

            if(isset($this->id)) {
                $new_id = (isset($current_node) ? $current_node->id : null);
                $sql = "UPDATE {$this->dataStore->sessionsTableName} 
                        SET last_modified = NOW(), current_node_id = :currentNodeId
                        WHERE id = :thisId";
                $cond = array(
                    ':currentNodeId'=>$new_id,
                    ':thisId'=>$this->id
                );
                $this->dataStore->select($sql, $cond);
            }
        }

        /**
         * @return Node|null null return means session complete
         * @throws Exception
         */
        public function getCurrentNode() //:?Node
        {
            if(empty($this->currentNode)) {
               $this->refreshFromDataStore();
               if(empty($this->currentNode))
                   return null;
            }

            return $this->currentNode;
        }

        /**
         * @throws Exception
         */
        public function startOver() //:void
        {
            $sql = "DELETE FROM {$this->dataStore->sessionChoicesTableName} WHERE session_id = :id";
            $cond = array(':id'=>$this->id);
            $this->dataStore->select($sql, $cond);
            $this->updateCurrentNodeInDataStore($this->flow->getStartNode());
            $this->populateDefaultSessionChoices($this->dataStore, $this, $this->flow->getStartNode());
        }

        /**
         * @return array
         * @throws Exception
         */
        public function getStateJSON() :array
        {
            $response = array(
                "sessionId" => $this->id,
                "sessionTitle" => $this->title,
                "sessionCompleted" => $this->getSessionComplete(),
            );
            $response = array_merge($response, $this->flow->getStateJSON());

            if(!$this->getSessionComplete()) {
                $currentNode = NodeFactory::getById($this->dataStore, $this->getCurrentNode()->id);
                $nodeState = $currentNode->getStateJSON();

                //we have to dig through and populate ListNode's children's session state (to keep ListNode from depending on session)
                if($currentNode->nodeType->title == 'ListNode') {
                    foreach($nodeState['nodeList'] as $key => $child) {
                        $listChoice = SessionChoice::getExistingSessionChoice($this->dataStore, $this->id, $child['nodeId']);
                        $nodeState['nodeList'][$key] = array_merge($child, $listChoice->getStateJSON());
                    }
                }

                $response = array_merge($response, $nodeState);
                $currentChoice = SessionChoice::getExistingSessionChoice($this->dataStore, $this->id, $currentNode->id);
                $response = array_merge($response, $currentChoice->getStateJSON());
            }

            return $response;
        }

        public function getSessionChoicePrice(array $sessionChoiceJSON) //:?float
        {
            if($sessionChoiceJSON['allow_custom_price'] == 1) {
                if (array_key_exists('custom_price', $sessionChoiceJSON) && $sessionChoiceJSON['custom_price'] != null) {
                    return $sessionChoiceJSON['custom_price'];
                }
            }

            return $sessionChoiceJSON['default_price'];
        }

        /**
         * @return array
         * @throws Exception
         */
        public function getSessionSummaryJSON() //:array
        {
            $sql = "SELECT 
                        nt.id, 
                        nt.title,
                        sc.custom_price,
                        sc.quantity,
                        tt.title as type,
                        mn.allow_custom_price,
                        mn.default_price,
                        sc.skipped FROM {$this->dataStore->sessionChoicesTableName} as sc 
                        INNER JOIN {$this->dataStore->nodesTableName} as nt ON sc.node_id = nt.id 
                        LEFT OUTER JOIN {$this->dataStore->landingNodesTableName} as ln ON nt.id = ln.node_id 
                        LEFT OUTER JOIN {$this->dataStore->manualNodesTableName} as mn ON nt.id = mn.node_id 
                        LEFT OUTER JOIN {$this->dataStore->nodeTypesTableName} as tt ON tt.id = nt.type_id 
                        WHERE sc.session_id = {$this->id}
                        ORDER BY sc.id";
            $response = array('nodeList' => $this->dataStore->select($sql, ['trustMe'=>true]));

            //add additional info
            foreach($response['nodeList'] as &$sessionChoice) {
                $node = NodeFactory::getById($this->dataStore, $sessionChoice['id']);
                $sessionChoice['price'] = $this->getSessionChoicePrice($sessionChoice);

                //TODO: tons of DB calls - store these in parent-child relationship table?
                $sessionChoice['treeDepth'] = $node->parent->getChildTreeDepth();
                $sessionChoice['childOfListNode'] = $node->parent->isParentListNode();
                $sessionChoice['buyableNode'] = ($node->nodeType->title == 'ManualNode' || $node->nodeType->title == 'AffiliateNode');
            }
            //add session info
            $response['flowTitle'] = $this->flow->title;
            $response['currencySymbol'] = $this->flow->currencySymbol;
            $response['sessionId'] = $this->id;
            $response['summaryTitle'] = $this->flow->summaryTitle;
            $response['displayTotalPriceOnSummary'] = $this->flow->displayTotalPriceOnSummary;
            $response['nodeId'] = 'summary';
            $response['nodeTypeTitle'] = 'SessionSummary';

            //TODO: move to http endpoint or new layer between there and here
            if( function_exists('do_shortcode')) {
                $response['summaryBody'] = do_shortcode(stripslashes(html_entity_decode($this->flow->summaryBody)), false);
            }
            else {
                $response['summaryBody'] = stripslashes(html_entity_decode($this->flow->summaryBody));
            }

            return $response;
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function updateDataStore(bool $childCaller = false) //:?string
        {
            throw new Exception('not implemented');
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function deleteFromDataStore(bool $childCaller = false) //:?string
        {
            throw new Exception('not implemented');
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param string $title
         * @return bool
         * @throws Exception
         */
        public static function checkExistsByTitle(DataStoreInterface $dataStore, string $title) : bool
        {
            throw new Exception('not applicable'); //Breaks the rules i know, but there are session title isn't unique across users
        }

        /**
         * @return array
         * @throws Exception
         */
        public static function getUpdatableProperties(): array
        {
            throw new Exception('not supported');
        }

        public function updateCurrentNodeIdInDataStore($start_node_id)
        {
            $node = NodeFactory::getById($this->dataStore, $start_node_id);
            $this->updateCurrentNodeInDataStore($node);
        }
    }
}
