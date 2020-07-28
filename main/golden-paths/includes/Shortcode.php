<?php /** @noinspection PhpUnused */

/** @noinspection PhpUndefinedFieldInspection */

/** @noinspection PhpUndefinedFunctionInspection */

/**
 * WP-Reactivate
 *
 *
 * @package   WP-Reactivate
 * @author    Pangolin
 * @license   GPL-3.0
 * @link      https://stradella.com
 * @copyright 2017 Pangolin (Pty) Ltd
 */

namespace Stradella\GPaths;
use Exception;

/**
 * @subpackage Shortcode
 */
class Shortcode
{

    protected $dataStore = null;
    const gpaths_cookie_name = "gpaths_session";
    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Return an instance of this class.
     *
     * @param DataStoreInterface $dataStore
     * @return    object    A single instance of this class.
     * @since     1.0.0
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
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @param DataStoreInterface $dataStore
     * @since     1.0.0
     */
    private function __construct(DataStoreInterface $dataStore)
    {
        $plugin = Plugin::get_instance();
        $this->plugin_slug = $plugin->get_plugin_slug();
        $this->version = $plugin->get_plugin_version();
        $this->dataStore = $dataStore;

        add_shortcode('golden-paths', array($this, 'shortcode'));
    }


    /**
     * Handle WP actions and filters.
     *
     * @since    1.0.0
     */
    private function do_hooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_scripts'));
        add_action('wp', array($this, 'gpaths_set_cookie'));
    }

    /**
     * Register frontend-specific javascript
     *
     * @since     1.0.0
     */
    public function register_frontend_scripts()
    {
        wp_register_script(
            $this->plugin_slug . '-shortcode-script',
            plugins_url('assets/js/shortcode.js', dirname(__FILE__)),
            array('jquery'),
            $this->version
        );
        wp_register_style(
            $this->plugin_slug . '-shortcode-style',
            plugins_url('assets/css/shortcode.css', dirname(__FILE__)),
            $this->version
        );
    }

    /**
     * @param $atts
     * @return string|void
     * @throws Exception
     */
    public function shortcode($atts)
    {
        wp_enqueue_script($this->plugin_slug . '-shortcode-script');
        wp_enqueue_style($this->plugin_slug . '-shortcode-style');

        $session = null;

        //TODO:
        //$attributes = apply_filters( 'Gpaths_flow_shortcode_attributes', $attributes );
        //$attributes = self::validateGpathsFlowShortcodeAttributes( $attributes );

        //The only time we actually look for the cookie is if sessionId isn't in GET.
        if(isset($_GET['sessionId']))
            $cookieId = Session::getExistingById($this->dataStore, $_GET['sessionId'])->cookieId;
        //If cookies were enabled they would have been created by now. Use a unique id in the DB instead
        elseif(!isset($_COOKIE[self::gpaths_cookie_name])) {
            $cookieId = md5(uniqid((rand()), true));
        }
        else {
            $cookieId = $_COOKIE[self::gpaths_cookie_name];
        }

        try {
            $session = Session::getNewOrExisting(
                $this->dataStore,
                $cookieId,
                $atts['path_title']
            ); //replace global with something fancy
        } catch (Exception $e) {
            echo "<p>Failed to get session.  Admin: does this shortcode ({$atts['path_title']}) point to an existing Golden Paths path title?</p>";
            $this->dataStore->logToDb('critical', 'couldnt find session');
            return;
        }

        //Allow overriding the current node.  Useful for jumping to nodes in existing sessions from the summary page AND for
        //testing
        if(isset($_GET['startNodeId'])) {
            $session->updateCurrentNodeIdInDataStore($_GET['startNodeId']);
        }

        $object_name = 'gpaths_object_' . uniqid();


        $object = shortcode_atts(
            array(
                'path_title' => 'missing path_title arg',
                'cookie_id' => $cookieId,
                'api_nonce' => wp_create_nonce('wp_rest'),
                'api_url' => rest_url($this->plugin_slug . '/v1/'),
            ),
            $atts,
            'golden-paths'
        );

        wp_localize_script($this->plugin_slug . '-shortcode-script', $object_name, $object);

        return '<div class="golden-paths-shortcode" data-object-id="' . $object_name . '"></div>';
    }

    public function gpaths_set_cookie()
    {
        if (!isset($_COOKIE[self::gpaths_cookie_name])) {
            $session_id = md5(uniqid(rand(), true));
            setcookie(self::gpaths_cookie_name, $session_id, time() + 31559626); //expires in a year
        }
        else { //update expiry
            $session_id = $_COOKIE[self::gpaths_cookie_name];
            setcookie(self::gpaths_cookie_name, $session_id, time() + 31559626); //expires in a year
        }
    }
}
