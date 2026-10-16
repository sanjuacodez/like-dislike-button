<?php
/**
 * Analytics screen.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

$likenook_periods = array(
	'7'   => __( '7 days', 'likenook' ),
	'30'  => __( '30 days', 'likenook' ),
	'90'  => __( '90 days', 'likenook' ),
	'365' => __( '12 months', 'likenook' ),
	'all' => __( 'All time', 'likenook' ),
);
$likenook_period = isset( $_GET['period'] ) ? sanitize_key( wp_unslash( $_GET['period'] ) ) : '30'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view filter.
$likenook_period = isset( $likenook_periods[ $likenook_period ] ) ? $likenook_period : '30';
$likenook_days   = Likenook_Stats::periods()[ $likenook_period ];

$likenook_all     = Likenook_Stats::totals( 0 );
$likenook_now     = Likenook_Stats::totals( $likenook_days );
$likenook_before  = $likenook_days ? Likenook_Stats::totals( $likenook_days, 1 ) : null;
$likenook_series  = Likenook_Stats::series( $likenook_days );
$likenook_liked   = Likenook_Stats::top( $likenook_days, 'like', 10 );
$likenook_dislike = Likenook_Stats::top( $likenook_days, 'dislike', 10 );

/**
 * Change against the previous period, as a signed percentage.
 *
 * @param int $now    This period.
 * @param int $before Previous period.
 * @return float|null
 */
$likenook_change = function ( $now, $before ) {
	if ( ! $before ) {
		return null;
	}
	return ( $now - $before ) / $before * 100;
};

/**
 * Prints the delta line under a stat.
 */
$likenook_delta = function ( $change, $up_is_good, $unit = '%' ) use ( $likenook_period ) {
	if ( null === $change ) {
		return;
	}
	$rounded = round( $change );
	if ( 0.0 === (float) $rounded ) {
		$class = 'is-flat';
		$arrow = '→';
	} else {
		$good  = ( $rounded > 0 ) === $up_is_good;
		$class = $good ? 'is-good' : 'is-bad';
		$arrow = $rounded > 0 ? '↑' : '↓';
	}
	$days = Likenook_Stats::periods()[ $likenook_period ];
	/* translators: %d: number of days */
	$span = sprintf( _n( '%d day', '%d days', $days, 'likenook' ), $days );
	/* translators: 1: arrow, 2: signed change such as +12%, 3: period length such as 30 days */
	$text = sprintf( __( '%1$s %2$s vs previous %3$s', 'likenook' ), $arrow, ( $rounded > 0 ? '+' : '' ) . number_format_i18n( $rounded ) . $unit, $span );
	echo '<span class="likenook-stat__delta ' . esc_attr( $class ) . '">' . esc_html( $text ) . '</span>';
};

$likenook_total_now = $likenook_now['likes'] + $likenook_now['dislikes'];
$likenook_ratio_now = $likenook_total_now ? $likenook_now['likes'] / $likenook_total_now * 100 : null;
$likenook_ratio_chg = null;
if ( $likenook_before && null !== $likenook_ratio_now && ( $likenook_before['likes'] + $likenook_before['dislikes'] ) ) {
	$likenook_ratio_chg = $likenook_ratio_now - $likenook_before['likes'] / ( $likenook_before['likes'] + $likenook_before['dislikes'] ) * 100;
}

/**
 * Prints a top-posts table.
 */
