<?php /** @noinspection PhpUnhandledExceptionInspection */

require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/DataStore.php');
use Stradella\GPaths\DataStore;
use Stradella\GPaths\{Node, ManualNode, LandingNode,  NodeFactory, AffiliateNode, ListNode, NodeParent};
use Stradella\GPaths\Flow;
use Stradella\GPaths\Session;
use Stradella\GPaths\NodeType;

class AdminModelCest
{
    private $dataStore;

    /**
     * @param FunctionalTester $I
     * @throws Exception
     */
    public function _before(FunctionalTester $I)
    {
        $this->dataStore = new DataStore();
        $this->dataStore->clearAllData(); //TODO: delete calls in tests?
    }

    // tests

    /**
     * Create a simple flow
     *
     * @param FunctionalTester $I
     * @throws Exception
     */
    public function CreateTrivialFlow(FunctionalTester $I)
    {
        $this->dataStore->clearAllData();

        $head = new LandingNode($this->dataStore);
        $head->childrenAreExclusive = false;
        $head->skippable=true;
        $head->title = 'test';
        $head->createInDataStore();

        $child1 = new LandingNode($this->dataStore);
        $child1->childrenAreExclusive = false;
        $child1->skippable=true;
        $child1->title = ('test-child1');
        $child1->setParent($head, 1);
        $child1->createInDataStore();

        $child2 = new LandingNode($this->dataStore);
        $child2->childrenAreExclusive = false;
        $child2->skippable=true;
        $child2->title = ('test-child2');
        $child2->setParent($head, 2);
        $child2->createInDataStore();

        $user_flow_steps = $head->getChildren();
        $I->assertTrue(!empty($user_flow_steps));

        $flow = new Flow($this->dataStore);
        $flow->id = $head->id;
        $flow->title = 'testPath';
        $flow->createInDataStore();

    }

    /**
     * @param FunctionalTester $I
     * @throws Exception
     *
     * create new session
     */
    public function CreateUserFlowSession(FunctionalTester $I)
    {
        $this->dataStore->clearAllData();
        
        $I->createSimpleFlow();
        
        $session = new Session($this->dataStore);
        $session->flow=Flow::getExistingByTitle($this->dataStore, 'SimpleTestPath');
        $session->cookieId='testCookieId';
        $session->title = ('test session');
        $session->createInDataStore();
        
    }

    /**
     * @param Functionaltester $I
     * @throws Exception
     *
     * Verify test ListNode is present
     */
    public function ListNode(Functionaltester $I)
    {
        $I->createSimpleListNodeFlow();

        $session = new Session($this->dataStore);
        $session->flow=Flow::getExistingByTitle($this->dataStore, 'SimpleListNodeFlow');
        $session->cookieId='testCookieId';
        $session->title = 'test session';
        $session->createInDataStore();
        $session->handleUserChoiceBool(false); //advance past landing node to list node
        $nodeState = NodeFactory::getById($this->dataStore, $session->getCurrentNode()->id)->getStateJSON();
        $I->assertArrayHasKey('nodeList', $nodeState);
        foreach ($nodeState['nodeList'] as $node)
        {
            $I->assertArrayHasKey('title', $node);
        }
    }

    /**
     * @param FunctionalTester $I
     * @throws Exception
     *
     * Create an AffiliateNode from scratch and save to db
     */
    public function AffiliateNode(FunctionalTester $I)
    {
        $node = new AffiliateNode($this->dataStore);
        $node->bodyPaneHtml=('bodypane test');
        $node->footerPaneHtml='footerpane test';
        $node->linkPaneHtml='linkpane test';
        $node->allowCustomPrice=false;
        $node->title='affiliateNodeTest';
        $node->createInDataStore();
        $nodeFromDataStore = NodeFactory::getById($this->dataStore, $node->id);
        $I->assertEquals($node->getStateJSON(), $nodeFromDataStore->getStateJSON());
        $I->assertEquals($node->getListItemJSON(), $nodeFromDataStore->getListItemJSON());
    }

    /**
     * @param FunctionalTester $I
     * @throws Exception
     *
     * Verify CRUD for flow object
     */
    public function crudFlow(FunctionalTester $I)
    {
        $landingNode = NodeFactory::getNewNode($this->dataStore, 'LandingNode');
        $landingNode->title = 'Landing Node test Title';
        $landingNode->createInDataStore();
        $flow = new Flow($this->dataStore);
        $flow->summaryTitle='summary title';
        $flow->summaryBody='summary body';
        $flow->id = $landingNode->id;
        $flow->title = 'flowTestTitle';
        $flow->createInDataStore();

        $flowFromDataStore = Flow::getExistingById($this->dataStore, $flow->id);
        $flowFromDataStore->refreshFromDataStore();
        $flow->verifyEquals($flowFromDataStore);

        $flow->summaryBody='new body';
        $flow->summaryTitle='nwe summary title';
        $flow->title = 'new title';
        $flow->updateDataStore();
        $flowFromDataStore = Flow::getExistingById($this->dataStore, $flow->id);
        $flowFromDataStore->refreshFromDataStore();
        $flow->verifyEquals($flowFromDataStore);
    }

