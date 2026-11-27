<?php
/**
 * Cleans up data created by this plugin
 *
 * @package Code_Snippets
 * @since   2.0.0
 */

namespace Code_Snippets;

use Code_Snippets\Core\Uninstaller;

// Ensure this plugin is actually being uninstalled.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ( defined( 'CODE_SNIPPETS_PRO' ) && CODE_SNIPPETS_PRO ) ) {
	return;
}

// WordPress runs this file without loading the plugin, so load what the uninstaller needs.
if ( ! defined( 'CODE_SNIPPETS_FILE' ) ) {
	$code_snippets_plugin_data = get_file_data( __DIR__ . '/code-snippets.php', [ 'Version' => 'Version' ] );

	define( 'CODE_SNIPPETS_VERSION', $code_snippets_plugin_data['Version'] );
	define( 'CODE_SNIPPETS_FILE', __DIR__ . '/code-snippets.php' );
	define( 'CODE_SNIPPETS_PRO', false );

	require_once __DIR__ . '/php/Core/load.php';
}

require_once __DIR__ . '/php/Core/Uninstaller.php';

$code_snippets_uninstaller = new Uninstaller();
$code_snippets_uninstaller->uninstall_plugin();
