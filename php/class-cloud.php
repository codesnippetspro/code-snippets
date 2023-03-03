<?php


namespace Code_Snippets;

use function Code_Snippets\Settings\get_setting;

/**
 * Functions used to manage cloud sync
 *
 * @package Code_Snippets
 */
class CS_Cloud {

    /**
     * Cloud API URL
     * 
     * @var String
     */
    const CLOUD_API_URL = 'https://codesnippets.cloud/api/v1/';


    /**
     * Cloud URL
     * 
     * @var String
     */
    const CLOUD_URL = 'https://codesnippets.cloud/';

    /**
     * Days to store for cloud snippets
     * 
     * @var Int
     */
    const DAYS_TO_STORE_CS = 1;


    /**
     * Cloud API key
     * 
     * @var String
     */
    private $cloud_key = '';

    /**
     * Cloud API Key Verification Status 
     * 
     * @var Bool
     */
    private $cloud_key_is_verified = false;

    /**
     * Cloud Snippets Object 
     * 
     * @var array
     */
    public $cloud_snippets;

    /**
     * Codevault Snippets Object
     * 
     * @var array
     */
    public $codevault_snippets;

    /**
     * Local to Cloud Snippets Map Object
     * 
     * @var array
     */
    public $local_to_cloud_map;


    /**
     * Class constructor.
     */
    public function __construct() {
        $this->cloud_key  = get_setting( 'cloud' , 'cloud_token');
        $this->cloud_key_is_verified  = get_setting( 'cloud' , 'token_verified');
        $this->codevault_snippets = get_transient('cs_codevault_snippets');
        $this->local_to_cloud_map = get_transient('cs_local_to_cloud_map');
        $this->init();

        //wp_die( var_dump( get_snippets( array()) ) );
        return $this->codevault_snippets;
    }
    
    /**
     * Initialise class functions.
     * 
     * 
     * @return String
     */
    public function init() {
        //Enqueue Prism Files
        $this->enqueue_all_prism_themes();
        //If no codevault snippets transient object then grab from api and store as transient
        if( empty($this->codevault_snippets) ){
            $this->codevault_snippets = $this->get_codevault_snippets();
            set_transient( 'cs_codevault_snippets', $this->codevault_snippets, ( DAY_IN_SECONDS * self::DAYS_TO_STORE_CS ) );
        }
        //If no local to cloud map transient object then generate this map and store as transient
        if( empty($this->local_to_cloud_map) ){
            $this->local_to_cloud_map = $this->generate_local_to_cloud_map();
        }
        $this->process_refresh_synced_data_request();
    }

    /**
     * Check if Cloud Key is set and verified
     *
     * @return bool
     */
    public function is_cloud_connection_available(){    
        if($this->cloud_key == ''){
            return false;
        }
        if($this->cloud_key_is_verified == 'false'){
            return false;
        }
        return true;
    }

    /**
     * Display Cloud Key Notice
     *
     * @return String
     */
    public function display_cloud_key_notice(){
        $message = sprintf( __( 'Please enter a valid Cloud API Token in the <a href="%s">Cloud Settings</a> to enable Cloud Sync.', 'code-snippets' ), esc_url( admin_url( 'admin.php?page=snippets-settings&section=cloud' ) ) );
        $class = 'notice notice-error is-dismissible';
        return printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
    }

