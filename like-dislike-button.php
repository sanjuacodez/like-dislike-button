<?php
/**
 * Plugin Name: Like / Dislike Button
 * Plugin URI: https://sanjayshankar.me
 * Description: Advanced voting system with Elementor & Gutenberg support
 * Version: 2.1.0
 * Author: Sanjay Shankar
 * Author URI: https://sanjayshankar.me
 * Text Domain: like-dislike-button
 * Domain Path: /languages
 */

 defined('ABSPATH') || exit;

 // Define plugin constants
 define('LD_BUTTON_VERSION', '2.1.0');
 define('LD_BUTTON_PATH', plugin_dir_path(__FILE__));
 define('LD_BUTTON_URL', plugin_dir_url(__FILE__));
 define('LD_BUTTON_NONCE_KEY', 'ldb_nonce_2025');
 
 // Load core files
 require_once LD_BUTTON_PATH . 'includes/Core/class-plugin-core.php';
 require_once LD_BUTTON_PATH . 'includes/Core/class-plugin-loader.php';
 
 // Initialize plugin
 function ld_button_init() {
     $plugin = Like_Dislike_Core::instance();
     $plugin->run();
 }
 add_action('plugins_loaded', 'ld_button_init');