$likenook_table = function ( $rows, $caption ) {
	if ( ! $rows ) {
		echo '<p class="likenook-empty-line">' . esc_html__( 'No votes in this period.', 'likenook' ) . '</p>';
		return;
	}
	_prime_post_caches( wp_list_pluck( $rows, 'id' ) );
	?>
	<table class="likenook-table">
		<caption class="screen-reader-text"><?php echo esc_html( $caption ); ?></caption>
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Post', 'likenook' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Likes', 'likenook' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Dislikes', 'likenook' ); ?></th>
				<th scope="col" class="ratio"><?php esc_html_e( 'Like ratio', 'likenook' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $rows as $row ) :
				$post  = get_post( (int) $row->id );
				$total = (int) $row->likes + (int) $row->dislikes;
				$ratio = $total ? round( $row->likes / $total * 100 ) : 0;
				?>
				<tr>
					<td>
						<?php if ( $post ) : ?>
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ? get_the_title( $post ) : __( '(no title)', 'likenook' ) ); ?></a>
							<span class="likenook-table__type"><?php echo esc_html( get_post_type_object( $post->post_type ) ? get_post_type_object( $post->post_type )->labels->singular_name : $post->post_type ); ?></span>
						<?php else : ?>
							<?php
							/* translators: %d: post ID */
							echo esc_html( sprintf( __( 'Deleted item #%d', 'likenook' ), $row->id ) );
							?>
						<?php endif; ?>
					</td>
					<td class="num"><?php echo esc_html( number_format_i18n( (int) $row->likes ) ); ?></td>
					<td class="num"><?php echo esc_html( number_format_i18n( (int) $row->dislikes ) ); ?></td>
					<td class="ratio">
						<span class="likenook-meter" aria-hidden="true"><span style="width:<?php echo esc_attr( $ratio ); ?>%"></span></span>
						<span class="likenook-meter__value"><?php echo esc_html( $ratio ); ?>%</span>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
};
?>
<div class="wrap likenook-admin">
	<?php Likenook_Admin::header( 'likenook' ); ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Likenook Dashboard', 'likenook' ); ?></h1>

	<?php if ( ! $likenook_all['likes'] && ! $likenook_all['dislikes'] ) : ?>
		<?php require LIKENOOK_PATH . 'includes/admin/views/welcome.php'; ?>
	<?php else : ?>

		<div class="likenook-toolbar">
			<nav class="likenook-segments likenook-segments--links" aria-label="<?php esc_attr_e( 'Period', 'likenook' ); ?>">
				<?php foreach ( $likenook_periods as $likenook_slug => $likenook_label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'period', $likenook_slug, admin_url( 'admin.php?page=likenook' ) ) ); ?>" class="<?php echo $likenook_slug === $likenook_period ? 'is-current' : ''; ?>" <?php echo $likenook_slug === $likenook_period ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $likenook_label ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>

		<div class="likenook-stats">
			<div class="likenook-stat">
				<span class="likenook-stat__label"><span class="likenook-key likenook-key--like" aria-hidden="true"></span><?php esc_html_e( 'Likes', 'likenook' ); ?></span>
				<span class="likenook-stat__value"><?php echo esc_html( Likenook_Render::format_number( $likenook_now['likes'] ) ); ?></span>
				<?php $likenook_delta( $likenook_before ? $likenook_change( $likenook_now['likes'], $likenook_before['likes'] ) : null, true ); ?>
			</div>
			<div class="likenook-stat">
				<span class="likenook-stat__label"><span class="likenook-key likenook-key--dislike" aria-hidden="true"></span><?php esc_html_e( 'Dislikes', 'likenook' ); ?></span>
				<span class="likenook-stat__value"><?php echo esc_html( Likenook_Render::format_number( $likenook_now['dislikes'] ) ); ?></span>
				<?php $likenook_delta( $likenook_before ? $likenook_change( $likenook_now['dislikes'], $likenook_before['dislikes'] ) : null, false ); ?>
			</div>
			<div class="likenook-stat">
				<span class="likenook-stat__label"><?php esc_html_e( 'Total votes', 'likenook' ); ?></span>
				<span class="likenook-stat__value"><?php echo esc_html( Likenook_Render::format_number( $likenook_total_now ) ); ?></span>
				<?php $likenook_delta( $likenook_before ? $likenook_change( $likenook_total_now, $likenook_before['likes'] + $likenook_before['dislikes'] ) : null, true ); ?>
			</div>
			<div class="likenook-stat">
				<span class="likenook-stat__label"><?php esc_html_e( 'Like ratio', 'likenook' ); ?></span>
				<span class="likenook-stat__value"><?php echo null === $likenook_ratio_now ? '—' : esc_html( round( $likenook_ratio_now ) . '%' ); ?></span>
				<?php $likenook_delta( $likenook_ratio_chg, true, ' ' . __( 'pts', 'likenook' ) ); ?>
			</div>
		</div>

		<div class="likenook-card likenook-chart-card">
			<div class="likenook-chart-card__head">
				<h2 class="likenook-card__title"><?php echo esc_html( ! $likenook_days || $likenook_days > 90 ? __( 'Votes per month', 'likenook' ) : __( 'Votes per day', 'likenook' ) ); ?></h2>
				<ul class="likenook-legend">
					<li><span class="likenook-key likenook-key--like" aria-hidden="true"></span><?php esc_html_e( 'Likes', 'likenook' ); ?></li>
					<li><span class="likenook-key likenook-key--dislike" aria-hidden="true"></span><?php esc_html_e( 'Dislikes', 'likenook' ); ?></li>
				</ul>
			</div>
			<?php require LIKENOOK_PATH . 'includes/admin/views/chart.php'; ?>
			<?php if ( ! $likenook_days ) : ?>
				<p class="likenook-help"><?php esc_html_e( 'Imported totals without dates count in the figures above but not in the chart.', 'likenook' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="likenook-columns">
			<div class="likenook-card">
				<h2 class="likenook-card__title"><?php esc_html_e( 'Most liked', 'likenook' ); ?></h2>
				<?php $likenook_table( $likenook_liked, __( 'Most liked posts', 'likenook' ) ); ?>
			</div>
			<div class="likenook-card">
				<h2 class="likenook-card__title"><?php esc_html_e( 'Most disliked', 'likenook' ); ?></h2>
				<?php $likenook_table( $likenook_dislike, __( 'Most disliked posts', 'likenook' ) ); ?>
			</div>
		</div>
	<?php endif; ?>
</div>
