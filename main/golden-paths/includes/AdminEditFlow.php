<?php /** @noinspection PhpUndefinedFunctionInspection */

namespace Stradella\GPaths;
use Exception;

class AdminEditFlow
{
    const createFlowPageTitle = 'Create New Path';
    const updateFlowPageTitle = 'Update Existing Path';
    const createFlowButtonText = 'Create Path';
    const updateFlowButtonText = 'Update Path';


    /**
     * @param DataStoreInterface $dataStore
     * @return int flow Id
     * @throws Exception
     * @throws Exception
     */
    public static function processPostForEdits(DataStoreInterface $dataStore) :int
    {
        switch($_GET['action']){
            case 'create':
            case 'update':
                return AdminEditFlow::createOrUpdateFromPost($dataStore);
                break;
            case 'delete':
                Flow::getExistingById($dataStore, $_GET['flowId'])->deleteFromDataStore(); //deletion comes from flow summary table, uses get
                return null; //null flowId
                break;
            default:
                throw new Exception('unsupported action');
        }
    }

    /**
     * @param DataStoreInterface $dataStore
     * @throws Exception
     */
    public static function render(DataStoreInterface $dataStore){
        $flow = null;
        $flowId = null;
        if(isset($_POST['flowId']))
            $flowId = $_POST['flowId'];
        $newFlow = empty($flowId);
        $pageTitle = $newFlow ? self::createFlowPageTitle : self::updateFlowPageTitle;

        //if we're editing an existing node
        if(!$newFlow)
        {
            $flow = Flow::getExistingById($dataStore, $flowId);
            $flow->refreshFromDataStore();
        }
        else{
            $flow = new Flow($dataStore);
        }

        echo "<h1>{$pageTitle}</h1>";
        echo '<form method="POST" action="' . admin_url( 'options-general.php?page=golden-paths&tab=flowSummary&actionObject=flow&action=update') . '">';
        //if existing, we need to send flowId to keep it as the select flow on flow summary page
        if(!$newFlow)
            echo '<input type="hidden" name="flowId" value="' . $flowId . '"/>';

        echo '<table class="form-table">';

        echo '<tr>';
        echo '<th>';
        $property = 'flowTitle';
        $value = isset($flow->title) ? $flow->title: Flow::getUniqueDefaultTitle($dataStore);
        echo '<label for=' . $property . '>Title</>';
        echo '</th><td>';
        echo '<input type="text" name="' . $property . '" value = "' . $value . '" class=regular-text"/>';
        echo '<p class="description" id="flowTitleDescription">This text will be shown to the user throughout their session. </p>';
        echo '</td></tr>';

        echo <<<SHORTCODE
<tr>
<th>Short Code</th>
<td>[golden-paths path_title="{$value}"]<p class="description">Copy and paste this text to any of your pages to provide an entry point to this Path</p></td>
</tr>
SHORTCODE;

        echo '<tr>';
        echo '<th>';
        $property = 'currencySymbol';
        $value = isset($flow->$property) ? $flow->$property : '';
        echo '<label for=' . $property . '>Currency Symbol</>';
        echo '</th><td>';
        echo '<input class=regular-text type="text" name="' . $property . '" value = "' . $value . '" maxlength="1"/>';
        echo '<p class="description">Displayed in front of all prices, single character recommended ($, €...)  </p>';
        echo '</td></tr>';

        echo '<tr>';
        echo '<th>';
        $property = 'displaySkipToSummary';
        $value = ($flow->$property) ? ' checked="checked"' : ' ';
        echo '<label for=' . $property . '>Skip To Summary Button</>';
        echo '</th><td>';
        echo '<input type="checkbox" name="' . $property . '" ' . $value . '/>';
        echo '<p class="description">If checked, each page will have a button at the bottom to allow skipping to the Path Summary.</p>';
        echo '</td></tr>';

        echo '<tr>';
        echo '<th>';
        $property = 'displayTotalPriceOnSummary';
        $value = ($flow->$property) ? ' checked="checked"' : ' ';
        echo '<label for=' . $property . '>Display Total on Summary</>';
        echo '</th><td>';
        echo '<input type="checkbox" name="' . $property . '" ' . $value . '/>';
        echo '<p class="description">If checked, a total price will be displayed based on the user\'s choices. This may not make sense for informational content or if you can\'t display static prices per affiliate marketing policy (Amazon).</p>';
        echo '</td></tr>';

        echo '<tr>';
        echo '<th>';
        $property = 'summaryTitle';
        $value = isset($flow->$property) ? $flow->$property : '';
        echo '<label for=' . $property . '>Summary Title</>';
        echo '</th><td>';
        echo '<input class=regular-text type="text" name="' . $property . '" value = "' . $value . '"/>';
        echo '<p class="description">(optional) This text appears at the top of the final Path Summary page.</p>';
        echo '</td></tr>';

        echo '<tr>';
        echo '<th>';
        $property = 'summaryBody';
        $value = isset($flow->$property) ? $flow->$property : '';
        echo '<label for=' . $property . '>Summary Body</>';
        echo '</th><td>';
        wp_editor($value, $property);
        echo '<p class="description">(optional) This pane appears above Path Summary results on the final Path Summary Page.</p>';
        echo '</td></tr>';
        echo '</table>';
        echo '<br><br>';

        $buttonText = $newFlow ? self::createFlowButtonText : self::updateFlowButtonText;
        echo '<input type="submit" class="button button-primary" value="' . $buttonText . '"/>';
        echo '</form>';

        echo '<form method="POST" action="' . admin_url( 'options-general.php?page=golden-paths&flowId=' . $flowId ) . '">';
        echo '<br>';
        echo '<input type="submit" class="button" value="Cancel" name="cancel">';
        echo '</form>';
    }


    /**
     * @param DataStoreInterface $dataStore
     * @return int returns current flowId
     * @throws Exception
     */
    private static function createOrUpdateFromPost(DataStoreInterface $dataStore) :int
    {
        $flow = new Flow($dataStore);
        $flow->summaryBody=$_POST['summaryBody'];
        $flow->title = $_POST['flowTitle'];
        $flow->summaryTitle=$_POST['summaryTitle'];
        $flow->currencySymbol=$_POST['currencySymbol'];
        $flow->displayTotalPriceOnSummary=isset($_POST['displayTotalPriceOnSummary']);
        $flow->displaySkipToSummary=isset($_POST['displaySkipToSummary']);

        if(isset($_POST['flowId'])) {
            $flow->id=$_POST['flowId'];
            $flow->updateDataStore();
            return $flow->id;
        }
        else{
            $landingNode = NodeFactory::getNewDefaultLandingNode($dataStore);
            $flow->id = $landingNode->id;
            $flow->createInDataStore();
            return $flow->id;
        }
    }

    /**
     * @param DataStoreInterface $dataStore
     * @param int $flowId
     * @return string|null
     * @throws Exception
     */
    public static function deleteFlow(DataStoreInterface $dataStore, int $flowId) :string
    {
        $flow = Flow::getExistingById($dataStore, $flowId);
        return $flow->deleteFromDataStore();
    }
}
