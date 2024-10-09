<?php
/**
 * The Template for displaying Match content.
 * Content only (without title and comments).
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/content-match.php.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.7.3
 *
 * @version       0.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// phpcs:disable WordPress.NamingConventions

$game_post = get_post();
$game_data = anwp_fl()->match->get_game_data( $game_post->ID );

if ( empty( $game_data['match_id'] ) || absint( $game_data['match_id'] ) !== $game_post->ID ) {
	return '';
}

$match_classes = [
	'match match__inner',
	'match-' . $game_post->ID,
	'match-status__' . $game_data['finished'],
	'match-fl-season--' . absint( $game_data['season_id'] ?? '' ),
	'match-fl-league--' . absint( $game_data['league_id'] ?? '' ),
	'match-fl-stage--' . absint( $game_data['competition_id'] ?? '' ),
	'match-fl-tournament--' . absint( $game_data['main_stage_id'] ?? '' ),
];
?>
	<div class="anwp-b-wrap <?php echo esc_attr( implode( ' ', $match_classes ) ); ?>">
	<?php

	// Get match data to render
	$game_data = anwp_fl()->match->prepare_match_data_to_render( $game_data, [], 'match', 'full' );

	/**
	 * Filter: anwpfl/tmpl-match/render_header
	 *
	 * @since 0.7.5
	 *
	 * @param WP_Post $match_post
	 */
	if ( apply_filters( 'anwpfl/tmpl-match/render_header', true, $game_post ) ) {
		anwp_fl()->load_partial( $game_data, 'match/match' );
	}

	do_action( 'anwpfl/tmpl-match/after_header', $game_post, $game_data );

	/*
	|--------------------------------------------------------------------
	| Sections
	|--------------------------------------------------------------------
	*/
	$match_sections = [
		'goals',
		'penalty_shootout',
		'missed_penalties',
		'lineups',
		'substitutes',
		'missing',
		'referees',
		'video',
		'cards',
		'stats',
		'summary',
		'gallery',
		'latest',
	];

	$match_sections = apply_filters( 'anwpfl/tmpl-match/sections', $match_sections, $game_data, $game_post );

	/*
	|--------------------------------------------------------------------
	| Extra Data
	|--------------------------------------------------------------------
	*/
	$game_data['summary']         = get_post_meta( $game_post->ID, '_anwpfl_summary', true );
	$game_data['video_source']    = get_post_meta( $game_post->ID, '_anwpfl_video_source', true );
	$game_data['video_media_url'] = get_post_meta( $game_post->ID, '_anwpfl_video_media_url', true );
	$game_data['video_id']        = get_post_meta( $game_post->ID, '_anwpfl_video_id', true );

	// Get extra Referees
	$game_data['assistant_1']       = get_post_meta( $game_post->ID, '_anwpfl_assistant_1', true );
	$game_data['assistant_2']       = get_post_meta( $game_post->ID, '_anwpfl_assistant_2', true );
	$game_data['referee_fourth_id'] = get_post_meta( $game_post->ID, '_anwpfl_referee_fourth', true );

	// Prepare Game players
	$game_data['players'] = anwp_fl()->player->get_game_players( $game_data );

	foreach ( $match_sections as $section ) {
		switch ( $section ) {
			case 'latest':
				if ( ! absint( $game_data['finished'] ) ) {
					anwp_fl()->load_partial( $game_data, 'match/match-latest' );
				}
				break;

			default:
				anwp_fl()->load_partial( $game_data, 'match/match-' . sanitize_key( $section ) );
		}
	}
	?>
</div>
<?php
/**
 * Hook: anwpfl/tmpl-match/after_wrapper
 *
 * @since 0.7.5
 *
 * @param WP_Post $match_post
 */
do_action( 'anwpfl/tmpl-match/after_wrapper', $game_post );
