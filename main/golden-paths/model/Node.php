<?php

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists( 'Gpaths_Node' ) ) {
    /**
     * Class Node
     *
     *
     *
     * @package Stradella\GPaths
     * @property int $id
     * @property string $title
     * @property string $heading
     * @property NodeType $nodeType
     * @property bool $childrenAreExclusive
     * @property bool $skippable
     * @property NodeParent $parent
     */
    class Node extends GpathsBaseObject implements ModelElementInterface
    {
        protected $children;
        protected $sequence;
        protected $dataStore;

        const defaultNodeTitle = 'your title here';


        public function __construct(DataStoreInterface $dataStore)
        {
            $this->dataStore = $dataStore;

            $this->childrenAreExclusive = false;
            $this->heading = '';
            $this->skippable = true;
        }

        /**
         * @param $dataStore
         * @param int $id
         * @return bool
         * @throws Exception
         */
        public static function checkExistsById(DataStoreInterface $dataStore, int $id) :bool
        {
            $sql = "SELECT id FROM {$dataStore->nodesTableName} WHERE id=:id";
            $cond = array(':id'=>$id);
            $result = $dataStore->select($sql, $cond);
            if(count($result) > 0)
                return true;
            return false;
        }

        public static function getUpdatableProperties() :array
        {
            return  ['id', 'title', 'heading', 'childrenAreExclusive', 'skippable', 'parent'];
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param string $title
         * @return bool
         * @throws Exception
         */
        public static function checkExistsByTitle(DataStoreInterface $dataStore, string $title) :bool
        {
            $sql = "SELECT id FROM {$dataStore->nodesTableName} WHERE title=:title";
            $cond = array(':title'=>$title);
            $result = $dataStore->select($sql, $cond);
            if(count($result) > 0)
                return true;
            return false;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @return string
         * @throws Exception
         */
        public static function getUniqueDefaultTitle(DataStoreInterface $dataStore) :string
        {
            $uniqueSuffix = 0;
            $title = self::defaultNodeTitle;

            do{
                if($uniqueSuffix != 0)
                    $title = (self::defaultNodeTitle . ' (' . $uniqueSuffix . ')');

                $uniqueSuffix++;
            }
            while(self::checkExistsByTitle($dataStore, $title) && $uniqueSuffix < 10000);

            if($uniqueSuffix > 10000)
               throw new Exception('Failed to find unique default title');

            return $title;
        }


        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function createInDataStore(bool $childCaller=false) //:?string
        {
            if(!isset($this->childrenAreExclusive))
                throw new Exception('childrenareexclusive not set');

            if(!$childCaller)
                throw new Exception('method should only be called by children');
            if (isset($this->data['id'])) {
                $this->dataStore->logToDb('critical', 'Node::create : id already set');
                throw new Exception('Node::create : id already set');
            }
            if (!isset($this->data['nodeType'])) {
                $this->dataStore->logToDb('critical', 'Node::create : type note set prior to call');
                throw new Exception('Node::create : type not already set');
            }

            if(!isset($this->data['title'])) {
                $this->title = self::getUniqueDefaultTitle($this->dataStore);
            }

            $sql = 'INSERT INTO ' . $this->dataStore->nodesTableName .
                    ' (title, children_are_exclusive, heading, skippable, type_id) 
                    VALUES (:title, :childrenAreExclusive, :heading, :skippable, :typeId)';

            $cond = array(
                ':title'=>$this->title,
                ':childrenAreExclusive'=> $this->childrenAreExclusive ? 1: 0,
                ':heading'=>$this->heading,
                ':skippable'=> $this->skippable ? 1: 0,
                ':typeId'=> intval($this->nodeType->id)
                );


            $this->dataStore->select($sql, $cond);

            //get node's new id
            $sql = "SELECT id FROM {$this->dataStore->nodesTableName} WHERE title=:title";
            $cond = array(':title' => $this->title);
            $result = $this->dataStore->select($sql, $cond);
            $this->id = $result[0]['id'];

            //parent can be null if we're creating this node in an odd order OR permanently null for LandingNodes
            //if parent is non-null, caller should have set childNodeId and sequence
            if(isset($this->data['parent'])) {
                $this->parent->childNodeId = $this->id;
                $this->parent->createInDataStore();
            }
            //else : error_log('skipping creation of NodeParent db entry for node id: ' . $this->id);

            return 'success';
        }

        /**
         *set all attributes prior to update, they will not be pulled from db on null, default values may be used
         *type cant be updated
         *if parent hasn't been set it will not be touched
         *
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function updateDataStore(bool $childCaller = false) //:?string
        {
            $message = 'success';

            if(!$childCaller)
                throw new Exception('updateDataStore should only be called by children');

            $sql = "UPDATE {$this->dataStore->nodesTableName} SET 
                title=:title,
                children_are_exclusive=:childrenAreExclusive,
                skippable=:skippable,
                heading=:heading
                WHERE id=:id";
            $conds = array(
                ':title' => $this->title,
                ':childrenAreExclusive' => $this->childrenAreExclusive ? 1 : 0,
                ':skippable' => $this->skippable ? 1 : 0,
                ':heading'=> $this->heading,
                ':id' => $this->id
            );
            $this->dataStore->select($sql, $conds);

            if(isset($this->data['parent']) && (isset($this->data['parent']->parentNodeId))) { //only save out the object if there's any actual parent
                $this->parent->childNodeId = $this->id;
                $message = $this->parent->updateDataStore();
            }
            else {
                $this->parent = new NodeParent($this->dataStore); //create an object regardless so we can test isset(sequence) and isset(parentNodeId) without first checking for set parent
                $this->parent->childNodeId = $this->id;
            }

            return $message;
        }

        /**
         * warning - this will only compare children ids/seq depth = 1
         * and immediate parent id
         * this will not pull from the DataStore (to facilitate unit testing)
         *
         * public to facilite some testing of node, but the child's verifyEquals should be used where possible
         *
         * @param Node $node
         * @throws Exception
         */
        public function verifyEquals(Node $node) //:void
        {
            GpathsBaseObject::verifyObjectsEqual($this->id, $node->id);

            if (isset($this->parent)) {
                $this->parent->verifyEquals($node->parent);
            }
            elseif (isset($node->parent)) {
                throw new Exception('parent isset not equals');
            }

            GpathsBaseObject::verifyObjectsEqual($this->title, $node->title);
            GpathsBaseObject::verifyObjectsEqual($this->heading, $node->heading);
            if(isset($this->nodeType))
                $this->nodeType->verifyEquals($node->nodeType);
            elseif(isset($node->nodeType))
                throw new Exception('nodeType isset mismatch');

            GpathsBaseObject::verifyObjectsEqual($this->skippable, $node->skippable);
            GpathsBaseObject::verifyObjectsEqual($this->childrenAreExclusive, $node->childrenAreExclusive);

            if (isset($this->children)) {
                foreach ($this->children as $child) {
                    //extract an array of ids from node's child property
                    //can't use array_column because id is private
                    $nodeChildIds = array_map(
                        function ($e) {
                            return $e->id;
                        },
                        $node->children
                    );
                    if (!in_array($child->id, $nodeChildIds)) {
                        throw new Exception('1st level child ids not equal');
                    }
                }
            }
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param string $title
         * @return bool
         * @throws Exception
         */
        public static function existsInDataStore(DataStoreInterface $dataStore, string $title) :bool
        {
            $sql = "SELECT id FROM {$dataStore->nodesTableName} WHERE title=:title";
            $cond = array(':title' => $title);
            $result = $dataStore->select($sql, $cond);
            return count($result) > 0;
        }


        /**
         * @return array of Node objects from the DB
         * @throws Exception
         */
        public function getChildren() :array
        {
            if ($this->dataStore->isMockDataStore()) {
                return $this->children;
            }

            if (!isset($this->id)) {
                throw new Exception('node::getchildren : id must be set prior to call');
            }

            $sql = "SELECT child_node_id
                    FROM {$this->dataStore->nodeChildrenTableName} as nodeChildren,
                         {$this->dataStore->nodesTableName} as nodes 
                    WHERE nodes.id = nodeChildren.child_node_id  
                      AND parent_node_id = :id  
                    ORDER BY sequence";
            $cond = array(':id'=>$this->id);
            $result = $this->dataStore->select($sql, $cond);

            $this->children = array();
            for ($i = 0; $i < count($result); $i++) {
                $child = NodeFactory::getById($this->dataStore, $result[$i]['child_node_id']);
                array_push($this->children, $child);
            }

            return $this->children;
        }

        /**
         * scans all children recursively. intended for use in finding invalid node parents to avoid loops
         * @return array an array of ids of every child of this node
         * @throws Exception
         */
        public function getAllChildrenIds() :array
        {
            $childrenIds = [];

            foreach ($this->getChildren() as $child) {
                array_push($childrenIds, $child->id);
                $childrenIds = array_merge($childrenIds, $child->getAllChildrenIds());
            }
            return $childrenIds;
        }

        /**
         * getNextChild
         *
         * @param Node $current_child
         * @return Node|null next child based on sequence or null
         * @throws Exception
         */
        public function getNextChild(Node $current_child) //:?Node
        {
            $children = $this->getChildren();

            for ($i = 0; $i < count($children); $i++) {
                if ($children[$i]->id == $current_child->id) {
                    if ($i + 1 < count($children)) {
                        return $children[$i + 1];
                    }
                }
            }

            return null;
        }

        /***
         * old style setter preserved for test back compat.  Does not call updateDataStore on $parent
         * to make this official
         * @param Node $parent
         * @param int $sequence
         */
        public function setParent(Node $parent, int $sequence) //:void
        {
            if(!isset($this->data['parent'])) {
                $this->parent = new NodeParent($this->dataStore);
            }

            $this->parent->parentNodeId = $parent ? $parent->id : 0;
            $this->parent->sequence = $sequence;
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function refreshFromDataStore(bool $childCaller = false) //:?string
        {
            if(!$childCaller)
                throw new Exception('Node:refreshFromDataStore should only be called by children. Did you use factory class to get node instance?');
            if($this->dataStore->isMockDataStore())
                return 'success';

            $sql = null;
            $cond = null;

            if (isset($this->data['id'])) {
                $sql = "SELECT * FROM {$this->dataStore->nodesTableName} WHERE id = :id";
                $cond = array(':id'=>$this->id);
            }
            elseif (isset($this->title)) {
                $sql = "SELECT * FROM {$this->dataStore->nodesTableName} WHERE title = :title";
                $cond = array(':title' => $this->title);
            }
            else {
                $this->dataStore->logToDb('critical', 'Node:refresh called without setting title or id');
                throw new Exception("Node:refresh called without setting id");
            }

            $result = $this->dataStore->select($sql, $cond);

            if (count($result) != 1) {
                $this->dataStore->logToDb('critical', "Node:refresh : node not found sql: {$sql}");
                throw new Exception("Node:refresh : node not found: sql: " . $sql . " " . print_r($cond,true));
            }

            $this->title = $result[0]['title'];
            $this->id = $result[0]['id'];

            $this->heading = $result[0]['heading'];
            $this->skippable = $result[0]['skippable'] == 1;
            $this->childrenAreExclusive = $result[0]['children_are_exclusive'] == 1;

            $this->nodeType = NodeType::getExistingFromId($this->dataStore, $result[0]['type_id']);
            $this->parent = NodeParent::getExistingByChildId($this->dataStore, $this->id);

            return 'success';
        }

        /***
         * @return bool returns true if it looks like this node has been populated from the data store
         */
        public function refreshed()
        {
            return isset($this->id);
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function deleteFromDataStore(bool $childCaller = false) //:?string
        {
            if(!$childCaller)
                throw new Exception('method should only be called by children');

            if(empty($this->parent->parentNodeId) && count($this->getChildren()) > 0) {
                error_log('failure : All children must be deleted from a Path before it can be deleted.');
                return null;
            }
            else {
                $sequence = 0;
                foreach ($this->getChildren() as $child) {
                    $child->parent->parentNodeId = $this->parent->parentNodeId;
                    $child->parent->sequence = $sequence++;
                    $child->parent->updateDataStore();
                }
            }

            $sql = "DELETE FROM {$this->dataStore->nodesTableName} WHERE id=:id";
            $cond = array(':id' => $this->id);
            $this->dataStore->select($sql, $cond);

            //make any orphaned children leafs of their grand parent
            //we've already filtered bad requests to delete root nodes, so we know there will be a parent
            if(!isset($this->data['parent']))
                $this->parent = NodeParent::getExistingByChildId($this->dataStore, $this->id);
            else
                $this->parent->childNodeId = $this->id;

            foreach($this->getChildren() as $child) {
                $child->parent->parentNodeId = $this->parent->parentNodeId;
                $child->parent->updateDataStore();
            }

            //delete the NodeParent (parent/child relationship) from the datastore
            $this->parent->deleteFromDataStore();

            return 'success';
        }

        /**
         * Returns information about a node and it's children (recursive).
         * Warning: getChildren returns a generic Node class (for performance reasons) that can't be refreshed.
         * if you need additional info it needs to be added there.
         *
         * @return array
         * @throws Exception
         */
        public function getFlowSummary() :array
        {
            $response = array($this->getStateJSON());
            $children = $this->getChildren();
            for( $i = 0; $i < count($children); $i++) {
                foreach ($children[$i]->getFlowSummary() as $node) {
                    $node['lastChild'] = ($i == count($children) - 1) ? 1 : 0; //figure out the last child here rather than relying on "sequence", which may not be unique
                    array_push($response, $node);
                }
            }
            return $response;
        }

        /**
         * @return array
         * @throws Exception
         */
        public function getStateJSON() :array
        {
            return array(
                'title' => $this->title,
                'heading' => empty($this->heading) ? $this->title : $this->heading, //Use title if heading is null
                'nodeTypeTitle' => $this->nodeType->title,
                'childOfListNode' => $this->parent->isParentListNode() ? 1 : 0,
                'nodeId' => $this->id,
                'skippable' => ($this->skippable) ? 1 : 0,
                'childrenAreExclusive' => ($this->childrenAreExclusive) ? 1 : 0,
                'treeDepth' => $this->parent->getChildTreeDepth(),
                'sequence' => $this->parent->sequence ?? null
            );
        }
    }
}

