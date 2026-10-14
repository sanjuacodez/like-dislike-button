<?php
/**
 * Dashboard before the first vote: setup checklist, preview and what's coming.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

$ldb_s        = LDB_Settings::all();
$ldb_can_edit = current_user_can( 'manage_options' );
$ldb_types    = array_values( array_filter( (array) $ldb_s['post_types'], 'post_type_exists' ) );
$ldb_manual   = 'manual' === $ldb_s['position'];
$ldb_live     = $ldb_types && ! $ldb_manual;

$ldb_type_names = array();
foreach ( $ldb_types as $ldb_type ) {
	$ldb_type_names[] = get_post_type_object( $ldb_type )->labels->name;
}

// A published post that shows the buttons, to try them out.
$ldb_sample     = $ldb_types ? get_posts(
	array(
		'post_type'   => $ldb_types,
		'numberposts' => 1,
		'post_status' => 'publish',
		'has_password' => false,
	)
) : array();
$ldb_sample_url = $ldb_sample ? get_permalink( $ldb_sample[0] ) : '';

// Another like plugin with data to bring over.
$ldb_import = null;
foreach ( LDB_Importer::sources() as $ldb_slug => $ldb_source ) {
	if ( $ldb_source['count'] && 'ldb-2' !== $ldb_slug ) {
		$ldb_import = $ldb_source;
		break;
	}
}

$ldb_steps = array(
	array(
		'done'  => $ldb_live || $ldb_manual,
		'title' => $ldb_live
			/* translators: %s: list of post types, e.g. "Posts and Pages" */
			? sprintf( __( 'Buttons are showing on %s', 'like-dislike-button' ), wp_sprintf_l( '%l', $ldb_type_names ) )
			: ( $ldb_manual ? __( 'You’re placing buttons by hand', 'like-dislike-button' ) : __( 'Choose where buttons appear', 'like-dislike-button' ) ),
		'text'  => $ldb_live
			? __( 'They appear after the content automatically. Add pages, products or comments any time.', 'like-dislike-button' )
			: __( 'Pick post types for automatic placement, or use the block and shortcode.', 'like-dislike-button' ),
		'link'  => $ldb_can_edit ? admin_url( 'admin.php?page=ldb-settings#placement' ) : '',
		'label' => $ldb_live ? __( 'Change', 'like-dislike-button' ) : __( 'Set up', 'like-dislike-button' ),
	),
	array(
		'done'  => false !== get_option( LDB_Settings::OPTION, false ),
		'title' => __( 'Make them match your site', 'like-dislike-button' ),
		'text'  => __( 'Pick from 9 icon sets and 6 styles, or start from a preset such as “Was this helpful?”.', 'like-dislike-button' ),
		'link'  => $ldb_can_edit ? admin_url( 'admin.php?page=ldb-settings' ) : '',
		'label' => __( 'Customize', 'like-dislike-button' ),
	),
);
if ( $ldb_import ) {
	$ldb_steps[] = array(
		'done'  => false,
		/* translators: %s: plugin name */
		'title' => sprintf( __( 'Bring over your votes from %s', 'like-dislike-button' ), $ldb_import['label'] ),
		/* translators: %s: number of votes or posts */
		'text'  => sprintf( __( 'We found %s records. Importing keeps dates and never changes the other plugin’s data.', 'like-dislike-button' ), number_format_i18n( $ldb_import['count'] ) ),
		'link'  => $ldb_can_edit ? admin_url( 'admin.php?page=ldb-tools' ) : '',
		'label' => __( 'Import', 'like-dislike-button' ),
	);
}
$ldb_steps[] = array(
	'done'     => false,
	'title'    => __( 'Get your first vote', 'like-dislike-button' ),
	'text'     => __( 'Open a post and click Like to see it work. Test votes can be reset from the post editor.', 'like-dislike-button' ),
	'link'     => $ldb_sample_url,
	'label'    => __( 'Open a post', 'like-dislike-button' ),
	'external' => true,
);

$ldb_done  = count( wp_list_filter( $ldb_steps, array( 'done' => true ) ) );
$ldb_total = count( $ldb_steps );

