<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection DuplicatedCode */

use Codeception\Util\Locator;

require_once(dirname(__DIR__) . '\..\..\..\..\main\golden-paths\DataStore\DataStore.php');
require_once(dirname(__DIR__) . '\..\..\..\..\main\golden-paths\DataStore\MockDataStore.php');
require_once(dirname(__DIR__) . '\..\..\..\..\main\golden-paths\includes\AdminEditFlow.php');
require_once(dirname(__DIR__) . '\..\..\..\..\main\golden-paths\includes\AdminEditNode.php');
require_once(dirname(__DIR__) . '\..\..\..\..\main\golden-paths\includes\AdminFlowSummary.php');
use Stradella\GPaths\{DataStore, MockDataStore};
use Stradella\GPaths\{Node, ManualNode, LandingNode, NodeFactory, AffiliateNode, ListNode, NodeType};


class AdminViewEditNodeCest
{
    private $model;
    private $mockModel;

    public function _before(FunctionalTester $I)
    {
        $this->model = new DataStore();
        $this->model->clearAllData();
        $this->mockModel = new MockDataStore();
    }

    /**
     * @param FunctionalTester $I
     * @throws Exception
     *
     * Scenario: Admin User adds a new Node to an existing Flow
     * Given: An Admin User is viewing the Flow Summary for an existing Flow
     * When: The user clicks "Add Child"
     * Then: They should be able to modify defaults, save, and delete their new node
     */
    public function NodeCrudView(FunctionalTester $I)
    {
        $I->wantTo('Be able to create, modify, and delete a node from the Admin Flow Summary Page');

        //activate plugin
        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->canSeePluginInstalled('golden-paths');
        $I->activatePlugin('golden-paths');
        $I->canSeePluginActivated('golden-paths');

        //create a simple flow to add to
        $I->createSimpleFlow();

        //go to plugin settings
        $I->seeElement(\Codeception\Util\Locator::find('tr', ['data-slug'=>'golden-paths']));
        $I->click('Settings', \Codeception\Util\Locator::find('tr', ['data-slug'=>'golden-paths']));

        //verify flow summary
        $I->seeOptionIsSelected('flowId', 'SimpleTestPath');
        $I->see('Summary');

        foreach(NodeType::getUserCreatableNodeTypeTitles($this->model) as $nodeTypeTitle) {
            //create a local node object that we can use to store and test values against DB
            $node = NodeFactory::getNewNode($this->mockModel, $nodeTypeTitle);
            $testParent = NodeFactory::getByTitle($this->model, 'simpleFlowTestNode');
            $testParent2 = NodeFactory::getByTitle($this->model, 'simpleFlowTestNode-child1');

            //create a new child of landing node
            $I->click('Add Child', Locator::contains('tr', 'simpleFlowTestNode'));

            //pick node type
            $I->selectOption('nodeTypeTitle', $node->nodeType->title);
            $I->click('Select Type');
            $I->see('Page Type');
            $I->see(NodeFactory::getFriendlyTypeName($node->nodeType->title));

            //create node
            //verify default properties and populate our local test object
            foreach($node->getUpdatableProperties() as $property) {
                switch($property) {
                    case 'title':
                        $node->$property = "your title here {$nodeTypeTitle}"; //default
                        $I->fillField($property, $node->$property);
                        break;
                    case 'heading':
                        $node->$property = "your heading here{$nodeTypeTitle}";
                        $I->fillField($property, $node->$property);
                        break;
                    case 'allowCustomPrice':
                        if(strcmp($node->nodeType->title, 'AffiliateNode') != 0) {
                            $I->cantSeeCheckboxIsChecked($property);
                        }
                        else {
                           $I->cantSee('#allowCustomPrice');
                        }
                        break;
                    case 'childrenAreExclusive':
                        $node->$property = false; //default
                        $I->cantSeeCheckboxIsChecked($property);
                        break;
                    case 'skippable':
                        if(strcmp($node->nodeType->title, 'ListNode') != 0) { //Don't display skippable for ListNode, since it's children can't be visited individually
                            $node->$property = true; //default
                            $I->canSeeCheckboxIsChecked($property);
                        }
                        break;
                    case 'defaultPrice':
                        $node->$property = 0; //default
                        $I->seeInField($property, $node->$property);
                        break;
                    case 'linkPaneHtml':
                    case 'bodyPaneHtml':
                    case 'footerPaneHtml':
                    case 'imagePaneHtml':
                        $node->$property = ''; //default
                        $I->seeInField($property, $node->$property);
                        break;
                    case 'parent':
                        $node->setParent($testParent, 1);
                        $I->seeOptionIsSelected('nodeParentTitle', $testParent->title);
                        break;
                    case 'sequence':
                        $node->setParent($testParent, 1);
                        $I->seeInField($property, 1);
                        break;
                    case 'id': //not visible to user
                        break;
                    default:
                        throw new Exception('untested property ' . $property);
                }
            }
            $I->click('Create Page');

            //verify flow summary
            $I->see('Operation Result: success');
            $I->see('Golden Paths');
            $I->see($node->title);

            //edit node
            $I->click('Edit', Locator::contains('tr', $node->title));

            //verify defaults persisted and then modify all fields
            foreach($node->getUpdatableProperties() as $property) {
                switch($property) {
                    case 'title':
                        $I->seeInField($property, $node->$property);
                        $node->$property = "your title here 2{$nodeTypeTitle}"; //default
                        $I->fillField($property, $node->$property);
                        break;
                    case 'heading':
                        $I->seeInField($property, $node->$property);
                        $node->$property = "your heading here 2{$nodeTypeTitle}"; //default
                        $I->fillField($property, $node->$property);
                        break;
                    case 'allowCustomPrice':
                        if(strcmp($node->nodeType->title, 'AffiliateNode') != 0) {
                            $I->cantSeeCheckboxIsChecked($property);
                            $node->$property = true;
                            $I->checkOption($property);
                        }
                        else {
                            $I->cantSee('#allowCustomPrice');
                        }
                        break;
                    case 'childrenAreExclusive':
                        $I->cantSeeCheckboxIsChecked($property);
                        $node->$property = true;
                        $I->checkOption($property);
                        break;
                    case 'skippable':
                        if(strcmp($node->nodeType->title, 'ListNode') != 0) { //Don't display skippable for ListNode, since it's children can't be visited individually
                            $I->canSeeCheckboxIsChecked($property);
                            $node->$property = false;
                            $I->uncheckOption($property);
                        }
                        break;
                    case 'defaultPrice':
                        $I->seeInField($property, $node->$property);
                        $node->$property = 10.32; //default
                        $I->fillField($property, $node->$property);
                        break;
                    case 'imagePaneHtml':
                        $I->seeInField($property, $node->$property);
                        $node->$property = 'image test'; //default
                        $I->fillField($property, $node->$property);
                        break;
                    case 'footerPaneHtml':
                        $I->seeInField($property, $node->$property);
                        $node->$property = 'footer test'; //default
                        $I->fillField($property, $node->$property);
                        break;
                    case 'linkPaneHtml':
                        $I->seeInField($property, $node->$property);
                        $node->$property = 'link test'; //default
                        $I->fillField($property, $node->$property);
                        break;
                    case 'bodyPaneHtml':
                        $I->seeInField($property, $node->$property);
                        $node->$property = 'body test'; //default
                        $I->fillField($property, $node->$property);
                        break;
                    case 'parent':
                        $I->seeOptionIsSelected('nodeParentTitle', $testParent->title);
                        $node->setParent($testParent2, 2);
                        $I->selectOption('nodeParentTitle', $testParent2->title);
                        break;
                    case 'sequence':
                        //this gets updated in our local node object with the parent call
                        $I->seeInField($property, 1);
                        break;
                    case 'id': //not visible to user
                        break;
                    default:
                        throw new Exception('untested property ' . $property);
                }
            }
            $I->click('Update Page');

            //verify flow summary
            $I->see('Operation Result: success');
            $I->see('Golden Paths');
            $I->see($node->title);

            //go to edit node tab (to verify edits)
            $I->click('Edit', Locator::contains('tr', $node->title));
            $I->see('Edit Existing Page');
            foreach($node->getUpdatableProperties() as $property) {
                switch($property) {
                    case 'bodyPaneHtml':
                    case 'linkPaneHtml':
                    case 'imagePaneHtml':
                    case 'footerPaneHtml':
                    case 'defaultPrice':
                    case 'heading':
                    case 'title':
                        $I->seeInField($property, $node->$property);
                        break;
                    case 'childrenAreExclusive':
                        $I->canSeeCheckboxIsChecked($property);
                    case 'allowCustomPrice':
                        if(strcmp($node->nodeType->title, 'AffiliateNode') != 0) {
                           $I->canSeeCheckboxIsChecked($property);
                        }
                        else {
                            $I->cantSee('#allowCustomPrice');
                        }
                        break;
                    case 'skippable':
                        if(strcmp($node->nodeType->title, 'ListNode') != 0) { //Don't display skippable for ListNode, since it's children can't be visited individually
                            $I->cantSeeCheckboxIsChecked($property);
                        }
                        else {
                            $I->cantSee('#skippable');
                        }
                        break;
                    case 'parent':
                        $I->seeOptionIsSelected('nodeParentTitle', $testParent2->title);
                        break;
                    case 'sequence':
                        //this gets updated in our local node object with the parent call
                        $I->seeInField($property, 2);
                        break;
                    case 'id': //not visible to user
                        break;
                    default:
                        throw new Exception('untested property ' . $property);
                }
            }
            $I->click('Cancel');

            //Delete Node
            $I->see('Golden Paths');
            $I->click('Delete', Locator::contains('tr', $node->title));
            $I->see('Golden Paths');
            $I->dontSee($node->title);
        }
    }

