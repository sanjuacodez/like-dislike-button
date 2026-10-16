<?php
/**
 * Elementor widget. Loaded only from the `elementor/widgets/register` hook.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Likenook Buttons widget for Elementor.
 */
class Likenook_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'likenook_buttons';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Likenook Buttons', 'likenook' );
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
		return array( 'likenook' );
	}

	/**
	 * Script handles the widget needs.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( 'likenook' );
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
				'options' => array( '' => esc_html__( 'Use plugin setting', 'likenook' ) ) + $options,
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
				'label' => esc_html__( 'Buttons', 'likenook' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'post_id',
			array(
				'label'       => esc_html__( 'Post ID', 'likenook' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'description' => esc_html__( 'Leave empty for the current post.', 'likenook' ),
			)
		);

		$this->choice( 'icon_set', esc_html__( 'Icons', 'likenook' ), wp_list_pluck( Likenook_Icons::sets(), 'label' ) );
		$this->choice( 'style', esc_html__( 'Button style', 'likenook' ), Likenook_Blocks::style_labels() );
		$this->choice(
			'size',
			esc_html__( 'Size', 'likenook' ),
			array(
				'sm' => esc_html__( 'Small', 'likenook' ),
				'md' => esc_html__( 'Medium', 'likenook' ),
				'lg' => esc_html__( 'Large', 'likenook' ),
			)
		);
		$this->choice(
			'align',
			esc_html__( 'Alignment', 'likenook' ),
			array(
				'left'   => esc_html__( 'Left', 'likenook' ),
				'center' => esc_html__( 'Center', 'likenook' ),
				'right'  => esc_html__( 'Right', 'likenook' ),
			)
		);
		$this->choice(
			'layout',
			esc_html__( 'Layout', 'likenook' ),
			array(
				'row'   => esc_html__( 'Side by side', 'likenook' ),
				'stack' => esc_html__( 'Stacked', 'likenook' ),
			)
		);
		$this->choice( 'counts', esc_html__( 'Counts', 'likenook' ), Likenook_Blocks::count_labels() );
		$this->choice(
			'show_labels',
			esc_html__( 'Labels', 'likenook' ),
			array(
				'yes' => esc_html__( 'Show', 'likenook' ),
				'no'  => esc_html__( 'Hide', 'likenook' ),
			)
		);
		$this->choice(
			'ratio_bar',
			esc_html__( 'Like ratio bar', 'likenook' ),
			array(
				'yes' => esc_html__( 'Show', 'likenook' ),
				'no'  => esc_html__( 'Hide', 'likenook' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'text',
			array(
				'label' => esc_html__( 'Text', 'likenook' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		foreach (
			array(
				'like_label'    => esc_html__( 'Like label', 'likenook' ),
				'dislike_label' => esc_html__( 'Dislike label', 'likenook' ),
				'question'      => esc_html__( 'Question above the buttons', 'likenook' ),
				'thanks'        => esc_html__( 'Thank-you message', 'likenook' ),
			) as $key => $label
		) {
			$this->add_control(
				$key,
				array(
					'label'       => $label,
					'type'        => \Elementor\Controls_Manager::TEXT,
					'placeholder' => esc_html__( 'Use plugin setting', 'likenook' ),
				)
			);
		}
		$this->end_controls_section();

		$this->start_controls_section(
			'colors',
			array(
				'label' => esc_html__( 'Colours', 'likenook' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		foreach (
			array(
				'like_color'    => array( esc_html__( 'Like', 'likenook' ), '--likenook-like' ),
				'dislike_color' => array( esc_html__( 'Dislike', 'likenook' ), '--likenook-dislike' ),
				'text_color'    => array( esc_html__( 'Text', 'likenook' ), '--likenook-fg' ),
				'bg_color'      => array( esc_html__( 'Button background', 'likenook' ), '--likenook-bg' ),
			) as $key => $control
		) {
			$this->add_control(
				$key,
				array(
					'label'     => $control[0],
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .likenook.likenook' => $control[1] . ': {{VALUE}};' ),
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
		$post_id  = ! empty( $settings['post_id'] ) ? absint( $settings['post_id'] ) : Likenook_Render::current_post_id();
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
			$counts           = Likenook_Votes::counts( 'post', $post_id );
			$args['preview']  = true;
			$args['likes']    = $counts['likes'];
			$args['dislikes'] = $counts['dislikes'];
		}

		echo Likenook_Render::render( 'post', $post_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
	}
}
