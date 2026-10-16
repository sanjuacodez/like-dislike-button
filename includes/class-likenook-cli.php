<?php
/**
 * WP-CLI commands.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage likes and dislikes.
 */
class Likenook_CLI {

	/**
	 * Shows vote totals for a period.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Days to look back. 0 for all time.
	 * ---
	 * default: 30
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp likenook stats --days=7
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Options.
	 */
	public function stats( $args, $assoc_args ) {
		$days   = (int) $assoc_args['days'];
		$totals = Likenook_Stats::totals( $days );
		WP_CLI::log( sprintf( 'Likes: %d', $totals['likes'] ) );
		WP_CLI::log( sprintf( 'Dislikes: %d', $totals['dislikes'] ) );

		$rows = array();
		foreach ( Likenook_Stats::top( $days, 'like', 10 ) as $row ) {
			$rows[] = array(
				'ID'       => $row->id,
				'title'    => get_the_title( (int) $row->id ),
				'likes'    => $row->likes,
				'dislikes' => $row->dislikes,
			);
		}
		if ( $rows ) {
			WP_CLI\Utils\format_items( 'table', $rows, array( 'ID', 'title', 'likes', 'dislikes' ) );
		}
	}

	/**
	 * Recounts every total from the votes table and imported totals.
	 *
	 * ## EXAMPLES
	 *
	 *     wp likenook recount
	 */
	public function recount() {
		foreach ( Likenook_Votes::object_types() as $type ) {
			$after = 0;
			do {
				$after = Likenook_Votes::recount_batch( $type, $after );
			} while ( $after );
		}
		WP_CLI::success( 'Totals recounted.' );
	}

	/**
	 * Deletes votes.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<id>]
	 * : Only reset this post.
	 *
	 * [--yes]
	 * : Skip the confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp likenook reset --post=42
	 *     wp likenook reset --yes
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Options.
	 */
	public function reset( $args, $assoc_args ) {
		if ( ! empty( $assoc_args['post'] ) ) {
			Likenook_Votes::reset( 'post', (int) $assoc_args['post'] );
			WP_CLI::success( 'Votes for that post were deleted.' );
			return;
		}
		WP_CLI::confirm( 'Delete every like and dislike on this site?', $assoc_args );
		Likenook_Votes::reset();
		WP_CLI::success( 'All votes were deleted.' );
	}

	/**
	 * Imports votes from another plugin.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : wp-ulike, like-dislike-for-wp, posts-like-dislike or likenook-2.
	 *
	 * ## EXAMPLES
	 *
	 *     wp likenook import wp-ulike
	 *
	 * @param array $args Positional arguments.
	 */
	public function import( $args ) {
		$cursor = array();
		$total  = 0;
		do {
			$result = Likenook_Importer::run_batch( $args[0], $cursor );
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
			}
			$total += $result['processed'];
			$cursor = $result['cursor'];
			WP_CLI::log( sprintf( 'Processed %d…', $total ) );
		} while ( ! $result['done'] );

		Likenook_Importer::finish();
		WP_CLI::success( sprintf( 'Imported %d records.', $total ) );
	}

	/**
	 * Lists plugins that votes can be imported from.
	 *
	 * ## EXAMPLES
	 *
	 *     wp likenook sources
	 *
	 * @subcommand sources
	 */
	public function sources() {
		$rows = array();
		foreach ( Likenook_Importer::sources() as $slug => $source ) {
			$rows[] = array(
				'source' => $slug,
				'name'   => $source['label'],
				'found'  => $source['count'],
			);
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( 'source', 'name', 'found' ) );
	}
}
