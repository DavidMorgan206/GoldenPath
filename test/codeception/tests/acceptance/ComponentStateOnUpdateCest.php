<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection DuplicatedCode */
use Codeception\Util\Locator;

require_once(dirname(__DIR__) . '/../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../main/golden-paths/DataStore/MockDataStore.php');

use Stradella\GPaths\{AffiliateNode, DataStore, Flow, LandingNode, ListNode, ManualNode, MockDataStore};

/**
 * Class ComponentStateOnUpdateCest
 *
 * This is a regression suite is focused on verifying we correctly update state when moving from Node Type X to Node Type X.  In
 * those cases where we have two nodes of the same type in a row, the constructor is not called for the new node and
 * changes have to be made in componentDidUpdate.
 */
class ComponentStateOnUpdateCest
{
    private $dataStore;
    private $mockDataStore;

    public function _before(AcceptanceTester $I)
    {
        $this->dataStore = new DataStore();
        $this->dataStore->clearAllData();
        $this->mockDataStore = new MockDataStore();
    }

    public function twoManualNodesInARow(AcceptanceTester $I)
    {
        $head = new ManualNode($this->dataStore);
        $head->childrenAreExclusive = false;
        $head->skippable=false;
        $head->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $head->title = "simpleFlowTestNode";
        $head->createInDataStore();

        $node1 = new ManualNode($this->dataStore);
        $node1->childrenAreExclusive = false;
        $node1->skippable=false;
        $node1->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $node1->title = "simpleFlowTestNode2";
        $node1->setParent($head, 1);
        $node1->createInDataStore();

        $flow = new Flow($this->dataStore);
        $flow->id = $head->id;
        $flow->title = "SimpleTestPath";
        $flow->currencySymbol = "$";
        $flow->createInDataStore();

        $I->amOnPage('simpletestpath');
        $I->waitForElement('#nextButton', 5);
        $I->click('#nextButton');
        $I->waitForText('simpleFlowTestNode2');
        $I->click('#nextButton');

        $I->waitForElement('#startOverButton');
    }

    public function twoLandingNodesInARow(AcceptanceTester $I)
    {
        $head = new LandingNode($this->dataStore);
        $head->childrenAreExclusive = false;
        $head->skippable=false;
        $head->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $head->title = "simpleFlowTestNode";
        $head->createInDataStore();

        $node1 = new LandingNode($this->dataStore);
        $node1->childrenAreExclusive = false;
        $node1->skippable=false;
        $node1->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $node1->title = "simpleFlowTestNode2";
        $node1->setParent($head, 1);
        $node1->createInDataStore();

        $flow = new Flow($this->dataStore);
        $flow->id = $head->id;
        $flow->title = "SimpleTestPath";
        $flow->currencySymbol = "$";
        $flow->createInDataStore();

        $I->amOnPage('simpletestpath');
        $I->waitForElement('#nextButton', 5);
        $I->click('#nextButton');
        $I->waitForText('simpleFlowTestNode2');
        $I->click('#nextButton');

        $I->waitForElement('#startOverButton');
    }

    public function twoListNodesInARow(AcceptanceTester $I)
    {
        $head = new LandingNode($this->dataStore);
        $head->childrenAreExclusive = false;
        $head->skippable=false;
        $head->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $head->title = "simpleFlowTestNode";
        $head->createInDataStore();

        $list1 = new ListNode($this->dataStore);
        $list1->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $list1->title = "list1";
        $list1->setParent($head, 1);
        $list1->createInDataStore();

        $node1 = new AffiliateNode($this->dataStore);
        $node1->childrenAreExclusive = false;
        $node1->skippable=false;
        $node1->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $node1->title = "child1";
        $node1->setParent($list1, 1);
        $node1->createInDataStore();

        $list2 = new ListNode($this->dataStore);
        $list2->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $list2->title = "list2";
        $list2->setParent($head, 1);
        $list2->createInDataStore();

        $node2 = new AffiliateNode($this->dataStore);
        $node2->childrenAreExclusive = false;
        $node2->skippable=false;
        $node2->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $node2->title = "child2";
        $node2->setParent($list2, 1);
        $node2->createInDataStore();
        $flow = new Flow($this->dataStore);

        $flow->id = $head->id;
        $flow->title = "SimpleTestPath";
        $flow->currencySymbol = "$";
        $flow->createInDataStore();

        $I->amOnPage('simpletestpath');
        $I->waitForElement('#nextButton', 5);
        $I->click('#nextButton');
        $I->waitForText('list1');
        $I->click('#nextButton');
        $I->waitForText('list2');
        $I->click('#nextButton');

        $I->waitForElement('#startOverButton');
    }

    public function twoAffiliateNodesInARow(AcceptanceTester $I)
    {
        $head = new AffiliateNode($this->dataStore);
        $head->childrenAreExclusive = false;
        $head->skippable=false;
        $head->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $head->title = "simpleFlowTestNode";
        $head->createInDataStore();

        $node1 = new AffiliateNode($this->dataStore);
        $node1->childrenAreExclusive = false;
        $node1->skippable=false;
        $node1->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
        $node1->title = "simpleFlowTestNode2";
        $node1->setParent($head, 1);
        $node1->createInDataStore();

        $flow = new Flow($this->dataStore);
        $flow->id = $head->id;
        $flow->title = "SimpleTestPath";
        $flow->currencySymbol = "$";
        $flow->createInDataStore();

        $I->amOnPage('simpletestpath');
        $I->waitForElement('#nextButton', 5);
        $I->click('#nextButton');
        $I->waitForText('simpleFlowTestNode2');
        $I->click('#nextButton');

        $I->waitForElement('#startOverButton');
    }
}
