<?php
/**
 * Option access and sanitization.
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the effective options (stored values merged over defaults).
 *
 * @param bool $refresh Bypass the static cache.
 * @return array
 */
function boposts_get_options( $refresh = false ) {
	static $options = null;

	if ( null !== $options && ! $refresh ) {
		return $options;
	}

	$stored = get_option( 'boposts', array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	// Drop empty strings/nulls but keep legitimate zeros (e.g. auto_append = 0).
	$stored = array_filter(
		$stored,
		static function ( $value ) {
			return null !== $value && '' !== $value;
		}
	);

	$options = wp_parse_args( $stored, boposts_default_options() );

	if ( empty( $options['post_types'] ) || ! is_array( $options['post_types'] ) ) {
		$options['post_types'] = array( 'post' );
	}

	/**
	 * Filters the effective plugin options.
	 *
	 * @param array $options Options.
	 */
	$options = apply_filters( 'boposts_options', $options );

	return $options;
}

/**
 * Sanitizes a template string entered by an administrator.
 *
 * @param string $html Raw template.
 * @return string
 */
function boposts_sanitize_template( $html ) {
	$html = (string) $html;
	if ( current_user_can( 'unfiltered_html' ) ) {
		return $html;
	}
	return wp_kses_post( $html );
}

/**
 * Sanitize callback for the "boposts" option (Settings API).
 *
 * @param mixed $input Submitted values.
 * @return array
 */
function boposts_sanitize_options( $input ) {
	$defaults = boposts_default_options();
	$current  = get_option( 'boposts', array() );
	if ( ! is_array( $current ) ) {
		$current = array();
	}
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$clean = wp_parse_args( $current, $defaults );

	foreach ( array( 'header', 'body', 'footer' ) as $key ) {
		if ( array_key_exists( $key, $input ) ) {
			$clean[ $key ] = boposts_sanitize_template( $input[ $key ] );
		}
	}

	if ( array_key_exists( 'max', $input ) ) {
		$max          = absint( $input['max'] );
		$clean['max'] = ( $max >= 1 && $max <= 20 ) ? $max : $defaults['max'];
	}

	if ( array_key_exists( 'excerpt', $input ) ) {
		$clean['excerpt'] = min( 1000, absint( $input['excerpt'] ) );
	}

	// Checkboxes: a missing key means unchecked. The form always submits a
	// hidden marker so we can tell "form submitted" from "key absent".
	if ( ! empty( $input['_form'] ) ) {
		$clean['auto_append']    = empty( $input['auto_append'] ) ? 0 : 1;
		$clean['featured_image'] = empty( $input['featured_image'] ) ? 0 : 1;

		$public_types = get_post_types( array( 'public' => true ) );
		$types        = isset( $input['post_types'] ) ? (array) $input['post_types'] : array();
		$types        = array_values( array_intersect( array_map( 'sanitize_key', $types ), $public_types ) );
		if ( empty( $types ) ) {
			$types = array( 'post' );
		}
		$clean['post_types'] = $types;
	}

	// cache_ttl is not exposed in the UI; keep whatever is stored/filtered.
	$clean['cache_ttl'] = isset( $current['cache_ttl'] ) ? absint( $current['cache_ttl'] ) : $defaults['cache_ttl'];

	boposts_bump_cache_generation();

	return $clean;
}
