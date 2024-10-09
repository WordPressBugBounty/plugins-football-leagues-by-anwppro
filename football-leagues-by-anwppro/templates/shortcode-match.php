<?php
/**
 * The Template for displaying Match Shortcode.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/shortcode-match.php.
 *
 * @var object $data - Object with shortcode args.
 *
 * @author          Andrei Strekozov <anwp.pro>
 * @package         AnWP-Football-Leagues/Templates
 * @since           0.6.1
 *
 * @version         0.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$args = wp_parse_args(
	$data,
	[
		'match_id'        => '',
		'club_last'       => '',
		'club_next'       => '',
		'layout'          => '',
		'sections'        => '',
		'show_header'     => 1,
		'class'           => '', // TODO add to params
		'show_club_logos' => '1', // TODO add to params
	]
);

// Get match data
if ( absint( $args['match_id'] ) ) {
	$match_id = $args['match_id'];
} elseif ( absint( $args['club_last'] ) ) {

	$matches = anwp_fl()->competition->tmpl_get_competition_matches_extended(
		[
			'type'            => 'result',
			'filter_by_clubs' => $args['club_last'],
			'limit'           => 1,
			'sort_by_date'    => 'desc',
		]
	);

	if ( empty( $matches ) || empty( $matches[0]->match_id ) ) {
		return;
	}

	$match_id = $matches[0]->match_id;
} elseif ( absint( $args['club_next'] ) ) {

	$matches = anwp_fl()->competition->tmpl_get_competition_matches_extended(
		[
			'type'            => 'fixture',
			'filter_by_clubs' => $args['club_next'],
			'limit'           => 1,
			'sort_by_date'    => 'asc',
		]
	);

	if ( empty( $matches ) || empty( $matches[0]->match_id ) ) {
		return;
	}

	$match_id = $matches[0]->match_id;
}

if ( ! empty( $match_id ) ) {
	$game_data = anwp_fl()->match->get_game_data( $match_id );
}

if ( empty( $game_data ) ) {
	return;
}
?>
<div class="anwp-b-wrap match-single match--shortcode match-slim--shortcode <?php echo esc_attr( $args['class'] ); ?>">
	<?php
	// Get match data to render
	$game_data = anwp_fl()->match->prepare_match_data_to_render( $game_data, $args, 'shortcode', $args['layout'] );

	if ( AnWP_Football_Leagues::string_to_bool( $args['show_header'] ) ) {
		anwp_fl()->load_partial( $game_data, 'match/match', $args['layout'] );
	}

	if ( ! empty( $args['sections'] ) && 'slim' !== $args['layout'] ) {
		$sections = explode( ',', $args['sections'] );

		/*
		|--------------------------------------------------------------------
		| Extra Data
		|--------------------------------------------------------------------
		*/
		$game_data['summary']         = get_post_meta( $match_id, '_anwpfl_summary', true );
		$game_data['video_source']    = get_post_meta( $match_id, '_anwpfl_video_source', true );
		$game_data['video_media_url'] = get_post_meta( $match_id, '_anwpfl_video_media_url', true );
		$game_data['video_id']        = get_post_meta( $match_id, '_anwpfl_video_id', true );

		// Get extra Referees
		$game_data['assistant_1']       = get_post_meta( $match_id, '_anwpfl_assistant_1', true );
		$game_data['assistant_2']       = get_post_meta( $match_id, '_anwpfl_assistant_2', true );
		$game_data['referee_fourth_id'] = get_post_meta( $match_id, '_anwpfl_referee_fourth', true );

		// Prepare Game players
		$game_data['players'] = anwp_fl()->player->get_game_players( $game_data );

		foreach ( $sections as $section ) {
			switch ( $section ) {
				case 'line_ups':
				case 'line-ups':
					anwp_fl()->load_partial( $game_data, 'match/match-lineups' );
					break;

				default:
					anwp_fl()->load_partial( $game_data, 'match/match-' . sanitize_key( $section ) );
			}
		}
	}
	?>
</div>
