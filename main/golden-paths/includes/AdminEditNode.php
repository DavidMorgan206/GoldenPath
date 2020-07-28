<?php /** @noinspection HtmlUnknownTarget */

/** @noinspection PhpUndefinedFunctionInspection */

namespace Stradella\GPaths;
use Exception;

class AdminEditNode
{
    const createNewNodePageTitle = 'Create New Page';
    const editExistingNodePageTitle = 'Edit Existing Page';
    const createNodeButtonText = 'Create Page';
    const updateNodeButtonText = 'Update Page';

    /**
     * Look at POST values to check for any pending edits and apply them.
     * @param DataStoreInterface $dataStore
     * @return string
     * @throws Exception
     */
    public static function processPostForEdits(DataStoreInterface $dataStore) :string
    {
        switch($_GET['action']){
            case 'create':
            case 'update':
                return AdminEditNode::createOrUpdateNodeFromPost($dataStore);
                break;
            case 'delete':
                return NodeFactory::getById($dataStore, $_GET['nodeId'])->deleteFromDataStore();
                break;
            default:
                throw new Exception('unsupported action');
        }
    }

    /**
     * @param DataStoreInterface $dataStore
     * @throws Exception
     */
    public static function displayNewNodeTypeSelection(DataStoreInterface $dataStore)
    {
        echo '<form method="POST" action="' . admin_url( 'options-general.php?page=golden-paths&tabEditNode=1&nodeParentTitle=' . urlencode($_GET['nodeParentTitle'])) . '">';
        self::renderHiddenInputs(null); //keep track of flow information so we eventually go back to the right summary
        $typeDescriptions = json_encode(NodeFactory::getNodeTypeDescriptions($dataStore));
        $nodeTypes = NodeType::getUserCreatableNodeTypeTitles($dataStore);
        //The assignment to [0] relies on getNodeTypeDescriptions using getUserCreatableNodeTypeTitles, so we know the ordering is going to be the same and can safely assume [0] will be selected below.
        //TODO: that's dumb. do it with javascript and delete comment
        if(isset($_POST['nodeTypeTitle])'])) {
            $selectedNodeTypeTitle = NodeFactory::getNodeTypeDescription($_POST['nodeTypeTitle']);
        }
        else {
            $selectedNodeTypeTitle = $nodeTypes[0];
        }
        $selectedTypeDescription = NodeFactory::getNodeTypeDescription($selectedNodeTypeTitle);

        echo '<label for="nodeType">Page Type </label>';
        echo '<select onchange="updateTypeDescription()" id="nodeTypeTitle" name="nodeTypeTitle">';

        foreach($nodeTypes as $nodeTypeTitle) {
            if(strcmp($selectedNodeTypeTitle, $nodeTypeTitle) == 0)
                echo '<option selected="Selected" value=' . $nodeTypeTitle . '>' . NodeFactory::getFriendlyTypeName($nodeTypeTitle) . '</option>';
            else
                echo '<option value=' . $nodeTypeTitle . '>' . NodeFactory::getFriendlyTypeName($nodeTypeTitle) . '</option>';
        }

        echo "</select>";
        echo<<<UPDATETYPEDESCRIPTION
<script>
function updateTypeDescription(){
    let typeDescriptions = {$typeDescriptions};
    let d = document.getElementById('nodeTypeTitle').value;
    document.getElementById("nodeTypeDescription").innerHTML = typeDescriptions[d];
    }
</script>
<p class="description" id="nodeTypeDescription">{$selectedTypeDescription}</p>
<br>
UPDATETYPEDESCRIPTION;

        echo '<input type="submit" value="Select Type"/>';
        echo '</form>';
        return;
    }

