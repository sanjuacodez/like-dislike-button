<?php
/**
 * Admin screens: dashboard, settings, tools, post meta box and list columns.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Everything under the "Likenook" admin menu.
 */
class Likenook_Admin {

	const STATS_CAP = 'edit_others_posts';

	/**
	 * Admin page hook suffixes.
	 *
	 * @var string[]
	 */
	private static $pages = array();

	/**
	 * Hooks into WordPress.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_init', array( __CLASS__, 'list_columns' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( LIKENOOK_FILE ), array( __CLASS__, 'action_links' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_by_likes' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'dashboard_widget' ) );
		add_action( 'update_option_' . Likenook_Settings::OPTION, array( 'Likenook_Settings', 'flush' ) );

		add_action( 'wp_ajax_likenook_preview', array( __CLASS__, 'ajax_preview' ) );
		add_action( 'wp_ajax_likenook_import', array( __CLASS__, 'ajax_import' ) );
		add_action( 'wp_ajax_likenook_recount', array( __CLASS__, 'ajax_recount' ) );
		add_action( 'admin_post_likenook_reset_post', array( __CLASS__, 'reset_post' ) );
		add_action( 'admin_post_likenook_reset_all', array( __CLASS__, 'reset_all' ) );
		add_action( 'admin_post_likenook_export', array( __CLASS__, 'export_csv' ) );
	}

	/**
	 * Adds the menu and its pages.
	 */
	public static function menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/><path d="M7 10v12"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Menu icon data URI.

