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
 * CLOUD API URL
 * 
 * @var string
 */
const CLOUD__API_URL = 'https://codesnippets.cloud/api/v1/private/';

/**
 * CLOUD URL
 * 
 * @var string
 */
const CLOUD_URL = 'https://codesnippets.cloud/';


/**
 * CLOUD API KEY
 * 
 * @var string
 */
private $Cloud_Key = '';

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
    $this->Cloud_Key  = get_setting( 'cloud' , 'cloud_token');
    add_action( 'init', array( $this, 'init' ) );
    
}

/**
 * Check if Cloud Key is set and verified
 *
 * @return bool
 */
public function is_cloud_key_set(){    
    if($this->Cloud_Key == ''){
        return false;
    }
    //Add check to see if key is verified

    return true;
}


/**
 * Initialise class functions.
 * This is called on the init hook and is used to get all cloud snippets if a cloud api key is set
 * 
 * @return void
 */
public function init() {
    //Get user cloud public and private snippets
    if($this->is_cloud_key_set()){
        $cloudSnippets = $this->get_cloud_snippets();
        $this->cloud_list_table = new Cloud_List_Table($cloudSnippets);
        $this->cloud_list_table->prepare_items(); 
        $this->cloud_list_table->display(); 
    }else{
        $cloud_token_error = sprintf( '<p>There seems to be a problem with the Cloud API Token, please check this is correct by going to the <a href="%s">%s</a></p>', esc_url( admin_url( 'admin.php?page=snippets-settings&section=cloud' ) ), __( 'Cloud Settings', 'code-snippets' ) );
        
        return print $cloud_token_error;
    }

}

/**
 * Get cloud snippets
 * Sends a request to the cloud api to get all snippets
 *
 * @return array|bool
 */
public function get_cloud_snippets(){
    $url = self::CLOUD__API_URL . 'allsnippets';
    $args = array(
        'headers'     => array(
            'Authorization' => 'Bearer ' . $this->Cloud_Key,
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





}