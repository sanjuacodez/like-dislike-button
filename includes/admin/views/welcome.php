<?php
/**
 * Dashboard before the first vote: setup checklist, preview and what's coming.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

$likenook_s        = Likenook_Settings::all();
$likenook_can_edit = current_user_can( 'manage_options' );
$likenook_types    = array_values( array_filter( (array) $likenook_s['post_types'], 'post_type_exists' ) );
$likenook_manual   = 'manual' === $likenook_s['position'];
$likenook_live     = $likenook_types && ! $likenook_manual;

$likenook_type_names = array();
foreach ( $likenook_types as $likenook_type ) {
	$likenook_type_names[] = get_post_type_object( $likenook_type )->labels->name;
}

// A published post that shows the buttons, to try them out.
$likenook_sample     = $likenook_types ? get_posts(
	array(
		'post_type'   => $likenook_types,
		'numberposts' => 1,
		'post_status' => 'publish',
		'has_password' => false,
	)
) : array();
$likenook_sample_url = $likenook_sample ? get_permalink( $likenook_sample[0] ) : '';

// Another like plugin with data to bring over.
$likenook_import = null;
foreach ( Likenook_Importer::sources() as $likenook_slug => $likenook_source ) {
	if ( $likenook_source['count'] && 'likenook-2' !== $likenook_slug ) {
		$likenook_import = $likenook_source;
		break;
	}
}

$likenook_steps = array(
	array(
		'done'  => $likenook_live || $likenook_manual,
		'title' => $likenook_live
			/* translators: %s: list of post types, e.g. "Posts and Pages" */
			? sprintf( __( 'Buttons are showing on %s', 'likenook' ), wp_sprintf_l( '%l', $likenook_type_names ) )
			: ( $likenook_manual ? __( 'You’re placing buttons by hand', 'likenook' ) : __( 'Choose where buttons appear', 'likenook' ) ),
		'text'  => $likenook_live
			? __( 'They appear after the content automatically. Add pages, products or comments any time.', 'likenook' )
			: __( 'Pick post types for automatic placement, or use the block and shortcode.', 'likenook' ),
		'link'  => $likenook_can_edit ? admin_url( 'admin.php?page=likenook-settings#placement' ) : '',
		'label' => $likenook_live ? __( 'Change', 'likenook' ) : __( 'Set up', 'likenook' ),
	),
	array(
		'done'  => false !== get_option( Likenook_Settings::OPTION, false ),
		'title' => __( 'Make them match your site', 'likenook' ),
		'text'  => __( 'Pick from 9 icon sets and 6 styles, or start from a preset such as “Was this helpful?”.', 'likenook' ),
		'link'  => $likenook_can_edit ? admin_url( 'admin.php?page=likenook-settings' ) : '',
		'label' => __( 'Customize', 'likenook' ),
	),
);
if ( $likenook_import ) {
	$likenook_steps[] = array(
		'done'  => false,
		/* translators: %s: plugin name */
		'title' => sprintf( __( 'Bring over your votes from %s', 'likenook' ), $likenook_import['label'] ),
		/* translators: %s: number of votes or posts */
		'text'  => sprintf( __( 'We found %s records. Importing keeps dates and never changes the other plugin’s data.', 'likenook' ), number_format_i18n( $likenook_import['count'] ) ),
		'link'  => $likenook_can_edit ? admin_url( 'admin.php?page=likenook-tools' ) : '',
		'label' => __( 'Import', 'likenook' ),
	);
}
$likenook_steps[] = array(
	'done'     => false,
	'title'    => __( 'Get your first vote', 'likenook' ),
	'text'     => __( 'Open a post and click Like to see it work. Test votes can be reset from the post editor.', 'likenook' ),
	'link'     => $likenook_sample_url,
	'label'    => __( 'Open a post', 'likenook' ),
	'external' => true,
);

$likenook_done  = count( wp_list_filter( $likenook_steps, array( 'done' => true ) ) );
$likenook_total = count( $likenook_steps );

