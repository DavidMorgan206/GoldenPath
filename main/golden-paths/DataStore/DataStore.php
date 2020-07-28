<?php

namespace Stradella\GPaths;
use Exception;
use PDO;

require_once(dirname(__DIR__) . '/model/BaseObject.php');
require_once(dirname(__DIR__) . '/model/ModelElementInterface.php');
require_once(dirname(__DIR__) . '/model/Session.php');
require_once(dirname(__DIR__) . '/model/SessionChoice.php');
require_once(dirname(__DIR__) . '/model/Flow.php');
require_once(dirname(__DIR__) . '/model/Node.php');
require_once(dirname(__DIR__) . '/model/NodeFactory.php');
require_once(dirname(__DIR__) . '/model/NodeType.php');
require_once(dirname(__DIR__) . '/model/ManualNode.php');
require_once(dirname(__DIR__) . '/model/LandingNode.php');
require_once(dirname(__DIR__) . '/model/AffiliateNode.php');
require_once(dirname(__DIR__) . '/model/ListNode.php');
require_once(dirname(__DIR__) . '/model/NodeParent.php');
require_once(dirname(__DIR__) . '/DataStore/DataStoreInterface.php');



if ( ! class_exists( 'Gpaths_Model' ) ) {
    /**
     * Class DataStore
     *
     * Production model, uses a real DataStore.
     * See DataStoreInterface for comments on arch.
     *
     * @package Stradella\GPaths
     * @property bool dd$implementsDataStore
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
    class DataStore implements DataStoreInterface
    {
        private $data = array();
        private $pdo = null;
        private $stmt = null;

        public function isMockDataStore() {
            return false;
        }

        public function __construct()
        {
            global $wpdb;

            try {
                $this->pdo = new PDO(
                    "mysql:host=localhost;dbname=wp_test;charset=utf8",  //TODO: pull from config, wpdb
                    "root", "", [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (Exception $ex) {
                die($ex->getMessage());
            }

            if (!isset($wpdb)) {
                $this->table_prefix = "wp_gpaths_";
            } else {
                $this->table_prefix = $wpdb->prefix . "gpaths_";
            }

            $this->nodesTableName = $this->table_prefix . 'nodes';
            $this->manualNodesTableName = $this->table_prefix . 'manualnodes';
            $this->affiliateNodesTableName = $this->table_prefix . 'affiliatenodes';
            $this->listNodesTableName = $this->table_prefix . 'listnodes';
            $this->landingNodesTableName = $this->table_prefix . 'landingnodes';
            $this->nodeChildrenTableName = $this->table_prefix . 'nodechildren';
            $this->nodeTypesTableName = $this->table_prefix . 'types';
            $this->sessionsTableName = $this->table_prefix . 'sessions';
            $this->flowsTableName = $this->table_prefix . 'flows';
            $this->sessionChoicesTableName = $this->table_prefix . 'sessionchoices';
            $this->debugLogTableName = $this->table_prefix . 'debug_log';
        }

        public function __destruct()
        {
            if ($this->stmt !== null) {
                $this->stmt = null;
            }
            if ($this->pdo !== null) {
                $this->pdo = null;
            }
        }

        public function __get($name)
        {
            return $this->data[$name];
        }

        public function __set($name, $value)
        {
            $this->data[$name] = $value;
        }


        /**
         * Execute query against configured DataStore
         *
         * @param string $sql query to run against configured data source
         * @param array|null $cond query params.  null isn't actually allowed, instead caller must pass ['trustMe'=>true]
         * @return array|null query results
         * @throws Exception
         */
        public function select(string $sql, array $cond = null) //: ?array
        {
            if ($cond == null) {
                throw new Exception('cond null for select, should this have been a trustMe == true call?');
            }

            //use this flag as a dumb work around for the few cases where we're using parameterless sql queries and having
            // cond == null wouldn't actually be a security issue
            if (isset($cond['trustMe']) && $cond['trustMe'] == true) {
                $cond = null;
            }

            try {
                $this->stmt = $this->pdo->prepare($sql);
                $this->stmt->execute($cond);
                $result = $this->stmt->fetchAll();
            } catch (Exception $ex) {
                error_log($ex->getMessage());
                error_log($sql);
                throw new Exception($sql . print_r($cond, true) . $ex);
            }

            $this->stmt = null;
            return $result;
        }

        /**
         * @throws Exception
         */
        public function dropAllTables() //: void
        {
            $sql = "SELECT Table_Name FROM INFORMATION_SCHEMA.tables WHERE Table_Name LIKE 'wp_gpaths_%'";
            $tables = $this->select($sql, ['trustMe' => true]);

            foreach ($tables as $table) {
                $sql = 'SET FOREIGN_KEY_CHECKS = 0;';
                $this->select($sql, ['trustMe' => true]);

                $sql = "DROP TABLE IF EXISTS {$table['Table_Name']} CASCADE; ";
                $this->select($sql, ['trustMe' => true]);

                $sql = 'SET FOREIGN_KEY_CHECKS = 1;';
                $this->select($sql, ['trustMe' => true]);
            }
        }


        /**
         * clearAllData (from tables) except types
         * @throws Exception
         */
        public function clearAllData() //: void
        {
            $sql = "SELECT Table_Name FROM INFORMATION_SCHEMA.tables WHERE Table_Name LIKE 'wp_gpaths_%' AND Table_Name NOT LIKE '{$this->nodeTypesTableName}'";
            $tables = $this->select($sql, ['trustMe' => true]);

            foreach ($tables as $table) {
                $sql = 'SET FOREIGN_KEY_CHECKS = 0;';
                $this->select($sql, ['trustMe' => true]);

                /** @noinspection SqlWithoutWhere */
                $sql = 'DELETE FROM ' . $table['Table_Name'];
                $this->select($sql, ['trustMe' => true]);

                $sql = 'SET FOREIGN_KEY_CHECKS = 1;';
                $this->select($sql, ['trustMe' => true]);
            }
        }

        /**
         * @param string $level
         * @param string $message
         * @throws Exception
         */
        public function logToDb(string $level, string $message) //: void
        {
            if (strcmp($level, 'critical')) {
                error_log($message);
            }

            $sql = "INSERT INTO {$this->debugLogTableName} (level, message, time) VALUES (
                    :level, :message, NOW())";
            $cond = array(
                ':level' => $level,
                ':message' => $message
            );
            $this->select($sql, $cond);
        }
    }
}
