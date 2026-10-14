<?php
/**
 * Imports votes from other like/dislike plugins.
 *
 * Plugins that keep individual votes (WP ULike, Like Dislike for WP) are
 * imported vote by vote, with dates, so logged-in users keep their vote and
 * the dashboard shows history. Plugins that only keep totals (Posts Like
 * Dislike, Like / Dislike Button 2.x) are imported as per-post totals.
 * Running an import again replaces what that source imported before.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * Batch importer used by the Tools screen and WP-CLI.
 */
class LDBTN_Importer {

	const BATCH = 1000;

	/**
	 * Known sources and how to read them.
	 *
	 * @param bool $with_counts Also count what each source holds (slow on big tables).
	 * @return array
	 */
	public static function sources( $with_counts = true ) {
		global $wpdb;

		$sources = array(
			'wp-ulike'           => array(
				'label' => 'WP ULike',
				'kind'  => 'rows',
				'steps' => array(),
			),
			'like-dislike-for-wp' => array(
				'label' => 'Like Dislike for WP',
				'kind'  => 'rows',
				'steps' => array(),
			),
			'posts-like-dislike' => array(
				'label' => 'Posts Like Dislike',
				'kind'  => 'meta',
				'keys'  => array( 'pld_like_count', 'pld_dislike_count' ),
			),
			'ldbtn-2'              => array(
				'label' => __( 'Like / Dislike Button 2.x (this plugin’s old version)', 'like-dislike-button' ),
				'kind'  => 'meta',
				'keys'  => array( '_ld_like_count', '_ld_dislike_count' ),
			),
		);

		// WP ULike 5+ keeps every vote in one ledger table; older versions use one table per item type.
		$pulse = $wpdb->prefix . 'ulike_pulse';
		if ( LDBTN_DB::table_exists( $pulse ) ) {
			$sources['wp-ulike']['steps'][] = array(
				'table'  => $pulse,
				'sql'    => "SELECT id, item_type AS type, item_id AS object_id, engagement_key AS reaction, status, user_id, ip, fingerprint AS token, date_time FROM {$pulse} WHERE id > %d AND engagement_kind = 'vote' ORDER BY id ASC LIMIT %d",
			);
		} else {
			foreach ( array( 'ulike' => array( 'post', 'post_id' ), 'ulike_comments' => array( 'comment', 'comment_id' ), 'ulike_forums' => array( 'post', 'topic_id' ) ) as $suffix => $map ) {
				$table = $wpdb->prefix . $suffix;
				if ( LDBTN_DB::table_exists( $table ) ) {
					$sources['wp-ulike']['steps'][] = array(
						'table' => $table,
						'sql'   => "SELECT id, '{$map[0]}' AS type, {$map[1]} AS object_id, status AS reaction, 'active' AS status, user_id, ip, '' AS token, date_time FROM {$table} WHERE id > %d ORDER BY id ASC LIMIT %d",
					);
				}
			}
		}

		$ldfw = $wpdb->prefix . 'likedislikewp';
		if ( LDBTN_DB::table_exists( $ldfw ) ) {
			$sources['like-dislike-for-wp']['steps'][] = array(
				'table' => $ldfw,
				'sql'   => "SELECT id, object_type AS type, post_id AS object_id, status AS reaction, 'active' AS status, user_id, ip, token, date_time FROM {$ldfw} WHERE id > %d ORDER BY id ASC LIMIT %d",
			);
		}

		foreach ( $sources as $slug => $source ) {
			$sources[ $slug ]['count'] = $with_counts ? self::count( $source ) : null;
		}

		return $sources;
	}

