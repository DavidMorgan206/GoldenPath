<?php /** @noinspection DuplicatedCode */


require_once('c:\dev\GoldenPaths/main/golden-paths/DataStore/DataStore.php');
use Stradella\GPaths\{Node, Flow, Session, DataStoreInterface, DataStore, LandingNode, ManualNode, AffiliateNode, ListNode};
#keep this clean of wordpress / codeception dependencies so commands can be invoked from command line for manual debugging

// here you can define custom actions
// all public methods declared in helper class will be available in $I

/**
 * @throws Exception
 */
function populate_test_content()
{
    create_simple_flow();
    create_list_node_flow();
    create_kitchen_stuff_flow();
    create_simple_list_node_flow();
    require_once('CreateDogDuesSampleSite.php');
    create_dog_dues_sample_site();
}

/**
 * @throws Exception
 */
function create_simple_flow()
{   
    $dataStore =  new DataStore();

    $head = new LandingNode($dataStore);
    $head->childrenAreExclusive = false;
    $head->skippable=false;
    $head->bodyPaneHtml=("This is a landing nodes bodyPaneHtml!");
    $head->title = "simpleFlowTestNode";
    $head->createInDataStore();

    $child1 = new ManualNode($dataStore);
    $child1->defaultPrice=10;
    $child1->allowCustomPrice=true;
    $child1->bodyPaneHtml=("This is a manual nodes bodyPaneHtml!");
    $child1->childrenAreExclusive = true;
    $child1->skippable=true;
    $child1->title = ("simpleFlowTestNode-child1");
    $child1->setParent($head, 1);
    $child1->createInDataStore();

    $child2 = new ManualNode($dataStore);
    $child2->defaultPrice=20;
    $child1->allowCustomPrice=false;
    $child2->bodyPaneHtml=("This is a manual 2 nodes bodyPaneHtml!");
    $child2->childrenAreExclusive = true;
    $child2->skippable=true;
    $child2->title = ("simpleFlowTestNode-child2");
    $child2->setParent($head, 2);
    $child2->createInDataStore();

    $flow = new Flow($dataStore);
    $flow->id = $head->id;
    $flow->title = "SimpleTestPath";
    $flow->currencySynbol = "$";
    $flow->createInDataStore();
}

/**
 * @param DataStoreInterface $dataStore
 * @param string $table_name
 * @throws Exception
 */
function delete_table_data(DataStoreInterface $dataStore, string $table_name)
{
    /** @noinspection SqlWithoutWhere */
    $dataStore->select(
        'DELETE FROM ' . $table_name,
           ['trustMe'=>true]
    );
}

/**
 * @throws Exception
 */
function delete_all_table_data()
{
    $dataStore = new DataStore();

    delete_table_data($dataStore, $dataStore->sessionChoicesTableName);
    delete_table_data($dataStore, $dataStore->sessionsTableName);
    delete_table_data($dataStore, $dataStore->flowsTableName);
    delete_table_data($dataStore, $dataStore->nodeChildrenTableName);
    delete_table_data($dataStore, $dataStore->manualNodesTableName);
    delete_table_data($dataStore, $dataStore->affiliateNodesTableName);
    delete_table_data($dataStore, $dataStore->nodeChildrenTableName);
    delete_table_data($dataStore, $dataStore->listNodesTableName);
    delete_table_data($dataStore, $dataStore->landingNodesTableName);
    delete_table_data($dataStore, $dataStore->nodesTableName);

}

/**
 * @throws Exception
 */
function create_simple_list_node_flow()
{

    $dataStore = new DataStore();

    $head = new LandingNode($dataStore);
    $head->childrenAreExclusive = false;
    $head->skippable=false;
    $head->bodyPaneHtml=("This is a landing nodes description!");
    $head->title = "simpleFlowListtest-landing";
    $head->createInDataStore();

    $listNode = new ListNode($dataStore);
    $listNode->childrenAreExclusive = false;
    $listNode->skippable=false;
    $listNode->bodyPaneHtml=("This is a list nodes description!  Look at all the cool stuff below!");
    $listNode->title = "simpleFlowListtest-listnode";
    $listNode->setParent($head, 1);
    $listNode->createInDataStore();

    $child1 = new ManualNode($dataStore);
    $child1->defaultPrice=10;
    $child1->allowCustomPrice=true;
    $child1->bodyPaneHtml=("This is a manual nodes description!");
    $child1->childrenAreExclusive = true;
    $child1->skippable=true;
    $child1->title ="simpleFlowListNodeTestNode-child1";
    $child1->setParent($listNode, 1);
    $child1->createInDataStore();

    $child2 = new AffiliateNode($dataStore);
    $child1->allowCustomPrice=false;
    $child2->bodyPaneHtml=("This is a manual 2 nodes description!");
    $child2->title = "simpleFlowListNodeTestNode-child2";
    $child2->setParent($listNode, 2);
    $child2->createInDataStore();

    $flow = new Flow($dataStore);
    $flow->id = $head->id;
    $flow->title = "SimpleListNodeFlow";
    $flow->createInDataStore();
}

