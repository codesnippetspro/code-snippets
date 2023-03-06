<?php


namespace Code_Snippets;

use function Code_Snippets\Settings\get_setting;
use Code_Snippets\Cloud_List_Table;

/**
 * Functions used to manage cloud sync
 *
 * @package Code_Snippets
 */
class CS_Cloud {

    /**
     * Cloud API URL
     * 
     * @var string
     */
    const CLOUD_API_URL = 'https://codesnippets.cloud/api/v1/';


    /**
     * Cloud URL
     * 
     * @var string
     */
    const CLOUD_URL = 'https://codesnippets.cloud/';

    /**
     * Days to store for cloud snippets
     * 
     * @var int
     */
    const DAYS_TO_STORE_CS = 1;


    /**
     * Cloud API key
     * 
     * @var string
     */
    private $cloud_key = '';

    /**
     * Cloud API Key Verification Status 
     * 
     * @var bool
     */
    private $cloud_key_is_verified = false;

    /**
     * Cloud List Table Object
     * 
     * @var Cloud_List_Table
     */
    public $cloud_list_table;

    /**
     * Class constructor.
     */
    public function __construct() {
        $this->cloud_key  = get_setting( 'cloud' , 'cloud_token');
        $this->cloud_key_is_verified  = get_setting( 'cloud' , 'token_verified');
        add_action( 'init', array( $this, 'init' ) );
        
    }

