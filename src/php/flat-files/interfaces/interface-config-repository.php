<?php

namespace Code_Snippets;

interface Snippet_Config_Repository_Interface {
	public function load( string $base_dir ): array;
	public function save( string $base_dir, array $active_snippets ): void;
	public function update( string $base_dir, Snippet $snippet, ?bool $remove = false ): void;
}