    /**
     * @param DataStoreInterface $dataStore
     * @throws Exception
     */
    public static function render(DataStoreInterface $dataStore){
        $node = null;
        $flowId = $_GET['flowId'] ?? $_POST['flowId'];
        $newNode = !isset($_GET['nodeId']);

        if(empty($flowId))
            throw new Exception('flowId must be set');

        //Page Start
        //page title
        $pageTitle = $newNode ? self::createNewNodePageTitle : self::editExistingNodePageTitle;
        echo "<h1>{$pageTitle}</h1>";

        //User is editing an existing node, pull from the db to populate fields
        if(!$newNode) {
            $node = NodeFactory::getById($dataStore, $_GET['nodeId']);
            if(empty($node))
                throw new Exception('Node not found');
        }
        //User is creating a new node
        elseif(isset($_GET['nodeParentTitle'])) {
            //display only type selection until a type is picked, changes aren't supported.
            if(!isset($_POST['nodeTypeTitle'])){
                self::displayNewNodeTypeSelection($dataStore);
                return;
            }
            else {
                $node = NodeFactory::getNewNode($dataStore, $_POST['nodeTypeTitle']);
                $node->setParent(NodeFactory::getByTitle($dataStore, $_GET['nodeParentTitle']), 1);
            }
        }
        else {
            throw new Exception('display_plugin_crud_node_page requires nodeId or nodeParentTitle in GET');
        }

        $formAction = admin_url( 'options-general.php?page=golden-paths&tab=flowSettings&actionObject=node&action=update');
        $pageType = NodeFactory::getFriendlyTypeName($node->nodeType->title);
        $pageTypeDescription = NodeFactory::getNodeTypeDescription($node->nodeType->title);
        echo <<<INTRO
<form method="POST" action="{$formAction}">
    <table class="form-table">
        <tr>
            <th>
                <label>Page Type</label>
            </th>
            <td>{$pageType}
            <p class="description">{$pageTypeDescription}</p>
            </td>
        </tr>
INTRO;

        //Only display parent selection stuff if this is not the root landing node (root has no parent)
        if($node->parent->parentNodeId != null) {
            echo <<<PARENT
<tr>
    <th>
        <label for="nodeParentTitle">Parent Page</label>
    </th>
    <td>
    <select name="nodeParentTitle">
PARENT;
            foreach (NodeParent::getPossibleParentNodesSql($dataStore, $node) as $parent) {
                if (intval($parent['id']) == $node->parent->parentNodeId) {
                    echo '<option selected="Selected" value="' . $parent['title'] . '">' . $parent['title'] . '</option>';
                } else {
                    echo '<option value="' . $parent['title'] . '">' . $parent['title'] . '</option>';
                }
            }
            echo <<<PARENT2
    </select>
    <p class="description">If a user chooses skip on the parent page, this page and all its children will be skipped. If you want the user to always see this page be sure Skippable is unchecked for all its parents.</p>
    </td>
</tr>
PARENT2;
            //SEQUENCE
            $property = 'sequence';
            $value = intval($node->parent->sequence);
            echo <<<PARENT3SEQUENCE
<tr><th>
<label for={$property}>Sequence</>
</th>
<td>
<input type="number" name="{$property}" value ="{$value}"/>
<p class="description">This page's order with respect it's sibling pages (under the same parent).</p>
</td>
</tr>
PARENT3SEQUENCE;
        }

        $property = 'title';
        $value = $node->title ?? Node::getUniqueDefaultTitle($dataStore);
        echo <<<TITLE
<tr><th>
<label for={$property}>Title</>
</th>
<td>
<input type="text" name="{$property}" value = "{$value}"/>
<p class="description">Used to identify this page in lists, buttons, permalinks, and within admin settings. Must be unique.</p>
</td></tr>
TITLE;


        $property = 'heading';
        $value = $node->heading;
        echo <<<HEADING
<tr><th>
<label for={$property}>Heading</>
</th>
<td>
<input type="text" name="{$property}" value = "{$value}"/>
<p class="description">Displayed in large text at top of page. Title will be used if left blank.</p>
</td></tr>
HEADING;

        //SKIPPABLE
        if(strcmp($node->nodeType->title, 'ListNode') != 0 && $node->parent->parentNodeId != null) { //Don't display skippable for ListNode, since it's children can't be visited individually.  Don't allow it for root either.
            $property = 'skippable';
            $value = ($node->skippable) ? ' checked="checked"' : ' ';
            echo <<<SKIPPABLE
<tr><th>
<label for={$property}>Skippable</>
</th>
<td>
<input type="checkbox" name="{$property}" {$value}/>
<p class="description">If checked, a skip button will be displayed on this page.  If skip is clicked, the user will skip all children of this page.</p>
</td></tr>
SKIPPABLE;
        }

        $property = 'childrenAreExclusive';
        $value = ($node->childrenAreExclusive) ? ' checked="checked"' : ' ';
        echo<<<CHILDRENAREEXCLUSIVE
<tr><th>
<label for={$property}>Narrow Path</>
</th>
<td>
<input type="checkbox" name="{$property}" {$value}/>
<p class="description">If this setting is checked only one child of this page can be selected by the user.  This means that as soon as the user accepts (doesn't skip) a child page, the rest of the child pages will be automaticlaly skipped.</p>
</td>
</tr>
CHILDRENAREEXCLUSIVE;

        $updatableProperties = $node->getUpdatableProperties();

        $property = 'defaultPrice';
        if(in_array($property, $updatableProperties)) {
            $value = $node->defaultPrice ?? 0;
            echo '<tr><th><label for=' . $property . '>Default Price</></th>';
            echo '<td><input type="text" step=".01" name="' . $property . '" value = "' . number_format((float) $value, 2, '.' , ',') . '"/>';
            echo '<p class="description">Price. Currency unit can be changing in Path Settings</p>';
            echo '<br>';
        }

        $property = 'allowCustomPrice';
        if(in_array($property, $updatableProperties)) {
            $value = ($node->allowCustomPrice  ? ' checked="checked"' : ' ');
            if(strcmp($node->nodeType->title, 'ListNode') == 0)
                echo '<tr><th><label for=' . $property . '>Allow Custom Prices (Unchecking overrides child settings)</></th>'; //TODO: test this like crazy
            else
                echo '<tr><th><label for=' . $property . '>Allow Custom Prices</></th>';
            echo '';
            echo <<<ALLOWCUSTOMPRICE
<td><input type="checkbox" name="{$property}" {$value}/>
<p class="description">Allow the user to override an item's default price.</p></td></tr>
ALLOWCUSTOMPRICE;

        }

       $property = 'imagePaneHtml';
       if(in_array($property, $updatableProperties)) {
           echo '<tr><th><label>Image Pane</label></th><td>';
           $settings = array('wpautop'=>false);
           //TODO: upgrade to block editor when available: https://github.com/WordPress/gutenberg/pull/13088
           wp_editor(stripslashes(html_entity_decode($node->imagePaneHtml)) ?? null, $property, $settings);
           echo '<p class="description">Your product image. </p></td></tr>';
       }

        $property = 'bodyPaneHtml';
        if(in_array($property, $updatableProperties)) {
            echo '<tr><th><label>Page Body</label></th><td>';
            $settings = array('wpautop'=>false);
            //TODO: upgrade to block editor when available: https://github.com/WordPress/gutenberg/pull/13088
            wp_editor(stripslashes(html_entity_decode($node->bodyPaneHtml)) ?? null, $property, $settings);
            echo '<p class="description">The main display area of your page.  If this will be displayed as a child of a List Page, this text should usually be kept brief</p></td></tr>';
        }

        $property = 'linkPaneHtml';
        if(in_array($property, $updatableProperties)) {
            echo '<br><tr><th><label>Link / Image</label></th><td>';
            echo "<div style='width:300px;'>";
            $settings = array('wpautop'=>false);
            //TODO: upgrade to block editor when available: https://github.com/WordPress/gutenberg/pull/13088
            wp_editor(stripslashes(html_entity_decode($node->linkPaneHtml)) ?? null, $property, $settings);
            echo '</div>';
            echo '<p class="description">(Optional) If not blank, this content will be displayed to the left of the Page Body area.  Meant for an image or a banner for an affiliate sites.</p></td></tr>';
        }

        $property = 'footerPaneHtml';
        if(in_array($property, $updatableProperties)) {
            echo '<tr><th><label>Footer Pane</label></th><td>';
            $settings = array('wpautop'=>false);
            //TODO: upgrade to block editor when available: https://github.com/WordPress/gutenberg/pull/13088
            wp_editor(stripslashes(html_entity_decode($node->footerPaneHtml)) ?? null, $property, $settings);
            echo '<p class="description">Optional page footer (not supported if displayed via a ListNode)</p></td></tr>';
        }

        echo '</table>';

        $buttonText = $newNode ? self::createNodeButtonText : self::updateNodeButtonText;
        echo '<input type="submit" value="' . $buttonText . '"/>';

        self::renderHiddenInputs($node); //should these be in GET?
        echo '</form>';

        //cancel button
        echo '<form method="POST" action="' . admin_url( 'options-general.php?page=golden-paths&flowId=' . $flowId ) . '">';
        echo '<input type="submit" value="Cancel" name="cancel">';
        echo '</form>';
    }


    /**
     * For update case - All existing values must be present in Node, we update every row field
     * @param DataStoreInterface $dataStore
     * @return string returns 'success' or error message
     * @throws Exception Will throw exceptions from DataStore, but also if a POST property doesn't match the selected
     * nodetype
     */
    private static function createOrUpdateNodeFromPost(DataStoreInterface $dataStore) :string
    {
        if(isset($_POST['nodeId'])) {
            $node = NodeFactory::getNewNodeExt($dataStore, $_POST);
            $node->id = $_POST['nodeId'];
            if(isset($_POST['nodeParentTitle'])) //if it's not the root node, init parent object //TODO: handle this better
                $node->parent->childNodeId = $_POST['nodeId'];
            $node->updateDataStore();
        }
        elseif(isset($_POST['nodeParentTitle'])) {
            $node = NodeFactory::getNewNodeExt($dataStore, $_POST);
            //For new nodes, an unset title is allowed (unique default will be used)
            $node->createInDataStore();
        }
        else {
            throw new Exception('must supply nodeId or nodeParentTitle. POST: ' . print_r($_POST, true));
        }

        return 'success';
    }

    /**
     * @param Node|null $node
     */
    public static function renderHiddenInputs($node)
    {
        $hiddenInputs = [];

        if(isset($_GET['flowTitle']))
            $hiddenInputs['flowTitle'] = $_GET['flowTitle'];
        if(isset($_GET['flowId']))
            $hiddenInputs['flowId'] = $_GET['flowId'];

        if(isset($node)) {
            if(isset($node->id))
                $hiddenInputs['nodeId'] = $node->id; //We need to pass nodeId (if set) to indicate this is an edit vs update
            $hiddenInputs['nodeTypeTitle'] = urlencode($node->nodeType->title); //always set if node is
        }

        foreach($hiddenInputs as $property => $value) {
            echo '<input type="hidden" name="' . $property . '" value="' . $value . '"/>';
        }
    }

}
