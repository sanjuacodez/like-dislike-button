<?php
/**
 * Elementor widget: Most Liked Posts. Loaded only from the
 * `elementor/widgets/register` hook.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Most Liked Posts widget for Elementor.
 */
class Likenook_Elementor_Most_Liked extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'likenook_most_liked';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Most Liked Posts', 'likenook' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-favorite';
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
		return array( 'like', 'popular', 'top', 'most liked', 'ranking' );
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
	 * Widget controls.
	 */
	protected function register_controls() {
		$types = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' !== $type->name ) {
				$types[ $type->name ] = $type->labels->name;
			}
		}

		$this->start_controls_section(
			'content',
			array(
				'label' => esc_html__( 'Most liked posts', 'likenook' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'number',
			array(
				'label'   => esc_html__( 'Number of posts', 'likenook' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 20,
				'default' => 5,
			)
		);
		$this->add_control(
			'post_type',
			array(
				'label'   => esc_html__( 'Post type', 'likenook' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'post',
				'options' => $types,
			)
		);
		$this->add_control(
			'period',
			array(
				'label'   => esc_html__( 'Period', 'likenook' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'all',
				'options' => array(
					'all'   => esc_html__( 'All time', 'likenook' ),
					'week'  => esc_html__( 'Last 7 days', 'likenook' ),
					'month' => esc_html__( 'Last 30 days', 'likenook' ),
					'year'  => esc_html__( 'Last 12 months', 'likenook' ),
				),
			)
		);
		$this->add_control(
			'show_count',
			array(
				'label'   => esc_html__( 'Show like count', 'likenook' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);
		$this->add_control(
			'show_thumbnail',
			array(
				'label' => esc_html__( 'Show featured image', 'likenook' ),
				'type'  => \Elementor\Controls_Manager::SWITCHER,
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Front-end and editor output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$html     = Likenook_Most_Liked::render(
			array(
				'number'         => isset( $settings['number'] ) ? (int) $settings['number'] : 5,
				'post_type'      => isset( $settings['post_type'] ) ? $settings['post_type'] : 'post',
				'period'         => isset( $settings['period'] ) ? $settings['period'] : 'all',
				'show_count'     => isset( $settings['show_count'] ) && 'yes' === $settings['show_count'],
				'show_thumbnail' => isset( $settings['show_thumbnail'] ) && 'yes' === $settings['show_thumbnail'],
			)
		);

		if ( '' === $html && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$html = '<p>' . esc_html__( 'No liked posts yet for this post type and period. The list fills in as visitors vote.', 'likenook' ) . '</p>';
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
	}
}
