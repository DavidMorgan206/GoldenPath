<?php


use Stradella\GPaths\GpathsBaseObject;
use Stradella\GPaths\MockDataStore;
use Stradella\GPaths\Node;
use Stradella\GPaths\NodeFactory;
use Stradella\GPaths\NodeParent;

require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/MockDataStore.php');


class BaseObjectTest extends \Codeception\Test\Unit
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

    public function testVerifyObjectsEqual()
    {
        $node = new Node($this->mockDataStore);
        $node->title = "foo";
        $node2 = new Node($this->mockDataStore);
        $node2->title = "foo";
        $baseNode = new GpathsBaseObject();

        $reflectionClass = new ReflectionClass('Stradella\Gpaths\GpathsBaseObject');
        $reflectionMethod = $reflectionClass->getMethod('verifyObjectsEqual');
        $reflectionMethod->setAccessible(true);

        //no exception means they're equal
        $reflectionMethod->invokeArgs($node, array($node, $node2));
    }

    public function testVerifyObjectsNotEqualWithDifferentValue()
    {
        $node = new Node($this->mockDataStore);
        $node->title = "foo";
        $node2 = new Node($this->mockDataStore);
        $node2->title ="foo2";
        $baseNode = new GpathsBaseObject();

        $reflectionClass = new ReflectionClass('Stradella\Gpaths\GpathsBaseObject');
        $reflectionMethod = $reflectionClass->getMethod('verifyObjectsEqual');
        $reflectionMethod->setAccessible(true);

        $this->tester->expectThrowable(Exception::class, function() use ($node2, $node, $reflectionMethod) {
            $reflectionMethod->invokeArgs($node, array($node, $node2));
        });
    }

    public function testVerifyObjectsNotEqualWithMissingValue()
    {
        $node = new Node($this->mockDataStore);
        $node->title = "foo";
        $node2 = new Node($this->mockDataStore);
        $node2->title = "foo";
        $node2->id = 1;
        $baseNode = new GpathsBaseObject();

        $reflectionClass = new ReflectionClass('Stradella\Gpaths\GpathsBaseObject');
        $reflectionMethod = $reflectionClass->getMethod('verifyObjectsEqual');
        $reflectionMethod->setAccessible(true);

        $this->tester->expectThrowable(Exception::class, function() use ($node2, $node, $reflectionMethod) {
            $reflectionMethod->invokeArgs($node, array($node, $node2));
        });
    }
}
