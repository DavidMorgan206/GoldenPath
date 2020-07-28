<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection DuplicatedCode */
use Codeception\Util\Locator;

require_once(dirname(__DIR__) . '/../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../main/golden-paths/DataStore/MockDataStore.php');

use Stradella\GPaths\{DataStore, Flow, MockDataStore};

/**
 * Class ClientViewCest
 *
 * requires existing pages create in wordpress with : {shortcode path_title}
 * simpletestflow :simpleTestFlow
 * kitchen:kitchenStuffFlow
 * listNodeFlow:listNodeFlow
 */
class ClientViewCest
{
    private $dataStore;
    private $mockDataStore;

    public function _before(AcceptanceTester $I)
    {
        $this->dataStore = new DataStore();
        $this->dataStore->clearAllData();
        $this->mockDataStore = new MockDataStore();
    }


    // tests
    public function skipToSummaryButton(AcceptanceTester $I)
    {
        //create a simple flow
        $I->createSimpleFlow();
        $I->amOnPage('simpletestpath');
        $I->waitForElement('#nextButton', 5);
        $I->click('#nextButton');
        $I->waitForElement('#skipToSummary', 5);
        $I->wait(1); //TODO: TESTBUG: below click is getting lost without this timeout
        $I->click('#skipToSummary');
        $I->waitForElement('#startOverButton');
    }


    /*
     * When I don't modify prices
     * The summary page shows default prices
     */
    public function defaultPrice(AcceptanceTester $I)
    {
        $I->createSimpleFlow();

        $I->amOnPage('simpletestpath');
        $I->waitForElement('#nextButton', 5);
        $I->see('simpleFlowTestNode');
        $I->click('#nextButton');

        $I->waitForElement('#customPrice', 5);
        $I->see("simpleFlowTestNode-child1");
        $I->click('#nextButton');

        $I->waitForElement('#defaultPrice', 5);
        $I->see("simpleFlowTestNode-child2");
        $I->click("#skipButton");

        $I->waitForElement('#startOverButton');

        $I->see('SimpleTestPath');

        $I->seeElement('//table/tbody/tr[1]', ['skipped'=>0]);
        $I->see('simpleFlowTestNode', '//table/tbody/tr[1]');
        $I->dontSee('$', '//table/tbody/tr[1]'); //no price information should be displayed for landing node type

        $I->seeElement('//table/tbody/tr[2]', ['skipped'=>0]);
        $I->see('simpleFlowTestNode-child1', '//table/tbody/tr[2]');
        $I->see('$10.00', '//table/tbody/tr[2]');

        $I->seeElement('//table/tbody/tr[3]', ['skipped'=>1]);
        $I->see('simpleFlowTestNode-child2', '//table/tbody/tr[3]');
        $I->dontSee('$20.00', '//table/tbody/tr[3]');

        $I->seeElement('//table/tbody/tr[4]', ['id'=>'totalPrice']);
       $I->see("$10.00", '//table/tbody/tr[4]');
    }

    /*
     * When I return to a list node from the Summary page
     * Then my old choices are repopulated (instead of defaults)
     */
    public function listNodeChoicesPersist(AcceptanceTester $I)
    {
        $I->CreateListNodeFlow();
        $pageId = $I->getPageIdFromTitle($this->dataStore, 'KnifeStuffListNode');
        $I->amOnPage('listNodeFlow?startNodeId=' . $pageId);
        $I->waitForElement('#nextButton');
        $I->see('NeatKnife');
        $I->checkOption('NeatKnife');
        $I->fillField('$', 1.01);
        $I->wait(1); //TODO: debug, shouldn't be required
        $I->click('#nextButton');
        $I->waitForText('Dish Towel');
        $I->click('#skipToSummary');
        $I->waitForElement('#startOverButton');
        $I->click('//table/tbody/tr[3]');
        $I->waitForElement('#skipToSummary');
        $I->seeCheckboxIsChecked('NeatKnife');
        $I->seeInField('$', 1.01);
    }

