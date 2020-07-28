<?php
/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection DuplicatedCode */

use Codeception\Util\Locator;

require_once(dirname(__DIR__) . '/../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../main/golden-paths/DataStore/MockDataStore.php');

use Stradella\GPaths\{DataStore, MockDataStore};

/**
 * Class ClientViewCest
 *
 * requires existing pages created in wordpress with : {shortcode path_title}
 * simpletestflow :simpleTestFlow
 * kitchen:kitchenStuffFlow
 * listNodeFlow:listNodeFlow
 */
class ViewElementsRenderCest
{
    private $dataStore;
    private $mockDataStore;

    public function _before(AcceptanceTester $I)
    {
        $this->dataStore = new DataStore();
        $this->dataStore->clearAllData();
        $this->mockDataStore = new MockDataStore();
    }


    /**
     * @param AcceptanceTester $I
     * @throws Exception
     */
    public function landingNode(AcceptanceTester $I)
    {
        //create a simple flow
        $I->createSimpleFlow();
        $I->amOnPage('simpletestpath');
        $I->waitForElement('#nextButton', 5);
        $I->see('simpleFlowTestNode');
    }

    public function manualNodeElements(AcceptanceTester $I)
    {
        $I->CreateKitchenStuffFlow();
        $pageId = $I->getPageIdFromTitle($this->dataStore, 'Knife');
        $I->amOnPage('kitchen?startNodeId=' . $pageId);

        $I->waitForElement('#nextButton');
        $I->see('Knife');
        $I->seeInField('customPrice', 39.99);
        $I->seeElement('#skipButton');
    }

    public function affiliateNodeElements(AcceptanceTester $I)
    {
        $I->CreateListNodeFlow();
        $pageId = $I->getPageIdFromTitle($this->dataStore, 'Dish Towel');
        $I->amOnPage('listNodeFlow?startNodeId=' . $pageId);
        $I->waitForElement('#nextButton');
        $I->see('Dish Towel');
        $I->dontSee("#customPrice");
        $I->see('Lorem');
        $I->see('link pane html test content');
    }

    public function listNodeElements(AcceptanceTester $I)
    {
        $I->CreateListNodeFlow();
        $pageId = $I->getPageIdFromTitle($this->dataStore, 'KnifeStuffListNode');
        $I->amOnPage('listNodeFlow?startNodeId=' . $pageId);
        $I->waitForElement('#nextButton');
        $I->see('NeatKnife');
        $I->see('kni2fe block');
        $I->see('Knife Sharpener');
    }
}
