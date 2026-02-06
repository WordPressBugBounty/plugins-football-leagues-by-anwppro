<?php
/**
 * Import Data page for Football Leagues
 *
 * @link       https://anwp.pro
 * @since      0.16.0
 *
 * @package    AnWP_Football_Leagues
 * @subpackage AnWP_Football_Leagues/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'anwp-football-leagues' ) );
}

$import_options = anwp_fl()->data->get_import_options();

/*
|--------------------------------------------------------------------
| Players
|--------------------------------------------------------------------
*/
$columns_player = [
	[
		'name'    => 'player_name',
		'title'   => __( 'Player Name', 'anwp-football-leagues' ),
		'fl_tool' => 'name',
		'visible' => true,
		'width'   => 120,
	],
	[
		'name'  => 'short_name',
		'title' => __( 'Short Name', 'anwp-football-leagues' ),
		'width' => 120,
	],
	[
		'name'  => 'full_name',
		'title' => __( 'Full Name', 'anwp-football-leagues' ),
		'width' => 150,
	],
	[
		'name'    => 'weight',
		'title'   => __( 'Weight (kg)', 'anwp-football-leagues' ),
		'visible' => true,
		'type'    => 'numeric',
	],
	[
		'name'    => 'height',
		'title'   => __( 'Height (cm)', 'anwp-football-leagues' ),
		'visible' => true,
		'type'    => 'numeric',
	],
	[
		'name'         => 'position',
		'title'        => __( 'Position', 'anwp-football-leagues' ),
		'visible'      => true,
		'autocomplete' => true,
		'width'        => 150,
		'source'       => $import_options['positions'],
		'type'         => 'dropdown',
	],
	[
		'name'         => 'team_id',
		'title'        => __( 'Current Club', 'anwp-football-leagues' ),
		'type'         => 'dropdown',
		'source'       => $import_options['clubs'],
		'autocomplete' => true,
	],
	[
		'name'         => 'national_team',
		'title'        => __( 'National Team', 'anwp-football-leagues' ),
		'type'         => 'dropdown',
		'source'       => $import_options['clubs'],
		'autocomplete' => true,
	],
	[
		'name'  => 'place_of_birth',
		'title' => __( 'Place of Birth', 'anwp-football-leagues' ),
	],
	[
		'name'        => 'date_of_birth',
		'title'       => __( 'Date of Birth', 'anwp-football-leagues' ),
		'mask'        => '9999-99-99',
		'type'        => 'input',
		'placeholder' => 'YYYY-MM-DD',
	],
	[
		'name'        => 'date_of_death',
		'title'       => __( 'Date of Death', 'anwp-football-leagues' ),
		'mask'        => '9999-99-99',
		'type'        => 'input',
		'placeholder' => 'YYYY-MM-DD',
	],
	[
		'name'         => 'country_of_birth',
		'title'        => __( 'Country of Birth', 'anwp-football-leagues' ),
		'source'       => $import_options['countries'],
		'type'         => 'dropdown',
		'autocomplete' => true,
	],
	[
		'name'         => 'nationality',
		'title'        => __( 'Nationality', 'anwp-football-leagues' ),
		'source'       => $import_options['countries'],
		'type'         => 'dropdown',
		'autocomplete' => true,
	],
	[
		'name'  => 'bio',
		'type'  => 'textarea',
		'title' => __( 'Bio', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'player_id',
		'title'   => __( 'Player ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'fl_tool' => 'id',
	],
	[
		'name'    => 'player_external_id',
		'title'   => __( 'Player External ID', 'anwp-football-leagues' ),
		'fl_tool' => 'external_id',
	],
	[
		'name'  => 'custom_title_1',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #1',
	],
	[
		'name'  => 'custom_value_1',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #1',
	],
	[
		'name'  => 'custom_title_2',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #2',
	],
	[
		'name'  => 'custom_value_2',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #2',
	],
	[
		'name'  => 'custom_title_3',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #3',
	],
	[
		'name'  => 'custom_value_3',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #3',
	],
];

$player_custom_fields = AnWPFL_Options::get_value( 'player_custom_fields' );

if ( ! empty( $player_custom_fields ) && is_array( $player_custom_fields ) ) {
	foreach ( $player_custom_fields as $custom_field ) {

		$columns_player[] = [
			'name'  => 'cf__' . esc_html( $custom_field ),
			'title' => 'Custom Field: ' . esc_html( $custom_field ),
		];
	}
}

/*
|--------------------------------------------------------------------
| Stadiums
|--------------------------------------------------------------------
*/
$columns_stadium = [
	[
		'name'    => 'stadium_title',
		'title'   => __( 'Stadium Title', 'anwp-football-leagues' ),
		'visible' => true,
		'fl_tool' => 'name',
	],
	[
		'name'  => 'address',
		'title' => __( 'Address', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'city',
		'title'   => __( 'City', 'anwp-football-leagues' ),
		'visible' => true,
	],
	[
		'name'  => 'website',
		'title' => __( 'Website', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'capacity',
		'title' => __( 'Capacity', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'opened',
		'title' => __( 'Opened', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'surface',
		'title' => __( 'Surface', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'description',
		'type'  => 'textarea',
		'title' => __( 'Description', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'custom_title_1',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #1',
	],
	[
		'name'  => 'custom_value_1',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #1',
	],
	[
		'name'  => 'custom_title_2',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #2',
	],
	[
		'name'  => 'custom_value_2',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #2',
	],
	[
		'name'  => 'custom_title_3',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #3',
	],
	[
		'name'  => 'custom_value_3',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #3',
	],
	[
		'name'    => 'stadium_id',
		'title'   => __( 'Stadium ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'fl_tool' => 'id',
	],
	[
		'name'    => 'stadium_external_id',
		'title'   => __( 'Stadium External ID', 'anwp-football-leagues' ),
		'fl_tool' => 'external_id',
	],
];

$custom_fields_stadium = AnWPFL_Options::get_value( 'stadium_custom_fields' );

if ( ! empty( $custom_fields_stadium ) && is_array( $custom_fields_stadium ) ) {
	foreach ( $custom_fields_stadium as $custom_field ) {
		$columns_stadium[] = [
			'name'  => 'cf__' . esc_html( $custom_field ),
			'title' => 'Custom Field: ' . esc_html( $custom_field ),
		];
	}
}

/*
|--------------------------------------------------------------------
| Staff
|--------------------------------------------------------------------
*/
$columns_staff = [
	[
		'name'    => 'staff_name',
		'title'   => __( 'Staff Name', 'anwp-football-leagues' ),
		'visible' => true,
		'fl_tool' => 'name',
	],
	[
		'name'  => 'short_name',
		'title' => __( 'Short Name', 'anwp-football-leagues' ),
	],
	[
		'name'         => 'current_club',
		'title'        => __( 'Current Club', 'anwp-football-leagues' ),
		'type'         => 'dropdown',
		'source'       => $import_options['clubs'],
		'autocomplete' => true,
	],
	[
		'name'    => 'job_title',
		'title'   => __( 'Job Title', 'anwp-football-leagues' ),
		'visible' => true,
	],
	[
		'name'  => 'place_of_birth',
		'title' => __( 'Place of Birth', 'anwp-football-leagues' ),
	],
	[
		'name'        => 'date_of_birth',
		'title'       => __( 'Date of Birth', 'anwp-football-leagues' ),
		'mask'        => '9999-99-99',
		'type'        => 'input',
		'placeholder' => 'YYYY-MM-DD',
	],
	[
		'name'        => 'date_of_death',
		'title'       => __( 'Date of death', 'anwp-football-leagues' ),
		'mask'        => '9999-99-99',
		'type'        => 'input',
		'placeholder' => 'YYYY-MM-DD',
	],
	[
		'name'         => 'nationality_1',
		'title'        => __( 'Nationality', 'anwp-football-leagues' ),
		'source'       => $import_options['countries'],
		'type'         => 'dropdown',
		'autocomplete' => true,
	],
	[
		'name'  => 'bio',
		'title' => __( 'Bio', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'custom_title_1',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #1',
	],
	[
		'name'  => 'custom_value_1',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #1',
	],
	[
		'name'  => 'custom_title_2',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #2',
	],
	[
		'name'  => 'custom_value_2',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #2',
	],
	[
		'name'  => 'custom_title_3',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #3',
	],
	[
		'name'  => 'custom_value_3',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #3',
	],
	[
		'name'    => 'staff_id',
		'title'   => __( 'Staff ID', 'anwp-football-leagues' ),
		'fl_tool' => 'id',
		'type'    => 'numeric',
	],
	[
		'name'    => 'staff_external_id',
		'title'   => __( 'Staff External ID', 'anwp-football-leagues' ),
		'fl_tool' => 'external_id',
	],
];

$staff_custom_fields = AnWPFL_Options::get_value( 'staff_custom_fields' );

if ( ! empty( $staff_custom_fields ) && is_array( $staff_custom_fields ) ) {
	foreach ( $staff_custom_fields as $custom_field ) {
		$columns_staff[] = [
			'name'  => 'cf__' . esc_html( $custom_field ),
			'title' => 'Custom Field: ' . esc_html( $custom_field ),
		];
	}
}

/*
|--------------------------------------------------------------------
| referees
|--------------------------------------------------------------------
*/
$columns_referee = [
	[
		'name'    => 'referee_name',
		'title'   => __( 'Referee Name', 'anwp-football-leagues' ),
		'visible' => true,
		'fl_tool' => 'name',
	],
	[
		'name'  => 'short_name',
		'title' => __( 'Short Name', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'place_of_birth',
		'title' => __( 'Place of Birth', 'anwp-football-leagues' ),
	],
	[
		'name'        => 'date_of_birth',
		'title'       => __( 'Date of Birth', 'anwp-football-leagues' ),
		'mask'        => '9999-99-99',
		'type'        => 'input',
		'placeholder' => 'YYYY-MM-DD',
	],
	[
		'name'    => 'job_title',
		'title'   => __( 'Job Title', 'anwp-football-leagues' ),
		'visible' => true,
	],
	[
		'name'         => 'nationality_1',
		'title'        => __( 'Nationality', 'anwp-football-leagues' ),
		'type'         => 'dropdown',
		'source'       => $import_options['countries'],
		'autocomplete' => true,
	],
	[
		'name'    => 'referee_id',
		'title'   => __( 'Referee ID', 'anwp-football-leagues' ),
		'fl_tool' => 'id',
		'type'    => 'numeric',
	],
	[
		'name'    => 'referee_external_id',
		'title'   => __( 'Referee External ID', 'anwp-football-leagues' ),
		'fl_tool' => 'external_id',
	],
	[
		'name'  => 'custom_title_1',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #1',
	],
	[
		'name'  => 'custom_value_1',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #1',
	],
	[
		'name'  => 'custom_title_2',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #2',
	],
	[
		'name'  => 'custom_value_2',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #2',
	],
	[
		'name'  => 'custom_title_3',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #3',
	],
	[
		'name'  => 'custom_value_3',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #3',
	],
];

$custom_fields_referee = AnWPFL_Options::get_value( 'referee_custom_fields' );

if ( ! empty( $custom_fields_referee ) && is_array( $custom_fields_referee ) ) {
	foreach ( $custom_fields_referee as $custom_field ) {

		$columns_referee[] = [
			'name'  => 'cf__' . esc_html( $custom_field ),
			'title' => 'Custom Field: ' . esc_html( $custom_field ),
		];
	}
}

/*
|--------------------------------------------------------------------
| Clubs
|--------------------------------------------------------------------
*/
$columns_club = [
	[
		'name'    => 'club_title',
		'title'   => __( 'Club Title', 'anwp-football-leagues' ),
		'visible' => true,
		'fl_tool' => 'name',
	],
	[
		'name'  => 'abbreviation',
		'title' => __( 'Abbreviation', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'city',
		'title'   => __( 'City', 'anwp-football-leagues' ),
		'visible' => true,
	],
	[
		'name'         => 'country',
		'title'        => __( 'Country', 'anwp-football-leagues' ),
		'type'         => 'dropdown',
		'autocomplete' => true,
		'source'       => $import_options['countries'],
	],
	[
		'name'  => 'address',
		'title' => __( 'Address', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'website',
		'title' => __( 'Website', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'founded',
		'title' => __( 'Founded', 'anwp-football-leagues' ),
	],
	[
		'name'   => 'is_national_team',
		'title'  => __( 'National Team', 'anwp-football-leagues' ),
		'type'   => 'dropdown',
		'source' => [ 'yes', 'no' ],
	],
	[
		'name'    => 'club_id',
		'title'   => __( 'Club ID', 'anwp-football-leagues' ),
		'fl_tool' => 'id',
		'type'    => 'numeric',
	],
	[
		'name'    => 'club_external_id',
		'title'   => __( 'Club External ID', 'anwp-football-leagues' ),
		'fl_tool' => 'external_id',
	],
	[
		'name'  => 'custom_title_1',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #1',
	],
	[
		'name'  => 'custom_value_1',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #1',
	],
	[
		'name'  => 'custom_title_2',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #2',
	],
	[
		'name'  => 'custom_value_2',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #2',
	],
	[
		'name'  => 'custom_title_3',
		'title' => __( 'Custom Field - Title', 'anwp-football-leagues' ) . ' #3',
	],
	[
		'name'  => 'custom_value_3',
		'title' => __( 'Custom Field - Value', 'anwp-football-leagues' ) . ' #3',
	],
];

$team_custom_fields = AnWPFL_Options::get_value( 'club_custom_fields' );

if ( ! empty( $team_custom_fields ) && is_array( $team_custom_fields ) ) {
	foreach ( $team_custom_fields as $custom_field ) {

		$columns_club[] = [
			'name'  => 'cf__' . esc_html( $custom_field ),
			'title' => 'Custom Field: ' . esc_html( $custom_field ),
		];
	}
}

/*
|--------------------------------------------------------------------
| Games
|--------------------------------------------------------------------
*/
$columns_game_insert = [
	[
		'name'    => 'competition_id',
		'title'   => __( 'Competition ID', 'anwp-football-leagues' ),
		'visible' => true,
		'type'    => 'numeric',
		'fl_tool' => 'competition_id',
	],
	[
		'name'    => 'round',
		'title'   => __( 'Round ID', 'anwp-football-leagues' ),
		'visible' => true,
		'type'    => 'numeric',
	],
	[
		'name'    => 'club_home_id',
		'title'   => __( 'Club Home ID', 'anwp-football-leagues' ),
		'visible' => true,
		'type'    => 'numeric',
	],
	[
		'name'  => 'club_home_external_id',
		'title' => __( 'Club Home External ID', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'club_away_id',
		'title'   => __( 'Club Away ID', 'anwp-football-leagues' ),
		'visible' => true,
		'type'    => 'numeric',
	],
	[
		'name'  => 'club_away_external_id',
		'title' => __( 'Club Away External ID', 'anwp-football-leagues' ),
	],
	[
		'name'   => 'finished',
		'title'  => 'Finished',
		'type'   => 'dropdown',
		'source' => [ 'yes', 'no' ],
	],
	[
		'name'        => 'kickoff',
		'title'       => __( 'Kickoff (YYYY-MM-DD HH:MM)', 'anwp-football-leagues' ),
		'width'       => 140,
		'visible'     => true,
		'mask'        => '9999-99-99 99:99',
		'type'        => 'input',
		'placeholder' => 'YYYY-MM-DD HH:MM',
	],
	[
		'name'  => 'matchweek',
		'title' => __( 'MatchWeek', 'anwp-football-leagues' ),
		'type'  => 'numeric',
	],
	[
		'name'    => 'home_goals',
		'title'   => 'home_goals',
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'    => 'away_goals',
		'title'   => 'away_goals',
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'home_goals_half',
		'title' => 'home_goals_half',
		'type'  => 'numeric',
	],
	[
		'name'  => 'away_goals_half',
		'title' => 'away_goals_half',
		'type'  => 'numeric',
	],
	[
		'name'  => 'home_goals_ft',
		'title' => 'home_goals_ft',
		'type'  => 'numeric',
	],
	[
		'name'  => 'away_goals_ft',
		'title' => 'away_goals_ft',
		'type'  => 'numeric',
	],
	[
		'name'  => 'home_goals_e',
		'title' => 'home_goals_e',
		'type'  => 'numeric',
	],
	[
		'name'  => 'away_goals_e',
		'title' => 'away_goals_e',
		'type'  => 'numeric',
	],
	[
		'name'  => 'home_goals_p',
		'title' => 'home_goals_p',
		'type'  => 'numeric',
	],
	[
		'name'  => 'away_goals_p',
		'title' => 'away_goals_p',
		'type'  => 'numeric',
	],
	[
		'name'  => 'aggtext',
		'title' => 'Aggregate Text',
	],
	[
		'name'  => 'stadium_id',
		'title' => __( 'Stadium ID', 'anwp-football-leagues' ),
		'type'  => 'numeric',
	],
	[
		'name'  => 'stadium_external_id',
		'title' => __( 'Stadium External ID', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'attendance',
		'title' => 'Attendance',
	],
	[
		'name'  => 'referee_id',
		'title' => 'Referee ID',
	],
	[
		'name'  => 'referee_external_id',
		'title' => 'Referee External ID',
	],
	[
		'name'  => 'assistant_1_id',
		'title' => 'Ref Assistant 1 ID',
	],
	[
		'name'  => 'assistant_1_external_id',
		'title' => 'Ref Assistant 1 External ID',
	],
	[
		'name'  => 'assistant_2_id',
		'title' => 'Ref Assistant 2 ID',
	],
	[
		'name'  => 'assistant_2_external_id',
		'title' => 'Ref Assistant 2 External ID',
	],
	[
		'name'  => 'referee_fourth_id',
		'title' => 'Fourth Official ID',
	],
	[
		'name'  => 'referee_fourth_external_id',
		'title' => 'Fourth Official External ID',
	],
	[
		'name'  => 'match_summary',
		'title' => 'Match Summary',
		'type'  => 'textarea',
	],
	[
		'name'    => 'match_external_id',
		'title'   => 'Match External ID',
		'fl_tool' => 'external_id',
	],
];

/*
|--------------------------------------------------------------------
| Games - update
|--------------------------------------------------------------------
*/
$columns_game_update = [
	[
		'name'    => 'match_id',
		'title'   => __( 'Match ID', 'anwp-football-leagues' ),
		'fl_tool' => 'id',
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'    => 'match_external_id',
		'title'   => 'Match External ID',
		'fl_tool' => 'external_id',
	],
	[
		'name'   => 'finished',
		'title'  => 'Finished',
		'type'   => 'dropdown',
		'source' => [ 'yes', 'no' ],
	],
	[
		'name'        => 'kickoff',
		'title'       => __( 'Kickoff (YYYY-MM-DD HH:MM)', 'anwp-football-leagues' ),
		'width'       => 140,
		'visible'     => true,
		'mask'        => '9999-99-99 99:99',
		'type'        => 'input',
		'placeholder' => 'YYYY-MM-DD HH:MM',
	],
	[
		'name'    => 'home_goals',
		'title'   => 'home_goals',
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'    => 'away_goals',
		'title'   => 'away_goals',
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'home_goals_half',
		'title' => 'home_goals_half',
		'type'  => 'numeric',
	],
	[
		'name'  => 'away_goals_half',
		'title' => 'away_goals_half',
		'type'  => 'numeric',
	],
	[
		'name'  => 'home_goals_ft',
		'title' => 'home_goals_ft',
		'type'  => 'numeric',
	],
	[
		'name'  => 'away_goals_ft',
		'title' => 'away_goals_ft',
		'type'  => 'numeric',
	],
	[
		'name'  => 'home_goals_e',
		'title' => 'home_goals_e',
		'type'  => 'numeric',
	],
	[
		'name'  => 'away_goals_e',
		'title' => 'away_goals_e',
		'type'  => 'numeric',
	],
	[
		'name'  => 'home_goals_p',
		'title' => 'home_goals_p',
		'type'  => 'numeric',
	],
	[
		'name'  => 'away_goals_p',
		'title' => 'away_goals_p',
		'type'  => 'numeric',
	],
	[
		'name'  => 'aggtext',
		'title' => 'Aggregate Text',
	],
	[
		'name'  => 'stadium_id',
		'title' => __( 'Stadium ID', 'anwp-football-leagues' ),
		'type'  => 'numeric',
	],
	[
		'name'  => 'stadium_external_id',
		'title' => __( 'Stadium External ID', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'attendance',
		'title' => 'Attendance',
	],
	[
		'name'  => 'referee_id',
		'title' => 'Referee ID',
	],
	[
		'name'  => 'referee_external_id',
		'title' => 'Referee External ID',
	],
	[
		'name'  => 'assistant_1_id',
		'title' => 'Ref Assistant 1 ID',
	],
	[
		'name'  => 'assistant_1_external_id',
		'title' => 'Ref Assistant 1 External ID',
	],
	[
		'name'  => 'assistant_2_id',
		'title' => 'Ref Assistant 2 ID',
	],
	[
		'name'  => 'assistant_2_external_id',
		'title' => 'Ref Assistant 2 External ID',
	],
	[
		'name'  => 'referee_fourth_id',
		'title' => 'Fourth Official ID',
	],
	[
		'name'  => 'referee_fourth_external_id',
		'title' => 'Fourth Official External ID',
	],
];

/*
|--------------------------------------------------------------------
| Lineups
|--------------------------------------------------------------------
*/
$columns_lineups = [
	[
		'name'    => 'match_id',
		'title'   => __( 'Match ID', 'anwp-football-leagues' ),
		'fl_tool' => 'id',
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'    => 'match_external_id',
		'title'   => 'Match External ID',
		'fl_tool' => 'external_id',
	],
	[
		'name'    => 'player_id',
		'title'   => __( 'Player ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'player_external_id',
		'title' => __( 'Player External ID', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'player_temp',
		'title' => __( 'Temporary Player', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'club_id',
		'title'   => __( 'Club ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'club_external_id',
		'title' => __( 'Club External ID', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'number',
		'title'   => 'Number',
		'visible' => true,
	],
	[
		'name'    => 'starting',
		'title'   => __( 'Starting XI', 'anwp-football-leagues' ),
		'type'    => 'dropdown',
		'source'  => [ 'yes', 'no' ],
		'visible' => true,
	],
	[
		'name'   => 'is_captain',
		'title'  => __( 'Is Captain', 'anwp-football-leagues' ),
		'type'   => 'dropdown',
		'source' => [ 'yes', 'no' ],
	],
];

/*
|--------------------------------------------------------------------
| Goals
|--------------------------------------------------------------------
*/
$columns_goals = [
	[
		'name'    => 'match_id',
		'title'   => __( 'Match ID', 'anwp-football-leagues' ),
		'fl_tool' => 'id',
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'    => 'match_external_id',
		'title'   => 'Match External ID',
		'fl_tool' => 'external_id',
	],
	[
		'name'    => 'player_id',
		'title'   => __( 'Player ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'player_external_id',
		'title' => __( 'Player External ID', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'player_temp',
		'title' => __( 'Temporary Player', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'assistant_id',
		'title'   => __( 'Assistant ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'assistant_external_id',
		'title' => __( 'Assistant External ID', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'assistant_temp',
		'title' => __( 'Temporary Assistant', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'club_id',
		'title'   => __( 'Club ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'club_external_id',
		'title' => __( 'Club External ID', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'minute',
		'title'   => __( 'Minute', 'anwp-football-leagues' ),
		'visible' => true,
	],
	[
		'name'  => 'minute_add',
		'title' => __( 'Minute Additional', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'own_goal',
		'title'   => __( 'Own Goal', 'anwp-football-leagues' ),
		'type'    => 'dropdown',
		'source'  => [ 'yes', 'no' ],
		'visible' => true,
	],
	[
		'name'   => 'from_penalty',
		'title'  => __( 'From Penalty', 'anwp-football-leagues' ),
		'type'   => 'dropdown',
		'source' => [ 'yes', 'no' ],
	],
];

/*
|--------------------------------------------------------------------
| substitutes
|--------------------------------------------------------------------
*/
$columns_subs = [
	[
		'name'    => 'match_id',
		'title'   => __( 'Match ID', 'anwp-football-leagues' ),
		'fl_tool' => 'id',
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'    => 'match_external_id',
		'title'   => 'Match External ID',
		'fl_tool' => 'external_id',
	],
	[
		'name'    => 'player_id',
		'title'   => __( 'Player ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'player_external_id',
		'title' => __( 'Player External ID', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'player_temp',
		'title' => __( 'Temporary Player', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'player_out_id',
		'title'   => __( 'Player Out ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'player_out_external_id',
		'title' => __( 'Player Out External ID', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'player_out_temp',
		'title' => __( 'Temporary Player Out', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'club_id',
		'title'   => __( 'Club ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'club_external_id',
		'title' => __( 'Club External ID', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'minute',
		'title'   => __( 'Minute', 'anwp-football-leagues' ),
		'visible' => true,
	],
	[
		'name'  => 'minute_add',
		'title' => __( 'Minute Additional', 'anwp-football-leagues' ),
	],
];

/*
|--------------------------------------------------------------------
| cards
|--------------------------------------------------------------------
*/
$columns_cards = [
	[
		'name'    => 'match_id',
		'title'   => __( 'Match ID', 'anwp-football-leagues' ),
		'fl_tool' => 'id',
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'    => 'match_external_id',
		'title'   => 'Match External ID',
		'fl_tool' => 'external_id',
	],
	[
		'name'    => 'player_id',
		'title'   => __( 'Player ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'player_external_id',
		'title' => __( 'Player External ID', 'anwp-football-leagues' ),
	],
	[
		'name'  => 'player_temp',
		'title' => __( 'Temporary Player', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'club_id',
		'title'   => __( 'Club ID', 'anwp-football-leagues' ),
		'type'    => 'numeric',
		'visible' => true,
	],
	[
		'name'  => 'club_external_id',
		'title' => __( 'Club External ID', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'minute',
		'title'   => __( 'Minute', 'anwp-football-leagues' ),
		'visible' => true,
	],
	[
		'name'  => 'minute_add',
		'title' => __( 'Minute Additional', 'anwp-football-leagues' ),
	],
	[
		'name'    => 'cart_type',
		'title'   => __( 'Card Type', 'anwp-football-leagues' ),
		'type'    => 'dropdown',
		'source'  => [ 'y', 'r', 'yr' ],
		'visible' => true,
	],
];

/*
|--------------------------------------------------------------------
| Generate Options
|--------------------------------------------------------------------
*/
$available_options = [
	[
		'slug'    => 'players',
		'title'   => __( 'players', 'anwp-football-leagues' ),
		'columns' => $columns_player,
	],
	[
		'slug'    => 'stadiums',
		'title'   => __( 'stadiums', 'anwp-football-leagues' ),
		'columns' => $columns_stadium,
	],
	[
		'slug'    => 'clubs',
		'title'   => __( 'clubs', 'anwp-football-leagues' ),
		'columns' => $columns_club,
	],
	[
		'slug'    => 'staff',
		'title'   => __( 'staff', 'anwp-football-leagues' ),
		'columns' => $columns_staff,
	],
	[
		'slug'    => 'referees',
		'title'   => __( 'referees', 'anwp-football-leagues' ),
		'columns' => $columns_referee,
	],
	[
		'slug'    => 'games_insert',
		'title'   => 'games (insert)',
		'columns' => $columns_game_insert,
		'update'  => false,
	],
	[
		'slug'    => 'games_update',
		'title'   => 'games (update)',
		'columns' => $columns_game_update,
		'update'  => false,
	],
	[
		'slug'    => 'goals',
		'title'   => __( 'goals', 'anwp-football-leagues' ),
		'columns' => $columns_goals,
		'update'  => false,
	],
	[
		'slug'    => 'lineups',
		'title'   => __( 'lineups', 'anwp-football-leagues' ),
		'columns' => $columns_lineups,
		'update'  => false,
	],
	[
		'slug'    => 'subs',
		'title'   => __( 'substitutes', 'anwp-football-leagues' ),
		'columns' => $columns_subs,
		'update'  => false,
	],
	[
		'slug'    => 'cards',
		'title'   => __( 'cards', 'anwp-football-leagues' ),
		'columns' => $columns_cards,
		'update'  => false,
	],
];
?>
<script type="text/javascript">
	window._flImportTool            = {};
	window._flImportTool.pages      = <?php echo wp_json_encode( $available_options ); ?>;
	window._flImportTool.rest_root  = '<?php echo esc_url_raw( rest_url() ); ?>';
	window._flImportTool.rest_nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';
</script>
<style>
    .sl-toggle-green-blue {
        --toggle-bg-on: #00733a;
        --toggle-border-on: #00733a;
        --toggle-bg-off: #0d37a1;
        --toggle-border-off: #0d37a1;
        --toggle-text-off: #fff;
    }

    .sl-toggle-blue-green {
        --toggle-bg-on: #0d37a1;
        --toggle-border-on: #0d37a1;
        --toggle-bg-off: #00733a;
        --toggle-border-off: #00733a;
        --toggle-text-on: #fff;
        --toggle-text-off: #fff;
    }

    .anwp-toggle-w-80 .toggle {
        width: 80px !important;
    }

    .anwp-toggle-w-100 .toggle {
        width: 100px !important;
    }

    .toggle-label {
        width: auto !important;
        padding-left: 5px;
        padding-right: 5px;
    }
</style>
<div class="anwp-bg-blue-50 anwp-border anwp-border-blue-300 anwp-text-blue-800 anwp-rounded anwp-p-3 anwp-my-4 anwp-w-max-800" role="alert">
	<div class="anwp-d-block anwp-mb-1 anwp-w-full">
		<?php echo esc_html__( 'Select import type. Then copy and paste data from your source into the table below.', 'anwp-football-leagues' ); ?>
	</div>
	<div class="anwp-d-flex anwp-items-center">
		<svg class="anwp-icon anwp-icon--s14 anwp-icon--octi anwp-mr-1">
			<use href="#icon-info"></use>
		</svg>
		<a href="https://anwppro.userecho.com/knowledge-bases/6/articles/86-data-import-tool" target="_blank"><?php echo esc_html__( 'more info', 'anwp-football-leagues' ); ?></a><br>
	</div>
</div>

<div id="anwp-fl-batch-import-tool-app"></div>
