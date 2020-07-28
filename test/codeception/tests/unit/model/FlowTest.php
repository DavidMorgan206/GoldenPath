<?php


use Stradella\GPaths\Flow;
use Stradella\GPaths\MockDataStore;
use Stradella\GPaths\Node;
use Stradella\GPaths\NodeFactory;
use Stradella\GPaths\NodeParent;

require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/MockDataStore.php');


class FlowTest extends \Codeception\Test\Unit
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

    public function testGetUniqueDefaultTitle()
    {
        $title = Flow::getUniqueDefaultTitle($this->mockDataStore);

        $this->tester->assertIsString($title);
        $this->tester->assertGreaterThan(0, strlen($title));
    }

    public function testDefaultState()
    {
        $flow = new Flow($this->mockDataStore);
        $flowTitle = 'test flow';
        $flow->title = $flowTitle;

        $json = $flow->getStateJSON();
        $this->tester->assertEquals($flowTitle, $json['flowTitle']);
        $this->tester->assertEquals('$', $json['currencySymbol']);
        $this->tester->assertEquals(true, $json['displayTotalPriceOnSummary']);
        $this->tester->assertEquals(true, $json['displaySkipToSummary']);
    }
}
