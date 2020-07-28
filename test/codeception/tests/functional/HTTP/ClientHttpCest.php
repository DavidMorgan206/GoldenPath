<?php

require_once(dirname(__DIR__) . '/../../../../main/golden-paths/DataStore/DataStore.php');
require_once(dirname(__DIR__) . '/../../../..\main\golden-paths\model\Session.php');
require_once(dirname(__DIR__) . '/../../../..\main\golden-paths\model\Flow.php');
require_once(dirname(__DIR__) . '/../../../..\main\golden-paths\model\SessionChoice.php');
require_once(dirname(__DIR__) . '/../../../..\main\golden-paths\model\NodeFactory.php');
use Stradella\GPaths\DataStore;
use Stradella\GPaths\{Session, SessionChoice, Flow, NodeFactory};

class ClientHttpCest
{
    private $dataStore;

    public function _before(FunctionalTester $I)
    {
        $this->dataStore = new DataStore();
    }

    /*
     *
     */
    public function ListNode(FunctionalTester $I)
    {
        $this->dataStore->clearAllData();
        $I->createSimpleListNodeFlow();

        $I->sendGET('/wp-json/golden-paths/v1/publicendpoint?path_title=SimpleListNodeFlow&cookie_id=testcookie&currentNode=landing');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.sessionId');
        $I->seeResponseJsonMatchesJsonPath('$.sessionTitle');
        $I->seeResponseContainsJson(array('nodeTypeTitle' => 'LandingNode'));
        $I->seeResponseContainsJson(array('flowTitle' => 'SimpleListNodeFlow'));
        $I->seeResponseJsonMatchesJsonPath('$.nodeId');
        $I->seeResponseContainsJson(array('skippable' => 0));
        $I->seeResponseContainsJson(array('childrenAreExclusive' => 0));

        $sessionId = $I->grabDataFromResponseByJsonPath('$.sessionId')[0];
        $currentNode = $I->grabDataFromResponseByJsonPath('$.nodeId')[0];

        $I->sendPOST('/wp-json/golden-paths/v1/publicendpoint', ['action'=> 'next',  'nextNode' => 'down', 'sessionId' => $sessionId, 'currentNode' => $currentNode]);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        $I->sendGET('/wp-json/golden-paths/v1/publicendpoint?path_title=SimpleListNodeFlow&cookie_id=testcookie');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.sessionId');
        $I->seeResponseJsonMatchesJsonPath('$.sessionTitle');
        $I->seeResponseContainsJson(array('flowTitle' => 'SimpleListNodeFlow'));
        $I->seeResponseContainsJson(array('nodeTypeTitle' => 'ListNode'));
        $I->seeResponseContainsJson(array('title' => 'simpleFlowListtest-listnode'));
        $I->seeResponseJsonMatchesJsonPath('$.nodeId');
        $I->seeResponseContainsJson(array('skippable' => 0));
        $I->seeResponseContainsJson(array('childrenAreExclusive' => 0));


        //$I->assertEquals($I->grabDataFromResponseByJsonPath('$.nodeList[0].nodeTitle'), 'simpleFlowListNodeTestNode-child1');
        $I->seeResponseContainsJson(['nodeList' => ['title'=>'simpleFlowListNodeTestNode-child1']]);
        $I->seeResponseContainsJson(['nodeList' => ['title'=>'simpleFlowListNodeTestNode-child2']]);

        $sessionId = $I->grabDataFromResponseByJsonPath('$.sessionId')[0];
        $session = Session::getExistingById($this->dataStore, $sessionId);
        $currentNode = $I->grabDataFromResponseByJsonPath('$.nodeId')[0];

        $I->sendPOST('/wp-json/golden-paths/v1/publicendpoint?action="next"&nextNode="right"&currentNode=' . $currentNode . '&sessionId=' . $sessionId,
            json_encode([
                    [
                        'nodeId' => $session->getCurrentNode()->id,
                        'isChecked' =>true
                    ],
                    [
                        'nodeId' => $session->getCurrentNode()->getChildren()[0]->id,
                        'isChecked' =>true
                    ]
            ])
        );
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $session = Session::getExistingById($this->dataStore, $sessionId); //force a refresh of the session object from the db
        $I->assertTrue($session->getSessionComplete());

    }

