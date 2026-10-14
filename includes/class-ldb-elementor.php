<?php
/**
 * Elementor widget registration.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the widget only when Elementor is active.
 */
class LDB_Elementor {

	/**
	 * Hooks into Elementor.
	 */
	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registers the widget.
	 *
	 * @param \Elementor\Widgets_Manager $manager Widgets manager.
	 */
	public static function register( $manager ) {
		require_once LDB_PATH . 'includes/class-ldb-elementor-widget.php';
		$manager->register( new LDB_Elementor_Widget() );
	}
}
