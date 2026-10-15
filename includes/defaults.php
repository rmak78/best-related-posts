<?php
/**
 * Default plugin options.
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the default options. Template keys are unchanged from 1.x so
 * existing installations keep their look.
 *
 * @return array
 */
function boposts_default_options() {
	$defaults = array(
		'header'         => '<div class="boposts"><h3 class="boposts-title">You might also like</h3><div class="boposts-grid">',
		'body'           => '<a class="boposts-item" href="{link}">' . "\n"
			. '<img class="boposts-thumb" src="{image}" alt="" loading="lazy" />' . "\n"
			. '<span class="boposts-item-title">{title}</span>' . "\n"
			. '<span class="boposts-excerpt">{excerpt}</span>' . "\n"
			. '</a>',
		'footer'         => '</div></div>',
		'max'            => 4,
		'excerpt'        => 100,
		'auto_append'    => 0,
		'post_types'     => array( 'post' ),
		'featured_image' => 1,
		'cache_ttl'      => 12 * HOUR_IN_SECONDS,
	);

	/**
	 * Filters the default options.
	 *
	 * @param array $defaults Default options.
	 */
	return apply_filters( 'boposts_default_options', $defaults );
}
