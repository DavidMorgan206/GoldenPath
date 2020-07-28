<?php

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists( 'Gpaths_Flow' ) )
{
    /**
     * Class Flow
     * A 'Flow' (sometimes aka 'Path') is a tree of Nodes with an id = the id of the root node
     * @package Stradella\GPaths
     * @property int $id
     * @property string $currencySymbol
     * @property string $title
     * @property bool $displayTotalPriceOnSummary
     * @property bool $displaySkipToSummary
     * @property string $summaryTitle //page title displayed on FlowSummary page
     * @property string $summaryBody //body displayed on FlowSummary page
     */
    class Flow extends GpathsBaseObject implements ModelElementInterface
    {
        private $dataStore;
        const defaultFlowTitle = 'Your Path Title';

        public function __construct (DataStoreInterface $dataStore)
        {
            $this->dataStore = $dataStore;

            $this->summaryTitle = '';
            $this->summaryBody = '';
            $this->currencySymbol = '$';
            $this->displaySkipToSummary = true;
            $this->displayTotalPriceOnSummary = true;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @return string
         * @throws Exception
         */
        public static function getUniqueDefaultTitle(DataStoreInterface $dataStore) :string
        {
            $title = self::defaultFlowTitle;

            $uniqueSuffix = 0;

            do{
                if($uniqueSuffix != 0)
                    $title = (self::defaultFlowTitle . ' (' . $uniqueSuffix . ')');

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
        public function deleteFromDataStore(bool $childCaller = false) //:?string
        {
            $rootNode = NodeFactory::getById($this->dataStore, $this->id);
            if(!isset($this->id)){
                throw new Exception('flow id must be set to delete');
            }
            if(count($rootNode->getChildren()) > 0) {
               return 'Error: Unable to delete a flow that still has children';
            }

            $sql = "DELETE FROM {$this->dataStore->flowsTableName} WHERE start_node_id=:startNodeId";
            $cond = array(':startNodeId'=>$this->id);
            $this->dataStore->select($sql, $cond);

            $rootNode->deleteFromDataStore();
            return 'success';
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function createInDataStore(bool $childCaller = false) //:?string
        {
            if(!isset($this->title))
                throw new Exception('must set title');
            if(!isset($this->id))
                throw new Exception('must set id (should == start node id)');


            $sql = "INSERT INTO {$this->dataStore->flowsTableName} (start_node_id, title, display_total_on_summary, display_skip_to_summary, summary_title, summary_body, currency_symbol) VALUES (:id, :title, :displayTotalPriceOnSummary, :displaySkipToSummary, :summaryTitle, :summaryBody, :currencySymbol)";
            $cond = array(
                ':id'=>$this->id,
                ':title'=>$this->title,
                ':displayTotalPriceOnSummary'=>$this->displayTotalPriceOnSummary ? 1 : 0,
                ':displaySkipToSummary'=>$this->displaySkipToSummary ? 1 : 0,
                ':summaryTitle'=>$this->summaryTitle,
                ':summaryBody'=>$this->summaryBody,
                ':currencySymbol'=>$this->currencySymbol
            );
            $this->dataStore->select($sql, $cond);
            $this->refreshFromDataStore(); //populate id
            return 'success';
        }


        /**
         * @return Node
         * @throws Exception
         */
        public function getStartNode() :Node
        {
            return NodeFactory::getById($this->dataStore, $this->id);
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception on error
         */
        public function updateDataStore(bool $childCaller = false) //:?string
        {
            $sql = "UPDATE {$this->dataStore->flowsTableName} SET
            title=:title, display_total_on_summary=:displayTotalPriceOnSummary, display_skip_to_summary=:displaySkipToSummary, summary_title=:summaryTitle, summary_body=:summaryBody, currency_symbol=:currencySymbol 
            WHERE start_node_id=:id";
            $cond = array (
                ':title'=>$this->title,
                ':displayTotalPriceOnSummary'=>$this->displayTotalPriceOnSummary ? 1 : 0,
                ':displaySkipToSummary'=>$this->displaySkipToSummary ? 1 : 0,
                ':summaryTitle'=>$this->summaryTitle,
                ':summaryBody'=>$this->summaryBody,
                ':currencySymbol'=>$this->currencySymbol,
                ':id'=>$this->id
            );
            $this->dataStore->select($sql, $cond);

            return 'success';
        }

        /**
         * @param Flow $flow
         * @throws Exception if not equal
         */
        public function verifyEquals(Flow $flow) //:void
        {
            GpathsBaseObject::verifyObjectsEqual($this->id, $flow->id);
            GpathsBaseObject::verifyObjectsEqual($this->title, $flow->title);
            GpathsBaseObject::verifyObjectsEqual($this->displayTotalPriceOnSummary, $flow->displayTotalPriceOnSummary);
            GpathsBaseObject::verifyObjectsEqual($this->displaySkipToSummary, $flow->displaySkipToSummary);
            GpathsBaseObject::verifyObjectsEqual($this->summaryBody, $flow->summaryBody);
            GpathsBaseObject::verifyObjectsEqual($this->summaryTitle, $flow->summaryTitle);
            GpathsBaseObject::verifyObjectsEqual($this->currencySymbol, $flow->currencySymbol);
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function refreshFromDataStore(bool $childCaller = false) //:?string
        {
            $sql = null;
    
            if(isset($this->id)) {
                $sql = "SELECT * FROM {$this->dataStore->flowsTableName} WHERE start_node_id = :id";
                $cond = array(':id'=>$this->id);
            }
            elseif(isset($this->title)) {
                $sql = "SELECT * FROM {$this->dataStore->flowsTableName} WHERE title = :title";
                $cond = array(':title'=>$this->title);
            }
            else {
                throw new Exception("Flow:refresh called without setting title or start_node_id");
            }
            
            $result = $this->dataStore->select($sql, $cond);
    
            if(empty($result)) {
                throw new Exception('Flow::refresh: flow not found. ' . (isset($this->id) ? $this->id : $this->title));
            }
    
            $this->title = $result[0]['title'];
            $this->id = $result[0]['start_node_id'];
            $this->summaryBody = $result[0]['summary_body'];
            $this->summaryTitle = $result[0]['summary_title'];
            $this->currencySymbol = $result[0]['currency_symbol'];
            $this->displayTotalPriceOnSummary = $result[0]['display_total_on_summary'] == 1;
            $this->displaySkipToSummary = $result[0]['display_skip_to_summary'] == 1;

            return 'success';
        }

        /**
         * @return array information about elements in the flow for display on FlowSummary page (At completion of user session)
         * @throws Exception
         */
        public function getFlowSummary() :array
        {
            return $this->getStartNode()->getFlowSummary();
        }

        public function getStateJSON() :array
        {
            return array(
                'flowTitle' => $this->title,
                'currencySymbol' => $this->currencySymbol,
                'displayTotalPriceOnSummary'=>$this->displayTotalPriceOnSummary ? 1 : 0,
                'displaySkipToSummary'=>$this->displaySkipToSummary ? 1 : 0
            );
        }

        //Static functions.

        /**
         * @param DataStoreInterface $dataStore
         * @param string $flow_title
         * @return bool
         * @throws Exception
         */
        public static function checkExistsByTitle(DataStoreInterface $dataStore, string $flow_title) :bool
        {
            $sql = "SELECT 1 FROM {$dataStore->flowsTableName} WHERE title = :flowTitle";
            $cond = array(':flowTitle'=>$flow_title);
            $result = $dataStore->select($sql, $cond);
            return !empty($result);
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param int $id
         * @return Flow
         * @throws Exception
         */
        public static function getExistingById(DataStoreInterface $dataStore, int $id) :Flow
        {
            $flow = new flow($dataStore);
            $flow->id=$id;
            $flow->refreshFromDataStore();
            return $flow;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param string $title
         * @return Flow
         * @throws Exception
         */
        public static function getExistingByTitle(DataStoreInterface $dataStore, string $title) :Flow
        {
            $flow = new Flow($dataStore);
            $flow->title = $title;
            $flow->refreshFromDataStore();
            return $flow;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @return array
         * @throws Exception
         */
        public static function getExistingFlowsSql(DataStoreInterface $dataStore) :array
        {
            $sql = "SELECT * FROM {$dataStore->flowsTableName}";
            return $dataStore->select($sql, ['trustMe'=>true]);
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

