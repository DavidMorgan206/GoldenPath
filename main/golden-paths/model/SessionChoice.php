<?php

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists( 'Gpaths_SessionChoice' ) )
{

    /**
     * Class SessionChoice
     *
     * Holds information about a user's interaction with a Node (as part of a Session of a Flow)
     *
     * Nodes that are explicitly skipped by the user (including ListNode children) will be added, but entries won't be
     * created for child nodes.
     *
     * TBD if skipped child nodes will be recursively added.  Doing so would make displaying flow summary
     * easier (no complex join of choices with nodes), but it becomes a nightmare trying to display user old sessions
     * using updated nodes (updated by admin) . Probably better to regenerate the flow summary from current node tree
     * each time (omitting choices for nodes that have been removed)
     *
     * @package Stradella\GPaths
     * @property Node $node
     * @property bool $skipped
     * @property int $quantity
     * @property Session $session
     * @property float $customPrice
     *
     */
    class SessionChoice extends GpathsBaseObject implements ModelElementInterface
    {
        private $dataStore;

        public function __construct(DataStoreInterface $dataStore)
        {
            $this->dataStore = $dataStore;

            $this->customPrice = 0;
            $this->quantity = 1;
            $this->skipped = false;
        }


        /**
         * supports create OR update
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function createInDataStore(bool $childCaller = false) //:?string
        {
            if(!isset($this->data['session'])  || !isset($this->session->id))
            {
                throw new Exception('invalid session args to SessionChoice::create');
            }
            if(!isset($this->data['node']) || !isset($this->node->id))
            {
                throw new Exception('invalid node args to SessionChoice::create');
            }

            $sql = 'INSERT INTO ' . $this->dataStore->sessionChoicesTableName .
                ' (session_id, node_id, skipped, custom_price, quantity) VALUES(
                :sessionId, :nodeId, :skipped1, :customPrice1, :quantity1) 
                ON DUPLICATE KEY UPDATE 
                skipped=:skipped2, custom_price=:customPrice2, quantity=:quantity2';
            $cond = array(
                ':sessionId'=>$this->session->id,
                ':nodeId'=>$this->node->id,
                ':skipped1'=>($this->skipped ? '1' : '0'),
                ':customPrice1'=> ($this->data['customPrice'] !== null ? $this->customPrice : 'NULL'),
                ':quantity1'=>(($this->data['quantity'] !== null) ? $this->quantity : 'NULL'),
                ':skipped2'=>($this->skipped ? '1' : '0'),
                ':customPrice2'=> ($this->data['customPrice'] !== null ? $this->customPrice : 'NULL'),
                ':quantity2'=>(($this->data['quantity'] !== null) ? $this->quantity : 'NULL')
            );
            $this->dataStore->select($sql, $cond);

            return 'success';
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param Session $session
         * @param Node $node
         * @param bool $skipped
         * @param float|null $customPrice
         * @param int|null $quantity
         * @return SessionChoice returns an object with properties set, but does not create it in the db
         */
        public static function initObject(
            DataStoreInterface $dataStore,
            Session $session,
            Node $node,
            bool $skipped = true,
            float $customPrice = null,
            int $quantity = null
        ) :SessionChoice
        {
            $ret = new SessionChoice($dataStore);
            $ret->node = $node;
            $ret->skipped = $skipped;
            $ret->quantity = $quantity;
            $ret->customPrice = $customPrice;
            $ret->session = $session;
            return $ret;
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function refreshFromDataStore(bool $childCaller = false) //:?string
        {
            $sql = null;
            $cond = null;

            if (isset($this->data['node']) && isset($this->data['session'])) {
                $sql = "SELECT * FROM {$this->dataStore->sessionChoicesTableName} WHERE node_id = :nodeId AND session_id = :sessionId";
                $cond = array(':nodeId'=>$this->node->id, 'sessionId'=>$this->session->id);
            }
            else {
                throw new Exception("SessionChoice:refresh called without setting node and session id");
            }

            $result = $this->dataStore->select($sql, $cond);

            if (count($result) != 1) {
                throw new Exception("SessionChoice:refresh : choice not found: sql: " . $sql . " " . print_r($cond,true));
            }

            $this->customPrice = $result[0]['custom_price'];
            $this->skipped = $result[0]['skipped'] == 1;
            $this->quantity = $result[0]['quantity'];

            return 'success';
        }

        public static function getExistingSessionChoice(DataStoreInterface $dataStore, int $sessionId, int $nodeId)
        {
            //TODO: if session choice isn't find we should probably just return the default choice. This would allow for admin adding new nodes without deleting all existing sessions
            $sessionChoice = new SessionChoice($dataStore);
            $sessionChoice->node = new Node($dataStore);
            $sessionChoice->node->id = $nodeId;
            $sessionChoice->session = new Session($dataStore);
            $sessionChoice->session->id = $sessionId;
            $sessionChoice->refreshFromDataStore();

            return $sessionChoice;
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function updateDataStore(bool $childCaller = false) //:?string
        {
            throw new Exception('not supported');
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function deleteFromDataStore(bool $childCaller = false) //:?string
        {
            throw new Exception('not supported');
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param string $title
         * @return bool
         * @throws Exception
         */
        public static function checkExistsByTitle(DataStoreInterface $dataStore, string $title): bool
        {
            throw new Exception('not supported');
        }

        /**
         * @return array
         * @throws Exception
         */
        public static function getUpdatableProperties(): array
        {
            throw new Exception('not implemented');
        }

        public function getStateJSON() {
            $response = array();
            $response['quantity'] = $this->quantity;
            $response['customPrice'] = $this->customPrice;
            $response['skipped'] = $this->skipped;

            return $response;
        }
    }
}

