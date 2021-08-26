<?php

namespace Code_Snippets;

/**
 * Functions specific to the Site Health page.
 *
 * @package Code_Snippets
 */
class Site_Health {

	/**
	 * Add hooks needed for functionality.
	 */
	public function hook() {
		add_filter( 'debug_information', array( $this, 'debug_information' ) );
	}

	/**
	 * Remove hooks needed for functionality.
	 */
	public function unhook() {
		remove_filter( 'debug_information', array( $this, 'debug_information' ) );
	}

	/**
	 * Add Code Snippets information to Site Health information.
	 *
	 * @param array $info The Site Health information.
	 *
	 * @return array The updated Site Health information.
	 */
	public function debug_information( $info ) {
		$info['code-snippets'] = array(
			'label'       => 'Code Snippets',
			// @todo Plugin Author to replace this description.
			'description' => 'You can put your description here',
			'fields'      => array(
				'code-snippets-active' => array(
					'label' => 'Active Code Snippets',
					// Split by | because line breaks don't get shown in the Dashboard UI (but they show on debug copy).
					'value' => implode( " | \n", $this->get_active_code_snippets() ),
				),
			),
		);

		return $info;
	}
  
	/**
	 * Get the list of active code snippets.
	 *
	 * @return array List of active code snippets.
	 */
	public function get_active_code_snippets() {
		$active_code_snippets = array();

		$args = array(
			'active_only' => true,
			'limit'       => 100,
		);

		$snippets = get_snippets( array(), null, $args );

		foreach ( $snippets as $snippet ) {
			$active_code_snippets[] = $snippet->name . ' (' . trim( $snippet->scope . ': ' . $snippet->tags_list, ': ' ) . ')';
		}

		return $active_code_snippets;
	}
}
