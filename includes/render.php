<?php
/**
 * HTML rendering of the related posts list.
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks which posts already had a list rendered in this request.
 *
 * @param int|null $post_id Post id to mark; null to only read.
 * @return bool Whether the post was already rendered before this call.
 */
function boposts_rendered_registry( $post_id = null ) {
	static $done = array();
	if ( null === $post_id ) {
		return false;
	}
	$already          = isset( $done[ $post_id ] );
	$done[ $post_id ] = true;
	return $already;
}

/**
 * Thumbnail URL for a related post.
 *
 * @param WP_Post $post    Related post.
 * @param array   $options Plugin options.
 * @return string
 */
function boposts_get_image_url( $post, array $options ) {
	$image = '';

	if ( ! empty( $options['featured_image'] ) && has_post_thumbnail( $post ) ) {
		/**
		 * Filters the image size used for featured-image thumbnails.
		 *
		 * @param string $size Image size name.
		 */
		$size  = apply_filters( 'boposts_image_size', 'medium' );
		$image = (string) get_the_post_thumbnail_url( $post, $size );
	}

	if ( '' === $image && preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $m ) ) {
		$image = $m[1];
	}

	if ( '' === $image ) {
		$image = plugins_url( 'empty.gif', BOPOSTS_FILE );
	}

	/**
	 * Filters the thumbnail URL for a related post.
	 *
	 * @param string  $image Image URL.
	 * @param WP_Post $post  Related post.
	 */
	return apply_filters( 'boposts_image_url', $image, $post );
}

/**
 * Excerpt text for a related post.
 *
 * @param WP_Post $post   Related post.
 * @param int     $length Maximum characters.
 * @return string
 */
function boposts_get_excerpt( $post, $length ) {
	$text = '' !== trim( (string) $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
	$text = strip_shortcodes( $text );
	$text = excerpt_remove_blocks( $text );
	$text = wp_strip_all_tags( $text );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	/**
	 * Filters the string appended to a trimmed excerpt.
	 *
	 * @param string $more Suffix.
	 */
	$more = apply_filters( 'boposts_excerpt_more', '…' );

	return boposts_trim_text( $text, (int) $length, $more );
}

/**
 * Builds the related posts HTML.
 *
 * @param array $args {
 *     Optional arguments.
 *
 *     @type int    $post_id Post to find relations for (default current post).
 *     @type int    $max     Number of posts (default option value).
 *     @type string $header  Template override.
 *     @type string $body    Template override.
 *     @type string $footer  Template override.
 * }
 * @return string HTML, or empty string when there is nothing to show.
 */
function boposts_render( array $args = array() ) {
	$options = boposts_get_options();
	$args    = wp_parse_args(
		$args,
		array(
			'post_id' => 0,
			'max'     => 0,
			'header'  => $options['header'],
			'body'    => $options['body'],
			'footer'  => $options['footer'],
		)
	);

	$post = get_post( $args['post_id'] ? (int) $args['post_id'] : get_the_ID() );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$ids = boposts_find_related_ids( $post->ID, (int) $args['max'] );
	if ( empty( $ids ) ) {
		/**
		 * Filters the HTML shown when no related posts exist.
		 *
		 * @param string  $html Default empty string.
		 * @param WP_Post $post Current post.
		 */
		return apply_filters( 'boposts_no_results_html', '', $post );
	}

	_prime_post_caches( $ids, false, false );

	$html = (string) $args['header'];
	foreach ( $ids as $id ) {
		$related = get_post( $id );
		if ( ! $related instanceof WP_Post ) {
			continue;
		}

		$placeholders = array(
			'{link}'    => esc_url( get_permalink( $related ) ),
			'{title}'   => esc_html( get_the_title( $related ) ),
			'{image}'   => esc_url( boposts_get_image_url( $related, $options ) ),
			'{excerpt}' => esc_html( boposts_get_excerpt( $related, (int) $options['excerpt'] ) ),
		);

		/**
		 * Filters the placeholder replacements for one related post.
		 *
		 * @param array   $placeholders '{token}' => escaped value.
		 * @param WP_Post $related      Related post.
		 * @param WP_Post $post         Current post.
		 */
		$placeholders = apply_filters( 'boposts_placeholders', $placeholders, $related, $post );

		$html .= str_replace( array_keys( $placeholders ), array_values( $placeholders ), (string) $args['body'] );
	}
	$html .= (string) $args['footer'];

	/**
	 * Filters the final related posts HTML.
	 *
	 * @param string  $html HTML.
	 * @param WP_Post $post Current post.
	 * @param int[]   $ids  Related post ids.
	 */
	return (string) apply_filters( 'boposts_output', $html, $post, $ids );
}

/**
 * Outputs the related posts list (back-compat theme API).
 *
 * @param array $args See boposts_render().
 */
function boposts_show( array $args = array() ) {
	$html = boposts_render( $args );
	if ( '' !== $html ) {
		$post_id = ! empty( $args['post_id'] ) ? (int) $args['post_id'] : (int) get_the_ID();
		boposts_rendered_registry( $post_id );
	}
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-defined template, values escaped above.
}
