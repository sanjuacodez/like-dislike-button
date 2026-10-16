<?php
/**
 * Classic widgets: Likenook Buttons and Most Liked Posts.
 *
 * For classic widget areas and builders that place WordPress widgets
 * (SiteOrigin Page Builder, Elementor's WordPress panel, Beaver Builder's
 * widget module). The block widget editor uses the blocks instead.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the widgets.
 */
class Likenook_Widgets {

	/**
	 * Hooks into WordPress.
	 */
	public static function init() {
		add_action( 'widgets_init', array( __CLASS__, 'register' ) );
		add_filter( 'widget_types_to_hide_from_legacy_widget_block', array( __CLASS__, 'hide_from_block_editor' ) );
	}

	/**
	 * Registers both widgets.
	 */
	public static function register() {
		register_widget( 'Likenook_Widget_Buttons' );
		register_widget( 'Likenook_Widget_Most_Liked' );
	}

	/**
	 * The block widget editor has blocks for both, so keep the classic
	 * versions out of its inserter, as core does for its own widgets.
	 *
	 * @param string[] $types Widget IDs to hide.
	 * @return string[]
	 */
	public static function hide_from_block_editor( $types ) {
		$types[] = 'likenook_buttons';
		$types[] = 'likenook_most_liked';
		return $types;
	}

	/**
	 * Prints a select field for a widget form.
	 *
	 * @param WP_Widget $widget  Widget.
	 * @param array     $values  Saved values.
	 * @param string    $key     Field name.
	 * @param string    $label   Label.
	 * @param array     $options Value => label.
	 */
	public static function select( $widget, $values, $key, $label, $options ) {
		$current = isset( $values[ $key ] ) ? $values[ $key ] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $widget->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $widget->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( $key ) ); ?>">
				<?php foreach ( $options as $value => $text ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) $current, (string) $value ); ?>><?php echo esc_html( $text ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Prints a text field for a widget form.
	 *
	 * @param WP_Widget $widget Widget.
	 * @param array     $values Saved values.
	 * @param string    $key    Field name.
	 * @param string    $label  Label.
	 * @param string    $type   Input type.
	 */
	public static function text( $widget, $values, $key, $label, $type = 'text' ) {
		$current = isset( $values[ $key ] ) ? $values[ $key ] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $widget->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
			<input class="widefat" type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $widget->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( $key ) ); ?>" value="<?php echo esc_attr( $current ); ?>" <?php echo 'number' === $type ? 'min="1" max="20" step="1"' : ''; ?>>
		</p>
		<?php
	}

	/**
	 * Prints a checkbox for a widget form.
	 *
	 * @param WP_Widget $widget Widget.
	 * @param array     $values Saved values.
	 * @param string    $key    Field name.
	 * @param string    $label  Label.
	 */
	public static function checkbox( $widget, $values, $key, $label ) {
		?>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $widget->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( $key ) ); ?>" value="1" <?php checked( ! empty( $values[ $key ] ) ); ?>>
			<label for="<?php echo esc_attr( $widget->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
		</p>
		<?php
	}

	/**
	 * "Use plugin setting" plus the given choices.
	 *
	 * @param array $options Value => label.
	 * @return array
	 */
	public static function inherit( $options ) {
		return array( '' => __( 'Use plugin setting', 'likenook' ) ) + $options;
	}
}

/**
 * Likenook Buttons widget: buttons for the post being viewed.
 */
class Likenook_Widget_Buttons extends WP_Widget {

