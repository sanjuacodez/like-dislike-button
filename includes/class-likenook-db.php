<?php
/**
 * The votes table.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates and upgrades the `{prefix}likenook_votes` table.
 *
 * One row per voter per item holds that voter's current reaction. Totals are
 * cached in post or comment meta (`_likenook_likes`, `_likenook_dislikes`, `_likenook_score`)
 * so lists and sorting never touch this table.
 */
class Likenook_DB {

	const VERSION_OPTION = 'likenook_db_version';

	/**
	 * Full table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'likenook_votes';
	}

	/**
	 * Creates or updates the table.
	 */
	public static function install() {
		global $wpdb;

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
object_type varchar(20) NOT NULL DEFAULT 'post',
object_id bigint(20) unsigned NOT NULL,
reaction varchar(20) NOT NULL,
voter_key varchar(64) NOT NULL,
user_id bigint(20) unsigned NOT NULL DEFAULT 0,
created_at datetime NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY voter (object_type,object_id,voter_key),
KEY object_reaction (object_type,object_id,reaction),
KEY created_at (created_at),
KEY user_id (user_id)
) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::VERSION_OPTION, LIKENOOK_DB_VERSION, false );
	}

	/**
	 * Runs the installer when the table is missing or out of date, for
	 * updates that skip the activation hook.
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::VERSION_OPTION ) !== LIKENOOK_DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Whether a table exists, for import sources.
	 *
	 * @param string $table Full table name.
	 * @return bool
	 */
	public static function table_exists( $table ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}
}
