<?php
/**
 * Analytics screen.
 *
 * @package LikeDislikeButton
 */

defined( 'ABSPATH' ) || exit;

$ldb_periods = array(
	'7'   => __( '7 days', 'like-dislike-button' ),
	'30'  => __( '30 days', 'like-dislike-button' ),
	'90'  => __( '90 days', 'like-dislike-button' ),
	'365' => __( '12 months', 'like-dislike-button' ),
	'all' => __( 'All time', 'like-dislike-button' ),
);
$ldb_period = isset( $_GET['period'] ) ? sanitize_key( wp_unslash( $_GET['period'] ) ) : '30'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view filter.
$ldb_period = isset( $ldb_periods[ $ldb_period ] ) ? $ldb_period : '30';
$ldb_days   = LDB_Stats::periods()[ $ldb_period ];

$ldb_all     = LDB_Stats::totals( 0 );
$ldb_now     = LDB_Stats::totals( $ldb_days );
$ldb_before  = $ldb_days ? LDB_Stats::totals( $ldb_days, 1 ) : null;
$ldb_series  = LDB_Stats::series( $ldb_days );
$ldb_liked   = LDB_Stats::top( $ldb_days, 'like', 10 );
$ldb_dislike = LDB_Stats::top( $ldb_days, 'dislike', 10 );

/**
 * Change against the previous period, as a signed percentage.
 *
 * @param int $now    This period.
 * @param int $before Previous period.
 * @return float|null
 */
$ldb_change = function ( $now, $before ) {
	if ( ! $before ) {
		return null;
	}
	return ( $now - $before ) / $before * 100;
};

/**
 * Prints the delta line under a stat.
 */
$ldb_delta = function ( $change, $up_is_good, $unit = '%' ) use ( $ldb_period ) {
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
	$days = LDB_Stats::periods()[ $ldb_period ];
	/* translators: %d: number of days */
	$span = sprintf( _n( '%d day', '%d days', $days, 'like-dislike-button' ), $days );
	/* translators: 1: arrow, 2: signed change such as +12%, 3: period length such as 30 days */
	$text = sprintf( __( '%1$s %2$s vs previous %3$s', 'like-dislike-button' ), $arrow, ( $rounded > 0 ? '+' : '' ) . number_format_i18n( $rounded ) . $unit, $span );
	echo '<span class="ldb-stat__delta ' . esc_attr( $class ) . '">' . esc_html( $text ) . '</span>';
};

$ldb_total_now = $ldb_now['likes'] + $ldb_now['dislikes'];
$ldb_ratio_now = $ldb_total_now ? $ldb_now['likes'] / $ldb_total_now * 100 : null;
$ldb_ratio_chg = null;
if ( $ldb_before && null !== $ldb_ratio_now && ( $ldb_before['likes'] + $ldb_before['dislikes'] ) ) {
	$ldb_ratio_chg = $ldb_ratio_now - $ldb_before['likes'] / ( $ldb_before['likes'] + $ldb_before['dislikes'] ) * 100;
}

/**
 * Prints a top-posts table.
 */
