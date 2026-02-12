<?php

namespace Code_Snippets\Flat_Files;

use Code_Snippets\Flat_Files\Interfaces\Snippet_Type_Handler;

/**
 * Class for storing registered handlers for converting the different snippet types into flat files.
 *
 * @package Code_Snippets
 */
class Handler_Registry {

	/**
	 * List of type handlers.
	 *
	 * @var Snippet_Type_Handler[]
	 */
	private array $handlers = [];

	/**
	 * Constructor.
	 *
	 * @param Snippet_Type_Handler[] $handlers Snippet type handlers to register.
	 */
	public function __construct( array $handlers ) {
		foreach ( $handlers as $type => $handler ) {
			$this->register_handler( $type, $handler );
		}
	}

	/**
	 * Registers a handler for a snippet type.
	 *
	 * @param string               $type    Handler key.
	 * @param Snippet_Type_Handler $handler Handler class.
	 *
	 * @return void
	 */
	public function register_handler( string $type, Snippet_Type_Handler $handler ): void {
		$this->handlers[ $type ] = $handler;
	}

	/**
	 * Gets the handler for a snippet type.
	 *
	 * @param string $type Handler key.
	 *
	 * @return Snippet_Type_Handler|null
	 */
	public function get_handler( string $type ): ?Snippet_Type_Handler {
		return $this->handlers[ $type ] ?? null;
	}
}
