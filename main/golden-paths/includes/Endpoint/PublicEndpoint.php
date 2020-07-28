<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedClassInspection */

/** @noinspection PhpUndefinedFunctionInspection */

/** @noinspection PhpUndefinedFieldInspection */

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

namespace Stradella\GPaths\Endpoint;
use Exception;
use Stradella\GPaths;
use Stradella\GPaths\DataStore;
use Stradella\GPaths\DataStoreInterface;
use Stradella\GPaths\Session;
use Stradella\GPaths\NodeFactory;
use Stradella\GPaths\Node;
use Stradella\Gpaths\SessionChoice;
use WP_REST_Response;
use WP_REST_Server;

/**
 * @subpackage REST_Controller
 */
class PublicEndpoint {
    protected $dataStore = null;
    /**
	 * Instance of this class.
	 *
	 * @since    0.8.1
	 *
	 * @var      object
	 */
	protected static $instance = null;

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @param DataStoreInterface $dataStore
     * @since     0.8.1
     */
	private function __construct(DataStoreInterface $dataStore) {
        $plugin = GPaths\Plugin::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$this->dataStore = $dataStore;
    }

    /**
     * Set up WordPress hooks and filters
     *
     * @return void
     */
    public function do_hooks() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Return an instance of this class.
     *
     * @param DataStoreInterface $dataStore
     * @return    object    A single instance of this class.
     * @since     0.8.1
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
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        $version = '1';
        $namespace = $this->plugin_slug . '/v' . $version;
        $endpoint = '/publicendpoint/';

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_public_page_data' ),
                'permission_callback'   => array( $this, 'example_permissions_check' ),
                'args'                  => array(
                    'path_title' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'path_title obtained from shortcode arg',
                        'validate_callback' => function($param, $request, $key) { return ! empty( $param);} // prevent submission of empty field
                    ),
                    'cookie_id' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'unique user identifier',
                        'validate_callback' => function($param, $request, $key) { return ! empty( $param);} // prevent submission of empty field
                    )
                ),
            ),
        ) );

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => WP_REST_Server::EDITABLE,
                'callback'              => array( $this, 'process_user_choice' ),
                'permission_callback'   => array( $this, 'example_permissions_check' ),
                'args'                  => array(
                    'sessionId' => array(
                        'required' => true,
                        'type' => 'int',
                        'description' => 'session to be updated',
                        'validate_callback' => function($param, $request, $key) { return ! empty( $param);} // prevent submission of empty field
                    ),
                    'nextNode' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'direction to move in tree (down or right)', //TODO: can we get rid of this param and base our logic on : action = "skip" || *
                        'validate_callback' => function($param, $request, $key) { return ! empty( $param);} // prevent submission of empty field
                    ),
                    'action' => array(
                        'required' => false,
                        'type' => 'string',
                        'description' => 'skip or buy',
                        'validate_callback' => function($param, $request, $key) { return ! empty( $param);} // prevent submission of empty field
                    ),
                    'customPrice' => array(
                        'required' => false,
                        'type' => 'number',
                        'description' => 'Override default price (may not be allowed for all node types',
                        'validate_callback' => function($param, $request, $key) { return true;} //TODO: ! empty( $param);} //
                    ),
                    'quantity' => array(
                        'required' => false,
                        'type' => 'number',
                        'description' => 'item quantity (ignored if action != buy)',
                        'validate_callback' => function($param, $request, $key) { return ! empty( $param);} // prevent submission of empty field
                    )
                )
            )
        ) );
    }

    /**
     * Get PublicEndpoint
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_REST_Response
     * @throws Exception
     */
    public function get_public_page_data( $request ) {
        $path_title = $request['path_title'];
        $cookie_id = $request['cookie_id'];

        // Don't return false if there is no option
        if( ! $cookie_id) {
            return new WP_REST_Response( array(
                'message' => 'missing cookie_id'
            ), 400 );
        }
        if( ! $path_title ) {
            return new WP_REST_Response( array(
                'message' => 'missing path_title'
            ), 400 );
        }

        $session = Session::getNewOrExisting(new DataStore(), $cookie_id, $path_title); //get model singleton

        if(!$session->getSessionComplete()) {
            $response = new WP_REST_Response(
                $session->getStateJSON()
                , 200);
        }
        else {
            $response = new WP_REST_Response(
                $session->getSessionSummaryJSON()
                , 200);
        }
        return $response;
    }

    //validate client state matches the server
    public function validateClientStateMatchesDataStore(Session $session, string $currentNode)
    {
        //we're on the root landing node (the first thing the user sees)
        if( (strcmp($currentNode, 'landing')==0 && $session->getCurrentNode()->id == $session->flow->id))
            return true;
        //we're on the flow summary page
        if(strcmp($currentNode, 'summary') == 0 && $session->getSessionComplete())
            return true;
        //we're on a flow node, user should have passed a valid, matching currentNodeId
        if (!($currentNode == $session->getCurrentNode()->id)) {
                error_log('WARN: ignoring user choice with currentNode that doesnt match server state.  currentNode: ' . $currentNode . ' , server currentNode: ' . $session->getCurrentNode());
                return false;
        }
        return true;
    }

    /**
     * Handle GET and POST from client by pulling from the datastore
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_REST_Response
     * @throws Exception
     */
    public function process_user_choice( $request )
    {
        $sessionId = intval($request->get_param('sessionId'));
        $session = Session::getExistingById(new DataStore(), $sessionId);
        $nextNode = $request->get_param('nextNode');
        $currentNode = $request->get_param('currentNode');

        if(!$this->validateClientStateMatchesDataStore($session, $currentNode)) {
                return new WP_REST_Response(
                array(
                    'success' => 'true',
                    'value' => 'Warning: ignored invalid request, multiple tabs?'
                )
            );
        }

        //User wants to start over, clear all choices and start over session
        if($nextNode == 'startOver' )
        {
            $session->startOver();
            return new WP_REST_Response( array(
                'success'   => 'true',
                'value'     => 'successfully started over session'
            ), 200 );

        }

        //user wants to skip to the summary
        if($nextNode == 'Summary') {
            $session->setSessionComplete(); // set's currentNode to null for this session
            return new WP_REST_Response( array(
                                             'success'   => 'true',
                                             'value'     => 'successfully skipping to summary'
                                         ), 200 );
        }

        //User wants to jump to a particular node (by id), change session's current node
        if(is_numeric($nextNode))
        {
            $session->updateCurrentNodeInDataStore(NodeFactory::getById(new DataStore(), $nextNode));
            //for now we never want to allow navigating directly to a child of a ListNode, override and set to parent
            //error_log('checking to see if this is a child of a listNode');
            if( $session->getCurrentNode()->parent->getParent() && //if it has a parent (not the root)
                $session->getCurrentNode()->parent->getParent()->nodeType->title == 'ListNode') {
                $session->updateCurrentNodeInDataStore($session->getCurrentNode()->parent->getParent());
                //error_log('this was a child of a list node, setting current to parent');
            }

            return new WP_REST_Response( array(
                'success'   => 'true',
                'value'     => 'successfully changed sessions current node'
            ), 200 );
        }

       //submit session choices
       //ListNode gets special treatment as it may contain many choices in a single submission
       if(strcmp($session->getCurrentNode()->nodeType->title, 'ListNode') == 0) {
            $choices = array();
            $current = new SessionChoice(new DataStore());
            $current->node=$session->getCurrentNode();
            $current->skipped=false;
            array_push($choices, $current);

            foreach (json_decode($request->get_body(), true) as $choiceArray) {
                $choice = new SessionChoice(new DataStore());
                $choice->node=NodeFactory::getById(new DataStore(), intval($choiceArray['nodeId']));
                $choice->skipped=!$choiceArray['isChecked'];
                if(array_key_exists('price', $choiceArray))
                    $choice->customPrice=floatval($choiceArray['price']);
                if(array_key_exists('quantity', $choiceArray))
                    $choice->quantity=intval($choiceArray['quantity']);
                array_push($choices, $choice);
            }
            $session->handleuserChoice($choices);
        }
        else {
            $choice = new SessionChoice(new DataStore());
            $choice->node=$session->getCurrentNode();
            $choice->skipped=strcmp($request->get_param('action'), 'skip') == 0;
            if (!$choice->skipped) {
                if($request->get_param('quantity'))
                    $choice->quantity=intval($request->get_param('quantity'));
                if($request->get_param('customPrice'))
                    $choice->customPrice=floatval($request->get_param('customPrice'));
            }

            $session->handleUserChoice(array($choice));
        }

        return new WP_REST_Response(
            array(
                'success' => 'true',
                'value' => 'successfully updated user choice'
            ), 200
        );
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function example_permissions_check( $request ) {
        //return current_user_can( 'manage_options' );
        //TODO: something
        return true;
    }
}
