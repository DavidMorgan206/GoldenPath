<?php /** @noinspection PhpUnused */

//This file is mostly unchanged from the wp-reactivate boilerplate, which is not PSR12.  Leaving it alone.
/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUndefinedFieldInspection */


namespace Stradella\GPaths;
use Exception;

/**
 * @subpackage Admin
 */
class Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Plugin basename.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_basename = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;


	protected $dataStore = null;
	protected $gpaths_active_tab = null;

    /**
     * Return an instance of this class.
     *
     * @param DataStoreInterface $dataStore
     * @return    object    A single instance of this class.
     * @since     1.0.0
     *
     */
	public static function get_instance(DataStoreInterface $dataStore) {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self($dataStore);
			self::$instance->do_hooks();
		}

		return self::$instance;
	}

    /**
     * Render the settings page for this plugin.
     *
     */
    public function display_plugin_admin_page()
    {
        //error_log('POST: ' . print_r($_POST, true));
        //error_log('GET: ' . print_r($_GET, true));

        $flowId = null;
        if(isset($_POST['flowId']))
            $flowId = $_POST['flowId'];
        $updateResults = ''; //stores the result of the any create/update operation if applicable

        try {
            //perform CRUD based on Post values
            if (isset($_GET['actionObject'])) {
                switch ($_GET['actionObject']) {
                    case 'flow':
                        $flowId = AdminEditFlow::processPostForEdits($this->dataStore);
                        $updateResults = 'success';
                        break;
                    case 'node':
                        $updateResults = AdminEditNode::processPostForEdits($this->dataStore);
                        break;
                    default:
                        throw new Exception('unsupported actionObject');
                }
            }
            if (isset($_POST['deleteFlow'])) {
                $updateResults = AdminEditFlow::deleteFlow($this->dataStore, $flowId);
            }

            if(strlen($updateResults) > 0)
                echo "<p id=updateResults>Operation Result: {$updateResults}</p>";
        }
        catch(Exception $e) {
            $updateResults = 'FAILED';
            echo "<p id=updateResults>Operation Result: {$updateResults}</p>";
            self::renderErrorMessage('Error encountered saving changes: ', $e);
            $_GET = []; //reset all parameters to send user back to flowSummary in clean state
            $_POST = [];
        }

        try {
            if (isset($_GET['tabEditNode'])) {
                AdminEditNode::render($this->dataStore);
            }
            elseif (isset($_POST['tabEditFlow'])) {
                AdminEditFlow::render($this->dataStore);
            }
            elseif (isset($_POST['tabCreateFlow'])) {
                unset($_POST['flowId']);
                AdminEditFlow::render($this->dataStore);
            }
            else {
                AdminFlowSummary::render($this->dataStore, $flowId, $updateResults);
            }
        }
        catch(Exception $e) {
            self::renderErrorMessage('Error encountered rendering settings page: ',  $e);
        }
    }

    public static function renderErrorMessage(string $metaMessage, Exception $e) {
        echo "<p style='color: red'><b>{$metaMessage}</b><br>{$e->getMessage()}<br>{$e->getTraceAsString()}</p>";
        //TODO: disable all but metamessage for production?
    }

    /**
     * Initialize the plugin by loading admin scripts & styles and adding a
     * settings page and menu.
     *
     * @param DataStoreInterface $dataStore
     * @since     1.0.0
     */
	private function __construct(DataStoreInterface $dataStore) {
		$plugin = Plugin::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$this->version = $plugin->get_plugin_version();
		$this->dataStore = $dataStore;

		$this->plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
	}

	/**
	 * Handle WP actions and filters.
	 *
	 * @since 	1.0.0
	 */
	private function do_hooks() {
		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		// Add plugin action link point to settings page
		add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'add_action_links' ) );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug . '-style', plugins_url( 'assets/css/admin.css', dirname( __FILE__ ) ), array(), $this->version );
		}
		return;
	}

	/**
	 * Register and enqueue admin-specific javascript
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {

			//TODO: remove react? //wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version );

			wp_localize_script( $this->plugin_slug . '-admin-script', 'gpaths_object', array(
				'api_nonce'   => wp_create_nonce( 'wp_rest' ),
				'api_url'	  => rest_url( $this->plugin_slug . '/v1/' ),
				)
			);
		}

		return;
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		/*
		 * Add a settings page for this plugin to the Settings menu.
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Golden Paths', $this->plugin_slug ),
			__( 'Golden Paths', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);
	}

    /**
     * Add settings action link to the plugins page.
     *
     * @param $links
     * @return array
     * @since    1.0.0
     */
	public function add_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>',
			),
			$links
		);
	}
}