$likenook_check = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 6 9 17l-5-5"/></svg>';
$likenook_arrow = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 17 17 7M8 7h9v9"/></svg>';
?>
<section class="likenook-hero" aria-labelledby="likenook-hero-title">
	<div class="likenook-hero__body">
		<p class="likenook-hero__eyebrow"><?php esc_html_e( 'Getting started', 'likenook' ); ?></p>
		<h2 class="likenook-hero__title" id="likenook-hero-title">
			<?php echo $likenook_live ? esc_html__( 'Your buttons are live. Let’s get the first vote.', 'likenook' ) : esc_html__( 'Let’s put your buttons on the page.', 'likenook' ); ?>
		</h2>
		<p class="likenook-hero__text"><?php esc_html_e( 'Once visitors start voting, this page shows likes over time, your most and least liked posts, and how each period compares with the last.', 'likenook' ); ?></p>
		<div class="likenook-hero__actions">
			<?php if ( $likenook_can_edit ) : ?>
				<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=likenook-settings' ) ); ?>"><?php esc_html_e( 'Customize buttons', 'likenook' ); ?></a>
			<?php endif; ?>
			<?php if ( $likenook_sample_url ) : ?>
				<a class="button button-hero likenook-button-ghost" href="<?php echo esc_url( $likenook_sample_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'See them on your site', 'likenook' ); ?>
					<?php echo $likenook_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'likenook' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="likenook-hero__preview">
		<div class="likenook-mock" aria-label="<?php esc_attr_e( 'Preview of your buttons. Click to try them; nothing is recorded.', 'likenook' ); ?>" role="group">
			<div class="likenook-mock__bar" aria-hidden="true"><i></i><i></i><i></i></div>
			<div class="likenook-mock__page">
				<span class="likenook-mock__title" aria-hidden="true"></span>
				<span class="likenook-mock__line" aria-hidden="true"></span>
				<span class="likenook-mock__line" aria-hidden="true"></span>
				<span class="likenook-mock__line is-short" aria-hidden="true"></span>
				<?php
				echo Likenook_Render::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
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
		<p class="likenook-hero__hint"><?php esc_html_e( 'Your current style. Click to try it.', 'likenook' ); ?></p>
	</div>
</section>

