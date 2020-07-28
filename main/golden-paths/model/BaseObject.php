<?php

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists('Gpaths_BaseObject' ) ) {
    /**
     * Class GpathsBaseObject
     * provides magic methods for Gpaths objects, get pulls from model's DataStore if possible
     *
     * @package Stradella\GPaths
     */
    class GpathsBaseObject
    {
        protected $data = array();

        public function __toString()
        {
            return print_r($this->data, true);
        }

        /**
         * @param $name
         * @return mixed
         * @throws Exception
         */
        public function __get($name)
        {
            if (!isset($this->data[$name])) {
                try {
                    $this->refreshFromDataStore();
                }
                catch(Exception $e) {
                    error_log('hit exception calling refreshFromDataStore to satisfy __get($name) where $name: ' . $name);
                    throw $e;
                }
            }



            //error_log('__get(' . $name . ') : ' . $this->data[$name]);
            return $this->data[$name];
        }

        public function __set($name, $value)
        {
            //error_log('set ' . $name . ' value: ' . $value);
            $this->data[$name] = $value;
        }

        public function __isset($name)
        {
            return isset($this->data[$name]);
        }

        /**
         * @param $object1
         * @param $object2
         * @throws Exception
         */
        protected static function verifyObjectsEqual($object1, $object2)
        {
            if (is_array($object1)) {
                $delta = array_diff($object1, $object2);
                if (count($delta) > 0) {
                    throw new Exception(
                        'not equal object1: ' . print_r($object1, true) . ' object2: ' . print_r($object2, true) . ' delta: ' . print_r($delta, true)
                    );
                }
            }
            elseif ($object1 != $object2) {
                throw new Exception('not equal object1: ' . $object1 . ' object2: ' . $object2);
            }
        }
    }
}

