<?php

namespace Stradella\GPaths;
use Exception;

if ( ! class_exists( 'Gpaths_LandingNode' ) ) {
    /**
     * Class LandingNode
     *
     * A node with nothing to buy, just the (disablable) option to skip or proceed with the flow.
     * the root node of a flow has to be a LandingNode.  They can be used anywhere but as children of listnodes
     *
     * @package Stradella\GPaths
     * @property string $bodyPaneHtml
     * @property NodeType $linkPaneHtml
     */
    class LandingNode extends Node
    {
        /**
         * LandingNode constructor.
         * @param DataStoreInterface $dataStore
         * @throws Exception
         */
        public function __construct(DataStoreInterface $dataStore)
        {
            parent::__construct($dataStore);
            $this->nodeType = NodeType::getExistingFromTitle($dataStore, 'LandingNode');

            $this->bodyPaneHtml = '';
            $this->linkPaneHtml = '';
        }

        public static function getUpdatableProperties() :array
        {
            $response = parent::getUpdatableProperties();
            return array_merge($response, ['bodyPaneHtml', 'linkPaneHtml']);
        }

        public function verifyEquals(Node $node) //:void
        {
            parent::verifyEquals($node);
            GpathsBaseObject::verifyObjectsEqual($this->bodyPaneHtml, $node->bodyPaneHtml);
            GpathsBaseObject::verifyObjectsEqual($this->linkPaneHtml, $node->linkPaneHtml);
        }

        /**
         * @param bool $childCaller
         * @return string|null
         * @throws Exception
         */
        public function refreshFromDataStore(bool $childCaller = false) //:?string
        {
            parent::refreshFromDataStore(true);

            if (!$this->id) {
                $this->dataStore->logToDb('critical', 'LandingNode::refresh called without setting id');
                throw new Exception("LandingNodes:refresh called without setting id");
            }

            $sql = "SELECT * FROM {$this->dataStore->landingNodesTableName} WHERE node_id = :id";
            $cond = array(':id'=>$this->id);
            $result = $this->dataStore->select($sql, $cond);

            if (empty($result)) {
                $this->dataStore->logToDb('critical', 'LandingNodew::refresh id not found');
                throw new Exception('Flow::refresh path not found');
            }
            $this->bodyPaneHtml = $result[0]['body_pane_html'];
            $this->linkPaneHtml = $result[0]['link_pane_html'];

            return 'success';

        }

        public function updateDataStore(bool $childCaller = false) //:?string
        {
            $message = parent::updateDataStore(true);

            $sql = "UPDATE {$this->dataStore->landingNodesTableName} SET 
                    body_pane_html=:bodyPaneHtml,
                    link_pane_html=:linkPaneHtml
                    WHERE node_id=:node_id";
            $conds = array(
                ':bodyPaneHtml' => $this->data['bodyPaneHtml'],
                ':linkPaneHtml' => $this->data['linkPaneHtml'],
                ':node_id' => $this->data['id']
            );
            $this->dataStore->select($sql, $conds);

            return $message;
        }

        public function createInDataStore(bool $childCaller = false) //:?string
        {
            parent::createInDataStore(true);

            $sql = "INSERT INTO {$this->dataStore->landingNodesTableName} 
                    (node_id, body_pane_html, link_pane_html)  VALUES 
                    (:id, :bodyPaneHtml, :linkPaneHtml)";
            $cond = array(
                ':id'=>$this->id,
                ':bodyPaneHtml'=>$this->bodyPaneHtml,
                ':linkPaneHtml'=>$this->linkPaneHtml
            );
            $this->dataStore->select($sql, $cond);
            return 'success';
        }

        public function deleteFromDataStore(bool $childCaller = false) //:?string
        {
            $sql = "DELETE FROM {$this->dataStore->landingNodesTableName} WHERE node_id=:nodeId";
            $cond = array(':nodeId' => $this->id);
            $this->dataStore->select($sql, $cond);
            return parent::deleteFromDataStore(true);
        }

        public function getStateJSON() :array
        {
            if( function_exists('do_shortcode')) {
                $bodyHtml = do_shortcode(stripslashes(html_entity_decode($this->bodyPaneHtml)), false);
                $linkHtml = do_shortcode(stripslashes(html_entity_decode($this->linkPaneHtml)), false);
            }
            else {
                $bodyHtml = stripslashes(html_entity_decode($this->bodyPaneHtml));
                $linkHtml = stripslashes(html_entity_decode($this->linkPaneHtml));
            }
            $response = parent::getStateJSON();
            $rootNode = is_null($this->parent->getParent());
            $response = array_merge(
                $response,
                array(
                    'rootNode' => $rootNode,
                    'bodyPaneHtml' => $bodyHtml,
                    'linkPaneHtml' => $linkHtml
                )
            );

            return $response;
        }
    }
}


