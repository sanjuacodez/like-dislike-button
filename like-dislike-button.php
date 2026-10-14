<?php
/**
 * Plugin Name:       Like Dislike Button
 * Plugin URI:        https://github.com/sanjuacodez/like-dislike-button
 * Description:       Like and dislike buttons for posts, pages, products and comments, with icon sets, styles, a block, most-liked lists and vote analytics. Works with page caching.
 * Version:           3.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Sanjay Shankar
 * Author URI:        https://sanjayshankar.me
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       like-dislike-button
 * Domain Path:       /languages
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

define( 'LDBTN_VERSION', '3.0.0' );
define( 'LDBTN_DB_VERSION', '1' );
define( 'LDBTN_FILE', __FILE__ );
define( 'LDBTN_PATH', plugin_dir_path( __FILE__ ) );
define( 'LDBTN_URL', plugin_dir_url( __FILE__ ) );

require_once LDBTN_PATH . 'includes/class-ldbtn-settings.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-db.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-icons.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-votes.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-render.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-most-liked.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-rest.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-blocks.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-stats.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-importer.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-privacy.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-schema.php';
require_once LDBTN_PATH . 'includes/class-ldbtn-elementor.php';
require_once LDBTN_PATH . 'includes/functions.php';

if ( is_admin() ) {
	require_once LDBTN_PATH . 'includes/admin/class-ldbtn-admin.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once LDBTN_PATH . 'includes/class-ldbtn-cli.php';
}

register_activation_hook( __FILE__, array( 'LDBTN_DB', 'install' ) );

add_action( 'plugins_loaded', 'ldbtn_boot' );

/**
 * Wires every part of the plugin once WordPress and other plugins are loaded.
 */
function ldbtn_boot() {
	LDBTN_DB::maybe_upgrade();
	LDBTN_Render::init();
	LDBTN_Rest::init();
	LDBTN_Blocks::init();
	LDBTN_Privacy::init();
	LDBTN_Schema::init();
	LDBTN_Elementor::init();

	if ( is_admin() ) {
		LDBTN_Admin::init();
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::add_command( 'ldbtn', 'LDBTN_CLI' );
	}
}
