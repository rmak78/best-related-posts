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
		'header'         => '<h3>You might also like</h3><table><tr>',
		'body'           => '<td valign="top" width="130" style="padding:5px">' . "\n"
			. '<table><tr><td style="word-break:break-all"><a href="{link}"><img src="{image}" width="110" alt="{title}"/></a></td></tr>' . "\n"
			. '<tr><td width="110"><p><strong><a href="{link}">{title}</a></strong><br />{excerpt}</p></td></tr>' . "\n"
			. '</table></td>',
		'footer'         => '</tr></table>',
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
