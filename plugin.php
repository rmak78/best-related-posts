<?php
/**
 * Plugin Name: Best Related Posts
 * Plugin URI:  https://github.com/rmak78/best-related-posts
 * Description: Shows truly related posts (by shared tags, categories and keywords) with thumbnails. Design your own layout, use the shortcode, the block, or auto-append to posts.
 * Version:     2.0.0
 * Author:      Rana Mansoor Akbar Khan
 * Author URI:  http://www.sutlej.net/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: best-related-posts
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

/*  Copyright 2011-2026  Sutlej.NET  (email : rmak@sutlej.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Back-compat constant some themes check before calling boposts_show().
if ( ! defined( 'BO_POSTS' ) ) {
	define( 'BO_POSTS', true );
}

define( 'BOPOSTS_VERSION', '2.0.0' );
define( 'BOPOSTS_DB_VERSION', 2 );
define( 'BOPOSTS_FILE', __FILE__ );
define( 'BOPOSTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'BOPOSTS_URL', plugin_dir_url( __FILE__ ) );

require_once BOPOSTS_DIR . 'includes/defaults.php';
require_once BOPOSTS_DIR . 'includes/options.php';
require_once BOPOSTS_DIR . 'includes/keywords.php';
require_once BOPOSTS_DIR . 'includes/scoring.php';
require_once BOPOSTS_DIR . 'includes/related.php';
require_once BOPOSTS_DIR . 'includes/render.php';
require_once BOPOSTS_DIR . 'includes/output.php';
require_once BOPOSTS_DIR . 'includes/upgrade.php';
if ( is_admin() ) {
	require_once BOPOSTS_DIR . 'includes/admin.php';
}

/**
 * Plugin bootstrap on plugins_loaded.
 */
function boposts_init() {
	load_plugin_textdomain( 'best-related-posts', false, dirname( plugin_basename( BOPOSTS_FILE ) ) . '/languages' );
	boposts_maybe_upgrade();
}
add_action( 'plugins_loaded', 'boposts_init' );

/**
 * Activation: seed defaults without overwriting existing settings and run upgrades.
 */
function boposts_activate() {
	add_option( 'boposts', boposts_default_options() );
	boposts_maybe_upgrade();
}
register_activation_hook( BOPOSTS_FILE, 'boposts_activate' );
