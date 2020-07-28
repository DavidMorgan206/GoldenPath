<?php

use Stradella\GPaths\MockDataStore;
use Stradella\GPaths\Node;
use Stradella\GPaths\NodeFactory;
use Stradella\GPaths\NodeParent;

require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/MockDataStore.php');


class NodeTest extends \Codeception\Test\Unit
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

    public function testCreateNewNode()
    {
        $testTitle = "test node";
        $node = new Node($this->mockDataStore);
        $node->title = $testTitle;

        $this->tester->assertEquals($node->title, $testTitle);
    }

    public function testUnsupportedNodeType()
    {
        $this->tester->expectThrowable(
            Exception::class,
            function () {
                NodeFactory::getNewNode($this->mockDataStore, "Foobar");
            }
        );
    }

    public function testSetNodeParent()
    {
        $child = NodeFactory::getNewNode($this->mockDataStore, "ManualNode");
        $child->title = "child";
        $child->parent = new NodeParent($this->mockDataStore);
    }

    /**
     * To avoid accidently calling Crud functions of the Node base class, the caller must always pass in $childCaller=true
     */
    public function testCrudFunctionsAreProtected()
    {
        $node = new Node($this->mockDataStore);
        $this->tester->expectThrowable(
            Exception::class,
            function () {
                (new Node($this->mockDataStore))->createInDataStore(false);
            }
        );
        $this->tester->expectThrowable(
            Exception::class,
            function () {
                (new Node($this->mockDataStore))->refreshFromDataStore(false);
            }
        );
        $this->tester->expectThrowable(
            Exception::class,
            function () {
                (new Node($this->mockDataStore))->updateDataStore(false);
            }
        );
        $this->tester->expectThrowable(
            Exception::class,
            function () {
                (new Node($this->mockDataStore))->deleteFromDataStore(false);
            }
        );
    }

    public function testGetChildren()
    {
        $node = new Node($this->mockDataStore);
        $reflectionClass = new ReflectionClass('Stradella\Gpaths\Node');
        $reflectionProperty = $reflectionClass->getProperty("children");
        $reflectionProperty->setAccessible(true);

        $testChildren = array(new Node($this->mockDataStore));
        $testChildren[0]->title = "testChild";
        $reflectionProperty->setValue($node, $testChildren);

        $reflectionClass->children = $testChildren;

        $this->tester->assertEquals($testChildren, $node->getChildren());
    }


    /*
     * If heading is null, title
     */
    public function testTitleIsUsedIfHeadingIsNull()
    {
        $node = NodeFactory::getNewNode($this->mockDataStore, 'AffiliateNode');
        $node->id = 0; //non-null makes the object think it has latest values from datasource
        $node->title = 'myTitle';
        $node->parent = new NodeParent($this->mockDataStore);
        $node->parent->sequence = 0;
        $node->parent->parentNodeId = null;

        $json = $node->getListItemJSON();
        $this->tester->assertEquals($node->title, $json['heading']);

        $node->heading = 'actualHeading';
        $json = $node->getListItemJSON();
        $this->tester->assertEquals($node->heading, $json['heading']);
    }
}