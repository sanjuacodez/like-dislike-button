<?php
/**
 * Icon sets.
 *
 * SVG paths come from Lucide (https://lucide.dev, ISC licence). Paths marked
 * `ldb-f` fill in when the button is active; `ldb-t` gets a light tint.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the like and dislike icons for each set.
 */
class LDB_Icons {

	/**
	 * Every icon set: label plus inner SVG markup or emoji per reaction.
	 *
	 * @return array
	 */
	public static function sets() {
		$sets = array(
			'thumbs'  => array(
				'label'   => __( 'Thumbs', 'like-dislike-button' ),
				'like'    => '<path class="ldb-f" d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/><path d="M7 10v12"/>',
				'dislike' => '<path class="ldb-f" d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"/><path d="M17 14V2"/>',
			),
			'hearts'  => array(
				'label'   => __( 'Hearts', 'like-dislike-button' ),
				'like'    => '<path class="ldb-f" d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/>',
				'dislike' => '<path class="ldb-t" d="M13.508 20.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5a5.5 5.5 0 0 1 9.591-3.677.6.6 0 0 0 .818.001A5.5 5.5 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5z"/><path d="M12.409 5.824c-.702.792-1.15 1.496-1.415 2.166l2.153 2.156a.5.5 0 0 1 0 .707l-2.293 2.293a.5.5 0 0 0 0 .707L12 15"/>',
			),
			'arrows'  => array(
				'label'   => __( 'Up / down', 'like-dislike-button' ),
				'like'    => '<path class="ldb-f" d="M9 19a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-6a1 1 0 0 1 1-1h3.293a.707.707 0 0 0 .5-1.207l-7.086-7.086a1 1 0 0 0-1.414 0l-7.086 7.086a.707.707 0 0 0 .5 1.207H8a1 1 0 0 1 1 1z"/>',
				'dislike' => '<path class="ldb-f" d="M9 5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v6a1 1 0 0 0 1 1h3.293a.707.707 0 0 1 .5 1.207l-7.086 7.086a1 1 0 0 1-1.414 0l-7.086-7.086a.707.707 0 0 1 .5-1.207H8a1 1 0 0 0 1-1z"/>',
			),
			'smileys' => array(
				'label'   => __( 'Smileys', 'like-dislike-button' ),
				'like'    => '<circle class="ldb-t" cx="12" cy="12" r="10"/><path d="M15 10V9"/><path d="M16.472 15a6 6 0 0 1-8.943 0"/><path d="M9 10V9"/>',
				'dislike' => '<circle class="ldb-t" cx="12" cy="12" r="10"/><path d="M15 10V9"/><path d="M9 10V9"/><path d="M9 16a5 5 0 0 1 6 0"/>',
			),
			'checks'  => array(
				'label'   => __( 'Yes / no', 'like-dislike-button' ),
				'like'    => '<circle class="ldb-t" cx="12" cy="12" r="10"/><path d="m16 9-5.5 5.5L8 12"/>',
				'dislike' => '<circle class="ldb-t" cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
			),
			'flames'  => array(
				'label'   => __( 'Hot / not', 'like-dislike-button' ),
				'like'    => '<path class="ldb-f" d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"/>',
				'dislike' => '<path d="m10 20-1.25-2.5L6 18"/><path d="M10 4 8.75 6.5 6 6"/><path d="m14 20 1.25-2.5L18 18"/><path d="m14 4 1.25 2.5L18 6"/><path d="m17 21-3-6h-4"/><path d="m17 3-3 6 1.5 3"/><path d="M2 12h6.5L10 9"/><path d="m20 10-1.5 2 1.5 2"/><path d="M22 12h-6.5L14 15"/><path d="m4 10 1.5 2L4 14"/><path d="m7 21 3-6-1.5-3"/><path d="m7 3 3 6h4"/>',
			),
			'emoji'   => array(
				'label'   => __( 'Emoji', 'like-dislike-button' ),
				'emoji'   => true,
				'like'    => '👍',
				'dislike' => '👎',
			),
			'none'    => array(
				'label' => __( 'Text only', 'like-dislike-button' ),
			),
			'custom'  => array(
				'label' => __( 'Your own images', 'like-dislike-button' ),
			),
		);

		/**
		 * Filters the icon sets. Add a set with `label`, `like` and `dislike`
		 * holding inner SVG markup for a 24×24 stroked icon.
		 *
		 * @param array $sets Icon sets keyed by slug.
		 */
		return apply_filters( 'ldb_icon_sets', $sets );
	}

	/**
	 * Icon markup for one button, or an empty string for the text-only set.
	 *
	 * @param string $set      Icon set slug.
	 * @param string $reaction like|dislike.
	 * @param array  $settings Settings, for custom images.
	 * @return string
	 */
	public static function get( $set, $reaction, $settings = null ) {
		$sets = self::sets();
		$set  = isset( $sets[ $set ] ) ? $set : 'thumbs';

		if ( 'custom' === $set ) {
			$settings = $settings ? $settings : LDB_Settings::all();
			$id       = (int) $settings[ 'custom_' . $reaction . '_icon' ];
			$src      = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
			if ( ! $src ) {
				return self::get( 'thumbs', $reaction );
			}
			return '<span class="ldb-icon ldb-icon--img" aria-hidden="true"><img src="' . esc_url( $src ) . '" alt="" width="24" height="24" decoding="async"></span>';
		}

		if ( empty( $sets[ $set ][ $reaction ] ) ) {
			return '';
		}

		if ( ! empty( $sets[ $set ]['emoji'] ) ) {
			return '<span class="ldb-icon ldb-icon--emoji" aria-hidden="true">' . esc_html( $sets[ $set ][ $reaction ] ) . '</span>';
		}

		return '<span class="ldb-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">'
			. wp_kses( $sets[ $set ][ $reaction ], self::allowed_svg() )
			. '</svg></span>';
	}

	/**
	 * SVG tags allowed inside an icon.
	 *
	 * @return array
	 */
	public static function allowed_svg() {
		$shape = array(
			'class'     => true,
			'd'         => true,
			'cx'        => true,
			'cy'        => true,
			'r'         => true,
			'x'         => true,
			'y'         => true,
			'width'     => true,
			'height'    => true,
			'rx'        => true,
			'points'    => true,
			'x1'        => true,
			'x2'        => true,
			'y1'        => true,
			'y2'        => true,
			'transform' => true,
		);
		return array(
			'path'     => $shape,
			'circle'   => $shape,
			'rect'     => $shape,
			'line'     => $shape,
			'polyline' => $shape,
			'polygon'  => $shape,
			'g'        => $shape,
		);
	}
}
