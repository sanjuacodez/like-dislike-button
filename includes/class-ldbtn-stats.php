<?php
/**
 * Vote analytics queries.
 *
 * Period figures come from the votes table, which records when each current
 * vote was cast. Totals imported from other plugins have no dates, so they
 * appear in all-time figures only.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * Totals, time series and top lists for the dashboard.
 */
class LDBTN_Stats {

	/**
	 * Dashboard periods: slug => days (0 = all time).
	 *
	 * @return array
	 */
	public static function periods() {
		return array(
			'7'   => 7,
			'30'  => 30,
			'90'  => 90,
			'365' => 365,
			'all' => 0,
		);
	}

	/**
	 * UTC start of a period that ends now.
	 *
	 * @param int $days   Length in days.
	 * @param int $offset Periods back (1 = the period before).
	 * @return string[] [start, end] in MySQL format.
	 */
	private static function range( $days, $offset = 0 ) {
		$end   = time() - $offset * $days * DAY_IN_SECONDS;
		$start = $end - $days * DAY_IN_SECONDS;
		return array( gmdate( 'Y-m-d H:i:s', $start ), gmdate( 'Y-m-d H:i:s', $end ) );
	}

	/**
	 * Likes and dislikes for a period.
	 *
	 * @param int $days   Days, 0 for all time (includes imported totals).
	 * @param int $offset Periods back.
	 * @return array{likes:int,dislikes:int}
	 */
	public static function totals( $days, $offset = 0 ) {
		global $wpdb;

		if ( ! $days ) {
			$totals = array(
				'likes'    => 0,
				'dislikes' => 0,
			);
			foreach ( array( $wpdb->postmeta, $wpdb->commentmeta ) as $meta_table ) {
				$meta_table = esc_sql( $meta_table );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core table names.
				$row                 = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(CASE WHEN meta_key = %s THEN meta_value ELSE 0 END) AS likes, SUM(CASE WHEN meta_key = %s THEN meta_value ELSE 0 END) AS dislikes FROM {$meta_table} WHERE meta_key IN (%s, %s)", LDBTN_Votes::META_LIKES, LDBTN_Votes::META_DISLIKES, LDBTN_Votes::META_LIKES, LDBTN_Votes::META_DISLIKES ) );
				$totals['likes']    += (int) $row->likes;
				$totals['dislikes'] += (int) $row->dislikes;
			}
			return $totals;
		}

		$table = esc_sql( LDBTN_DB::table() );
		list( $start, $end ) = self::range( $days, $offset );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(reaction = 'like') AS likes, SUM(reaction = 'dislike') AS dislikes FROM {$table} WHERE created_at >= %s AND created_at < %s", $start, $end ) );

		return array(
			'likes'    => (int) $row->likes,
			'dislikes' => (int) $row->dislikes,
		);
	}

	/**
	 * Votes per day (periods up to 90 days) or per month, in the site's time zone.
	 *
	 * @param int $days Days, 0 for all time.
	 * @return array List of [label, likes, dislikes, bucket key].
	 */
	public static function series( $days ) {
		global $wpdb;
		$table   = esc_sql( LDBTN_DB::table() );
		$offset  = (int) wp_timezone()->getOffset( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) );
		$monthly = ! $days || $days > 90;
		$format  = $monthly ? '%Y-%m' : '%Y-%m-%d';

		if ( $days ) {
			list( $start ) = self::range( $days );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name.
			$start = $wpdb->get_var( "SELECT MIN(created_at) FROM {$table}" );
			if ( ! $start ) {
				return array();
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT DATE_FORMAT(DATE_ADD(created_at, INTERVAL %d SECOND), %s) AS bucket, SUM(reaction = 'like') AS likes, SUM(reaction = 'dislike') AS dislikes FROM {$table} WHERE created_at >= %s GROUP BY bucket", $offset, $format, $start ) );

		$found = array();
		foreach ( $rows as $row ) {
			$found[ $row->bucket ] = array( (int) $row->likes, (int) $row->dislikes );
		}

		// Fill empty days or months so the axis is continuous.
		$tz     = wp_timezone();
		$cursor = new DateTime( $start, new DateTimeZone( 'UTC' ) );
		$cursor->setTimezone( $tz );
		$now = new DateTime( 'now', $tz );
		if ( $monthly ) {
			$cursor->modify( 'first day of this month' );
		}
		$cursor->setTime( 0, 0 );

		$series = array();
		while ( $cursor <= $now ) {
			$key      = $cursor->format( $monthly ? 'Y-m' : 'Y-m-d' );
			$values   = isset( $found[ $key ] ) ? $found[ $key ] : array( 0, 0 );
			$label    = $monthly ? wp_date( 'M Y', $cursor->getTimestamp() ) : wp_date( 'M j', $cursor->getTimestamp() );
			$series[] = array( $label, $values[0], $values[1], $key );
			$cursor->modify( $monthly ? '+1 month' : '+1 day' );
		}

		// A rolling window spans one extra partial day; keep exactly $days buckets.
		if ( $days && ! $monthly && count( $series ) > $days ) {
			$series = array_slice( $series, -$days );
		}

		return $series;
	}

	/**
	 * Posts with the most likes or dislikes in a period.
	 *
	 * @param int    $days     Days, 0 for all time.
	 * @param string $reaction like|dislike.
	 * @param int    $limit    Rows.
	 * @return array List of objects with id, likes, dislikes.
	 */
	public static function top( $days, $reaction, $limit = 10 ) {
		global $wpdb;
		$order = 'like' === $reaction ? 'likes' : 'dislikes';

		if ( ! $days ) {
			$key = 'like' === $reaction ? LDBTN_Votes::META_LIKES : LDBTN_Votes::META_DISLIKES;
			// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Order column is fixed above.
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT p.ID AS id,
						CAST(COALESCE(l.meta_value, 0) AS UNSIGNED) AS likes,
						CAST(COALESCE(d.meta_value, 0) AS UNSIGNED) AS dislikes
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = %s
					LEFT JOIN {$wpdb->postmeta} l ON l.post_id = p.ID AND l.meta_key = %s
					LEFT JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key = %s
					WHERE CAST(s.meta_value AS UNSIGNED) > 0
					ORDER BY {$order} DESC, p.ID DESC LIMIT %d",
					$key,
					LDBTN_Votes::META_LIKES,
					LDBTN_Votes::META_DISLIKES,
					$limit
				)
			);
			// phpcs:enable
		}

		$table           = esc_sql( LDBTN_DB::table() );
		list( $start )   = self::range( $days );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name; order column is fixed above.
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT object_id AS id, SUM(reaction = 'like') AS likes, SUM(reaction = 'dislike') AS dislikes
				FROM {$table} WHERE object_type = 'post' AND created_at >= %s
				GROUP BY object_id HAVING {$order} > 0
				ORDER BY {$order} DESC, object_id DESC LIMIT %d",
				$start,
				$limit
			)
		);
		// phpcs:enable
	}
}