	/**
	 * How many rows or posts a source holds.
	 *
	 * @param array $source Source definition.
	 * @return int
	 */
	private static function count( $source ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names come from sources().
		if ( 'meta' === $source['kind'] ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s) AND meta_value > 0", $source['keys'][0], $source['keys'][1] ) );
		}
		$total = 0;
		foreach ( $source['steps'] as $step ) {
			$table  = esc_sql( $step['table'] );
			$total += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}
		// phpcs:enable
		return $total;
	}

	/**
	 * Imports one batch.
	 *
	 * @param string $slug   Source slug.
	 * @param array  $cursor Position from the previous batch: step and last ID.
	 * @return array|WP_Error {done:bool, cursor:array, processed:int}
	 */
	public static function run_batch( $slug, $cursor = array() ) {
		$sources = self::sources( false );
		if ( ! isset( $sources[ $slug ] ) || ( 'rows' === $sources[ $slug ]['kind'] && ! $sources[ $slug ]['steps'] ) ) {
			return new WP_Error( 'ldbtn_source', __( 'Nothing to import from this plugin.', 'like-dislike-button' ) );
		}
		$source = $sources[ $slug ];
		$cursor = wp_parse_args(
			$cursor,
			array(
				'step'  => 0,
				'after' => 0,
			)
		);

		if ( 'meta' === $source['kind'] ) {
			return self::meta_batch( $slug, $source, (int) $cursor['after'] );
		}
		return self::rows_batch( $slug, $source, (int) $cursor['step'], (int) $cursor['after'] );
	}

	/**
	 * Copies per-post totals into `_ldbtn_imported`.
	 *
	 * @param string $slug   Source slug.
	 * @param array  $source Source definition.
	 * @param int    $after  Last post ID handled.
	 * @return array
	 */
	private static function meta_batch( $slug, $source, $after ) {
		global $wpdb;
		$limit = 500;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s) AND post_id > %d ORDER BY post_id ASC LIMIT %d", $source['keys'][0], $source['keys'][1], $after, $limit ) );

		foreach ( $ids as $id ) {
			$id       = (int) $id;
			$likes    = (int) get_post_meta( $id, $source['keys'][0], true );
			$dislikes = (int) get_post_meta( $id, $source['keys'][1], true );
			$imported = get_post_meta( $id, LDBTN_Votes::META_IMPORTED, true );
			$imported = is_array( $imported ) ? $imported : array();
			if ( $likes || $dislikes ) {
				$imported[ $slug ] = array( $likes, $dislikes );
			} else {
				unset( $imported[ $slug ] );
			}
			if ( $imported ) {
				update_post_meta( $id, LDBTN_Votes::META_IMPORTED, $imported );
			} else {
				delete_post_meta( $id, LDBTN_Votes::META_IMPORTED );
			}
			LDBTN_Votes::recount( 'post', $id );
		}

		$done = count( $ids ) < $limit;
		return array(
			'done'      => $done,
			'cursor'    => array( 'after' => $done ? 0 : (int) end( $ids ) ),
			'processed' => count( $ids ),
		);
	}

	/**
	 * Copies individual votes, keeping each voter's latest reaction.
	 *
	 * @param string $slug   Source slug.
	 * @param array  $source Source definition.
	 * @param int    $step   Index of the table being read.
	 * @param int    $after  Last row ID handled.
	 * @return array
	 */
	private static function rows_batch( $slug, $source, $step, $after ) {
		global $wpdb;

		if ( ! isset( $source['steps'][ $step ] ) ) {
			return array(
				'done'      => true,
				'cursor'    => array(),
				'processed' => 0,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Fixed SQL from sources(), table names checked to exist.
		$rows = $wpdb->get_results( $wpdb->prepare( $source['steps'][ $step ]['sql'], $after, self::BATCH ) );

		// Latest state per voter and item, in row order.
		$final = array();
		foreach ( $rows as $row ) {
			$type = 'comment' === $row->type ? 'comment' : ( 'post' === $row->type || 'topic' === $row->type ? 'post' : '' );
			$id   = (int) $row->object_id;
			if ( ! $type || ! $id ) {
				continue;
			}

			$user = is_numeric( $row->user_id ) ? (int) $row->user_id : 0;
			if ( $user ) {
				$key = 'u:' . $user;
			} else {
				$who = '' !== (string) $row->token ? 'token|' . $row->token : 'ip|' . $row->ip;
				$key = 'i:' . substr( hash( 'sha256', $slug . '|' . $who ), 0, 62 );
			}

			$reaction = in_array( $row->reaction, LDBTN_Votes::REACTIONS, true ) && 'active' === $row->status ? $row->reaction : '';
			$date     = preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $row->date_time ) ? $row->date_time : current_time( 'mysql', true );

			$final[ $type . ':' . $id . ':' . $key ] = array( $type, $id, $key, $user, $reaction, $date );
		}

		$table    = esc_sql( LDBTN_DB::table() );
		$affected = array();
		foreach ( $final as $vote ) {
			list( $type, $id, $key, $user, $reaction, $date ) = $vote;
			if ( $reaction ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name.
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (object_type, object_id, reaction, voter_key, user_id, created_at) VALUES (%s, %d, %s, %s, %d, %s) ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), created_at = VALUES(created_at)", $type, $id, $reaction, $key, $user, $date ) );
			} else {
				$wpdb->delete( $table, array( 'object_type' => $type, 'object_id' => $id, 'voter_key' => $key ), array( '%s', '%d', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			$affected[ $type . ':' . $id ] = array( $type, $id );
		}

		foreach ( $affected as $item ) {
			LDBTN_Votes::recount( $item[0], $item[1] );
		}

		$last = $rows ? (int) end( $rows )->id : 0;
		if ( count( $rows ) < self::BATCH ) {
			$next = $step + 1;
			return array(
				'done'      => ! isset( $source['steps'][ $next ] ),
				'cursor'    => array(
					'step'  => $next,
					'after' => 0,
				),
				'processed' => count( $rows ),
			);
		}

		return array(
			'done'      => false,
			'cursor'    => array(
				'step'  => $step,
				'after' => $last,
			),
			'processed' => count( $rows ),
		);
	}

	/**
	 * After an import, keeps the old plugin's shortcodes working.
	 */
	public static function finish() {
		LDBTN_Settings::update( array( 'compat_shortcodes' => true ) );
		delete_transient( 'ldbtn_admin_counts' );
	}
}
