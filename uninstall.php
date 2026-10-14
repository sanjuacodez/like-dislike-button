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
function ldbtn_uninstall_site() {
	global $wpdb;

	$settings = get_option( 'ldbtn_settings' );
	if ( empty( $settings['delete_data'] ) ) {
		return;
	}

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ldbtn_votes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange

	foreach ( array( '_ldbtn_likes', '_ldbtn_dislikes', '_ldbtn_score', '_ldbtn_imported', '_ldbtn_hide' ) as $key ) {
		delete_metadata( 'post', 0, $key, '', true );
		delete_metadata( 'comment', 0, $key, '', true );
	}

	delete_option( 'ldbtn_settings' );
	delete_option( 'ldbtn_db_version' );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_ldbtn\_%' OR option_name LIKE '\_transient\_timeout\_ldbtn\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $ldbtn_site ) {
		switch_to_blog( $ldbtn_site );
		ldbtn_uninstall_site();
		restore_current_blog();
	}
} else {
	ldbtn_uninstall_site();
}
