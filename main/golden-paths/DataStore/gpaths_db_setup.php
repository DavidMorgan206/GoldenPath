<?php /** @noinspection PhpIncludeInspection */

/** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFunctionInspection */

namespace Stradella\GPaths;
use Exception;

global $gpaths_db_version;
$gpaths_db_version = '.5';

class gpaths_db_setup
{
    //private DataStore $DataStore = null;
    private $GpathsModel = null;

    public function __construct(DataStoreInterface $GpathsModel)
    {
        $var = $GpathsModel;
        $this->GpathsModel = $var;
    }

    public function __destruct()
    {
    }

    public function install_db()
    {
        try {
            $this->build_node_tables();
            $this->populate_types();
            $this->build_session_tables();
        } catch (Exception $e) {
            echo $e->getMessage(); //TODO: handle re-activation case more gracefully by checking if db content exists
        }
    }

    public function build_node_tables()
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->nodeTypesTableName} ( 
            id int NOT NULL AUTO_INCREMENT,
            title tinytext NOT NULL UNIQUE,
            PRIMARY KEY ( id )
        ) {$charset_collate};";

        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->nodesTableName} (
            id int NOT NULL AUTO_INCREMENT,
            type_id int NOT NULL,
            title tinytext NOT NULL UNIQUE ,
            heading tinytext,
            skippable int NOT NULL,
            children_are_exclusive int NOT NULL,
            PRIMARY KEY ( id ),
            FOREIGN KEY (type_id)
                REFERENCES {$this->GpathsModel->nodeTypesTableName} (id)
                ON DELETE CASCADE
            ) {$charset_collate};";

        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->landingNodesTableName} (
            node_id int NOT NULL UNIQUE,
            body_pane_html text NOT NULL,
            link_pane_html text NOT NULL,
            FOREIGN KEY(node_id)
                REFERENCES {$this->GpathsModel->nodesTableName} (id)
            ) {$charset_collate};";

        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->affiliateNodesTableName} (
            node_id int NOT NULL UNIQUE,
            body_pane_html text NOT NULL,
            link_pane_html text NOT NULL,
            image_pane_html text NOT NULL,
            footer_pane_html text NOT NULL,
           allow_custom_price int NOT NULL,
            FOREIGN KEY(node_id)
                REFERENCES {$this->GpathsModel->nodesTableName} (id)
            ) {$charset_collate};";

        dbDelta($sql);
        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->listNodesTableName} (
            node_id int UNIQUE NOT NULL,
            body_pane_html text NOT NULL,
            allow_custom_price int NOT NULL,
            FOREIGN KEY(node_id)
                REFERENCES {$this->GpathsModel->nodesTableName} (id)
            ) {$charset_collate};";

        dbDelta($sql);


        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->manualNodesTableName} (
            node_id int UNIQUE NOT NULL,
            body_pane_html text NOT NULL,
            allow_custom_price int NOT NULL,
            default_price decimal(10,2) NOT NULL,
            priceInfoModified datetime  ,
            link_pane_html text NOT NULL,
            FOREIGN KEY(node_id)
                REFERENCES {$this->GpathsModel->nodesTableName} (id)
            ) {$charset_collate};";

        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->nodeChildrenTableName} ( 
            parent_node_id int NOT NULL,
            child_node_id int NOT NULL UNIQUE,
            sequence int NOT NULL,
            FOREIGN KEY(parent_node_id)
                REFERENCES {$this->GpathsModel->nodesTableName} (id)
                ON DELETE CASCADE,
            FOREIGN KEY(child_node_id)
                REFERENCES {$this->GpathsModel->nodesTableName} (id)
                ON DELETE CASCADE,
            UNIQUE KEY relationship (parent_node_id, child_node_id)
            ) {$charset_collate};";

        dbDelta($sql);
    }


    public function build_session_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb === null ? "" : $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        //TODO: ifdef debug
        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->debugLogTableName} ( 
                level varchar(20),
                message text,
                time DATETIME) {$charset_collate};";
        dbDelta($sql);
        //end

        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->flowsTableName} (
            start_node_id int NOT NULL UNIQUE, 
            title tinytext NOT NULL UNIQUE,
            summary_title text NOT NULL,
            summary_body text NOT NULL,
            display_skip_to_summary bool NOT NULL,
            display_total_on_summary bool NOT NULL,
            currency_symbol varchar(8),
            FOREIGN KEY (start_node_id)
                REFERENCES {$this->GpathsModel->nodesTableName} (id)
            ) {$charset_collate};";

        dbDelta($sql);

        //allow null current_node_id to indicate session complete
        //dont allow null flowId to avoid deletion of flow without deletion of child sessions
        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->sessionsTableName} (
            id int NOT NULL AUTO_INCREMENT,
            cookie_id varchar(40) NOT NULL, 
            flow_id int NOT NULL,
            last_modified datetime,
            title varchar(50) NOT NULL,
            current_node_id int NULL, 
            PRIMARY KEY (id), 
            FOREIGN KEY (flow_id)
                REFERENCES {$this->GpathsModel->flowsTableName} (start_node_id)
                ON DELETE CASCADE,
            FOREIGN KEY(current_node_id)
                REFERENCES {$this->GpathsModel->nodesTableName} (id)
                ON DELETE CASCADE,
            UNIQUE (cookie_id, flow_id, title)
        ) {$charset_collate};";

        dbDelta($sql);

        //allow null on custom_price and quantity for cases where skip = true
        $sql = "CREATE TABLE IF NOT EXISTS {$this->GpathsModel->sessionChoicesTableName} (
            id int NOT NULL AUTO_INCREMENT,
            session_id int NOT NULL,
            node_id int NOT NULL,
            skipped int NOT NULL,
            custom_price decimal (10, 2),
            quantity int,
            PRIMARY KEY (id),
            FOREIGN KEY(node_id)
                REFERENCES {$this->GpathsModel->nodesTableName} (id)
                ON DELETE CASCADE,
            FOREIGN KEY(session_id)
                REFERENCES {$this->GpathsModel->sessionsTableName} (id)
                ON DELETE CASCADE,
            UNIQUE (session_id, node_id)
        ) {$charset_collate};";

        dbDelta($sql);
    }


    public function populate_types()
    {
        global $wpdb;

        $sql = "INSERT INTO {$this->GpathsModel->nodeTypesTableName} (title) VALUES('ManualNode') ON DUPLICATE KEY UPDATE title=title";
        dbDelta($sql);

        $sql = "INSERT INTO {$this->GpathsModel->nodeTypesTableName} (title) VALUES('LandingNode') ON DUPLICATE KEY UPDATE title=title";
        dbDelta($sql);

        $sql = "INSERT INTO {$this->GpathsModel->nodeTypesTableName} (title) VALUES('ListNode') ON DUPLICATE KEY UPDATE title=title";
        dbDelta($sql);

        $sql = "INSERT INTO {$this->GpathsModel->nodeTypesTableName} (title) VALUES('AffiliateNode') ON DUPLICATE KEY UPDATE title=title";
        dbDelta($sql);
    }
}

