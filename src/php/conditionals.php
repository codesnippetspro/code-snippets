<?php
/**
 * Utilities for handling snippet conditions.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets;

use WP_Error;

/**
 * Evaluate an individual clause of a condition.
 *
 * @param ?string                    $subject Type of object that this condition represents.
 * @param array<string | int | bool> $objects Object that this condition is testing for.
 *
 * @return bool|WP_Error Result of evaluating condition.
 */
function evaluate_condition_clause( ?string $subject, array $objects ) {
	switch ( $subject ) {
		case 'post':
		case 'page':
			return is_single( $objects );

		case 'postType':
			$post = get_post();
			return $post && in_array( $post->post_type, $objects, true );

		case 'tag':
		case 'category':
			return has_term( $objects, $subject );

		case 'user':
			$user = wp_get_current_user();
			return $user && in_array( $user->ID, $objects, true );

		case 'authenticated':
			return is_user_logged_in();

		case 'userRole':
			$user = wp_get_current_user();
			return $user && ! empty( array_intersect( $user->roles, $objects ) );

		case 'userCap':
			foreach ( $objects as $cap ) {
				if ( current_user_can( $cap ) ) {
					return true;
				}
			}

			return false;

		default:
			return new WP_Error( "Invalid condition subject: $subject." );
	}
}

/**
 * Evaluate a single group of conditions using AND logic, ensuring each condition evaluates to true.
 *
 * @param array $rule Condition rule.
 *
 * @return bool
 */
function evaluate_condition_rule( array $rule ): bool {
	$subject = $rule['subject'] ?? null;
	$operator = $rule['operator'] ?? null;
	$objects = isset( $rule['object'] ) && is_array( $rule['object'] ) ? $rule['object'] : [];

	$result = evaluate_condition_clause( $subject, $objects );

	if ( 'not' === $operator ) {
		$result = ! $result;
	}

	return $result && ! is_wp_error( $result );
}

/**
 * Determine the result of evaluating a given condition for the current page.
 *
 * @param string $condition_json Conditional code, in JSON string format.
 *
 * @return boolean Result of evaluating the condition.
 */
function evaluate_condition( string $condition_json ): bool {
	$groups = json_decode( $condition_json, false );

	foreach ( $groups as $group ) {
		$is_true = true;

		foreach ( $group as $rule ) {
			if ( ! evaluate_condition_rule( get_object_vars( $rule ) ) ) {
				$is_true = false;
				break;
			}
		}

		if ( $is_true ) {
			return true;
		}
	}

	return false;
}
