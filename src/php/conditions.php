<?php
/**
 * Utilities for handling snippet conditions.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Conditions;

use DateTimeImmutable;
use Exception;
use WP_Error;

/**
 * Parse DateTime value from a string without triggering an error.
 *
 * @param ?string $datetime String representation of DateTime value.
 *
 * @return DateTimeImmutable|null
 */
function safe_parse_datetime( ?string $datetime ): ?DateTimeImmutable {
	try {
		return $datetime ? new DateTimeImmutable( $datetime ) : null;
	} catch ( Exception $e ) {
		return null;
	}
}

/**
 * Determine if a given date is within a specified range.
 *
 * @param DateTimeImmutable $date     Date to check.
 * @param string|null       $operator Operator to use for comparison.
 * @param array             $objects  Array of dates to use for comparison.
 *
 * @return bool
 */
function is_within_date_range( DateTimeImmutable $date, ?string $operator, array $objects ): bool {
	switch ( $operator ) {
		case 'before':
			$end = isset( $objects[0] ) ? safe_parse_datetime( $objects[0] ) : null;
			return $end && $date < $end;

		case 'after':
			$start = isset( $objects[0] ) ? safe_parse_datetime( $objects[0] ) : null;
			return $start && $start > $date;

		case 'between':
			$start = isset( $objects[0] ) ? safe_parse_datetime( $objects[0] ) : null;
			$end = isset( $objects[1] ) ? safe_parse_datetime( $objects[1] ) : null;
			return $start && $end && $start <= $date && $date <= $end;
	}

	return false;
}

/**
 * Evaluate an individual clause of a condition.
 *
 * @param ?string                    $subject  Type of object that this condition represents.
 * @param ?string                    $operator Operator to use when evaluating the condition.
 * @param array<string | int | bool> $objects  Object that this condition is testing for.
 *
 * @return bool|WP_Error Result of evaluating condition.
 */
function evaluate_condition_clause( ?string $subject, ?string $operator, array $objects ) {
	switch ( $subject ) {
		/* Site conditions. */
		case 'siteArea':
			$object = $objects[0] ?? null;

			return 'global' === $object ||
			       ( 'admin' === $object && is_admin() ) ||
			       ( 'frontend' === $object && ! is_admin() );

		case 'currentQuery':
			return array_any(
				$objects,
				function ( $condition_object ) {
					switch ( $condition_object ) {
						case 'home':
							return is_home();

						case 'frontpage':
							return is_front_page();

						case 'search':
							return is_search();

						case 'archive':
							return is_archive();

						case '404':
							return is_404();

						case 'single':
							return is_single();

						case 'page':
							return is_page();

						case 'postTypeArchive':
							return is_post_type_archive();

						default:
							return new WP_Error( "Invalid currentQuery condition object: $condition_object." );
					}
				}
			);

		case 'visitorLanguage':
			$lang = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null ) );
			return $lang && in_array( $lang, $objects, true );

		case 'currentTheme':
			return in_array( get_stylesheet(), $objects, true ) || in_array( get_template(), $objects, true );

		case 'activePlugin':
			$active_plugins = (array) get_option( 'active_plugins', [] );
			$active_multisite_plugins = is_multisite() ?
				(array) get_site_option( 'active_sitewide_plugins', [] ) : [];

			return array_intersect( $active_plugins, $objects ) || array_intersect( $active_multisite_plugins, $objects );

		case 'debugEnabled':
			return defined( 'WP_DEBUG' ) && WP_DEBUG;

		/* Snippets conditions. */
		case 'condition':
			// TODO implement this in a non-recursive way.
			return false;

		/* Posts and pages conditions. */
		case 'post':
			return is_single( $objects );

		case 'page':
			return is_page( $objects );

		case 'postType':
			$post = get_post();
			return $post && in_array( $post->post_type, $objects, true );

		case 'tag':
		case 'category':
			return has_term( $objects, $subject );

		case 'author':
			$post = get_post();
			return $post && (int) $post->post_author && in_array( (int) $post->post_author, $objects, true );

		case 'postStatus':
			$post = get_post();
			return $post && in_array( $post->post_status, $objects, true );

		case 'postParent':
			$post = get_post();
			return $post && $post->post_parent && in_array( $post->post_parent, $objects, true );

		case 'postPublished':
			$datetime = get_post_datetime();
			return $datetime && is_within_date_range( $datetime, $operator, $objects );

		case 'postModified':
			$datetime = get_post_datetime( null, 'modified' );
			return $datetime && is_within_date_range( $datetime, $operator, $objects );

		/* User conditions. */
		case 'authenticated':
			return is_user_logged_in();

		case 'user':
			$user = wp_get_current_user();
			return $user && in_array( $user->ID, $objects, true );

		case 'userRole':
			$user = wp_get_current_user();
			return $user && ! empty( array_intersect( $user->roles, $objects ) );

		case 'userCap':
			return array_any( $objects, 'current_user_can' );

		case 'userLocale':
			$locale = get_user_locale();
			return $locale && in_array( $locale, $objects, true );

		case 'userRegistered':
			$user = wp_get_current_user();
			$datetime = $user ? safe_parse_datetime( $user->user_registered ) : null;
			return $datetime && is_within_date_range( $datetime, $operator, $objects );

		/* Date and time conditions. */
		case 'timeOfDay':
		case 'currentDate':
			return is_within_date_range( new DateTimeImmutable(), $operator, $objects );

		case 'dayOfWeek':
			return in_array( gmdate( 'D' ), $objects, true );

		/* Fallback. */
		default:
			return new WP_Error( "Invalid condition subject: $subject." );
	}
}

/**
 * Evaluate a single group of conditions using AND logic, ensuring each condition evaluates to true.
 *
 * @param array{ subject: ?string, operator: ?string, object?: array<string | int | bool> } $rule Condition rule.
 *
 * @return bool
 */
function evaluate_condition_rule( array $rule ): bool {
	$subject = $rule['subject'] ?? null;
	$operator = $rule['operator'] ?? null;
	$objects = $rule['object'] ?? [];

	if ( ! is_array( $objects ) ) {
		$objects = [ $objects ];
	}

	$result = evaluate_condition_clause( $subject, $operator, $objects );

	if ( is_wp_error( $result ) ) {
		return false;
	}

	if ( 'not' === $operator || 'not in' === $operator || 'false' === $operator ) {
		return ! $result;
	}

	return $result;
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

	if ( $groups ) {
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
	}

	return false;
}
