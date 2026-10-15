<?php
/**
 * Uninstall: remove options and cached results.
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'boposts' );
delete_option( 'boposts_db_version' );
delete_option( 'boposts_cache_gen' );

global $wpdb;
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->esc_like( '_transient_boposts_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_boposts_' ) . '%'
	)
);
