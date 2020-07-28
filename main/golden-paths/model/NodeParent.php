<?php

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists( 'Gpaths_NodeParent' ) )
{
    /**
     * Class NodeParent
     *
     * holds the relationship between parent and child node. Maps to table in DataStore
     *
     * @package Stradella\GPaths
     * @property int $childNodeId
     * @property int $parentNodeId
     * @property int $sequence //priority order nodes will be displayed in UI.  No uniqueness checks.
     * @property int $childTreeDepth //used for indentation purposes in the UI (to show children under parents).
     */
    class NodeParent extends GpathsBaseObject implements ModelElementInterface
    {
        private $dataStore;

        public function __construct (DataStoreInterface $dataStore)
        {
            $this->dataStore = $dataStore;
        }

        /**
         * @return Node|null null if parentNodeId is null (to handle root node cases).
         * @throws Exception
         */
        public function getParent()
        {
            $this->refreshFromDataStore();

            if(isset($this->data['parentNodeId']))
            // requery every time to avoid tracking DataStore updates elsewhere in class. (performance issue?)
                return NodeFactory::getById($this->dataStore, $this->parentNodeId);
            else
                return null;
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function createInDataStore(bool $childCaller = false) //: ?string
        {
            if(!isset($this->data['childNodeId']))
                throw new Exception('childNodeId not set');
            if(!isset($this->data['parentNodeId']))
                throw new Exception('parentNodeId not set');
            if(!isset($this->data['sequence']))
                throw new Exception('sequence not set');


            $sql = 'INSERT INTO ' . $this->dataStore->nodeChildrenTableName .
                '(parent_node_id, child_node_id, sequence) VALUES (:parentNodeId, :childNodeId, :sequence)
            ON DUPLICATE KEY UPDATE parent_node_id=:parentNodeId2, sequence=:sequence2';

            $conds = array(
                ':parentNodeId' => $this->parentNodeId,
                ':childNodeId' => $this->childNodeId,
                ':sequence' => $this->sequence,
                ':parentNodeId2' => $this->parentNodeId,
                ':sequence2' => $this->sequence,
            );

            $this->dataStore->select($sql, $conds);
            return 'success';
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function refreshFromDataStore(bool $childCaller = false) //: ?string
        {
            if($this->dataStore->isMockDataStore())
                return 'success';
            if(!isset($this->data['childNodeId']))
                throw new Exception('childNodeId not set');

            unset($this->childTreeDepth);

            $sql = "SELECT parent_node_id, sequence FROM {$this->dataStore->nodeChildrenTableName} 
                    WHERE child_node_id = :childNodeId";
            $cond = array(':childNodeId'=>$this->childNodeId);
            $result = $this->dataStore->select($sql, $cond);

            if (count($result) < 1) {
                $this->parentNodeId = null;
            } elseif (count($result) > 1) {
                throw new Exception('duplicate entries in parent child table');
            } else {
                $this->parentNodeId = $result[0]['parent_node_id'];
                $this->sequence = $result[0]['sequence'];
            }

            return 'success';
        }

        /**
         * @param NodeParent $nodeParent
         * @throws Exception
         */
        public function verifyEquals(NodeParent $nodeParent) //:void
        {
            if(count(array_diff($this->data, $nodeParent->data)) != 0)
                throw new Exception('nodeParents not equal parent: ' . print_r($this->data, true) . ' passed: ' . print_r($nodeParent->data, true));
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function updateDataStore(bool $childCaller = false) //: ?string
        {
            // create handles update on duplicate
            return $this->createInDataStore($childCaller);
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function deleteFromDataStore(bool $childCaller = false) //: ?string
        {
            if(!isset($this->data['childNodeId']))
                throw new Exception('childNodeId not set');

            $sql = 'DELETE FROM ' . $this->dataStore->nodeChildrenTableName . ' WHERE child_node_id=:childNodeId';
            $cond = array(':childNodeId' => $this->childNodeId);
            $this->dataStore->select($sql, $cond);

            return 'success';
        }

        /**
         * @return int
         * @throws Exception
         */
        public function getChildTreeDepth()
        {
            if (empty($this->childTreeDepth)) {
                $this->childTreeDepth = 0;
                $parentNodeId = $this->parentNodeId;
                while ($parentNodeId != 0) {
                    $this->childTreeDepth++;
                    $parentNodeId = NodeParent::getExistingByChildId($this->dataStore, $parentNodeId)->parentNodeId;
                }
            }

            return $this->childTreeDepth;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param $childId
         * @return NodeParent
         */
        public static function getExistingByChildId(DataStoreInterface $dataStore, int $childId) :NodeParent
        {
            $parentNode = new NodeParent($dataStore);
            $parentNode->childNodeId = $childId;
            try {
                $parentNode->refreshFromDataStore();
            } catch (Exception $e) {
                //This may mean the node is the root node or that node hasn't been fully setup yet. Ignore either way.
            }
            return $parentNode;
        }

        /**
         * this is a helper function for getPossibleParentNodesSql
         * @param DataStoreInterface $dataStore
         * @return array
         * @throws Exception
         */
        private static function getNodesNotChildrenOfListNodesSql(DataStoreInterface $dataStore) :array
        {
            $sql = "SELECT nodes.id, nodes.title FROM {$dataStore->nodesTableName} nodes LEFT JOIN 
                    (SELECT child_node_id as id FROM {$dataStore->nodeChildrenTableName} nodeChildren INNER JOIN (SELECT nodes.id, type_id FROM {$dataStore->nodesTableName} nodes  INNER JOIN  (SELECT id, title FROM {$dataStore->nodeTypesTableName} WHERE title='ListNode') listType
                            ON listType.id = type_id
                         ) listNodes  ON nodeChildren.parent_node_id= listNodes.id) listNodeChildren  ON nodes.id = listNodeChildren.id
               WHERE listNodeChildren.id IS NULL ";

            return $dataStore->select($sql, ['trustMe'=>true]); //this query has no params to prepare
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param Node|null $child
         * @return array
         * @throws Exception
         */
        public static function getPossibleParentNodesSql(DataStoreInterface $dataStore, /*?Node*/ $child) :array
        {
            $childrenIds = [];

            //Perform some additional checks if this check is for an existing node
            if ($child->refreshed()) {
                $childrenIds = $child->getAllChildrenIds();
            }

            $parents = NodeParent::getNodesNotChildrenOfListNodesSql($dataStore);

            for ($i = 0; $i < count($parents); $i++) {
                //exclude this
                if ($child->refreshed() && intval($child->id) == intval($parents[$i]['id'])) {
                    unset($parents[$i]);
                } //exclude children of this
                elseif (in_array($parents[$i]['id'], $childrenIds)) {
                    unset($parents[$i]);
                }
            }

            return $parents;
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

        public function isParentListNode()
        {
            if(empty($this->parentNodeId )) { //root
                return false;
            }
            else if($this->getParent()->nodeType->title != 'ListNode') {
                return false;
            }
            return true;
        }
    }
}
