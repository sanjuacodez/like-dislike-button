<?php
/**
 * Stacked column chart of likes and dislikes, as inline SVG.
 *
 * Expects $ldbtn_series: list of [label, likes, dislikes, key].
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

if ( ! $ldbtn_series ) {
	echo '<p class="ldbtn-empty-line">' . esc_html__( 'No votes in this period.', 'like-dislike-button' ) . '</p>';
	return;
}

$ldbtn_w      = 1100;
$ldbtn_h      = 300;
$ldbtn_left   = 40;
$ldbtn_right  = 6;
$ldbtn_top    = 10;
$ldbtn_bottom = 26;
$ldbtn_plot_w = $ldbtn_w - $ldbtn_left - $ldbtn_right;
$ldbtn_plot_h = $ldbtn_h - $ldbtn_top - $ldbtn_bottom;
$ldbtn_n      = count( $ldbtn_series );
$ldbtn_max    = 0;
foreach ( $ldbtn_series as $ldbtn_point ) {
	$ldbtn_max = max( $ldbtn_max, $ldbtn_point[1] + $ldbtn_point[2] );
}

// Round the axis up to a clean step: 1, 2, 2.5 or 5 times a power of ten.
$ldbtn_step = 1;
if ( $ldbtn_max > 4 ) {
	$ldbtn_raw  = $ldbtn_max / 4;
	$ldbtn_mag  = pow( 10, floor( log10( $ldbtn_raw ) ) );
	$ldbtn_step = $ldbtn_mag * 10;
	foreach ( array( 1, 2, 2.5, 5, 10 ) as $ldbtn_mult ) {
		if ( $ldbtn_mult * $ldbtn_mag >= $ldbtn_raw ) {
			$ldbtn_step = $ldbtn_mult * $ldbtn_mag;
			break;
		}
	}
	$ldbtn_step = max( 1, $ldbtn_step );
}
$ldbtn_y_max = max( 4, ceil( $ldbtn_max / $ldbtn_step ) * $ldbtn_step );
if ( $ldbtn_y_max / $ldbtn_step > 5 ) {
	$ldbtn_step = $ldbtn_y_max / 4;
}

$ldbtn_band  = $ldbtn_plot_w / $ldbtn_n;
$ldbtn_bar_w = min( 24, max( 2, $ldbtn_band * 0.7 ) );
$ldbtn_base  = $ldbtn_top + $ldbtn_plot_h;
$ldbtn_every = (int) ceil( $ldbtn_n / 8 );

/**
 * A column segment; only the top segment gets rounded corners.
 *
 * @param float $x       Left.
 * @param float $y       Top.
 * @param float $w       Width.
 * @param float $h       Height.
 * @param bool  $rounded Round the top corners.
 * @return string Path data.
 */
