<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Exception;
use Stradella\GPaths\DataStoreInterface;
use Stradella\GPaths\{NodeFactory, AffiliateNode};

class Acceptance extends \Codeception\Module
{

    public function createSimpleFlow()
    {
        include_once 'debug_tools.php';
        create_simple_flow();

    }

    public function CreateKitchenStuffFlow()
    {
        include_once 'debug_tools.php';
        create_kitchen_stuff_flow();

    }

    public function CreateListNodeFlow()
    {
        include_once 'debug_tools.php';
        create_list_node_flow();
    }

    public function createSimpleListNodeFlow()
    {

        include_once 'debug_tools.php';
        create_simple_list_node_flow();
    }

    /**
     * @param DataStoreInterface $dataStore
     * @param string $title
     * @return int
     * @throws Exception
     */
    public function getPageIdFromTitle(DataStoreInterface $dataStore, string $title) : int
    {
       $node = NodeFactory::getByTitle($dataStore, $title);
        return $node->id;
    }

}
