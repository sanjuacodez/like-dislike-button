<?php
/**
 * Casting, reading and counting votes.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * Vote logic shared by the REST API, admin tools, importers and WP-CLI.
 */
class LDB_Votes {

	const COOKIE    = 'ldb_vid';
	const REACTIONS = array( 'like', 'dislike' );

	const META_LIKES    = '_ldb_likes';
	const META_DISLIKES = '_ldb_dislikes';
	const META_SCORE    = '_ldb_score';
	const META_IMPORTED = '_ldb_imported';
	const META_HIDE     = '_ldb_hide';

	/**
	 * Object types that can be voted on.
	 *
	 * @return string[]
	 */
	public static function object_types() {
		return array( 'post', 'comment' );
	}

	/**
	 * Cached totals for one item.
	 *
	 * @param string $type post|comment.
	 * @param int    $id   Item ID.
	 * @return array{likes:int,dislikes:int}
	 */
	public static function counts( $type, $id ) {
		return array(
			'likes'    => (int) get_metadata( $type, $id, self::META_LIKES, true ),
			'dislikes' => (int) get_metadata( $type, $id, self::META_DISLIKES, true ),
		);
	}

	/**
	 * Recounts one item from the votes table plus any imported totals, and
	 * stores the result in meta.
	 *
	 * @param string $type post|comment.
	 * @param int    $id   Item ID.
	 * @return array{likes:int,dislikes:int}
	 */
	public static function recount( $type, $id ) {
		global $wpdb;
		$table = esc_sql( LDB_DB::table() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT reaction, COUNT(*) AS total FROM {$table} WHERE object_type = %s AND object_id = %d GROUP BY reaction", $type, $id ) );

		$counts = array(
			'likes'    => 0,
			'dislikes' => 0,
		);
		foreach ( $rows as $row ) {
			if ( 'like' === $row->reaction ) {
				$counts['likes'] = (int) $row->total;
			} elseif ( 'dislike' === $row->reaction ) {
				$counts['dislikes'] = (int) $row->total;
			}
		}

		$imported = get_metadata( $type, $id, self::META_IMPORTED, true );
		if ( is_array( $imported ) ) {
			foreach ( $imported as $totals ) {
				$counts['likes']    += isset( $totals[0] ) ? (int) $totals[0] : 0;
				$counts['dislikes'] += isset( $totals[1] ) ? (int) $totals[1] : 0;
			}
		}

		if ( $counts['likes'] || $counts['dislikes'] ) {
			update_metadata( $type, $id, self::META_LIKES, $counts['likes'] );
			update_metadata( $type, $id, self::META_DISLIKES, $counts['dislikes'] );
			update_metadata( $type, $id, self::META_SCORE, $counts['likes'] - $counts['dislikes'] );
		} else {
			delete_metadata( $type, $id, self::META_LIKES );
			delete_metadata( $type, $id, self::META_DISLIKES );
			delete_metadata( $type, $id, self::META_SCORE );
		}

		return $counts;
	}

	/**
	 * Checks that an item exists and is open for voting.
	 *
	 * @param string $type post|comment.
	 * @param int    $id   Item ID.
	 * @return true|WP_Error
	 */
	public static function validate_object( $type, $id ) {
		$missing = new WP_Error( 'ldb_missing', __( 'This item can no longer be voted on.', 'like-dislike-button' ), array( 'status' => 404 ) );

		if ( 'comment' === $type ) {
			$comment = get_comment( $id );
			if ( ! $comment || '1' !== (string) $comment->comment_approved || ! LDB_Settings::get( 'comments' ) ) {
				return $missing;
			}
			$id = (int) $comment->comment_post_ID;
		} elseif ( 'post' !== $type ) {
			return $missing;
		}

		$post = get_post( $id );
		if ( ! $post || 'publish' !== $post->post_status || ! is_post_type_viewable( $post->post_type ) || post_password_required( $post ) ) {
			return $missing;
		}

		return true;
	}

	/**
	 * Checks whether the current visitor may vote.
	 *
	 * @param string $type post|comment.
	 * @param int    $id   Item ID.
	 * @return true|WP_Error
	 */
	public static function can_vote( $type, $id ) {
		$result = true;

		if ( 'logged_in' === LDB_Settings::get( 'who' ) ) {
			if ( ! is_user_logged_in() ) {
				$result = new WP_Error( 'ldb_login', __( 'Please log in to vote.', 'like-dislike-button' ), array( 'status' => 401 ) );
			} else {
				$roles = (array) LDB_Settings::get( 'roles' );
				if ( $roles && ! array_intersect( $roles, (array) wp_get_current_user()->roles ) ) {
					$result = new WP_Error( 'ldb_role', __( 'Your account can’t vote on this site.', 'like-dislike-button' ), array( 'status' => 403 ) );
				}
			}
		}

		/**
		 * Filters whether the current visitor may vote on an item.
		 *
		 * @param true|WP_Error $result True to allow, or an error to show.
		 * @param string        $type   post|comment.
		 * @param int           $id     Item ID.
		 */
		return apply_filters( 'ldb_can_vote', $result, $type, $id );
	}

	/**
	 * Whether a reaction is switched on (like-only and dislike-only modes).
	 *
	 * @param string $reaction like|dislike.
	 * @return bool
	 */
	public static function reaction_enabled( $reaction ) {
		$buttons = LDB_Settings::get( 'buttons' );
		return in_array( $reaction, self::REACTIONS, true ) && ( 'both' === $buttons || $reaction === $buttons );
	}

	/**
	 * The visitor's IP address, using the proxy header chosen in settings.
	 *
	 * @return string
	 */
	public static function ip() {
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$header = LDB_Settings::get( 'ip_header' );

		if ( $header && ! empty( $_SERVER[ $header ] ) ) {
			$list      = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
			$candidate = trim( $list[0] );
			if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
				$ip = $candidate;
			}
		}

		/**
		 * Filters the visitor IP used for duplicate checks and rate limiting.
		 * It is only ever stored as a keyed hash.
		 *
		 * @param string $ip IP address.
		 */
		return (string) apply_filters( 'ldb_visitor_ip', $ip );
	}

