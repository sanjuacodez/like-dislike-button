<?php
/**
 * Most liked posts: query, shortcode and block output.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lists the posts with the most likes, all time or for a recent period.
 */
class Likenook_Most_Liked {

	/**
	 * Period slugs and their length in days (0 = all time).
	 *
	 * @return array
	 */
	public static function periods() {
		return array(
			'all'   => 0,
			'week'  => 7,
			'month' => 30,
			'year'  => 365,
		);
	}

	/**
	 * Post IDs with their like totals, most liked first.
	 *
	 * @param array $args number, post_type, period.
	 * @return array List of [post_id, likes].
	 */
	public static function query( $args ) {
		global $wpdb;

		$number    = max( 1, min( 50, (int) $args['number'] ) );
		$post_type = sanitize_key( $args['post_type'] );
		$periods   = self::periods();
		$days      = isset( $periods[ $args['period'] ] ) ? $periods[ $args['period'] ] : 0;
		$cache_key = 'likenook_ml_' . md5( $number . '|' . $post_type . '|' . $days );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$rows = array();
		if ( $days ) {
			$table = esc_sql( Likenook_DB::table() );
			$since = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
			// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT v.object_id AS id, COUNT(*) AS likes
					FROM {$table} v INNER JOIN {$wpdb->posts} p ON p.ID = v.object_id
					WHERE v.object_type = 'post' AND v.reaction = 'like' AND v.created_at >= %s
						AND p.post_type = %s AND p.post_status = 'publish' AND p.post_password = ''
					GROUP BY v.object_id ORDER BY likes DESC, v.object_id DESC LIMIT %d",
					$since,
					$post_type,
					$number
				)
			);
			// phpcs:enable
		} else {
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT p.ID AS id, CAST(m.meta_value AS UNSIGNED) AS likes
					FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
					WHERE m.meta_key = %s AND CAST(m.meta_value AS UNSIGNED) > 0
						AND p.post_type = %s AND p.post_status = 'publish' AND p.post_password = ''
					ORDER BY likes DESC, p.ID DESC LIMIT %d",
					Likenook_Votes::META_LIKES,
					$post_type,
					$number
				)
			);
		}

		$list = array();
		foreach ( $rows as $row ) {
			$list[] = array( (int) $row->id, (int) $row->likes );
		}

		set_transient( $cache_key, $list, 10 * MINUTE_IN_SECONDS );
		return $list;
	}

	/**
	 * The list markup.
	 *
	 * @param array  $args               number, post_type, period, show_count, show_thumbnail.
	 * @param string $wrapper_attributes Attributes for the list element.
	 * @return string
	 */
	public static function render( $args, $wrapper_attributes = '' ) {
		$args = wp_parse_args(
			$args,
			array(
				'number'         => 5,
				'post_type'      => 'post',
				'period'         => 'all',
				'show_count'     => true,
				'show_thumbnail' => false,
			)
		);

		$list = self::query( $args );
		if ( ! $list ) {
			return '';
		}

		wp_enqueue_style( 'likenook' );
		_prime_post_caches( wp_list_pluck( $list, 0 ) );

		$attributes = $wrapper_attributes ? $wrapper_attributes : 'class="likenook-most-liked"';
		$icon       = Likenook_Icons::get( Likenook_Settings::get( 'icon_set' ), 'like' );

		$html = '<ol ' . $attributes . '>';
		foreach ( $list as $item ) {
			$post = get_post( $item[0] );
			if ( ! $post ) {
				continue;
			}
			$html .= '<li>';
			if ( $args['show_thumbnail'] && has_post_thumbnail( $post ) ) {
				$html .= '<a class="likenook-most-liked__thumb" href="' . esc_url( get_permalink( $post ) ) . '" tabindex="-1" aria-hidden="true">' . get_the_post_thumbnail( $post, 'thumbnail', array( 'alt' => '' ) ) . '</a>';
			}
			$html .= '<a class="likenook-most-liked__title" href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
			if ( $args['show_count'] ) {
				$html .= '<span class="likenook-most-liked__count">' . $icon . '<span>' . esc_html(
					/* translators: %s: number of likes */
					sprintf( _n( '%s like', '%s likes', $item[1], 'likenook' ), Likenook_Render::format_number( $item[1] ) )
				) . '</span></span>';
			}
			$html .= '</li>';
		}
		$html .= '</ol>';

		return $html;
	}

	/**
	 * [likenook_most_liked number="5" post_type="post" period="all|week|month|year" show_count="yes" show_thumbnail="no"]
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'number'         => 5,
				'post_type'      => 'post',
				'period'         => 'all',
				'show_count'     => 'yes',
				'show_thumbnail' => 'no',
			),
			$atts,
			'likenook_most_liked'
		);
		return self::render(
			array(
				'number'         => (int) $atts['number'],
				'post_type'      => $atts['post_type'],
				'period'         => $atts['period'],
				'show_count'     => 'no' !== $atts['show_count'],
				'show_thumbnail' => 'yes' === $atts['show_thumbnail'],
			)
		);
	}
}
