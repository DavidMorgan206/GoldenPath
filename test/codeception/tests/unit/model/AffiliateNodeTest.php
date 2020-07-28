<?php


use Stradella\GPaths\MockDataStore;
use Stradella\GPaths\Node;
use Stradella\GPaths\NodeFactory;
use Stradella\GPaths\NodeParent;

require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/MockDataStore.php');


class AffiliateNodeTest extends \Codeception\Test\Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;
    private $mockDataStore;

    protected function _after()
    {
    }

    public function _before()
    {
        $this->mockDataStore = new MockDataStore();
    }

    /**
     * @throws Exception
     *
     * Validates and corrects and problems with the node configuration
     *
     * Insures that customPrice can't be turned on for affiliate nodes, which violates policy for many affiliate programs.
     * Don't just hardcode it in case Admins want the capability regardless
     */
    public function testValidateNodeConfiguration()
    {
        $node = NodeFactory::getNewNode($this->mockDataStore, 'AffiliateNode');
        $node->allowCustomPrice = true; //invalid config

        $reflectionClass = new ReflectionClass('Stradella\Gpaths\AffiliateNode');
        $reflectionMethod = $reflectionClass->getMethod('validateNodeConfiguration');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invoke($node);

        $this->tester->assertEquals(false, $node->allowCustomPrice);
    }

    /**
     * @throws Exception
     *
     * getPrice should always return null until special casing for each affiliate program's policy is implemented
     */
    public function testGetPriceReturnsNull()
    {

        $node = NodeFactory::getNewNode($this->mockDataStore, 'AffiliateNode');

        $this->tester->assertEquals(null, $node->getPrice());
    }

    /*
     * Verifies defaults get used for optional parameters
     */
    public function testDefaultStateJson()
    {
        $node = NodeFactory::getNewNode($this->mockDataStore, 'AffiliateNode');
        $node->id = 0; //non-null makes the object think it has latest values from datasource
        $node->title = '';
        $node->skippable = false;
        $node->childrenAreExclusive = false;
        $node->parent = new NodeParent($this->mockDataStore);
        $node->parent->sequence = 0;
        $node->parent->parentNodeId = null;

        $json = $node->getStateJSON();

        $this->tester->assertEquals(null, $json['price']);
        $this->tester->assertEquals(false, $json['allowCustomPrice']);
        $this->tester->assertEquals('', $json['linkPaneHtml']);
        $this->tester->assertEquals('', $json['bodyPaneHtml']);
        $this->tester->assertEquals('', $json['imagePaneHtml']);
        $this->tester->assertEquals('', $json['footerPaneHtml']);
    }

    /*
     * If imagePaneHtml is null, linkPaneHtml should be returned in it's place
     */
    public function testStateJsonWithNullImagePaneHtml()
    {
        $node = NodeFactory::getNewNode($this->mockDataStore, 'AffiliateNode');
        $node->id = 0; //non-null makes the object think it has latest values from datasource
        $node->title = '';
        $node->parent = new NodeParent($this->mockDataStore);
        $node->parent->sequence = 0;
        $node->parent->parentNodeId = null;
        $node->imagePaneHtml = 'realImagePaneValue';

        $json = $node->getStateJSON();
        $this->tester->assertEquals('realImagePaneValue', $json['imagePaneHtml']);

        $node->linkPaneHtml = 'test';
        $node->imagePaneHtml = '';
        $json = $node->getStateJSON();
        $this->tester->assertEquals('test', $json['imagePaneHtml']);
    }



    /*
     *  verify defaults get used for optional parameters
     */
    public function testDefaultListItemJson()
    {
        $node = NodeFactory::getNewNode($this->mockDataStore, 'AffiliateNode');
        $node->id = 0; //non-null makes the object think it has latest values from datasource
        $node->title = '';
        $node->skippable = false;
        $node->childrenAreExclusive = false;
        $node->parent = new NodeParent($this->mockDataStore);
        $node->parent->sequence = 0;
        $node->parent->parentNodeId = null;

        $json = $node->getListItemJSON();

        $this->tester->assertEquals(false, $json['allowCustomPrice']);
        $this->tester->assertEquals('', $json['linkPaneHtml']);
        $this->tester->assertEquals('', $json['bodyPaneHtml']);
        $this->tester->assertEquals('', $json['imagePaneHtml']);
        $this->tester->assertEquals('', $json['footerPaneHtml']);
        $this->tester->assertEquals('', $json['title']); //validate one property from parent class to make sure it's getting called
    }

    public function testListItemJson()
    {
        $node = NodeFactory::getNewNode($this->mockDataStore, 'AffiliateNode');
        $node->id = 0; //non-null makes the object think it has latest values from datasource
        $node->title = '';
        $node->skippable = false;
        $node->childrenAreExclusive = false;
        $node->parent = new NodeParent($this->mockDataStore);
        $node->parent->sequence = 0;
        $node->parent->parentNodeId = null;

        $json = $node->getListItemJSON();

        $this->tester->assertEquals(false, $json['allowCustomPrice']);
        $this->tester->assertEquals('', $json['linkPaneHtml']);
        $this->tester->assertEquals('', $json['bodyPaneHtml']);
        $this->tester->assertEquals('', $json['imagePaneHtml']);
        $this->tester->assertEquals('', $json['footerPaneHtml']);
        $this->tester->assertEquals('', $json['title']); //validate one property from parent class to make sure it's getting called
    }


    /*
     * If imagePaneHtml is null, linkPaneHtml should be returned in it's place
     */
    public function testListNodeJsonWithNullImagePaneHtml()
    {
        $node = NodeFactory::getNewNode($this->mockDataStore, 'AffiliateNode');
        $node->id = 0; //non-null makes the object think it has latest values from datasource
        $node->title = '';
        $node->parent = new NodeParent($this->mockDataStore);
        $node->parent->sequence = 0;
        $node->parent->parentNodeId = null;
        $node->imagePaneHtml = 'realImagePaneValue';

        $json = $node->getListItemJSON();
        $this->tester->assertEquals('realImagePaneValue', $json['imagePaneHtml']);

        $node->linkPaneHtml = 'test';
        $node->imagePaneHtml = '';
        $json = $node->getListItemJSON();
        $this->tester->assertEquals('test', $json['imagePaneHtml']);
    }
}