    public function skipToSummary(FunctionalTester $I)
    {
        $this->dataStore->clearAllData();
        $I->createSimpleFlow();

        $I->sendGET('/wp-json/golden-paths/v1/publicendpoint?path_title=SimpleTestPath&cookie_id=testcookie');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.sessionId');
        $I->seeResponseJsonMatchesJsonPath('$.sessionTitle');
        $I->seeResponseContainsJson(array('flowTitle' => 'SimpleTestPath'));
        $I->seeResponseContainsJson(array('title' => 'simpleFlowTestNode'));
        $I->seeResponseContainsJson(array('nodeTypeTitle' => 'LandingNode'));
        $I->seeResponseJsonMatchesJsonPath('$.nodeId');
        $I->seeResponseContainsJson(array('skippable' => 0));
        $I->seeResponseContainsJson(array('childrenAreExclusive' => 0));
        $I->seeResponseJsonMatchesJsonPath('$.bodyPaneHtml');

        $sessionId = $I->grabDataFromResponseByJsonPath('$.sessionId')[0];
        $currentNode = $I->grabDataFromResponseByJsonPath('$.nodeId')[0];

        $I->sendPOST('/wp-json/golden-paths/v1/publicendpoint', ['action'=> 'next', 'customPrice' => 180, 'nextNode' => 'Summary', 'sessionId' => $sessionId, 'currentNode' => $currentNode]);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        $I->sendGET('/wp-json/golden-paths/v1/publicendpoint?path_title=SimpleTestPath&cookie_id=testcookie');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.sessionId');
        $I->seeResponseContainsJson(array('nodeTypeTitle' => 'SessionSummary'));
    }

    public function SimplePath(FunctionalTester $I)
    {
        $this->dataStore->clearAllData();
        $I->createSimpleFlow();

        $I->sendGET('/wp-json/golden-paths/v1/publicendpoint?path_title=SimpleTestPath&cookie_id=testcookie');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.sessionId');
        $I->seeResponseJsonMatchesJsonPath('$.sessionTitle');
        $I->seeResponseContainsJson(array('flowTitle' => 'SimpleTestPath'));
        $I->seeResponseContainsJson(array('title' => 'simpleFlowTestNode'));
        $I->seeResponseContainsJson(array('nodeTypeTitle' => 'LandingNode'));
        $I->seeResponseJsonMatchesJsonPath('$.nodeId');
        $I->seeResponseContainsJson(array('skippable' => 0));
        $I->seeResponseContainsJson(array('childrenAreExclusive' => 0));
        $I->seeResponseJsonMatchesJsonPath('$.bodyPaneHtml');

        $sessionId = $I->grabDataFromResponseByJsonPath('$.sessionId')[0];
        $currentNode = $I->grabDataFromResponseByJsonPath('$.nodeId')[0];

        $I->sendPOST('/wp-json/golden-paths/v1/publicendpoint', ['action'=> 'next', 'customPrice' => 180, 'nextNode' => 'down', 'sessionId' => $sessionId, 'currentNode' => $currentNode]);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);


        $I->sendGET('/wp-json/golden-paths/v1/publicendpoint?path_title=SimpleTestPath&cookie_id=testcookie');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.sessionId');
        $I->seeResponseJsonMatchesJsonPath('$.sessionTitle');
        $I->seeResponseContainsJson(array('flowTitle' => 'SimpleTestPath'));
        $I->seeResponseContainsJson(array('title' => 'simpleFlowTestNode-child1'));
        $I->seeResponseContainsJson(array('nodeTypeTitle' => 'ManualNode'));
        $I->seeResponseJsonMatchesJsonPath('$.nodeId');
        $I->seeResponseContainsJson(array('skippable' => 1));
        $I->seeResponseContainsJson(array('childrenAreExclusive' => 1));
        $I->seeResponseJsonMatchesJsonPath('$.bodyPaneHtml');

        $currentNode = $I->grabDataFromResponseByJsonPath('$.nodeId')[0];
        $I->sendPOST('/wp-json/golden-paths/v1/publicendpoint', ['action'=> 'skip', 'customPrice' => 10, 'nextNode' => 'down', 'sessionId' => $sessionId, 'currentNode' => $currentNode]);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        $I->sendGET('/wp-json/golden-paths/v1/publicendpoint?path_title=SimpleTestPath&cookie_id=testcookie');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.sessionId');
        $I->seeResponseJsonMatchesJsonPath('$.sessionTitle');
        $I->seeResponseContainsJson(array('flowTitle' => 'SimpleTestPath'));
        $I->seeResponseContainsJson(array('title' => 'simpleFlowTestNode-child2'));
        $I->seeResponseContainsJson(array('nodeTypeTitle' => 'ManualNode'));
        $I->seeResponseJsonMatchesJsonPath('$.nodeId');
        $I->seeResponseContainsJson(array('skippable' => 1));
        $I->seeResponseContainsJson(array('childrenAreExclusive' => 1));
        $I->seeResponseJsonMatchesJsonPath('$.bodyPaneHtml');

        $currentNode = $I->grabDataFromResponseByJsonPath('$.nodeId')[0];
        $I->sendPOST('/wp-json/golden-paths/v1/publicendpoint', ['action'=> 'skip', 'customPrice' => 10, 'nextNode' => 'down', 'sessionId' => $sessionId, 'currentNode' => $currentNode]);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        $I->sendGET('/wp-json/golden-paths/v1/publicendpoint?path_title=SimpleTestPath&cookie_id=testcookie');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.sessionId');
        $I->seeResponseContainsJson(array('nodeTypeTitle' => 'SessionSummary'));
    }
}
?>
