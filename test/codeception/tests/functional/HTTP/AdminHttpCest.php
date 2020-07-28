<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection DuplicatedCode */

require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/MockDataStore.php');
require_once(dirname(__DIR__) . '/../../../..\main\golden-paths\includes\AdminEditFlow.php');
require_once(dirname(__DIR__) . '/../../../..\main\golden-paths\includes\AdminEditNode.php');
require_once(dirname(__DIR__) . '/../../../..\main\golden-paths\includes\AdminFlowSummary.php');
use Stradella\GPaths\{DataStore, MockDataStore};
use Stradella\GPaths\{Node, ManualNode, LandingNode, NodeFactory, AffiliateNode, ListNode, NodeType};
use Stradella\GPaths\Flow;
use Stradella\GPaths\Session;

class AdminHttpCest
{
    private $dataStore;
    private $mockDataStore;

    public function _before(FunctionalTester $I)
    {
        $this->dataStore = new DataStore();
        $this->dataStore->clearAllData();
        $this->mockDataStore = new MockDataStore();
    }



    /**
     * @param FunctionalTester $I
     * @throws Exception
     *
     * Create a Flow with POST/GET
     * //TODO: codeception doesnt support pulling values from mce control? no coverage for summary body
     */
    public function createNewFLow(FunctionalTester $I)
    {
        //create a flow
        $_POST = array();
        $localTestFlow = new Flow($this->mockDataStore); //use the mock model so we never accidently write to db directly from test
        $localTestFlow->title = 'test flow title';
        $localTestFlow->summaryBody='summary body test';
        $localTestFlow->summaryTitle='summary title foo';
        $localTestFlow->displaySkipToSummary = 1;
        $localTestFlow->displayTotalPriceOnSummary = 1;
        $_POST['flowTitle'] = $localTestFlow->title;
        $_POST['summaryBody'] = $localTestFlow->summaryBody;
        $_POST['summaryTitle'] = $localTestFlow->summaryTitle;
        $_POST['currencySymbol'] = $localTestFlow->currencySymbol;
        $_POST['displaySkipToSummary'] = $localTestFlow->displaySkipToSummary ? 1 : 0;
        $_POST['displayTotalPriceOnSummary'] = $localTestFlow->displayTotalPriceOnSummary ? 1: 0;
        $_GET['action'] = 'create';
        $_GET['actionObject'] = 'flow';
        \Stradella\GPaths\AdminEditFlow::processPostForEdits($this->dataStore);

        //make sure flow was created
        $I->assertTrue(Flow::checkExistsByTitle($this->dataStore, $localTestFlow->title));
        $flowFromDS = Flow::getExistingByTitle($this->dataStore, $localTestFlow->title);

        //make sure a default landing node was created
        $I->assertTrue(Node::checkExistsById($this->dataStore, $flowFromDS->id));
        $landingNode = NodeFactory::getById($this->dataStore, $flowFromDS->id);
        $I->assertEquals($landingNode->nodeType->title, 'LandingNode');

       //update the flow
        $_POST = array();
        $localTestFlow->title = 'new title';
        $localTestFlow->summaryBody='new summary body test';
        $localTestFlow->summaryTitle='new summary title foo';
        $localTestFlow->currencySymbol='new$';
        $localTestFlow->displayTotalPriceOnSummary = 0;
        $localTestFlow->displaySkipToSummary = 0;
        $localTestFlow->id = $landingNode->id;
        $_POST['flowId'] = $localTestFlow->id;
        $_POST['flowTitle'] = $localTestFlow->title;
        $_POST['summaryBody'] = $localTestFlow->summaryBody;
        //$_POST['displaySkipToSummary'] = $localTestFlow->displaySkipToSummary;
        //$_POST['displayTotalPriceOnSummary'] = $localTestFlow->displayTotalPriceOnSummary;
        $_POST['summaryTitle'] = $localTestFlow->summaryTitle;
        $_POST['currencySymbol'] = $localTestFlow->currencySymbol;
        $_GET['action'] = 'update';
        $_GET['actionObject'] = 'flow';
        \Stradella\GPaths\AdminEditFlow::processPostForEdits($this->dataStore);

        //verify edits to the flow
        error_log('localTestFlow dislaySkipToSummary value ' . $localTestFlow->displaySkipToSummary);
        $I->assertTrue(Flow::checkExistsByTitle($this->dataStore, $localTestFlow->title));
        $flowFromDS2 = Flow::getExistingById($this->dataStore, $landingNode->id);
        $flowFromDS2->verifyEquals($localTestFlow);

        //verify landing node unchanged
        $sameRoot = NodeFactory::getById($this->dataStore, $landingNode->id);
        $I->assertEquals($sameRoot->nodeType->title, 'LandingNode');
        $I->assertEquals($sameRoot->id, $sameRoot->id);
        $landingNode->verifyEquals($sameRoot);

        //delete the flow to make sure the view updates. Deletion covered as part of Flow Summary
        $flowFromDS2->deleteFromDataStore();
        $I->assertFalse(Flow::checkExistsByTitle($this->dataStore, $localTestFlow->title));
        $I->assertFalse(Node::checkExistsByTitle($this->dataStore, $sameRoot->id));
    }