    /**
     * Initialise class functions.
     * This is called on the init hook and is used to get all cloud snippets if a cloud api key is set
     * 
     * @return void
     */
    public function init() {
        $upload_dir = $this->get_cloud_snippets_path();

        //Check if database changes are made
        $this->check_db_updated_for_cloud();

        //Check if any cloud snippets are stored in upload folder and any updates are available
        $this->retrieve_cloud_snippets_and_check_updates($upload_dir);

        //Check if cloud api key is set and valid
        if($this->is_cloud_key_valid()){
            //Get cloud snippet data from json file in upload folder
            $file = glob($upload_dir . '*'); 
            $cloud_snippets = json_decode(file_get_contents($file[0]), true);
            $this->cloud_list_table = new Cloud_List_Table($cloud_snippets);
            $this->cloud_list_table->prepare_items(); 
            $this->cloud_list_table->display(); 
        }else{
            
            return print sprintf( '<p>There seems to be a problem with the Cloud API Token, please check this is correct by going to the <a href="%s">%s</a></p>', esc_url( admin_url( 'admin.php?page=snippets-settings&section=cloud' ) ), __( 'Cloud Settings', 'code-snippets' ) );
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
     * Cloud DB Update Check
     * Checks if the cloud db has been updated and if so updates the local db
     *
     * @return void
     */
    public function check_db_updated_for_cloud(){
        global $wpdb;
        //Check if database changes are made
        $row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'wp_snippets' AND column_name = 'revision'"  );
        //Add revision and cloud_id columns if they don't exist
        if(empty($row)){
            $wpdb->query("ALTER TABLE wp_snippets ADD revision SMALLINT(6) NOT NULL DEFAULT(1), ADD cloud_id VARCHAR(255) NULL");
        }
    }

    /**
     * Check if cloud snippets are stored in upload folder
     * If not then get them from the cloud api and check for updates
     *
     * @return void
     */
    public function retrieve_cloud_snippets_and_check_updates($upload_dir){        
        //Check if file exists in upload folder
        $files = glob($upload_dir . '*'); // get all file names

        //If no files exist then create a new file with all cloud snippets
        if(empty($files)){
            $this->generate_cloud_snippets_json($upload_dir );
        }

        //If file exists then check if it is older than 1 day
        if(!empty($files) && is_file($files[0])){ 
            $now = time();
            $file_time = basename($files[0], ".json");
            $time_since_file_created = ($now - $file_time)/ 86400;
            //If file is older than 1 day then delete it and get cloud snippets from api and check for any updates
            if($time_since_file_created > self::DAYS_TO_STORE_CS){
                unlink($files[0]); // delete file
                $cloud_snippets = $this->get_cloud_snippets();
                $this->check_for_updates($cloud_snippets);
            }
        }
    }

    /**
     * Generate cloud snippets json file
     * 
     * @param string $upload_dir
     * @return void
     */
    public function generate_cloud_snippets_json($upload_dir, $snippet_data = null){
        if(is_null($snippet_data)){
            $cloud_snippets = $this->get_cloud_snippets();
        }else{
            $cloud_snippets = $snippet_data;
        }

        $filename_now_time = time();
        $all_snippet_file = $upload_dir . $filename_now_time .'.json';
        $all_snippets = json_encode($cloud_snippets);
        file_put_contents($all_snippet_file, $all_snippets);
    }

    /**
     * Get Path to Cloud Snippets folder in upload directory
     * 
     * @return string
     */
    public function get_cloud_snippets_path(){
        $upload_dir = wp_upload_dir();
        $upload_dir = $upload_dir['basedir'];
        $upload_dir = $upload_dir . '/code-snippets/cloud/';
        
        //Check if upload folder exists otherwise create it
        if(!file_exists($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }

        return $upload_dir;
    }

    /**
     * Get cloud snippets
     * Sends a request to the cloud api to get all snippets
     *
     * @return array|bool
     */
    public function get_cloud_snippets(){
        $url = self::CLOUD_API_URL . 'private/allsnippets';
        $args = array(
            'headers'     => array(
                'Authorization' => 'Bearer ' . $this->cloud_key,
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
     * Check for updates
     * Checks if any snippets have been downloaded and need updating
     *
     * @param string $cloud_snippets json data 
     * @return void
     */
    public function check_for_updates($cloud_snippets_from_api){
        $cloud_id_rev_array = []; //e.g. [163_1] => 2 
        $cloud_ids = [];
        $cloud_items_to_show_update = [];
        $cloud_items_to_show_download = [];
        $snip_objects = json_encode($cloud_snippets_from_api); //Trick step is needed to convert the array to an object
        $cloud_snippets = json_decode($snip_objects, false); 

        foreach ($cloud_snippets as $cloud_snippet) {
            //Get cloud revision and id and store in array
            $cloud_id_rev_array[$cloud_snippet->cloud_id] = $cloud_snippet->revision;
            $cloud_ids[] = $cloud_snippet->cloud_id;            
        }
        $local_snippets = get_snippets( array(), true, $cloud_ids );

        //If no local snippets that have been downloaded then set all cloud snippets to downloaded=>false
        if(empty($local_snippets)){
            foreach ($cloud_snippets as $key => $cloud_array) {
                $cloud_array->downloaded = 'false';
                $cloud_array->update_available = 'false';           
            }
        }

        //If there are local snippets that have been downloaded then check if they need updating
        if(count($local_snippets) > 0) {
            foreach ($local_snippets as $local_snippet) {
                //Check if local revision is less than cloud revision
                if( intval($local_snippet->revision) < intval($cloud_id_rev_array[$local_snippet->cloud_id]) ){
                    //add to cloud items to show update array
                    $cloud_items_to_show_update[] = $local_snippet->cloud_id;
                }else{
                    //add to cloud items to show download array
                    $cloud_items_to_show_download[] = $local_snippet->cloud_id;
                }
            }
            foreach ($cloud_snippets as $key => $cloud_array) {
                //If cloud id is in cloud items to show download array then set downloaded to true
                if( in_array($cloud_array->cloud_id, $cloud_items_to_show_download) ||  in_array($cloud_array->cloud_id, $cloud_items_to_show_update) ) {
                    $cloud_array->downloaded = 'true';
                    if( in_array($cloud_array->cloud_id, $cloud_items_to_show_update) ) {
                        $cloud_array->update_available = 'true';
                    }else{
                        $cloud_array->update_available = 'false';
                    }
                }else{
                    $cloud_array->downloaded = 'false';
                    $cloud_array->update_available = 'false';
                }       
            }
        }

        $this->generate_cloud_snippets_json($this->get_cloud_snippets_path(), $cloud_snippets);
        
    }


    /**
     * Render Cloud Snippet Thickbox Popup
     * Returns the html for the thickbox popup
     *
     * @return string
     */
    public function render_cloud_snippet_thickbox(){
        echo
            '<div id="show-code-preview" style="display:none;">
                <p id="snippet-name-thickbox"></p>
                <p>Snippet Code:</p>
                <pre class="thickbox-code-viewer"><code id="snippet-code-thickbox"></code></pre>
		    </div>';
    }



}