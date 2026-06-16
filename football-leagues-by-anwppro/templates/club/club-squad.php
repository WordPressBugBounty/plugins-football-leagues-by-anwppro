<?php
/**
 * The Template for displaying Club >> Squad Section.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/club/club-squad.php.
 *
 * @var object $data - Object with args.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.8.4
 *
 * @version       0.18.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Parse template data
$data = (object) wp_parse_args(
	$data,
	[
		'club_id'   => '',
		'season_id' => '',
	]
);

$club = get_post( $data->club_id );

/**
 * Hook: anwpfl/tmpl-club/before_squad
 *
 * @param WP_Post $club
 * @param integer $season_id
 *
 * @since 0.7.5
 */
do_action( 'anwpfl/tmpl-club/before_squad', $club, $data->season_id );

/**
 * Filter: anwpfl/tmpl-club/render_squad
 *
 * @param bool
 * @param WP_Post $club
 * @param integer $season_id
 *
 * @since 0.7.5
 */
if ( ! apply_filters( 'anwpfl/tmpl-club/render_squad', true, $club, $data->season_id ) ) {
	return;
}
?>
<div class="club-squad anwp-section">

	<?php
	/**
	 * Filter: anwpfl/tmpl-club/squad_layout
	 *
	 * @param bool
	 * @param WP_Post $club
	 * @param integer $season_id
	 *
	 * @since 0.7.5
	 */
	$squad_layout = apply_filters( 'anwpfl/tmpl-club/squad_layout', anwp_fl()->customizer->get_value( 'squad', 'club_squad_layout' ), $club, $data->season_id );

	$shortcode_args = [
		'club_id'   => $data->club_id,
		'layout'    => $squad_layout,
		'season_id' => $data->season_id,
	];

	echo anwp_football_leagues()->template->shortcode_loader( 'summary' === ( anwp_fl()->club->get_row( (int) $data->club_id )['root_type'] ?? '' ) ? 'squad-summary' : 'squad', $shortcode_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>

