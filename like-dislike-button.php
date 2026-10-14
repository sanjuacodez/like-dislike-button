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

define( 'LDB_VERSION', '3.0.0' );
define( 'LDB_DB_VERSION', '1' );
define( 'LDB_FILE', __FILE__ );
define( 'LDB_PATH', plugin_dir_path( __FILE__ ) );
define( 'LDB_URL', plugin_dir_url( __FILE__ ) );

require_once LDB_PATH . 'includes/class-ldb-settings.php';
require_once LDB_PATH . 'includes/class-ldb-db.php';
require_once LDB_PATH . 'includes/class-ldb-icons.php';
require_once LDB_PATH . 'includes/class-ldb-votes.php';
require_once LDB_PATH . 'includes/class-ldb-render.php';
require_once LDB_PATH . 'includes/class-ldb-most-liked.php';
require_once LDB_PATH . 'includes/class-ldb-rest.php';
require_once LDB_PATH . 'includes/class-ldb-blocks.php';
require_once LDB_PATH . 'includes/class-ldb-stats.php';
require_once LDB_PATH . 'includes/class-ldb-importer.php';
require_once LDB_PATH . 'includes/class-ldb-privacy.php';
require_once LDB_PATH . 'includes/class-ldb-schema.php';
require_once LDB_PATH . 'includes/class-ldb-elementor.php';
require_once LDB_PATH . 'includes/functions.php';

if ( is_admin() ) {
	require_once LDB_PATH . 'includes/admin/class-ldb-admin.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once LDB_PATH . 'includes/class-ldb-cli.php';
}

register_activation_hook( __FILE__, array( 'LDB_DB', 'install' ) );

add_action( 'plugins_loaded', 'ldb_boot' );

/**
 * Wires every part of the plugin once WordPress and other plugins are loaded.
 */
function ldb_boot() {
	LDB_DB::maybe_upgrade();
	LDB_Render::init();
	LDB_Rest::init();
	LDB_Blocks::init();
	LDB_Privacy::init();
	LDB_Schema::init();
	LDB_Elementor::init();

	if ( is_admin() ) {
		LDB_Admin::init();
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::add_command( 'ldb', 'LDB_CLI' );
	}
}
