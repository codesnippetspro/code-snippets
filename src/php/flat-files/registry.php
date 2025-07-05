<?php

namespace Code_Snippets;

class Snippet_Handler_Registry {
	/**
	 * @var Snippet_Type_Handler_Interface[]
	 */
	private array $handlers = [];

	/**
	 * Constructor
	 *
	 * @param Snippet_Type_Handler_Interface[] $handlers
	 */
	public function __construct( array $handlers ) {
		foreach ( $handlers as $type => $handler ) {
			$this->register_handler( $type, $handler );
		}
	}

	/**
	 * Registers a handler for a snippet type.
	 *
	 * @param string $type
	 * @param Snippet_Type_Handler_Interface $handler
	 * @return void
	 */
	public function register_handler( string $type, Snippet_Type_Handler_Interface $handler ): void {
		$this->handlers[ $type ] = $handler;
	}

	/**
	 * Gets the handler for a snippet type.
	 *
	 * @param string $type
	 *
	 * @return Snippet_Type_Handler_Interface|null
	 */
	public function get_handler( string $type ): ?Snippet_Type_Handler_Interface {
		if ( ! isset( $this->handlers[ $type ] ) ) {
			return null;
		}

		return $this->handlers[ $type ];
	}
}
