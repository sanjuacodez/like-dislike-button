<?php
/**
 * Dashboard before the first vote: setup checklist, preview and what's coming.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

$ldbtn_s        = LDBTN_Settings::all();
$ldbtn_can_edit = current_user_can( 'manage_options' );
$ldbtn_types    = array_values( array_filter( (array) $ldbtn_s['post_types'], 'post_type_exists' ) );
$ldbtn_manual   = 'manual' === $ldbtn_s['position'];
$ldbtn_live     = $ldbtn_types && ! $ldbtn_manual;

$ldbtn_type_names = array();
foreach ( $ldbtn_types as $ldbtn_type ) {
	$ldbtn_type_names[] = get_post_type_object( $ldbtn_type )->labels->name;
}

// A published post that shows the buttons, to try them out.
$ldbtn_sample     = $ldbtn_types ? get_posts(
	array(
		'post_type'   => $ldbtn_types,
		'numberposts' => 1,
		'post_status' => 'publish',
		'has_password' => false,
	)
) : array();
$ldbtn_sample_url = $ldbtn_sample ? get_permalink( $ldbtn_sample[0] ) : '';

// Another like plugin with data to bring over.
$ldbtn_import = null;
foreach ( LDBTN_Importer::sources() as $ldbtn_slug => $ldbtn_source ) {
	if ( $ldbtn_source['count'] && 'ldbtn-2' !== $ldbtn_slug ) {
		$ldbtn_import = $ldbtn_source;
		break;
	}
}

$ldbtn_steps = array(
	array(
		'done'  => $ldbtn_live || $ldbtn_manual,
		'title' => $ldbtn_live
			/* translators: %s: list of post types, e.g. "Posts and Pages" */
			? sprintf( __( 'Buttons are showing on %s', 'like-dislike-button' ), wp_sprintf_l( '%l', $ldbtn_type_names ) )
			: ( $ldbtn_manual ? __( 'You’re placing buttons by hand', 'like-dislike-button' ) : __( 'Choose where buttons appear', 'like-dislike-button' ) ),
		'text'  => $ldbtn_live
			? __( 'They appear after the content automatically. Add pages, products or comments any time.', 'like-dislike-button' )
			: __( 'Pick post types for automatic placement, or use the block and shortcode.', 'like-dislike-button' ),
		'link'  => $ldbtn_can_edit ? admin_url( 'admin.php?page=ldbtn-settings#placement' ) : '',
		'label' => $ldbtn_live ? __( 'Change', 'like-dislike-button' ) : __( 'Set up', 'like-dislike-button' ),
	),
	array(
		'done'  => false !== get_option( LDBTN_Settings::OPTION, false ),
		'title' => __( 'Make them match your site', 'like-dislike-button' ),
		'text'  => __( 'Pick from 9 icon sets and 6 styles, or start from a preset such as “Was this helpful?”.', 'like-dislike-button' ),
		'link'  => $ldbtn_can_edit ? admin_url( 'admin.php?page=ldbtn-settings' ) : '',
		'label' => __( 'Customize', 'like-dislike-button' ),
	),
);
if ( $ldbtn_import ) {
	$ldbtn_steps[] = array(
		'done'  => false,
		/* translators: %s: plugin name */
		'title' => sprintf( __( 'Bring over your votes from %s', 'like-dislike-button' ), $ldbtn_import['label'] ),
		/* translators: %s: number of votes or posts */
		'text'  => sprintf( __( 'We found %s records. Importing keeps dates and never changes the other plugin’s data.', 'like-dislike-button' ), number_format_i18n( $ldbtn_import['count'] ) ),
		'link'  => $ldbtn_can_edit ? admin_url( 'admin.php?page=ldbtn-tools' ) : '',
		'label' => __( 'Import', 'like-dislike-button' ),
	);
}
$ldbtn_steps[] = array(
	'done'     => false,
	'title'    => __( 'Get your first vote', 'like-dislike-button' ),
	'text'     => __( 'Open a post and click Like to see it work. Test votes can be reset from the post editor.', 'like-dislike-button' ),
	'link'     => $ldbtn_sample_url,
	'label'    => __( 'Open a post', 'like-dislike-button' ),
	'external' => true,
);

$ldbtn_done  = count( wp_list_filter( $ldbtn_steps, array( 'done' => true ) ) );
$ldbtn_total = count( $ldbtn_steps );

