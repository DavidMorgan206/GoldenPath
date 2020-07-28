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


class AdminViewEditFlowCest
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
     *
     * //TODO: fiddle with encoding to allow codeception to verify html code within the wp_editor control
     * @param FunctionalTester $I
     * @throws Exception
     *
     * Scenario: Admin User creates a new Flow
     * Given: An Admin User is viewing the Flow Summary page
     * When: The user clicks "New Flow"
     * Then: They can modify defaults, save, and delete their Flow
     */
    public function flowCrudView(FunctionalTester $I)
    {
        $I->wantTo('Be able to CRUD a flow');

        //activate plugin
        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->canSeePluginInstalled('golden-paths');
        $I->activatePlugin('golden-paths');
        $I->canSeePluginActivated('golden-paths');

        //go to plugin settings
        $I->seeElement(\Codeception\Util\Locator::find('tr', ['data-slug'=>'golden-paths']));
        $I->click('Settings', \Codeception\Util\Locator::find('tr', ['data-slug'=>'golden-paths']));

        //verify flow summary
        $I->see('Golden Paths');
        $I->cantSee('Summary');

        //create flow
        $I->click('Create New Path');
        $I->canSee('Create New Path');
        $I->fillField('flowTitle', 'test flow title');
        $I->fillField('summaryTitle', 'test summaryTitle');
        $I->fillField('summaryBody', 'summaryBody');
        $I->click('Create Path');

        //verify flow summary
        $I->see('Operation Result: success');
        $I->see('Golden Paths');

        //summary table stuff
        $I->see('Summary');
        $I->see('New Golden Path'); //default landing node title
        $I->see(NodeFactory::getFriendlyTypeName('LandingNode')); //landing node type
        $I->seeLink('Add Child');
        $I->seeLink('Edit');
        $I->dontSeeLink('Delete'); //only node in summary will be root landing node, which can't be deleted directly (gets deleted when flow deleted)

        //edit flow
        $I->click('Edit Path');
        $I->seeInField('flowTitle', 'test flow title');
        $I->fillField('flowTitle', 'test flow title 2');
        $I->seeInField('summaryTitle', 'test summaryTitle');
        $I->fillField('summaryTitle', 'test summaryTitle 2');
        //$I->seeInField('summaryBody', 'summaryBody');
        //$I->fillField('summaryBody', 'summaryBody2');
        $I->seeCheckboxIsChecked('displayTotalPriceOnSummary');
        $I->uncheckOption('displayTotalPriceOnSummary');
        $I->seeCheckboxIsChecked('displaySkipToSummary');
        $I->uncheckOption('displaySkipToSummary');
        $I->click('Update Path');

        //verify edits
        //verify flow summary
        $I->see('Operation Result: success');
        $I->click('Edit Path');
        $I->seeInField('flowTitle', 'test flow title 2');
        //$I->seeInField('summaryBody', 'summaryBody2');
        $I->dontSeeCheckboxIsChecked('displaySkipToSummary');
        $I->dontSeeCheckboxIsChecked('displayTotalPriceOnSummary');
        $I->click('Cancel');

        //verify flow summary
        $I->dontSee('Operation Result');
        $I->see('Golden Paths');

        //delete the flow
        $I->seeOptionIsSelected('flowId', 'test flow title 2');
        $I->click('Delete Path');
        $I->see('Operation Result: success');
        $I->dontSee('flowTitle');
    }
}