$ldbtn_segment = function ( $x, $y, $w, $h, $rounded ) {
	$r = $rounded ? min( 4, $h, $w / 2 ) : 0;
	$b = $y + $h;
	if ( ! $r ) {
		return sprintf( 'M%.2F %.2FH%.2FV%.2FH%.2FZ', $x, $b, $x + $w, $y, $x );
	}
	return sprintf(
		'M%1$.2F %2$.2FV%3$.2FA%4$.2F %4$.2F 0 0 1 %5$.2F %6$.2FH%7$.2FA%4$.2F %4$.2F 0 0 1 %8$.2F %3$.2FV%2$.2FZ',
		$x,
		$b,
		$y + $r,
		$r,
		$x + $r,
		$y,
		$x + $w - $r,
		$x + $w
	);
};
?>
<div class="ldbtn-chart">
	<svg viewBox="0 0 <?php echo (int) $ldbtn_w; ?> <?php echo (int) $ldbtn_h; ?>" role="img" aria-labelledby="ldbtn-chart-desc" class="ldbtn-chart__svg">
		<desc id="ldbtn-chart-desc">
			<?php
			/* translators: 1: likes, 2: dislikes */
			echo esc_html( sprintf( __( 'Likes and dislikes over time: %1$s likes and %2$s dislikes in total. The same data is available as a table below.', 'like-dislike-button' ), number_format_i18n( array_sum( wp_list_pluck( $ldbtn_series, 1 ) ) ), number_format_i18n( array_sum( wp_list_pluck( $ldbtn_series, 2 ) ) ) ) );
			?>
		</desc>
		<g class="ldbtn-chart__grid">
			<?php for ( $ldbtn_v = 0; $ldbtn_v <= $ldbtn_y_max + 0.001; $ldbtn_v += $ldbtn_step ) : ?>
				<?php $ldbtn_y = $ldbtn_base - $ldbtn_v / $ldbtn_y_max * $ldbtn_plot_h; ?>
				<line x1="<?php echo esc_attr( $ldbtn_left ); ?>" x2="<?php echo esc_attr( $ldbtn_w - $ldbtn_right ); ?>" y1="<?php echo esc_attr( round( $ldbtn_y, 2 ) ); ?>" y2="<?php echo esc_attr( round( $ldbtn_y, 2 ) ); ?>" class="<?php echo 0 === (int) $ldbtn_v ? 'is-base' : ''; ?>"/>
				<text x="<?php echo esc_attr( $ldbtn_left - 8 ); ?>" y="<?php echo esc_attr( round( $ldbtn_y + 4, 2 ) ); ?>" text-anchor="end"><?php echo esc_html( LDBTN_Render::format_number( (int) round( $ldbtn_v ) ) ); ?></text>
			<?php endfor; ?>
		</g>
		<g class="ldbtn-chart__bars">
			<?php
			foreach ( $ldbtn_series as $ldbtn_i => $ldbtn_point ) :
				list( $ldbtn_label, $ldbtn_likes, $ldbtn_dislikes ) = $ldbtn_point;
				$ldbtn_x   = $ldbtn_left + $ldbtn_i * $ldbtn_band + ( $ldbtn_band - $ldbtn_bar_w ) / 2;
				$ldbtn_h_l = $ldbtn_likes / $ldbtn_y_max * $ldbtn_plot_h;
				$ldbtn_h_d = $ldbtn_dislikes / $ldbtn_y_max * $ldbtn_plot_h;
				$ldbtn_gap = $ldbtn_h_l && $ldbtn_h_d ? 2 : 0;
				?>
				<g class="ldbtn-chart__col" data-label="<?php echo esc_attr( $ldbtn_label ); ?>" data-likes="<?php echo esc_attr( number_format_i18n( $ldbtn_likes ) ); ?>" data-dislikes="<?php echo esc_attr( number_format_i18n( $ldbtn_dislikes ) ); ?>" tabindex="0" aria-label="<?php echo esc_attr( sprintf( '%s: %s %s, %s %s', $ldbtn_label, number_format_i18n( $ldbtn_likes ), __( 'likes', 'like-dislike-button' ), number_format_i18n( $ldbtn_dislikes ), __( 'dislikes', 'like-dislike-button' ) ) ); ?>">
					<rect class="ldbtn-chart__hit" x="<?php echo esc_attr( round( $ldbtn_left + $ldbtn_i * $ldbtn_band, 2 ) ); ?>" y="<?php echo esc_attr( $ldbtn_top ); ?>" width="<?php echo esc_attr( round( $ldbtn_band, 2 ) ); ?>" height="<?php echo esc_attr( $ldbtn_plot_h ); ?>"/>
					<?php if ( $ldbtn_h_l > 0 ) : ?>
						<path class="ldbtn-chart__like" d="<?php echo esc_attr( $ldbtn_segment( $ldbtn_x, $ldbtn_base - $ldbtn_h_l, $ldbtn_bar_w, $ldbtn_h_l, ! $ldbtn_h_d ) ); ?>"/>
					<?php endif; ?>
					<?php if ( $ldbtn_h_d > 0 ) : ?>
						<?php $ldbtn_h_d_draw = max( 1, $ldbtn_h_d - $ldbtn_gap ); ?>
						<path class="ldbtn-chart__dislike" d="<?php echo esc_attr( $ldbtn_segment( $ldbtn_x, $ldbtn_base - $ldbtn_h_l - $ldbtn_gap - $ldbtn_h_d_draw, $ldbtn_bar_w, $ldbtn_h_d_draw, true ) ); ?>"/>
					<?php endif; ?>
				</g>
				<?php if ( 0 === $ldbtn_i % $ldbtn_every || $ldbtn_i === $ldbtn_n - 1 && ( $ldbtn_n - 1 ) % $ldbtn_every > $ldbtn_every / 2 ) : ?>
					<text class="ldbtn-chart__x" x="<?php echo esc_attr( round( $ldbtn_left + ( $ldbtn_i + 0.5 ) * $ldbtn_band, 2 ) ); ?>" y="<?php echo esc_attr( $ldbtn_h - 6 ); ?>" text-anchor="middle"><?php echo esc_html( $ldbtn_label ); ?></text>
				<?php endif; ?>
			<?php endforeach; ?>
		</g>
	</svg>
	<div class="ldbtn-chart__tip" role="presentation" hidden></div>
</div>

<details class="ldbtn-chart-table">
	<summary><?php esc_html_e( 'Show as a table', 'like-dislike-button' ); ?></summary>
	<table class="ldbtn-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Date', 'like-dislike-button' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Likes', 'like-dislike-button' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Dislikes', 'like-dislike-button' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( array_reverse( $ldbtn_series ) as $ldbtn_point ) : ?>
				<tr>
					<td><?php echo esc_html( $ldbtn_point[0] ); ?></td>
					<td class="num"><?php echo esc_html( number_format_i18n( $ldbtn_point[1] ) ); ?></td>
					<td class="num"><?php echo esc_html( number_format_i18n( $ldbtn_point[2] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</details>
