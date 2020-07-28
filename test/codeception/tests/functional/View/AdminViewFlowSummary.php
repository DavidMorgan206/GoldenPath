<?php

/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection DuplicatedCode */

use Codeception\Util\Locator;

require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/MockDataStore.php');
require_once(dirname(__DIR__) . '/../../../..\main\golden-paths\includes\AdminEditFlow.php');
require_once(dirname(__DIR__) . '/../../../..\main\golden-paths\includes\AdminEditNode.php');
require_once(dirname(__DIR__) . '/../../../..\main\golden-paths\includes\AdminFlowSummary.php');

use Stradella\GPaths\{DataStore, MockDataStore};
use Stradella\GPaths\{Node, ManualNode, LandingNode, NodeFactory, AffiliateNode, ListNode, NodeType};


class AdminViewFlowSummary
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
     * Scenario: Admin User views Flow Summary on plugin Settings page
     * Given: An Admin User is logged in with an existing flow
     * When: The user clicks "settings"
     * Then: They should be able see existing flow and flow summary
     */
    public function viewFlowSummary(FunctionalTester $I)
    {
        $I->wantTo('Be able to view FlowSummar');

        //activate plugin
        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->canSeePluginInstalled('golden-paths');
        $I->activatePlugin('golden-paths');
        $I->canSeePluginActivated('golden-paths');

        //create a simple flow to add to
        $I->createSimpleFlow();

        //go to plugin settings
        $I->seeElement(\Codeception\Util\Locator::find('tr', ['data-slug' => 'golden-paths']));
        $I->click('Settings', \Codeception\Util\Locator::find('tr', ['data-slug' => 'golden-paths']));

        //verify flow summary
        $I->seeOptionIsSelected('flowId', 'SimpleTestPath');
        $I->see('Summary');

    }
}