	/**
	 * One-way keyed hash, so IPs and cookie IDs are never stored as-is.
	 *
	 * @param string $value Value to hash.
	 * @return string 64 hex characters.
	 */
	public static function hash( $value ) {
		return hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
	}

	/**
	 * Identifies the current voter.
	 *
	 * Logged-in users are keyed by account. Visitors are keyed by a random
	 * cookie ID or by their hashed IP, depending on settings.
	 *
	 * @param bool $create Create a cookie ID if the visitor has none yet.
	 * @return array{key:string,user:int}
	 */
	public static function voter( $create = false ) {
		$user = get_current_user_id();
		if ( $user ) {
			return array(
				'key'  => 'u:' . $user,
				'user' => $user,
			);
		}

		if ( 'ip' === LDB_Settings::get( 'guest_tracking' ) ) {
			$ip = self::ip();
			return array(
				'key'  => $ip ? self::hash( 'ip|' . $ip ) : '',
				'user' => 0,
			);
		}

		$vid = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';
		if ( ! preg_match( '/^[a-f0-9]{32}$/', $vid ) ) {
			$vid = '';
			if ( $create ) {
				$vid = bin2hex( random_bytes( 16 ) );
				self::set_cookie( $vid );
			}
		}

		return array(
			'key'  => $vid ? self::hash( 'cookie|' . $vid ) : '',
			'user' => 0,
		);
	}