/**
 * @throws Exception
 */
function create_kitchen_stuff_flow()
{
    $dataStore = new DataStore();

    $head = new LandingNode($dataStore);
    $head->childrenAreExclusive = false;
    $head->skippable=false;
    $head->bodyPaneHtml=("Lets pick out some KitchenStuff! Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur. At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $head->title = "KitchenStuff";
    $head->createInDataStore();

    $knife = new ManualNode($dataStore);
    $knife->childrenAreExclusive = true;
    $knife->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur. At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $knife->defaultPrice=39.99;
    $knife->allowCustomPrice=true;
    $knife->linkPaneHtml='http://localhost/wp-content/uploads/2019/12/knife.jpg';
    $knife->skippable=true;
    $knife->title = "Knife";
    $knife->setParent($head, 1);
    $knife->createInDataStore();

    $knifeblock = new ManualNode($dataStore);
    $knifeblock->skippable=true;
    $knifeblock->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur. At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $knifeblock->defaultPrice=29.99;
    $knifeblock->allowCustomPrice=true;
    $knifeblock->linkPaneHtml='http://localhost/wp-content/uploads/2019/12/knifeblock.jpg';
    $knifeblock->title = "knife block";
    $knifeblock->setParent($knife, 1);
    $knifeblock->createInDataStore();

    $magneticrack = new ManualNode($dataStore);
    $magneticrack->skippable=true;
    $magneticrack->title = "Magnetic Rack";
    $magneticrack->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur. At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $magneticrack->defaultPrice=13.99;
    $magneticrack->allowCustomPrice=true;
    $magneticrack->linkPaneHtml='http://localhost/wp-content/uploads/2019/12/magnetickniferack.jpg';
    $magneticrack->setParent($knife, 2);
    $magneticrack->createInDataStore();

    $saucepan = new ManualNode($dataStore);
    $saucepan->childrenAreExclusive = true;
    $saucepan->skippable=true;
    $saucepan->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur. At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $saucepan->defaultPrice=23.99;
    $saucepan->allowCustomPrice=true;
    $saucepan->linkPaneHtml='http://localhost/wp-content/uploads/2019/12/saucepan.jpg';
    $saucepan->title = "Saucepan";
    $saucepan->setParent($head, 2);
    $saucepan->createInDataStore();

    $cuttingboard = new ManualNode($dataStore);
    $cuttingboard->childrenAreExclusive = true;
    $cuttingboard->skippable=true;
    $cuttingboard->bodyPaneHtml=("to put things on");
    $cuttingboard->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur. At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $cuttingboard->defaultPrice=49.99;
    $cuttingboard->allowCustomPrice=true;
    $cuttingboard->linkPaneHtml='http://localhost/wp-content/uploads/2019/12/cuttingboard.jpg';
    $cuttingboard->title = "cutting board";
    $cuttingboard->setParent($head, 3);
    $cuttingboard->createInDataStore();

    $cuttingboardoil = new ManualNode($dataStore);
    $cuttingboardoil->childrenAreExclusive = true;
    $cuttingboardoil->skippable=true;
    $cuttingboardoil->bodyPaneHtml=("for the board");
    $cuttingboardoil->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur. At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $cuttingboardoil->defaultPrice=9.99;
    $cuttingboardoil->allowCustomPrice=true;
    $cuttingboardoil->linkPaneHtml='http://localhost/wp-content/uploads/2019/12/cuttingboardoil.jpg';
    $cuttingboardoil->title = "cutting board oil";
    $cuttingboardoil->setParent($cuttingboard, 1);
    $cuttingboardoil->createInDataStore();

    $cuttingboardoilholder = new ManualNode($dataStore);
    $cuttingboardoilholder->childrenAreExclusive = true;
    $cuttingboardoilholder->skippable=true;
    $cuttingboardoilholder->bodyPaneHtml=("for the oil");
    $cuttingboardoilholder->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur. At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $cuttingboardoilholder->defaultPrice=19.99;
    $cuttingboardoilholder->allowCustomPrice=true;
    $cuttingboardoilholder->linkPaneHtml='http://localhost/wp-content/uploads/2019/12/holder.jpg';
    $cuttingboardoilholder->title = "cutting board oil holder";
    $cuttingboardoilholder->setParent($cuttingboardoil, 1);
    $cuttingboardoilholder->createInDataStore();

    $flow = new Flow($dataStore);
    $flow->summaryTitle='Here is your shopping list!';
    $flow->summaryBody=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur.  At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $flow->title = "KitchenStuff";
    $flow->id = $head->id;
    $flow->createInDataStore();
}

/**
 * @param string $path_title
 * @param string $node_title
 * @throws Exception
 */
function create_session_on_this_node(string $path_title, string $node_title)
{
    /*
   $options = getopt("", array("path_title:", "current_node_title"));
   $path_title = $options['path_title'];
   $node_title = $options['node_title'];
    */

   $dataStore = new DataStore();
   $session = new Session($dataStore);
   $session->title = 'debug_tools.php test session';
   $session->flow = Flow::getExistingByTitle($dataStore, $path_title);
   $session->cookieId = md5(uniqid(rand(), true));
   $session->createInDataStore();
   $node = \Stradella\GPaths\NodeFactory::getByTitle($dataStore, $node_title);

   echo "sessionId={$session->id}&currentNodeId={$node->id}";
}
/**
 * @throws Exception
 */
function create_list_node_flow()
{
    $dataStore = new DataStore();

    $head = new LandingNode($dataStore);
    $head->childrenAreExclusive = false;
    $head->skippable=false;
    $head->bodyPaneHtml=("Lets pick out some CookingStuff! Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur. At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $head->title = "CookingStuff";
    $head->createInDataStore();

    $knifeStuff = new ListNode($dataStore);
    $knifeStuff->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur. At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?");
    $knifeStuff->title = "KnifeStuffListNode";
    $knifeStuff->setParent($head, 1);
    $knifeStuff->createInDataStore();

    $knife = new ManualNode($dataStore);
    $knife->childrenAreExclusive = true;
    $knife->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. ");
    $knife->defaultPrice=39.99;
    $knife->allowCustomPrice=true;
    $knife->linkPaneHtml='<img src="http://localhost/wp-content/uploads/2019/12/knife.jpg />';
    $knife->skippable=true;
    $knife->title = "NeatKnife";
    $knife->setParent($knifeStuff, 1);
    $knife->createInDataStore();

    $knifeblock = new ManualNode($dataStore);
    $knifeblock->skippable=true;
    $knifeblock->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. ");
    $knifeblock->defaultPrice=29.99;
    $knifeblock->allowCustomPrice=true;
    $knifeblock->linkPaneHtml='<img src="http://localhost/wp-content/uploads/2019/12/knifeblock.jpg/>';
    $knifeblock->title = "kni2fe block";
    $knifeblock->setParent($knifeStuff, 2);
    $knifeblock->createInDataStore();

    $magneticrack = new ManualNode($dataStore);
    $magneticrack->skippable=true;
    $magneticrack->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. ");
    $magneticrack->defaultPrice=13.99;
    $magneticrack->allowCustomPrice=false;
    $magneticrack->linkPaneHtml='<img src="http://localhost/wp-content/uploads/2019/12/magnetickniferack.jpg/>';
    $magneticrack->title = "Ma2gnetic Rack";
    $magneticrack->setParent($knifeStuff, 3);
    $magneticrack->createInDataStore();

    $knifesharpener = new AffiliateNode($dataStore);
    $knifesharpener->skippable=true;
    $knifesharpener->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. ");
    $knifesharpener->linkPaneHtml='link pane <b>html</b> test content';
    $knifesharpener->allowCustomPrice=false;
    $knifesharpener->title = "Knife Sharpener";
    $knifesharpener->setParent($knifeStuff, 4);
    $knifesharpener->createInDataStore();

    $dishtowel = new AffiliateNode($dataStore);
    $dishtowel->skippable=true;
    $dishtowel->bodyPaneHtml=("Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. ");
    $dishtowel->linkPaneHtml="link pane html <b>test</b> content";
    $dishtowel->allowCustomPrice=false;
    $dishtowel->title = "Dish Towel";
    $dishtowel->setParent($head, 4);
    $dishtowel->createInDataStore();

    $flow = new Flow($dataStore);
    $flow->summaryTitle='Here is your shopping list!';
    $flow->summaryBody=('Lorem ipsum dolor sit amet, vis no dicat abhorreant. At duo timeam qualisque gloriatur, vis ut diam justo nostrum, et quod iisque eum. Nostro intellegat adipiscing quo et? Ex elitr elaboraret signiferumque sit, qui an cibo liber luptatum. Pri et civibus gloriatur.  At alia feugiat facilisi ius, pro eligendi voluptua forensibus ei. Sed ut dicam inermis? Dicunt tamquam definiebas vim ad, cu nec everti offendit deserunt. Ea dolor scripta maluisset eam, usu novum ornatus perfecto cu. Ut ius prima omnes tempor?');
    $flow->title = "CookingStuff";
    $flow->id = $head->id;
    $flow->createInDataStore();
}

