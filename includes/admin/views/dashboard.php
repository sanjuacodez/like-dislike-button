<?php
/**
 * Analytics screen.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

$ldbtn_periods = array(
	'7'   => __( '7 days', 'like-dislike-button' ),
	'30'  => __( '30 days', 'like-dislike-button' ),
	'90'  => __( '90 days', 'like-dislike-button' ),
	'365' => __( '12 months', 'like-dislike-button' ),
	'all' => __( 'All time', 'like-dislike-button' ),
);
$ldbtn_period = isset( $_GET['period'] ) ? sanitize_key( wp_unslash( $_GET['period'] ) ) : '30'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view filter.
$ldbtn_period = isset( $ldbtn_periods[ $ldbtn_period ] ) ? $ldbtn_period : '30';
$ldbtn_days   = LDBTN_Stats::periods()[ $ldbtn_period ];

$ldbtn_all     = LDBTN_Stats::totals( 0 );
$ldbtn_now     = LDBTN_Stats::totals( $ldbtn_days );
$ldbtn_before  = $ldbtn_days ? LDBTN_Stats::totals( $ldbtn_days, 1 ) : null;
$ldbtn_series  = LDBTN_Stats::series( $ldbtn_days );
$ldbtn_liked   = LDBTN_Stats::top( $ldbtn_days, 'like', 10 );
$ldbtn_dislike = LDBTN_Stats::top( $ldbtn_days, 'dislike', 10 );

/**
 * Change against the previous period, as a signed percentage.
 *
 * @param int $now    This period.
 * @param int $before Previous period.
 * @return float|null
 */
$ldbtn_change = function ( $now, $before ) {
	if ( ! $before ) {
		return null;
	}
	return ( $now - $before ) / $before * 100;
};

/**
 * Prints the delta line under a stat.
 */
$ldbtn_delta = function ( $change, $up_is_good, $unit = '%' ) use ( $ldbtn_period ) {
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
	$days = LDBTN_Stats::periods()[ $ldbtn_period ];
	/* translators: %d: number of days */
	$span = sprintf( _n( '%d day', '%d days', $days, 'like-dislike-button' ), $days );
	/* translators: 1: arrow, 2: signed change such as +12%, 3: period length such as 30 days */
	$text = sprintf( __( '%1$s %2$s vs previous %3$s', 'like-dislike-button' ), $arrow, ( $rounded > 0 ? '+' : '' ) . number_format_i18n( $rounded ) . $unit, $span );
	echo '<span class="ldbtn-stat__delta ' . esc_attr( $class ) . '">' . esc_html( $text ) . '</span>';
};

$ldbtn_total_now = $ldbtn_now['likes'] + $ldbtn_now['dislikes'];
$ldbtn_ratio_now = $ldbtn_total_now ? $ldbtn_now['likes'] / $ldbtn_total_now * 100 : null;
$ldbtn_ratio_chg = null;
if ( $ldbtn_before && null !== $ldbtn_ratio_now && ( $ldbtn_before['likes'] + $ldbtn_before['dislikes'] ) ) {
	$ldbtn_ratio_chg = $ldbtn_ratio_now - $ldbtn_before['likes'] / ( $ldbtn_before['likes'] + $ldbtn_before['dislikes'] ) * 100;
}

/**
 * Prints a top-posts table.
 */