	/**
	 * Sets up the widget.
	 */
	public function __construct() {
		parent::__construct(
			'likenook_buttons',
			__( 'Likenook Buttons', 'likenook' ),
			array(
				'description'                 => __( 'Like and dislike buttons for the post being viewed. Hidden on blog and archive pages.', 'likenook' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Sidebar arguments.
	 * @param array $instance Saved values.
	 */
	public function widget( $args, $instance ) {
		$post_id = Likenook_Render::current_post_id();
		if ( ! $post_id ) {
			return;
		}

		$overrides = array();
		foreach ( array( 'icon_set', 'style', 'size', 'align', 'layout', 'counts' ) as $key ) {
			$overrides[ $key ] = isset( $instance[ $key ] ) ? $instance[ $key ] : '';
		}
		$overrides['question'] = isset( $instance['question'] ) ? $instance['question'] : '';

		$title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) : '';

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup from the theme's sidebar registration.
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup from the theme's sidebar registration.
		}
		echo Likenook_Render::render( 'post', $post_id, $overrides ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup from the theme's sidebar registration.
	}

	/**
	 * Settings form.
	 *
	 * @param array $instance Saved values.
	 * @return string
	 */
	public function form( $instance ) {
		Likenook_Widgets::text( $this, $instance, 'title', __( 'Title:', 'likenook' ) );
		Likenook_Widgets::text( $this, $instance, 'question', __( 'Question above the buttons (optional):', 'likenook' ) );
		Likenook_Widgets::select( $this, $instance, 'icon_set', __( 'Icons:', 'likenook' ), Likenook_Widgets::inherit( wp_list_pluck( Likenook_Icons::sets(), 'label' ) ) );
		Likenook_Widgets::select( $this, $instance, 'style', __( 'Button style:', 'likenook' ), Likenook_Widgets::inherit( Likenook_Blocks::style_labels() ) );
		Likenook_Widgets::select(
			$this,
			$instance,
			'size',
			__( 'Size:', 'likenook' ),
			Likenook_Widgets::inherit(
				array(
					'sm' => __( 'Small', 'likenook' ),
					'md' => __( 'Medium', 'likenook' ),
					'lg' => __( 'Large', 'likenook' ),
				)
			)
		);
		Likenook_Widgets::select(
			$this,
			$instance,
			'align',
			__( 'Alignment:', 'likenook' ),
			Likenook_Widgets::inherit(
				array(
					'left'   => __( 'Left', 'likenook' ),
					'center' => __( 'Center', 'likenook' ),
					'right'  => __( 'Right', 'likenook' ),
				)
			)
		);
		Likenook_Widgets::select(
			$this,
			$instance,
			'layout',
			__( 'Layout:', 'likenook' ),
			Likenook_Widgets::inherit(
				array(
					'row'   => __( 'Side by side', 'likenook' ),
					'stack' => __( 'Stacked', 'likenook' ),
				)
			)
		);
		Likenook_Widgets::select( $this, $instance, 'counts', __( 'Counts:', 'likenook' ), Likenook_Widgets::inherit( Likenook_Blocks::count_labels() ) );
		return '';
	}

	/**
	 * Cleans saved values.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Previous values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$choices = Likenook_Settings::choices();
		$clean   = array(
			'title'    => sanitize_text_field( isset( $new_instance['title'] ) ? $new_instance['title'] : '' ),
			'question' => sanitize_text_field( isset( $new_instance['question'] ) ? $new_instance['question'] : '' ),
		);
		foreach ( array( 'icon_set', 'style', 'size', 'align', 'layout', 'counts' ) as $key ) {
			$value         = isset( $new_instance[ $key ] ) ? (string) $new_instance[ $key ] : '';
			$clean[ $key ] = in_array( $value, $choices[ $key ], true ) ? $value : '';
		}
		return $clean;
	}
}

/**
 * Most Liked Posts widget.
 */
class Likenook_Widget_Most_Liked extends WP_Widget {

	/**
	 * Sets up the widget.
	 */
	public function __construct() {
		parent::__construct(
			'likenook_most_liked',
			__( 'Most Liked Posts', 'likenook' ),
			array(
				'description'                 => __( 'Your most liked posts, all time or for a recent period.', 'likenook' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Sidebar arguments.
	 * @param array $instance Saved values.
	 */
	public function widget( $args, $instance ) {
		$instance = wp_parse_args(
			$instance,
			array(
				'title'          => __( 'Most liked', 'likenook' ),
				'number'         => 5,
				'post_type'      => 'post',
				'period'         => 'all',
				'show_count'     => 1,
				'show_thumbnail' => 0,
			)
		);

		$list = Likenook_Most_Liked::render(
			array(
				'number'         => (int) $instance['number'],
				'post_type'      => $instance['post_type'],
				'period'         => $instance['period'],
				'show_count'     => ! empty( $instance['show_count'] ),
				'show_thumbnail' => ! empty( $instance['show_thumbnail'] ),
			)
		);
		if ( '' === $list ) {
			return;
		}

		$title = $instance['title'] ? apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) : '';

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup from the theme's sidebar registration.
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup from the theme's sidebar registration.
		}
		echo $list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup from the theme's sidebar registration.
	}

	/**
	 * Settings form.
	 *
	 * @param array $instance Saved values.
	 * @return string
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			$instance,
			array(
				'title'      => __( 'Most liked', 'likenook' ),
				'number'     => 5,
				'post_type'  => 'post',
				'period'     => 'all',
				'show_count' => 1,
			)
		);
		$types    = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' !== $type->name ) {
				$types[ $type->name ] = $type->labels->name;
			}
		}

		Likenook_Widgets::text( $this, $instance, 'title', __( 'Title:', 'likenook' ) );
		Likenook_Widgets::text( $this, $instance, 'number', __( 'Number of posts:', 'likenook' ), 'number' );
		Likenook_Widgets::select( $this, $instance, 'post_type', __( 'Post type:', 'likenook' ), $types );
		Likenook_Widgets::select(
			$this,
			$instance,
			'period',
			__( 'Period:', 'likenook' ),
			array(
				'all'   => __( 'All time', 'likenook' ),
				'week'  => __( 'Last 7 days', 'likenook' ),
				'month' => __( 'Last 30 days', 'likenook' ),
				'year'  => __( 'Last 12 months', 'likenook' ),
			)
		);
		Likenook_Widgets::checkbox( $this, $instance, 'show_count', __( 'Show like count', 'likenook' ) );
		Likenook_Widgets::checkbox( $this, $instance, 'show_thumbnail', __( 'Show featured image', 'likenook' ) );
		return '';
	}

	/**
	 * Cleans saved values.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Previous values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$period = isset( $new_instance['period'] ) ? (string) $new_instance['period'] : 'all';
		$type   = isset( $new_instance['post_type'] ) ? sanitize_key( $new_instance['post_type'] ) : 'post';
		return array(
			'title'          => sanitize_text_field( isset( $new_instance['title'] ) ? $new_instance['title'] : '' ),
			'number'         => max( 1, min( 20, absint( isset( $new_instance['number'] ) ? $new_instance['number'] : 5 ) ) ),
			'post_type'      => post_type_exists( $type ) ? $type : 'post',
			'period'         => array_key_exists( $period, Likenook_Most_Liked::periods() ) ? $period : 'all',
			'show_count'     => empty( $new_instance['show_count'] ) ? 0 : 1,
			'show_thumbnail' => empty( $new_instance['show_thumbnail'] ) ? 0 : 1,
		);
	}
}
