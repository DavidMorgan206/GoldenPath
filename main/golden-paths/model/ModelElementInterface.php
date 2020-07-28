<?php

namespace Stradella\GPaths;

/**
 * Interface ModelElementInterface
 * @package Stradella\GPaths
 *
 * data source interface for model elements (which each map a table)
 */
interface ModelElementInterface
{
    /*
     * The $childCaller convention isn't ideal, but allows us to try and block direct calls to parent objects like Node
     * from non-child callers
     *
     * returned string contains a terse, english user facing status message.  Standardize on 'success' to hopefully make
     * eventual localization easier.
     */
    public function createInDataStore(bool $childCaller=false); //:?string;
    public function refreshFromDataStore(bool $childCaller=false); //:?string;
    public function updateDataStore(bool $childCaller=false); //:?string;
    public function deleteFromDataStore(bool $childCaller = false); //:?string;
    public static function checkExistsByTitle(DataStoreInterface $dataStore, string $title); //:bool;
    /**
     * returns all properties that can be set after creation
     * @return array
     */
    public static function getUpdatableProperties(); //:array;
}
