<?php
/**
 * The Template for displaying Player Data.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/shortcode-player-data.php.
 *
 * @var object $data - Object with widget data.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.11.7
 *
 * @version       0.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$data = wp_parse_args(
	$data,
	[
		'player_id' => '',
		'season_id' => '',
		'sections'  => '',
		'context'   => 'shortcode',
	]
);

if ( empty( $data['player_id'] ) || empty( $data['sections'] ) ) {
	return;
}

// Get Season ID
if ( empty( $data['season_id'] ) ) {
	$data['season_id'] = anwp_fl()->get_active_player_season( $data['player_id'] );
}

if ( ! absint( $data['season_id'] ) ) {
	return;
}

/*
|--------------------------------------------------------------------------
| Prepare player data for sections
|--------------------------------------------------------------------------
*/
$position_code = get_post_meta( $data['player_id'], '_anwpfl_position', true );

// Card icons
$card_icons = [
	'y'  => '<svg class="icon__card m-0"><use xlink:href="#icon-card_y"></use></svg>',
	'r'  => '<svg class="icon__card m-0"><use xlink:href="#icon-card_r"></use></svg>',
	'yr' => '<svg class="icon__card m-0"><use xlink:href="#icon-card_yr"></use></svg>',
];

$series_map = anwp_fl()->data->get_series();

// Get season matches
$season_matches      = anwp_fl()->player->tmpl_get_latest_matches( $data['player_id'], $data['season_id'] );
$competition_matches = anwp_fl()->player->tmpl_prepare_competition_matches( $season_matches );

$player_data = [
	'current_season_id'   => $data['season_id'],
	'competition_matches' => $competition_matches,
	'card_icons'          => $card_icons,
	'series_map'          => $series_map,
	'header'              => false,
];

$player_data += anwp_fl()->player->get_player_data( $data['player_id'] );

$player_data['club_id']       = $player_data['team_id']; // compatibility with pre v0.16.0
$player_data['position_code'] = $player_data['position']; // compatibility with pre v0.16.0

$player_data['club_title'] = anwp_fl()->club->get_club_title_by_id( $player_data['club_id'] );
$player_data['club_link']  = anwp_fl()->club->get_club_link_by_id( $player_data['club_id'] );
?>
<div class="anwp-b-wrap player player__inner player-id-<?php echo (int) $data['player_id']; ?>">
	<?php
	$player_sections = wp_parse_slug_list( $data['sections'] );

	foreach ( $player_sections as $section ) {
		anwp_fl()->load_partial( $player_data, 'player/player-' . sanitize_key( $section ) );
	}
	?>
</div>
