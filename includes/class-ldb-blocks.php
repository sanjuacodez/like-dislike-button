<?php
/**
 * Blocks: Like Dislike Buttons and Most Liked Posts.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the blocks and renders them on the server.
 */
class LDB_Blocks {

	/**
	 * Hooks into WordPress.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registers both blocks.
	 */
	public static function register() {
		register_block_type( LDB_PATH . 'blocks/buttons', array( 'render_callback' => array( __CLASS__, 'render_buttons' ) ) );
		register_block_type( LDB_PATH . 'blocks/most-liked', array( 'render_callback' => array( __CLASS__, 'render_most_liked' ) ) );

		$handle = generate_block_asset_handle( 'like-dislike-button/buttons', 'editorScript' );
		wp_add_inline_script( $handle, 'window.ldbBlockOptions = ' . wp_json_encode( self::editor_options() ) . ';', 'before' );

		foreach ( array( 'buttons', 'most-liked' ) as $block ) {
			wp_set_script_translations( generate_block_asset_handle( 'like-dislike-button/' . $block, 'editorScript' ), 'like-dislike-button' );
		}
	}

	/**
	 * Choices shown in the block sidebar.
	 *
	 * @return array
	 */
	private static function editor_options() {
		$options = function ( $labels ) {
			$list = array();
			foreach ( $labels as $value => $label ) {
				$list[] = array(
					'value' => $value,
					'label' => $label,
				);
			}
			return $list;
		};

		return array(
			'iconSets' => $options( wp_list_pluck( LDB_Icons::sets(), 'label' ) ),
			'styles'   => $options( self::style_labels() ),
			'sizes'    => $options(
				array(
					'sm' => __( 'Small', 'like-dislike-button' ),
					'md' => __( 'Medium', 'like-dislike-button' ),
					'lg' => __( 'Large', 'like-dislike-button' ),
				)
			),
			'layouts'  => $options(
				array(
					'row'   => __( 'Side by side', 'like-dislike-button' ),
					'stack' => __( 'Stacked', 'like-dislike-button' ),
				)
			),
			'counts'   => $options( self::count_labels() ),
		);
	}

	/**
	 * Button style names.
	 *
	 * @return array
	 */
	public static function style_labels() {
		return array(
			'soft'    => __( 'Soft', 'like-dislike-button' ),
			'outline' => __( 'Outline', 'like-dislike-button' ),
			'pill'    => __( 'Pill', 'like-dislike-button' ),
			'minimal' => __( 'Minimal', 'like-dislike-button' ),
			'solid'   => __( 'Solid', 'like-dislike-button' ),
			'glass'   => __( 'Glass', 'like-dislike-button' ),
		);
	}

	/**
	 * Count display names.
	 *
	 * @return array
	 */
	public static function count_labels() {
		return array(
			'show'       => __( 'Show counts', 'like-dislike-button' ),
			'hide'       => __( 'Hide counts', 'like-dislike-button' ),
			'after_vote' => __( 'Show after voting', 'like-dislike-button' ),
			'percent'    => __( 'Percentages', 'like-dislike-button' ),
			'score'      => __( 'Score (likes minus dislikes)', 'like-dislike-button' ),
		);
	}

	/**
	 * Server render for the buttons block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Inner content (unused).
	 * @param WP_Block $block      Block instance.
	 * @return string
	 */
	public static function render_buttons( $attributes, $content, $block ) {
		$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$args = array(
			'block'    => true,
			'icon_set' => isset( $attributes['iconSet'] ) ? $attributes['iconSet'] : '',
			'style'    => isset( $attributes['buttonStyle'] ) ? $attributes['buttonStyle'] : '',
			'size'     => isset( $attributes['size'] ) ? $attributes['size'] : '',
			'align'    => isset( $attributes['alignment'] ) ? $attributes['alignment'] : '',
			'layout'   => isset( $attributes['layout'] ) ? $attributes['layout'] : '',
			'counts'   => isset( $attributes['counts'] ) ? $attributes['counts'] : '',
		);
		foreach ( array( 'showLabels' => 'show_labels', 'ratioBar' => 'ratio_bar' ) as $attribute => $setting ) {
			if ( ! empty( $attributes[ $attribute ] ) ) {
				$args[ $setting ] = 'yes' === $attributes[ $attribute ];
			}
		}
		foreach ( array( 'question' => 'question', 'thanks' => 'thanks', 'likeLabel' => 'like_label', 'dislikeLabel' => 'dislike_label' ) as $attribute => $setting ) {
			if ( isset( $attributes[ $attribute ] ) ) {
				$args[ $setting ] = $attributes[ $attribute ];
			}
		}

		return LDB_Render::render( 'post', $post_id, $args );
	}

	/**
	 * Server render for the most liked block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_most_liked( $attributes ) {
		return LDB_Most_Liked::render(
			array(
				'number'         => isset( $attributes['number'] ) ? (int) $attributes['number'] : 5,
				'post_type'      => isset( $attributes['postType'] ) ? $attributes['postType'] : 'post',
				'period'         => isset( $attributes['period'] ) ? $attributes['period'] : 'all',
				'show_count'     => ! empty( $attributes['showCount'] ),
				'show_thumbnail' => ! empty( $attributes['showThumbnail'] ),
			),
			get_block_wrapper_attributes( array( 'class' => 'ldb-most-liked' ) )
		);
	}
}
