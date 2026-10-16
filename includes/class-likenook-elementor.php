<?php
/**
 * Elementor widget registration.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the widget only when Elementor is active.
 */
class Likenook_Elementor {

	/**
	 * Hooks into Elementor.
	 */
	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registers the widgets.
	 *
	 * @param \Elementor\Widgets_Manager $manager Widgets manager.
	 */
	public static function register( $manager ) {
		require_once LIKENOOK_PATH . 'includes/class-likenook-elementor-widget.php';
		require_once LIKENOOK_PATH . 'includes/class-likenook-elementor-most-liked.php';
		$manager->register( new Likenook_Elementor_Widget() );
		$manager->register( new Likenook_Elementor_Most_Liked() );
	}
}
