<?php /** @noinspection PhpUnused */

/** @noinspection PhpUndefinedFieldInspection */

/** @noinspection PhpUndefinedFunctionInspection */

/** @noinspection PhpIncludeInspection */

/** @noinspection PhpUndefinedClassInspection */

namespace Stradella\GPaths;
use Exception;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
use WP_List_Table;

class AdminFlowSummaryTable extends WP_List_Table
{
    private $data;
    public $items; //required by WP_LIST_TABLE
    private $flowId;

    public function __construct(array $data, int $flowId)
    {
        $this->data = $data;
        $this->flowId = $flowId;
        /** @noinspection PhpParamsInspection */
        parent::__construct(); //TODO: look at WP_List_Table to see why passing/using parent's $data fails
    }

    public function get_columns()
    {
        return array(
            'title' => 'Title',
            'nodeTypeTitle' => 'Type',
            'edit' => 'Edit',
            'addChild' => 'AddChild',
            'sequence' => 'Sequence',
            'skippable' => 'Skippable',
            'delete' => 'Delete'
        );
    }

    public static function getTitlePrefix(array $item) : string
    {
        if($item['treeDepth'] < 1)
            return '';
        elseif($item['lastChild'] == 1)
            return '&nbsp&nbsp' . str_repeat('│&nbsp',$item['treeDepth'] - 1) . '└─ ';
        else
            return '&nbsp&nbsp' . str_repeat('│&nbsp', $item['treeDepth'] - 1) . '├─ ';
    }

    /**
     * @param $item
     * @param $column_name
     * @return string
     *
     * @throws Exception
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'title':
                return self::getTitlePrefix($item) . $item[$column_name];
            case 'nodeTypeTitle':
                return NodeFactory::getFriendlyTypeName($item['nodeTypeTitle']);
            case 'sequence':
                if($item['treeDepth'] == 0)
                    return ''; //dont list sequence for root node to make it look more special
                return $item['sequence'];
            case 'skippable':
                return $item['skippable'] == 1 ? 'true' : 'false';
            case 'edit':
                return '<a href="' . admin_url(
                        'options-general.php?page=golden-paths&tabEditNode=&flowId=' . $this->flowId . '&nodeId=' . $item['nodeId']
                    ) . '">Edit</a>';
            case 'addChild':
                if($item['childOfListNode']) //don't allow adding children to children of list nodes, they aren't reachable
                    return '';
                return '<a href="' . admin_url(
                        'options-general.php?page=golden-paths&tabEditNode=1&flowId=' . $this->flowId . '&nodeParentTitle=' . urlencode($item['title'])
                    ) . '">Add Child</a>';
            case 'delete':
                if(strcmp($item['nodeTypeTitle'], 'LandingNode') == 0 && $item['treeDepth'] == 0)
                    return '';
                return '<a href="' . admin_url(
                        'options-general.php?page=golden-paths&actionObject=node&flowId=' . $this->flowId . '&action=delete&nodeId=' . $item['nodeId']
                    ) . '">Delete</a>';
            default:
                throw new Exception('AdminFlowSummaryTable unsupported column name');
        }
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $this->data;
    }
}


