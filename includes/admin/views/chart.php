<?php
/**
 * Stacked column chart of likes and dislikes, as inline SVG.
 *
 * Expects $ldb_series: list of [label, likes, dislikes, key].
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

if ( ! $ldb_series ) {
	echo '<p class="ldb-empty-line">' . esc_html__( 'No votes in this period.', 'like-dislike-button' ) . '</p>';
	return;
}

$ldb_w      = 1100;
$ldb_h      = 300;
$ldb_left   = 40;
$ldb_right  = 6;
$ldb_top    = 10;
$ldb_bottom = 26;
$ldb_plot_w = $ldb_w - $ldb_left - $ldb_right;
$ldb_plot_h = $ldb_h - $ldb_top - $ldb_bottom;
$ldb_n      = count( $ldb_series );
$ldb_max    = 0;
foreach ( $ldb_series as $ldb_point ) {
	$ldb_max = max( $ldb_max, $ldb_point[1] + $ldb_point[2] );
}

// Round the axis up to a clean step: 1, 2, 2.5 or 5 times a power of ten.
$ldb_step = 1;
if ( $ldb_max > 4 ) {
	$ldb_raw  = $ldb_max / 4;
	$ldb_mag  = pow( 10, floor( log10( $ldb_raw ) ) );
	$ldb_step = $ldb_mag * 10;
	foreach ( array( 1, 2, 2.5, 5, 10 ) as $ldb_mult ) {
		if ( $ldb_mult * $ldb_mag >= $ldb_raw ) {
			$ldb_step = $ldb_mult * $ldb_mag;
			break;
		}
	}
	$ldb_step = max( 1, $ldb_step );
}
$ldb_y_max = max( 4, ceil( $ldb_max / $ldb_step ) * $ldb_step );
if ( $ldb_y_max / $ldb_step > 5 ) {
	$ldb_step = $ldb_y_max / 4;
}

$ldb_band  = $ldb_plot_w / $ldb_n;
$ldb_bar_w = min( 24, max( 2, $ldb_band * 0.7 ) );
$ldb_base  = $ldb_top + $ldb_plot_h;
$ldb_every = (int) ceil( $ldb_n / 8 );

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
$ldb_segment = function ( $x, $y, $w, $h, $rounded ) {
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
<div class="ldb-chart">
	<svg viewBox="0 0 <?php echo (int) $ldb_w; ?> <?php echo (int) $ldb_h; ?>" role="img" aria-labelledby="ldb-chart-desc" class="ldb-chart__svg">
		<desc id="ldb-chart-desc">
			<?php
			/* translators: 1: likes, 2: dislikes */
			echo esc_html( sprintf( __( 'Likes and dislikes over time: %1$s likes and %2$s dislikes in total. The same data is available as a table below.', 'like-dislike-button' ), number_format_i18n( array_sum( wp_list_pluck( $ldb_series, 1 ) ) ), number_format_i18n( array_sum( wp_list_pluck( $ldb_series, 2 ) ) ) ) );
			?>
		</desc>
		<g class="ldb-chart__grid">
			<?php for ( $ldb_v = 0; $ldb_v <= $ldb_y_max + 0.001; $ldb_v += $ldb_step ) : ?>
				<?php $ldb_y = $ldb_base - $ldb_v / $ldb_y_max * $ldb_plot_h; ?>
				<line x1="<?php echo esc_attr( $ldb_left ); ?>" x2="<?php echo esc_attr( $ldb_w - $ldb_right ); ?>" y1="<?php echo esc_attr( round( $ldb_y, 2 ) ); ?>" y2="<?php echo esc_attr( round( $ldb_y, 2 ) ); ?>" class="<?php echo 0 === (int) $ldb_v ? 'is-base' : ''; ?>"/>
				<text x="<?php echo esc_attr( $ldb_left - 8 ); ?>" y="<?php echo esc_attr( round( $ldb_y + 4, 2 ) ); ?>" text-anchor="end"><?php echo esc_html( LDB_Render::format_number( (int) round( $ldb_v ) ) ); ?></text>
			<?php endfor; ?>
		</g>
		<g class="ldb-chart__bars">
			<?php
			foreach ( $ldb_series as $ldb_i => $ldb_point ) :
				list( $ldb_label, $ldb_likes, $ldb_dislikes ) = $ldb_point;
				$ldb_x   = $ldb_left + $ldb_i * $ldb_band + ( $ldb_band - $ldb_bar_w ) / 2;
				$ldb_h_l = $ldb_likes / $ldb_y_max * $ldb_plot_h;
				$ldb_h_d = $ldb_dislikes / $ldb_y_max * $ldb_plot_h;
				$ldb_gap = $ldb_h_l && $ldb_h_d ? 2 : 0;
				?>
				<g class="ldb-chart__col" data-label="<?php echo esc_attr( $ldb_label ); ?>" data-likes="<?php echo esc_attr( number_format_i18n( $ldb_likes ) ); ?>" data-dislikes="<?php echo esc_attr( number_format_i18n( $ldb_dislikes ) ); ?>" tabindex="0" aria-label="<?php echo esc_attr( sprintf( '%s: %s %s, %s %s', $ldb_label, number_format_i18n( $ldb_likes ), __( 'likes', 'like-dislike-button' ), number_format_i18n( $ldb_dislikes ), __( 'dislikes', 'like-dislike-button' ) ) ); ?>">
					<rect class="ldb-chart__hit" x="<?php echo esc_attr( round( $ldb_left + $ldb_i * $ldb_band, 2 ) ); ?>" y="<?php echo esc_attr( $ldb_top ); ?>" width="<?php echo esc_attr( round( $ldb_band, 2 ) ); ?>" height="<?php echo esc_attr( $ldb_plot_h ); ?>"/>
					<?php if ( $ldb_h_l > 0 ) : ?>
						<path class="ldb-chart__like" d="<?php echo esc_attr( $ldb_segment( $ldb_x, $ldb_base - $ldb_h_l, $ldb_bar_w, $ldb_h_l, ! $ldb_h_d ) ); ?>"/>
					<?php endif; ?>
					<?php if ( $ldb_h_d > 0 ) : ?>
						<?php $ldb_h_d_draw = max( 1, $ldb_h_d - $ldb_gap ); ?>
						<path class="ldb-chart__dislike" d="<?php echo esc_attr( $ldb_segment( $ldb_x, $ldb_base - $ldb_h_l - $ldb_gap - $ldb_h_d_draw, $ldb_bar_w, $ldb_h_d_draw, true ) ); ?>"/>
					<?php endif; ?>
				</g>
				<?php if ( 0 === $ldb_i % $ldb_every || $ldb_i === $ldb_n - 1 && ( $ldb_n - 1 ) % $ldb_every > $ldb_every / 2 ) : ?>
					<text class="ldb-chart__x" x="<?php echo esc_attr( round( $ldb_left + ( $ldb_i + 0.5 ) * $ldb_band, 2 ) ); ?>" y="<?php echo esc_attr( $ldb_h - 6 ); ?>" text-anchor="middle"><?php echo esc_html( $ldb_label ); ?></text>
				<?php endif; ?>
			<?php endforeach; ?>
		</g>
	</svg>
	<div class="ldb-chart__tip" role="presentation" hidden></div>
</div>

<details class="ldb-chart-table">
	<summary><?php esc_html_e( 'Show as a table', 'like-dislike-button' ); ?></summary>
	<table class="ldb-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Date', 'like-dislike-button' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Likes', 'like-dislike-button' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Dislikes', 'like-dislike-button' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( array_reverse( $ldb_series ) as $ldb_point ) : ?>
				<tr>
					<td><?php echo esc_html( $ldb_point[0] ); ?></td>
					<td class="num"><?php echo esc_html( number_format_i18n( $ldb_point[1] ) ); ?></td>
					<td class="num"><?php echo esc_html( number_format_i18n( $ldb_point[2] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</details>
