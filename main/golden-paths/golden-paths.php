<?php /** @noinspection PhpUnusedLocalVariableInspection */

/** @noinspection PhpIncludeInspection */

/** @noinspection PhpUndefinedFunctionInspection */

/**
 * @wordpress-plugin
 * Version:          .9
 * Plugin Name:		Golden Paths
 * Plugin URI: 		https://stradellacreative.com/GoldenPaths
 * Description:		Quickly create structured user flows (Paths) like Listicles, Buying Guides, How-To's, Wizards, etc.
 * Author:			Stradella Creative
 * Author URI:		https://stradellacreative.com
 */


namespace Stradella\GPaths;
use Exception;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'GOLDEN_PATHS_VERSION', '1.0.0' );

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
require_once('DataStore\DataStore.php');
global $GpathsModel;
$GpathsModel = new DataStore();


global $enable_gpaths_sql_logging;
//$enable_gpaths_sql_logging = $true;

/**
 * Autoloader
 *
 * @param string $class The fully-qualified class name.
 * @return void
 *
 *  * @since 1.0.0
 */
spl_autoload_register(function ($class) {

    // project-specific namespace prefix
    $prefix = __NAMESPACE__;

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/includes/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Initialize Plugin
 *
 * @since 1.0.0
 */
function init() {
    global $GpathsModel;
	$wpr = Plugin::get_instance();
	$gpaths_shortcode = Shortcode::get_instance($GpathsModel);
	$gpaths_admin = Admin::get_instance($GpathsModel);
	$gpaths_rest = Endpoint\PublicEndpoint::get_instance($GpathsModel);
}
add_action( 'plugins_loaded', 'Stradella\\GPaths\\init' );

function install() {
    global $GpathsModel;
    require_once('model\gpaths_db_setup.php');
    $dbsetup = new gpaths_db_setup($GpathsModel);
    $dbsetup->install_db();
}


/**
 * Register activation and deactivation hooks
 */
register_activation_hook( __FILE__, array( 'Stradella\\GPaths\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Stradella\\GPaths\\Plugin', 'deactivate' ) );