$ldbtn_table = function ( $rows, $caption ) {
	if ( ! $rows ) {
		echo '<p class="ldbtn-empty-line">' . esc_html__( 'No votes in this period.', 'like-dislike-button' ) . '</p>';
		return;
	}
	_prime_post_caches( wp_list_pluck( $rows, 'id' ) );
	?>
	<table class="ldbtn-table">
		<caption class="screen-reader-text"><?php echo esc_html( $caption ); ?></caption>
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Post', 'like-dislike-button' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Likes', 'like-dislike-button' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Dislikes', 'like-dislike-button' ); ?></th>
				<th scope="col" class="ratio"><?php esc_html_e( 'Like ratio', 'like-dislike-button' ); ?></th>
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
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ? get_the_title( $post ) : __( '(no title)', 'like-dislike-button' ) ); ?></a>
							<span class="ldbtn-table__type"><?php echo esc_html( get_post_type_object( $post->post_type ) ? get_post_type_object( $post->post_type )->labels->singular_name : $post->post_type ); ?></span>
						<?php else : ?>
							<?php
							/* translators: %d: post ID */
							echo esc_html( sprintf( __( 'Deleted item #%d', 'like-dislike-button' ), $row->id ) );
							?>
						<?php endif; ?>
					</td>
					<td class="num"><?php echo esc_html( number_format_i18n( (int) $row->likes ) ); ?></td>
					<td class="num"><?php echo esc_html( number_format_i18n( (int) $row->dislikes ) ); ?></td>
					<td class="ratio">
						<span class="ldbtn-meter" aria-hidden="true"><span style="width:<?php echo esc_attr( $ratio ); ?>%"></span></span>
						<span class="ldbtn-meter__value"><?php echo esc_html( $ratio ); ?>%</span>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
};
?>
<div class="wrap ldbtn-admin">
	<?php LDBTN_Admin::header( 'ldbtn' ); ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Like Dislike Dashboard', 'like-dislike-button' ); ?></h1>

	<?php if ( ! $ldbtn_all['likes'] && ! $ldbtn_all['dislikes'] ) : ?>
		<?php require LDBTN_PATH . 'includes/admin/views/welcome.php'; ?>
	<?php else : ?>

		<div class="ldbtn-toolbar">
			<nav class="ldbtn-segments ldbtn-segments--links" aria-label="<?php esc_attr_e( 'Period', 'like-dislike-button' ); ?>">
				<?php foreach ( $ldbtn_periods as $ldbtn_slug => $ldbtn_label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'period', $ldbtn_slug, admin_url( 'admin.php?page=ldbtn' ) ) ); ?>" class="<?php echo $ldbtn_slug === $ldbtn_period ? 'is-current' : ''; ?>" <?php echo $ldbtn_slug === $ldbtn_period ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $ldbtn_label ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>

		<div class="ldbtn-stats">
			<div class="ldbtn-stat">
				<span class="ldbtn-stat__label"><span class="ldbtn-key ldbtn-key--like" aria-hidden="true"></span><?php esc_html_e( 'Likes', 'like-dislike-button' ); ?></span>
				<span class="ldbtn-stat__value"><?php echo esc_html( LDBTN_Render::format_number( $ldbtn_now['likes'] ) ); ?></span>
				<?php $ldbtn_delta( $ldbtn_before ? $ldbtn_change( $ldbtn_now['likes'], $ldbtn_before['likes'] ) : null, true ); ?>
			</div>
			<div class="ldbtn-stat">
				<span class="ldbtn-stat__label"><span class="ldbtn-key ldbtn-key--dislike" aria-hidden="true"></span><?php esc_html_e( 'Dislikes', 'like-dislike-button' ); ?></span>
				<span class="ldbtn-stat__value"><?php echo esc_html( LDBTN_Render::format_number( $ldbtn_now['dislikes'] ) ); ?></span>
				<?php $ldbtn_delta( $ldbtn_before ? $ldbtn_change( $ldbtn_now['dislikes'], $ldbtn_before['dislikes'] ) : null, false ); ?>
			</div>
			<div class="ldbtn-stat">
				<span class="ldbtn-stat__label"><?php esc_html_e( 'Total votes', 'like-dislike-button' ); ?></span>
				<span class="ldbtn-stat__value"><?php echo esc_html( LDBTN_Render::format_number( $ldbtn_total_now ) ); ?></span>
				<?php $ldbtn_delta( $ldbtn_before ? $ldbtn_change( $ldbtn_total_now, $ldbtn_before['likes'] + $ldbtn_before['dislikes'] ) : null, true ); ?>
			</div>
			<div class="ldbtn-stat">
				<span class="ldbtn-stat__label"><?php esc_html_e( 'Like ratio', 'like-dislike-button' ); ?></span>
				<span class="ldbtn-stat__value"><?php echo null === $ldbtn_ratio_now ? '—' : esc_html( round( $ldbtn_ratio_now ) . '%' ); ?></span>
				<?php $ldbtn_delta( $ldbtn_ratio_chg, true, ' ' . __( 'pts', 'like-dislike-button' ) ); ?>
			</div>
		</div>

		<div class="ldbtn-card ldbtn-chart-card">
			<div class="ldbtn-chart-card__head">
				<h2 class="ldbtn-card__title"><?php echo esc_html( ! $ldbtn_days || $ldbtn_days > 90 ? __( 'Votes per month', 'like-dislike-button' ) : __( 'Votes per day', 'like-dislike-button' ) ); ?></h2>
				<ul class="ldbtn-legend">
					<li><span class="ldbtn-key ldbtn-key--like" aria-hidden="true"></span><?php esc_html_e( 'Likes', 'like-dislike-button' ); ?></li>
					<li><span class="ldbtn-key ldbtn-key--dislike" aria-hidden="true"></span><?php esc_html_e( 'Dislikes', 'like-dislike-button' ); ?></li>
				</ul>
			</div>
			<?php require LDBTN_PATH . 'includes/admin/views/chart.php'; ?>
			<?php if ( ! $ldbtn_days ) : ?>
				<p class="ldbtn-help"><?php esc_html_e( 'Imported totals without dates count in the figures above but not in the chart.', 'like-dislike-button' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="ldbtn-columns">
			<div class="ldbtn-card">
				<h2 class="ldbtn-card__title"><?php esc_html_e( 'Most liked', 'like-dislike-button' ); ?></h2>
				<?php $ldbtn_table( $ldbtn_liked, __( 'Most liked posts', 'like-dislike-button' ) ); ?>
			</div>
			<div class="ldbtn-card">
				<h2 class="ldbtn-card__title"><?php esc_html_e( 'Most disliked', 'like-dislike-button' ); ?></h2>
				<?php $ldbtn_table( $ldbtn_dislike, __( 'Most disliked posts', 'like-dislike-button' ) ); ?>
			</div>
		</div>
	<?php endif; ?>
</div>
