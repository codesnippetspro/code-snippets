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

require_once __DIR__ . '/php/Core/Uninstaller.php';

$uninstaller = new Uninstaller();
$uninstaller->uninstall_plugin();
