<?php

/**
 * Global functions which interact with the code snippets plugin
 *
 * @package    Code_Snippets
 * @subpackage Functions
 */

/**
 * Add submenu page to the snippets main menu.
 *
 * @param  string      $page_title The text to be displayed in the title tags of the page when the menu is selected
 * @param  string      $menu_title The text to be used for the menu
 * @param  string      $capability The capability required for this menu to be displayed to the user.
 * @param  string      $menu_slug  The slug name to refer to this menu by (should be unique for this menu)
 * @param  callback    $function   The function to be called to output the content for this page.
 * @return string|bool             The resulting page's hook_suffix, or false if the user does not have the capability required.
 */
function add_snippets_page( $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
	global $code_snippets;
	return add_submenu_page( $code_snippets->admin->manage_page, $page_title, $menu_title, $capability, $menu_slug, $function );
}
