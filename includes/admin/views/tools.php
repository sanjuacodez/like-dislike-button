<?php
/**
 * Tools screen: import, recount, export and reset.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

$ldbtn_sources = LDBTN_Importer::sources();
$ldbtn_notice  = isset( $_GET['ldbtn_notice'] ) ? sanitize_key( wp_unslash( $_GET['ldbtn_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
?>
<div class="wrap ldbtn-admin">
	<?php LDBTN_Admin::header( 'ldbtn-tools' ); ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Like Dislike Tools', 'like-dislike-button' ); ?></h1>

	<?php if ( 'reset' === $ldbtn_notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Votes deleted.', 'like-dislike-button' ); ?></p></div>
	<?php endif; ?>

	<div class="ldbtn-card">
		<h2 class="ldbtn-card__title"><?php esc_html_e( 'Import from another plugin', 'like-dislike-button' ); ?></h2>
		<p class="ldbtn-card__intro"><?php esc_html_e( 'Bring your existing likes over. Vote-by-vote imports keep dates and let logged-in users see their earlier votes. The other plugin can be active or inactive; its data is only read, never changed. Running an import again updates it rather than doubling it.', 'like-dislike-button' ); ?></p>

		<table class="ldbtn-table ldbtn-sources">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Plugin', 'like-dislike-button' ); ?></th>
					<th scope="col"><?php esc_html_e( 'What is imported', 'like-dislike-button' ); ?></th>
					<th scope="col" class="num"><?php esc_html_e( 'Found', 'like-dislike-button' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Action', 'like-dislike-button' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $ldbtn_sources as $ldbtn_slug => $ldbtn_source ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $ldbtn_source['label'] ); ?></strong></td>
						<td><?php echo 'rows' === $ldbtn_source['kind'] ? esc_html__( 'Every vote, with dates', 'like-dislike-button' ) : esc_html__( 'Totals per post', 'like-dislike-button' ); ?></td>
						<td class="num">
							<?php
							if ( $ldbtn_source['count'] ) {
								echo esc_html(
									'rows' === $ldbtn_source['kind']
										/* translators: %s: number of votes */
										? sprintf( _n( '%s vote', '%s votes', $ldbtn_source['count'], 'like-dislike-button' ), number_format_i18n( $ldbtn_source['count'] ) )
										/* translators: %s: number of posts */
										: sprintf( _n( '%s post', '%s posts', $ldbtn_source['count'], 'like-dislike-button' ), number_format_i18n( $ldbtn_source['count'] ) )
								);
							} else {
								echo '<span class="ldbtn-muted">' . esc_html__( 'Nothing found', 'like-dislike-button' ) . '</span>';
							}
							?>
						</td>
						<td class="ldbtn-sources__action">
							<button type="button" class="button ldbtn-import" data-source="<?php echo esc_attr( $ldbtn_slug ); ?>" <?php disabled( ! $ldbtn_source['count'] ); ?>><?php esc_html_e( 'Import', 'like-dislike-button' ); ?></button>
							<span class="ldbtn-progress" role="status" aria-live="polite"></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="ldbtn-help">
			<?php
			/* translators: %s: WP-CLI command */
			printf( esc_html__( 'Very large sites can import from the command line instead: %s', 'like-dislike-button' ), '<code>wp ldbtn import wp-ulike</code>' );
			?>
		</p>
	</div>

	<div class="ldbtn-columns">
		<div class="ldbtn-card">
			<h2 class="ldbtn-card__title"><?php esc_html_e( 'Recount totals', 'like-dislike-button' ); ?></h2>
			<p><?php esc_html_e( 'Rebuilds every like and dislike total from the stored votes. Useful after editing the database by hand.', 'like-dislike-button' ); ?></p>
			<p>
				<button type="button" class="button" id="ldbtn-recount"><?php esc_html_e( 'Recount now', 'like-dislike-button' ); ?></button>
				<span class="ldbtn-progress" role="status" aria-live="polite"></span>
			</p>
		</div>

		<div class="ldbtn-card">
			<h2 class="ldbtn-card__title"><?php esc_html_e( 'Export', 'like-dislike-button' ); ?></h2>
			<p><?php esc_html_e( 'Download a CSV of every post with votes: likes, dislikes, score and link.', 'like-dislike-button' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ldbtn_export' ), 'ldbtn_export' ) ); ?>"><?php esc_html_e( 'Download CSV', 'like-dislike-button' ); ?></a></p>
		</div>
	</div>

	<div class="ldbtn-card ldbtn-card--danger">
		<h2 class="ldbtn-card__title"><?php esc_html_e( 'Delete all votes', 'like-dislike-button' ); ?></h2>
		<p><?php esc_html_e( 'Removes every like and dislike on this site, including imported totals. Settings are kept. This can’t be undone, so export first if you might need the numbers.', 'like-dislike-button' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ldbtn-reset-form">
			<input type="hidden" name="action" value="ldbtn_reset_all">
			<?php wp_nonce_field( 'ldbtn_reset_all' ); ?>
			<button type="submit" class="button ldbtn-button-danger"><?php esc_html_e( 'Delete all votes', 'like-dislike-button' ); ?></button>
		</form>
	</div>
</div>
