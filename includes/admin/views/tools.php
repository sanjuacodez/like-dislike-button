<?php
/**
 * Tools screen: import, recount, export and reset.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

$likenook_sources = Likenook_Importer::sources();
$likenook_notice  = isset( $_GET['likenook_notice'] ) ? sanitize_key( wp_unslash( $_GET['likenook_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
?>
<div class="wrap likenook-admin">
	<?php Likenook_Admin::header( 'likenook-tools' ); ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Likenook Tools', 'likenook' ); ?></h1>

	<?php if ( 'reset' === $likenook_notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Votes deleted.', 'likenook' ); ?></p></div>
	<?php endif; ?>

	<div class="likenook-card">
		<h2 class="likenook-card__title"><?php esc_html_e( 'Import from another plugin', 'likenook' ); ?></h2>
		<p class="likenook-card__intro"><?php esc_html_e( 'Bring your existing likes over. Vote-by-vote imports keep dates and let logged-in users see their earlier votes. The other plugin can be active or inactive; its data is only read, never changed. Running an import again updates it rather than doubling it.', 'likenook' ); ?></p>

		<table class="likenook-table likenook-sources">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Plugin', 'likenook' ); ?></th>
					<th scope="col"><?php esc_html_e( 'What is imported', 'likenook' ); ?></th>
					<th scope="col" class="num"><?php esc_html_e( 'Found', 'likenook' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Action', 'likenook' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $likenook_sources as $likenook_slug => $likenook_source ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $likenook_source['label'] ); ?></strong></td>
						<td><?php echo 'rows' === $likenook_source['kind'] ? esc_html__( 'Every vote, with dates', 'likenook' ) : esc_html__( 'Totals per post', 'likenook' ); ?></td>
						<td class="num">
							<?php
							if ( $likenook_source['count'] ) {
								echo esc_html(
									'rows' === $likenook_source['kind']
										/* translators: %s: number of votes */
										? sprintf( _n( '%s vote', '%s votes', $likenook_source['count'], 'likenook' ), number_format_i18n( $likenook_source['count'] ) )
										/* translators: %s: number of posts */
										: sprintf( _n( '%s post', '%s posts', $likenook_source['count'], 'likenook' ), number_format_i18n( $likenook_source['count'] ) )
								);
							} else {
								echo '<span class="likenook-muted">' . esc_html__( 'Nothing found', 'likenook' ) . '</span>';
							}
							?>
						</td>
						<td class="likenook-sources__action">
							<button type="button" class="button likenook-import" data-source="<?php echo esc_attr( $likenook_slug ); ?>" <?php disabled( ! $likenook_source['count'] ); ?>><?php esc_html_e( 'Import', 'likenook' ); ?></button>
							<span class="likenook-progress" role="status" aria-live="polite"></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="likenook-help">
			<?php
			/* translators: %s: WP-CLI command */
			printf( esc_html__( 'Very large sites can import from the command line instead: %s', 'likenook' ), '<code>wp likenook import wp-ulike</code>' );
			?>
		</p>
	</div>

	<div class="likenook-columns">
		<div class="likenook-card">
			<h2 class="likenook-card__title"><?php esc_html_e( 'Recount totals', 'likenook' ); ?></h2>
			<p><?php esc_html_e( 'Rebuilds every like and dislike total from the stored votes. Useful after editing the database by hand.', 'likenook' ); ?></p>
			<p>
				<button type="button" class="button" id="likenook-recount"><?php esc_html_e( 'Recount now', 'likenook' ); ?></button>
				<span class="likenook-progress" role="status" aria-live="polite"></span>
			</p>
		</div>

		<div class="likenook-card">
			<h2 class="likenook-card__title"><?php esc_html_e( 'Export', 'likenook' ); ?></h2>
			<p><?php esc_html_e( 'Download a CSV of every post with votes: likes, dislikes, score and link.', 'likenook' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=likenook_export' ), 'likenook_export' ) ); ?>"><?php esc_html_e( 'Download CSV', 'likenook' ); ?></a></p>
		</div>
	</div>

	<div class="likenook-card likenook-card--danger">
		<h2 class="likenook-card__title"><?php esc_html_e( 'Delete all votes', 'likenook' ); ?></h2>
		<p><?php esc_html_e( 'Removes every like and dislike on this site, including imported totals. Settings are kept. This can’t be undone, so export first if you might need the numbers.', 'likenook' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="likenook-reset-form">
			<input type="hidden" name="action" value="likenook_reset_all">
			<?php wp_nonce_field( 'likenook_reset_all' ); ?>
			<button type="submit" class="button likenook-button-danger"><?php esc_html_e( 'Delete all votes', 'likenook' ); ?></button>
		</form>
	</div>
</div>
