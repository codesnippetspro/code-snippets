<?php

namespace Code_Snippets;

interface File_System_Interface {
	public function put_contents( string $path, string $contents, $chmod );
	public function exists( string $path ): bool;
	public function delete( string $path ): bool;
	public function is_dir( string $path ): bool;
	public function mkdir( string $path, $chmod );
	public function rmdir( string $path, bool $recursive = false ): bool;
}
