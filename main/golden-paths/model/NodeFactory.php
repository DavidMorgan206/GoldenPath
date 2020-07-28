<?php

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists( 'Gpaths_NodeFactory' ) ) {
    /**
     * Class NodeFactory
     *
     * functions for getting the most specific class instance for a given node
     * plus some other static helpers
     *
     * @package Stradella\GPaths
     */
    abstract class NodeFactory
    {
        /**
         * @param DataStoreInterface $dataStore
         * @param int $id
         * @return Node that has been populated from DataStore
         * @throws Exception
         */
        public static function getById(DataStoreInterface $dataStore, int $id): Node
        {
            $baseNode = NodeFactory::getExistingById($dataStore, $id);
            $nodeType = $baseNode->nodeType;
            $classname = '\\Stradella\\Gpaths\\' . $nodeType->title;
            $node = new $classname($dataStore);
            $node->id = $id;
            /** @noinspection PhpUndefinedMethodInspection */
            $node->refreshFromDataStore();
            return $node;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param string $title
         * @return Node
         * @throws Exception
         */
        public static function getByTitle(DataStoreInterface $dataStore, string $title): Node
        {
            $baseNode = NodeFactory::getExistingByTitle($dataStore, $title);
            $classname = '\\Stradella\\Gpaths\\' . $baseNode->nodeType->title;
            $node = new $classname($dataStore);
            $node->title = $title;
            /** @noinspection PhpUndefinedMethodInspection */
            $node->refreshFromDataStore();
            return $node;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param int $id
         * @return Node
         * @throws Exception
         */
        private static function getExistingById(DataStoreInterface $dataStore, int $id): Node
        {
            $node = new Node($dataStore);
            $node->id = $id;
            $node->refreshFromDataStore(true); //we're only getting this to query type, then we'll get the appropriate child object
            return $node;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param string $title
         * @return Node
         * @throws Exception
         */
        private static function getExistingByTitle(DataStoreInterface $dataStore, string $title): Node
        {
            $node = new Node($dataStore);
            $node->title = $title;
            $node->refreshFromDataStore(
                true
            );  //we're only getting this to query type, then we'll get the appropriate child object
            return $node;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @return Node
         * @throws Exception
         */
        public static function getNewDefaultLandingNode(DataStoreInterface $dataStore) :Node
        {
            $nodeBaseTitle = 'New Golden Path';
            $nodeTitle = $nodeBaseTitle;
            $uniqueSuffix = 0;

            do {
                if($uniqueSuffix != 0)
                    $nodeTitle = $nodeBaseTitle . $uniqueSuffix;
                $uniqueSuffix++;
            } while(Node::existsInDataStore($dataStore, $nodeTitle) && $uniqueSuffix < 10000);

            if($uniqueSuffix > 10000) {
                throw new Exception('failed to find unique default landing node title');
            }


            $node = NodeFactory::getNewNode($dataStore, 'LandingNode');
            $node->title = $nodeTitle;
            $node->createInDataStore();
            return $node;
        }

        /**
         * @param DataStoreInterface $dataStore
         * @param string $nodeTypeTitle
         * @return Node instance of type specific node class that has not been saved to the datastore
         * @throws Exception
         */
        public static function getNewNode(DataStoreInterface $dataStore, string $nodeTypeTitle): Node
        {
            $node = null;
            switch ($nodeTypeTitle) {
                case 'AffiliateNode':
                    $node =  new AffiliateNode($dataStore);
                    break;
                case 'ManualNode':
                    $node =  new ManualNode($dataStore);
                    break;
                case 'ListNode':
                    $node =  new ListNode($dataStore);
                    break;
                case 'LandingNode':
                    $node =  new LandingNode($dataStore);
                    break;
                default:
                    throw new Exception('unsupported node type ' . $nodeTypeTitle);
            }

            return $node;
        }

        /**
         * This is very dependent on AdminEditNode, should be moved out
         *
         * @param DataStoreInterface $dataStore
         * @param array $properties
         * @return Node a Node instance populated with all the passed properties. New node is not saved to the datastore.
         * @throws Exception
         */
        public static function getNewNodeExt(DataStoreInterface $dataStore, array $properties) :Node
        {
            $node = self::getNewNode($dataStore, $properties['nodeTypeTitle']);

            //set simple node properties sent set by user
            $someNodeProperties = array(
                'title', 'heading', 'defaultPrice'
            );
            foreach($someNodeProperties as $property){
                if(isset($properties[$property]))
                    $node->$property = $properties[$property];
            }
            $boolNodeProperties = array(
                'skippable', 'allowCustomPrice', 'childrenAreExclusive'
            );
            foreach($boolNodeProperties as $property){
                $node->$property = isset($properties[$property]);
            }
            //set html encoded props
            $someNodeProperties = array(
                'bodyPaneHtml', 'linkPaneHtml', 'footerPaneHtml', 'imagePaneHtml'
            );
            foreach($someNodeProperties as $property){
                if(isset($properties[$property])) {
                    $node->$property = (stripslashes($properties[$property]));
                }
            }

            //set properties with one-off needs
            if(isset($properties['nodeParentTitle'])) { // wont be set / shouldn't be set for root node
                $node->setParent(NodeFactory::getByTitle($dataStore, $properties['nodeParentTitle']), (isset($properties['sequence']) ? $properties['sequence'] : 1));
            }


            return $node;
        }

        //TODO: Move out strings in all below functions to resource files?
        /**
         * Presents pretty names for types in admin pages.  NodeType->title is hardcoded throughout the model to
         * point they it will need to remain pretty stable.  These strings may change though, so don't take a hard
         * dependency on output.
         *
         * @param string $nodeTypeTitle
         * @return string
         * @throws Exception
         */
        public static function getFriendlyTypeName(string $nodeTypeTitle) :string
        {

            switch ($nodeTypeTitle) {
                case 'AffiliateNode':
                    return 'Affiliate';
                case 'ManualNode':
                    return 'Custom';
                case 'ListNode':
                    return 'List';
                case 'LandingNode':
                    return 'Info';
                default:
                    throw new Exception('unsupported node type ' . $nodeTypeTitle);
            }
        }

        /**
         * @param string $nodeTypeTitle
         * @return string
         * @throws Exception
         */
        public static function getNodeTypeDescription(string $nodeTypeTitle) : string {

            switch ($nodeTypeTitle) {
                case 'AffiliateNode':
                    return "Template specifically for an Affiliate Associates \"Text+Image\" style iframe link (more info).  All pricing informmation for this template is disabled to comply with Affiliate's strict policies against displaying (potentially outdated) prices.  Our pro version supports using an API key to pull this information in real-time from Affiliate, for more information see www.stradellacreative.com/goldenpaths/pro"; //TODO: link
                case 'ManualNode':
                    return 'Basic template for affiliate marketing or anything with a price. ';
                case 'ListNode':
                    return 'A page that displays all its child Item Pages as a selectable list.  Useful for letting your visitors compare several similar options.  Note that child pages of this page cannot have child pages of their own.';
                case 'LandingNode':
                    return 'A blank page with no price information, just the option to "skip" (child pages).  Useful for giving your visitors a choice before showing a series of detailed child pages';
                default:
                    throw new Exception('unsupported node type ' . $nodeTypeTitle);
            }
        }

        /**
         * @param DataStoreInterface $dataStore
         * @return array
         * @throws Exception
         */
        public static function getNodeTypeDescriptions(DataStoreInterface $dataStore) : array {
            $descriptions = array();
            foreach(NodeType::getUserCreatableNodeTypeTitles($dataStore) as $nodeType) {
                $descriptions[$nodeType] = self::getNodeTypeDescription($nodeType);
            }
            return $descriptions;
        }
    }
}
