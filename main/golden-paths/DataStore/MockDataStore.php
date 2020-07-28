<?php

namespace Stradella\GPaths;

require_once(dirname(__DIR__) . '/DataStore/DataStoreInterface.php');
    /**
    * @package Stradella\GPaths
    * @property string $table_prefix
    * @property string $nodesTableName
    * @property string $manualNodesTableName
    * @property string $landingNodesTableName
    * @property string $affiliateNodesTableName
    * @property string $listNodesTableName
    * @property string $nodeChildrenTableName
    * @property string $nodeTypesTableName
    * @property string $flowsTableName
    * @property string $sessionsTableName
    * @property string $sessionChoicesTableName
    * //ifdef debug
    * @property string $debugLogTableName //TODO: logging best practices?
    * //end ifdef debug
    */
    class MockDataStore implements DataStoreInterface
    {

        public function select(string $sql, array $cond = null) //: ?array
        {
            error_log('warn: GpathsmockDataStore ignoring attempt at DataStore access (select), sql: ' . $sql);
            return [];
        }

        public function __construct()
        {
        }

        public function isMockDataStore() {
            return true;
        }

        public function __get($name)
        {
            return 'mock_' . $name;
        }

        public function logToDb(string $level, string $message) //: void
        {
            if (strcmp($level, 'critical')) {
                error_log($message);
            }
        }
        public function clearAllData() //:void
        {
            error_log('warn: GpathsmockDataStore ignoring attempt at DataStore access (clearAllData)');
        }
    }


