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
class LDB_Render {

	/**
	 * Settings that blocks, shortcodes and widgets may override per instance.
	 */
	const OVERRIDABLE = array( 'icon_set', 'style', 'size', 'align', 'layout', 'counts', 'show_labels', 'like_label', 'dislike_label', 'ratio_bar', 'animation', 'question', 'thanks' );

	/**
	 * Hooks into WordPress.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_assets' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), 20 );
		add_filter( 'comment_text', array( __CLASS__, 'filter_comment' ), 20, 2 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'product_summary' ), 35 );
	}

	/**
	 * Registers the script and stylesheet used wherever buttons appear.
	 */
	public static function register_assets() {
		wp_register_style( 'ldb', LDB_URL . 'assets/css/ldb.css', array(), LDB_VERSION );
		wp_add_inline_style( 'ldb', self::inline_css() );

		wp_register_script( 'ldb', LDB_URL . 'assets/js/ldb.js', array(), LDB_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script(
			'ldb',
			'ldbConfig',
			array(
				'rest'        => esc_url_raw( rest_url( 'ldb/v1/' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'ajax'        => admin_url( 'admin-ajax.php' ),
				'allowChange' => (bool) LDB_Settings::get( 'allow_change' ),
				'short'       => (bool) LDB_Settings::get( 'short_numbers' ),
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
		$settings = $settings ? $settings : LDB_Settings::all();
		$map      = array(
			'like_color'    => '--ldb-like',
			'dislike_color' => '--ldb-dislike',
			'text_color'    => '--ldb-fg',
			'bg_color'      => '--ldb-bg',
		);
		$vars     = '';
		foreach ( $map as $key => $var ) {
			if ( ! empty( $settings[ $key ] ) ) {
				$vars .= $var . ':' . $settings[ $key ] . ';';
			}
		}
		return $vars ? '.ldb.ldb{' . $vars . '}' : '';
	}

	/**
	 * Loads assets early on pages where buttons are likely, so styles land in the head.
	 */
	public static function maybe_enqueue() {
		$likely = is_singular() && ( LDB_Settings::type_enabled( get_post_type() ) || ( LDB_Settings::get( 'comments' ) && comments_open() ) );
		$likely = $likely || ( LDB_Settings::get( 'archives' ) && ( is_home() || is_archive() ) );
		if ( $likely ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueues the front-end assets.
	 */
	public static function enqueue() {
		wp_enqueue_style( 'ldb' );
		wp_enqueue_script( 'ldb' );
	}

	/**
	 * Whether automatic placement applies to the current post.
	 *
	 * @param WP_Post $post Post.
	 * @return bool
	 */
	private static function auto_applies( $post ) {
		if ( ! $post || 'manual' === LDB_Settings::get( 'position' ) || ! LDB_Settings::type_enabled( $post->post_type ) ) {
			return false;
		}
		if ( get_post_meta( $post->ID, LDB_Votes::META_HIDE, true ) || post_password_required( $post ) ) {
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
		if ( ! $singular && ! ( LDB_Settings::get( 'archives' ) && ! is_singular() ) ) {
			return $content;
		}

		$buttons  = self::render( 'post', $post->ID );
		$position = LDB_Settings::get( 'position' );

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
		if ( is_admin() || is_feed() || ! $comment || ! LDB_Settings::get( 'comments' ) || '1' !== (string) $comment->comment_approved ) {
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
	 *                     `block` (bool, inside a block render) and `class`.
	 * @return string
	 */
	public static function render( $type, $id, $args = array() ) {
		$settings = isset( $args['settings'] ) ? $args['settings'] : LDB_Settings::all();
		foreach ( self::OVERRIDABLE as $key ) {
			// Empty choices inherit the saved setting; an empty question or thank-you text clears it.
			if ( ! isset( $args[ $key ] ) || ( '' === $args[ $key ] && ! in_array( $key, array( 'question', 'thanks' ), true ) ) ) {
				continue;
			}
			$settings[ $key ] = $args[ $key ];
		}
		$settings = LDB_Settings::sanitize( $settings );
		$preview  = ! empty( $args['preview'] );

		if ( $preview ) {
			$counts = array(
				'likes'    => isset( $args['likes'] ) ? (int) $args['likes'] : 128,
				'dislikes' => isset( $args['dislikes'] ) ? (int) $args['dislikes'] : 14,
			);
		} else {
			$counts = LDB_Votes::counts( $type, $id );
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
			'ldb',
			'ldb--' . $type,
			'ldb--style-' . $settings['style'],
			'ldb--size-' . $settings['size'],
			'ldb--align-' . $settings['align'],
			'ldb--layout-' . $settings['layout'],
			'ldb--icons-' . $settings['icon_set'],
			'ldb--counts-' . $counts_mode,
			'ldb--anim-' . $settings['animation'],
		);
		if ( $labels ) {
			$classes[] = 'ldb--labels';
		}
		if ( ! empty( $args['class'] ) ) {
			$classes[] = $args['class'];
		}

		$data = array(
			'data-ldb-type'     => $type,
			'data-ldb-id'       => $id,
			'data-ldb-likes'    => $counts['likes'],
			'data-ldb-dislikes' => $counts['dislikes'],
			'data-ldb-counts'   => $counts_mode,
		);
		if ( '' !== $settings['thanks'] ) {
			$data['data-ldb-thanks'] = $settings['thanks'];
		}
		if ( $preview ) {
			$data['data-ldb-preview'] = '1';
		}
		if ( 'logged_in' === $settings['who'] && ! is_user_logged_in() && ! $preview ) {
			$data['data-ldb-login'] = wp_login_url( self::current_url() );
		}

		if ( ! empty( $args['block'] ) ) {
			// Adds the block's colour, spacing and typography supports to the wrapper.
			$attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );
		} else {
			$attributes = 'class="' . esc_attr( implode( ' ', $classes ) ) . '"';
		}
		foreach ( $data as $name => $value ) {
			$attributes .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}

		$group_label = 'comment' === $type ? __( 'Rate this comment', 'like-dislike-button' ) : __( 'Rate this content', 'like-dislike-button' );

		$html = '<div ' . $attributes . '>';
		if ( '' !== $settings['question'] ) {
			$html .= '<p class="ldb-question">' . esc_html( $settings['question'] ) . '</p>';
		}
		$html .= '<div class="ldb-buttons" role="group" aria-label="' . esc_attr( '' !== $settings['question'] ? $settings['question'] : $group_label ) . '">';

		foreach ( $reactions as $index => $reaction ) {
			if ( 'score' === $counts_mode && 1 === $index ) {
				$html .= '<span class="ldb-score" aria-live="polite">' . esc_html( self::format_number( $counts['likes'] - $counts['dislikes'], $settings['short_numbers'] ) ) . '</span>';
			}
			$html .= self::button( $reaction, $counts, $counts_mode, $labels, $settings );
		}

		$html .= '</div>';

		if ( $settings['ratio_bar'] && 'both' === $settings['buttons'] ) {
			$total  = $counts['likes'] + $counts['dislikes'];
			$share  = $total ? round( $counts['likes'] / $total * 100, 1 ) : 0;
			$html  .= '<div class="ldb-bar" aria-hidden="true"><span style="width:' . esc_attr( $share ) . '%"></span></div>';
		}

		$html .= '<p class="ldb-msg" role="status" aria-live="polite"></p>';
		$html .= '</div>';

		/**
		 * Filters the buttons markup.
		 *
		 * @param string $html     Markup.
		 * @param string $type     post|comment.
		 * @param int    $id       Item ID.
		 * @param array  $settings Settings used, after overrides.
		 */
		return apply_filters( 'ldb_buttons_html', $html, $type, $id, $settings );
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
		$label = LDB_Settings::label( $reaction, $settings );
		$count = 'like' === $reaction ? $counts['likes'] : $counts['dislikes'];
		$total = $counts['likes'] + $counts['dislikes'];

		$text = '';
		if ( 'show' === $counts_mode ) {
			$text = self::format_number( $count, $settings['short_numbers'] );
		} elseif ( 'percent' === $counts_mode ) {
			$text = $total ? round( $count / $total * 100 ) . '%' : '';
		}

		$html  = '<button type="button" class="ldb-btn ldb-btn--' . esc_attr( $reaction ) . '" data-ldb-reaction="' . esc_attr( $reaction ) . '" aria-pressed="false">';
		$html .= LDB_Icons::get( $settings['icon_set'], $reaction, $settings );
		$html .= '<span class="' . ( $labels ? 'ldb-label' : 'ldb-sr' ) . '">' . esc_html( $label ) . '</span>';
		if ( in_array( $counts_mode, array( 'show', 'percent', 'after_vote' ), true ) ) {
			$html .= '<span class="ldb-count"' . ( 'after_vote' === $counts_mode ? ' hidden' : '' ) . '>' . esc_html( $text ) . '</span>';
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
		add_shortcode( 'ldb_most_liked', array( 'LDB_Most_Liked', 'shortcode' ) );

		if ( LDB_Settings::get( 'compat_shortcodes' ) ) {
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
			$id = 'comment' === $type ? get_comment_ID() : get_the_ID();
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