$ldbtn_check = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 6 9 17l-5-5"/></svg>';
$ldbtn_arrow = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 17 17 7M8 7h9v9"/></svg>';
?>
<section class="ldbtn-hero" aria-labelledby="ldbtn-hero-title">
	<div class="ldbtn-hero__body">
		<p class="ldbtn-hero__eyebrow"><?php esc_html_e( 'Getting started', 'like-dislike-button' ); ?></p>
		<h2 class="ldbtn-hero__title" id="ldbtn-hero-title">
			<?php echo $ldbtn_live ? esc_html__( 'Your buttons are live. Let’s get the first vote.', 'like-dislike-button' ) : esc_html__( 'Let’s put your buttons on the page.', 'like-dislike-button' ); ?>
		</h2>
		<p class="ldbtn-hero__text"><?php esc_html_e( 'Once visitors start voting, this page shows likes over time, your most and least liked posts, and how each period compares with the last.', 'like-dislike-button' ); ?></p>
		<div class="ldbtn-hero__actions">
			<?php if ( $ldbtn_can_edit ) : ?>
				<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=ldbtn-settings' ) ); ?>"><?php esc_html_e( 'Customize buttons', 'like-dislike-button' ); ?></a>
			<?php endif; ?>
			<?php if ( $ldbtn_sample_url ) : ?>
				<a class="button button-hero ldbtn-button-ghost" href="<?php echo esc_url( $ldbtn_sample_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'See them on your site', 'like-dislike-button' ); ?>
					<?php echo $ldbtn_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'like-dislike-button' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="ldbtn-hero__preview">
		<div class="ldbtn-mock" aria-label="<?php esc_attr_e( 'Preview of your buttons. Click to try them; nothing is recorded.', 'like-dislike-button' ); ?>" role="group">
			<div class="ldbtn-mock__bar" aria-hidden="true"><i></i><i></i><i></i></div>
			<div class="ldbtn-mock__page">
				<span class="ldbtn-mock__title" aria-hidden="true"></span>
				<span class="ldbtn-mock__line" aria-hidden="true"></span>
				<span class="ldbtn-mock__line" aria-hidden="true"></span>
				<span class="ldbtn-mock__line is-short" aria-hidden="true"></span>
				<?php
				echo LDBTN_Render::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
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
		<p class="ldbtn-hero__hint"><?php esc_html_e( 'Your current style. Click to try it.', 'like-dislike-button' ); ?></p>
	</div>
</section>

