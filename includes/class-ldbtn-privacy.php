<?php
/**
 * Privacy: policy text, personal data export and erasure.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * Connects votes to the WordPress privacy tools.
 */
class LDBTN_Privacy {

	/**
	 * Hooks into WordPress.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'policy' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/**
	 * Suggested privacy policy text.
	 */
	public static function policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$text = '<p>' . __( 'When you like or dislike a post or comment, we store your reaction and the date. If you are logged in, the vote is linked to your account. If you are not, we store either a random ID from a cookie named ldbtn_vid or a one-way hash of your IP address, so each person votes once. Your IP address itself is never stored. No vote data is sent to other services.', 'like-dislike-button' ) . '</p>';
		wp_add_privacy_policy_content( 'Like Dislike Button', wp_kses_post( $text ) );
	}

	/**
	 * Registers the exporter.
	 *
	 * @param array $exporters Exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['like-dislike-button'] = array(
			'exporter_friendly_name' => __( 'Likes and dislikes', 'like-dislike-button' ),
			'callback'               => array( __CLASS__, 'export' ),
		);
		return $exporters;
	}

	/**
	 * Registers the eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['like-dislike-button'] = array(
			'eraser_friendly_name' => __( 'Likes and dislikes', 'like-dislike-button' ),
			'callback'             => array( __CLASS__, 'erase' ),
		);
		return $erasers;
	}

	/**
	 * Exports a user's votes.
	 *
	 * @param string $email Email address.
	 * @param int    $page  Page number.
	 * @return array
	 */
	public static function export( $email, $page = 1 ) {
		global $wpdb;
		$user  = get_user_by( 'email', $email );
		$items = array();
		$limit = 200;
		$rows  = array();

		if ( $user ) {
			$table = esc_sql( LDBTN_DB::table() );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, object_type, object_id, reaction, created_at FROM {$table} WHERE user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d", $user->ID, $limit, ( max( 1, (int) $page ) - 1 ) * $limit ) );

			foreach ( $rows as $row ) {
				$link    = 'comment' === $row->object_type ? get_comment_link( (int) $row->object_id ) : get_permalink( (int) $row->object_id );
				$items[] = array(
					'group_id'    => 'ldbtn-votes',
					'group_label' => __( 'Likes and dislikes', 'like-dislike-button' ),
					'item_id'     => 'ldbtn-vote-' . $row->id,
					'data'        => array(
						array(
							'name'  => __( 'Item', 'like-dislike-button' ),
							'value' => $link ? $link : $row->object_type . ' #' . $row->object_id,
						),
						array(
							'name'  => __( 'Reaction', 'like-dislike-button' ),
							'value' => $row->reaction,
						),
						array(
							'name'  => __( 'Date (UTC)', 'like-dislike-button' ),
							'value' => $row->created_at,
						),
					),
				);
			}
		}

		return array(
			'data' => $items,
			'done' => count( $rows ) < $limit,
		);
	}

	/**
	 * Deletes a user's votes and recounts the affected items.
	 *
	 * @param string $email Email address.
	 * @return array
	 */
	public static function erase( $email ) {
		global $wpdb;
		$user    = get_user_by( 'email', $email );
		$removed = 0;

		if ( $user ) {
			$table = esc_sql( LDBTN_DB::table() );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name.
			$items = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT object_type, object_id FROM {$table} WHERE user_id = %d", $user->ID ) );
			$removed = (int) $wpdb->delete( $table, array( 'user_id' => $user->ID ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			foreach ( $items as $item ) {
				LDBTN_Votes::recount( $item->object_type, (int) $item->object_id );
			}
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