		self::$pages[] = add_menu_page( __( 'Likenook', 'likenook' ), __( 'Likenook', 'likenook' ), self::STATS_CAP, 'likenook', array( __CLASS__, 'page_dashboard' ), $icon, 26 );
		self::$pages[] = add_submenu_page( 'likenook', __( 'Likenook Dashboard', 'likenook' ), __( 'Dashboard', 'likenook' ), self::STATS_CAP, 'likenook', array( __CLASS__, 'page_dashboard' ) );
		self::$pages[] = add_submenu_page( 'likenook', __( 'Likenook Settings', 'likenook' ), __( 'Settings', 'likenook' ), 'manage_options', 'likenook-settings', array( __CLASS__, 'page_settings' ) );
		self::$pages[] = add_submenu_page( 'likenook', __( 'Likenook Tools', 'likenook' ), __( 'Tools', 'likenook' ), 'manage_options', 'likenook-tools', array( __CLASS__, 'page_tools' ) );
	}

	/**
	 * Registers the settings option.
	 */
	public static function register_setting() {
		register_setting(
			'likenook_settings',
			Likenook_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'Likenook_Settings', 'sanitize' ),
				'default'           => Likenook_Settings::defaults(),
			)
		);
	}

	/**
	 * Settings and dashboard links on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=likenook-settings' ) ) . '">' . esc_html__( 'Settings', 'likenook' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Loads admin assets on plugin screens.
	 *
	 * @param string $hook Current screen hook.
	 */
	public static function assets( $hook ) {
		if ( 'edit.php' === $hook && Likenook_Settings::type_enabled( get_current_screen()->post_type ) ) {
			wp_register_style( 'likenook-list', false, array(), LIKENOOK_VERSION );
			wp_enqueue_style( 'likenook-list' );
			wp_add_inline_style( 'likenook-list', '.column-likenook_votes{width:8.5em}.likenook-col{display:inline-flex;gap:.6em;font-variant-numeric:tabular-nums}.likenook-col .dashicons{font-size:16px;width:16px;height:16px;vertical-align:text-bottom;color:#8c8f94}' );
		}
		if ( ! in_array( $hook, self::$pages, true ) ) {
			return;
		}

		wp_enqueue_style( 'likenook' );
		wp_enqueue_style( 'likenook-admin', LIKENOOK_URL . 'assets/css/admin.css', array( 'wp-color-picker' ), LIKENOOK_VERSION );

		$deps = array( 'jquery', 'wp-color-picker' );
		wp_enqueue_script( 'likenook' );
		if ( false !== strpos( $hook, 'likenook-settings' ) ) {
			wp_enqueue_media();
			// The preview brings its own colours from the unsaved form, so drop the saved ones.
			wp_styles()->registered['likenook']->extra['after'] = array();
		}
		wp_enqueue_script( 'likenook-admin', LIKENOOK_URL . 'assets/js/admin.js', $deps, LIKENOOK_VERSION, true );
		wp_localize_script(
			'likenook-admin',
			'likenookAdmin',
			array(
				'ajax'    => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'likenook_admin' ),
				'presets' => self::presets(),
				'i18n'    => array(
					'choose'       => __( 'Choose an image', 'likenook' ),
					'use'          => __( 'Use this image', 'likenook' ),
					/* translators: %d: number of records processed so far */
					'importing'    => __( 'Importing… %d processed', 'likenook' ),
					/* translators: %d: number of records imported */
					'imported'     => __( 'Done. Imported: %d', 'likenook' ),
					'recounting'   => __( 'Recounting…', 'likenook' ),
					'recounted'    => __( 'All totals are up to date.', 'likenook' ),
					'error'        => __( 'Something went wrong. Please try again.', 'likenook' ),
					'confirmReset' => __( 'Delete every like and dislike on this site? This can’t be undone.', 'likenook' ),
					'copied'       => __( 'Copied', 'likenook' ),
					'likes'        => __( 'Likes', 'likenook' ),
					'dislikes'     => __( 'Dislikes', 'likenook' ),
				),
			)
		);
	}

	/**
	 * One-click presets for the Appearance tab.
	 *
	 * @return array
	 */
	public static function presets() {
		$base = array(
			'icon_set'      => 'thumbs',
			'style'         => 'soft',
			'layout'        => 'row',
			'buttons'       => 'both',
			'counts'        => 'show',
			'show_labels'   => false,
			'like_label'    => '',
			'dislike_label' => '',
			'ratio_bar'     => false,
			'animation'     => 'pop',
			'question'      => '',
			'thanks'        => '',
		);
		return array(
			'classic' => array(
				'label'  => __( 'Like / dislike', 'likenook' ),
				'values' => $base,
			),
			'helpful' => array(
				'label'  => __( 'Was this helpful?', 'likenook' ),
				'values' => array_merge(
					$base,
					array(
						'style'         => 'outline',
						'show_labels'   => true,
						'like_label'    => __( 'Yes', 'likenook' ),
						'dislike_label' => __( 'No', 'likenook' ),
						'counts'        => 'hide',
						'question'      => __( 'Was this article helpful?', 'likenook' ),
						'thanks'        => __( 'Thanks for your feedback!', 'likenook' ),
					)
				),
			),
			'hearts'  => array(
				'label'  => __( 'Hearts', 'likenook' ),
				'values' => array_merge(
					$base,
					array(
						'icon_set'  => 'hearts',
						'style'     => 'minimal',
						'buttons'   => 'like',
						'animation' => 'burst',
					)
				),
			),
			'updown'  => array(
				'label'  => __( 'Upvote / downvote', 'likenook' ),
				'values' => array_merge(
					$base,
					array(
						'icon_set'  => 'arrows',
						'style'     => 'minimal',
						'layout'    => 'stack',
						'counts'    => 'score',
						'animation' => 'bounce',
					)
				),
			),
			'emoji'   => array(
				'label'  => __( 'Emoji', 'likenook' ),
				'values' => array_merge(
					$base,
					array(
						'icon_set' => 'emoji',
						'style'    => 'pill',
					)
				),
			),
			'ratio'   => array(
				'label'  => __( 'Rating bar', 'likenook' ),
				'values' => array_merge(
					$base,
					array(
						'style'     => 'outline',
						'counts'    => 'percent',
						'ratio_bar' => true,
					)
				),
			),
		);
	}

	/**
	 * Shared page header with navigation.
	 *
	 * @param string $current Current page slug.
	 */
	public static function header( $current ) {
		$tabs = array(
			'likenook'          => __( 'Dashboard', 'likenook' ),
			'likenook-settings' => __( 'Settings', 'likenook' ),
			'likenook-tools'    => __( 'Tools', 'likenook' ),
		);
		if ( ! current_user_can( 'manage_options' ) ) {
			$tabs = array_slice( $tabs, 0, 1, true );
		}
		?>
		<div class="likenook-topbar">
			<div class="likenook-topbar__brand">
				<span class="likenook-topbar__logo" aria-hidden="true"><?php echo Likenook_Icons::get( 'thumbs', 'like' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
				<span class="likenook-topbar__name"><?php esc_html_e( 'Likenook', 'likenook' ); ?></span>
				<span class="likenook-topbar__version"><?php echo esc_html( LIKENOOK_VERSION ); ?></span>
			</div>
			<nav class="likenook-topbar__nav" aria-label="<?php esc_attr_e( 'Likenook sections', 'likenook' ); ?>">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>" class="<?php echo $slug === $current ? 'is-current' : ''; ?>" <?php echo $slug === $current ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>
		<?php
	}

	/**
	 * Analytics page.
	 */
	public static function page_dashboard() {
		require LIKENOOK_PATH . 'includes/admin/views/dashboard.php';
	}

	/**
	 * Settings page.
	 */
	public static function page_settings() {
		require LIKENOOK_PATH . 'includes/admin/views/settings.php';
	}

	/**
	 * Tools page.
	 */
	public static function page_tools() {
		require LIKENOOK_PATH . 'includes/admin/views/tools.php';
	}

	/**
	 * Renders the preview for the settings form.
	 *
	 * @param array $settings Settings, possibly unsaved.
	 * @return string
	 */
	public static function preview_html( $settings ) {
		$settings = Likenook_Settings::sanitize( $settings );
		// Unsaved colours go on the preview itself as custom properties.
		return Likenook_Render::render(
			'post',
			0,
			array(
				'preview'  => true,
				'settings' => $settings,
				'style'    => Likenook_Render::css_vars( $settings ),
			)
		);
	}

	/**
	 * AJAX: preview for unsaved settings.
	 */
	public static function ajax_preview() {
		check_ajax_referer( 'likenook_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$raw = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Parsed and sanitized below.
		parse_str( (string) $raw, $parsed );
		$settings = isset( $parsed[ Likenook_Settings::OPTION ] ) ? $parsed[ Likenook_Settings::OPTION ] : array();
		wp_send_json_success( array( 'html' => self::preview_html( $settings ) ) );
	}

	/**
	 * AJAX: one import batch.
	 */
	public static function ajax_import() {
		check_ajax_referer( 'likenook_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$source = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : '';
		$cursor = array(
			'step'  => isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 0,
			'after' => isset( $_POST['after'] ) ? absint( $_POST['after'] ) : 0,
		);

		$result = Likenook_Importer::run_batch( $source, $cursor );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		if ( $result['done'] ) {
			Likenook_Importer::finish();
		}
		wp_send_json_success( $result );
	}

	/**
	 * AJAX: one recount batch.
	 */
	public static function ajax_recount() {
		check_ajax_referer( 'likenook_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$types = Likenook_Votes::object_types();
		$step  = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 0;
		$after = isset( $_POST['after'] ) ? absint( $_POST['after'] ) : 0;

		if ( ! isset( $types[ $step ] ) ) {
			wp_send_json_success( array( 'done' => true ) );
		}

		$next = Likenook_Votes::recount_batch( $types[ $step ], $after );
		wp_send_json_success(
			array(
				'done'  => ! $next && ! isset( $types[ $step + 1 ] ),
				'step'  => $next ? $step : $step + 1,
				'after' => $next,
			)
		);
	}

	/**
	 * Deletes all votes after confirmation.
	 */
	public static function reset_all() {
		check_admin_referer( 'likenook_reset_all' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'likenook' ) );
		}
		Likenook_Votes::reset();
		wp_safe_redirect( add_query_arg( 'likenook_notice', 'reset', admin_url( 'admin.php?page=likenook-tools' ) ) );
		exit;
	}

	/**
	 * Deletes votes for one post.
	 */
	public static function reset_post() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( 'likenook_reset_post_' . $post_id );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'likenook' ) );
		}
		Likenook_Votes::reset( 'post', $post_id );
		wp_safe_redirect( add_query_arg( 'likenook_notice', 'reset', wp_get_referer() ? wp_get_referer() : get_edit_post_link( $post_id, 'url' ) ) );
		exit;
	}

	/**
	 * Downloads per-post totals as CSV.
	 */
	public static function export_csv() {
		global $wpdb;
		check_admin_referer( 'likenook_export' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'likenook' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, p.post_type,
					CAST(COALESCE(l.meta_value, 0) AS UNSIGNED) AS likes,
					CAST(COALESCE(d.meta_value, 0) AS UNSIGNED) AS dislikes
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} l ON l.post_id = p.ID AND l.meta_key = %s
				LEFT JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key = %s
				WHERE l.meta_value IS NOT NULL OR d.meta_value IS NOT NULL
				ORDER BY likes DESC",
				Likenook_Votes::META_LIKES,
				Likenook_Votes::META_DISLIKES
			)
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=likes-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv( $out, array( 'ID', 'Title', 'Type', 'Likes', 'Dislikes', 'Score', 'URL' ) );
		foreach ( $rows as $row ) {
			// Prefix cells that spreadsheets would treat as formulas.
			$title = preg_match( '/^[=+\-@]/', $row->post_title ) ? "'" . $row->post_title : $row->post_title;
			fputcsv( $out, array( $row->ID, $title, $row->post_type, $row->likes, $row->dislikes, $row->likes - $row->dislikes, get_permalink( $row->ID ) ) );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Meta box on enabled post types.
	 */
	public static function meta_box() {
		foreach ( (array) Likenook_Settings::get( 'post_types' ) as $type ) {
			add_meta_box( 'likenook-votes', __( 'Likes & dislikes', 'likenook' ), array( __CLASS__, 'render_meta_box' ), $type, 'side', 'low' );
		}
	}

	/**
	 * Meta box content.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		$counts = Likenook_Votes::counts( 'post', $post->ID );
		$hidden = (bool) get_post_meta( $post->ID, Likenook_Votes::META_HIDE, true );
		wp_nonce_field( 'likenook_meta_box', 'likenook_meta_box_nonce' );
		?>
		<p class="likenook-metabox-counts">
			<?php
			printf(
				/* translators: 1: likes, 2: dislikes */
				esc_html__( '%1$s likes · %2$s dislikes', 'likenook' ),
				'<strong>' . esc_html( number_format_i18n( $counts['likes'] ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $counts['dislikes'] ) ) . '</strong>'
			);
			?>
		</p>
		<p>
			<label><input type="checkbox" name="likenook_hide" value="1" <?php checked( $hidden ); ?>> <?php esc_html_e( 'Hide the buttons on this post', 'likenook' ); ?></label>
		</p>
		<?php if ( $counts['likes'] || $counts['dislikes'] ) : ?>
			<p>
				<a class="likenook-confirm" data-confirm="<?php esc_attr_e( 'Delete all votes on this post?', 'likenook' ); ?>" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=likenook_reset_post&post=' . $post->ID ), 'likenook_reset_post_' . $post->ID ) ); ?>" onclick="return confirm(this.getAttribute('data-confirm'));"><?php esc_html_e( 'Reset votes', 'likenook' ); ?></a>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Saves the "hide buttons" checkbox.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['likenook_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['likenook_meta_box_nonce'] ) ), 'likenook_meta_box' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! empty( $_POST['likenook_hide'] ) ) {
			update_post_meta( $post_id, Likenook_Votes::META_HIDE, 1 );
		} else {
			delete_post_meta( $post_id, Likenook_Votes::META_HIDE );
		}
	}

	/**
	 * Adds a sortable "Votes" column to enabled post types.
	 */
	public static function list_columns() {
		foreach ( (array) Likenook_Settings::get( 'post_types' ) as $type ) {
			add_filter(
				"manage_{$type}_posts_columns",
				function ( $columns ) {
					$columns['likenook_votes'] = '<span class="likenook-col-head">' . esc_html__( 'Votes', 'likenook' ) . '</span>';
					return $columns;
				}
			);
			add_action( "manage_{$type}_posts_custom_column", array( __CLASS__, 'column_content' ), 10, 2 );
			add_filter(
				"manage_edit-{$type}_sortable_columns",
				function ( $columns ) {
					$columns['likenook_votes'] = array( 'likenook_likes', true );
					return $columns;
				}
			);
		}
	}

	/**
	 * Column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'likenook_votes' !== $column ) {
			return;
		}
		$counts = Likenook_Votes::counts( 'post', $post_id );
		printf(
			'<span class="likenook-col"><span title="%1$s"><span class="dashicons dashicons-thumbs-up" aria-hidden="true"></span> <span class="screen-reader-text">%1$s</span>%2$s</span><span title="%3$s"><span class="dashicons dashicons-thumbs-down" aria-hidden="true"></span> <span class="screen-reader-text">%3$s</span>%4$s</span></span>',
			esc_attr__( 'Likes', 'likenook' ),
			esc_html( Likenook_Render::format_number( $counts['likes'] ) ),
			esc_attr__( 'Dislikes', 'likenook' ),
			esc_html( Likenook_Render::format_number( $counts['dislikes'] ) )
		);
	}

	/**
	 * Sorts the posts list by likes, keeping posts without votes.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function sort_by_likes( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'likenook_likes' !== $query->get( 'orderby' ) ) {
			return;
		}
		add_filter(
			'posts_clauses',
			function ( $clauses, $current ) use ( $query ) {
				global $wpdb;
				if ( $current !== $query ) {
					return $clauses;
				}
				$order              = 'ASC' === strtoupper( (string) $query->get( 'order' ) ) ? 'ASC' : 'DESC';
				$clauses['join']   .= $wpdb->prepare( " LEFT JOIN {$wpdb->postmeta} likenook_m ON (likenook_m.post_id = {$wpdb->posts}.ID AND likenook_m.meta_key = %s)", Likenook_Votes::META_LIKES );
				$clauses['orderby'] = "CAST(COALESCE(likenook_m.meta_value, 0) AS SIGNED) {$order}, {$wpdb->posts}.post_date DESC";
				return $clauses;
			},
			10,
			2
		);
	}

	/**
	 * Dashboard widget with this week's votes.
	 */
	public static function dashboard_widget() {
		if ( current_user_can( self::STATS_CAP ) ) {
			wp_add_dashboard_widget( 'likenook_dashboard', __( 'Likes this week', 'likenook' ), array( __CLASS__, 'render_dashboard_widget' ) );
		}
	}

	/**
	 * Dashboard widget content.
	 */
	public static function render_dashboard_widget() {
		$totals = Likenook_Stats::totals( 7 );
		$top    = Likenook_Stats::top( 7, 'like', 5 );
		?>
		<div class="likenook-dw">
			<p style="display:flex;gap:2em;margin:0 0 1em">
				<span><strong style="font-size:1.6em;display:block"><?php echo esc_html( number_format_i18n( $totals['likes'] ) ); ?></strong><?php esc_html_e( 'Likes', 'likenook' ); ?></span>
				<span><strong style="font-size:1.6em;display:block"><?php echo esc_html( number_format_i18n( $totals['dislikes'] ) ); ?></strong><?php esc_html_e( 'Dislikes', 'likenook' ); ?></span>
			</p>
			<?php if ( $top ) : ?>
				<h3><?php esc_html_e( 'Most liked this week', 'likenook' ); ?></h3>
				<ul>
					<?php foreach ( $top as $row ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( (int) $row->id ) ); ?>"><?php echo esc_html( get_the_title( (int) $row->id ) ); ?></a> — <?php echo esc_html( number_format_i18n( (int) $row->likes ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p><?php esc_html_e( 'No votes in the last 7 days.', 'likenook' ); ?></p>
			<?php endif; ?>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=likenook' ) ); ?>"><?php esc_html_e( 'View all stats', 'likenook' ); ?> →</a></p>
		</div>
		<?php
	}
}
