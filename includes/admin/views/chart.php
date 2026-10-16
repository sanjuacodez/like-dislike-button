<?php
/**
 * Stacked column chart of likes and dislikes, as inline SVG.
 *
 * Expects $likenook_series: list of [label, likes, dislikes, key].
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

if ( ! $likenook_series ) {
	echo '<p class="likenook-empty-line">' . esc_html__( 'No votes in this period.', 'likenook' ) . '</p>';
	return;
}

$likenook_w      = 1100;
$likenook_h      = 300;
$likenook_left   = 40;
$likenook_right  = 6;
$likenook_top    = 10;
$likenook_bottom = 26;
$likenook_plot_w = $likenook_w - $likenook_left - $likenook_right;
$likenook_plot_h = $likenook_h - $likenook_top - $likenook_bottom;
$likenook_n      = count( $likenook_series );
$likenook_max    = 0;
foreach ( $likenook_series as $likenook_point ) {
	$likenook_max = max( $likenook_max, $likenook_point[1] + $likenook_point[2] );
}

// Round the axis up to a clean step: 1, 2, 2.5 or 5 times a power of ten.
$likenook_step = 1;
if ( $likenook_max > 4 ) {
	$likenook_raw  = $likenook_max / 4;
	$likenook_mag  = pow( 10, floor( log10( $likenook_raw ) ) );
	$likenook_step = $likenook_mag * 10;
	foreach ( array( 1, 2, 2.5, 5, 10 ) as $likenook_mult ) {
		if ( $likenook_mult * $likenook_mag >= $likenook_raw ) {
			$likenook_step = $likenook_mult * $likenook_mag;
			break;
		}
	}
	$likenook_step = max( 1, $likenook_step );
}
$likenook_y_max = max( 4, ceil( $likenook_max / $likenook_step ) * $likenook_step );
if ( $likenook_y_max / $likenook_step > 5 ) {
	$likenook_step = $likenook_y_max / 4;
}

$likenook_band  = $likenook_plot_w / $likenook_n;
$likenook_bar_w = min( 24, max( 2, $likenook_band * 0.7 ) );
$likenook_base  = $likenook_top + $likenook_plot_h;
$likenook_every = (int) ceil( $likenook_n / 8 );

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
$likenook_segment = function ( $x, $y, $w, $h, $rounded ) {
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
<div class="likenook-chart">
	<svg viewBox="0 0 <?php echo (int) $likenook_w; ?> <?php echo (int) $likenook_h; ?>" role="img" aria-labelledby="likenook-chart-desc" class="likenook-chart__svg">
		<desc id="likenook-chart-desc">
			<?php
			/* translators: 1: likes, 2: dislikes */
			echo esc_html( sprintf( __( 'Likes and dislikes over time: %1$s likes and %2$s dislikes in total. The same data is available as a table below.', 'likenook' ), number_format_i18n( array_sum( wp_list_pluck( $likenook_series, 1 ) ) ), number_format_i18n( array_sum( wp_list_pluck( $likenook_series, 2 ) ) ) ) );
			?>
		</desc>
		<g class="likenook-chart__grid">
			<?php for ( $likenook_v = 0; $likenook_v <= $likenook_y_max + 0.001; $likenook_v += $likenook_step ) : ?>
				<?php $likenook_y = $likenook_base - $likenook_v / $likenook_y_max * $likenook_plot_h; ?>
				<line x1="<?php echo esc_attr( $likenook_left ); ?>" x2="<?php echo esc_attr( $likenook_w - $likenook_right ); ?>" y1="<?php echo esc_attr( round( $likenook_y, 2 ) ); ?>" y2="<?php echo esc_attr( round( $likenook_y, 2 ) ); ?>" class="<?php echo 0 === (int) $likenook_v ? 'is-base' : ''; ?>"/>
				<text x="<?php echo esc_attr( $likenook_left - 8 ); ?>" y="<?php echo esc_attr( round( $likenook_y + 4, 2 ) ); ?>" text-anchor="end"><?php echo esc_html( Likenook_Render::format_number( (int) round( $likenook_v ) ) ); ?></text>
			<?php endfor; ?>
		</g>
		<g class="likenook-chart__bars">
			<?php
			foreach ( $likenook_series as $likenook_i => $likenook_point ) :
				list( $likenook_label, $likenook_likes, $likenook_dislikes ) = $likenook_point;
				$likenook_x   = $likenook_left + $likenook_i * $likenook_band + ( $likenook_band - $likenook_bar_w ) / 2;
				$likenook_h_l = $likenook_likes / $likenook_y_max * $likenook_plot_h;
				$likenook_h_d = $likenook_dislikes / $likenook_y_max * $likenook_plot_h;
				$likenook_gap = $likenook_h_l && $likenook_h_d ? 2 : 0;
				?>
				<g class="likenook-chart__col" data-label="<?php echo esc_attr( $likenook_label ); ?>" data-likes="<?php echo esc_attr( number_format_i18n( $likenook_likes ) ); ?>" data-dislikes="<?php echo esc_attr( number_format_i18n( $likenook_dislikes ) ); ?>" tabindex="0" aria-label="<?php echo esc_attr( sprintf( '%s: %s %s, %s %s', $likenook_label, number_format_i18n( $likenook_likes ), __( 'likes', 'likenook' ), number_format_i18n( $likenook_dislikes ), __( 'dislikes', 'likenook' ) ) ); ?>">
					<rect class="likenook-chart__hit" x="<?php echo esc_attr( round( $likenook_left + $likenook_i * $likenook_band, 2 ) ); ?>" y="<?php echo esc_attr( $likenook_top ); ?>" width="<?php echo esc_attr( round( $likenook_band, 2 ) ); ?>" height="<?php echo esc_attr( $likenook_plot_h ); ?>"/>
					<?php if ( $likenook_h_l > 0 ) : ?>
						<path class="likenook-chart__like" d="<?php echo esc_attr( $likenook_segment( $likenook_x, $likenook_base - $likenook_h_l, $likenook_bar_w, $likenook_h_l, ! $likenook_h_d ) ); ?>"/>
					<?php endif; ?>
					<?php if ( $likenook_h_d > 0 ) : ?>
						<?php $likenook_h_d_draw = max( 1, $likenook_h_d - $likenook_gap ); ?>
						<path class="likenook-chart__dislike" d="<?php echo esc_attr( $likenook_segment( $likenook_x, $likenook_base - $likenook_h_l - $likenook_gap - $likenook_h_d_draw, $likenook_bar_w, $likenook_h_d_draw, true ) ); ?>"/>
					<?php endif; ?>
				</g>
				<?php if ( 0 === $likenook_i % $likenook_every || $likenook_i === $likenook_n - 1 && ( $likenook_n - 1 ) % $likenook_every > $likenook_every / 2 ) : ?>
					<text class="likenook-chart__x" x="<?php echo esc_attr( round( $likenook_left + ( $likenook_i + 0.5 ) * $likenook_band, 2 ) ); ?>" y="<?php echo esc_attr( $likenook_h - 6 ); ?>" text-anchor="middle"><?php echo esc_html( $likenook_label ); ?></text>
				<?php endif; ?>
			<?php endforeach; ?>
		</g>
	</svg>
	<div class="likenook-chart__tip" role="presentation" hidden></div>
</div>

<details class="likenook-chart-table">
	<summary><?php esc_html_e( 'Show as a table', 'likenook' ); ?></summary>
	<table class="likenook-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Date', 'likenook' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Likes', 'likenook' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Dislikes', 'likenook' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( array_reverse( $likenook_series ) as $likenook_point ) : ?>
				<tr>
					<td><?php echo esc_html( $likenook_point[0] ); ?></td>
					<td class="num"><?php echo esc_html( number_format_i18n( $likenook_point[1] ) ); ?></td>
					<td class="num"><?php echo esc_html( number_format_i18n( $likenook_point[2] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</details>
