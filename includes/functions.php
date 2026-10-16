<?php
/**
 * Template tags for themes.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the buttons for a post.
 *
 * @param int   $post_id Post ID, defaults to the current post.
 * @param array $args    Overrides: icon_set, style, size, align, layout, counts,
 *                       show_labels, ratio_bar, animation, question, thanks.
 * @return string
 */
function likenook_get_buttons( $post_id = 0, $args = array() ) {
	$post_id = $post_id ? (int) $post_id : Likenook_Render::current_post_id();
	return $post_id ? Likenook_Render::render( 'post', $post_id, $args ) : '';
}

/**
 * Prints the buttons for a post.
 *
 * @param int   $post_id Post ID, defaults to the current post.
 * @param array $args    See likenook_get_buttons().
 */
function likenook_buttons( $post_id = 0, $args = array() ) {
	echo likenook_get_buttons( $post_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
}

/**
 * Like and dislike totals for a post or comment.
 *
 * @param int    $id   Item ID, defaults to the current post.
 * @param string $type post|comment.
 * @return array{likes:int,dislikes:int}
 */
function likenook_get_counts( $id = 0, $type = 'post' ) {
	$id = $id ? (int) $id : Likenook_Render::current_post_id();
	return Likenook_Votes::counts( 'comment' === $type ? 'comment' : 'post', $id );
}
