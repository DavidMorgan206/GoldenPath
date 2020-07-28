<?php /** @noinspection PhpUnhandledExceptionInspection */

require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/DataStore.php');
use Stradella\GPaths\DataStore;
use Stradella\GPaths\{Node, ManualNode, LandingNode,  NodeFactory, AffiliateNode, ListNode, NodeParent};
use Stradella\GPaths\Flow;
use Stradella\GPaths\Session;
use Stradella\GPaths\NodeType;

class ClientModelCest
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

    /**
     * Complete trivial flow with minimal verification
     */
    public function CompleteSimpleFlow(FunctionalTester $I)
    {
        $this->dataStore->clearAllData();
        $I->createSimpleFlow();

        $session = new Session($this->dataStore);
        $session->flow=Flow::getExistingByTitle($this->dataStore, 'SimpleTestPath');
        $session->cookieId='testCookieId';
        $session->title = 'test session CompleteSimpleFlow';
        $session->createInDataStore();
        
        
        $I->assertSame($session->getCurrentNode()->title, 'simpleFlowTestNode');
        $session->handleUserChoiceBool(false);
        $I->assertSame($session->getCurrentNode()->title, 'simpleFlowTestNode-child1');
        $session->handleUserChoiceBool(false);
        $I->assertSame($session->getCurrentNode()->title, 'simpleFlowTestNode-child2');
        $I->assertSame($session->getSessionComplete(), false);
        $session->handleUserChoiceBool(false);
        $I->assertSame($session->getSessionComplete(), true);
        
    }

    /**
     * When I "buy" (don't skip) a node that is a child of a node with "exclusive child" set, I should skip over other children of my parent.
     */
    public function ExclusiveChild(FunctionalTester $I)
    {
        $this->dataStore->clearAllData();
        $I->CreateKitchenStuffFlow();
        
        $session = new Session($this->dataStore);
        $session->flow=Flow::getExistingByTitle($this->dataStore, 'KitchenStuff');
        $session->cookieId='testCookieId';
        $session->title = 'test session CompleteComplexFlow';
        $session->createInDataStore();
        $session->updateCurrentNodeInDataStore(\Stradella\GPaths\NodeFactory::getByTitle($this->dataStore, "Knife"));
        $I->assertSame($session->getCurrentNode()->title, 'Knife');
        $session->handleUserChoiceBool(false);
        $I->assertSame($session->getCurrentNode()->title, 'knife block');
        $session->handleUserChoiceBool(false);
        $I->assertSame($session->getCurrentNode()->title, 'Saucepan');
        $I->assertSame($session->getSessionComplete(), false);
    }

    /**
     * When I skip a node i should proceed to the next sibling of current (not current's children)
     */
    public function SkipChildren(FunctionalTester $I)
    {
        $this->dataStore->clearAllData();
        $I->CreateKitchenStuffFlow();
        
        $session = new Session($this->dataStore);
        $session->flow=Flow::getExistingByTitle($this->dataStore, 'KitchenStuff');
        $session->cookieId='testCookieId';
        $session->title = 'test session CompleteComplexFlow';
        $session->createInDataStore();
        $session->updateCurrentNodeInDataStore(\Stradella\GPaths\NodeFactory::getByTitle($this->dataStore, "Knife"));
        $currentNode = $session->getCurrentNode();
        $I->assertSame($currentNode->title, 'Knife');
        $session->handleUserChoiceBool(true);
        $currentNode = $session->getCurrentNode();
        $I->assertSame($currentNode->title, 'Saucepan');
        $I->assertSame($session->getSessionComplete(), false);
    }

    /**
     * Things shouldn't blow up traversing a deep tree
     */
    public function DeepChild(FunctionalTester $I)
    {
        $this->dataStore->clearAllData();
        $I->CreateKitchenStuffFlow();
        
        $session = new Session($this->dataStore);
        $session->flow=Flow::getExistingByTitle($this->dataStore, 'KitchenStuff');
        $session->cookieId='testCookieId';
        $session->title = ('test session CompleteComplexFlow');
        $session->createInDataStore();
        $session->updateCurrentNodeInDataStore(NodeFactory::getByTitle($this->dataStore, "cutting board"));
        $I->assertSame($session->getCurrentNode()->title, 'cutting board');
        $session->handleUserChoiceBool(false);
        $session->updateCurrentNodeInDataStore(NodeFactory::getByTitle($this->dataStore, "cutting board oil"));
        $I->assertSame($session->getCurrentNode()->title, 'cutting board oil');
        $session->handleUserChoiceBool(false);
        $session->updateCurrentNodeInDataStore(NodeFactory::getByTitle($this->dataStore, "cutting board oil holder"));
        $I->assertSame($session->getCurrentNode()->title, 'cutting board oil holder');
        $session->handleUserChoiceBool(false);
        $I->assertSame($session->getSessionComplete(), true);
    }
}
