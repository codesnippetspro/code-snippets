<?php

/**
 * Code Snippets - An easy, clean and simple way to add code snippets to your site.
 *
 * If you're interested in helping to develop Code Snippets, or perhaps
 * contribute to the localization, please see http://code-snippets.bungeshea.com
 *
 * @package   Code_Snippets
 * @version   2.0-dev
 * @author    Shea Bunge <http://bungeshea.com/>
 * @copyright Copyright (c) 2012-2014, Shea Bunge
 * @link      http://code-snippets.bungeshea.com
 * @license   http://opensource.org/licenses/MIT
 */

/*
Plugin Name: Code Snippets
Plugin URI:  http://code-snippets.bungeshea.com
Description: An easy, clean and simple way to add code snippets to your site. No need to edit to your theme's functions.php file again!
Author:      Shea Bunge
Author URI:  http://bungeshea.com
Version:     2.0-dev
License:     MIT
License URI: license.txt
Text Domain: code-snippets
Domain Path: /languages/
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A skeleton class containing helpers used throughout the plugin.
 * The real meaty functions are in their own files
 *
 * @since 2.0 Pulled virtually all methods out of class
 * @since 1.0
 */
class Code_Snippets {

	/**
	 * The version number for this release of the plugin.
	 * This will later be used for upgrades and enqueueing files
	 *
	 * This should be set to the 'Plugin Version' value,
	 * as defined above in the plugin header
	 *
	 * @since 1.0
	 * @access public
	 * @var string A PHP-standardized version number string
	 */
	public $version = '2.0-dev';

	/**
	 * Variables to hold plugin paths
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	public $file, $plugin_dir, $plugin_url = '';

	/**
	 * Initialize this plugin
	 */
	public function __construct() {

		/* Initialize member variables */
		$this->file = __FILE__;
		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

		/* Include files */
		$this->includes();

		/* Run the upgrader */
		code_snippets_upgrade( $this->version );

		/* Load plugin textdomain */
		$this->load_textdomain();

		/* Call the done action */
		do_action( 'code_snippets_init' );
	}

	/**
	 * Include plugin files
	 */
	public function includes() {
		$version = $this->version;

		/* Database operations functions */
		require_once $this->plugin_dir . 'includes/db.php';

		/* Upgrade function */
		require_once $this->plugin_dir . 'includes/upgrade.php';

		/* Capability functions */
		require_once $this->plugin_dir . 'includes/caps.php';

		/* Snippet operations functions */
		require_once $this->plugin_dir . 'includes/snippet-ops.php';

		/* Administration functions */
		if ( is_admin() ) {
			require_once $this->plugin_dir . 'includes/admin.php';
		}
	}

	/**
	 * Load up the localization file if we're using WordPress in a different language.
	 * Place it in this plugin's "languages" folder and name it "code-snippets-[value in wp-config].mo"
	 *
	 * If you wish to contribute a language file to be included in the Code Snippets package,
	 * please see create an issue on GitHub: https://github.com/bungeshea/code-snippets/issues
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'code-snippets', false, dirname( basename( __FILE__ ) ) . '/languages/' );
	}
}

/**
 * Initialize the Code_Snippets class
 * @since 2.0
 */
function code_snippets_init() {
	global $code_snippets;
	$code_snippets = new Code_Snippets;
	do_action( 'code_snippets_init' );
}

add_action( 'plugins_loaded', 'code_snippets_init' );
