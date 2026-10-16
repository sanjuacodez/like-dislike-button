<?php
/**
 * Blocks: Likenook Buttons and Most Liked Posts.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the blocks and renders them on the server.
 */
class Likenook_Blocks {

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
		register_block_type( LIKENOOK_PATH . 'blocks/buttons', array( 'render_callback' => array( __CLASS__, 'render_buttons' ) ) );
		register_block_type( LIKENOOK_PATH . 'blocks/most-liked', array( 'render_callback' => array( __CLASS__, 'render_most_liked' ) ) );

		$handle = generate_block_asset_handle( 'likenook/buttons', 'editorScript' );
		wp_add_inline_script( $handle, 'window.likenookBlockOptions = ' . wp_json_encode( self::editor_options() ) . ';', 'before' );

		foreach ( array( 'buttons', 'most-liked' ) as $block ) {
			wp_set_script_translations( generate_block_asset_handle( 'likenook/' . $block, 'editorScript' ), 'likenook' );
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
			'iconSets' => $options( wp_list_pluck( Likenook_Icons::sets(), 'label' ) ),
			'styles'   => $options( self::style_labels() ),
			'sizes'    => $options(
				array(
					'sm' => __( 'Small', 'likenook' ),
					'md' => __( 'Medium', 'likenook' ),
					'lg' => __( 'Large', 'likenook' ),
				)
			),
			'layouts'  => $options(
				array(
					'row'   => __( 'Side by side', 'likenook' ),
					'stack' => __( 'Stacked', 'likenook' ),
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
			'soft'    => __( 'Soft', 'likenook' ),
			'outline' => __( 'Outline', 'likenook' ),
			'pill'    => __( 'Pill', 'likenook' ),
			'minimal' => __( 'Minimal', 'likenook' ),
			'solid'   => __( 'Solid', 'likenook' ),
			'glass'   => __( 'Glass', 'likenook' ),
		);
	}

	/**
	 * Count display names.
	 *
	 * @return array
	 */
	public static function count_labels() {
		return array(
			'show'       => __( 'Show counts', 'likenook' ),
			'hide'       => __( 'Hide counts', 'likenook' ),
			'after_vote' => __( 'Show after voting', 'likenook' ),
			'percent'    => __( 'Percentages', 'likenook' ),
			'score'      => __( 'Score (likes minus dislikes)', 'likenook' ),
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
		// In a widget area the block context is just the last post listed; use the viewed post instead.
		if ( Likenook_Render::in_sidebar() || ! isset( $block->context['postId'] ) ) {
			$post_id = Likenook_Render::current_post_id();
		} else {
			$post_id = (int) $block->context['postId'];
		}
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

		return Likenook_Render::render( 'post', $post_id, $args );
	}

	/**
	 * Server render for the most liked block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_most_liked( $attributes ) {
		return Likenook_Most_Liked::render(
			array(
				'number'         => isset( $attributes['number'] ) ? (int) $attributes['number'] : 5,
				'post_type'      => isset( $attributes['postType'] ) ? $attributes['postType'] : 'post',
				'period'         => isset( $attributes['period'] ) ? $attributes['period'] : 'all',
				'show_count'     => ! empty( $attributes['showCount'] ),
				'show_thumbnail' => ! empty( $attributes['showThumbnail'] ),
			),
			get_block_wrapper_attributes( array( 'class' => 'likenook-most-liked' ) )
		);
	}
}
