<?php
/**
 * REST API: `ldb/v1/state`, `ldb/v1/vote` and the `ldb_votes` field.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST routes used by the buttons, open to visitors by design.
 */
class LDB_Rest {

	const NS = 'ldb/v1';

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
				// Anyone may vote; LDB_Votes::cast() applies the login and role rules from settings.
				'permission_callback' => '__return_true',
				'args'                => array(
					'type'     => array(
						'type'    => 'string',
						'enum'    => LDB_Votes::object_types(),
						'default' => 'post',
					),
					'id'       => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
					),
					'reaction' => array(
						'type'     => 'string',
						'enum'     => LDB_Votes::REACTIONS,
						'required' => true,
					),
				),
			)
		);

		if ( LDB_Settings::get( 'rest_field' ) ) {
			register_rest_field(
				(array) LDB_Settings::get( 'post_types' ),
				'ldb_votes',
				array(
					'get_callback' => function ( $post ) {
						return LDB_Votes::counts( 'post', (int) $post['id'] );
					},
					'schema'       => array(
						'description' => __( 'Like and dislike totals.', 'like-dislike-button' ),
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
			if ( 2 === count( $parts ) && in_array( $parts[0], LDB_Votes::object_types(), true ) && absint( $parts[1] ) ) {
				$items[ $parts[0] . ':' . absint( $parts[1] ) ] = array( $parts[0], absint( $parts[1] ) );
			}
		}

		$ids = array();
		foreach ( $items as $item ) {
			$ids[ $item[0] ][] = $item[1];
		}
		foreach ( $ids as $type => $list ) {
			update_meta_cache( $type, $list );
		}

		$voter = LDB_Votes::voter();
		$votes = LDB_Votes::votes_for( array_values( $items ), $voter['key'] );

		$out = array();
		foreach ( $items as $key => $item ) {
			$out[ $key ] = LDB_Votes::counts( $item[0], $item[1] ) + array( 'vote' => isset( $votes[ $key ] ) ? $votes[ $key ] : '' );
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
		$result = LDB_Votes::cast( $request['type'], (int) $request['id'], $request['reaction'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$response = rest_ensure_response( $result );
		$response->header( 'Cache-Control', 'no-store, private' );
		return $response;
	}
}