    /**
     * Get cloud snippets
     * Sends a request to the cloud api to get all snippets
     *
     * @return array|bool
     */
    public function get_codevault_snippets(){
        $url = self::CLOUD_API_URL . 'private/allsnippets';
        $cloud_api_key  = get_setting( 'cloud' , 'cloud_token');
        $args = array(
            'headers'     => array(
                'Authorization' => 'Bearer ' . $cloud_api_key,
            ),
        ); 

        $response = wp_remote_get( $url, $args );
        if ( is_wp_error( $response ) ) {

            return false;
        }
        $body = wp_remote_retrieve_body( $response );
        $snippets = json_decode( $body, true );
        return  $snippets['snippets'];
    }

    
    /**
     * Create Local to Cloud Map to keep track of local snippets
     * that have been synced to the cloud
     * 
     * @return void
     */
    public function generate_local_to_cloud_map(){
        $snippet_revision_array = []; //e.g. cloud_id => revision -> [163_1 => 2 ]
        $local_to_cloud_map  = []; //e.g. local_id, cloud_id, downloaded, update_available
        $codevault_snippet_ids = [];

        //wp_die(var_dump($this->codevault_snippets));
        foreach ($this->codevault_snippets as $codevault_snippet) {
            //Get snippet revision and id and store in array
            $snippet_revision_array[$codevault_snippet['cloud_id']] = $codevault_snippet['revision'];  
            $codevault_snippet_ids[] = $codevault_snippet['cloud_id'];         
        }

        //Get all local snippets stored in db
        $local_snippets = get_snippets( array() );
        //Loop through local snippets
        foreach ($local_snippets as $local_snippet) {
            //check if cloud id is null and if so skip this item
            if($local_snippet->cloud_id == NULL){
                continue;
            }
            //Check if snippet is a synced codevault snippet
            if( in_array($local_snippet->cloud_id, $codevault_snippet_ids) ){
                $in_codevault = true;
                //Check if local revision is less than cloud revision
                if( intval($local_snippet->revision) < intval($snippet_revision_array[$local_snippet->cloud_id]) ){ 
                    $update_available = true;
                }else{
                    $update_available = false;
                }
            }else{
                $cloud_snippet_revision = $this->get_cloud_snippet_revision($local_snippet->cloud_id);
                $in_codevault = false;
                if( intval($local_snippet->revision) < intval($cloud_snippet_revision) ){   
                    $update_available = true;
                }else{
                    $update_available = true;
                }
            }

            $local_to_cloud_map[] = [
                'local_id' => $local_snippet->id,
                'cloud_id' => $local_snippet->cloud_id,
                'in_codevault' => $in_codevault,
                'update_available' => $update_available,
            ];
        }
        set_transient( 'cs_local_to_cloud_map', $local_to_cloud_map, ( DAY_IN_SECONDS * self::DAYS_TO_STORE_CS ) );
    }

    /**
	 * Enqueue all available Prism themes.
	 *
	 * @return void
	 */
	public function enqueue_all_prism_themes() {
        Frontend::register_prism_assets();

		foreach ( Frontend::get_prism_themes() as $theme => $label ) {
            wp_enqueue_style( Frontend::get_prism_theme_style_handle( $theme ) );
        }
        
		wp_enqueue_style( Frontend::PRISM_HANDLE );
        wp_enqueue_script( Frontend::PRISM_HANDLE );
	}

    /**
	 * Process a request to refresh all synced data.
	 *
	 * @return void
	 */
    public function process_refresh_synced_data_request() {
        $refreshed = false;
        if( isset( $_GET['refresh'] ) &&  $_GET['refresh'] == 'true' ){
            $refreshed = $this->refresh_synced_data();
        }
        if ( $refreshed ) {
            return [
                'success' => true,
                'message' => __( 'Synced data refreshed successfully', 'code-snippets' ),
            ];
        }
    }

    /**
	 * Refresh all transient data.
	 *
	 * @return void
	 */
	public function refresh_synced_data() {
        //Delete local to cloud map transient
        delete_transient( 'cs_local_to_cloud_map' );
        //Delete cloud snippets transient
        delete_transient( 'cs_codevault_snippets' );
        
        //Get cloud snippets and store in transient
        $this->codevault_snippets = $this->get_codevault_snippets();
        set_transient( 'cs_codevault_snippets', $this->codevault_snippets, ( DAY_IN_SECONDS * self::DAYS_TO_STORE_CS ) );

        //Get local to cloud map and store in transient
        $this->local_to_cloud_map = $this->generate_local_to_cloud_map();

        return true;
	}

    /** Static Methods **/

