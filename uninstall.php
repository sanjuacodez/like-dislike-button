<?php
/**
 * Removes plugin data on uninstall, only if "Delete all data" was switched on.
 *
 * @package Likenook
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Cleans up one site.
 */
function likenook_uninstall_site() {
	global $wpdb;

	$settings = get_option( 'likenook_settings' );
	if ( empty( $settings['delete_data'] ) ) {
		return;
	}

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}likenook_votes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange

	foreach ( array( '_likenook_likes', '_likenook_dislikes', '_likenook_score', '_likenook_imported', '_likenook_hide' ) as $key ) {
		delete_metadata( 'post', 0, $key, '', true );
		delete_metadata( 'comment', 0, $key, '', true );
	}

	delete_option( 'likenook_settings' );
	delete_option( 'likenook_db_version' );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_likenook\_%' OR option_name LIKE '\_transient\_timeout\_likenook\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $likenook_site ) {
		switch_to_blog( $likenook_site );
		likenook_uninstall_site();
		restore_current_blog();
	}
} else {
	likenook_uninstall_site();
}
