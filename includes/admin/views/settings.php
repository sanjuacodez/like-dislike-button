<?php
/**
 * Settings screen.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

$ldb_s    = LDB_Settings::all();
$ldb_name = function ( $key ) {
	return LDB_Settings::OPTION . '[' . $key . ']';
};

/**
 * Prints an on/off switch.
 */
$ldb_toggle = function ( $key, $label, $help = '' ) use ( $ldb_s, $ldb_name ) {
	?>
	<div class="ldb-field ldb-field--toggle">
		<label class="ldb-switch">
			<input type="checkbox" name="<?php echo esc_attr( $ldb_name( $key ) ); ?>" value="1" <?php checked( ! empty( $ldb_s[ $key ] ) ); ?>>
			<span class="ldb-switch__track" aria-hidden="true"></span>
			<span class="ldb-switch__label"><?php echo esc_html( $label ); ?></span>
		</label>
		<?php if ( $help ) : ?>
			<p class="ldb-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

/**
 * Prints a segmented radio group.
 */
$ldb_segments = function ( $key, $label, $options, $help = '' ) use ( $ldb_s, $ldb_name ) {
	?>
	<fieldset class="ldb-field">
		<legend class="ldb-field__label"><?php echo esc_html( $label ); ?></legend>
		<div class="ldb-segments">
			<?php foreach ( $options as $value => $text ) : ?>
				<label>
					<input type="radio" name="<?php echo esc_attr( $ldb_name( $key ) ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $ldb_s[ $key ], $value ); ?>>
					<span><?php echo esc_html( $text ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php if ( $help ) : ?>
			<p class="ldb-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</fieldset>
	<?php
};

/**
 * Prints a text input.
 */
$ldb_text = function ( $key, $label, $placeholder = '', $help = '' ) use ( $ldb_s, $ldb_name ) {
	$id = 'ldb-' . str_replace( '_', '-', $key );
	?>
	<div class="ldb-field">
		<label class="ldb-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<input type="text" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $ldb_name( $key ) ); ?>" value="<?php echo esc_attr( $ldb_s[ $key ] ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>">
		<?php if ( $help ) : ?>
			<p class="ldb-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

/**
 * Prints a select.
 */
$ldb_select = function ( $key, $label, $options, $help = '' ) use ( $ldb_s, $ldb_name ) {
	$id = 'ldb-' . str_replace( '_', '-', $key );
	?>
	<div class="ldb-field">
		<label class="ldb-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $ldb_name( $key ) ); ?>">
			<?php foreach ( $options as $value => $text ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $ldb_s[ $key ], $value ); ?>><?php echo esc_html( $text ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( $help ) : ?>
			<p class="ldb-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

$ldb_types = get_post_types( array( 'public' => true ), 'objects' );
unset( $ldb_types['attachment'] );
?>
<div class="wrap ldb-admin">
	<?php LDB_Admin::header( 'ldb-settings' ); ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Like Dislike Settings', 'like-dislike-button' ); ?></h1>
	<?php settings_errors(); ?>

	<nav class="ldb-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'like-dislike-button' ); ?>">
		<button type="button" role="tab" class="ldb-tab" aria-selected="true" aria-controls="ldb-tab-appearance" id="ldb-tab-btn-appearance"><?php esc_html_e( 'Appearance', 'like-dislike-button' ); ?></button>
		<button type="button" role="tab" class="ldb-tab" aria-selected="false" aria-controls="ldb-tab-placement" id="ldb-tab-btn-placement"><?php esc_html_e( 'Placement', 'like-dislike-button' ); ?></button>
		<button type="button" role="tab" class="ldb-tab" aria-selected="false" aria-controls="ldb-tab-voting" id="ldb-tab-btn-voting"><?php esc_html_e( 'Voting', 'like-dislike-button' ); ?></button>
		<button type="button" role="tab" class="ldb-tab" aria-selected="false" aria-controls="ldb-tab-advanced" id="ldb-tab-btn-advanced"><?php esc_html_e( 'Advanced', 'like-dislike-button' ); ?></button>
	</nav>

	<form method="post" action="options.php" id="ldb-settings-form">
		<?php settings_fields( 'ldb_settings' ); ?>

		<div class="ldb-layout">
			<div class="ldb-main">

				<section class="ldb-panel" role="tabpanel" id="ldb-tab-appearance" aria-labelledby="ldb-tab-btn-appearance">
					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Start from a preset', 'like-dislike-button' ); ?></h2>
						<p class="ldb-card__intro"><?php esc_html_e( 'Presets fill in the options below. Adjust anything afterwards, then save.', 'like-dislike-button' ); ?></p>
						<div class="ldb-presets">
							<?php foreach ( LDB_Admin::presets() as $ldb_slug => $ldb_preset ) : ?>
								<button type="button" class="ldb-preset" data-preset="<?php echo esc_attr( $ldb_slug ); ?>">
									<span class="ldb-preset__icons ldb--icons-<?php echo esc_attr( $ldb_preset['values']['icon_set'] ); ?>" aria-hidden="true">
										<?php
										echo LDB_Icons::get( $ldb_preset['values']['icon_set'], 'like' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from kses-filtered SVG.
										if ( 'both' === $ldb_preset['values']['buttons'] ) {
											echo LDB_Icons::get( $ldb_preset['values']['icon_set'], 'dislike' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from kses-filtered SVG.
										}
										?>
									</span>
									<span class="ldb-preset__name"><?php echo esc_html( $ldb_preset['label'] ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Icons', 'like-dislike-button' ); ?></h2>
						<fieldset class="ldb-field">
							<legend class="screen-reader-text"><?php esc_html_e( 'Icon set', 'like-dislike-button' ); ?></legend>
							<div class="ldb-choices ldb-choices--icons">
								<?php foreach ( LDB_Icons::sets() as $ldb_slug => $ldb_set ) : ?>
									<label class="ldb-choice">
										<input type="radio" name="<?php echo esc_attr( $ldb_name( 'icon_set' ) ); ?>" value="<?php echo esc_attr( $ldb_slug ); ?>" <?php checked( $ldb_s['icon_set'], $ldb_slug ); ?>>
										<span class="ldb-choice__body">
											<span class="ldb-choice__icons ldb--icons-<?php echo esc_attr( $ldb_slug ); ?>">
												<?php
												if ( 'none' === $ldb_slug ) {
													echo '<span class="ldb-choice__text">Aa</span>';
												} elseif ( 'custom' === $ldb_slug ) {
													echo '<span class="dashicons dashicons-format-image" aria-hidden="true"></span>';
												} else {
													echo LDB_Icons::get( $ldb_slug, 'like' ) . LDB_Icons::get( $ldb_slug, 'dislike' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from kses-filtered SVG.
												}
												?>
											</span>
											<span class="ldb-choice__name"><?php echo esc_html( $ldb_set['label'] ); ?></span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<div class="ldb-custom-icons" data-show-when="icon_set=custom">
							<?php foreach ( array( 'like' => __( 'Like image', 'like-dislike-button' ), 'dislike' => __( 'Dislike image', 'like-dislike-button' ) ) as $ldb_reaction => $ldb_label ) : ?>
								<?php
								$ldb_key = 'custom_' . $ldb_reaction . '_icon';
								$ldb_src = $ldb_s[ $ldb_key ] ? wp_get_attachment_image_url( $ldb_s[ $ldb_key ], 'thumbnail' ) : '';
								?>
								<div class="ldb-media">
									<span class="ldb-media__thumb"><?php echo $ldb_src ? '<img src="' . esc_url( $ldb_src ) . '" alt="">' : ''; ?></span>
									<span class="ldb-media__label"><?php echo esc_html( $ldb_label ); ?></span>
									<input type="hidden" name="<?php echo esc_attr( $ldb_name( $ldb_key ) ); ?>" value="<?php echo esc_attr( $ldb_s[ $ldb_key ] ); ?>">
									<button type="button" class="button ldb-media__pick"><?php esc_html_e( 'Choose', 'like-dislike-button' ); ?></button>
									<button type="button" class="button-link ldb-media__clear"><?php esc_html_e( 'Remove', 'like-dislike-button' ); ?></button>
								</div>
							<?php endforeach; ?>
							<p class="ldb-help"><?php esc_html_e( 'Square PNG, SVG or WebP images work best. They are shown at the button’s icon size.', 'like-dislike-button' ); ?></p>
						</div>
					</div>

					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Buttons', 'like-dislike-button' ); ?></h2>
						<fieldset class="ldb-field">
							<legend class="ldb-field__label"><?php esc_html_e( 'Style', 'like-dislike-button' ); ?></legend>
							<div class="ldb-choices ldb-choices--styles">
								<?php foreach ( LDB_Blocks::style_labels() as $ldb_slug => $ldb_label ) : ?>
									<label class="ldb-choice">
										<input type="radio" name="<?php echo esc_attr( $ldb_name( 'style' ) ); ?>" value="<?php echo esc_attr( $ldb_slug ); ?>" <?php checked( $ldb_s['style'], $ldb_slug ); ?>>
										<span class="ldb-choice__body">
											<span class="ldb-choice__sample ldb-sample--<?php echo esc_attr( $ldb_slug ); ?>" aria-hidden="true"><i></i><i></i></span>
											<span class="ldb-choice__name"><?php echo esc_html( $ldb_label ); ?></span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<div class="ldb-grid">
							<?php
							$ldb_segments(
								'size',
								__( 'Size', 'like-dislike-button' ),
								array(
									'sm' => __( 'Small', 'like-dislike-button' ),
									'md' => __( 'Medium', 'like-dislike-button' ),
									'lg' => __( 'Large', 'like-dislike-button' ),
								)
							);
							$ldb_segments(
								'align',
								__( 'Alignment', 'like-dislike-button' ),
								array(
									'left'   => __( 'Left', 'like-dislike-button' ),
									'center' => __( 'Center', 'like-dislike-button' ),
									'right'  => __( 'Right', 'like-dislike-button' ),
								)
							);
							$ldb_segments(
								'layout',
								__( 'Layout', 'like-dislike-button' ),
								array(
									'row'   => __( 'Side by side', 'like-dislike-button' ),
									'stack' => __( 'Stacked', 'like-dislike-button' ),
								)
							);
							$ldb_segments(
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

					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Labels and counts', 'like-dislike-button' ); ?></h2>
						<?php $ldb_toggle( 'show_labels', __( 'Show text labels next to the icons', 'like-dislike-button' ), __( 'Screen readers always hear the labels, even when they are hidden.', 'like-dislike-button' ) ); ?>
						<div class="ldb-grid">
							<?php
							$ldb_text( 'like_label', __( 'Like label', 'like-dislike-button' ), __( 'Like', 'like-dislike-button' ) );
							$ldb_text( 'dislike_label', __( 'Dislike label', 'like-dislike-button' ), __( 'Dislike', 'like-dislike-button' ) );
							?>
						</div>
						<div class="ldb-grid">
							<?php
							$ldb_select( 'counts', __( 'Counts', 'like-dislike-button' ), LDB_Blocks::count_labels() );
							?>
							<div class="ldb-field-stack">
								<?php
								$ldb_toggle( 'short_numbers', __( 'Shorten large numbers (1.2K)', 'like-dislike-button' ) );
								$ldb_toggle( 'ratio_bar', __( 'Show a like ratio bar', 'like-dislike-button' ) );
								?>
							</div>
						</div>
					</div>

					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Question and thank-you', 'like-dislike-button' ); ?></h2>
						<?php
						$ldb_text( 'question', __( 'Question above the buttons', 'like-dislike-button' ), __( 'Was this article helpful?', 'like-dislike-button' ), __( 'Optional. Good for documentation and help articles.', 'like-dislike-button' ) );
						$ldb_text( 'thanks', __( 'Message after voting', 'like-dislike-button' ), __( 'Thanks for your feedback!', 'like-dislike-button' ) );
						?>
					</div>

					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Colours', 'like-dislike-button' ); ?></h2>
						<p class="ldb-card__intro"><?php esc_html_e( 'Leave empty to use colours that suit the icon set and your theme. Text and background follow your theme by default, so light and dark themes both work.', 'like-dislike-button' ); ?></p>
						<div class="ldb-grid ldb-grid--colors">
							<?php
							foreach (
								array(
									'like_color'    => __( 'Like', 'like-dislike-button' ),
									'dislike_color' => __( 'Dislike', 'like-dislike-button' ),
									'text_color'    => __( 'Text', 'like-dislike-button' ),
									'bg_color'      => __( 'Button background', 'like-dislike-button' ),
								) as $ldb_key => $ldb_label
							) :
								?>
								<div class="ldb-field">
									<label class="ldb-field__label" for="ldb-<?php echo esc_attr( $ldb_key ); ?>"><?php echo esc_html( $ldb_label ); ?></label>
									<input type="text" class="ldb-color" id="ldb-<?php echo esc_attr( $ldb_key ); ?>" name="<?php echo esc_attr( $ldb_name( $ldb_key ) ); ?>" value="<?php echo esc_attr( $ldb_s[ $ldb_key ] ); ?>">
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</section>

				<section class="ldb-panel" role="tabpanel" id="ldb-tab-placement" aria-labelledby="ldb-tab-btn-placement" hidden>
					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Where buttons appear', 'like-dislike-button' ); ?></h2>
						<fieldset class="ldb-field">
							<legend class="ldb-field__label"><?php esc_html_e( 'Content types', 'like-dislike-button' ); ?></legend>
							<div class="ldb-checks">
								<?php foreach ( $ldb_types as $ldb_type ) : ?>
									<label><input type="checkbox" name="<?php echo esc_attr( $ldb_name( 'post_types' ) ); ?>[]" value="<?php echo esc_attr( $ldb_type->name ); ?>" <?php checked( LDB_Settings::type_enabled( $ldb_type->name ) ); ?>> <?php echo esc_html( $ldb_type->labels->name ); ?></label>
								<?php endforeach; ?>
							</div>
							<p class="ldb-help"><?php esc_html_e( 'WooCommerce products show the buttons in the product summary. Each post also has a box in the editor to hide them.', 'like-dislike-button' ); ?></p>
						</fieldset>
						<?php
						$ldb_toggle( 'comments', __( 'Add small buttons under each comment', 'like-dislike-button' ) );
						$ldb_segments(
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
						$ldb_toggle( 'archives', __( 'Also show on blog and archive pages that display full posts', 'like-dislike-button' ), __( 'Block themes can add the Like Dislike Buttons block to a Query Loop instead.', 'like-dislike-button' ) );
						?>
					</div>

					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Which buttons', 'like-dislike-button' ); ?></h2>
						<?php
						$ldb_segments(
							'buttons',
							__( 'Buttons', 'like-dislike-button' ),
							array(
								'both'    => __( 'Like and dislike', 'like-dislike-button' ),
								'like'    => __( 'Like only', 'like-dislike-button' ),
								'dislike' => __( 'Dislike only', 'like-dislike-button' ),
							)
						);
						$ldb_segments(
							'order',
							__( 'Order', 'like-dislike-button' ),
							array(
								'like_first'    => __( 'Like first', 'like-dislike-button' ),
								'dislike_first' => __( 'Dislike first', 'like-dislike-button' ),
							)
						);
						?>
					</div>

					<div class="ldb-card ldb-card--muted">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Placing buttons by hand', 'like-dislike-button' ); ?></h2>
						<dl class="ldb-reference">
							<dt><?php esc_html_e( 'Block', 'like-dislike-button' ); ?></dt>
							<dd><?php esc_html_e( 'Like Dislike Buttons and Most Liked Posts, in the Widgets category. Both work in the site editor and inside Query Loop.', 'like-dislike-button' ); ?></dd>
							<dt><?php esc_html_e( 'Shortcode', 'like-dislike-button' ); ?></dt>
							<dd><code>[like_dislike_button]</code> <code>[like_dislike_button id="42" icons="hearts" style="pill"]</code> <code>[ldb_most_liked number="5" period="month"]</code></dd>
							<dt><?php esc_html_e( 'Theme template', 'like-dislike-button' ); ?></dt>
							<dd><code>&lt;?php if ( function_exists( 'ldb_buttons' ) ) { ldb_buttons(); } ?&gt;</code></dd>
						</dl>
					</div>
				</section>

				<section class="ldb-panel" role="tabpanel" id="ldb-tab-voting" aria-labelledby="ldb-tab-btn-voting" hidden>
					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Who can vote', 'like-dislike-button' ); ?></h2>
						<?php
						$ldb_segments(
							'who',
							__( 'Voters', 'like-dislike-button' ),
							array(
								'everyone'  => __( 'Everyone', 'like-dislike-button' ),
								'logged_in' => __( 'Logged-in users only', 'like-dislike-button' ),
							),
							__( 'Visitors who can’t vote still see the buttons and counts, and get a link to log in when they click.', 'like-dislike-button' )
						);
						?>
						<fieldset class="ldb-field" data-show-when="who=logged_in">
							<legend class="ldb-field__label"><?php esc_html_e( 'Only these roles (leave empty for all)', 'like-dislike-button' ); ?></legend>
							<div class="ldb-checks">
								<?php foreach ( wp_roles()->get_names() as $ldb_role => $ldb_role_name ) : ?>
									<label><input type="checkbox" name="<?php echo esc_attr( $ldb_name( 'roles' ) ); ?>[]" value="<?php echo esc_attr( $ldb_role ); ?>" <?php checked( in_array( $ldb_role, (array) $ldb_s['roles'], true ) ); ?>> <?php echo esc_html( translate_user_role( $ldb_role_name ) ); ?></label>
								<?php endforeach; ?>
							</div>
						</fieldset>
						<?php $ldb_toggle( 'allow_change', __( 'Let people change or remove their vote', 'like-dislike-button' ), __( 'Clicking the same button again removes the vote; clicking the other one switches it.', 'like-dislike-button' ) ); ?>
					</div>

					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'One vote per visitor', 'like-dislike-button' ); ?></h2>
						<p class="ldb-card__intro"><?php esc_html_e( 'Logged-in users always get one vote per item, tied to their account. For visitors, choose how to recognise them:', 'like-dislike-button' ); ?></p>
						<fieldset class="ldb-field">
							<legend class="screen-reader-text"><?php esc_html_e( 'Visitor tracking', 'like-dislike-button' ); ?></legend>
							<div class="ldb-options">
								<label class="ldb-option">
									<input type="radio" name="<?php echo esc_attr( $ldb_name( 'guest_tracking' ) ); ?>" value="cookie" <?php checked( $ldb_s['guest_tracking'], 'cookie' ); ?>>
									<span><strong><?php esc_html_e( 'Per browser (recommended)', 'like-dislike-button' ); ?></strong>
									<?php esc_html_e( 'A random ID is stored in a cookie when someone votes. People sharing an office or school network can each vote.', 'like-dislike-button' ); ?></span>
								</label>
								<label class="ldb-option">
									<input type="radio" name="<?php echo esc_attr( $ldb_name( 'guest_tracking' ) ); ?>" value="ip" <?php checked( $ldb_s['guest_tracking'], 'ip' ); ?>>
									<span><strong><?php esc_html_e( 'Per IP address', 'like-dislike-button' ); ?></strong>
									<?php esc_html_e( 'Harder to get around by clearing cookies, but everyone on a shared network counts as one voter.', 'like-dislike-button' ); ?></span>
								</label>
							</div>
							<p class="ldb-help"><?php esc_html_e( 'IP addresses are never stored, only a keyed one-way hash. Votes are also limited to 30 per minute per network.', 'like-dislike-button' ); ?></p>
						</fieldset>
						<?php
						$ldb_select(
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

				<section class="ldb-panel" role="tabpanel" id="ldb-tab-advanced" aria-labelledby="ldb-tab-btn-advanced" hidden>
					<div class="ldb-card">
						<h2 class="ldb-card__title"><?php esc_html_e( 'Advanced', 'like-dislike-button' ); ?></h2>
						<?php
						$ldb_toggle( 'schema', __( 'Add like counts to structured data (schema.org)', 'like-dislike-button' ), __( 'Adds interactionStatistic (LikeAction and DislikeAction counts) to the page’s existing Article or WebPage markup. Works with Yoast SEO, Rank Math, All in One SEO and themes that print their own JSON-LD. Only added when vote results are visible (counts, percentages or score), because search engines expect marked-up data to be visible.', 'like-dislike-button' ) );
						$ldb_toggle( 'rest_field', __( 'Add like and dislike totals to the REST API', 'like-dislike-button' ), __( 'Adds an ldb_votes field to enabled post types, for headless sites and apps.', 'like-dislike-button' ) );
						$ldb_toggle( 'compat_shortcodes', __( 'Keep shortcodes from other like plugins working', 'like-dislike-button' ), __( 'Shows these buttons wherever [posts_like_dislike], [like_dislike] or [wp_ulike] appear, as long as those plugins are inactive. Switched on automatically after an import.', 'like-dislike-button' ) );
						$ldb_toggle( 'delete_data', __( 'Delete all votes and settings when the plugin is deleted', 'like-dislike-button' ) );
						?>
					</div>

					<div class="ldb-card ldb-card--muted">
						<h2 class="ldb-card__title"><?php esc_html_e( 'For developers', 'like-dislike-button' ); ?></h2>
						<dl class="ldb-reference">
							<dt><?php esc_html_e( 'Template tags', 'like-dislike-button' ); ?></dt>
							<dd><code>ldb_buttons( $post_id, $args )</code> <code>ldb_get_buttons()</code> <code>ldb_get_counts( $id, 'post' )</code></dd>
							<dt><?php esc_html_e( 'REST API', 'like-dislike-button' ); ?></dt>
							<dd><code>GET /wp-json/ldb/v1/state?items=post:1,comment:2</code> <code>POST /wp-json/ldb/v1/vote</code></dd>
							<dt><?php esc_html_e( 'Actions', 'like-dislike-button' ); ?></dt>
							<dd><code>ldb_vote_recorded</code></dd>
							<dt><?php esc_html_e( 'Filters', 'like-dislike-button' ); ?></dt>
							<dd><code>ldb_buttons_html</code> <code>ldb_can_vote</code> <code>ldb_icon_sets</code> <code>ldb_visitor_ip</code> <code>ldb_rate_limit</code> <code>ldb_schema_statistics</code></dd>
							<dt><?php esc_html_e( 'CSS', 'like-dislike-button' ); ?></dt>
							<dd><code>.ldb { --ldb-like: #2563eb; --ldb-dislike: #dc2626; --ldb-radius: .6em; }</code></dd>
							<dt>WP-CLI</dt>
							<dd><code>wp ldb stats</code> <code>wp ldb recount</code> <code>wp ldb import wp-ulike</code> <code>wp ldb reset --post=42</code></dd>
						</dl>
					</div>
				</section>

				<div class="ldb-savebar">
					<?php submit_button( __( 'Save settings', 'like-dislike-button' ), 'primary', 'submit', false ); ?>
				</div>
			</div>

			<aside class="ldb-aside" aria-label="<?php esc_attr_e( 'Live preview', 'like-dislike-button' ); ?>">
				<div class="ldb-preview-card">
					<div class="ldb-preview-card__head">
						<span class="ldb-preview-card__title"><?php esc_html_e( 'Live preview', 'like-dislike-button' ); ?></span>
						<div class="ldb-preview-bg" role="radiogroup" aria-label="<?php esc_attr_e( 'Preview background', 'like-dislike-button' ); ?>">
							<button type="button" role="radio" aria-checked="true" data-bg="light"><?php esc_html_e( 'Light', 'like-dislike-button' ); ?></button>
							<button type="button" role="radio" aria-checked="false" data-bg="dark"><?php esc_html_e( 'Dark', 'like-dislike-button' ); ?></button>
							<button type="button" role="radio" aria-checked="false" data-bg="image"><?php esc_html_e( 'Image', 'like-dislike-button' ); ?></button>
						</div>
					</div>
					<div class="ldb-preview-stage" data-bg="light">
						<div class="ldb-preview-article" aria-hidden="true">
							<span></span><span></span><span></span>
						</div>
						<div id="ldb-preview">
							<?php echo LDB_Admin::preview_html( $ldb_s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building. ?>
						</div>
					</div>
					<p class="ldb-preview-card__note"><?php esc_html_e( 'Click the buttons to try them. Nothing is recorded here.', 'like-dislike-button' ); ?></p>
				</div>
			</aside>
		</div>
	</form>
</div>
