<?php
/**
 * Settings screen.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

$likenook_s    = Likenook_Settings::all();
$likenook_name = function ( $key ) {
	return Likenook_Settings::OPTION . '[' . $key . ']';
};

/**
 * Prints an on/off switch.
 */
$likenook_toggle = function ( $key, $label, $help = '' ) use ( $likenook_s, $likenook_name ) {
	?>
	<div class="likenook-field likenook-field--toggle">
		<label class="likenook-switch">
			<input type="checkbox" name="<?php echo esc_attr( $likenook_name( $key ) ); ?>" value="1" <?php checked( ! empty( $likenook_s[ $key ] ) ); ?>>
			<span class="likenook-switch__track" aria-hidden="true"></span>
			<span class="likenook-switch__label"><?php echo esc_html( $label ); ?></span>
		</label>
		<?php if ( $help ) : ?>
			<p class="likenook-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

/**
 * Prints a segmented radio group.
 */
$likenook_segments = function ( $key, $label, $options, $help = '' ) use ( $likenook_s, $likenook_name ) {
	?>
	<fieldset class="likenook-field">
		<legend class="likenook-field__label"><?php echo esc_html( $label ); ?></legend>
		<div class="likenook-segments">
			<?php foreach ( $options as $value => $text ) : ?>
				<label>
					<input type="radio" name="<?php echo esc_attr( $likenook_name( $key ) ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $likenook_s[ $key ], $value ); ?>>
					<span><?php echo esc_html( $text ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php if ( $help ) : ?>
			<p class="likenook-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</fieldset>
	<?php
};

/**
 * Prints a text input.
 */
$likenook_text = function ( $key, $label, $placeholder = '', $help = '' ) use ( $likenook_s, $likenook_name ) {
	$id = 'likenook-' . str_replace( '_', '-', $key );
	?>
	<div class="likenook-field">
		<label class="likenook-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<input type="text" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $likenook_name( $key ) ); ?>" value="<?php echo esc_attr( $likenook_s[ $key ] ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>">
		<?php if ( $help ) : ?>
			<p class="likenook-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

/**
 * Prints a select.
 */
$likenook_select = function ( $key, $label, $options, $help = '' ) use ( $likenook_s, $likenook_name ) {
	$id = 'likenook-' . str_replace( '_', '-', $key );
	?>
	<div class="likenook-field">
		<label class="likenook-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $likenook_name( $key ) ); ?>">
			<?php foreach ( $options as $value => $text ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $likenook_s[ $key ], $value ); ?>><?php echo esc_html( $text ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( $help ) : ?>
			<p class="likenook-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

$likenook_types = get_post_types( array( 'public' => true ), 'objects' );
unset( $likenook_types['attachment'] );
?>
<div class="wrap likenook-admin">
	<?php Likenook_Admin::header( 'likenook-settings' ); ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Likenook Settings', 'likenook' ); ?></h1>
	<?php settings_errors(); ?>

	<nav class="likenook-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'likenook' ); ?>">
		<button type="button" role="tab" class="likenook-tab" aria-selected="true" aria-controls="likenook-tab-appearance" id="likenook-tab-btn-appearance"><?php esc_html_e( 'Appearance', 'likenook' ); ?></button>
		<button type="button" role="tab" class="likenook-tab" aria-selected="false" aria-controls="likenook-tab-placement" id="likenook-tab-btn-placement"><?php esc_html_e( 'Placement', 'likenook' ); ?></button>
		<button type="button" role="tab" class="likenook-tab" aria-selected="false" aria-controls="likenook-tab-voting" id="likenook-tab-btn-voting"><?php esc_html_e( 'Voting', 'likenook' ); ?></button>
		<button type="button" role="tab" class="likenook-tab" aria-selected="false" aria-controls="likenook-tab-advanced" id="likenook-tab-btn-advanced"><?php esc_html_e( 'Advanced', 'likenook' ); ?></button>
	</nav>

	<form method="post" action="options.php" id="likenook-settings-form">
		<?php settings_fields( 'likenook_settings' ); ?>

		<div class="likenook-layout">
			<div class="likenook-main">

				<section class="likenook-panel" role="tabpanel" id="likenook-tab-appearance" aria-labelledby="likenook-tab-btn-appearance">
					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Start from a preset', 'likenook' ); ?></h2>
						<p class="likenook-card__intro"><?php esc_html_e( 'Presets fill in the options below. Adjust anything afterwards, then save.', 'likenook' ); ?></p>
						<div class="likenook-presets">
							<?php foreach ( Likenook_Admin::presets() as $likenook_slug => $likenook_preset ) : ?>
								<button type="button" class="likenook-preset" data-preset="<?php echo esc_attr( $likenook_slug ); ?>">
									<span class="likenook-preset__icons likenook--icons-<?php echo esc_attr( $likenook_preset['values']['icon_set'] ); ?>" aria-hidden="true">
										<?php
										echo Likenook_Icons::get( $likenook_preset['values']['icon_set'], 'like' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from kses-filtered SVG.
										if ( 'both' === $likenook_preset['values']['buttons'] ) {
											echo Likenook_Icons::get( $likenook_preset['values']['icon_set'], 'dislike' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from kses-filtered SVG.
										}
										?>
									</span>
									<span class="likenook-preset__name"><?php echo esc_html( $likenook_preset['label'] ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Icons', 'likenook' ); ?></h2>
						<fieldset class="likenook-field">
							<legend class="screen-reader-text"><?php esc_html_e( 'Icon set', 'likenook' ); ?></legend>
							<div class="likenook-choices likenook-choices--icons">
								<?php foreach ( Likenook_Icons::sets() as $likenook_slug => $likenook_set ) : ?>
									<label class="likenook-choice">
										<input type="radio" name="<?php echo esc_attr( $likenook_name( 'icon_set' ) ); ?>" value="<?php echo esc_attr( $likenook_slug ); ?>" <?php checked( $likenook_s['icon_set'], $likenook_slug ); ?>>
										<span class="likenook-choice__body">
											<span class="likenook-choice__icons likenook--icons-<?php echo esc_attr( $likenook_slug ); ?>">
												<?php
												if ( 'none' === $likenook_slug ) {
													echo '<span class="likenook-choice__text">Aa</span>';
												} elseif ( 'custom' === $likenook_slug ) {
													echo '<span class="dashicons dashicons-format-image" aria-hidden="true"></span>';
												} else {
													echo Likenook_Icons::get( $likenook_slug, 'like' ) . Likenook_Icons::get( $likenook_slug, 'dislike' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from kses-filtered SVG.
												}
												?>
											</span>
											<span class="likenook-choice__name"><?php echo esc_html( $likenook_set['label'] ); ?></span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<div class="likenook-custom-icons" data-show-when="icon_set=custom">
							<?php foreach ( array( 'like' => __( 'Like image', 'likenook' ), 'dislike' => __( 'Dislike image', 'likenook' ) ) as $likenook_reaction => $likenook_label ) : ?>
								<?php
								$likenook_key = 'custom_' . $likenook_reaction . '_icon';
								$likenook_src = $likenook_s[ $likenook_key ] ? wp_get_attachment_image_url( $likenook_s[ $likenook_key ], 'thumbnail' ) : '';
								?>
								<div class="likenook-media">
									<span class="likenook-media__thumb"><?php echo $likenook_src ? '<img src="' . esc_url( $likenook_src ) . '" alt="">' : ''; ?></span>
									<span class="likenook-media__label"><?php echo esc_html( $likenook_label ); ?></span>
									<input type="hidden" name="<?php echo esc_attr( $likenook_name( $likenook_key ) ); ?>" value="<?php echo esc_attr( $likenook_s[ $likenook_key ] ); ?>">
									<button type="button" class="button likenook-media__pick"><?php esc_html_e( 'Choose', 'likenook' ); ?></button>
									<button type="button" class="button-link likenook-media__clear"><?php esc_html_e( 'Remove', 'likenook' ); ?></button>
								</div>
							<?php endforeach; ?>
							<p class="likenook-help"><?php esc_html_e( 'Square PNG, SVG or WebP images work best. They are shown at the button’s icon size.', 'likenook' ); ?></p>
						</div>
					</div>

					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Buttons', 'likenook' ); ?></h2>
						<fieldset class="likenook-field">
							<legend class="likenook-field__label"><?php esc_html_e( 'Style', 'likenook' ); ?></legend>
							<div class="likenook-choices likenook-choices--styles">
								<?php foreach ( Likenook_Blocks::style_labels() as $likenook_slug => $likenook_label ) : ?>
									<label class="likenook-choice">
										<input type="radio" name="<?php echo esc_attr( $likenook_name( 'style' ) ); ?>" value="<?php echo esc_attr( $likenook_slug ); ?>" <?php checked( $likenook_s['style'], $likenook_slug ); ?>>
										<span class="likenook-choice__body">
											<span class="likenook-choice__sample likenook-sample--<?php echo esc_attr( $likenook_slug ); ?>" aria-hidden="true"><i></i><i></i></span>
											<span class="likenook-choice__name"><?php echo esc_html( $likenook_label ); ?></span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<div class="likenook-grid">
							<?php
							$likenook_segments(
								'size',
								__( 'Size', 'likenook' ),
								array(
									'sm' => __( 'Small', 'likenook' ),
									'md' => __( 'Medium', 'likenook' ),
									'lg' => __( 'Large', 'likenook' ),
								)
							);
							$likenook_segments(
								'align',
								__( 'Alignment', 'likenook' ),
								array(
									'left'   => __( 'Left', 'likenook' ),
									'center' => __( 'Center', 'likenook' ),
									'right'  => __( 'Right', 'likenook' ),
								)
							);
							$likenook_segments(
								'layout',
								__( 'Layout', 'likenook' ),
								array(
									'row'   => __( 'Side by side', 'likenook' ),
									'stack' => __( 'Stacked', 'likenook' ),
								)
							);
							$likenook_segments(
								'animation',
								__( 'Click animation', 'likenook' ),
								array(
									'none'   => __( 'None', 'likenook' ),
									'pop'    => __( 'Pop', 'likenook' ),
									'bounce' => __( 'Bounce', 'likenook' ),
									'burst'  => __( 'Burst', 'likenook' ),
								),
								__( 'Visitors who prefer reduced motion never see animations.', 'likenook' )
							);
							?>
						</div>
					</div>

					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Labels and counts', 'likenook' ); ?></h2>
						<?php $likenook_toggle( 'show_labels', __( 'Show text labels next to the icons', 'likenook' ), __( 'Screen readers always hear the labels, even when they are hidden.', 'likenook' ) ); ?>
						<div class="likenook-grid">
							<?php
							$likenook_text( 'like_label', __( 'Like label', 'likenook' ), __( 'Like', 'likenook' ) );
							$likenook_text( 'dislike_label', __( 'Dislike label', 'likenook' ), __( 'Dislike', 'likenook' ) );
							?>
						</div>
						<div class="likenook-grid">
							<?php
							$likenook_select( 'counts', __( 'Counts', 'likenook' ), Likenook_Blocks::count_labels() );
							?>
							<div class="likenook-field-stack">
								<?php
								$likenook_toggle( 'short_numbers', __( 'Shorten large numbers (1.2K)', 'likenook' ) );
								$likenook_toggle( 'ratio_bar', __( 'Show a like ratio bar', 'likenook' ) );
								?>
							</div>
						</div>
					</div>

					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Question and thank-you', 'likenook' ); ?></h2>
						<?php
						$likenook_text( 'question', __( 'Question above the buttons', 'likenook' ), __( 'Was this article helpful?', 'likenook' ), __( 'Optional. Good for documentation and help articles.', 'likenook' ) );
						$likenook_text( 'thanks', __( 'Message after voting', 'likenook' ), __( 'Thanks for your feedback!', 'likenook' ) );
						?>
					</div>

					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Colours', 'likenook' ); ?></h2>
						<p class="likenook-card__intro"><?php esc_html_e( 'Leave empty to use colours that suit the icon set and your theme. Text and background follow your theme by default, so light and dark themes both work.', 'likenook' ); ?></p>
						<div class="likenook-grid likenook-grid--colors">
							<?php
							foreach (
								array(
									'like_color'    => __( 'Like', 'likenook' ),
									'dislike_color' => __( 'Dislike', 'likenook' ),
									'text_color'    => __( 'Text', 'likenook' ),
									'bg_color'      => __( 'Button background', 'likenook' ),
								) as $likenook_key => $likenook_label
							) :
								?>
								<div class="likenook-field">
									<label class="likenook-field__label" for="likenook-<?php echo esc_attr( $likenook_key ); ?>"><?php echo esc_html( $likenook_label ); ?></label>
									<input type="text" class="likenook-color" id="likenook-<?php echo esc_attr( $likenook_key ); ?>" name="<?php echo esc_attr( $likenook_name( $likenook_key ) ); ?>" value="<?php echo esc_attr( $likenook_s[ $likenook_key ] ); ?>">
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</section>

				<section class="likenook-panel" role="tabpanel" id="likenook-tab-placement" aria-labelledby="likenook-tab-btn-placement" hidden>
					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Where buttons appear', 'likenook' ); ?></h2>
						<fieldset class="likenook-field">
							<legend class="likenook-field__label"><?php esc_html_e( 'Content types', 'likenook' ); ?></legend>
							<div class="likenook-checks">
								<?php foreach ( $likenook_types as $likenook_type ) : ?>
									<label><input type="checkbox" name="<?php echo esc_attr( $likenook_name( 'post_types' ) ); ?>[]" value="<?php echo esc_attr( $likenook_type->name ); ?>" <?php checked( Likenook_Settings::type_enabled( $likenook_type->name ) ); ?>> <?php echo esc_html( $likenook_type->labels->name ); ?></label>
								<?php endforeach; ?>
							</div>
							<p class="likenook-help"><?php esc_html_e( 'WooCommerce products show the buttons in the product summary. Each post also has a box in the editor to hide them.', 'likenook' ); ?></p>
						</fieldset>
						<?php
						$likenook_toggle( 'comments', __( 'Add small buttons under each comment', 'likenook' ) );
						$likenook_segments(
							'position',
							__( 'Position', 'likenook' ),
							array(
								'after'  => __( 'After content', 'likenook' ),
								'before' => __( 'Before content', 'likenook' ),
								'both'   => __( 'Both', 'likenook' ),
								'manual' => __( 'Only where I place them', 'likenook' ),
							),
							__( '“Only where I place them” turns off automatic placement. Use the block, the shortcode or the template tag instead.', 'likenook' )
						);
						$likenook_toggle( 'archives', __( 'Also show on blog and archive pages that display full posts', 'likenook' ), __( 'Block themes can add the Likenook Buttons block to a Query Loop instead.', 'likenook' ) );
						?>
					</div>

					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Which buttons', 'likenook' ); ?></h2>
						<?php
						$likenook_segments(
							'buttons',
							__( 'Buttons', 'likenook' ),
							array(
								'both'    => __( 'Like and dislike', 'likenook' ),
								'like'    => __( 'Like only', 'likenook' ),
								'dislike' => __( 'Dislike only', 'likenook' ),
							)
						);
						$likenook_segments(
							'order',
							__( 'Order', 'likenook' ),
							array(
								'like_first'    => __( 'Like first', 'likenook' ),
								'dislike_first' => __( 'Dislike first', 'likenook' ),
							)
						);
						?>
					</div>

					<div class="likenook-card likenook-card--muted">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Placing buttons by hand', 'likenook' ); ?></h2>
						<dl class="likenook-reference">
							<dt><?php esc_html_e( 'Block', 'likenook' ); ?></dt>
							<dd><?php esc_html_e( 'Likenook Buttons and Most Liked Posts, in the Widgets category. Both work in posts, Query Loop, the site editor and widget areas.', 'likenook' ); ?></dd>
							<dt><?php esc_html_e( 'Widgets', 'likenook' ); ?></dt>
							<dd><?php esc_html_e( 'Classic themes get Likenook Buttons and Most Liked Posts widgets. In a sidebar, the buttons belong to the post being viewed and hide on blog and archive pages.', 'likenook' ); ?></dd>
							<dt><?php esc_html_e( 'Page builders', 'likenook' ); ?></dt>
							<dd><?php esc_html_e( 'Elementor has its own widgets. In Divi, Bricks, Beaver Builder, WPBakery, Oxygen and others, use the shortcode in a shortcode or text module.', 'likenook' ); ?></dd>
							<dt><?php esc_html_e( 'Shortcode', 'likenook' ); ?></dt>
							<dd><code>[likenook]</code> <code>[likenook id="42" icons="hearts" style="pill"]</code> <code>[likenook_most_liked number="5" period="month"]</code> <code>[likenook style="outline" labels="yes" like_label="Yes" dislike_label="No"]</code></dd>
							<dt><?php esc_html_e( 'Theme template', 'likenook' ); ?></dt>
							<dd><code>&lt;?php if ( function_exists( 'likenook_buttons' ) ) { likenook_buttons(); } ?&gt;</code></dd>
						</dl>
					</div>
				</section>

				<section class="likenook-panel" role="tabpanel" id="likenook-tab-voting" aria-labelledby="likenook-tab-btn-voting" hidden>
					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Who can vote', 'likenook' ); ?></h2>
						<?php
						$likenook_segments(
							'who',
							__( 'Voters', 'likenook' ),
							array(
								'everyone'  => __( 'Everyone', 'likenook' ),
								'logged_in' => __( 'Logged-in users only', 'likenook' ),
							),
							__( 'Visitors who can’t vote still see the buttons and counts, and get a link to log in when they click.', 'likenook' )
						);
						?>
						<fieldset class="likenook-field" data-show-when="who=logged_in">
							<legend class="likenook-field__label"><?php esc_html_e( 'Only these roles (leave empty for all)', 'likenook' ); ?></legend>
							<div class="likenook-checks">
								<?php foreach ( wp_roles()->get_names() as $likenook_role => $likenook_role_name ) : ?>
									<label><input type="checkbox" name="<?php echo esc_attr( $likenook_name( 'roles' ) ); ?>[]" value="<?php echo esc_attr( $likenook_role ); ?>" <?php checked( in_array( $likenook_role, (array) $likenook_s['roles'], true ) ); ?>> <?php echo esc_html( translate_user_role( $likenook_role_name ) ); ?></label>
								<?php endforeach; ?>
							</div>
						</fieldset>
						<?php $likenook_toggle( 'allow_change', __( 'Let people change or remove their vote', 'likenook' ), __( 'Clicking the same button again removes the vote; clicking the other one switches it.', 'likenook' ) ); ?>
					</div>

					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'One vote per visitor', 'likenook' ); ?></h2>
						<p class="likenook-card__intro"><?php esc_html_e( 'Logged-in users always get one vote per item, tied to their account. For visitors, choose how to recognise them:', 'likenook' ); ?></p>
						<fieldset class="likenook-field">
							<legend class="screen-reader-text"><?php esc_html_e( 'Visitor tracking', 'likenook' ); ?></legend>
							<div class="likenook-options">
								<label class="likenook-option">
									<input type="radio" name="<?php echo esc_attr( $likenook_name( 'guest_tracking' ) ); ?>" value="cookie" <?php checked( $likenook_s['guest_tracking'], 'cookie' ); ?>>
									<span><strong><?php esc_html_e( 'Per browser (recommended)', 'likenook' ); ?></strong>
									<?php esc_html_e( 'A random ID is stored in a cookie when someone votes. People sharing an office or school network can each vote.', 'likenook' ); ?></span>
								</label>
								<label class="likenook-option">
									<input type="radio" name="<?php echo esc_attr( $likenook_name( 'guest_tracking' ) ); ?>" value="ip" <?php checked( $likenook_s['guest_tracking'], 'ip' ); ?>>
									<span><strong><?php esc_html_e( 'Per IP address', 'likenook' ); ?></strong>
									<?php esc_html_e( 'Harder to get around by clearing cookies, but everyone on a shared network counts as one voter.', 'likenook' ); ?></span>
								</label>
							</div>
							<p class="likenook-help"><?php esc_html_e( 'IP addresses are never stored, only a keyed one-way hash. Votes are also limited to 30 per minute per network.', 'likenook' ); ?></p>
						</fieldset>
						<?php
						$likenook_select(
							'ip_header',
							__( 'Visitor IP comes from', 'likenook' ),
							array(
								''                      => __( 'The connection (default)', 'likenook' ),
								'HTTP_CF_CONNECTING_IP' => __( 'Cloudflare header (CF-Connecting-IP)', 'likenook' ),
								'HTTP_X_FORWARDED_FOR'  => __( 'Proxy header (X-Forwarded-For)', 'likenook' ),
								'HTTP_X_REAL_IP'        => __( 'Proxy header (X-Real-IP)', 'likenook' ),
							),
							__( 'Change this only if your site is behind Cloudflare or a proxy and every visitor shows the same IP. Headers can be faked when the site isn’t behind that proxy.', 'likenook' )
						);
						?>
					</div>
				</section>

				<section class="likenook-panel" role="tabpanel" id="likenook-tab-advanced" aria-labelledby="likenook-tab-btn-advanced" hidden>
					<div class="likenook-card">
						<h2 class="likenook-card__title"><?php esc_html_e( 'Advanced', 'likenook' ); ?></h2>
						<?php
						$likenook_toggle( 'schema', __( 'Add like counts to structured data (schema.org)', 'likenook' ), __( 'Adds interactionStatistic (LikeAction and DislikeAction counts) to the page’s existing Article or WebPage markup. Works with Yoast SEO, Rank Math, All in One SEO and themes that print their own JSON-LD. Only added when vote results are visible (counts, percentages or score), because search engines expect marked-up data to be visible.', 'likenook' ) );
						$likenook_toggle( 'rest_field', __( 'Add like and dislike totals to the REST API', 'likenook' ), __( 'Adds an likenook_votes field to enabled post types, for headless sites and apps.', 'likenook' ) );
						$likenook_toggle( 'compat_shortcodes', __( 'Keep shortcodes from other like plugins working', 'likenook' ), __( 'Shows these buttons wherever [posts_like_dislike], [like_dislike] or [wp_ulike] appear, as long as those plugins are inactive. Switched on automatically after an import.', 'likenook' ) );
						$likenook_toggle( 'delete_data', __( 'Delete all votes and settings when the plugin is deleted', 'likenook' ) );
						?>
					</div>

					<div class="likenook-card likenook-card--muted">
						<h2 class="likenook-card__title"><?php esc_html_e( 'For developers', 'likenook' ); ?></h2>
						<dl class="likenook-reference">
							<dt><?php esc_html_e( 'Template tags', 'likenook' ); ?></dt>
							<dd><code>likenook_buttons( $post_id, $args )</code> <code>likenook_get_buttons()</code> <code>likenook_get_counts( $id, 'post' )</code></dd>
							<dt><?php esc_html_e( 'REST API', 'likenook' ); ?></dt>
							<dd><code>GET /wp-json/likenook/v1/state?items=post:1,comment:2</code> <code>POST /wp-json/likenook/v1/vote</code></dd>
							<dt><?php esc_html_e( 'Actions', 'likenook' ); ?></dt>
							<dd><code>likenook_vote_recorded</code></dd>
							<dt><?php esc_html_e( 'Filters', 'likenook' ); ?></dt>
							<dd><code>likenook_buttons_html</code> <code>likenook_can_vote</code> <code>likenook_icon_sets</code> <code>likenook_visitor_ip</code> <code>likenook_rate_limit</code> <code>likenook_schema_statistics</code></dd>
							<dt><?php esc_html_e( 'CSS', 'likenook' ); ?></dt>
							<dd><code>.likenook { --likenook-like: #2563eb; --likenook-dislike: #dc2626; --likenook-radius: .6em; }</code></dd>
							<dt>WP-CLI</dt>
							<dd><code>wp likenook stats</code> <code>wp likenook recount</code> <code>wp likenook import wp-ulike</code> <code>wp likenook reset --post=42</code></dd>
						</dl>
					</div>
				</section>

				<div class="likenook-savebar">
					<?php submit_button( __( 'Save settings', 'likenook' ), 'primary', 'submit', false ); ?>
				</div>
			</div>

			<aside class="likenook-aside" aria-label="<?php esc_attr_e( 'Live preview', 'likenook' ); ?>">
				<div class="likenook-preview-card">
					<div class="likenook-preview-card__head">
						<span class="likenook-preview-card__title"><?php esc_html_e( 'Live preview', 'likenook' ); ?></span>
						<div class="likenook-preview-bg" role="radiogroup" aria-label="<?php esc_attr_e( 'Preview background', 'likenook' ); ?>">
							<button type="button" role="radio" aria-checked="true" data-bg="light"><?php esc_html_e( 'Light', 'likenook' ); ?></button>
							<button type="button" role="radio" aria-checked="false" data-bg="dark"><?php esc_html_e( 'Dark', 'likenook' ); ?></button>
							<button type="button" role="radio" aria-checked="false" data-bg="image"><?php esc_html_e( 'Image', 'likenook' ); ?></button>
						</div>
					</div>
					<div class="likenook-preview-stage" data-bg="light">
						<div class="likenook-preview-article" aria-hidden="true">
							<span></span><span></span><span></span>
						</div>
						<div id="likenook-preview">
							<?php echo Likenook_Admin::preview_html( $likenook_s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building. ?>
						</div>
					</div>
					<p class="likenook-preview-card__note"><?php esc_html_e( 'Click the buttons to try them. Nothing is recorded here.', 'likenook' ); ?></p>
				</div>
			</aside>
		</div>
	</form>
</div>
