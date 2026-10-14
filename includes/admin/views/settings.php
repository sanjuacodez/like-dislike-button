<?php
/**
 * Settings screen.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

$ldbtn_s    = LDBTN_Settings::all();
$ldbtn_name = function ( $key ) {
	return LDBTN_Settings::OPTION . '[' . $key . ']';
};

/**
 * Prints an on/off switch.
 */
$ldbtn_toggle = function ( $key, $label, $help = '' ) use ( $ldbtn_s, $ldbtn_name ) {
	?>
	<div class="ldbtn-field ldbtn-field--toggle">
		<label class="ldbtn-switch">
			<input type="checkbox" name="<?php echo esc_attr( $ldbtn_name( $key ) ); ?>" value="1" <?php checked( ! empty( $ldbtn_s[ $key ] ) ); ?>>
			<span class="ldbtn-switch__track" aria-hidden="true"></span>
			<span class="ldbtn-switch__label"><?php echo esc_html( $label ); ?></span>
		</label>
		<?php if ( $help ) : ?>
			<p class="ldbtn-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

/**
 * Prints a segmented radio group.
 */
$ldbtn_segments = function ( $key, $label, $options, $help = '' ) use ( $ldbtn_s, $ldbtn_name ) {
	?>
	<fieldset class="ldbtn-field">
		<legend class="ldbtn-field__label"><?php echo esc_html( $label ); ?></legend>
		<div class="ldbtn-segments">
			<?php foreach ( $options as $value => $text ) : ?>
				<label>
					<input type="radio" name="<?php echo esc_attr( $ldbtn_name( $key ) ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $ldbtn_s[ $key ], $value ); ?>>
					<span><?php echo esc_html( $text ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php if ( $help ) : ?>
			<p class="ldbtn-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</fieldset>
	<?php
};

/**
 * Prints a text input.
 */
$ldbtn_text = function ( $key, $label, $placeholder = '', $help = '' ) use ( $ldbtn_s, $ldbtn_name ) {
	$id = 'ldbtn-' . str_replace( '_', '-', $key );
	?>
	<div class="ldbtn-field">
		<label class="ldbtn-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<input type="text" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $ldbtn_name( $key ) ); ?>" value="<?php echo esc_attr( $ldbtn_s[ $key ] ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>">
		<?php if ( $help ) : ?>
			<p class="ldbtn-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

/**
 * Prints a select.
 */
$ldbtn_select = function ( $key, $label, $options, $help = '' ) use ( $ldbtn_s, $ldbtn_name ) {
	$id = 'ldbtn-' . str_replace( '_', '-', $key );
	?>
	<div class="ldbtn-field">
		<label class="ldbtn-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $ldbtn_name( $key ) ); ?>">
			<?php foreach ( $options as $value => $text ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $ldbtn_s[ $key ], $value ); ?>><?php echo esc_html( $text ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( $help ) : ?>
			<p class="ldbtn-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

$ldbtn_types = get_post_types( array( 'public' => true ), 'objects' );
unset( $ldbtn_types['attachment'] );
?>
<div class="wrap ldbtn-admin">
	<?php LDBTN_Admin::header( 'ldbtn-settings' ); ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Like Dislike Settings', 'like-dislike-button' ); ?></h1>
	<?php settings_errors(); ?>

	<nav class="ldbtn-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'like-dislike-button' ); ?>">
		<button type="button" role="tab" class="ldbtn-tab" aria-selected="true" aria-controls="ldbtn-tab-appearance" id="ldbtn-tab-btn-appearance"><?php esc_html_e( 'Appearance', 'like-dislike-button' ); ?></button>
		<button type="button" role="tab" class="ldbtn-tab" aria-selected="false" aria-controls="ldbtn-tab-placement" id="ldbtn-tab-btn-placement"><?php esc_html_e( 'Placement', 'like-dislike-button' ); ?></button>
		<button type="button" role="tab" class="ldbtn-tab" aria-selected="false" aria-controls="ldbtn-tab-voting" id="ldbtn-tab-btn-voting"><?php esc_html_e( 'Voting', 'like-dislike-button' ); ?></button>
		<button type="button" role="tab" class="ldbtn-tab" aria-selected="false" aria-controls="ldbtn-tab-advanced" id="ldbtn-tab-btn-advanced"><?php esc_html_e( 'Advanced', 'like-dislike-button' ); ?></button>
	</nav>

	<form method="post" action="options.php" id="ldbtn-settings-form">
		<?php settings_fields( 'ldbtn_settings' ); ?>

		<div class="ldbtn-layout">
			<div class="ldbtn-main">

				<section class="ldbtn-panel" role="tabpanel" id="ldbtn-tab-appearance" aria-labelledby="ldbtn-tab-btn-appearance">
					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Start from a preset', 'like-dislike-button' ); ?></h2>
						<p class="ldbtn-card__intro"><?php esc_html_e( 'Presets fill in the options below. Adjust anything afterwards, then save.', 'like-dislike-button' ); ?></p>
						<div class="ldbtn-presets">
							<?php foreach ( LDBTN_Admin::presets() as $ldbtn_slug => $ldbtn_preset ) : ?>
								<button type="button" class="ldbtn-preset" data-preset="<?php echo esc_attr( $ldbtn_slug ); ?>">
									<span class="ldbtn-preset__icons ldbtn--icons-<?php echo esc_attr( $ldbtn_preset['values']['icon_set'] ); ?>" aria-hidden="true">
										<?php
										echo LDBTN_Icons::get( $ldbtn_preset['values']['icon_set'], 'like' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from kses-filtered SVG.
										if ( 'both' === $ldbtn_preset['values']['buttons'] ) {
											echo LDBTN_Icons::get( $ldbtn_preset['values']['icon_set'], 'dislike' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from kses-filtered SVG.
										}
										?>
									</span>
									<span class="ldbtn-preset__name"><?php echo esc_html( $ldbtn_preset['label'] ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Icons', 'like-dislike-button' ); ?></h2>
						<fieldset class="ldbtn-field">
							<legend class="screen-reader-text"><?php esc_html_e( 'Icon set', 'like-dislike-button' ); ?></legend>
							<div class="ldbtn-choices ldbtn-choices--icons">
								<?php foreach ( LDBTN_Icons::sets() as $ldbtn_slug => $ldbtn_set ) : ?>
									<label class="ldbtn-choice">
										<input type="radio" name="<?php echo esc_attr( $ldbtn_name( 'icon_set' ) ); ?>" value="<?php echo esc_attr( $ldbtn_slug ); ?>" <?php checked( $ldbtn_s['icon_set'], $ldbtn_slug ); ?>>
										<span class="ldbtn-choice__body">
											<span class="ldbtn-choice__icons ldbtn--icons-<?php echo esc_attr( $ldbtn_slug ); ?>">
												<?php
												if ( 'none' === $ldbtn_slug ) {
													echo '<span class="ldbtn-choice__text">Aa</span>';
												} elseif ( 'custom' === $ldbtn_slug ) {
													echo '<span class="dashicons dashicons-format-image" aria-hidden="true"></span>';
												} else {
													echo LDBTN_Icons::get( $ldbtn_slug, 'like' ) . LDBTN_Icons::get( $ldbtn_slug, 'dislike' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from kses-filtered SVG.
												}
												?>
											</span>
											<span class="ldbtn-choice__name"><?php echo esc_html( $ldbtn_set['label'] ); ?></span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<div class="ldbtn-custom-icons" data-show-when="icon_set=custom">
							<?php foreach ( array( 'like' => __( 'Like image', 'like-dislike-button' ), 'dislike' => __( 'Dislike image', 'like-dislike-button' ) ) as $ldbtn_reaction => $ldbtn_label ) : ?>
								<?php
								$ldbtn_key = 'custom_' . $ldbtn_reaction . '_icon';
								$ldbtn_src = $ldbtn_s[ $ldbtn_key ] ? wp_get_attachment_image_url( $ldbtn_s[ $ldbtn_key ], 'thumbnail' ) : '';
								?>
								<div class="ldbtn-media">
									<span class="ldbtn-media__thumb"><?php echo $ldbtn_src ? '<img src="' . esc_url( $ldbtn_src ) . '" alt="">' : ''; ?></span>
									<span class="ldbtn-media__label"><?php echo esc_html( $ldbtn_label ); ?></span>
									<input type="hidden" name="<?php echo esc_attr( $ldbtn_name( $ldbtn_key ) ); ?>" value="<?php echo esc_attr( $ldbtn_s[ $ldbtn_key ] ); ?>">
									<button type="button" class="button ldbtn-media__pick"><?php esc_html_e( 'Choose', 'like-dislike-button' ); ?></button>
									<button type="button" class="button-link ldbtn-media__clear"><?php esc_html_e( 'Remove', 'like-dislike-button' ); ?></button>
								</div>
							<?php endforeach; ?>
							<p class="ldbtn-help"><?php esc_html_e( 'Square PNG, SVG or WebP images work best. They are shown at the button’s icon size.', 'like-dislike-button' ); ?></p>
						</div>
					</div>

					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Buttons', 'like-dislike-button' ); ?></h2>
						<fieldset class="ldbtn-field">
							<legend class="ldbtn-field__label"><?php esc_html_e( 'Style', 'like-dislike-button' ); ?></legend>
							<div class="ldbtn-choices ldbtn-choices--styles">
								<?php foreach ( LDBTN_Blocks::style_labels() as $ldbtn_slug => $ldbtn_label ) : ?>
									<label class="ldbtn-choice">
										<input type="radio" name="<?php echo esc_attr( $ldbtn_name( 'style' ) ); ?>" value="<?php echo esc_attr( $ldbtn_slug ); ?>" <?php checked( $ldbtn_s['style'], $ldbtn_slug ); ?>>
										<span class="ldbtn-choice__body">
											<span class="ldbtn-choice__sample ldbtn-sample--<?php echo esc_attr( $ldbtn_slug ); ?>" aria-hidden="true"><i></i><i></i></span>
											<span class="ldbtn-choice__name"><?php echo esc_html( $ldbtn_label ); ?></span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<div class="ldbtn-grid">
							<?php
							$ldbtn_segments(
								'size',
								__( 'Size', 'like-dislike-button' ),
								array(
									'sm' => __( 'Small', 'like-dislike-button' ),
									'md' => __( 'Medium', 'like-dislike-button' ),
									'lg' => __( 'Large', 'like-dislike-button' ),
								)
							);
							$ldbtn_segments(
								'align',
								__( 'Alignment', 'like-dislike-button' ),
								array(
									'left'   => __( 'Left', 'like-dislike-button' ),
									'center' => __( 'Center', 'like-dislike-button' ),
									'right'  => __( 'Right', 'like-dislike-button' ),
								)
							);
							$ldbtn_segments(
								'layout',
								__( 'Layout', 'like-dislike-button' ),
								array(
									'row'   => __( 'Side by side', 'like-dislike-button' ),
									'stack' => __( 'Stacked', 'like-dislike-button' ),
								)
							);
							$ldbtn_segments(
								'animation',
								__( 'Click animation', 'like-dislike-button' ),
								array(
									'none'   => __( 'None', 'like-dislike-button' ),
									'pop'    => __( 'Pop', 'like-dislike-button' ),
									'bounce' => __( 'Bounce', 'like-dislike-button' ),
									'burst'  => __( 'Burst', 'like-dislike-button' ),
								),
								__( 'Visitors who prefer reduced motion never see animations.', 'like-dislike-button' )
							);
							?>
						</div>
					</div>

					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Labels and counts', 'like-dislike-button' ); ?></h2>
						<?php $ldbtn_toggle( 'show_labels', __( 'Show text labels next to the icons', 'like-dislike-button' ), __( 'Screen readers always hear the labels, even when they are hidden.', 'like-dislike-button' ) ); ?>
						<div class="ldbtn-grid">
							<?php
							$ldbtn_text( 'like_label', __( 'Like label', 'like-dislike-button' ), __( 'Like', 'like-dislike-button' ) );
							$ldbtn_text( 'dislike_label', __( 'Dislike label', 'like-dislike-button' ), __( 'Dislike', 'like-dislike-button' ) );
							?>
						</div>
						<div class="ldbtn-grid">
							<?php
							$ldbtn_select( 'counts', __( 'Counts', 'like-dislike-button' ), LDBTN_Blocks::count_labels() );
							?>
							<div class="ldbtn-field-stack">
								<?php
								$ldbtn_toggle( 'short_numbers', __( 'Shorten large numbers (1.2K)', 'like-dislike-button' ) );
								$ldbtn_toggle( 'ratio_bar', __( 'Show a like ratio bar', 'like-dislike-button' ) );
								?>
							</div>
						</div>
					</div>

					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Question and thank-you', 'like-dislike-button' ); ?></h2>
						<?php
						$ldbtn_text( 'question', __( 'Question above the buttons', 'like-dislike-button' ), __( 'Was this article helpful?', 'like-dislike-button' ), __( 'Optional. Good for documentation and help articles.', 'like-dislike-button' ) );
						$ldbtn_text( 'thanks', __( 'Message after voting', 'like-dislike-button' ), __( 'Thanks for your feedback!', 'like-dislike-button' ) );
						?>
					</div>

					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Colours', 'like-dislike-button' ); ?></h2>
						<p class="ldbtn-card__intro"><?php esc_html_e( 'Leave empty to use colours that suit the icon set and your theme. Text and background follow your theme by default, so light and dark themes both work.', 'like-dislike-button' ); ?></p>
						<div class="ldbtn-grid ldbtn-grid--colors">
							<?php
							foreach (
								array(
									'like_color'    => __( 'Like', 'like-dislike-button' ),
									'dislike_color' => __( 'Dislike', 'like-dislike-button' ),
									'text_color'    => __( 'Text', 'like-dislike-button' ),
									'bg_color'      => __( 'Button background', 'like-dislike-button' ),
								) as $ldbtn_key => $ldbtn_label
							) :
								?>
								<div class="ldbtn-field">
									<label class="ldbtn-field__label" for="ldbtn-<?php echo esc_attr( $ldbtn_key ); ?>"><?php echo esc_html( $ldbtn_label ); ?></label>
									<input type="text" class="ldbtn-color" id="ldbtn-<?php echo esc_attr( $ldbtn_key ); ?>" name="<?php echo esc_attr( $ldbtn_name( $ldbtn_key ) ); ?>" value="<?php echo esc_attr( $ldbtn_s[ $ldbtn_key ] ); ?>">
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</section>

				<section class="ldbtn-panel" role="tabpanel" id="ldbtn-tab-placement" aria-labelledby="ldbtn-tab-btn-placement" hidden>
					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Where buttons appear', 'like-dislike-button' ); ?></h2>
						<fieldset class="ldbtn-field">
							<legend class="ldbtn-field__label"><?php esc_html_e( 'Content types', 'like-dislike-button' ); ?></legend>
							<div class="ldbtn-checks">
								<?php foreach ( $ldbtn_types as $ldbtn_type ) : ?>
									<label><input type="checkbox" name="<?php echo esc_attr( $ldbtn_name( 'post_types' ) ); ?>[]" value="<?php echo esc_attr( $ldbtn_type->name ); ?>" <?php checked( LDBTN_Settings::type_enabled( $ldbtn_type->name ) ); ?>> <?php echo esc_html( $ldbtn_type->labels->name ); ?></label>
								<?php endforeach; ?>
							</div>
							<p class="ldbtn-help"><?php esc_html_e( 'WooCommerce products show the buttons in the product summary. Each post also has a box in the editor to hide them.', 'like-dislike-button' ); ?></p>
						</fieldset>
						<?php
						$ldbtn_toggle( 'comments', __( 'Add small buttons under each comment', 'like-dislike-button' ) );
						$ldbtn_segments(
							'position',
							__( 'Position', 'like-dislike-button' ),
							array(
								'after'  => __( 'After content', 'like-dislike-button' ),
								'before' => __( 'Before content', 'like-dislike-button' ),
								'both'   => __( 'Both', 'like-dislike-button' ),
								'manual' => __( 'Only where I place them', 'like-dislike-button' ),
							),
							__( '“Only where I place them” turns off automatic placement. Use the block, the shortcode or the template tag instead.', 'like-dislike-button' )
						);
						$ldbtn_toggle( 'archives', __( 'Also show on blog and archive pages that display full posts', 'like-dislike-button' ), __( 'Block themes can add the Like Dislike Buttons block to a Query Loop instead.', 'like-dislike-button' ) );
						?>
					</div>

					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Which buttons', 'like-dislike-button' ); ?></h2>
						<?php
						$ldbtn_segments(
							'buttons',
							__( 'Buttons', 'like-dislike-button' ),
							array(
								'both'    => __( 'Like and dislike', 'like-dislike-button' ),
								'like'    => __( 'Like only', 'like-dislike-button' ),
								'dislike' => __( 'Dislike only', 'like-dislike-button' ),
							)
						);
						$ldbtn_segments(
							'order',
							__( 'Order', 'like-dislike-button' ),
							array(
								'like_first'    => __( 'Like first', 'like-dislike-button' ),
								'dislike_first' => __( 'Dislike first', 'like-dislike-button' ),
							)
						);
						?>
					</div>

					<div class="ldbtn-card ldbtn-card--muted">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Placing buttons by hand', 'like-dislike-button' ); ?></h2>
						<dl class="ldbtn-reference">
							<dt><?php esc_html_e( 'Block', 'like-dislike-button' ); ?></dt>
							<dd><?php esc_html_e( 'Like Dislike Buttons and Most Liked Posts, in the Widgets category. Both work in the site editor and inside Query Loop.', 'like-dislike-button' ); ?></dd>
							<dt><?php esc_html_e( 'Shortcode', 'like-dislike-button' ); ?></dt>
							<dd><code>[like_dislike_button]</code> <code>[like_dislike_button id="42" icons="hearts" style="pill"]</code> <code>[ldbtn_most_liked number="5" period="month"]</code></dd>
							<dt><?php esc_html_e( 'Theme template', 'like-dislike-button' ); ?></dt>
							<dd><code>&lt;?php if ( function_exists( 'ldbtn_buttons' ) ) { ldbtn_buttons(); } ?&gt;</code></dd>
						</dl>
					</div>
				</section>

				<section class="ldbtn-panel" role="tabpanel" id="ldbtn-tab-voting" aria-labelledby="ldbtn-tab-btn-voting" hidden>
					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Who can vote', 'like-dislike-button' ); ?></h2>
						<?php
						$ldbtn_segments(
							'who',
							__( 'Voters', 'like-dislike-button' ),
							array(
								'everyone'  => __( 'Everyone', 'like-dislike-button' ),
								'logged_in' => __( 'Logged-in users only', 'like-dislike-button' ),
							),
							__( 'Visitors who can’t vote still see the buttons and counts, and get a link to log in when they click.', 'like-dislike-button' )
						);
						?>
						<fieldset class="ldbtn-field" data-show-when="who=logged_in">
							<legend class="ldbtn-field__label"><?php esc_html_e( 'Only these roles (leave empty for all)', 'like-dislike-button' ); ?></legend>
							<div class="ldbtn-checks">
								<?php foreach ( wp_roles()->get_names() as $ldbtn_role => $ldbtn_role_name ) : ?>
									<label><input type="checkbox" name="<?php echo esc_attr( $ldbtn_name( 'roles' ) ); ?>[]" value="<?php echo esc_attr( $ldbtn_role ); ?>" <?php checked( in_array( $ldbtn_role, (array) $ldbtn_s['roles'], true ) ); ?>> <?php echo esc_html( translate_user_role( $ldbtn_role_name ) ); ?></label>
								<?php endforeach; ?>
							</div>
						</fieldset>
						<?php $ldbtn_toggle( 'allow_change', __( 'Let people change or remove their vote', 'like-dislike-button' ), __( 'Clicking the same button again removes the vote; clicking the other one switches it.', 'like-dislike-button' ) ); ?>
					</div>

					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'One vote per visitor', 'like-dislike-button' ); ?></h2>
						<p class="ldbtn-card__intro"><?php esc_html_e( 'Logged-in users always get one vote per item, tied to their account. For visitors, choose how to recognise them:', 'like-dislike-button' ); ?></p>
						<fieldset class="ldbtn-field">
							<legend class="screen-reader-text"><?php esc_html_e( 'Visitor tracking', 'like-dislike-button' ); ?></legend>
							<div class="ldbtn-options">
								<label class="ldbtn-option">
									<input type="radio" name="<?php echo esc_attr( $ldbtn_name( 'guest_tracking' ) ); ?>" value="cookie" <?php checked( $ldbtn_s['guest_tracking'], 'cookie' ); ?>>
									<span><strong><?php esc_html_e( 'Per browser (recommended)', 'like-dislike-button' ); ?></strong>
									<?php esc_html_e( 'A random ID is stored in a cookie when someone votes. People sharing an office or school network can each vote.', 'like-dislike-button' ); ?></span>
								</label>
								<label class="ldbtn-option">
									<input type="radio" name="<?php echo esc_attr( $ldbtn_name( 'guest_tracking' ) ); ?>" value="ip" <?php checked( $ldbtn_s['guest_tracking'], 'ip' ); ?>>
									<span><strong><?php esc_html_e( 'Per IP address', 'like-dislike-button' ); ?></strong>
									<?php esc_html_e( 'Harder to get around by clearing cookies, but everyone on a shared network counts as one voter.', 'like-dislike-button' ); ?></span>
								</label>
							</div>
							<p class="ldbtn-help"><?php esc_html_e( 'IP addresses are never stored, only a keyed one-way hash. Votes are also limited to 30 per minute per network.', 'like-dislike-button' ); ?></p>
						</fieldset>
						<?php
						$ldbtn_select(
							'ip_header',
							__( 'Visitor IP comes from', 'like-dislike-button' ),
							array(
								''                      => __( 'The connection (default)', 'like-dislike-button' ),
								'HTTP_CF_CONNECTING_IP' => __( 'Cloudflare header (CF-Connecting-IP)', 'like-dislike-button' ),
								'HTTP_X_FORWARDED_FOR'  => __( 'Proxy header (X-Forwarded-For)', 'like-dislike-button' ),
								'HTTP_X_REAL_IP'        => __( 'Proxy header (X-Real-IP)', 'like-dislike-button' ),
							),
							__( 'Change this only if your site is behind Cloudflare or a proxy and every visitor shows the same IP. Headers can be faked when the site isn’t behind that proxy.', 'like-dislike-button' )
						);
						?>
					</div>
				</section>

				<section class="ldbtn-panel" role="tabpanel" id="ldbtn-tab-advanced" aria-labelledby="ldbtn-tab-btn-advanced" hidden>
					<div class="ldbtn-card">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'Advanced', 'like-dislike-button' ); ?></h2>
						<?php
						$ldbtn_toggle( 'schema', __( 'Add like counts to structured data (schema.org)', 'like-dislike-button' ), __( 'Adds interactionStatistic (LikeAction and DislikeAction counts) to the page’s existing Article or WebPage markup. Works with Yoast SEO, Rank Math, All in One SEO and themes that print their own JSON-LD. Only added when vote results are visible (counts, percentages or score), because search engines expect marked-up data to be visible.', 'like-dislike-button' ) );
						$ldbtn_toggle( 'rest_field', __( 'Add like and dislike totals to the REST API', 'like-dislike-button' ), __( 'Adds an ldbtn_votes field to enabled post types, for headless sites and apps.', 'like-dislike-button' ) );
						$ldbtn_toggle( 'compat_shortcodes', __( 'Keep shortcodes from other like plugins working', 'like-dislike-button' ), __( 'Shows these buttons wherever [posts_like_dislike], [like_dislike] or [wp_ulike] appear, as long as those plugins are inactive. Switched on automatically after an import.', 'like-dislike-button' ) );
						$ldbtn_toggle( 'delete_data', __( 'Delete all votes and settings when the plugin is deleted', 'like-dislike-button' ) );
						?>
					</div>

					<div class="ldbtn-card ldbtn-card--muted">
						<h2 class="ldbtn-card__title"><?php esc_html_e( 'For developers', 'like-dislike-button' ); ?></h2>
						<dl class="ldbtn-reference">
							<dt><?php esc_html_e( 'Template tags', 'like-dislike-button' ); ?></dt>
							<dd><code>ldbtn_buttons( $post_id, $args )</code> <code>ldbtn_get_buttons()</code> <code>ldbtn_get_counts( $id, 'post' )</code></dd>
							<dt><?php esc_html_e( 'REST API', 'like-dislike-button' ); ?></dt>
							<dd><code>GET /wp-json/ldbtn/v1/state?items=post:1,comment:2</code> <code>POST /wp-json/ldbtn/v1/vote</code></dd>
							<dt><?php esc_html_e( 'Actions', 'like-dislike-button' ); ?></dt>
							<dd><code>ldbtn_vote_recorded</code></dd>
							<dt><?php esc_html_e( 'Filters', 'like-dislike-button' ); ?></dt>
							<dd><code>ldbtn_buttons_html</code> <code>ldbtn_can_vote</code> <code>ldbtn_icon_sets</code> <code>ldbtn_visitor_ip</code> <code>ldbtn_rate_limit</code> <code>ldbtn_schema_statistics</code></dd>
							<dt><?php esc_html_e( 'CSS', 'like-dislike-button' ); ?></dt>
							<dd><code>.ldbtn { --ldbtn-like: #2563eb; --ldbtn-dislike: #dc2626; --ldbtn-radius: .6em; }</code></dd>
							<dt>WP-CLI</dt>
							<dd><code>wp ldbtn stats</code> <code>wp ldbtn recount</code> <code>wp ldbtn import wp-ulike</code> <code>wp ldbtn reset --post=42</code></dd>
						</dl>
					</div>
				</section>

				<div class="ldbtn-savebar">
					<?php submit_button( __( 'Save settings', 'like-dislike-button' ), 'primary', 'submit', false ); ?>
				</div>
			</div>

			<aside class="ldbtn-aside" aria-label="<?php esc_attr_e( 'Live preview', 'like-dislike-button' ); ?>">
				<div class="ldbtn-preview-card">
					<div class="ldbtn-preview-card__head">
						<span class="ldbtn-preview-card__title"><?php esc_html_e( 'Live preview', 'like-dislike-button' ); ?></span>
						<div class="ldbtn-preview-bg" role="radiogroup" aria-label="<?php esc_attr_e( 'Preview background', 'like-dislike-button' ); ?>">
							<button type="button" role="radio" aria-checked="true" data-bg="light"><?php esc_html_e( 'Light', 'like-dislike-button' ); ?></button>
							<button type="button" role="radio" aria-checked="false" data-bg="dark"><?php esc_html_e( 'Dark', 'like-dislike-button' ); ?></button>
							<button type="button" role="radio" aria-checked="false" data-bg="image"><?php esc_html_e( 'Image', 'like-dislike-button' ); ?></button>
						</div>
					</div>
					<div class="ldbtn-preview-stage" data-bg="light">
						<div class="ldbtn-preview-article" aria-hidden="true">
							<span></span><span></span><span></span>
						</div>
						<div id="ldbtn-preview">
							<?php echo LDBTN_Admin::preview_html( $ldbtn_s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building. ?>
						</div>
					</div>
					<p class="ldbtn-preview-card__note"><?php esc_html_e( 'Click the buttons to try them. Nothing is recorded here.', 'like-dislike-button' ); ?></p>
				</div>
			</aside>
		</div>
	</form>
</div>
