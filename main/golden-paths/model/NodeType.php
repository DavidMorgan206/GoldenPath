<?php

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists( 'Gpaths_NodeType' ) )
{
    /**
     * Class NodeType
     *
     * Holds node type information.  New types will require additions to several parts of the codebase
 *          -NodeFactory newNode
     *      -DB - new type specific table
     *      -others: search for case AffiliateNode
     *      -Update listnode child checks (can new node be a child of listnode?)
     *      -Client View, Admin View (node CRUD)
     *
     * @package Stradella\GPaths
     * @property int $id
     * @property string $title
     */
    class NodeType extends GpathsBaseObject implements ModelElementInterface
    {
        private $dataStore;

        /**
         * @param DataStoreInterface $dataStore
         * @param string $title
         * @return NodeType
         * @throws Exception
         */
        public static function getExistingFromTitle(DataStoreInterface $dataStore, string $title) :NodeType
        {
            $type = new NodeType($dataStore);
            $type->title = $title;
            $type->refreshFromDataStore();
            return $type;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param int $id
         * @return NodeType
         * @throws Exception
         */
        public static function getExistingFromId(DataStoreInterface $dataStore, int $id) :NodeType
        {
            $type = new NodeType($dataStore);
            $type->id = ($id);
            $type->refreshFromDataStore();
            return $type;
        }

        public function __construct (DataStoreInterface $dataStore)
        {
            $this->dataStore = $dataStore;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @return array
         * @throws Exception
         */
        public static function getUserCreatableNodeTypeTitles(DataStoreInterface $dataStore) :array
        {
            $sql = 'SELECT title FROM ' . $dataStore->nodeTypesTableName;
            return array_column($dataStore->select($sql, ['trustMe'=>true]), 'title');
        }

        /**
         * @param NodeType $type
         * @throws Exception
         */
        public function verifyEquals(NodeType $type)
        {
            GpathsBaseObject::verifyObjectsEqual($this->id, $type->id);
            GpathsBaseObject::verifyObjectsEqual($this->title, $type->title);
        }

        /**
         * @param bool $childCaller NodeType has no child clases, childCaller is ignored
         * @return string|null
         * @throws Exception
         */
        public function refreshFromDataStore(bool $childCaller = false) //:?string
        {
            if($this->dataStore->isMockDataStore())
                return 'skipping db call for mock model';

            $sql = null;
            $cond = null;

            if(isset($this->data['id'])) {
                $sql = "SELECT * FROM {$this->dataStore->nodeTypesTableName} WHERE id = :id";
                $cond = array(':id'=>$this->id);
            }
            elseif(isset($this->data['title'])) {
                $sql = "SELECT * FROM {$this->dataStore->nodeTypesTableName} WHERE title = :title";
                $cond = array(':title'=>$this->title);
            }
            else {
                throw new Exception("NodeType:refresh called without setting title or id");
            }

            $result = $this->dataStore->select($sql, $cond);

            if(empty($result)) {
                throw new Exception('NodeType::refresh path not found');
            }

            $this->title = $result[0]['title'];
            $this->id = $result[0]['id'];

            return 'success';

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
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function createInDataStore(bool $childCaller = false) //:?string
        {
            throw new Exception('not implemented');
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param string $title
         * @return bool
         * @throws Exception
         */
        public static function checkExistsByTitle(DataStoreInterface $dataStore, string $title): bool
        {
            throw new Exception('not implemented');
        }

        /**
         * @return array
         * @throws Exception
         */
        public static function getUpdatableProperties(): array
        {
            throw new Exception('not implemented');
        }
    }
}

