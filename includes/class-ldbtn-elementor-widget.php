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
		$this->choice(
			'layout',
			esc_html__( 'Layout', 'like-dislike-button' ),
			array(
				'row'   => esc_html__( 'Side by side', 'like-dislike-button' ),
				'stack' => esc_html__( 'Stacked', 'like-dislike-button' ),
			)
		);
		$this->choice( 'counts', esc_html__( 'Counts', 'like-dislike-button' ), LDBTN_Blocks::count_labels() );
		$this->choice(
			'show_labels',
			esc_html__( 'Labels', 'like-dislike-button' ),
			array(
				'yes' => esc_html__( 'Show', 'like-dislike-button' ),
				'no'  => esc_html__( 'Hide', 'like-dislike-button' ),
			)
		);
		$this->choice(
			'ratio_bar',
			esc_html__( 'Like ratio bar', 'like-dislike-button' ),
			array(
				'yes' => esc_html__( 'Show', 'like-dislike-button' ),
				'no'  => esc_html__( 'Hide', 'like-dislike-button' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'text',
			array(
				'label' => esc_html__( 'Text', 'like-dislike-button' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		foreach (
			array(
				'like_label'    => esc_html__( 'Like label', 'like-dislike-button' ),
				'dislike_label' => esc_html__( 'Dislike label', 'like-dislike-button' ),
				'question'      => esc_html__( 'Question above the buttons', 'like-dislike-button' ),
				'thanks'        => esc_html__( 'Thank-you message', 'like-dislike-button' ),
			) as $key => $label
		) {
			$this->add_control(
				$key,
				array(
					'label'       => $label,
					'type'        => \Elementor\Controls_Manager::TEXT,
					'placeholder' => esc_html__( 'Use plugin setting', 'like-dislike-button' ),
				)
			);
		}
		$this->end_controls_section();

		$this->start_controls_section(
			'colors',
			array(
				'label' => esc_html__( 'Colours', 'like-dislike-button' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		foreach (
			array(
				'like_color'    => array( esc_html__( 'Like', 'like-dislike-button' ), '--ldbtn-like' ),
				'dislike_color' => array( esc_html__( 'Dislike', 'like-dislike-button' ), '--ldbtn-dislike' ),
				'text_color'    => array( esc_html__( 'Text', 'like-dislike-button' ), '--ldbtn-fg' ),
				'bg_color'      => array( esc_html__( 'Button background', 'like-dislike-button' ), '--ldbtn-bg' ),
			) as $key => $control
		) {
			$this->add_control(
				$key,
				array(
					'label'     => $control[0],
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .ldbtn.ldbtn' => $control[1] . ': {{VALUE}};' ),
				)
			);
		}
		$this->end_controls_section();
	}

	/**
	 * Front-end and editor output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$post_id  = ! empty( $settings['post_id'] ) ? absint( $settings['post_id'] ) : LDBTN_Render::current_post_id();
		if ( ! $post_id ) {
			return;
		}

		$args = array();
		foreach ( array( 'icon_set', 'style', 'size', 'align', 'layout', 'counts' ) as $key ) {
			$args[ $key ] = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		}
		foreach ( array( 'show_labels', 'ratio_bar' ) as $key ) {
			if ( ! empty( $settings[ $key ] ) ) {
				$args[ $key ] = 'yes' === $settings[ $key ];
			}
		}
		foreach ( array( 'like_label', 'dislike_label', 'question', 'thanks' ) as $key ) {
			if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
				$args[ $key ] = $settings[ $key ];
			}
		}
		// In the Elementor editor, clicks only preview the animation.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$counts           = LDBTN_Votes::counts( 'post', $post_id );
			$args['preview']  = true;
			$args['likes']    = $counts['likes'];
			$args['dislikes'] = $counts['dislikes'];
		}

		echo LDBTN_Render::render( 'post', $post_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
	}
}
