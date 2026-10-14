<?php
/**
 * Demo content for the WordPress Playground "Live Preview".
 *
 * Runs inside Playground (see bin/build-blueprint): creates a small travel
 * blog, adds about two months of likes and dislikes with realistic dates, and
 * turns on comment buttons, so the preview opens on a populated dashboard and
 * every post shows buttons with real counts.
 *
 * Not shipped with the plugin. bin/build-blueprint puts the wp-load.php
 * require in front of this file when it embeds it, so WordPress is loaded
 * before the guard below.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the demo site.
 */
function ldbtn_blueprint_demo() {
	global $wp_rewrite, $wpdb;
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
	flush_rewrite_rules( false );

	$posts = array(
		array( 'Seven Days in Kyoto on a Budget', 'Temples, markets and where to eat well for under twenty dollars a day.', 120, 0.92 ),
		array( 'The Best Time to Visit Iceland', 'Northern lights in winter or midnight sun in summer? A month-by-month guide.', 95, 0.88 ),
		array( 'How I Pack for Two Weeks in a Carry-On', 'A packing list that fits in seven kilos, tested on four continents.', 80, 0.81 ),
		array( 'Is Travel Insurance Worth It?', 'What it covers, what it never covers, and when to skip it.', 64, 0.55 ),
		array( 'Lisbon Neighbourhoods, Ranked', 'Alfama, Baixa, Bairro Alto and more, for first-time visitors.', 58, 0.74 ),
		array( 'Night Trains Are Back in Europe', 'Routes, prices and what a sleeper cabin is really like.', 40, 0.9 ),
		array( 'Why I Stopped Using Booking Apps', 'Booking direct is often cheaper, and here is how to do it.', 30, 0.38 ),
	);

	wp_delete_post( 1, true );
	wp_delete_post( 2, true );

	$table = LDBTN_DB::table();

	foreach ( $posts as $i => $post ) {
		$paragraphs = '';
		for ( $p = 0; $p < 4; $p++ ) {
			$paragraphs .= '<!-- wp:paragraph --><p>' . esc_html( $post[1] ) . ' Planning ahead pays off: book early, travel light and leave room in the schedule for the places you find by accident.</p><!-- /wp:paragraph -->';
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => $post[0],
				'post_content' => $paragraphs,
				'post_date'    => gmdate( 'Y-m-d H:i:s', time() - ( 70 + $i * 5 ) * DAY_IN_SECONDS ),
			)
		);

		// Votes spread over the last 60 days, a little busier towards today.
		for ( $v = 0; $v < $post[2]; $v++ ) {
			$age = (int) floor( 60 * pow( wp_rand( 0, 1000 ) / 1000, 1.4 ) );
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Seeds the plugin's own table with demo votes.
				$table,
				array(
					'object_type' => 'post',
					'object_id'   => $id,
					'reaction'    => wp_rand( 0, 1000 ) / 1000 < $post[3] ? 'like' : 'dislike',
					'voter_key'   => 'demo:' . $i . ':' . $v,
					'user_id'     => 0,
					'created_at'  => gmdate( 'Y-m-d H:i:s', time() - $age * DAY_IN_SECONDS - wp_rand( 0, 80000 ) ),
				)
			);
		}
		LDBTN_Votes::recount( 'post', $id );

		if ( 0 === $i ) {
			$comment = wp_insert_comment(
				array(
					'comment_post_ID'  => $id,
					'comment_author'   => 'Maya',
					'comment_content'  => 'Saved this for my trip in spring. The market tips are gold.',
					'comment_approved' => 1,
				)
			);
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Seeds the plugin's own table with a demo comment vote.
				$table,
				array(
					'object_type' => 'comment',
					'object_id'   => $comment,
					'reaction'    => 'like',
					'voter_key'   => 'demo:c',
					'user_id'     => 0,
					'created_at'  => gmdate( 'Y-m-d H:i:s' ),
				)
			);
			LDBTN_Votes::recount( 'comment', $comment );
		}
	}

	// A page showing a few of the styles, all voting on the first post.
	$first  = get_page_by_path( sanitize_title( $posts[0][0] ), OBJECT, 'post' );
	$styles = array(
		'Soft, thumbs'                    => '',
		'Outline, labels, like ratio bar' => 'style="outline" labels="yes" bar="yes"',
		'Pill, hearts, percentages'       => 'style="pill" icons="hearts" counts="percent"',
		'Solid, smileys, large'           => 'style="solid" icons="smileys" size="lg"',
		'Was this helpful?'               => 'style="outline" icons="checks" labels="yes" like_label="Yes" dislike_label="No" counts="hide" question="Was this article helpful?" thanks="Thanks for your feedback!"',
		'Minimal, up/down score'          => 'style="minimal" icons="arrows" layout="stack" counts="score"',
		'Emoji pill'                      => 'icons="emoji" style="pill"',
		'Hot or not'                      => 'icons="flames"',
	);
	$showcase = '';
	foreach ( $styles as $label => $atts ) {
		$showcase .= '<!-- wp:heading {"level":4} --><h4 class="wp-block-heading">' . esc_html( $label ) . '</h4><!-- /wp:heading -->';
		$showcase .= '<!-- wp:shortcode -->[like_dislike_button id="' . (int) $first->ID . '" ' . $atts . ']<!-- /wp:shortcode -->';
	}
	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Button styles',
			'post_name'    => 'button-styles',
			'post_content' => $showcase,
		)
	);

	// Buttons under comments too, and a Most Liked Posts list on the front page.
	LDBTN_Settings::update( array( 'comments' => true ) );
	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Most liked',
			'post_name'    => 'most-liked',
			'post_content' => '<!-- wp:like-dislike-button/most-liked {"number":5,"period":"month"} /-->',
		)
	);
}

ldbtn_blueprint_demo();