$ldb_check = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 6 9 17l-5-5"/></svg>';
$ldb_arrow = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 17 17 7M8 7h9v9"/></svg>';
?>
<section class="ldb-hero" aria-labelledby="ldb-hero-title">
	<div class="ldb-hero__body">
		<p class="ldb-hero__eyebrow"><?php esc_html_e( 'Getting started', 'like-dislike-button' ); ?></p>
		<h2 class="ldb-hero__title" id="ldb-hero-title">
			<?php echo $ldb_live ? esc_html__( 'Your buttons are live. Let’s get the first vote.', 'like-dislike-button' ) : esc_html__( 'Let’s put your buttons on the page.', 'like-dislike-button' ); ?>
		</h2>
		<p class="ldb-hero__text"><?php esc_html_e( 'Once visitors start voting, this page shows likes over time, your most and least liked posts, and how each period compares with the last.', 'like-dislike-button' ); ?></p>
		<div class="ldb-hero__actions">
			<?php if ( $ldb_can_edit ) : ?>
				<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=ldb-settings' ) ); ?>"><?php esc_html_e( 'Customize buttons', 'like-dislike-button' ); ?></a>
			<?php endif; ?>
			<?php if ( $ldb_sample_url ) : ?>
				<a class="button button-hero ldb-button-ghost" href="<?php echo esc_url( $ldb_sample_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'See them on your site', 'like-dislike-button' ); ?>
					<?php echo $ldb_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'like-dislike-button' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="ldb-hero__preview">
		<div class="ldb-mock" aria-label="<?php esc_attr_e( 'Preview of your buttons. Click to try them; nothing is recorded.', 'like-dislike-button' ); ?>" role="group">
			<div class="ldb-mock__bar" aria-hidden="true"><i></i><i></i><i></i></div>
			<div class="ldb-mock__page">
				<span class="ldb-mock__title" aria-hidden="true"></span>
				<span class="ldb-mock__line" aria-hidden="true"></span>
				<span class="ldb-mock__line" aria-hidden="true"></span>
				<span class="ldb-mock__line is-short" aria-hidden="true"></span>
				<?php
				echo LDB_Render::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
					'post',
					0,
					array(
						'preview'  => true,
						'likes'    => 24,
						'dislikes' => 3,
					)
				);
				?>
			</div>
		</div>
		<p class="ldb-hero__hint"><?php esc_html_e( 'Your current style. Click to try it.', 'like-dislike-button' ); ?></p>
	</div>
</section>

