<?php
/**
 * Elementor widget. Loaded only from the `elementor/widgets/register` hook.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * Like Dislike Buttons widget for Elementor.
 */
class LDBTN_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ldbtn_buttons';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Like Dislike Buttons', 'like-dislike-button' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-rating';
	}

	/**
	 * Widget categories.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'like', 'dislike', 'vote', 'reaction', 'helpful' );
	}

	/**
	 * Style handles the widget needs.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( 'ldbtn' );
	}

	/**
	 * Script handles the widget needs.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( 'ldbtn' );
	}

	/**
	 * Adds a choice control with a "use plugin setting" option.
	 *
	 * @param string $id      Control ID.
	 * @param string $label   Label.
	 * @param array  $options Choices.
	 */
	private function choice( $id, $label, $options ) {
		$this->add_control(
			$id,
			array(
				'label'   => $label,
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array( '' => esc_html__( 'Use plugin setting', 'like-dislike-button' ) ) + $options,
			)
		);
	}

	/**
	 * Widget controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content',
			array(
				'label' => esc_html__( 'Buttons', 'like-dislike-button' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'post_id',
			array(
				'label'       => esc_html__( 'Post ID', 'like-dislike-button' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'description' => esc_html__( 'Leave empty for the current post.', 'like-dislike-button' ),
			)
		);

		$this->choice( 'icon_set', esc_html__( 'Icons', 'like-dislike-button' ), wp_list_pluck( LDBTN_Icons::sets(), 'label' ) );
		$this->choice( 'style', esc_html__( 'Button style', 'like-dislike-button' ), LDBTN_Blocks::style_labels() );
		$this->choice(
			'size',
			esc_html__( 'Size', 'like-dislike-button' ),
			array(
				'sm' => esc_html__( 'Small', 'like-dislike-button' ),
				'md' => esc_html__( 'Medium', 'like-dislike-button' ),
				'lg' => esc_html__( 'Large', 'like-dislike-button' ),
			)
		);
		$this->choice(
			'align',
			esc_html__( 'Alignment', 'like-dislike-button' ),
			array(
				'left'   => esc_html__( 'Left', 'like-dislike-button' ),
				'center' => esc_html__( 'Center', 'like-dislike-button' ),
				'right'  => esc_html__( 'Right', 'like-dislike-button' ),
			)
		);
		$this->choice( 'counts', esc_html__( 'Counts', 'like-dislike-button' ), LDBTN_Blocks::count_labels() );

		$this->end_controls_section();
	}

	/**
	 * Front-end and editor output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$post_id  = ! empty( $settings['post_id'] ) ? absint( $settings['post_id'] ) : get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$args = array();
		foreach ( array( 'icon_set', 'style', 'size', 'align', 'counts' ) as $key ) {
			$args[ $key ] = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		}

		echo LDBTN_Render::render( 'post', $post_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
	}
}
