<?php
/**
 * Settings: defaults, reading and sanitizing.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * Holds the single `ldbtn_settings` option.
 */
class LDBTN_Settings {

	const OPTION = 'ldbtn_settings';

	/**
	 * Settings for this request, merged over the defaults.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Every setting and its default. Empty text means "use the translated default".
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// Where.
			'post_types'        => array( 'post' ),
			'comments'          => false,
			'position'          => 'after',
			'archives'          => false,
			// What.
			'buttons'           => 'both',
			'order'             => 'like_first',
			// Look.
			'icon_set'          => 'thumbs',
			'custom_like_icon'  => 0,
			'custom_dislike_icon' => 0,
			'style'             => 'soft',
			'size'              => 'md',
			'align'             => 'left',
			'layout'            => 'row',
			'show_labels'       => false,
			'like_label'        => '',
			'dislike_label'     => '',
			'counts'            => 'show',
			'short_numbers'     => true,
			'ratio_bar'         => false,
			'animation'         => 'pop',
			'like_color'        => '',
			'dislike_color'     => '',
			'text_color'        => '',
			'bg_color'          => '',
			'question'          => '',
			'thanks'            => '',
			// Voting.
			'who'               => 'everyone',
			'roles'             => array(),
			'guest_tracking'    => 'cookie',
			'allow_change'      => true,
			'ip_header'         => '',
			// Advanced.
			'rest_field'        => true,
			'schema'            => true,
			'compat_shortcodes' => false,
			'delete_data'       => false,
		);
	}

	/**
	 * Allowed values for every choice setting.
	 *
	 * @return array
	 */
	public static function choices() {
		return array(
			'position'       => array( 'after', 'before', 'both', 'manual' ),
			'buttons'        => array( 'both', 'like', 'dislike' ),
			'order'          => array( 'like_first', 'dislike_first' ),
			'icon_set'       => array_keys( LDBTN_Icons::sets() ),
			'style'          => array( 'soft', 'outline', 'pill', 'minimal', 'solid', 'glass' ),
			'size'           => array( 'sm', 'md', 'lg' ),
			'align'          => array( 'left', 'center', 'right' ),
			'layout'         => array( 'row', 'stack' ),
			'counts'         => array( 'show', 'hide', 'after_vote', 'percent', 'score' ),
			'animation'      => array( 'none', 'pop', 'bounce', 'burst' ),
			'who'            => array( 'everyone', 'logged_in' ),
			'guest_tracking' => array( 'cookie', 'ip' ),
			'ip_header'      => array( '', 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP' ),
		);
	}

	/**
	 * All settings.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		}
		return self::$cache;
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting name.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Saves a partial update without going through the settings form.
	 *
	 * @param array $changes Settings to change.
	 */
	public static function update( array $changes ) {
		// Merge over defaults too, since sanitize() reads a missing checkbox as "off".
		self::flush();
		update_option( self::OPTION, self::sanitize( array_merge( self::all(), $changes ) ) );
		self::flush();
	}

	/**
	 * Forgets the cached settings, e.g. after they were saved.
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Whether buttons are switched on for a post type.
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public static function type_enabled( $post_type ) {
		return in_array( $post_type, (array) self::get( 'post_types' ), true );
	}

	/**
	 * Button label, falling back to the translated default.
	 *
	 * @param string $reaction like|dislike.
	 * @param array  $settings Settings to read from.
	 * @return string
	 */
	public static function label( $reaction, $settings = null ) {
		$settings = $settings ? $settings : self::all();
		$custom   = isset( $settings[ $reaction . '_label' ] ) ? $settings[ $reaction . '_label' ] : '';
		if ( '' !== $custom ) {
			return $custom;
		}
		return 'like' === $reaction ? __( 'Like', 'like-dislike-button' ) : __( 'Dislike', 'like-dislike-button' );
	}

	/**
	 * Cleans settings coming from the form or from code.
	 *
	 * @param mixed $input Raw settings.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$choices  = self::choices();
		$clean    = array();

		foreach ( $defaults as $key => $default ) {
			$value = isset( $input[ $key ] ) ? $input[ $key ] : null;

			if ( isset( $choices[ $key ] ) ) {
				$value         = is_string( $value ) ? $value : '';
				$clean[ $key ] = in_array( $value, $choices[ $key ], true ) ? $value : $default;
			} elseif ( is_bool( $default ) ) {
				$clean[ $key ] = ! empty( $value ) && 'no' !== $value;
			} elseif ( is_int( $default ) ) {
				$clean[ $key ] = absint( $value );
			} elseif ( is_array( $default ) ) {
				$clean[ $key ] = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $value ) ) ) );
			} elseif ( substr( $key, -6 ) === '_color' ) {
				$color         = sanitize_hex_color( is_string( $value ) ? $value : '' );
				$clean[ $key ] = $color ? $color : '';
			} else {
				$clean[ $key ] = sanitize_text_field( is_string( $value ) ? $value : '' );
			}
		}

		// Keep only real post types and roles.
		$clean['post_types'] = array_values( array_filter( $clean['post_types'], 'post_type_exists' ) );
		$roles               = array_keys( wp_roles()->roles );
		$clean['roles']      = array_values( array_intersect( $clean['roles'], $roles ) );

		return $clean;
	}
}
