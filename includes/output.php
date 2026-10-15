<?php
/**
 * Shortcode, the_content auto-append and block registration.
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [best_related_posts post_id="" max=""] shortcode.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function boposts_shortcode( $atts ) {
	static $rendering = false;
	if ( $rendering ) {
		return '';
	}
	$rendering = true;

	$atts = shortcode_atts(
		array(
			'post_id' => 0,
			'max'     => 0,
		),
		$atts,
		'best_related_posts'
	);

	$html = boposts_render(
		array(
			'post_id' => absint( $atts['post_id'] ),
			'max'     => absint( $atts['max'] ),
		)
	);
	if ( '' !== $html ) {
		boposts_rendered_registry( absint( $atts['post_id'] ) ? absint( $atts['post_id'] ) : (int) get_the_ID() );
	}

	$rendering = false;
	return $html;
}

/**
 * Registers the shortcode and the block.
 */
function boposts_register_output() {
	add_shortcode( 'best_related_posts', 'boposts_shortcode' );

	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	// No build step, so declare the editor script's dependencies by hand.
	wp_register_script(
		'boposts-editor',
		BOPOSTS_URL . 'blocks/related-posts/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
		BOPOSTS_VERSION,
		true
	);
	wp_set_script_translations( 'boposts-editor', 'best-related-posts' );

	register_block_type( BOPOSTS_DIR . 'blocks/related-posts' );
}
add_action( 'init', 'boposts_register_output' );

/**
 * Appends the related posts list to single post content when enabled.
 *
 * @param string $content Post content.
 * @return string
 */
function boposts_auto_append( $content ) {
	$options = boposts_get_options();
	if ( empty( $options['auto_append'] ) ) {
		return $content;
	}
	if ( is_admin() || is_feed() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return $content;
	}
	if ( ! is_singular( (array) $options['post_types'] ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( doing_filter( 'get_the_excerpt' ) || post_password_required() ) {
		return $content;
	}

	$post_id = (int) get_the_ID();
	if ( ! $post_id || get_queried_object_id() !== $post_id ) {
		return $content;
	}

	// Skip if a shortcode/block/theme call already rendered the list for this post.
	if ( boposts_rendered_registry( $post_id ) ) {
		return $content;
	}

	$html = boposts_render( array( 'post_id' => $post_id ) );
	if ( '' === $html ) {
		return $content;
	}

	return $content . "\n" . $html;
}
add_filter( 'the_content', 'boposts_auto_append', 20 );