    /**
     * @param FunctionalTester $I
     * @throws Exception
     *
     * Verify create and delete for node object with all node types
     */
    public function createAndDeleteNode(FunctionalTester $I)
    {
        foreach (NodeType::getUserCreatableNodeTypeTitles($this->dataStore) as $nodeTypeTitle) {
            $parent = NodeFactory::getNewNode($this->dataStore, 'LandingNode');
            $parent->title = 'LandingNode parent ' . $nodeTypeTitle;
            $parent->createInDataStore();

            $node = NodeFactory::getNewNode($this->dataStore, $nodeTypeTitle);
            $nodeTitle = 'delete me test ' . $nodeTypeTitle;
            $I->assertFalse(Node::existsInDataStore($this->dataStore, $nodeTitle));
            $node->title=$nodeTitle;
            $node->setParent($parent, 0);
            $node->createInDataStore();
            $I->assertTrue(Node::existsInDataStore($this->dataStore, $nodeTitle));


            $child = NodeFactory::getNewNode($this->dataStore, 'ManualNode');
            $child->title = 'child ' . $nodeTypeTitle;
            $child->setParent($node, 2);
            $child->createInDataStore();
            $oldChildDepth = $child->parent->getChildTreeDepth();

            $node->deleteFromDataStore();
            $I->assertFalse(Node::existsInDataStore($this->dataStore, $nodeTitle));

            $child->refreshFromDataStore();
            $tempParent = $child->parent->getParent();
            $typedParent = NodeFactory::getByTitle($this->dataStore, $tempParent->title);
            $typedParent->refreshFromDataStore();
            $typedParent->nodeType->id;

            $typedParent->verifyEquals($parent);
            $I->assertEquals($oldChildDepth - 1, $child->parent->getChildTreeDepth());
        }
     }

    /**
     * @param FunctionalTester $I
     * @throws Exception
     *
     * Verify create and update for all node types
     */
    public function createAndUpdateNode(FunctionalTester $I)
    {
        $newParent = new ManualNode($this->dataStore);
        $newParent->title = 'new parent node';
        $newParent->createInDataStore();
        foreach(NodeType::getUserCreatableNodeTypeTitles($this->dataStore) as $parentNodeTypeTitle) {
            $oldParent = NodeFactory::getNewNode($this->dataStore, $parentNodeTypeTitle);
            $oldParent->title = 'old parent node' . $parentNodeTypeTitle;
            $oldParent->createInDataStore();

            foreach (NodeType::getUserCreatableNodeTypeTitles($this->dataStore) as $nodeTypeTitle) {
                $node = NodeFactory::getNewNode($this->dataStore, $nodeTypeTitle);
                $node->childrenAreExclusive = false;
                $node->skippable=false;

                switch($nodeTypeTitle)
                {
                    case 'LandingNode':
                        $node->bodyPaneHtml=('old body pane');
                        $node->linkPaneHtml='old link pane';
                        break;
                    case 'ManualNode':
                        $node->bodyPaneHtml=('old body pane');
                        $node->linkPaneHtml='old link pane';
                        $node->allowCustomPrice=false;
                        $node->defaultPrice=10.1;
                        break;
                    case 'AffiliateNode':
                        $node->bodyPaneHtml=('bodypane test');
                        $node->footerPaneHtml='footerpane test';
                        $node->imagePaneHtml='imagepane test';
                        $node->linkPaneHtml='linkpane test';
                        $node->allowCustomPrice=false;
                        break;
                    case 'ListNode':
                        $node->bodyPaneHtml=('old body pane');
                        $node->allowCustomPrice=false;
                        break;
                    default:
                        throw new Exception('unsupported nodeType');
                }

                $I->assertFalse($node->refreshed());
                $nodeTitle = 'old title ' . $nodeTypeTitle . ' parent ' . $parentNodeTypeTitle;
                $node->title = $nodeTitle;
                $node->setParent($oldParent, 1);
                $node->createInDataStore();
                $I->assertTrue($node->refreshed());
                //force populate properties since verifyEquals will not refresh
                $node->parent->getChildTreeDepth();

                $nodeFromDataStore = NodeFactory::getByTitle($this->dataStore,$nodeTitle);
                $nodeFromDataStore->refreshFromDataStore();
                //force populate properties since verifyEquals will not refresh
                $nodeFromDataStore->parent->getParent();
                $nodeFromDataStore->parent->getChildTreeDepth();
                $nodeFromDataStore->nodeType->id;
                $node->verifyEquals($nodeFromDataStore);

                //modify all changeable attributes
                $node->childrenAreExclusive = true;
                $node->skippable=true;
                $node->setParent($newParent, 2);
                $nodeTitle = $nodeTitle . '2';
                $node->title = $nodeTitle;

                switch($nodeTypeTitle)
                {
                    case 'LandingNode':
                        $node->bodyPaneHtml=('old body pane2');
                        $node->linkPaneHtml='old link pane2';
                        break;
                    case 'ManualNode':
                        $node->bodyPaneHtml=('old body pane2');
                        $node->linkPaneHtml='old link pane2';
                        $node->allowCustomPrice=true;
                        $node->defaultPrice=20.20;
                        break;
                    case 'AffiliateNode':
                        $node->bodyPaneHtml=('bodypane test2');
                        $node->footerPaneHtml='footerpane test2';
                        $node->linkPaneHtml='linkpane test2j';
                        $node->imagePaneHtml='imagepane test2';
                        $node->allowCustomPrice=true;
                        break;
                    case 'ListNode':
                        $node->bodyPaneHtml=('old body pane');
                        $node->allowCustomPrice=true;
                        break;
                    default:
                        throw new Exception('unsupported nodeType');
                }

                //update and verify changes
                $node->updateDataStore();
                $nodeFromDataStore = NodeFactory::getById($this->dataStore, $node->id);
                //force populate properties since verifyEquals will not refresh
                $nodeFromDataStore->refreshFromDataStore();
                $nodeFromDataStore->parent->getParent();
                $nodeFromDataStore->parent->getChildTreeDepth();
                $nodeFromDataStore->nodeType->id;
                $node->verifyEquals($nodeFromDataStore);


            }
        }
    }
}
