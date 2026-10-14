<?php
/**
 * Removes plugin data on uninstall, only if "Delete all data" was switched on.
 *
 * @package LikeDislikeButton
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Cleans up one site.
 */
function ldb_uninstall_site() {
	global $wpdb;

	$settings = get_option( 'ldb_settings' );
	if ( empty( $settings['delete_data'] ) ) {
		return;
	}

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ldb_votes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange

	foreach ( array( '_ldb_likes', '_ldb_dislikes', '_ldb_score', '_ldb_imported', '_ldb_hide' ) as $key ) {
		delete_metadata( 'post', 0, $key, '', true );
		delete_metadata( 'comment', 0, $key, '', true );
	}

	delete_option( 'ldb_settings' );
	delete_option( 'ldb_db_version' );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_ldb\_%' OR option_name LIKE '\_transient\_timeout\_ldb\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $ldb_site ) {
		switch_to_blog( $ldb_site );
		ldb_uninstall_site();
		restore_current_blog();
	}
} else {
	ldb_uninstall_site();
}