    /*
     * When I return to a manual node from the Summary page
     * Then my old choices are repopulated (instead of defaults)
     */
    public function manualNodeChoicesPersist(AcceptanceTester $I)
    {
        $priceSelector = '#customPrice';

        $I->createSimpleFlow();
        $pageId = $I->getPageIdFromTitle($this->dataStore, 'simpleFlowTestNode-child1');
        $I->amOnPage('simpletestpath?startNodeId=' . $pageId);
        $I->waitForElement($priceSelector);
        $I->fillField($priceSelector, 1.01);
        $I->click('#nextButton');
        $I->waitForElement('#skipToSummary');
        $I->wait(1); //TODO: why isn't skipToSummary actually ready?
        $I->click('#skipToSummary');
        $I->waitForElement('#startOverButton');
        $I->click('#nodeTitle');
        $I->waitForElement('#nextButton');
        $I->click('#nextButton');
        $I->waitForElement($priceSelector);
        $I->seeInField($priceSelector, 1.01);
    }

    public function manualNodeChoicesDontPersistOnSkipToSummary(AcceptanceTester $I)
    {
        $priceSelector = '#customPrice';

        $I->createSimpleFlow();
        $pageId = $I->getPageIdFromTitle($this->dataStore, 'simpleFlowTestNode-child1');
        $I->amOnPage('simpletestpath?startNodeId=' . $pageId);
        $I->waitForElement($priceSelector);
        $I->fillField($priceSelector, 1.01);
        $I->click('#skipToSummary');
        $I->waitForElement('#startOverButton');
        $I->click('#nodeTitle');
        $I->waitForText('simpleFlowTestNode');
        $I->click('#nextButton');
        $I->waitForElement($priceSelector);
        $I->seeInField($priceSelector, 10);
    }

    public function verifyFlowSettingsdisplayTotalPriceOnSummary(AcceptanceTester $I)
    {
        $priceSelector = '#customPrice';

        $I->createSimpleFlow();
        $flow = Flow::getExistingByTitle($this->dataStore, 'SimpleTestPath');
        $flow->displayTotalPriceOnSummary = false;
        $flow->updateDataStore();

        $I->amOnPage('simpletestpath');
        $I->waitForElement('#nextButton');
        $I->click('#skipToSummary');
        $I->waitForElement('#startOverButton');
        $I->dontSee('total');
    }

    public function verifyFlowSettingsDisplaySkipToSummary(AcceptanceTester $I)
    {
        $priceSelector = '#customPrice';

        $I->createSimpleFlow();
        $flow = Flow::getExistingByTitle($this->dataStore, 'SimpleTestPath');
        $flow->displaySkipToSummary = false;
        $flow->updateDataStore();

        $I->amOnPage('simpletestpath');
        $I->waitForElement('#nextButton');
        $I->dontSee('#skipToSummary');
    }

    public function completeSimpleFlow(AcceptanceTester $I)
    {
        $I->createSimpleFlow();

        $I->amOnPage('simpletestpath');
        $I->waitForElement('#nextButton', 5);
        $I->see('simpleFlowTestNode');
        $I->click('#nextButton');

        $I->waitForElement('#customPrice', 5);
        $I->see("simpleFlowTestNode-child1");
        $I->fillField('customPrice', 13.37);
        $I->click('#nextButton');

        $I->waitForElement('#defaultPrice', 5);
        $I->see("simpleFlowTestNode-child2");
        $I->click("#skipButton");

        $I->waitForElement('#startOverButton');

        $I->see('SimpleTestPath');

        $I->seeElement('//table/tbody/tr[1]', ['skipped'=>0]);
        $I->see('simpleFlowTestNode', '//table/tbody/tr[1]');
        $I->dontsee('$', '//table/tbody/tr[1]');

        $I->seeElement('//table/tbody/tr[2]', ['skipped'=>0]);
        $I->see('simpleFlowTestNode-child1', '//table/tbody/tr[2]');
        $I->see('13.37', '//table/tbody/tr[2]');

        $I->seeElement('//table/tbody/tr[3]', ['skipped'=>1]);
        $I->see('simpleFlowTestNode-child2', '//table/tbody/tr[3]');
        $I->dontSee('20.00', '//table/tbody/tr[3]');

        $I->seeElement('//table/tbody/tr[4]', ['id'=>'totalPrice']);
        $I->see("13.37", '//table/tbody/tr[4]');

        $I->click('#startOverButton');
        $I->waitForElement('#nextButton', 5);
        $I->see('simpleFlowTestNode');
    }
}
