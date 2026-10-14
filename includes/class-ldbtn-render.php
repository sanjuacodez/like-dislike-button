<?php
/**
 * Front-end output: automatic placement, shortcodes and assets.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the buttons markup and puts it on the page.
 */
class LDBTN_Render {

	/**
	 * Settings that blocks, shortcodes and widgets may override per instance.
	 */
	const OVERRIDABLE = array( 'icon_set', 'style', 'size', 'align', 'layout', 'counts', 'show_labels', 'like_label', 'dislike_label', 'ratio_bar', 'animation', 'question', 'thanks' );

	/**
	 * Whether a sidebar (widget area) is being rendered.
	 *
	 * @var bool
	 */
	private static $in_sidebar = false;

	/**
	 * Hooks into WordPress.
	 */
	public static function init() {
		add_action( 'dynamic_sidebar_before', array( __CLASS__, 'enter_sidebar' ) );
		add_action( 'dynamic_sidebar_after', array( __CLASS__, 'leave_sidebar' ) );
		add_action( 'init', array( __CLASS__, 'register_assets' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), 20 );
		add_filter( 'comment_text', array( __CLASS__, 'filter_comment' ), 20, 2 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'product_summary' ), 35 );
	}

	/**
	 * Marks the start of a widget area.
	 */
	public static function enter_sidebar() {
		self::$in_sidebar = true;
	}

	/**
	 * Marks the end of a widget area.
	 */
	public static function leave_sidebar() {
		self::$in_sidebar = false;
	}

	/**
	 * Whether a widget area is being rendered.
	 *
	 * @return bool
	 */
	public static function in_sidebar() {
		return self::$in_sidebar;
	}

	/**
	 * The post that buttons placed without an ID belong to.
	 *
	 * Inside content, loops and templates that is the current post. In a
	 * sidebar it is the post being viewed, and nothing on blog and archive
	 * pages, where the "current" post would only be the last one listed.
	 *
	 * @return int Post ID, or 0.
	 */
	public static function current_post_id() {
		if ( self::$in_sidebar ) {
			$id = is_singular() ? (int) get_queried_object_id() : 0;
		} else {
			$id = (int) get_the_ID();
		}

		/**
		 * Filters which post buttons without an explicit ID belong to.
		 *
		 * @param int  $id         Post ID, or 0 for none.
		 * @param bool $in_sidebar Whether a widget area is being rendered.
		 */
		return (int) apply_filters( 'ldbtn_current_post_id', $id, self::$in_sidebar );
	}

	/**
	 * Registers the script and stylesheet used wherever buttons appear.
	 */
	public static function register_assets() {
		wp_register_style( 'ldbtn', LDBTN_URL . 'assets/css/ldbtn.css', array(), LDBTN_VERSION );
		wp_add_inline_style( 'ldbtn', self::inline_css() );

		wp_register_script( 'ldbtn', LDBTN_URL . 'assets/js/ldbtn.js', array(), LDBTN_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script(
			'ldbtn',
			'ldbtnConfig',
			array(
				'rest'        => esc_url_raw( rest_url( 'ldbtn/v1/' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'ajax'        => admin_url( 'admin-ajax.php' ),
				'allowChange' => (bool) LDBTN_Settings::get( 'allow_change' ),
				'short'       => (bool) LDBTN_Settings::get( 'short_numbers' ),
				'i18n'        => array(
					'error'   => __( 'Something went wrong. Please try again.', 'like-dislike-button' ),
					'voted'   => __( 'You have already voted.', 'like-dislike-button' ),
					/* translators: %s: login link */
					'login'   => __( 'Please %s to vote.', 'like-dislike-button' ),
					'logIn'   => __( 'log in', 'like-dislike-button' ),
					'removed' => __( 'Your vote was removed.', 'like-dislike-button' ),
				),
			)
		);
	}

	/**
	 * CSS custom properties for the colours chosen in settings.
	 *
	 * @param array $settings Settings to read, defaults to saved settings.
	 * @return string
	 */
	public static function inline_css( $settings = null ) {
		$vars = self::css_vars( $settings );
		return $vars ? '.ldbtn.ldbtn{' . $vars . '}' : '';
	}

	/**
	 * The colour custom properties alone, e.g. for a style attribute.
	 *
	 * @param array $settings Settings to read, defaults to saved settings.
	 * @return string
	 */
	public static function css_vars( $settings = null ) {
		$settings = $settings ? $settings : LDBTN_Settings::all();
		$map      = array(
			'like_color'    => '--ldbtn-like',
			'dislike_color' => '--ldbtn-dislike',
			'text_color'    => '--ldbtn-fg',
			'bg_color'      => '--ldbtn-bg',
		);
		$vars     = '';
		foreach ( $map as $key => $var ) {
			if ( ! empty( $settings[ $key ] ) ) {
				$vars .= $var . ':' . $settings[ $key ] . ';';
			}
		}
		return $vars;
	}

	/**
	 * Loads assets early on pages where buttons are likely, so styles land in the head.
	 */
	public static function maybe_enqueue() {
		$likely = is_singular() && ( LDBTN_Settings::type_enabled( get_post_type() ) || ( LDBTN_Settings::get( 'comments' ) && comments_open() ) );
		$likely = $likely || ( LDBTN_Settings::get( 'archives' ) && ( is_home() || is_archive() ) );
		if ( $likely ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueues the front-end assets.
	 */
	public static function enqueue() {
		wp_enqueue_style( 'ldbtn' );
		wp_enqueue_script( 'ldbtn' );
	}

	/**
	 * Whether automatic placement applies to the current post.
	 *
	 * @param WP_Post $post Post.
	 * @return bool
	 */
	private static function auto_applies( $post ) {
		if ( ! $post || 'manual' === LDBTN_Settings::get( 'position' ) || ! LDBTN_Settings::type_enabled( $post->post_type ) ) {
			return false;
		}
		if ( get_post_meta( $post->ID, LDBTN_Votes::META_HIDE, true ) || post_password_required( $post ) ) {
			return false;
		}
		// Placed by hand already, so don't add a second set.
		if ( has_block( 'like-dislike-button/buttons', $post ) || has_shortcode( $post->post_content, 'like_dislike_button' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Adds buttons before and/or after post content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function filter_content( $content ) {
		if ( is_admin() || is_feed() || wp_is_json_request() || ! in_the_loop() || doing_filter( 'get_the_excerpt' ) ) {
			return $content;
		}

		$post = get_post();
		if ( ! self::auto_applies( $post ) ) {
			return $content;
		}

		// WooCommerce products get buttons in the summary instead of the description tab.
		if ( 'product' === $post->post_type && function_exists( 'wc_get_product' ) ) {
			return $content;
		}

		$singular = is_singular() && is_main_query() && get_queried_object_id() === $post->ID;
		if ( ! $singular && ! ( LDBTN_Settings::get( 'archives' ) && ! is_singular() ) ) {
			return $content;
		}

		$buttons  = self::render( 'post', $post->ID );
		$position = LDBTN_Settings::get( 'position' );

		if ( 'before' === $position || 'both' === $position ) {
			$content = $buttons . $content;
		}
		if ( 'after' === $position || 'both' === $position ) {
			$content .= $buttons;
		}
		return $content;
	}

	/**
	 * Buttons in the WooCommerce product summary.
	 */
	public static function product_summary() {
		$post = get_post();
		if ( self::auto_applies( $post ) ) {
			echo self::render( 'post', $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
		}
	}

	/**
	 * Adds compact buttons under each approved comment.
	 *
	 * @param string          $text    Comment text.
	 * @param WP_Comment|null $comment Comment.
	 * @return string
	 */
	public static function filter_comment( $text, $comment = null ) {
		if ( is_admin() || is_feed() || ! $comment || ! LDBTN_Settings::get( 'comments' ) || '1' !== (string) $comment->comment_approved ) {
			return $text;
		}
		if ( wp_is_json_request() || doing_filter( 'get_comment_excerpt' ) ) {
			return $text;
		}
		return $text . self::render(
			'comment',
			(int) $comment->comment_ID,
			array(
				'size'      => 'sm',
				'question'  => '',
				'ratio_bar' => false,
			)
		);
	}

	/**
	 * Builds the buttons for one item.
	 *
	 * @param string $type post|comment.
	 * @param int    $id   Item ID.
	 * @param array  $args Per-instance overrides of look settings, plus
	 *                     `preview` (bool), `likes`/`dislikes` (for previews),
	 *                     `block` (bool, inside a block render), `class` and
	 *                     `style` (inline CSS for the wrapper).
	 * @return string
	 */
	public static function render( $type, $id, $args = array() ) {
		$settings = isset( $args['settings'] ) ? $args['settings'] : LDBTN_Settings::all();
		foreach ( self::OVERRIDABLE as $key ) {
			// Empty choices inherit the saved setting; an empty question or thank-you text clears it.
			if ( ! isset( $args[ $key ] ) || ( '' === $args[ $key ] && ! in_array( $key, array( 'question', 'thanks' ), true ) ) ) {
				continue;
			}
			$settings[ $key ] = $args[ $key ];
		}
		$settings = LDBTN_Settings::sanitize( $settings );
		$preview  = ! empty( $args['preview'] );

		if ( $preview ) {
			$counts = array(
				'likes'    => isset( $args['likes'] ) ? (int) $args['likes'] : 128,
				'dislikes' => isset( $args['dislikes'] ) ? (int) $args['dislikes'] : 14,
			);
		} else {
			$counts = LDBTN_Votes::counts( $type, $id );
			self::enqueue();
		}

		$counts_mode = $settings['counts'];
		$reactions   = 'both' === $settings['buttons'] ? array( 'like', 'dislike' ) : array( $settings['buttons'] );
		if ( 'dislike_first' === $settings['order'] && 'stack' !== $settings['layout'] ) {
			$reactions = array_reverse( $reactions );
		}
		if ( 'score' === $counts_mode && 1 === count( $reactions ) ) {
			$counts_mode = 'show';
		}
		$labels = $settings['show_labels'] || 'none' === $settings['icon_set'];

		$classes = array(
			'ldbtn',
			'ldbtn--' . $type,
			'ldbtn--style-' . $settings['style'],
			'ldbtn--size-' . $settings['size'],
			'ldbtn--align-' . $settings['align'],
			'ldbtn--layout-' . $settings['layout'],
			'ldbtn--icons-' . $settings['icon_set'],
			'ldbtn--counts-' . $counts_mode,
			'ldbtn--anim-' . $settings['animation'],
		);
		if ( $labels ) {
			$classes[] = 'ldbtn--labels';
		}
		if ( ! empty( $args['class'] ) ) {
			$classes[] = $args['class'];
		}

		$data = array(
			'data-ldbtn-type'     => $type,
			'data-ldbtn-id'       => $id,
			'data-ldbtn-likes'    => $counts['likes'],
			'data-ldbtn-dislikes' => $counts['dislikes'],
			'data-ldbtn-counts'   => $counts_mode,
		);
		if ( '' !== $settings['thanks'] ) {
			$data['data-ldbtn-thanks'] = $settings['thanks'];
		}
		if ( $preview ) {
			$data['data-ldbtn-preview'] = '1';
		}
		if ( 'logged_in' === $settings['who'] && ! is_user_logged_in() && ! $preview ) {
			$data['data-ldbtn-login'] = wp_login_url( self::current_url() );
		}

		if ( ! empty( $args['block'] ) ) {
			// Adds the block's colour, spacing and typography supports to the wrapper.
			$attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );
		} else {
			$attributes = 'class="' . esc_attr( implode( ' ', $classes ) ) . '"';
		}
		if ( ! empty( $args['style'] ) ) {
			$attributes .= ' style="' . esc_attr( $args['style'] ) . '"';
		}
		foreach ( $data as $name => $value ) {
			$attributes .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}

		$group_label = 'comment' === $type ? __( 'Rate this comment', 'like-dislike-button' ) : __( 'Rate this content', 'like-dislike-button' );

		$html = '<div ' . $attributes . '>';
		if ( '' !== $settings['question'] ) {
			$html .= '<p class="ldbtn-question">' . esc_html( $settings['question'] ) . '</p>';
		}
		$html .= '<div class="ldbtn-buttons" role="group" aria-label="' . esc_attr( '' !== $settings['question'] ? $settings['question'] : $group_label ) . '">';

		foreach ( $reactions as $index => $reaction ) {
			if ( 'score' === $counts_mode && 1 === $index ) {
				$html .= '<span class="ldbtn-score" aria-live="polite">' . esc_html( self::format_number( $counts['likes'] - $counts['dislikes'], $settings['short_numbers'] ) ) . '</span>';
			}
			$html .= self::button( $reaction, $counts, $counts_mode, $labels, $settings );
		}

		$html .= '</div>';

		if ( $settings['ratio_bar'] && 'both' === $settings['buttons'] ) {
			$total  = $counts['likes'] + $counts['dislikes'];
			$share  = $total ? round( $counts['likes'] / $total * 100, 1 ) : 0;
			$html  .= '<div class="ldbtn-bar" aria-hidden="true"><span style="width:' . esc_attr( $share ) . '%"></span></div>';
		}

		$html .= '<p class="ldbtn-msg" role="status" aria-live="polite"></p>';
		$html .= '</div>';

		/**
		 * Filters the buttons markup.
		 *
		 * @param string $html     Markup.
		 * @param string $type     post|comment.
		 * @param int    $id       Item ID.
		 * @param array  $settings Settings used, after overrides.
		 */
		return apply_filters( 'ldbtn_buttons_html', $html, $type, $id, $settings );
	}

	/**
	 * One button.
	 *
	 * @param string $reaction    like|dislike.
	 * @param array  $counts      Totals.
	 * @param string $counts_mode How counts are shown.
	 * @param bool   $labels      Whether labels are visible.
	 * @param array  $settings    Settings.
	 * @return string
	 */
	private static function button( $reaction, $counts, $counts_mode, $labels, $settings ) {
		$label = LDBTN_Settings::label( $reaction, $settings );
		$count = 'like' === $reaction ? $counts['likes'] : $counts['dislikes'];
		$total = $counts['likes'] + $counts['dislikes'];

		$text = '';
		if ( 'show' === $counts_mode ) {
			$text = self::format_number( $count, $settings['short_numbers'] );
		} elseif ( 'percent' === $counts_mode ) {
			$text = $total ? round( $count / $total * 100 ) . '%' : '';
		}

		$html  = '<button type="button" class="ldbtn-btn ldbtn-btn--' . esc_attr( $reaction ) . '" data-ldbtn-reaction="' . esc_attr( $reaction ) . '" aria-pressed="false">';
		$html .= LDBTN_Icons::get( $settings['icon_set'], $reaction, $settings );
		$html .= '<span class="' . ( $labels ? 'ldbtn-label' : 'ldbtn-sr' ) . '">' . esc_html( $label ) . '</span>';
		if ( in_array( $counts_mode, array( 'show', 'percent', 'after_vote' ), true ) ) {
			$html .= '<span class="ldbtn-count"' . ( 'after_vote' === $counts_mode ? ' hidden' : '' ) . '>' . esc_html( $text ) . '</span>';
		}
		$html .= '</button>';
		return $html;
	}

	/**
	 * Formats a count, optionally as 1.2K / 3.4M.
	 *
	 * @param int  $number Number.
	 * @param bool $short  Shorten large numbers.
	 * @return string
	 */
	public static function format_number( $number, $short = true ) {
		$number = (int) $number;
		$abs    = abs( $number );
		if ( $short && $abs >= 1000000 ) {
			return number_format_i18n( $number / 1000000, $abs >= 10000000 ? 0 : 1 ) . 'M';
		}
		if ( $short && $abs >= 1000 ) {
			return number_format_i18n( $number / 1000, $abs >= 10000 ? 0 : 1 ) . 'K';
		}
		return number_format_i18n( $number );
	}

	/**
	 * URL of the current page, for login redirects.
	 *
	 * @return string
	 */
	private static function current_url() {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		return home_url( $path );
	}

	/**
	 * Registers shortcodes, plus the old shortcodes of other plugins after an import.
	 */
	public static function register_shortcodes() {
		add_shortcode( 'like_dislike_button', array( __CLASS__, 'shortcode_buttons' ) );
		add_shortcode( 'ldbtn_most_liked', array( 'LDBTN_Most_Liked', 'shortcode' ) );

		if ( LDBTN_Settings::get( 'compat_shortcodes' ) ) {
			foreach ( array( 'posts_like_dislike', 'like_dislike', 'wp_ulike' ) as $tag ) {
				if ( ! shortcode_exists( $tag ) ) {
					add_shortcode( $tag, array( __CLASS__, 'shortcode_buttons' ) );
				}
			}
		}
	}

	/**
	 * [like_dislike_button id="" type="post|comment" icons="" style="" size="" align="" layout="" counts="" labels="yes|no" like_label="" dislike_label="" bar="yes|no" question="" thanks=""]
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode_buttons( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'            => 0,
				'type'          => 'post',
				'icons'         => '',
				'style'         => '',
				'size'          => '',
				'align'         => '',
				'layout'        => '',
				'counts'        => '',
				'labels'        => '',
				'like_label'    => '',
				'dislike_label' => '',
				'bar'           => '',
				'question'      => null,
				'thanks'        => null,
			),
			$atts,
			'like_dislike_button'
		);

		$type = 'comment' === $atts['type'] ? 'comment' : 'post';
		$id   = absint( $atts['id'] );
		if ( ! $id ) {
			$id = 'comment' === $type ? get_comment_ID() : self::current_post_id();
		}
		if ( ! $id ) {
			return '';
		}

		$args = array(
			'icon_set'      => $atts['icons'],
			'style'         => $atts['style'],
			'size'          => $atts['size'],
			'align'         => $atts['align'],
			'layout'        => $atts['layout'],
			'counts'        => $atts['counts'],
			'question'      => $atts['question'],
			'thanks'        => $atts['thanks'],
			'like_label'    => $atts['like_label'],
			'dislike_label' => $atts['dislike_label'],
		);
		if ( '' !== $atts['labels'] ) {
			$args['show_labels'] = 'yes' === $atts['labels'];
		}
		if ( '' !== $atts['bar'] ) {
			$args['ratio_bar'] = 'yes' === $atts['bar'];
		}

		return self::render( $type, (int) $id, $args );
	}
}