    /**
     *
     * @param FunctionalTester $I
     * @throws Exception
     */

    /**
     * @param FunctionalTester $I
     * @throws Exception
     *
     * Create, Update a Node with POST/GET
     */
    public function CrudANode(FunctionalTester $I)
    {
        //create a node
        $nodeParent = NodeFactory::getNewDefaultLandingNode($this->dataStore);
        $nodeParent2 = NodeFactory::getNewDefaultLandingNode($this->dataStore);

        //TODO: add testing for NodeType specific properties (although these are mostly? handled in loops that also contain tested elements)
        foreach(NodeType::getUserCreatableNodeTypeTitles($this->dataStore) as $nodeTypeTitle) {
            //create
            $localNode = NodeFactory::getNewNode($this->dataStore, $nodeTypeTitle);
            $localNode->parent = new \Stradella\GPaths\NodeParent($this->dataStore);
            $localNode->parent->parentNodeId = $nodeParent->id;
            $localNode->parent->sequence = 1;
            $localNode->childrenAreExclusive = 1;
            $localNode->skippable = 1;
            $localNode->title = 'test title' . $nodeTypeTitle;
            $localNode->heading = 'test heading ' . $nodeTypeTitle;

            $_POST = array();
            $_POST['title'] = $localNode->title;
            $_POST['heading'] = $localNode->heading;
            $_POST['nodeParentTitle'] = $nodeParent->title;
            $_POST['nodeTypeTitle'] = $localNode->nodeType->title;
            $_POST['sequence'] = $localNode->parent->sequence;
            $_POST['skippable'] = $localNode->skippable;
            $_POST['childrenAreExclusive'] = $localNode->childrenAreExclusive;
            $_GET['action'] = 'create';
            $_GET['actionObject'] = 'node';
            \Stradella\Gpaths\AdminEditNode::processPostForEdits($this->dataStore);

            //verify creation
            $I->assertTrue(Node::checkExistsByTitle($this->dataStore, $localNode->title));
            $nodeFromDS = NodeFactory::getByTitle($this->dataStore, $localNode->title);
            $nodeFromDS->parent->refreshFromDataStore();
            $localNode->id = $nodeFromDS->id;
            $localNode->parent->childNodeId = $localNode->id;
            $localNode->verifyEquals($nodeFromDS);

            //send update
            $localNode->title = 'test title' . $nodeTypeTitle . ' 2 ' ;
            $localNode->parent->parentNodeId = $nodeParent2->id;
            $localNode->skippable = 0;
            $localNode->childrenAreExclusive = 0;
            $_POST = array();
            $_POST['nodeId'] = $localNode->id;
            $_POST['title'] = $localNode->title;
            $_POST['heading'] = $localNode->heading;
            $_POST['nodeParentTitle'] = $nodeParent2->title;
            $_POST['nodeTypeTitle'] = $localNode->nodeType->title;
            $_POST['sequence'] = $localNode->parent->sequence;
            $_GET['action'] = 'update';
            $_GET['actionObject'] = 'node';
            \Stradella\Gpaths\AdminEditNode::processPostForEdits($this->dataStore);

            //verify update
            $I->assertTrue(Node::checkExistsByTitle($this->dataStore, ($localNode->title)));
            $nodeFromDS2 = NodeFactory::getByTitle($this->dataStore, $localNode->title);
            $nodeFromDS2->parent->refreshFromDataStore();
            $localNode->verifyEquals($nodeFromDS2);

            //send delete
            //Deleting directly via the model doesn't test anything here. Delete covered elsewhere
            $nodeFromDS2->deleteFromDataStore();

            //verify delete
            $I->assertFalse(Node::checkExistsByTitle($this->dataStore, ($localNode->title)));
            $I->assertFalse(Node::checkExistsById($this->dataStore, ($localNode->id)));
        }

    }
}

