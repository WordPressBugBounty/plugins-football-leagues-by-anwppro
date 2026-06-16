<?php
/**
 * The Template for displaying Standing Clubs Shortcode.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/shortcode-clubs.php.
 *
 * @var object $data - Object with shortcode data.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.10.23
 *
 * @version       0.18.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$data = (object) wp_parse_args(
	$data,
	[
		'competition_id' => '',
		'logo_size'      => 'big',
		'layout'         => '',
		'context'        => 'shortcode',
		'logo_height'    => '50px',
		'logo_width'     => '50px',
		'show_club_name' => false,
		'exclude_ids'    => '',
		'include_ids'    => '',
	]
);

if ( ! empty( $data->include_ids ) ) {
	$clubs_array = wp_parse_id_list( $data->include_ids );
} else {

	if ( empty( $data->competition_id ) ) {
		return;
	}

	$clubs_array = anwp_football_leagues()->competition->get_competition_clubs( $data->competition_id, 'all' );

	// Check exclude ids
	if ( ! empty( $data->exclude_ids ) ) {
		$clubs_array = array_diff( $clubs_array, wp_parse_id_list( $data->exclude_ids ) );
	}
}

if ( empty( $clubs_array ) ) {
	return;
}

// Prepare data - get ordered IDs, then use custom table for display data
$club_ids = get_posts(
	[
		'numberposts'      => - 1,
		'post_type'        => 'anwp_club',
		'suppress_filters' => false,
		'include'          => $clubs_array,
		'post_status'      => 'publish',
		'order'            => 'ASC',
		'orderby'          => 'title',
		'fields'           => 'ids',
	]
);

if ( empty( $club_ids ) ) {
	return;
}

anwp_football_leagues()->club->warm_clubs( $club_ids );

if ( '' === $data->layout ) : ?>
	<div class="anwp-b-wrap clubs-shortcode m-n1">
		<div class="d-flex flex-wrap">
			<?php
			foreach ( $club_ids as $club_id ) :
				$club_row = anwp_football_leagues()->club->get_club_list_row( $club_id );

				if ( ! $club_row ) {
					continue;
				}

				$logo = 'small' === $data->logo_size ? ( $club_row['logo'] ?? '' ) : ( $club_row['logo_big'] ?? '' );
				$name = $club_row['abbr'] ?: ( $club_row['title'] ?? '' );
				?>
				<div class="clubs-shortcode__wrapper club-logo position-relative anwp-text-center p-2 m-1 anwp-border anwp-border-light">
					<img loading="lazy" class="clubs-shortcode__logo anwp-object-contain mx-auto" style="width: <?php echo esc_attr( $data->logo_width ); ?>; height: <?php echo esc_attr( $data->logo_height ); ?>;"
						src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $name ); ?>">

					<?php if ( AnWP_Football_Leagues::string_to_bool( $data->show_club_name ) ) : ?>
						<div class="clubs-shortcode__text anwp-text-center anwp-text-truncate anwp-text-xs" style="width: <?php echo esc_attr( $data->logo_width ); ?>;">
							<?php echo esc_html( $name ); ?>
						</div>
					<?php endif; ?>

					<a class="anwp-link-without-effects anwp-link-cover" aria-label="<?php echo esc_attr( $club_row['title'] ?? '' ); ?>" href="<?php echo esc_url( anwp_football_leagues()->club->get_club_link_by_id( $club_id ) ); ?>"></a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php elseif ( in_array( $data->layout, [ '2col', '3col', '4col', '6col' ], true ) ) : ?>
	<div class="anwp-b-wrap clubs-shortcode">
		<div class="anwp-row anwp-no-gutters layout--grid">
			<?php
			$col_class = [
				'2col' => 'anwp-col-6',
				'3col' => 'anwp-col-4',
				'4col' => 'anwp-col-3',
				'6col' => 'anwp-col-2',
			];

			foreach ( $club_ids as $club_id ) :
				$club_row = anwp_football_leagues()->club->get_club_list_row( $club_id );

				if ( ! $club_row ) {
					continue;
				}

				$logo = 'small' === $data->logo_size ? ( $club_row['logo'] ?? '' ) : ( $club_row['logo_big'] ?? '' );
				?>
				<div class="<?php echo esc_attr( $col_class[ $data->layout ] ); ?> d-flex align-self-stretch">
					<div class="club-logo club-logo--grid w-100 d-flex flex-column m-1 p-2 anwp-border anwp-border-light">
						<a class="anwp-link-without-effects d-flex align-items-center justify-content-center w-100 h-100 anwp-club-logo--widget anwp-image-background-contain"
							style="background-image: url('<?php echo esc_attr( $logo ); ?>')"
							href="<?php echo esc_url( anwp_football_leagues()->club->get_club_link_by_id( $club_id ) ); ?>">
							<img src="<?php echo esc_attr( $logo ); ?>" style="visibility: hidden;">
						</a>
						<?php if ( AnWP_Football_Leagues::string_to_bool( $data->show_club_name ) ) : ?>
							<div class="clubs-shortcode__text anwp-text-center anwp-text-truncate anwp-text-xs py-1"><?php echo esc_html( $club_row['abbr'] ?: ( $club_row['title'] ?? '' ) ); ?></div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
endif;
