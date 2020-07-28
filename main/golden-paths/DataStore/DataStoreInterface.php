<?php

namespace Stradella\GPaths;
use Exception;

/**
 * Interface DataStoreInterface
 * @package Stradella\GPaths
 *
 * Interface for DataStore (which is really just the data source to the larger web of model classes)
 *
 * Depending on the performance of our ORM stuff we may need to add caching.
 * That would be a good time to move the DataStore out as a seperate (mockable) class
 *       - create *DataStore classes for each existing model class (each db table)
 *       - move the 4 CRUD methods for each class (defined by ModelElementInterface) to *DataStore class
 *          - replace any remaining sql with calls to CRUD methods
 *       - create an id-based (and title?) object cache {className, id} that works with CRUD methods to receive updates
            / service requests
 *
 */
interface DataStoreInterface{
    /***
     * @param string $sql
     * @param array $cond
     * @return array|null
     * @throws Exception
     */
    public function select(string $sql, array $cond); //:?array;

    /***
     * @param string $level
     * @param string $message
     * @throws Exception
     */
    public function logToDb(string $level, string $message); //:void;

    public function clearAllData(); //:void;

    public function isMockDataStore(); //used (sparingly) for test hooks within product code
}
