<?php

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists( 'Gpaths_ManualNode' ) ) {
    /**
     * Class ManualNode
     *
     * A node intended to be as blank a container as possible.  Weird new node settings should get dumped here rather
     * than dealing with a ton of different nodeTypes and associated tables
     *
     * @package Stradella\GPaths
     * @property bool $allowCustomPrice
     * @property string $bodyPaneHtml
     * @property float $defaultPrice
     * @property NodeType $linkPaneHtml
     */
    class ManualNode extends Node
    {
        /**
         * ManualNode constructor.
         * @param DataStoreInterface $dataStore
         * @throws Exception
         */
        public function __construct(DataStoreInterface $dataStore)
        {
            parent::__construct($dataStore);
            $this->nodeType = NodeType::getExistingFromTitle($dataStore, 'ManualNode');
            $this->allowCustomPrice = false;
            $this->bodyPaneHtml = '';
            $this->linkPaneHtml = '';
            $this->defaultPrice = 0.0;
        }

        public static function getUpdatableProperties() :array
        {
            $response = parent::getUpdatableProperties();
            return array_merge($response, ['allowCustomPrice', 'bodyPaneHtml', 'defaultPrice', 'linkPaneHtml']);
        }

        public function refreshFromDataStore(bool $childCaller = false) //:?string
        {
            parent::refreshFromDataStore(true);

            $sql = null;

            if (!$this->id) {
                $this->dataStore->logToDb('critical', 'ManualNode::refresh called without setting node_id');
                throw new Exception("ManualNodes:refresh called without setting or start_node_id");
            }

            $sql = "SELECT * FROM {$this->dataStore->manualNodesTableName} WHERE node_id = :id";
            $cond = array(':id'=>$this->id);
            $result = $this->dataStore->select($sql, $cond);

            if (empty($result)) {
                $this->dataStore->logToDb('critical', 'ManualNode::refresh id not found');
                throw new Exception('ManualNode::refresh id not found');
            }

            $this->bodyPaneHtml = $result[0]['body_pane_html'];
            $this->defaultPrice = $result[0]['default_price'];
            $this->allowCustomPrice = $result[0]['allow_custom_price'] == 1;
            $this->linkPaneHtml = $result[0]['link_pane_html'];

            return 'success';
        }

        /**
         * @return float|null
         * @throws Exception
         */
        public function getDefaultPrice() //:?float
        {
            if (!isset($this->defaultPrice)) {
                $this->refreshFromDataStore();
            }
            return $this->defaultPrice;
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function deleteFromDataStore(bool $childCaller = false) //:?string
        {
            $sql = "DELETE FROM {$this->dataStore->manualNodesTableName} WHERE node_id=:nodeId";
            $cond = array(':nodeId' => $this->id);
            $this->dataStore->select($sql, $cond);
            return parent::deleteFromDataStore(true);
        }

        /**
         * @param Node $node
         * @throws Exception
         */
        public function verifyEquals(Node $node) //:void
        {
            parent::verifyEquals($node);

            GpathsBaseObject::verifyObjectsEqual($this->bodyPaneHtml, $node->bodyPaneHtml);
            GpathsBaseObject::verifyObjectsEqual($this->linkPaneHtml, $node->linkPaneHtml);
            GpathsBaseObject::verifyObjectsEqual($this->defaultPrice, $node->defaultPrice);
            GpathsBaseObject::verifyObjectsEqual($this->allowCustomPrice, $node->allowCustomPrice);
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function updateDataStore(bool $childCaller = false) //:?string
        {
            $message = parent::updateDataStore(true);

            $sql = "UPDATE {$this->dataStore->manualNodesTableName} SET 
                    body_pane_html=:bodyPaneHtml,
                    link_pane_html=:linkPaneHtml,
                    default_price=:defaultPrice,
                    allow_custom_price=:allowCustomPrice
                    WHERE node_id=:node_id";
            $conds = array(
                ':bodyPaneHtml' => $this->bodyPaneHtml,
                ':linkPaneHtml' => $this->linkPaneHtml,
                ':defaultPrice' => $this->defaultPrice,
                ':allowCustomPrice' => $this->allowCustomPrice ? 1 : 0,
                ':node_id' => $this->id
            );
            $this->dataStore->select($sql, $conds);

            return $message;
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function createInDataStore(bool $childCaller = false) //:?string
        {
            $message = parent::createInDataStore(true);

            $sql = "INSERT INTO {$this->dataStore->manualNodesTableName} (node_id, body_pane_html, allow_custom_price, default_price, link_pane_html, priceInfoModified)  VALUES (:id, :bodyPaneHtml, :allowCustomPrice, :defaultPrice, :linkPaneHtml, :modified)";
            $cond = array(
                ':id'=> $this->id,
                ':bodyPaneHtml'=> $this->bodyPaneHtml,
                ':allowCustomPrice'=> $this->allowCustomPrice ? 1: 0,
                ':defaultPrice'=> $this->defaultPrice,
                ':linkPaneHtml'=> $this->linkPaneHtml,
                ':modified'=>'NOW()'
            );
            $this->dataStore->select($sql, $cond);
            return $message;
        }

        public function getStateJSON() :array
        {

            if( function_exists('do_shortcode')) {
                $bodyHtml = do_shortcode(stripslashes($this->bodyPaneHtml));
                $linkHtml = do_shortcode(stripslashes($this->linkPaneHtml));
            }
            else {
                $bodyHtml = stripslashes($this->bodyPaneHtml);
                $linkHtml = stripslashes($this->linkPaneHtml);
            }
            $response = parent::getStateJSON();
            $response = array_merge(
                $response,
                array(
                    'bodyPaneHtml' => $bodyHtml,
                    'defaultPrice' => $this->defaultPrice,
                    'linkPaneHtml' => $linkHtml,
                    'allowCustomPrice' => $this->allowCustomPrice ? 1 : 0
                )
            );

            return $response;
        }

        /**
         * @return array
         * @throws Exception
         */
        public function getListItemJSON() :array
        {
            $response = parent::getStateJSON();
            //if we're running in a WP environment, activate any shortcodes the admin may have added
            if( function_exists('do_shortcode')) {
                $response['linkPaneHtml'] = do_shortcode(stripslashes($this->linkPaneHtml));
                $response['bodyPaneHtml'] = do_shortcode(stripslashes($this->bodyPaneHtml));
            }
            else {
                $response['linkPaneHtml'] = (stripslashes($this->linkPaneHtml));
                $response['bodyPaneHtml'] = (stripslashes($this->bodyPaneHtml));
            }
            $response['allowCustomPrice'] = $this->allowCustomPrice ? 1 : 0;
            $response['price'] = $this->getDefaultPrice();

            return $response;
        }
    }
}

