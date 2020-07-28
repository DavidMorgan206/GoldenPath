<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFunctionInspection */

namespace Stradella\GPaths;

use Exception;

class AdminFlowSummary
{
    /**
     * @param DataStoreInterface $dataStore
     * @param int|null $flowId
     * @param string $updateResults
     * @throws Exception
     */
    public static function render(DataStoreInterface $dataStore, $flowId, string $updateResults)
    {
        $editFlowUri = admin_url('options-general.php?page=golden-paths&tab=editFlow');
        $createFlowUri = admin_url('options-general.php?page=golden-paths&tab=editFlow');
        $pageTitle = 'Golden Paths';
        $flows = Flow::getExistingFlowsSql($dataStore);

echo <<<INTRO
        <h1>{$pageTitle}</h1>
        <p>Welcome!  If you're just getting started, try starting with one of our sample paths.</p>
INTRO;

        echo '<form method="POST" action="' . admin_url('options-general.php?page=golden-paths&tab=flowSummary') . '">';

        //display edit flow picker if there are existing flows
        if(count($flows) > 0) {
            echo '<label for="flowId">Path: </label>';
            echo '<select onchange="this.form.submit()" name="flowId">';
            foreach ($flows as $flow) {
                    if (isset($flowId) && $flowId == intval($flow['start_node_id'])) {
                    echo "<option selected=\"selected\" value={$flow['start_node_id']}>{$flow['title']}</option>";
                } else {
                    echo "<option value={$flow['start_node_id']}>{$flow['title']}</option>";
                }
            }
            echo '</select>';

            echo '<input type="submit" id="editPathButton" value="Edit Path" name="tabEditFlow">';
            echo '<input type="submit" id="deleteFlow" value="Delete Path" name="deleteFlow">';
        }

        echo <<<'CREATEPATHBUTTON'
    <br><br>
    <input type="submit" id="createPathButton" value="Create New Path" name="tabCreateFlow">
</form>
<br>
<hr>
CREATEPATHBUTTON;

        if(count($flows) > 0) {
            $summaryTitle = isset($flowId) ? Flow::getExistingById($dataStore, $flowId)->title : Flow::getExistingById($dataStore, $flows[0]['start_node_id'])->title;
            echo '<h2>' . $summaryTitle . ' Path Summary</h2>';

            //display summary of current flow
            $currentFlow = null;
            if (isset($_POST['flowId'])) {
                $currentFlow = Flow::getExistingById($dataStore, $_POST['flowId']);
            } else { //handle first load by picking the first item, this matches the behavior of the flow <select>
                $currentFlow = Flow::getExistingById($dataStore, $flows[0]['start_node_id']);
            }

            $flowSummary = $currentFlow->getFlowSummary();
            $myListTable = new AdminFlowSummaryTable($flowSummary, $currentFlow->id);
            $myListTable->prepare_items();
            $myListTable->display();
        }
    }
}
