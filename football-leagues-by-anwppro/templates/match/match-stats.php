<?php
/**
 * The Template for displaying Match >> Stats Section.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/match/match-stats.php.
 *
 * @since         0.9.0
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @var object $data - Object with args.
 *
 * @version       0.16.11
 */
// phpcs:disable WordPress.NamingConventions.ValidVariableName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = wp_parse_args(
	$data,
	[
		'kickoff'         => '',
		'match_date'      => '',
		'match_time'      => '',
		'home_club'       => '',
		'away_club'       => '',
		'club_home_title' => '',
		'club_away_title' => '',
		'club_home_link'  => '',
		'club_away_link'  => '',
		'club_home_logo'  => '',
		'club_away_logo'  => '',
		'match_id'        => '',
		'season_id'       => '',
		'finished'        => '',
		'home_goals'      => '',
		'away_goals'      => '',
		'match_week'      => '',
		'stadium_id'      => '',
		'competition_id'  => '',
		'main_stage_id'   => '',
		'stage_title'     => '',
		'events'          => [],
		'stats'           => [],
		'home_line_up'    => '',
		'away_line_up'    => '',
		'home_subs'       => '',
		'away_subs'       => '',
		'header'          => true,
	]
);

$color_home = get_post_meta( $data['home_club'], '_anwpfl_main_color', true );
$color_away = get_post_meta( $data['away_club'], '_anwpfl_main_color', true );

if ( empty( $color_home ) ) {
	$color_home = anwp_fl()->customizer->get_value( 'club', 'default_home_color', '#0085ba' );
}

if ( empty( $color_away ) ) {
	$color_home = anwp_fl()->customizer->get_value( 'club', 'default_away_color', '#dc3545' );
}

/**
 * Hook: anwpfl/tmpl-match/stats_before
 *
 * @param object $data Match data
 *
 * @since 0.7.5
 */
do_action( 'anwpfl/tmpl-match/stats_before', $data );

$stats_array = [
	[
		'stat'  => 'shots',
		'h'     => 'home_shots',
		'a'     => 'away_shots',
		'text'  => AnWPFL_Text::get_value( 'match__stats__shots', __( 'Shots', 'anwp-football-leagues' ) ),
		'multi' => 2,
	],
	[
		'stat'  => 'shotsOnGoals',
		'h'     => 'home_shots_on_goal',
		'a'     => 'away_shots_on_goal',
		'text'  => AnWPFL_Text::get_value( 'match__stats__shots_on_target', __( 'Shots on Target', 'anwp-football-leagues' ) ),
		'multi' => 2,
	],
	[
		'stat'  => 'fouls',
		'h'     => 'home_fouls',
		'a'     => 'away_fouls',
		'text'  => AnWPFL_Text::get_value( 'match__stats__fouls', __( 'Fouls', 'anwp-football-leagues' ) ),
		'multi' => 2,
	],
	[
		'stat'  => 'corners',
		'h'     => 'home_corners',
		'a'     => 'away_corners',
		'text'  => AnWPFL_Text::get_value( 'match__stats__corners', __( 'Corners', 'anwp-football-leagues' ) ),
		'multi' => 4,
	],
	[
		'stat'  => 'offsides',
		'h'     => 'home_offsides',
		'a'     => 'away_offsides',
		'text'  => AnWPFL_Text::get_value( 'match__stats__offsides', __( 'Offsides', 'anwp-football-leagues' ) ),
		'multi' => 4,
	],
	[
		'stat'  => 'possession',
		'h'     => 'home_possession',
		'a'     => 'away_possession',
		'text'  => AnWPFL_Text::get_value( 'match__stats__ball_possession', __( 'Ball Possession', 'anwp-football-leagues' ) ),
		'multi' => 1,
	],
	[
		'stat'  => 'yellowCards',
		'h'     => 'home_cards_y',
		'a'     => 'away_cards_y',
		'text'  => AnWPFL_Text::get_value( 'match__stats__yellow_cards', __( 'Yellow Cards', 'anwp-football-leagues' ) ),
		'multi' => 10,
	],
	[
		'stat'  => 'yellow2RCards',
		'h'     => 'home_cards_yr',
		'a'     => 'away_cards_yr',
		'text'  => AnWPFL_Text::get_value( 'match__stats__2_d_yellow_red_cards', __( '2d Yellow > Red Cards', 'anwp-football-leagues' ) ),
		'multi' => 10,
	],
	[
		'stat'  => 'redCards',
		'h'     => 'home_cards_r',
		'a'     => 'away_cards_r',
		'text'  => AnWPFL_Text::get_value( 'match__stats__red_cards', __( 'Red Cards', 'anwp-football-leagues' ) ),
		'multi' => 10,
	],
];

ob_start();

foreach ( $stats_array as $stats_value ) :
	if ( ! empty( $data[ $stats_value['h'] ] ) || ! empty( $data[ $stats_value['a'] ] ) ) :
		?>
		<div class="match-stats__stat-wrapper anwp-fl-border-bottom anwp-border-light p-2 club-stats__<?php echo esc_attr( $stats_value['stat'] ); ?>">
			<div class="match-stats__stat-name anwp-text-center anwp-text-base"><?php echo esc_html( $stats_value['text'] ); ?></div>
			<div class="d-flex mt-1 match-stats__stat-row">
				<div class="match-stats__stat-value anwp-flex-none match__stats-number mx-1 anwp-text-base"><?php echo (int) ( $data[ $stats_value['h'] ] ?? 0 ); ?></div>
				<div class="anwp-flex-1 mx-1">
					<div class="match-stats__stat-bar d-flex anwp-overflow-hidden anwp-h-20 flex-row-reverse">
						<div class="match-stats__stat-bar-inner" style="width: <?php echo (int) ( $data[ $stats_value['h'] ] ?? 0 ) * $stats_value['multi']; ?>%; background-color: <?php echo esc_attr( $color_home ); ?>"></div>
					</div>
				</div>
				<div class="anwp-flex-1 mx-1">
					<div class="match-stats__stat-bar d-flex anwp-overflow-hidden anwp-h-20">
						<div class="match-stats__stat-bar-inner" style="width: <?php echo (int) ( $data[ $stats_value['a'] ] ?? 0 ) * $stats_value['multi']; ?>%; background-color: <?php echo esc_attr( $color_away ); ?>"></div>
					</div>
				</div>
				<div class="match-stats__stat-value anwp-flex-none match__stats-number mx-1 anwp-text-base"><?php echo (int) ( $data[ $stats_value['a'] ] ?? 0 ); ?></div>
			</div>
		</div>
		<?php
	endif;
endforeach;

$stats_output = ob_get_clean();

if ( empty( $stats_output ) ) {
	return '';
}
?>
<div class="anwp-section match-stats">

	<?php
	/*
	|--------------------------------------------------------------------
	| Block Header
	|--------------------------------------------------------------------
	*/
	if ( AnWP_Football_Leagues::string_to_bool( $data['header'] ) ) {
		anwp_fl()->load_partial(
			[
				'text' => AnWPFL_Text::get_value( 'match__stats__match_statistics', __( 'Match Statistics', 'anwp-football-leagues' ) ),
			],
			'general/header'
		);
	}

	anwp_fl()->load_partial(
		[
			'home_club' => $data['home_club'],
			'away_club' => $data['away_club'],
		],
		'club/clubs-title-line'
	);

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stats_output;
	?>
</div>
