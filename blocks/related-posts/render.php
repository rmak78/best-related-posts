<?php
/**
 * Server-side render for the Best Related Posts block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$boposts_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
$boposts_max     = isset( $attributes['max'] ) ? (int) $attributes['max'] : 0;

if ( ! $boposts_post_id ) {
	return;
}

$boposts_html = boposts_render(
	array(
		'post_id' => $boposts_post_id,
		'max'     => $boposts_max,
	)
);

$boposts_is_preview = defined( 'REST_REQUEST' ) && REST_REQUEST;

if ( '' === $boposts_html ) {
	if ( $boposts_is_preview ) {
		$boposts_html = '<p><em>' . esc_html__( 'No related posts yet. Related posts appear here once there are other published posts.', 'best-related-posts' ) . '</em></p>';
	} else {
		return;
	}
} elseif ( $boposts_is_preview && 'publish' !== get_post_status( $boposts_post_id ) ) {
	$boposts_html = '<p><em>' . esc_html__( 'Preview: related posts are recalculated once this post is published.', 'best-related-posts' ) . '</em></p>' . $boposts_html;
}

if ( ! $boposts_is_preview ) {
	boposts_rendered_registry( $boposts_post_id );
}

printf(
	'<div %1$s>%2$s</div>',
	get_block_wrapper_attributes( array( 'class' => 'boposts-block' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$boposts_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin template with escaped values.
);
