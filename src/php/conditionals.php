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
 * @param ?string $item_type Type of object that this condition represents.
 * @param ?string $item      Object that this condition is testing for.
 *
 * @return bool|WP_Error Result of evaluating condition.
 */
function evaluate_conditional_clause( ?string $item_type, ?string $item ) {
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

		case 'userCap':
			return current_user_can( $item );

		default:
			return new WP_Error( "Invalid conditional subject: $item_type." );
	}
}

/**
 * Evaluate a single group of conditions using AND logic, ensuring each condition evaluates to true.
 *
 * @param array $rule Condition rule.
 *
 * @return bool
 */
function evaluate_conditional_rule( array $rule ): bool {
	$enabled = $rule['enabled'] ?? null;
	$subject = $rule['subject'] ?? null;
	$object = $rule['object'] ?? null;
	$operator = $rule['operator'] ?? null;

	$is_true = evaluate_conditional_clause( $subject, $object );
	$result = 'not' === $operator ? ! $is_true : $is_true;

	if ( $enabled === false ) {
		$result = ! $result;
	}

	if ( ! $result || is_wp_error( $result ) ) {
		return false;
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
	$rules = json_decode( $conditional, false );

	foreach ( $rules as $rule ) {
		if ( evaluate_conditional_rule( get_object_vars( $rule ) ) ) {
			return true;
		}
	}

	return false;
}
