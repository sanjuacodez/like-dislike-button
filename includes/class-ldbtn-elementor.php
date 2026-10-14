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
	 * Registers the widgets.
	 *
	 * @param \Elementor\Widgets_Manager $manager Widgets manager.
	 */
	public static function register( $manager ) {
		require_once LDBTN_PATH . 'includes/class-ldbtn-elementor-widget.php';
		require_once LDBTN_PATH . 'includes/class-ldbtn-elementor-most-liked.php';
		$manager->register( new LDBTN_Elementor_Widget() );
		$manager->register( new LDBTN_Elementor_Most_Liked() );
	}
}
