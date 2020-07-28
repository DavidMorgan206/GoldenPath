<?php


use Stradella\GPaths\MockDataStore;
use Stradella\GPaths\Node;
use Stradella\GPaths\NodeFactory;
use Stradella\GPaths\NodeParent;

require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/MockDataStore.php');


class SessionChoiceTest extends \Codeception\Test\Unit
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
}
