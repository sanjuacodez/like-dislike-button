<?php
/**
 * Template tags for themes.
 *
 * @package LikeDislikeButton
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
function ldbtn_get_buttons( $post_id = 0, $args = array() ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	return $post_id ? LDBTN_Render::render( 'post', $post_id, $args ) : '';
}

/**
 * Prints the buttons for a post.
 *
 * @param int   $post_id Post ID, defaults to the current post.
 * @param array $args    See ldbtn_get_buttons().
 */
function ldbtn_buttons( $post_id = 0, $args = array() ) {
	echo ldbtn_get_buttons( $post_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
}

/**
 * Like and dislike totals for a post or comment.
 *
 * @param int    $id   Item ID, defaults to the current post.
 * @param string $type post|comment.
 * @return array{likes:int,dislikes:int}
 */
function ldbtn_get_counts( $id = 0, $type = 'post' ) {
	$id = $id ? (int) $id : (int) get_the_ID();
	return LDBTN_Votes::counts( 'comment' === $type ? 'comment' : 'post', $id );
}