    /**
     * @param FunctionalTester $I
     * @throws Exception
     *
     * Scenario: Admin User modifies an existing root node
     * Given: An Admin User is viewing the Flow Summary for an existing Flow
     * When: The user clicks "edit" for an existing root node
     * Then: They can modify existing values
     */
    public function modifyRootNode(FunctionalTester $I)
    {
        $I->wantTo('Be able to modify a root node');

        //activate plugin
        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->canSeePluginInstalled('golden-paths');
        $I->activatePlugin('golden-paths');
        $I->canSeePluginActivated('golden-paths');

        //create a simple flow to add to
        $I->createSimpleFlow();

        //go to plugin settings
        $I->seeElement(\Codeception\Util\Locator::find('tr', ['data-slug'=>'golden-paths']));
        $I->click('Settings', \Codeception\Util\Locator::find('tr', ['data-slug'=>'golden-paths']));

        //verify flow summary
        $I->seeOptionIsSelected('flowId', 'SimpleTestPath');
        $I->see('Summary');

        //create a node object that we can use to store and test values against DB
        $node = NodeFactory::getByTitle($this->model, 'simpleFlowTestNode');

        //Go to edit node page
        $I->click('Edit', Locator::contains('tr', 'simpleFlowTestNode'));
        $I->see('Edit Existing Page');

        //Verify title field and change it
        $I->seeInField('title', $node->title);
        $node->title = 'updated title value';
        $I->fillField('title', $node->title);
        $I->click('Update Page');

        //go back to edit node page and verify edits saved
        $I->see('Path Summary');
        $I->click('Edit', Locator::contains('tr', $node->title));
        $I->see('Edit Existing Page');
        $I->seeInField('title', $node->title);
    }

}