    /**
     * Store Snippets in Cloud - Static Function
     *
     * @param Snippet $snippets json data
     *
     */
    public static function store_snippets_to_cloud($snippets){
        $cloud_api_key  = get_setting( 'cloud' , 'cloud_token');
		/** Snippet @var Snippet $snippet */
		foreach ( $snippets as $snippet ) {
			//send post request to cs store api with snippet data
			$cs_stre_api_response = wp_remote_post( self::CLOUD_API_URL . 'private/storesnippet', array(
				'method' => 'POST',
				'headers' => array(
					'Authorization' => 'Bearer ' . $cloud_api_key,
				),
				'body' => array(
					'name' => $snippet->name,
					'desc' => $snippet->desc,
					'code' => $snippet->code,
					'scope' => $snippet->scope,
					'revision' => $snippet->revision,
				),
			));		
			//get response body
			$body = wp_remote_retrieve_body( $cs_stre_api_response );
			//decode json response
			$cloud_snippet = json_decode( $body, true );
			//update snippet fields
			$update = update_snippet_fields( $snippet->id,
                array(
				'cloud_id' => $cloud_snippet['cloud_id'],
				'revision' => $snippet->revision ? $snippet->revision : $cloud_snippet['revision'],)
            );
			//update local to cloud map transient
			$local_to_cloud_map = get_transient( 'cs_local_to_cloud_map' );
			$local_to_cloud_map[] = array(
				'local_id' 		=> $snippet->id,
				'cloud_id' 		=> $cloud_snippet['cloud_id'],
				'in_codevault'	=> true,
				'update_available' => false,
			);
			set_transient( 'cs_local_to_cloud_map', $local_to_cloud_map, ( DAY_IN_SECONDS * self::DAYS_TO_STORE_CS ) );

            //Update codevault snippet transient
            delete_transient( 'cs_codevault_snippets' );
            $cloud_snippets = self::get_codevault_snippets();
            set_transient( 'cs_codevault_snippets', $cloud_snippets, ( DAY_IN_SECONDS * self::DAYS_TO_STORE_CS ) );

		}	
    }

    /**
     * Delete Snippet from Local-Cloud Map -> Static Function
     *
     * @param Snippet $snippet id - local snippet id
     * @return void
     */
    public static function delete_snippet_from_transient_data($snippet_id){
        //Remove from local to cloud map
        $local_to_cloud_map = get_transient( 'cs_local_to_cloud_map' );
        foreach ($local_to_cloud_map as $key => $value) {
            if($value['local_id'] == $snippet_id){
                unset($local_to_cloud_map[$key]);
            }
        }
        set_transient( 'cs_local_to_cloud_map', $local_to_cloud_map, ( DAY_IN_SECONDS * self::DAYS_TO_STORE_CS ) );
    }

    /**
     * Search Code Snippets Cloud -> Static Function
     *
     * @param String $search
     * @param Int $page default 0
     * 
     * @return Object $cloud_snippets
     */
    public static function search_cloud_snippets($search, $page = 0){
        //construct api endpoint request url
        $api_url = self::CLOUD_API_URL . 'public/search';

        //Send GET request to request url with search query
        $response = wp_remote_get( $api_url . '?s=' . $search . '&page=' . $page);

        //get response body
        $body = wp_remote_retrieve_body( $response );
        //decode json response
        $cloud_snippets = json_decode( $body, true );
        //return cloud snippets
        return $cloud_snippets;
    }

    /**
     * Get Single Cloud Snippets -> Static Function
     * 
     * @param String $cloud_id
     *
     * @return Object $cloud_snippets
     */
    public static function get_single_cloud_snippet($cloud_id){
        //construct api endpoint request url
        $api_url = self::CLOUD_API_URL . 'public/getsnippet/' . $cloud_id;
        $site_token = get_setting('cloud' , 'local_token');
        //Get site host name
        $site_host = parse_url( get_site_url(), PHP_URL_HOST );
        //Send GET request to request url with search query
        $response = wp_remote_get( $api_url . '?site_host=' . $site_host . '&site_token=' . $site_token);
        //get response body
        $body = wp_remote_retrieve_body( $response );
        //decode json response
        $cloud_snippet = json_decode( $body, true );
        //return cloud snippets
        return $cloud_snippet;
    }

    /**
     * Get Single Cloud Snippets Revsion -> Static Function
     * 
     * @param String $cloud_id
     *
     * @return String $revision
     */
    public static function get_cloud_snippet_revision($cloud_id){
        //construct api endpoint request url
        $api_url = self::CLOUD_API_URL . 'public/getsnippetrevision/' . $cloud_id;
        //Send GET request to request url with search query
        $response = wp_remote_get( $api_url );
        //get response body
        $body = wp_remote_retrieve_body( $response );
        //decode json response
        $cloud_snippet_revision = json_decode( $body, true );
        //return cloud snippets revision number
        return $cloud_snippet_revision['snippet_revision'];
    }

   
}