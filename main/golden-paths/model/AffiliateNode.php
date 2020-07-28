<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists('Gpaths_AffiliateNode' ) ) {
    /**
     * Class Affiliate
     * AffiliateNode represents an entry in DataStore, minimal business logic
     *
     * @package Stradella\GPaths
     * @property bool $allowCustomPrice
     * @property string $bodyPaneHtml
     * @property string $linkPaneHtml
     * @property string $imagePaneHtml
     * @property string $footerPaneHtml
     */
    class AffiliateNode extends Node
    {
        public function __construct(DataStoreInterface $dataStore)
        {
            parent::__construct($dataStore);
            $this->nodeType = NodeType::getExistingFromTitle($dataStore, 'AffiliateNode');

            $this->allowCustomPrice = false;
            $this->bodyPaneHtml = '';
            $this->linkPaneHtml = '';
            $this->footerPaneHtml = '';
            $this->imagePaneHtml = '';
        }

        public static function getUpdatableProperties() :array
        {
            $response = parent::getUpdatableProperties();
            return array_merge($response, ['imagePaneHtml', 'bodyPaneHtml', 'footerPaneHtml', 'linkPaneHtml']);
        }

        /**
         * overrides any user or node setting for allowing custom price.
         */
        private function validateNodeConfiguration()// : void
        {
            if ($this->allowCustomPrice == true) {
                $this->allowCustomPrice = false;
                $this->dataStore->logToDb(
                    'warning',
                    'price and allowcustomprice set for an affiliate node, unsetting allow custom to comply with affiliate rules'
                );
            }
        }

        public function refreshFromDataStore(bool $childCaller = false) //:?string
        {
            if($this->dataStore->isMockDataStore())
                return 'success';

            $message = parent::refreshFromDataStore(true);

            if (!isset($this->data['id'])) {
                throw new Exception("AffiliateNodes:refresh called without setting or start_node_id");
            }

            $sql = "SELECT * FROM {$this->dataStore->affiliateNodesTableName} WHERE node_id = :nodeId";
            $cond = [':nodeId'=> $this->id];
            $result = $this->dataStore->select($sql,$cond);

            if (empty($result)) {
                throw new Exception('AffiliateNode::refresh id not found');
            }

            $this->allowCustomPrice = $result[0]['allow_custom_price'] == 1;
            $this->linkPaneHtml = $result[0]['link_pane_html'];
            $this->imagePaneHtml = $result[0]['image_pane_html'];
            $this->footerPaneHtml = $result[0]['footer_pane_html'];
            $this->bodyPaneHtml = $result[0]['body_pane_html'];

            return $message;
        }

        public function verifyEquals(Node $node) //:void
        {
            parent::verifyEquals($node);

            GpathsBaseObject::verifyObjectsEqual($this->bodyPaneHtml, $node->bodyPaneHtml);
            GpathsBaseObject::verifyObjectsEqual($this->linkPaneHtml, $node->linkPaneHtml);
            GpathsBaseObject::verifyObjectsEqual($this->imagePaneHtml, $node->imagePaneHtml);
            GpathsBaseObject::verifyObjectsEqual($this->footerPaneHtml, $node->footerPaneHtml);
            GpathsBaseObject::verifyObjectsEqual($this->allowCustomPrice, $node->allowCustomPrice);
        }

        /***
         * returns price from an external service
         */
        public function getPrice() //:?float
        {
            //TODO: pull real time price info from Affiliate if available
            return null;
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function updateDataStore(bool $childCaller = false) //:?string
        {
            $this->validateNodeConfiguration();
            $message = parent::updateDataStore(true);

            $sql = "UPDATE {$this->dataStore->affiliateNodesTableName} SET 
                body_pane_html=:bodyPaneHtml,
                link_pane_html=:linkPaneHtml,
                 image_pane_html=:imagePaneHtml,
                footer_pane_html=:footer_pane_html,
                allow_custom_price=:allowCustomPrice
                WHERE node_id=:node_id";
            $conds = array(
                ':bodyPaneHtml' => $this->bodyPaneHtml,
                ':linkPaneHtml' => $this->linkPaneHtml,
                ':imagePaneHtml' => $this->imagePaneHtml,
                ':footer_pane_html' => $this->footerPaneHtml,
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
        public function deleteFromDataStore(bool $childCaller = false) //:?string
        {
            $sql = "DELETE FROM {$this->dataStore->affiliateNodesTableName} WHERE node_id=:nodeId";
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
            $this->validateNodeConfiguration();
            $message = parent::createInDataStore(true);

            $sql = "INSERT INTO {$this->dataStore->affiliateNodesTableName} (node_id, body_pane_html, image_pane_html, allow_custom_price, link_pane_html, footer_pane_html)  values (
                :nodeId, :bodyPaneHtml, :imagePaneHtml, :allowCustomPrice, :linkPaneHtml, :footerPaneHtml)";
            $conds = array(
                ':nodeId'=> $this->id,
                ':bodyPaneHtml'=> $this->bodyPaneHtml,
                ':imagePaneHtml'=>$this->imagePaneHtml,
                ':allowCustomPrice'=> $this->allowCustomPrice ? 1 : 0,
                ':linkPaneHtml'=> $this->linkPaneHtml,
                ':footerPaneHtml'=> $this->footerPaneHtml
            );

            $this->dataStore->select($sql, $conds);
            return $message;
        }

        /***
         * @return array an array of entries intended to populate a cell in a ListNode's user facing rendering
         * @throws Exception
         */
        public function getListItemJSON() :array
        {
            $this->validateNodeConfiguration();

            $response = parent::getStateJSON();

            $response['linkPaneHtml'] = ($this->linkPaneHtml);
            $response['bodyPaneHtml'] = ($this->bodyPaneHtml);
            $response['imagePaneHtml'] = empty($this->imagePaneHtml) ? $this->linkPaneHtml : $this->imagePaneHtml; //imagePaneHtml is optional, if it's not included we'll show the link twice
            $response['footerPaneHtml'] = ($this->footerPaneHtml);
            $response['allowCustomPrice'] = $this->allowCustomPrice;
            $response['price'] = $this->getPrice();

            return $response;
        }

        /***
         * @return array Get an array of entries intended to populate a full user facing rendering
         * @throws Exception
         */
        public function getStateJSON() :array
        {
            $this->validateNodeConfiguration();

            $response = parent::getStateJSON();
            $response = array_merge(
                $response,
                array(
                    'bodyPaneHtml' => ($this->bodyPaneHtml),
                    'linkPaneHtml' => ($this->linkPaneHtml),
                    'imagePaneHtml' => empty($this->imagePaneHtml) ? $this->linkPaneHtml : $this->imagePaneHtml, //imagePaneHtml is optional, if it's not included we'll show the link twice
                    'footerPaneHtml' => ($this->footerPaneHtml),
                    'allowCustomPrice' => $this->allowCustomPrice ? 1 : 0,
                    'price' => $this->getPrice(),
                    'nodeTypeTitle' => 'AffiliateNode'
                )
            );

            return $response;
        }

    }
}