<div class="ldb-onboard">
	<section class="ldb-card ldb-steps" aria-labelledby="ldb-steps-title">
		<div class="ldb-steps__head">
			<h2 class="ldb-card__title" id="ldb-steps-title"><?php esc_html_e( 'Setup', 'like-dislike-button' ); ?></h2>
			<span class="ldb-steps__count">
				<?php
				/* translators: 1: steps done, 2: total steps */
				echo esc_html( sprintf( __( '%1$d of %2$d done', 'like-dislike-button' ), $ldb_done, $ldb_total ) );
				?>
			</span>
		</div>
		<div class="ldb-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $ldb_total ); ?>" aria-valuenow="<?php echo esc_attr( $ldb_done ); ?>" aria-labelledby="ldb-steps-title">
			<span style="width:<?php echo esc_attr( round( $ldb_done / $ldb_total * 100 ) ); ?>%"></span>
		</div>

		<ol class="ldb-steps__list">
			<?php foreach ( $ldb_steps as $ldb_i => $ldb_step ) : ?>
				<li class="ldb-step <?php echo $ldb_step['done'] ? 'is-done' : ''; ?>">
					<span class="ldb-step__mark" aria-hidden="true"><?php echo $ldb_step['done'] ? $ldb_check : esc_html( $ldb_i + 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
					<div class="ldb-step__body">
						<h3 class="ldb-step__title">
							<?php echo esc_html( $ldb_step['title'] ); ?>
							<span class="screen-reader-text"><?php echo $ldb_step['done'] ? esc_html__( '(done)', 'like-dislike-button' ) : esc_html__( '(to do)', 'like-dislike-button' ); ?></span>
						</h3>
						<p class="ldb-step__text"><?php echo esc_html( $ldb_step['text'] ); ?></p>
					</div>
					<?php if ( $ldb_step['link'] ) : ?>
						<a class="button ldb-step__action" href="<?php echo esc_url( $ldb_step['link'] ); ?>" <?php echo ! empty( $ldb_step['external'] ) ? 'target="_blank" rel="noopener"' : ''; ?>>
							<?php echo esc_html( $ldb_step['label'] ); ?>
							<?php if ( ! empty( $ldb_step['external'] ) ) : ?>
								<?php echo $ldb_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
								<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'like-dislike-button' ); ?></span>
							<?php endif; ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>

	<section class="ldb-card ldb-places" aria-labelledby="ldb-places-title">
		<h2 class="ldb-card__title" id="ldb-places-title"><?php esc_html_e( 'More ways to show them', 'like-dislike-button' ); ?></h2>
		<ul class="ldb-places__list">
			<li>
				<span class="ldb-places__icon dashicons dashicons-block-default" aria-hidden="true"></span>
				<div>
					<strong><?php esc_html_e( 'Blocks', 'like-dislike-button' ); ?></strong>
					<p><?php esc_html_e( 'Search “Like Dislike” in the block inserter. Works inside Query Loop and the site editor.', 'like-dislike-button' ); ?></p>
				</div>
			</li>
			<li>
				<span class="ldb-places__icon dashicons dashicons-shortcode" aria-hidden="true"></span>
				<div>
					<strong><?php esc_html_e( 'Shortcode', 'like-dislike-button' ); ?></strong>
					<p class="ldb-copy">
						<code>[like_dislike_button]</code>
						<button type="button" class="button-link ldb-copy__btn" data-copy="[like_dislike_button]"><?php esc_html_e( 'Copy', 'like-dislike-button' ); ?></button>
					</p>
				</div>
			</li>
			<li>
				<span class="ldb-places__icon dashicons dashicons-star-filled" aria-hidden="true"></span>
				<div>
					<strong><?php esc_html_e( 'Most liked posts', 'like-dislike-button' ); ?></strong>
					<p class="ldb-copy">
						<code>[ldb_most_liked]</code>
						<button type="button" class="button-link ldb-copy__btn" data-copy="[ldb_most_liked]"><?php esc_html_e( 'Copy', 'like-dislike-button' ); ?></button>
					</p>
				</div>
			</li>
		</ul>
	</section>
</div>

<section class="ldb-ghost" aria-labelledby="ldb-ghost-title">
	<h2 class="ldb-ghost__title" id="ldb-ghost-title"><?php esc_html_e( 'What you’ll see here', 'like-dislike-button' ); ?></h2>
	<div class="ldb-ghost__frame" aria-hidden="true">
		<div class="ldb-stats">
			<?php foreach ( array( __( 'Likes', 'like-dislike-button' ), __( 'Dislikes', 'like-dislike-button' ), __( 'Total votes', 'like-dislike-button' ), __( 'Like ratio', 'like-dislike-button' ) ) as $ldb_i => $ldb_label ) : ?>
				<div class="ldb-stat">
					<span class="ldb-stat__label">
						<?php if ( $ldb_i < 2 ) : ?>
							<span class="ldb-key ldb-key--<?php echo 0 === $ldb_i ? 'like' : 'dislike'; ?>"></span>
						<?php endif; ?>
						<?php echo esc_html( $ldb_label ); ?>
					</span>
					<span class="ldb-stat__value">—</span>
					<span class="ldb-ghost__line"></span>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="ldb-card ldb-ghost__chart">
			<div class="ldb-ghost__bars">
				<?php
				// A gentle, fixed shape: placeholder only.
				foreach ( array( 30, 42, 36, 55, 48, 62, 40, 58, 70, 52, 66, 78, 60, 72, 84, 68, 76, 90, 74, 82 ) as $ldb_height ) {
					echo '<i style="height:' . (int) $ldb_height . '%"></i>';
				}
				?>
			</div>
		</div>
	</div>
	<p class="ldb-ghost__note"><?php esc_html_e( 'Your chart and top posts appear here after the first vote.', 'like-dislike-button' ); ?></p>
</section>
