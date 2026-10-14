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
class LDBTN_Elementor {

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
		require_once LDBTN_PATH . 'includes/class-ldbtn-elementor-widget.php';
		$manager->register( new LDBTN_Elementor_Widget() );
	}
}
