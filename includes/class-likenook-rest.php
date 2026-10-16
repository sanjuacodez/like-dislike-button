<?php
/**
 * REST API: `likenook/v1/state`, `likenook/v1/vote` and the `likenook_votes` field.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST routes used by the buttons, open to visitors by design.
 */
class Likenook_Rest {

	const NS = 'likenook/v1';

	/**
	 * Hooks into WordPress.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registers routes and the post field.
	 */
	public static function register() {
		register_rest_route(
			self::NS,
			'/state',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'state' ),
				// Public by design: buttons on cached pages load their counts here for logged-out
				// visitors. It only answers for items anyone can already see (see state()), and
				// returns only their like/dislike totals plus the caller's own vote.
				'permission_callback' => '__return_true',
				'args'                => array(
					'items' => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'Comma-separated list of type:id pairs, up to 100.',
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/vote',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'vote' ),
				// Anyone may vote; Likenook_Votes::cast() applies the login and role rules from settings.
				'permission_callback' => '__return_true',
				'args'                => array(
					'type'     => array(
						'type'    => 'string',
						'enum'    => Likenook_Votes::object_types(),
						'default' => 'post',
					),
					'id'       => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
					),
					'reaction' => array(
						'type'     => 'string',
						'enum'     => Likenook_Votes::REACTIONS,
						'required' => true,
					),
				),
			)
		);

		if ( Likenook_Settings::get( 'rest_field' ) ) {
			register_rest_field(
				(array) Likenook_Settings::get( 'post_types' ),
				'likenook_votes',
				array(
					'get_callback' => function ( $post ) {
						return Likenook_Votes::counts( 'post', (int) $post['id'] );
					},
					'schema'       => array(
						'description' => __( 'Like and dislike totals.', 'likenook' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'likes'    => array( 'type' => 'integer' ),
							'dislikes' => array( 'type' => 'integer' ),
						),
					),
				)
			);
		}
	}

	/**
	 * Live totals and the visitor's own votes for several items.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function state( WP_REST_Request $request ) {
		$items = array();
		foreach ( array_slice( explode( ',', (string) $request['items'] ), 0, 100 ) as $pair ) {
			$parts = explode( ':', trim( $pair ) );
			if ( 2 === count( $parts ) && in_array( $parts[0], Likenook_Votes::object_types(), true ) && absint( $parts[1] ) ) {
				$items[ $parts[0] . ':' . absint( $parts[1] ) ] = array( $parts[0], absint( $parts[1] ) );
			}
		}

		$ids = array();
		foreach ( $items as $item ) {
			$ids[ $item[0] ][] = $item[1];
		}
		if ( ! empty( $ids['post'] ) ) {
			_prime_post_caches( $ids['post'] );
		}
		if ( ! empty( $ids['comment'] ) ) {
			_prime_comment_caches( $ids['comment'] );
		}

		// Only items a visitor could see anyway: published, publicly viewable posts that are
		// not password protected, and approved comments on them. Anything else is left out.
		$items = array_filter(
			$items,
			function ( $item ) {
				return true === Likenook_Votes::validate_object( $item[0], $item[1] );
			}
		);

		$ids = array();
		foreach ( $items as $item ) {
			$ids[ $item[0] ][] = $item[1];
		}
		foreach ( $ids as $type => $list ) {
			update_meta_cache( $type, $list );
		}

		$voter = Likenook_Votes::voter();
		$votes = Likenook_Votes::votes_for( array_values( $items ), $voter['key'] );

		$out = array();
		foreach ( $items as $key => $item ) {
			$out[ $key ] = Likenook_Votes::counts( $item[0], $item[1] ) + array( 'vote' => isset( $votes[ $key ] ) ? $votes[ $key ] : '' );
		}

		$response = rest_ensure_response( array( 'items' => (object) $out ) );
		$response->header( 'Cache-Control', 'no-store, private' );
		return $response;
	}

	/**
	 * Casts, switches or removes a vote.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function vote( WP_REST_Request $request ) {
		$result = Likenook_Votes::cast( $request['type'], (int) $request['id'], $request['reaction'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$response = rest_ensure_response( $result );
		$response->header( 'Cache-Control', 'no-store, private' );
		return $response;
	}
}
