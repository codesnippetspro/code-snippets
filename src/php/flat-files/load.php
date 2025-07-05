<?php

$files = [
	'registry.php',
	'interfaces/interface-config-repository.php',
	'interfaces/interface-file-system.php',
	'interfaces/interface-snippet-handler.php',
	'handlers/php-snippet-handler.php',
	'handlers/html-snippet-handler.php',
	'classes/class-file-system-adapter.php',
	'classes/class-snippet-files.php',
	'classes/class-config-repository.php',
];

foreach ( $files as $file ) {
	require_once $file;
}
