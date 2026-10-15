<?php
/**
 * Upgrade routines.
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs pending upgrades. Cheap when nothing is pending (one autoloaded option).
 */
function boposts_maybe_upgrade() {
	$installed = (int) get_option( 'boposts_db_version', 0 );
	if ( $installed >= BOPOSTS_DB_VERSION ) {
		return;
	}

	if ( $installed < 2 ) {
		boposts_drop_legacy_fulltext_index();
	}

	update_option( 'boposts_db_version', BOPOSTS_DB_VERSION );
	boposts_bump_cache_generation();
}

/**
 * Removes the FULLTEXT index that 1.x added to the posts table.
 */
function boposts_drop_legacy_fulltext_index() {
	global $wpdb;

	// Only MySQL/MariaDB have this index; other database layers (e.g. SQLite) do not.
	if ( ! $wpdb instanceof wpdb || ! method_exists( $wpdb, 'db_version' ) ) {
		return;
	}
	if ( defined( 'DB_ENGINE' ) && 'mysql' !== DB_ENGINE ) {
		return;
	}

	$suppress = $wpdb->suppress_errors( true );
	$index    = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM {$wpdb->posts} WHERE Key_name = %s", 'boposts_index' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( $index ) {
		$wpdb->query( "DROP INDEX boposts_index ON {$wpdb->posts}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	}
	$wpdb->suppress_errors( $suppress );
}