<div class="ldbtn-onboard">
	<section class="ldbtn-card ldbtn-steps" aria-labelledby="ldbtn-steps-title">
		<div class="ldbtn-steps__head">
			<h2 class="ldbtn-card__title" id="ldbtn-steps-title"><?php esc_html_e( 'Setup', 'like-dislike-button' ); ?></h2>
			<span class="ldbtn-steps__count">
				<?php
				/* translators: 1: steps done, 2: total steps */
				echo esc_html( sprintf( __( '%1$d of %2$d done', 'like-dislike-button' ), $ldbtn_done, $ldbtn_total ) );
				?>
			</span>
		</div>
		<div class="ldbtn-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $ldbtn_total ); ?>" aria-valuenow="<?php echo esc_attr( $ldbtn_done ); ?>" aria-labelledby="ldbtn-steps-title">
			<span style="width:<?php echo esc_attr( round( $ldbtn_done / $ldbtn_total * 100 ) ); ?>%"></span>
		</div>

		<ol class="ldbtn-steps__list">
			<?php foreach ( $ldbtn_steps as $ldbtn_i => $ldbtn_step ) : ?>
				<li class="ldbtn-step <?php echo $ldbtn_step['done'] ? 'is-done' : ''; ?>">
					<span class="ldbtn-step__mark" aria-hidden="true"><?php echo $ldbtn_step['done'] ? $ldbtn_check : esc_html( $ldbtn_i + 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
					<div class="ldbtn-step__body">
						<h3 class="ldbtn-step__title">
							<?php echo esc_html( $ldbtn_step['title'] ); ?>
							<span class="screen-reader-text"><?php echo $ldbtn_step['done'] ? esc_html__( '(done)', 'like-dislike-button' ) : esc_html__( '(to do)', 'like-dislike-button' ); ?></span>
						</h3>
						<p class="ldbtn-step__text"><?php echo esc_html( $ldbtn_step['text'] ); ?></p>
					</div>
					<?php if ( $ldbtn_step['link'] ) : ?>
						<a class="button ldbtn-step__action" href="<?php echo esc_url( $ldbtn_step['link'] ); ?>" <?php echo ! empty( $ldbtn_step['external'] ) ? 'target="_blank" rel="noopener"' : ''; ?>>
							<?php echo esc_html( $ldbtn_step['label'] ); ?>
							<?php if ( ! empty( $ldbtn_step['external'] ) ) : ?>
								<?php echo $ldbtn_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
								<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'like-dislike-button' ); ?></span>
							<?php endif; ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>

	<section class="ldbtn-card ldbtn-places" aria-labelledby="ldbtn-places-title">
		<h2 class="ldbtn-card__title" id="ldbtn-places-title"><?php esc_html_e( 'More ways to show them', 'like-dislike-button' ); ?></h2>
		<ul class="ldbtn-places__list">
			<li>
				<span class="ldbtn-places__icon dashicons dashicons-block-default" aria-hidden="true"></span>
				<div>
					<strong><?php esc_html_e( 'Blocks and widgets', 'like-dislike-button' ); ?></strong>
					<p><?php esc_html_e( 'Search “Like Dislike” in the block inserter. Works in Query Loop, the site editor and sidebars, and Elementor has its own widgets.', 'like-dislike-button' ); ?></p>
				</div>
			</li>
			<li>
				<span class="ldbtn-places__icon dashicons dashicons-shortcode" aria-hidden="true"></span>
				<div>
					<strong><?php esc_html_e( 'Shortcode, for any page builder', 'like-dislike-button' ); ?></strong>
					<p class="ldbtn-copy">
						<code>[like_dislike_button]</code>
						<button type="button" class="button-link ldbtn-copy__btn" data-copy="[like_dislike_button]"><?php esc_html_e( 'Copy', 'like-dislike-button' ); ?></button>
					</p>
				</div>
			</li>
			<li>
				<span class="ldbtn-places__icon dashicons dashicons-star-filled" aria-hidden="true"></span>
				<div>
					<strong><?php esc_html_e( 'Most liked posts', 'like-dislike-button' ); ?></strong>
					<p class="ldbtn-copy">
						<code>[ldbtn_most_liked]</code>
						<button type="button" class="button-link ldbtn-copy__btn" data-copy="[ldbtn_most_liked]"><?php esc_html_e( 'Copy', 'like-dislike-button' ); ?></button>
					</p>
				</div>
			</li>
		</ul>
	</section>
</div>

<section class="ldbtn-ghost" aria-labelledby="ldbtn-ghost-title">
	<h2 class="ldbtn-ghost__title" id="ldbtn-ghost-title"><?php esc_html_e( 'What you’ll see here', 'like-dislike-button' ); ?></h2>
	<div class="ldbtn-ghost__frame" aria-hidden="true">
		<div class="ldbtn-stats">
			<?php foreach ( array( __( 'Likes', 'like-dislike-button' ), __( 'Dislikes', 'like-dislike-button' ), __( 'Total votes', 'like-dislike-button' ), __( 'Like ratio', 'like-dislike-button' ) ) as $ldbtn_i => $ldbtn_label ) : ?>
				<div class="ldbtn-stat">
					<span class="ldbtn-stat__label">
						<?php if ( $ldbtn_i < 2 ) : ?>
							<span class="ldbtn-key ldbtn-key--<?php echo 0 === $ldbtn_i ? 'like' : 'dislike'; ?>"></span>
						<?php endif; ?>
						<?php echo esc_html( $ldbtn_label ); ?>
					</span>
					<span class="ldbtn-stat__value">—</span>
					<span class="ldbtn-ghost__line"></span>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="ldbtn-card ldbtn-ghost__chart">
			<div class="ldbtn-ghost__bars">
				<?php
				// A gentle, fixed shape: placeholder only.
				foreach ( array( 30, 42, 36, 55, 48, 62, 40, 58, 70, 52, 66, 78, 60, 72, 84, 68, 76, 90, 74, 82 ) as $ldbtn_height ) {
					echo '<i style="height:' . (int) $ldbtn_height . '%"></i>';
				}
				?>
			</div>
		</div>
	</div>
	<p class="ldbtn-ghost__note"><?php esc_html_e( 'Your chart and top posts appear here after the first vote.', 'like-dislike-button' ); ?></p>
</section>