<div class="likenook-onboard">
	<section class="likenook-card likenook-steps" aria-labelledby="likenook-steps-title">
		<div class="likenook-steps__head">
			<h2 class="likenook-card__title" id="likenook-steps-title"><?php esc_html_e( 'Setup', 'likenook' ); ?></h2>
			<span class="likenook-steps__count">
				<?php
				/* translators: 1: steps done, 2: total steps */
				echo esc_html( sprintf( __( '%1$d of %2$d done', 'likenook' ), $likenook_done, $likenook_total ) );
				?>
			</span>
		</div>
		<div class="likenook-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $likenook_total ); ?>" aria-valuenow="<?php echo esc_attr( $likenook_done ); ?>" aria-labelledby="likenook-steps-title">
			<span style="width:<?php echo esc_attr( round( $likenook_done / $likenook_total * 100 ) ); ?>%"></span>
		</div>

		<ol class="likenook-steps__list">
			<?php foreach ( $likenook_steps as $likenook_i => $likenook_step ) : ?>
				<li class="likenook-step <?php echo $likenook_step['done'] ? 'is-done' : ''; ?>">
					<span class="likenook-step__mark" aria-hidden="true"><?php echo $likenook_step['done'] ? $likenook_check : esc_html( $likenook_i + 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
					<div class="likenook-step__body">
						<h3 class="likenook-step__title">
							<?php echo esc_html( $likenook_step['title'] ); ?>
							<span class="screen-reader-text"><?php echo $likenook_step['done'] ? esc_html__( '(done)', 'likenook' ) : esc_html__( '(to do)', 'likenook' ); ?></span>
						</h3>
						<p class="likenook-step__text"><?php echo esc_html( $likenook_step['text'] ); ?></p>
					</div>
					<?php if ( $likenook_step['link'] ) : ?>
						<a class="button likenook-step__action" href="<?php echo esc_url( $likenook_step['link'] ); ?>" <?php echo ! empty( $likenook_step['external'] ) ? 'target="_blank" rel="noopener"' : ''; ?>>
							<?php echo esc_html( $likenook_step['label'] ); ?>
							<?php if ( ! empty( $likenook_step['external'] ) ) : ?>
								<?php echo $likenook_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
								<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'likenook' ); ?></span>
							<?php endif; ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>

	<section class="likenook-card likenook-places" aria-labelledby="likenook-places-title">
		<h2 class="likenook-card__title" id="likenook-places-title"><?php esc_html_e( 'More ways to show them', 'likenook' ); ?></h2>
		<ul class="likenook-places__list">
			<li>
				<span class="likenook-places__icon dashicons dashicons-block-default" aria-hidden="true"></span>
				<div>
					<strong><?php esc_html_e( 'Blocks and widgets', 'likenook' ); ?></strong>
					<p><?php esc_html_e( 'Search “Likenook” in the block inserter. Works in Query Loop, the site editor and sidebars, and Elementor has its own widgets.', 'likenook' ); ?></p>
				</div>
			</li>
			<li>
				<span class="likenook-places__icon dashicons dashicons-shortcode" aria-hidden="true"></span>
				<div>
					<strong><?php esc_html_e( 'Shortcode, for any page builder', 'likenook' ); ?></strong>
					<p class="likenook-copy">
						<code>[likenook]</code>
						<button type="button" class="button-link likenook-copy__btn" data-copy="[likenook]"><?php esc_html_e( 'Copy', 'likenook' ); ?></button>
					</p>
				</div>
			</li>
			<li>
				<span class="likenook-places__icon dashicons dashicons-star-filled" aria-hidden="true"></span>
				<div>
					<strong><?php esc_html_e( 'Most liked posts', 'likenook' ); ?></strong>
					<p class="likenook-copy">
						<code>[likenook_most_liked]</code>
						<button type="button" class="button-link likenook-copy__btn" data-copy="[likenook_most_liked]"><?php esc_html_e( 'Copy', 'likenook' ); ?></button>
					</p>
				</div>
			</li>
		</ul>
	</section>
</div>

<section class="likenook-ghost" aria-labelledby="likenook-ghost-title">
	<h2 class="likenook-ghost__title" id="likenook-ghost-title"><?php esc_html_e( 'What you’ll see here', 'likenook' ); ?></h2>
	<div class="likenook-ghost__frame" aria-hidden="true">
		<div class="likenook-stats">
			<?php foreach ( array( __( 'Likes', 'likenook' ), __( 'Dislikes', 'likenook' ), __( 'Total votes', 'likenook' ), __( 'Like ratio', 'likenook' ) ) as $likenook_i => $likenook_label ) : ?>
				<div class="likenook-stat">
					<span class="likenook-stat__label">
						<?php if ( $likenook_i < 2 ) : ?>
							<span class="likenook-key likenook-key--<?php echo 0 === $likenook_i ? 'like' : 'dislike'; ?>"></span>
						<?php endif; ?>
						<?php echo esc_html( $likenook_label ); ?>
					</span>
					<span class="likenook-stat__value">—</span>
					<span class="likenook-ghost__line"></span>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="likenook-card likenook-ghost__chart">
			<div class="likenook-ghost__bars">
				<?php
				// A gentle, fixed shape: placeholder only.
				foreach ( array( 30, 42, 36, 55, 48, 62, 40, 58, 70, 52, 66, 78, 60, 72, 84, 68, 76, 90, 74, 82 ) as $likenook_height ) {
					echo '<i style="height:' . (int) $likenook_height . '%"></i>';
				}
				?>
			</div>
		</div>
	</div>
	<p class="likenook-ghost__note"><?php esc_html_e( 'Your chart and top posts appear here after the first vote.', 'likenook' ); ?></p>
</section>