$ldb_table = function ( $rows, $caption ) {
	if ( ! $rows ) {
		echo '<p class="ldb-empty-line">' . esc_html__( 'No votes in this period.', 'like-dislike-button' ) . '</p>';
		return;
	}
	_prime_post_caches( wp_list_pluck( $rows, 'id' ) );
	?>
	<table class="ldb-table">
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
							<span class="ldb-table__type"><?php echo esc_html( get_post_type_object( $post->post_type ) ? get_post_type_object( $post->post_type )->labels->singular_name : $post->post_type ); ?></span>
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
						<span class="ldb-meter" aria-hidden="true"><span style="width:<?php echo esc_attr( $ratio ); ?>%"></span></span>
						<span class="ldb-meter__value"><?php echo esc_html( $ratio ); ?>%</span>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
};
?>
<div class="wrap ldb-admin">
	<?php LDB_Admin::header( 'ldb' ); ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Like Dislike Dashboard', 'like-dislike-button' ); ?></h1>

	<?php if ( ! $ldb_all['likes'] && ! $ldb_all['dislikes'] ) : ?>
		<?php require LDB_PATH . 'includes/admin/views/welcome.php'; ?>
	<?php else : ?>

		<div class="ldb-toolbar">
			<nav class="ldb-segments ldb-segments--links" aria-label="<?php esc_attr_e( 'Period', 'like-dislike-button' ); ?>">
				<?php foreach ( $ldb_periods as $ldb_slug => $ldb_label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'period', $ldb_slug, admin_url( 'admin.php?page=ldb' ) ) ); ?>" class="<?php echo $ldb_slug === $ldb_period ? 'is-current' : ''; ?>" <?php echo $ldb_slug === $ldb_period ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $ldb_label ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>

		<div class="ldb-stats">
			<div class="ldb-stat">
				<span class="ldb-stat__label"><span class="ldb-key ldb-key--like" aria-hidden="true"></span><?php esc_html_e( 'Likes', 'like-dislike-button' ); ?></span>
				<span class="ldb-stat__value"><?php echo esc_html( LDB_Render::format_number( $ldb_now['likes'] ) ); ?></span>
				<?php $ldb_delta( $ldb_before ? $ldb_change( $ldb_now['likes'], $ldb_before['likes'] ) : null, true ); ?>
			</div>
			<div class="ldb-stat">
				<span class="ldb-stat__label"><span class="ldb-key ldb-key--dislike" aria-hidden="true"></span><?php esc_html_e( 'Dislikes', 'like-dislike-button' ); ?></span>
				<span class="ldb-stat__value"><?php echo esc_html( LDB_Render::format_number( $ldb_now['dislikes'] ) ); ?></span>
				<?php $ldb_delta( $ldb_before ? $ldb_change( $ldb_now['dislikes'], $ldb_before['dislikes'] ) : null, false ); ?>
			</div>
			<div class="ldb-stat">
				<span class="ldb-stat__label"><?php esc_html_e( 'Total votes', 'like-dislike-button' ); ?></span>
				<span class="ldb-stat__value"><?php echo esc_html( LDB_Render::format_number( $ldb_total_now ) ); ?></span>
				<?php $ldb_delta( $ldb_before ? $ldb_change( $ldb_total_now, $ldb_before['likes'] + $ldb_before['dislikes'] ) : null, true ); ?>
			</div>
			<div class="ldb-stat">
				<span class="ldb-stat__label"><?php esc_html_e( 'Like ratio', 'like-dislike-button' ); ?></span>
				<span class="ldb-stat__value"><?php echo null === $ldb_ratio_now ? '—' : esc_html( round( $ldb_ratio_now ) . '%' ); ?></span>
				<?php $ldb_delta( $ldb_ratio_chg, true, ' ' . __( 'pts', 'like-dislike-button' ) ); ?>
			</div>
		</div>

		<div class="ldb-card ldb-chart-card">
			<div class="ldb-chart-card__head">
				<h2 class="ldb-card__title"><?php echo esc_html( ! $ldb_days || $ldb_days > 90 ? __( 'Votes per month', 'like-dislike-button' ) : __( 'Votes per day', 'like-dislike-button' ) ); ?></h2>
				<ul class="ldb-legend">
					<li><span class="ldb-key ldb-key--like" aria-hidden="true"></span><?php esc_html_e( 'Likes', 'like-dislike-button' ); ?></li>
					<li><span class="ldb-key ldb-key--dislike" aria-hidden="true"></span><?php esc_html_e( 'Dislikes', 'like-dislike-button' ); ?></li>
				</ul>
			</div>
			<?php require LDB_PATH . 'includes/admin/views/chart.php'; ?>
			<?php if ( ! $ldb_days ) : ?>
				<p class="ldb-help"><?php esc_html_e( 'Imported totals without dates count in the figures above but not in the chart.', 'like-dislike-button' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="ldb-columns">
			<div class="ldb-card">
				<h2 class="ldb-card__title"><?php esc_html_e( 'Most liked', 'like-dislike-button' ); ?></h2>
				<?php $ldb_table( $ldb_liked, __( 'Most liked posts', 'like-dislike-button' ) ); ?>
			</div>
			<div class="ldb-card">
				<h2 class="ldb-card__title"><?php esc_html_e( 'Most disliked', 'like-dislike-button' ); ?></h2>
				<?php $ldb_table( $ldb_dislike, __( 'Most disliked posts', 'like-dislike-button' ) ); ?>
			</div>
		</div>
	<?php endif; ?>
</div>
