<?php
/**
 * Utilities for handling conditional logic.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets;

use WP_Error;

/**
 * Evaluate an individual clause of a conditional.
 *
 * @param string $item_type Type of object that this condition represents.
 * @param string $item      Object that this condition is testing for.
 *
 * @return bool|WP_Error Result of evaluating condition.
 */
function evaluate_conditional_clause( string $item_type, string $item ) {
	switch ( $item_type ) {
		case 'post':
		case 'page':
			$post = get_post();
			return $post && intval( $item ) === $post->ID && $post->post_type === $item_type;

		case 'postType':
			$post = get_post();
			return $post && $post->post_type === $item;

		case 'tag':
		case 'category':
			$terms = get_the_terms( false, $item_type );
			return $terms && ! is_wp_error( $terms ) &&
			       in_array( intval( $item ), wp_list_pluck( $terms, 'term_id' ), true );

		case 'user':
			$user = wp_get_current_user();
			return $user && intval( $item ) === $user->ID;

		case 'authenticated':
			return is_user_logged_in();

		case 'userRole':
			$user = wp_get_current_user();
			return $user && in_array( $item, $user->roles, true );

		default:
			return new WP_Error( "Invalid conditional subject: $item_type." );
	}
}

/**
 * Evaluate a single group of conditions using AND logic, ensuring each condition evaluates to true.
 *
 * @param array $conditions Group of conditions.
 *
 * @return bool
 */
function evaluate_conditional_group( array $conditions ): bool {
	foreach ( $conditions as $condition ) {
		$is_true = evaluate_conditional_clause( $condition->subject, $condition->object );
		$result = 'neq' === $condition->operator ? ! $is_true : $is_true;

		if ( ! $result || is_wp_error( $result ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Determine the result of evaluating a given conditional for the current page.
 *
 * @param string $conditional Conditional code, in JSON string format.
 *
 * @return boolean Result of evaluating the conditional.
 */
function evaluate_conditional( string $conditional ): bool {
	$or_groups = json_decode( $conditional, false );

	foreach ( $or_groups as $and_group ) {
		if ( evaluate_conditional_group( get_object_vars( $and_group ) ) ) {
			return true;
		}
	}

	return false;
}
