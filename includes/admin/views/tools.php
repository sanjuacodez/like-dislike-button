<?php
/**
 * Tools screen: import, recount, export and reset.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

$ldb_sources = LDB_Importer::sources();
$ldb_notice  = isset( $_GET['ldb_notice'] ) ? sanitize_key( wp_unslash( $_GET['ldb_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
?>
<div class="wrap ldb-admin">
	<?php LDB_Admin::header( 'ldb-tools' ); ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Like Dislike Tools', 'like-dislike-button' ); ?></h1>

	<?php if ( 'reset' === $ldb_notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Votes deleted.', 'like-dislike-button' ); ?></p></div>
	<?php endif; ?>

	<div class="ldb-card">
		<h2 class="ldb-card__title"><?php esc_html_e( 'Import from another plugin', 'like-dislike-button' ); ?></h2>
		<p class="ldb-card__intro"><?php esc_html_e( 'Bring your existing likes over. Vote-by-vote imports keep dates and let logged-in users see their earlier votes. The other plugin can be active or inactive; its data is only read, never changed. Running an import again updates it rather than doubling it.', 'like-dislike-button' ); ?></p>

		<table class="ldb-table ldb-sources">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Plugin', 'like-dislike-button' ); ?></th>
					<th scope="col"><?php esc_html_e( 'What is imported', 'like-dislike-button' ); ?></th>
					<th scope="col" class="num"><?php esc_html_e( 'Found', 'like-dislike-button' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Action', 'like-dislike-button' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $ldb_sources as $ldb_slug => $ldb_source ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $ldb_source['label'] ); ?></strong></td>
						<td><?php echo 'rows' === $ldb_source['kind'] ? esc_html__( 'Every vote, with dates', 'like-dislike-button' ) : esc_html__( 'Totals per post', 'like-dislike-button' ); ?></td>
						<td class="num">
							<?php
							if ( $ldb_source['count'] ) {
								echo esc_html(
									'rows' === $ldb_source['kind']
										/* translators: %s: number of votes */
										? sprintf( _n( '%s vote', '%s votes', $ldb_source['count'], 'like-dislike-button' ), number_format_i18n( $ldb_source['count'] ) )
										/* translators: %s: number of posts */
										: sprintf( _n( '%s post', '%s posts', $ldb_source['count'], 'like-dislike-button' ), number_format_i18n( $ldb_source['count'] ) )
								);
							} else {
								echo '<span class="ldb-muted">' . esc_html__( 'Nothing found', 'like-dislike-button' ) . '</span>';
							}
							?>
						</td>
						<td class="ldb-sources__action">
							<button type="button" class="button ldb-import" data-source="<?php echo esc_attr( $ldb_slug ); ?>" <?php disabled( ! $ldb_source['count'] ); ?>><?php esc_html_e( 'Import', 'like-dislike-button' ); ?></button>
							<span class="ldb-progress" role="status" aria-live="polite"></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="ldb-help">
			<?php
			/* translators: %s: WP-CLI command */
			printf( esc_html__( 'Very large sites can import from the command line instead: %s', 'like-dislike-button' ), '<code>wp ldb import wp-ulike</code>' );
			?>
		</p>
	</div>

	<div class="ldb-columns">
		<div class="ldb-card">
			<h2 class="ldb-card__title"><?php esc_html_e( 'Recount totals', 'like-dislike-button' ); ?></h2>
			<p><?php esc_html_e( 'Rebuilds every like and dislike total from the stored votes. Useful after editing the database by hand.', 'like-dislike-button' ); ?></p>
			<p>
				<button type="button" class="button" id="ldb-recount"><?php esc_html_e( 'Recount now', 'like-dislike-button' ); ?></button>
				<span class="ldb-progress" role="status" aria-live="polite"></span>
			</p>
		</div>

		<div class="ldb-card">
			<h2 class="ldb-card__title"><?php esc_html_e( 'Export', 'like-dislike-button' ); ?></h2>
			<p><?php esc_html_e( 'Download a CSV of every post with votes: likes, dislikes, score and link.', 'like-dislike-button' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ldb_export' ), 'ldb_export' ) ); ?>"><?php esc_html_e( 'Download CSV', 'like-dislike-button' ); ?></a></p>
		</div>
	</div>

	<div class="ldb-card ldb-card--danger">
		<h2 class="ldb-card__title"><?php esc_html_e( 'Delete all votes', 'like-dislike-button' ); ?></h2>
		<p><?php esc_html_e( 'Removes every like and dislike on this site, including imported totals. Settings are kept. This can’t be undone, so export first if you might need the numbers.', 'like-dislike-button' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ldb-reset-form">
			<input type="hidden" name="action" value="ldb_reset_all">
			<?php wp_nonce_field( 'ldb_reset_all' ); ?>
			<button type="submit" class="button ldb-button-danger"><?php esc_html_e( 'Delete all votes', 'like-dislike-button' ); ?></button>
		</form>
	</div>
</div>
