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
     * Class constructor.
     */
    public function __construct() {
        $this->cloud_key  = get_setting( 'cloud' , 'cloud_token');
        $this->cloud_key_is_verified  = get_setting( 'cloud' , 'token_verified');
        $this->cloud_snippets = get_transient('cs_cloud_snippets');
        $this->init();

        return $this->cloud_snippets;
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
        //Check if cloud api key is set and valid - if not then display error notice
        if(!$this->is_cloud_key_valid()){
            return $this->display_cloud_key_notice();
        }
        //Check if any cloud snippets already stored as a transient
        if( empty($this->cloud_snippets) ){
            //If no cloud snippet transient object then grab cloud snippets from api and store as transient
            $this->cloud_snippets = $this->get_cloud_snippets();
            set_transient( 'cs_cloud_snippets', $this->cloud_snippets, ( DAY_IN_SECONDS * self::DAYS_TO_STORE_CS ) );
            $this->check_snippets_for_cloud_updates($this->cloud_snippets);
        }
    }

    /**
     * Check if Cloud Key is set and verified
     *
     * @return bool
     */
    public function is_cloud_key_valid(){    
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
    public function get_cloud_snippets(){
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
        //wp_die( print_r ($snippets['data']) );
        return  $snippets['data'];
    }

    /**
     * Check local snippets for cloud updates
     *
     * @param object $cloud_snippets json data
     * @return void
     */
    public function check_snippets_for_cloud_updates($cloud_snippets){
        $cloud_id_rev_array = []; //e.g. cloud_id => revision -> [163_1 => 2 ]
        $cloud_ids = [];
        $local_to_cloud_map  = []; //e.g. local_id, cloud_id, downloaded, update_available
        //string to array
        //$cloud_snippets = json_decode($cloud_snippets_transient, false);

        foreach ($cloud_snippets as $cloud_snippet) {
            //Get cloud revision and id and store in array
            $cloud_id_rev_array[$cloud_snippet['cloud_id']] = $cloud_snippet['revision'];
            $cloud_ids[] = $cloud_snippet['cloud_id'];            
        }

        //Get local snippets that based on cloud ids from codevault
        $local_snippets = get_snippets( array(), true, $cloud_ids );

        //wp_die(var_dump($local_snippets));

        //If there are local snippets that have been downloaded then check if they need updating
        if(count($local_snippets) > 0) {
            foreach ($local_snippets as $local_snippet) {
                //Check if local revision is less than cloud revision
                if( intval($local_snippet->revision) < intval($cloud_id_rev_array[$local_snippet->cloud_id]) ){
                    //add to cloud items to show update array
                    $local_to_cloud_map[] = [
                        'local_id' => $local_snippet->id,
                        'cloud_id' => $local_snippet->cloud_id,
                        'downloaded' => 'true',
                        'update_available' => 'true'
                    ];
                }else{
                    //add to cloud items to show download array
                    $local_to_cloud_map[] = [
                        'local_id' => $local_snippet->id,
                        'cloud_id' => $local_snippet->cloud_id,
                        'downloaded' => 'true',
                        'update_available' => 'false'
                    ];
                }
            }
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
				'downloaded'	=> 'true',
				'update_available' => 'false',
			);
			set_transient( 'cs_local_to_cloud_map', $local_to_cloud_map, ( DAY_IN_SECONDS * self::DAYS_TO_STORE_CS ) );

            //Update cloud snippet transient
            delete_transient( 'cs_cloud_snippets' );
            $cloud_snippets = self::get_cloud_snippets();
            set_transient( 'cs_cloud_snippets', $cloud_snippets, ( DAY_IN_SECONDS * self::DAYS_TO_STORE_CS ) );

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
}