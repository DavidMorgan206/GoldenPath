<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Functional extends \Codeception\Module
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

    public function createSimpleListNodeFlow()
    {

        include_once 'debug_tools.php';
        create_simple_list_node_flow();
    }

    
    

}

