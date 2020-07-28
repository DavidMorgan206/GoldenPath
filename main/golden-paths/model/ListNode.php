<?php

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists( 'Gpaths_ListNode' ) ) {
    /**
     * Class ListNode
     *
     * A ListNode has buyable child nodes, which are displayed all at once (instead of visited individually during a flow).
     * a ListNode's children can have no children (would break flow to navigate down on an individual 'buy' action)
     *
     * @package Stradella\GPaths
     * @property bool $allowCustomPrice
     * @property string $bodyPaneHtml
     */
    class ListNode extends Node
    {
        /**
         * ListNode constructor.
         * @param DataStoreInterface $dataStore
         * @throws Exception
         */
        public function __construct(DataStoreInterface $dataStore)
        {
            parent::__construct($dataStore);
            $this->nodeType = NodeType::getExistingFromTitle($dataStore, 'ListNode');

            $this->allowCustomPrice = false;
            $this->bodyPaneHtml = '';
        }

        public static function getUpdatableProperties() :array
        {
            $response = parent::getUpdatableProperties();
            return array_merge($response, ['allowCustomPrice', 'bodyPaneHtml']);
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function refreshFromDataStore(bool $childCaller = false) //:?string
        {
            parent::refreshFromDataStore(true);

            if (!$this->id) {
                $this->dataStore->logToDb('critical', 'ListNode::refresh called without setting node_id');
                throw new Exception("ListNodes:refresh called without setting or start_node_id");
            }

            $sql = "SELECT * FROM {$this->dataStore->listNodesTableName} WHERE node_id = :id";
            $cond = array(':id'=>$this->id);
            $result = $this->dataStore->select($sql,$cond);

            if (empty($result)) {
                $this->dataStore->logToDb('critical', 'ListNode::refresh id not found');
                throw new Exception('ListNode::refresh id not found');
            }

            $this->bodyPaneHtml = $result[0]['body_pane_html'];
            $this->allowCustomPrice = $result[0]['allow_custom_price'] == 1;

            return 'success';
        }

        /**
         * @param Node $node
         * @throws Exception
         */
        public function verifyEquals(Node $node) //:void
        {
            parent::verifyEquals($node);

            GpathsBaseObject::verifyObjectsEqual($this->bodyPaneHtml, $node->bodyPaneHtml);
            GpathsBaseObject::verifyObjectsEqual($this->allowCustomPrice, $node->allowCustomPrice);
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function deleteFromDataStore(bool $childCaller = false) //:?string
        {
            $sql = "DELETE FROM {$this->dataStore->listNodesTableName} WHERE node_id=:nodeId";
            $cond = array(':nodeId' => $this->id);
            $this->dataStore->select($sql, $cond);

            return parent::deleteFromDataStore(true);
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function createInDataStore(bool $childCaller = false) //:?string
        {
            $message = parent::createInDataStore(true);

            $sql = "INSERT INTO {$this->dataStore->listNodesTableName} (node_id, body_pane_html, allow_custom_price)  VALUES (
            :nodeId, :bodyPaneHtml, :allowCustomPrice)";
            $cond = array(
                ':nodeId' => $this->id,
                ':bodyPaneHtml' => $this->bodyPaneHtml,
                ':allowCustomPrice' => $this->allowCustomPrice ? 1 : 0
            );

            $this->dataStore->select($sql, $cond);

            return $message;
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function updateDataStore(bool $childCaller = false) //:?string
        {
            $message = parent::updateDataStore(true);

            $sql = 'UPDATE ' . $this->dataStore->listNodesTableName . ' SET 
                body_pane_html=:bodyPaneHtml,
                allow_custom_price=:allowCustomPrice
                WHERE node_id=:node_id';
            $conds = array(
                ':bodyPaneHtml' => $this->bodyPaneHtml,
                ':allowCustomPrice' => $this->allowCustomPrice ? 1 : 0,
                ':node_id' => $this->id
            );
            $this->dataStore->select($sql, $conds);

            return $message;
        }

        /**
         * @return array
         * @throws Exception
         */
        public function getStateJSON() :array
        {
            $response = parent::getStateJSON();
            $response['bodyPaneHtml'] = ($this->bodyPaneHtml);
            $response['allowCustomPrice'] = $this->allowCustomPrice ? 1 : 0;

            $sql = "SELECT child_node_id 
                    FROM {$this->dataStore->nodeChildrenTableName} 
                    WHERE parent_node_id = :id ORDER BY sequence";
            $cond = array(
                ':id'=>$this->id
            );
            $sqlResponse['nodeList'] = $this->dataStore->select($sql, $cond);

            if (!empty($sqlResponse)) {
                $response['nodeList'] = array();
                //add node info for each child node
                foreach ($sqlResponse['nodeList'] as &$nodeListItem) {
                    $node = NodeFactory::getById($this->dataStore, $nodeListItem['child_node_id']);
                    if (!method_exists($node, 'getListItemJSON')) {
                        throw new Exception("A listnode's child must implement getListItemJSON");
                    }

                    array_push($response['nodeList'], $node->getListItemJSON());
                }
            }
            return $response;
        }
    }
}
