<?php
/**
 * Plugin Name:       Likenook – Like Dislike Buttons
 * Plugin URI:        https://github.com/sanjuacodez/likenook
 * Description:       Like and dislike buttons for posts, pages, products and comments, with icon sets, styles, a block, most-liked lists and vote analytics. Works with page caching.
 * Version:           3.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Sanjay Shankar
 * Author URI:        https://sanjayshankar.me
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       likenook
 * Domain Path:       /languages
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

define( 'LIKENOOK_VERSION', '3.0.0' );
define( 'LIKENOOK_DB_VERSION', '1' );
define( 'LIKENOOK_FILE', __FILE__ );
define( 'LIKENOOK_PATH', plugin_dir_path( __FILE__ ) );
define( 'LIKENOOK_URL', plugin_dir_url( __FILE__ ) );

require_once LIKENOOK_PATH . 'includes/class-likenook-settings.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-db.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-icons.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-votes.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-render.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-most-liked.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-rest.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-blocks.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-stats.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-importer.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-privacy.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-schema.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-widgets.php';
require_once LIKENOOK_PATH . 'includes/class-likenook-elementor.php';
require_once LIKENOOK_PATH . 'includes/functions.php';

if ( is_admin() ) {
	require_once LIKENOOK_PATH . 'includes/admin/class-likenook-admin.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once LIKENOOK_PATH . 'includes/class-likenook-cli.php';
}

register_activation_hook( __FILE__, array( 'Likenook_DB', 'install' ) );

add_action( 'plugins_loaded', 'likenook_boot' );

/**
 * Wires every part of the plugin once WordPress and other plugins are loaded.
 */
function likenook_boot() {
	Likenook_DB::maybe_upgrade();
	Likenook_Render::init();
	Likenook_Rest::init();
	Likenook_Blocks::init();
	Likenook_Privacy::init();
	Likenook_Schema::init();
	Likenook_Widgets::init();
	Likenook_Elementor::init();

	if ( is_admin() ) {
		Likenook_Admin::init();
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::add_command( 'likenook', 'Likenook_CLI' );
	}
}