	/**
	 * Sends the visitor ID cookie.
	 *
	 * @param string $vid Visitor ID.
	 */
	private static function set_cookie( $vid ) {
		$_COOKIE[ self::COOKIE ] = $vid;
		if ( headers_sent() ) {
			return;
		}
		setcookie(
			self::COOKIE,
			$vid,
			array(
				'expires'  => time() + YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	/**
	 * The current voter's reactions for several items at once.
	 *
	 * @param array  $items     List of [type, id] pairs.
	 * @param string $voter_key Voter key.
	 * @return array "type:id" => reaction.
	 */
	public static function votes_for( array $items, $voter_key ) {
		global $wpdb;
		if ( ! $items || '' === $voter_key ) {
			return array();
		}

		$where = array();
		$args  = array( $voter_key );
		foreach ( $items as $item ) {
			$where[] = '(object_type = %s AND object_id = %d)';
			$args[]  = $item[0];
			$args[]  = $item[1];
		}

		$table = esc_sql( LDB_DB::table() );
		$sql   = "SELECT object_type, object_id, reaction FROM {$table} WHERE voter_key = %s AND (" . implode( ' OR ', $where ) . ')';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Placeholders are built above.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );

		$votes = array();
		foreach ( $rows as $row ) {
			$votes[ $row->object_type . ':' . $row->object_id ] = $row->reaction;
		}
		return $votes;
	}

	/**
	 * Casts, switches or removes the current visitor's vote.
	 *
	 * Clicking the reaction you already chose removes it; clicking the other
	 * one switches. Both need "Allow changing votes" in settings.
	 *
	 * @param string $type     post|comment.
	 * @param int    $id       Item ID.
	 * @param string $reaction like|dislike.
	 * @return array|WP_Error Totals plus the visitor's vote after the change.
	 */
	public static function cast( $type, $id, $reaction ) {
		global $wpdb;

		if ( ! self::reaction_enabled( $reaction ) ) {
			return new WP_Error( 'ldb_reaction', __( 'This reaction is turned off.', 'like-dislike-button' ), array( 'status' => 400 ) );
		}

		$valid = self::validate_object( $type, $id );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$allowed = self::can_vote( $type, $id );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$limited = self::rate_limited();
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$voter = self::voter( true );
		if ( '' === $voter['key'] ) {
			return new WP_Error( 'ldb_unknown', __( 'Your vote could not be recorded. Please allow cookies and try again.', 'like-dislike-button' ), array( 'status' => 400 ) );
		}

		$table = esc_sql( LDB_DB::table() );
		$lock  = 'ldb_' . md5( $type . '|' . $id . '|' . $voter['key'] );

		// One click at a time per voter and item, so double clicks can't double count.
		$wpdb->query( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name.
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT reaction FROM {$table} WHERE object_type = %s AND object_id = %d AND voter_key = %s", $type, $id, $voter['key'] ) );
		$where    = array(
			'object_type' => $type,
			'object_id'   => $id,
			'voter_key'   => $voter['key'],
		);
		$result   = null;

		if ( $existing && ! LDB_Settings::get( 'allow_change' ) ) {
			$result = new WP_Error( 'ldb_voted', __( 'You have already voted.', 'like-dislike-button' ), array( 'status' => 409 ) );
		} elseif ( $existing === $reaction ) {
			$wpdb->delete( $table, $where, array( '%s', '%d', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$vote = '';
		} elseif ( $existing ) {
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				array(
					'reaction'   => $reaction,
					'created_at' => current_time( 'mysql', true ),
				),
				$where,
				array( '%s', '%s' ),
				array( '%s', '%d', '%s' )
			);
			$vote = $reaction;
		} else {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				$where + array(
					'reaction'   => $reaction,
					'user_id'    => $voter['user'],
					'created_at' => current_time( 'mysql', true ),
				),
				array( '%s', '%d', '%s', '%s', '%d', '%s' )
			);
			$vote = $reaction;
		}

		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$counts = self::recount( $type, $id );

		/**
		 * Fires after a vote is cast, switched or removed.
		 *
		 * @param string $type     post|comment.
		 * @param int    $id       Item ID.
		 * @param string $vote     New reaction, or '' when removed.
		 * @param string $previous Previous reaction, or '' for a first vote.
		 * @param int    $user_id  Voter's user ID, 0 for visitors.
		 * @param array  $counts   New totals.
		 */
		do_action( 'ldb_vote_recorded', $type, $id, $vote, (string) $existing, $voter['user'], $counts );

		return $counts + array( 'vote' => $vote );
	}

	/**
	 * Limits how many votes one network can cast per minute.
	 *
	 * @return true|WP_Error
	 */
	private static function rate_limited() {
		/**
		 * Filters how many votes one IP address may cast per minute. 0 turns the limit off.
		 *
		 * @param int $limit Votes per minute.
		 */
		$limit = (int) apply_filters( 'ldb_rate_limit', 30 );
		$ip    = self::ip();
		if ( $limit < 1 || '' === $ip ) {
			return true;
		}

		$key   = 'ldb_rl_' . substr( self::hash( 'rl|' . $ip ), 0, 20 );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return new WP_Error( 'ldb_flood', __( 'Too many votes in a short time. Please wait a minute and try again.', 'like-dislike-button' ), array( 'status' => 429 ) );
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Deletes votes for one item, or for every item.
	 *
	 * @param string|null $type post|comment, or null for everything.
	 * @param int|null    $id   Item ID.
	 */
	public static function reset( $type = null, $id = null ) {
		global $wpdb;
		$table = esc_sql( LDB_DB::table() );

		if ( $type && $id ) {
			$wpdb->delete( $table, array( 'object_type' => $type, 'object_id' => $id ), array( '%s', '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			delete_metadata( $type, $id, self::META_IMPORTED );
			self::recount( $type, $id );
			return;
		}

		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( self::object_types() as $object_type ) {
			foreach ( array( self::META_LIKES, self::META_DISLIKES, self::META_SCORE, self::META_IMPORTED ) as $key ) {
				delete_metadata( $object_type, 0, $key, '', true );
			}
		}
	}

	/**
	 * Recounts a batch of items that have votes or imported totals.
	 *
	 * @param string $type   post|comment.
	 * @param int    $after  Continue after this item ID.
	 * @param int    $limit  Batch size.
	 * @return int Last item ID handled, or 0 when done.
	 */
	public static function recount_batch( $type, $after, $limit = 500 ) {
		global $wpdb;
		$table      = esc_sql( LDB_DB::table() );
		$meta_table = esc_sql( 'comment' === $type ? $wpdb->commentmeta : $wpdb->postmeta );
		$id_column  = esc_sql( 'comment' === $type ? 'comment_id' : 'post_id' );

		// Items with votes, plus items that only have cached or imported totals (so stale totals get cleared).
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are internal.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM (
					SELECT object_id AS id FROM {$table} WHERE object_type = %s AND object_id > %d
					UNION SELECT {$id_column} AS id FROM {$meta_table} WHERE meta_key IN (%s, %s) AND {$id_column} > %d
				) AS ids ORDER BY id ASC LIMIT %d",
				$type,
				$after,
				self::META_LIKES,
				self::META_IMPORTED,
				$after,
				$limit
			)
		);
		// phpcs:enable

		foreach ( $ids as $id ) {
			self::recount( $type, (int) $id );
		}

		return count( $ids ) < $limit ? 0 : (int) end( $ids );
	}
}
