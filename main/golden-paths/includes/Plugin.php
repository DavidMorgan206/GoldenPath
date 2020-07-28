<?php /** @noinspection PhpUnused */

/** @noinspection PhpUndefinedFieldInspection */

/** @noinspection PhpUndefinedFunctionInspection */

namespace Stradella\GPaths;
use Exception;

/**
 * @subpackage Plugin
 */
class Plugin {

	/**
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'golden-paths';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Setup instance attributes
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		$this->plugin_version = GOLDEN_PATHS_VERSION;
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    string Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return the plugin version.
	 *
	 * @since    1.0.0
	 *
	 * @return    string Plugin slug variable.
	 */
	public function get_plugin_version() {
		return $this->plugin_version;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        global $GpathsModel;
        require_once(dirname(__DIR__) . '/DataStore/gpaths_db_setup.php');
        $dbsetup = new gpaths_db_setup($GpathsModel);
        $dbsetup->install_db();
	}

    /**
     * Fired when the plugin is deactivated.
     *
     * @throws Exception
     * @since    1.0.0
     */
	public static function deactivate() {
	}


	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
