<?php
/**
 * AnWP Football Leagues :: Data Port (import/export)
 *
 * @package AnWP_Football_Leagues
 */

class AnWPFL_Data_Port {

	/**
	 * @var AnWP_Football_Leagues
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @param AnWP_Football_Leagues $plugin Main plugin object.
	 */
	public function __construct( AnWP_Football_Leagues $plugin ) {
		$this->plugin = $plugin;

		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 */
	public function hooks() {
		add_action( 'admin_init', [ $this, 'download_csv' ] );
		add_action( 'rest_api_init', [ $this, 'add_rest_routes' ] );
	}

	/**
	 * Register REST routes.
	 *
	 * @since 0.9.2
	 */
	public function add_rest_routes() {
		register_rest_route(
			'anwpfl',
			'/import-tool/(?P<type>[a-z_]+)/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_import_data' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/**
	 * Download CSV files.
	 *
	 * @since 0.12.0
	 */
	public function download_csv() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_GET['anwp_export'] ) ) {
			return;
		}

		// Check if we are in WP-Admin
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$export_type = sanitize_key( $_GET['anwp_export'] );

		switch ( $export_type ) {
			case 'players':
				$this->download_csv_players();
				break;

			case 'games':
				$this->download_csv_games();
				break;
		}
	}

	/**
	 * Download CSV files - Players.
	 *
	 * @since 0.12.0
	 */
	private function download_csv_players() {
		global $wpdb;

		/*
		|--------------------------------------------------------------------
		| Mapping Data
		|--------------------------------------------------------------------
		*/
		$map_positions = anwp_fl()->data->get_positions();
		$map_clubs     = anwp_fl()->club->get_clubs_options();
		$map_countries = anwp_fl()->data->cb_get_countries();

		$custom_fields = AnWPFL_Options::get_value( 'player_custom_fields' );

		$header_row = [
			'Player Name',
			'Short Name',
			'Full Name',
			'Weight (kg)',
			'Height (cm)',
			'Position',
			'National Team',
			'Current Club',
			'Place of Birth',
			'Country of Birth',
			'Date of Birth',
			'Date of Death',
			'Bio',
			'Nationality #1',
			'Nationality #2',
			'Custom Field - Title #1',
			'Custom Field - Value #1',
			'Custom Field - Title #2',
			'Custom Field - Value #2',
			'Custom Field - Title #3',
			'Custom Field - Value #3',
			'Player ID',
			'Player External ID',
		];

		if ( ! empty( $custom_fields ) && is_array( $custom_fields ) ) {
			$header_row = array_merge( $header_row, $custom_fields );
		}

		$data_rows = [];

		$players = $wpdb->get_results(
			"
				SELECT *
				FROM $wpdb->anwpfl_player_data
			",
			OBJECT_K
		);

		$posts = get_posts(
			[
				'numberposts' => - 1,
				'post_type'   => 'anwp_player',
			]
		);

		/** @var $p WP_Post */
		foreach ( $posts as $p ) {
			if ( empty( $players[ $p->ID ] ) ) {
				continue;
			}

			$player_data = (array) $players[ $p->ID ];

			/*
			|--------------------------------------------------------------------
			| Prepare Nationality data
			|--------------------------------------------------------------------
			*/
			$player_nationality   = [ $player_data['nationality'] ];
			$player_nationality_1 = '';
			$player_nationality_2 = '';

			if ( ! empty( $player_data['nationality_extra'] ) ) {
				$player_nationality = array_merge( $player_nationality, explode( '%', trim( $player_data['nationality_extra'], '%' ) ) );
			}

			if ( ! empty( $player_nationality[0] ) && ! empty( $map_countries[ $player_nationality[0] ] ) ) {
				$player_nationality_1 = $map_countries[ $player_nationality[0] ];
			}

			if ( ! empty( $player_nationality[1] ) && ! empty( $map_countries[ $player_nationality[1] ] ) ) {
				$player_nationality_2 = $map_countries[ $player_nationality[1] ];
			}

			$country_of_birth = $player_data['country_of_birth'];

			if ( ! empty( $country_of_birth ) ) {
				$country_of_birth = $map_countries[ $country_of_birth ] ?? '';
			}

			$single_row_data = [
				$player_data['name'],
				$player_data['short_name'],
				$player_data['full_name'],
				$player_data['weight'],
				$player_data['height'],
				$map_positions[ $player_data['position'] ] ?? '',
				$map_clubs[ $player_data['national_team'] ] ?? '',
				$map_clubs[ $player_data['team_id'] ] ?? '',
				$player_data['place_of_birth'],
				$country_of_birth,
				$player_data['date_of_birth'],
				$player_data['date_of_death'],
				get_post_meta( $p->ID, '_anwpfl_description', true ),
				$player_nationality_1,
				$player_nationality_2,
				get_post_meta( $p->ID, '_anwpfl_custom_title_1', true ),
				get_post_meta( $p->ID, '_anwpfl_custom_value_1', true ),
				get_post_meta( $p->ID, '_anwpfl_custom_title_2', true ),
				get_post_meta( $p->ID, '_anwpfl_custom_value_2', true ),
				get_post_meta( $p->ID, '_anwpfl_custom_title_3', true ),
				get_post_meta( $p->ID, '_anwpfl_custom_value_3', true ),
				$p->ID,
				$player_data['player_external_id'],
			];

			/*
			|--------------------------------------------------------------------
			| Custom fields
			|--------------------------------------------------------------------
			*/
			if ( ! empty( $custom_fields ) && is_array( $custom_fields ) ) {
				$custom_fields_data = get_post_meta( $p->ID, '_anwpfl_custom_fields', true );

				foreach ( $custom_fields as $custom_field ) {
					if ( ! empty( $custom_fields_data ) && is_array( $custom_fields_data ) && ! empty( $custom_fields_data[ $custom_field ] ) ) {
						$single_row_data[] = $custom_fields_data[ $custom_field ];
					} else {
						$single_row_data[] = '';
					}
				}
			}

			$data_rows[] = $single_row_data;
		}

		ob_start();

		$fh = @fopen( 'php://output', 'w' ); // phpcs:ignore

		fprintf( $fh, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv' );
		header( 'Content-Disposition: attachment; filename=players.csv' );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		fputcsv( $fh, $header_row );

		foreach ( $data_rows as $data_row ) {
			fputcsv( $fh, $data_row );
		}

		fclose( $fh ); // phpcs:ignore

		ob_end_flush();

		die();
	}

	/**
	 * Download CSV files - Games.
	 *
	 * @since 0.16.0
	 */
	private function download_csv_games() {
		global $wpdb;

		$competition_id = absint( $_GET['competition_id'] ?? 0 ); // phpcs:ignore

		if ( empty( $competition_id ) ) {
			return;
		}

		$map_clubs = anwp_fl()->club->get_clubs_options();

		$header_row = [
			'Game ID',
			'Kickoff',
			'Home Team',
			'Away Team',
			'Home Score',
			'Away Score',
			'Home Score Half',
			'Away Score Half',
			'Finished',
			'Home Team ID',
			'Away Team ID',
			'MatchWeek',
			'Special Status',
			'Attendance',
			'Home Score Full Time',
			'Away Score Full Time',
			'Home Score Penalty',
			'Away Score Penalty',
			'Home Captain ID',
			'Away Captain ID',
		];

		if ( ! empty( $custom_fields ) && is_array( $custom_fields ) ) {
			$header_row = array_merge( $header_row, $custom_fields );
		}

		$data_rows = [];

		$games = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT a.*, b.captain_home, b.captain_away
				FROM $wpdb->anwpfl_matches a
				LEFT JOIN {$wpdb->prefix}anwpfl_lineups b ON b.match_id = a.match_id
				WHERE a.competition_id = %d OR a.main_stage_id = %d
				",
				$competition_id,
				$competition_id
			),
			ARRAY_A
		);

		foreach ( $games as $game ) {
			$data_rows[] = [
				$game['match_id'],
				$game['kickoff'] ?? '',
				$map_clubs[ $game['home_club'] ] ?? '',
				$map_clubs[ $game['away_club'] ] ?? '',
				$game['home_goals'],
				$game['away_goals'],
				$game['home_goals_half'],
				$game['away_goals_half'],
				$game['finished'],
				$game['home_club'],
				$game['away_club'],
				$game['match_week'],
				$game['special_status'],
				$game['attendance'] ? : '',
				$game['home_goals_ft'],
				$game['away_goals_ft'],
				$game['home_goals_p'],
				$game['away_goals_p'],
				$game['captain_home'],
				$game['captain_away'],
			];
		}

		ob_start();

		$fh = @fopen( 'php://output', 'w' ); // phpcs:ignore

		fprintf( $fh, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv' );
		header( 'Content-Disposition: attachment; filename=games.csv' );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		fputcsv( $fh, $header_row );

		foreach ( $data_rows as $data_row ) {
			fputcsv( $fh, $data_row );
		}

		fclose( $fh ); // phpcs:ignore

		ob_end_flush();

		die();
	}

	/**
	 * Handle import Rest request.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function save_import_data( WP_REST_Request $request ) {

		$params = $request->get_params();

		$import_status = [
			'result'     => 'error',
			'post_title' => '',
			'post_url'   => '',
			'post_edit'  => '',
		];

		switch ( $params['type'] ) {

			case 'players':
				$import_status = $this->import_players( $params, $import_status );
				break;

			case 'clubs':
				$import_status = $this->import_clubs( $params, $import_status );
				break;

			case 'stadiums':
				$import_status = $this->import_stadiums( $params, $import_status );
				break;

			case 'staff':
				$import_status = $this->import_staff( $params, $import_status );
				break;

			case 'referees':
				$import_status = $this->import_referees( $params, $import_status );
				break;

			case 'games_insert':
				$import_status = $this->import_games_insert( $params, $import_status );
				break;

			case 'games_update':
				$import_status = $this->import_games_update( $params, $import_status );
				break;

			case 'goals':
				$import_status = $this->import_goals( $params, $import_status );
				break;

			case 'cards':
				$import_status = $this->import_cards( $params, $import_status );
				break;

			case 'lineups':
				$import_status = $this->import_lineups( $params, $import_status );
				break;

			case 'subs':
				$import_status = $this->import_subs( $params, $import_status );
				break;
		}

		return rest_ensure_response( $import_status );
	}

	/**
	 * Import Matches.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_games_insert( array $params, array $import_status ): array {

		$current_user_id = get_current_user_id();
		$current_time    = current_time( 'mysql' );

		$row_data        = $params['row_data'];
		$table_game_data = [];

		$game_data = [
			'post_title'   => '',
			'post_content' => '',
			'post_type'    => 'anwp_match',
			'post_status'  => 'publish',
			'post_author'  => $current_user_id,
			'meta_input'   => [
				'_anwpfl_import_time' => $current_time,
			],
		];

		if ( empty( $row_data['competition_id'] ) || ! absint( $row_data['competition_id'] ) ) {
			$import_status['post_title'] = 'Invalid Competition ID';

			return $import_status;
		}

		$competition_obj = anwp_fl()->competition->get_competition( $row_data['competition_id'] );

		if ( empty( $competition_obj ) || ! in_array( $competition_obj->type, [ 'knockout', 'round-robin' ], true ) ) {
			$import_status['post_title'] = 'Invalid Competition Data';

			return $import_status;
		}

		$table_game_data['competition_id'] = absint( $row_data['competition_id'] );

		if ( 'secondary' === $competition_obj->multistage ) {
			$table_game_data['main_stage_id'] = $competition_obj->multistage_main;
		}

		/*
		|--------------------------------------------------------------------
		| Get Team IDs
		|--------------------------------------------------------------------
		*/
		// Get Home and Away Clubs IDs
		$table_game_data['home_club'] = absint( $row_data['club_home_id'] ?? 0 );
		$table_game_data['away_club'] = absint( $row_data['club_away_id'] ?? 0 );

		if ( empty( $row_data['club_home_id'] ) && ! empty( $row_data['club_home_external_id'] ) ) {
			$table_game_data['home_club'] = absint( anwp_fl()->club->get_club_id_by_external_id( sanitize_text_field( $row_data['club_home_external_id'] ) ) );
		}

		if ( empty( $row_data['club_away_id'] ) && ! empty( $row_data['club_away_external_id'] ) ) {
			$table_game_data['away_club'] = absint( anwp_fl()->club->get_club_id_by_external_id( sanitize_text_field( $row_data['club_away_external_id'] ) ) );
		}

		if ( ! $table_game_data['home_club'] || ! $table_game_data['away_club'] ) {
			$import_status['post_title'] = 'Invalid Team IDs';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Prepare structure data
		|--------------------------------------------------------------------
		*/
		$group_id = '';
		$round_id = absint( $row_data['round'] ?? 0 );

		foreach ( $competition_obj->groups as $c_group ) {
			if ( $round_id && absint( $c_group->round ) !== $round_id ) {
				continue;
			}

			$group_clubs = array_unique( array_map( 'absint', $c_group->clubs ) );

			if ( in_array( $table_game_data['home_club'], $group_clubs, true ) && in_array( $table_game_data['away_club'], $group_clubs, true ) ) {
				$table_game_data['group_id'] = absint( $c_group->id );
				$round_id                    = absint( $c_group->round );

				break;
			}
		}

		if ( ! $table_game_data['group_id'] ) {
			$import_status['post_title'] = 'Invalid Group ID';

			return $import_status;
		}

		$table_game_data['season_id'] = intval( $competition_obj->season_ids );
		$table_game_data['league_id'] = intval( $competition_obj->league_id );

		// Set MatchWeek for Round Robin competition
		if ( 'round-robin' === $competition_obj->type ) {
			$table_game_data['match_week'] = absint( $row_data['matchweek'] ?? 0 );
		} else {
			$table_game_data['match_week'] = absint( $round_id );
		}

		foreach ( $row_data as $slug => $value ) {
			if ( empty( $value ) || in_array( $slug, [ 'import_info', 'import_status' ], true ) ) {
				continue;
			}

			switch ( $slug ) {
				case 'home_goals':
				case 'away_goals':
				case 'home_goals_half':
				case 'away_goals_half':
				case 'home_goals_ft':
				case 'away_goals_ft':
				case 'home_goals_e':
				case 'away_goals_e':
				case 'home_goals_p':
				case 'away_goals_p':
				case 'aggtext':
				case 'attendance':
					$table_game_data[ $slug ] = sanitize_text_field( $value );
					break;

				case 'match_external_id':
					$game_data['meta_input']['_anwpfl_match_external_id'] = sanitize_text_field( $value );
					break;

				case 'match_summary':
					$game_data['meta_input']['_anwpfl_summary'] = sanitize_textarea_field( $value );
					break;
			}
		}

		/*
		|--------------------------------------------------------------------
		| Game data
		|--------------------------------------------------------------------
		*/
		if ( ! empty( $row_data['stadium_id'] ) ) {
			$table_game_data['stadium_id'] = absint( $row_data['stadium_id'] );
		} elseif ( ! empty( $row_data['stadium_external_id'] ) ) {
			$table_game_data['stadium_id'] = anwp_fl()->stadium->get_stadium_id_by_external_id( $row_data['stadium_external_id'] );
		}

		$table_game_data['finished'] = 'yes' === ( $row_data['finished'] ?? '' ) ? 1 : 0;

		if ( ! empty( $row_data['kickoff'] ) ) {
			$match_date = DateTime::createFromFormat( 'Y-m-d H:i', sanitize_text_field( $row_data['kickoff'] . ( mb_strpos( $row_data['kickoff'], ':' ) ? '' : ' 00:00' ) ) );

			if ( $match_date && anwp_fl()->helper->validate_date( $match_date->format( 'Y-m-d H:i:s' ) ) ) {
				$table_game_data['kickoff'] = $match_date->format( 'Y-m-d H:i:s' );
			}
		}

		if ( 'friendly' === get_post_meta( $table_game_data['competition_id'], '_anwpfl_competition_status', true ) ) {
			$table_game_data['game_status'] = 0;
		}

		$extra_time = '' !== ( $row_data['home_goals_e'] ?? '' ) && '' !== ( $row_data['away_goals_e'] ?? '' );
		$penalty    = '' !== ( $row_data['home_goals_p'] ?? '' ) && '' !== ( $row_data['away_goals_p'] ?? '' );

		$table_game_data['extra'] = $penalty ? ( $extra_time ? 2 : 3 ) : ( $extra_time ? 1 : 0 );

		foreach ( [ 'referee', 'assistant_1', 'assistant_2', 'referee_fourth' ] as $ref_slug ) {
			$maybe_ref_id = absint( $row_data[ $ref_slug . '_id' ] ?? 0 );

			if ( empty( $maybe_ref_id ) && ! empty( $row_data[ $ref_slug . '_external_id' ] ) ) {
				$maybe_ref_id = anwp_fl()->referee->get_referee_id_by_external_id( $row_data[ $ref_slug . '_external_id' ] );
			}

			if ( ! empty( $maybe_ref_id ) ) {
				if ( 'referee' === $ref_slug ) {
					$table_game_data['referee'] = $maybe_ref_id;
				} else {
					$game_data['meta_input'][ '_anwpfl_' . $ref_slug ] = $maybe_ref_id;
				}
			}
		}

		/*
		|--------------------------------------------------------------------
		| Save post
		|--------------------------------------------------------------------
		*/
		$post_id = wp_insert_post( $game_data );

		if ( absint( $post_id ) ) {
			$table_game_data['match_id'] = $post_id;
			anwp_fl()->match->insert( $table_game_data );

			// Update Match title and slug.
			$home_club_title = anwp_fl()->club->get_club_title_by_id( $table_game_data['home_club'] );
			$away_club_title = anwp_fl()->club->get_club_title_by_id( $table_game_data['away_club'] );

			if ( trim( AnWPFL_Options::get_value( 'match_title_generator' ) ) ) {
				$match_title = anwp_fl()->match->get_match_title_generated( $table_game_data, $home_club_title, $away_club_title );
			} else {
				$match_title_separator = AnWPFL_Options::get_value( 'match_title_separator', '-' );
				$match_title           = sanitize_text_field( $home_club_title . ' ' . $match_title_separator . ' ' . $away_club_title );
			}

			$match_slug = anwp_fl()->match->get_match_slug_generated( $table_game_data, $home_club_title, $away_club_title, get_post( $post_id ) );

			// Rename Match (title and slug)
			wp_update_post(
				[
					'ID'         => $post_id,
					'post_title' => $match_title,
					'post_name'  => $match_slug,
				]
			);

			$post_obj = get_post( $post_id );

			$import_status['result']     = 'success';
			$import_status['post_title'] = $post_obj->post_title;
			$import_status['post_url']   = get_permalink( $post_obj );
			$import_status['post_edit']  = get_edit_post_link( $post_obj );
		}

		return $import_status;
	}

	/**
	 * Import Matches.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_games_update( array $params, array $import_status ): array {

		$row_data        = $params['row_data'];
		$table_game_data = [];

		$game_data = [
			'post_type'   => 'anwp_match',
			'post_status' => 'publish',
			'ID'          => '',
		];

		if ( ! empty( $row_data['match_id'] ) ) {
			if ( 'anwp_match' === get_post_type( absint( $row_data['match_id'] ) ) ) {
				$game_data['ID'] = absint( $row_data['match_id'] );
			}
		} elseif ( ! empty( $row_data['match_external_id'] ) ) {
			$maybe_game_id = anwp_fl()->match->get_match_id_by_external_id( $row_data['match_external_id'] );

			if ( ! empty( $maybe_game_id ) ) {
				$game_data['ID'] = absint( $maybe_game_id );
			}
		}

		if ( empty( $game_data['ID'] ) ) {
			$import_status['post_title'] = 'Invalid Match ID or External Match ID';

			return $import_status;
		}

		foreach ( $row_data as $slug => $value ) {
			if ( empty( $value ) || in_array( $slug, [ 'import_info', 'import_status' ], true ) ) {
				continue;
			}

			switch ( $slug ) {
				case 'home_goals':
				case 'away_goals':
				case 'home_goals_half':
				case 'away_goals_half':
				case 'home_goals_ft':
				case 'away_goals_ft':
				case 'home_goals_e':
				case 'away_goals_e':
				case 'home_goals_p':
				case 'away_goals_p':
				case 'aggtext':
				case 'attendance':
					$table_game_data[ $slug ] = sanitize_text_field( $value );
					break;

				case 'match_external_id':
					$game_data['meta_input']['_anwpfl_match_external_id'] = sanitize_text_field( $value );
					break;

				case 'match_summary':
					$game_data['meta_input']['_anwpfl_summary'] = sanitize_textarea_field( $value );
					break;
			}
		}

		/*
		|--------------------------------------------------------------------
		| Game data
		|--------------------------------------------------------------------
		*/
		if ( ! empty( $row_data['stadium_id'] ) ) {
			$table_game_data['stadium_id'] = absint( $row_data['stadium_id'] );
		} elseif ( ! empty( $row_data['stadium_external_id'] ) ) {
			$table_game_data['stadium_id'] = anwp_fl()->stadium->get_stadium_id_by_external_id( $row_data['stadium_external_id'] );
		}

		if ( isset( $row_data['finished'] ) ) {
			$table_game_data['finished'] = 'yes' === $row_data['finished'] ? 1 : 0;
		}

		if ( ! empty( $row_data['kickoff'] ) ) {
			$match_date = DateTime::createFromFormat( 'Y-m-d H:i', sanitize_text_field( $row_data['kickoff'] . ( mb_strpos( $row_data['kickoff'], ':' ) ? '' : ' 00:00' ) ) );

			if ( $match_date && anwp_fl()->helper->validate_date( $match_date->format( 'Y-m-d H:i:s' ) ) ) {
				$table_game_data['kickoff'] = $match_date->format( 'Y-m-d H:i:s' );
			}
		}

		if ( isset( $row_data['home_goals_e'] ) || isset( $row_data['home_goals_p'] ) ) {
			$extra_time = '' !== ( $row_data['home_goals_e'] ?? '' ) && '' !== ( $row_data['away_goals_e'] ?? '' );
			$penalty    = '' !== ( $row_data['home_goals_p'] ?? '' ) && '' !== ( $row_data['away_goals_p'] ?? '' );

			$table_game_data['extra'] = $penalty ? ( $extra_time ? 2 : 3 ) : ( $extra_time ? 1 : 0 );
		}

		foreach ( [ 'referee', 'assistant_1', 'assistant_2', 'referee_fourth' ] as $ref_slug ) {
			$maybe_ref_id = absint( $row_data[ $ref_slug . '_id' ] ?? 0 );

			if ( empty( $maybe_ref_id ) && ! empty( $row_data[ $ref_slug . '_external_id' ] ) ) {
				$maybe_ref_id = anwp_fl()->referee->get_referee_id_by_external_id( $row_data[ $ref_slug . '_external_id' ] );
			}

			if ( ! empty( $maybe_ref_id ) ) {
				if ( 'referee' === $ref_slug ) {
					$table_game_data['referee'] = $maybe_ref_id;
				} else {
					$game_data['meta_input'][ '_anwpfl_' . $ref_slug ] = $maybe_ref_id;
				}
			}
		}

		/*
		|--------------------------------------------------------------------
		| Save post
		|--------------------------------------------------------------------
		*/
		$post_id = wp_update_post( $game_data );

		if ( absint( $post_id ) ) {
			anwp_fl()->match->update( $post_id, $table_game_data );

			$table_game_data = anwp_fl()->match->get_game_data( $post_id );

			// Update Match title and slug.
			$home_club_title = anwp_fl()->club->get_club_title_by_id( $table_game_data['home_club'] );
			$away_club_title = anwp_fl()->club->get_club_title_by_id( $table_game_data['away_club'] );

			if ( trim( AnWPFL_Options::get_value( 'match_title_generator' ) ) ) {
				$match_title = anwp_fl()->match->get_match_title_generated( $table_game_data, $home_club_title, $away_club_title );
			} else {
				$match_title_separator = AnWPFL_Options::get_value( 'match_title_separator', '-' );
				$match_title           = sanitize_text_field( $home_club_title . ' ' . $match_title_separator . ' ' . $away_club_title );
			}

			$match_slug = anwp_fl()->match->get_match_slug_generated( $table_game_data, $home_club_title, $away_club_title, get_post( $post_id ) );

			// Rename Match (title and slug)
			wp_update_post(
				[
					'ID'         => $post_id,
					'post_title' => $match_title,
					'post_name'  => $match_slug,
				]
			);

			$post_obj = get_post( $post_id );

			$import_status['result']     = 'success';
			$import_status['post_title'] = $post_obj->post_title;
			$import_status['post_url']   = get_permalink( $post_obj );
			$import_status['post_edit']  = get_edit_post_link( $post_obj );
		}

		return $import_status;
	}

	/**
	 * Import Clubs.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_clubs( array $params, array $import_status ): array {

		$current_user_id = get_current_user_id();
		$current_time    = current_time( 'mysql' );

		$row_data    = $params['row_data'];
		$insert_mode = 'insert' === $params['mode'];

		$custom_fields_data = [];

		if ( $insert_mode ) {
			$club_data = [
				'post_title'   => '',
				'post_content' => '',
				'post_type'    => 'anwp_club',
				'post_status'  => 'publish',
				'post_author'  => $current_user_id,
				'meta_input'   => [
					'_anwpfl_import_time' => $current_time,
				],
			];

			if ( empty( trim( $row_data['club_title'] ) ) ) {
				$import_status['post_title'] = 'Empty Name not allowed';

				return $import_status;
			}
		} else {
			$club_data = [
				'ID'         => '',
				'post_type'  => 'anwp_club',
				'meta_input' => [
					'_anwpfl_import_time' => $current_time,
				],
			];

			if ( ! empty( $row_data['club_id'] ) ) {
				if ( 'anwp_club' === get_post_type( absint( $row_data['club_id'] ) ) ) {
					$club_data['ID'] = absint( $row_data['club_id'] );
				}
			} elseif ( ! empty( $row_data['club_external_id'] ) ) {
				$maybe_club_id = anwp_fl()->club->get_club_id_by_external_id( $row_data['club_external_id'] );

				if ( ! empty( $maybe_club_id ) ) {
					$club_data['ID'] = absint( $maybe_club_id );
				}
			}

			if ( empty( $club_data['ID'] ) ) {
				$import_status['post_title'] = 'Invalid Club ID or External Club ID';

				return $import_status;
			}
		}

		foreach ( $row_data as $slug => $value ) {
			if ( empty( $value ) || in_array( $slug, [ 'import_info', 'import_status' ], true ) ) {
				continue;
			}

			switch ( $slug ) {
				case 'club_title':
					$club_data['post_title'] = sanitize_text_field( $value );
					break;

				case 'abbreviation':
					$club_data['meta_input']['_anwpfl_abbr'] = sanitize_text_field( $value );
					break;

				case 'city':
					$club_data['meta_input']['_anwpfl_city'] = sanitize_text_field( $value );
					break;

				case 'address':
					$club_data['meta_input']['_anwpfl_address'] = sanitize_text_field( $value );
					break;

				case 'website':
					$club_data['meta_input']['_anwpfl_website'] = sanitize_text_field( $value );
					break;

				case 'founded':
					$club_data['meta_input']['_anwpfl_founded'] = sanitize_text_field( $value );
					break;

				case 'country':
					$club_data['meta_input']['_anwpfl_nationality'] = sanitize_text_field( $value );
					break;

				case 'is_national_team':
					$club_data['meta_input']['_anwpfl_is_national_team'] = 'yes' === sanitize_text_field( $value ) ? 'yes' : '';
					break;

				case 'club_external_id':
					$club_data['meta_input']['_anwpfl_club_external_id'] = sanitize_text_field( $value );
					break;

				default:
					if ( 0 === mb_strpos( $slug, 'cf__' ) ) {

						$maybe_custom_field = mb_substr( $slug, 4 );

						if ( ! empty( $maybe_custom_field ) ) {
							$custom_fields_data[ $maybe_custom_field ] = sanitize_text_field( $value );
						}
					}
			}
		}

		// Custom Fields
		if ( ! empty( $custom_fields_data ) ) {
			$custom_fields_old = get_post_meta( $club_data['ID'], '_anwpfl_custom_fields', true );

			if ( ! empty( $custom_fields_old ) && is_array( $custom_fields_old ) ) {
				$custom_fields_data = array_merge( $custom_fields_old, $custom_fields_data );
			}
		}

		if ( ! empty( $custom_fields_data ) ) {
			$club_data['meta_input']['_anwpfl_custom_fields'] = $custom_fields_data;
		}

		$post_id = $insert_mode ? wp_insert_post( $club_data ) : wp_update_post( $club_data );

		if ( absint( $post_id ) ) {
			$post_obj = get_post( $post_id );

			$import_status['result']     = 'success';
			$import_status['post_title'] = $post_obj->post_title;
			$import_status['post_url']   = get_permalink( $post_obj );
			$import_status['post_edit']  = get_edit_post_link( $post_obj );
		}

		return $import_status;
	}

	/**
	 * Import Stadiums.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_stadiums( array $params, array $import_status ): array {

		$current_user_id = get_current_user_id();
		$current_time    = current_time( 'mysql' );

		$row_data    = $params['row_data'];
		$insert_mode = 'insert' === $params['mode'];

		$custom_fields_data = [];

		if ( $insert_mode ) {
			$stadium_data = [
				'post_title'   => '',
				'post_content' => '',
				'post_type'    => 'anwp_stadium',
				'post_status'  => 'publish',
				'post_author'  => $current_user_id,
				'meta_input'   => [
					'_anwpfl_import_time' => $current_time,
				],
			];

			if ( empty( trim( $row_data['stadium_title'] ) ) ) {
				$import_status['post_title'] = 'Empty Name not allowed';

				return $import_status;
			}
		} else {
			$stadium_data = [
				'ID'         => '',
				'post_type'  => 'anwp_stadium',
				'meta_input' => [
					'_anwpfl_import_time' => $current_time,
				],
			];

			if ( ! empty( $row_data['stadium_id'] ) ) {
				if ( 'anwp_stadium' === get_post_type( absint( $row_data['stadium_id'] ) ) ) {
					$stadium_data['ID'] = absint( $row_data['stadium_id'] );
				}
			} elseif ( ! empty( $row_data['stadium_external_id'] ) ) {
				$maybe_stadium_id = anwp_fl()->stadium->get_stadium_id_by_external_id( $row_data['stadium_external_id'] );

				if ( ! empty( $maybe_stadium_id ) ) {
					$stadium_data['ID'] = absint( $maybe_stadium_id );
				}
			}

			if ( empty( $stadium_data['ID'] ) ) {
				$import_status['post_title'] = 'Invalid Stadium ID or External Stadium ID';

				return $import_status;
			}
		}

		foreach ( $row_data as $slug => $value ) {
			if ( empty( $value ) || in_array( $slug, [ 'import_info', 'import_status' ], true ) ) {
				continue;
			}

			switch ( $slug ) {
				case 'stadium_title':
					$stadium_data['post_title'] = sanitize_text_field( $value );
					break;

				case 'city':
					$stadium_data['meta_input']['_anwpfl_city'] = sanitize_text_field( $value );
					break;

				case 'address':
					$stadium_data['meta_input']['_anwpfl_address'] = sanitize_text_field( $value );
					break;

				case 'website':
					$stadium_data['meta_input']['_anwpfl_website'] = sanitize_text_field( $value );
					break;

				case 'capacity':
					$stadium_data['meta_input']['_anwpfl_capacity'] = sanitize_text_field( $value );
					break;

				case 'opened':
					$stadium_data['meta_input']['_anwpfl_opened'] = sanitize_text_field( $value );
					break;

				case 'surface':
					$stadium_data['meta_input']['_anwpfl_surface'] = sanitize_text_field( $value );
					break;

				case 'description':
					$stadium_data['meta_input']['_anwpfl_description'] = sanitize_text_field( $value );
					break;

				case 'stadium_external_id':
					$stadium_data['meta_input']['_anwpfl_stadium_external_id'] = sanitize_text_field( $value );
					break;

				case 'custom_title_1':
					$stadium_data['meta_input']['_anwpfl_custom_title_1'] = sanitize_text_field( $value );
					break;

				case 'custom_title_2':
					$stadium_data['meta_input']['_anwpfl_custom_title_2'] = sanitize_text_field( $value );
					break;

				case 'custom_title_3':
					$stadium_data['meta_input']['_anwpfl_custom_title_3'] = sanitize_text_field( $value );
					break;

				case 'custom_value_1':
					$stadium_data['meta_input']['_anwpfl_custom_value_1'] = sanitize_text_field( $value );
					break;

				case 'custom_value_2':
					$stadium_data['meta_input']['_anwpfl_custom_value_2'] = sanitize_text_field( $value );
					break;

				case 'custom_value_3':
					$stadium_data['meta_input']['_anwpfl_custom_value_3'] = sanitize_text_field( $value );
					break;

				default:
					if ( 0 === mb_strpos( $slug, 'cf__' ) ) {

						$maybe_custom_field = mb_substr( $slug, 4 );

						if ( ! empty( $maybe_custom_field ) ) {
							$custom_fields_data[ $maybe_custom_field ] = sanitize_text_field( $value );
						}
					}
			}
		}

		// Custom Fields
		if ( ! empty( $custom_fields_data ) ) {
			$custom_fields_old = get_post_meta( $stadium_data['ID'], '_anwpfl_custom_fields', true );

			if ( ! empty( $custom_fields_old ) && is_array( $custom_fields_old ) ) {
				$custom_fields_data = array_merge( $custom_fields_old, $custom_fields_data );
			}
		}

		if ( ! empty( $custom_fields_data ) ) {
			$stadium_data['meta_input']['_anwpfl_custom_fields'] = $custom_fields_data;
		}

		$post_id = $insert_mode ? wp_insert_post( $stadium_data ) : wp_update_post( $stadium_data );

		if ( absint( $post_id ) ) {
			$post_obj = get_post( $post_id );

			$import_status['result']     = 'success';
			$import_status['post_title'] = $post_obj->post_title;
			$import_status['post_url']   = get_permalink( $post_obj );
			$import_status['post_edit']  = get_edit_post_link( $post_obj );
		}

		return $import_status;
	}

	/**
	 * Import Staff.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_staff( array $params, array $import_status ): array {

		$current_user_id = get_current_user_id();
		$current_time    = current_time( 'mysql' );

		$row_data    = $params['row_data'];
		$insert_mode = 'insert' === $params['mode'];

		$custom_fields_data = [];

		if ( $insert_mode ) {
			$staff_data = [
				'post_title'   => '',
				'post_content' => '',
				'post_type'    => 'anwp_staff',
				'post_status'  => 'publish',
				'post_author'  => $current_user_id,
				'meta_input'   => [
					'_anwpfl_import_time' => $current_time,
				],
			];

			if ( empty( trim( $row_data['staff_name'] ) ) ) {
				$import_status['post_title'] = 'Empty Name not allowed';

				return $import_status;
			}
		} else {
			$staff_data = [
				'ID'         => '',
				'post_type'  => 'anwp_staff',
				'meta_input' => [
					'_anwpfl_import_time' => $current_time,
				],
			];

			if ( ! empty( $row_data['staff_id'] ) ) {
				if ( 'anwp_staff' === get_post_type( absint( $row_data['staff_id'] ) ) ) {
					$staff_data['ID'] = absint( $row_data['staff_id'] );
				}
			} elseif ( ! empty( $row_data['staff_external_id'] ) ) {
				$maybe_staff_id = anwp_fl()->staff->get_staff_id_by_external_id( $row_data['staff_external_id'] );

				if ( ! empty( $maybe_staff_id ) ) {
					$staff_data['ID'] = absint( $maybe_staff_id );
				}
			}

			if ( empty( $staff_data['ID'] ) ) {
				$import_status['post_title'] = 'Invalid Staff ID or External Staff ID';

				return $import_status;
			}
		}

		foreach ( $row_data as $slug => $value ) {
			if ( empty( $value ) || in_array( $slug, [ 'import_info', 'import_status' ], true ) ) {
				continue;
			}

			switch ( $slug ) {
				case 'staff_name':
					$staff_data['post_title'] = sanitize_text_field( $value );
					break;

				case 'short_name':
					$staff_data['meta_input']['_anwpfl_short_name'] = sanitize_text_field( $value );
					break;

				case 'job_title':
					$staff_data['meta_input']['_anwpfl_job_title'] = sanitize_text_field( $value );
					break;

				case 'current_club':
					$staff_data['meta_input']['_anwpfl_current_club'] = sanitize_text_field( $value );
					break;

				case 'place_of_birth':
					$staff_data['meta_input']['_anwpfl_place_of_birth'] = sanitize_text_field( $value );
					break;

				case 'date_of_birth':
					$staff_data['meta_input']['_anwpfl_date_of_birth'] = sanitize_text_field( $value );
					break;

				case 'date_of_death':
					$staff_data['meta_input']['_anwpfl_date_of_death'] = sanitize_text_field( $value );
					break;

				case 'nationality_1':
					$staff_data['meta_input']['_anwpfl_nationality'][] = sanitize_text_field( $value );
					break;

				case 'bio':
					$staff_data['meta_input']['_anwpfl_description'] = sanitize_text_field( $value );
					break;

				case 'staff_external_id':
					$staff_data['meta_input']['_anwpfl_staff_external_id'] = sanitize_text_field( $value );
					break;

				case 'custom_title_1':
					$staff_data['meta_input']['_anwpfl_custom_title_1'] = sanitize_text_field( $value );
					break;

				case 'custom_title_2':
					$staff_data['meta_input']['_anwpfl_custom_title_2'] = sanitize_text_field( $value );
					break;

				case 'custom_title_3':
					$staff_data['meta_input']['_anwpfl_custom_title_3'] = sanitize_text_field( $value );
					break;

				case 'custom_value_1':
					$staff_data['meta_input']['_anwpfl_custom_value_1'] = sanitize_text_field( $value );
					break;

				case 'custom_value_2':
					$staff_data['meta_input']['_anwpfl_custom_value_2'] = sanitize_text_field( $value );
					break;

				case 'custom_value_3':
					$staff_data['meta_input']['_anwpfl_custom_value_3'] = sanitize_text_field( $value );
					break;

				default:
					if ( 0 === mb_strpos( $slug, 'cf__' ) ) {

						$maybe_custom_field = mb_substr( $slug, 4 );

						if ( ! empty( $maybe_custom_field ) ) {
							$custom_fields_data[ $maybe_custom_field ] = sanitize_text_field( $value );
						}
					}
			}
		}

		// Custom Fields
		if ( ! empty( $custom_fields_data ) ) {
			$custom_fields_old = get_post_meta( $staff_data['ID'], '_anwpfl_custom_fields', true );

			if ( ! empty( $custom_fields_old ) && is_array( $custom_fields_old ) ) {
				$custom_fields_data = array_merge( $custom_fields_old, $custom_fields_data );
			}
		}

		if ( ! empty( $custom_fields_data ) ) {
			$staff_data['meta_input']['_anwpfl_custom_fields'] = $custom_fields_data;
		}

		$post_id = $insert_mode ? wp_insert_post( $staff_data ) : wp_update_post( $staff_data );

		if ( absint( $post_id ) ) {
			$post_obj = get_post( $post_id );

			$import_status['result']     = 'success';
			$import_status['post_title'] = $post_obj->post_title;
			$import_status['post_url']   = get_permalink( $post_obj );
			$import_status['post_edit']  = get_edit_post_link( $post_obj );
		}

		return $import_status;
	}

	/**
	 * Import Referees.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_referees( array $params, array $import_status ): array {

		$current_user_id = get_current_user_id();
		$current_time    = current_time( 'mysql' );

		$row_data    = $params['row_data'];
		$insert_mode = 'insert' === $params['mode'];

		$custom_fields_data = [];

		if ( $insert_mode ) {
			$referee_data = [
				'post_title'   => '',
				'post_content' => '',
				'post_type'    => 'anwp_referee',
				'post_status'  => 'publish',
				'post_author'  => $current_user_id,
				'meta_input'   => [
					'_anwpfl_import_time' => $current_time,
				],
			];

			if ( empty( trim( $row_data['referee_name'] ) ) ) {
				$import_status['post_title'] = 'Empty Name not allowed';

				return $import_status;
			}
		} else {
			$referee_data = [
				'ID'         => '',
				'post_type'  => 'anwp_referee',
				'meta_input' => [
					'_anwpfl_import_time' => $current_time,
				],
			];

			if ( ! empty( $row_data['referee_id'] ) ) {
				if ( 'anwp_referee' === get_post_type( absint( $row_data['referee_id'] ) ) ) {
					$referee_data['ID'] = absint( $row_data['referee_id'] );
				}
			} elseif ( ! empty( $row_data['referee_external_id'] ) ) {
				$maybe_referee_id = anwp_fl()->referee->get_referee_id_by_external_id( $row_data['referee_external_id'] );

				if ( ! empty( $maybe_referee_id ) ) {
					$referee_data['ID'] = absint( $maybe_referee_id );
				}
			}

			if ( empty( $referee_data['ID'] ) ) {
				$import_status['post_title'] = 'Invalid Referee ID or External Referee ID';

				return $import_status;
			}
		}

		foreach ( $row_data as $slug => $value ) {
			if ( empty( $value ) || in_array( $slug, [ 'import_info', 'import_status' ], true ) ) {
				continue;
			}

			switch ( $slug ) {
				case 'referee_name':
					$referee_data['post_title'] = sanitize_text_field( $value );
					break;

				case 'short_name':
					$referee_data['meta_input']['_anwpfl_short_name'] = sanitize_text_field( $value );
					break;

				case 'job_title':
					$referee_data['meta_input']['_anwpfl_job_title'] = sanitize_text_field( $value );
					break;

				case 'place_of_birth':
					$referee_data['meta_input']['_anwpfl_place_of_birth'] = sanitize_text_field( $value );
					break;

				case 'date_of_birth':
					$referee_data['meta_input']['_anwpfl_date_of_birth'] = sanitize_text_field( $value );
					break;

				case 'nationality_1':
					$referee_data['meta_input']['_anwpfl_nationality'][] = sanitize_text_field( $value );
					break;

				case 'referee_external_id':
					$referee_data['meta_input']['_anwpfl_referee_external_id'] = sanitize_text_field( $value );
					break;

				case 'custom_title_1':
					$referee_data['meta_input']['_anwpfl_custom_title_1'] = sanitize_text_field( $value );
					break;

				case 'custom_title_2':
					$referee_data['meta_input']['_anwpfl_custom_title_2'] = sanitize_text_field( $value );
					break;

				case 'custom_title_3':
					$referee_data['meta_input']['_anwpfl_custom_title_3'] = sanitize_text_field( $value );
					break;

				case 'custom_value_1':
					$referee_data['meta_input']['_anwpfl_custom_value_1'] = sanitize_text_field( $value );
					break;

				case 'custom_value_2':
					$referee_data['meta_input']['_anwpfl_custom_value_2'] = sanitize_text_field( $value );
					break;

				case 'custom_value_3':
					$referee_data['meta_input']['_anwpfl_custom_value_3'] = sanitize_text_field( $value );
					break;

				default:
					if ( 0 === mb_strpos( $slug, 'cf__' ) ) {
						$maybe_custom_field = mb_substr( $slug, 4 );

						if ( ! empty( $maybe_custom_field ) ) {
							$custom_fields_data[ $maybe_custom_field ] = sanitize_text_field( $value );
						}
					}
			}
		}

		// Custom Fields
		if ( ! empty( $custom_fields_data ) ) {
			$custom_fields_old = get_post_meta( $referee_data['ID'], '_anwpfl_custom_fields', true );

			if ( ! empty( $custom_fields_old ) && is_array( $custom_fields_old ) ) {
				$custom_fields_data = array_merge( $custom_fields_old, $custom_fields_data );
			}
		}

		if ( ! empty( $custom_fields_data ) ) {
			$referee_data['meta_input']['_anwpfl_custom_fields'] = $custom_fields_data;
		}

		$post_id = $insert_mode ? wp_insert_post( $referee_data ) : wp_update_post( $referee_data );

		if ( absint( $post_id ) ) {
			$post_obj = get_post( $post_id );

			$import_status['result']     = 'success';
			$import_status['post_title'] = $post_obj->post_title;
			$import_status['post_url']   = get_permalink( $post_obj );
			$import_status['post_edit']  = get_edit_post_link( $post_obj );
		}

		return $import_status;
	}

	/**
	 * Import Players.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_players( array $params, array $import_status ): array {

		$current_user_id = get_current_user_id();
		$current_time    = current_time( 'mysql' );

		$row_data           = $params['row_data'];
		$insert_mode        = 'insert' === $params['mode'];
		$custom_fields_data = [];
		$table_player_data  = [];

		if ( $insert_mode ) {
			$player_data = [
				'post_title'   => '',
				'post_content' => '',
				'post_type'    => 'anwp_player',
				'post_status'  => 'publish',
				'post_author'  => $current_user_id,
				'meta_input'   => [
					'_anwpfl_import_time' => $current_time,
				],
			];

			if ( empty( trim( $row_data['player_name'] ) ) ) {
				$import_status['post_title'] = 'Empty Name not allowed';

				return $import_status;
			}
		} else {
			$player_data = [
				'ID'         => '',
				'post_type'  => 'anwp_player',
				'meta_input' => [
					'_anwpfl_import_time' => $current_time,
				],
			];

			if ( ! empty( $row_data['player_id'] ) ) {
				if ( 'anwp_player' === get_post_type( absint( $row_data['player_id'] ) ) ) {
					$player_data['ID'] = absint( $row_data['player_id'] );
				}
			} elseif ( ! empty( $row_data['player_external_id'] ) ) {
				$maybe_player_id = anwp_fl()->player->get_player_id_by_external_id( $row_data['player_external_id'] );

				if ( ! empty( $maybe_player_id ) ) {
					$player_data['ID'] = absint( $maybe_player_id );
				}
			}

			if ( empty( $player_data['ID'] ) ) {
				$import_status['post_title'] = 'Invalid Player ID or External Player ID';

				return $import_status;
			}
		}

		foreach ( $row_data as $slug => $value ) {
			if ( empty( $value ) || in_array( $slug, [ 'import_info', 'import_status' ], true ) ) {
				continue;
			}

			switch ( $slug ) {
				case 'player_name':
					$player_data['post_title'] = sanitize_text_field( $value );
					$table_player_data['name'] = sanitize_text_field( $value );
					break;

				case 'short_name':
				case 'full_name':
				case 'weight':
				case 'height':
				case 'position':
				case 'team_id':
				case 'national_team':
				case 'nationality':
				case 'place_of_birth':
				case 'country_of_birth':
				case 'date_of_birth':
				case 'date_of_death':
				case 'player_external_id':
					$table_player_data[ $slug ] = sanitize_text_field( $value );
					break;

				case 'bio':
					$player_data['meta_input']['_anwpfl_description'] = sanitize_textarea_field( $value );
					break;

				case 'custom_title_1':
					$player_data['meta_input']['_anwpfl_custom_title_1'] = sanitize_text_field( $value );
					break;

				case 'custom_title_2':
					$player_data['meta_input']['_anwpfl_custom_title_2'] = sanitize_text_field( $value );
					break;

				case 'custom_title_3':
					$player_data['meta_input']['_anwpfl_custom_title_3'] = sanitize_text_field( $value );
					break;

				case 'custom_value_1':
					$player_data['meta_input']['_anwpfl_custom_value_1'] = sanitize_text_field( $value );
					break;

				case 'custom_value_2':
					$player_data['meta_input']['_anwpfl_custom_value_2'] = sanitize_text_field( $value );
					break;

				case 'custom_value_3':
					$player_data['meta_input']['_anwpfl_custom_value_3'] = sanitize_text_field( $value );
					break;

				default:
					if ( 0 === mb_strpos( $slug, 'cf__' ) ) {

						$maybe_custom_field = mb_substr( $slug, 4 );

						if ( ! empty( $maybe_custom_field ) ) {
							$custom_fields_data[ $maybe_custom_field ] = sanitize_text_field( $value );
						}
					}
			}
		}

		// Custom Fields
		if ( ! empty( $custom_fields_data ) ) {
			$custom_fields_old = get_post_meta( $player_data['ID'], '_anwpfl_custom_fields', true );

			if ( ! empty( $custom_fields_old ) && is_array( $custom_fields_old ) ) {
				$custom_fields_data = array_merge( $custom_fields_old, $custom_fields_data );
			}
		}

		if ( ! empty( $custom_fields_data ) ) {
			$player_data['meta_input']['_anwpfl_custom_fields'] = $custom_fields_data;
		}

		$post_id = $insert_mode ? wp_insert_post( $player_data ) : wp_update_post( $player_data );

		if ( absint( $post_id ) ) {
			if ( $insert_mode ) {
				$table_player_data['player_id'] = $post_id;
				anwp_fl()->player->insert( $table_player_data );
			} elseif ( ! empty( $table_player_data ) ) {
				anwp_fl()->player->update( $post_id, $table_player_data );
			}

			$post_obj = get_post( $post_id );

			$import_status['result']     = 'success';
			$import_status['post_title'] = $post_obj->post_title;
			$import_status['post_url']   = get_permalink( $post_obj );
			$import_status['post_edit']  = get_edit_post_link( $post_obj );
		}

		return $import_status;
	}

	/**
	 * Import Substitutes.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_subs( array $params, array $import_status ): array {

		$row_data = $params['row_data'];
		$game_id  = '';

		if ( ! empty( $row_data['match_id'] ) ) {
			if ( 'anwp_match' === get_post_type( absint( $row_data['match_id'] ) ) ) {
				$game_id = absint( $row_data['match_id'] );
			}
		} elseif ( ! empty( $row_data['match_external_id'] ) ) {
			$maybe_game_id = anwp_fl()->match->get_match_id_by_external_id( $row_data['match_external_id'] );

			if ( ! empty( $maybe_game_id ) ) {
				$game_id = absint( $maybe_game_id );
			}
		}

		if ( empty( $game_id ) || 'anwp_match' !== get_post_type( absint( $game_id ) ) ) {
			$import_status['post_title'] = 'Invalid Match ID or External Match ID';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Club ID
		|--------------------------------------------------------------------
		*/
		$club_id = '';

		if ( ! empty( $row_data['club_id'] ) ) {
			$club_id = absint( $row_data['club_id'] );
		} elseif ( ! empty( $row_data['club_external_id'] ) ) {
			$club_id = absint( anwp_fl()->club->get_club_id_by_external_id( $row_data['club_external_id'] ) );
		}

		if ( empty( $club_id ) || 'anwp_club' !== get_post_type( absint( $club_id ) ) ) {
			$import_status['post_title'] = 'Invalid Club ID or External Club ID';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Check Player IDs
		|--------------------------------------------------------------------
		*/
		$player_id = '';

		if ( ! empty( $row_data['player_id'] ) ) {
			$player_id = absint( $row_data['player_id'] );
		} elseif ( ! empty( $row_data['player_external_id'] ) ) {
			$player_id = absint( anwp_fl()->player->get_player_id_by_external_id( $row_data['player_external_id'] ) );
		}

		if ( empty( $player_id ) && ! empty( $row_data['player_temp'] ) ) {
			$player_id = 'temp__' . $row_data['player_temp'];
		} elseif ( empty( $player_id ) || 'anwp_player' !== get_post_type( absint( $player_id ) ) ) {
			$player_id = '';
		}

		$player_out_id = '';

		if ( ! empty( $row_data['player_out_id'] ) ) {
			$player_out_id = absint( $row_data['player_out_id'] );
		} elseif ( ! empty( $row_data['player_out_external_id'] ) ) {
			$player_out_id = absint( anwp_fl()->player->get_player_id_by_external_id( $row_data['player_out_external_id'] ) );
		}

		if ( empty( $player_out_id ) && ! empty( $row_data['player_out_temp'] ) ) {
			$player_out_id = 'temp__' . $row_data['player_out_temp'];
		} elseif ( empty( $player_out_id ) || 'anwp_player' !== get_post_type( absint( $player_out_id ) ) ) {
			$player_out_id = '';
		}

		if ( empty( $player_id ) || empty( $player_out_id ) ) {
			$import_status['post_title'] = 'Invalid Player IDs';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Prepare data
		|--------------------------------------------------------------------
		*/
		$minute     = sanitize_text_field( $row_data['minute'] ?? '' );
		$minute_add = sanitize_text_field( $row_data['minute_add'] ?? '' );

		$game_data    = anwp_fl()->match->get_game_data( $game_id );
		$game_events  = json_decode( $game_data['match_events'], true ) ?: [];
		$temp_players = [];
		$is_home_team = absint( $club_id ) === absint( $game_data['home_club'] );

		if ( mb_strpos( $player_id, 'temp__' ) !== false ) {
			if ( empty( $temp_players ) ) {
				$temp_players = json_decode( wp_unslash( get_post_meta( $game_id, '_anwpfl_match_temp_players', true ) ), true ) ?: [];
			}

			$last_temp_id = $temp_players ? mb_substr( end( $temp_players )->id, 6 ) : 1;
			$player_temp  = mb_substr( $temp_players, 6 );
			$player_id    = 'temp__' . ( ++$last_temp_id );

			$temp_players[] = [
				'id'       => $player_id,
				'club_id'  => absint( $club_id ),
				'country'  => '',
				'position' => '',
				'name'     => $player_temp,
				'context'  => $is_home_team ? 'home' : 'away',
			];
		}

		if ( mb_strpos( $player_out_id, 'temp__' ) !== false ) {
			if ( empty( $temp_players ) ) {
				$temp_players = json_decode( wp_unslash( get_post_meta( $game_id, '_anwpfl_match_temp_players', true ) ), true ) ?: [];
			}

			$last_temp_id  = $temp_players ? mb_substr( end( $temp_players )->id, 6 ) : 1;
			$player_temp   = mb_substr( $temp_players, 6 );
			$player_out_id = 'temp__' . ( ++$last_temp_id );

			$temp_players[] = [
				'id'       => $player_out_id,
				'club_id'  => absint( $club_id ),
				'country'  => '',
				'position' => '',
				'name'     => $player_temp,
				'context'  => $is_home_team ? 'home' : 'away',
			];
		}

		/*
		|--------------------------------------------------------------------
		| Save data
		|--------------------------------------------------------------------
		*/
		$game_events[] = [
			'type'        => 'substitute',
			'club'        => $club_id,
			'minute'      => $minute,
			'minuteAdd'   => $minute_add,
			'player'      => $player_id,
			'assistant'   => '',
			'playerOut'   => $player_out_id,
			'card'        => '',
			'ownGoal'     => '',
			'fromPenalty' => '',
			'id'          => (int) round( microtime( true ) * 1000 ),
			'comment'     => '',
			'sorting'     => '',
		];

		if ( ! empty( $temp_players ) ) {
			update_post_meta( $game_id, '_anwpfl_match_temp_players', wp_slash( wp_json_encode( $temp_players ) ) );
		}

		$game_events = wp_list_sort(
			$game_events,
			[
				'minute'    => 'ASC',
				'minuteAdd' => 'ASC',
				'sorting'   => 'ASC',
			]
		);

		anwp_fl()->match->update( $game_id, [ 'match_events' => wp_json_encode( $game_events ) ] );

		/*
		|--------------------------------------------------------------------
		| Update player stats
		|--------------------------------------------------------------------
		*/
		anwp_fl()->match->save_player_statistics( $game_data, $game_events );

		$post_obj = get_post( $game_id );

		$import_status['result']     = 'success';
		$import_status['post_title'] = $post_obj->post_title;
		$import_status['post_url']   = get_permalink( $post_obj );
		$import_status['post_edit']  = get_edit_post_link( $post_obj );

		return $import_status;
	}

	/**
	 * Import Goals.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_cards( array $params, array $import_status ): array {

		$row_data = $params['row_data'];
		$game_id  = '';

		if ( ! empty( $row_data['match_id'] ) ) {
			if ( 'anwp_match' === get_post_type( absint( $row_data['match_id'] ) ) ) {
				$game_id = absint( $row_data['match_id'] );
			}
		} elseif ( ! empty( $row_data['match_external_id'] ) ) {
			$maybe_game_id = anwp_fl()->match->get_match_id_by_external_id( $row_data['match_external_id'] );

			if ( ! empty( $maybe_game_id ) ) {
				$game_id = absint( $maybe_game_id );
			}
		}

		if ( empty( $game_id ) || 'anwp_match' !== get_post_type( absint( $game_id ) ) ) {
			$import_status['post_title'] = 'Invalid Match ID or External Match ID';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Club ID
		|--------------------------------------------------------------------
		*/
		$club_id = '';

		if ( ! empty( $row_data['club_id'] ) ) {
			$club_id = absint( $row_data['club_id'] );
		} elseif ( ! empty( $row_data['club_external_id'] ) ) {
			$club_id = absint( anwp_fl()->club->get_club_id_by_external_id( $row_data['club_external_id'] ) );
		}

		if ( empty( $club_id ) || 'anwp_club' !== get_post_type( absint( $club_id ) ) ) {
			$import_status['post_title'] = 'Invalid Club ID or External Club ID';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Check Player IDs
		|--------------------------------------------------------------------
		*/
		$player_id = '';

		if ( ! empty( $row_data['player_id'] ) ) {
			$player_id = absint( $row_data['player_id'] );
		} elseif ( ! empty( $row_data['player_external_id'] ) ) {
			$player_id = absint( anwp_fl()->player->get_player_id_by_external_id( $row_data['player_external_id'] ) );
		}

		if ( empty( $player_id ) && ! empty( $row_data['player_temp'] ) ) {
			$player_id = 'temp__' . $row_data['player_temp'];
		} elseif ( empty( $player_id ) || 'anwp_player' !== get_post_type( absint( $player_id ) ) ) {
			$player_id = '';
		}

		if ( empty( $player_id ) ) {
			$import_status['post_title'] = 'Invalid Player ID';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Prepare data
		|--------------------------------------------------------------------
		*/
		$minute     = sanitize_text_field( $row_data['minute'] ?? '' );
		$minute_add = sanitize_text_field( $row_data['minute_add'] ?? '' );
		$card_type  = sanitize_text_field( $row_data['cart_type'] ?? '' );

		if ( ! in_array( $card_type, [ 'y', 'r', 'yr' ], true ) ) {
			$import_status['post_title'] = 'Invalid Card Type';

			return $import_status;
		}

		$game_data    = anwp_fl()->match->get_game_data( $game_id );
		$game_events  = json_decode( $game_data['match_events'], true ) ?: [];
		$temp_players = [];
		$is_home_team = absint( $club_id ) === absint( $game_data['home_club'] );

		if ( mb_strpos( $player_id, 'temp__' ) !== false ) {
			if ( empty( $temp_players ) ) {
				$temp_players = json_decode( wp_unslash( get_post_meta( $game_id, '_anwpfl_match_temp_players', true ) ), true ) ?: [];
			}

			$last_temp_id = $temp_players ? mb_substr( end( $temp_players )->id, 6 ) : 1;
			$player_temp  = mb_substr( $temp_players, 6 );
			$player_id    = 'temp__' . ( ++ $last_temp_id );

			$temp_players[] = [
				'id'       => $player_id,
				'club_id'  => absint( $club_id ),
				'country'  => '',
				'position' => '',
				'name'     => $player_temp,
				'context'  => $is_home_team ? 'home' : 'away',
			];
		}

		/*
		|--------------------------------------------------------------------
		| Save data
		|--------------------------------------------------------------------
		*/
		$game_events[] = [
			'type'        => 'card',
			'club'        => $club_id,
			'minute'      => $minute,
			'minuteAdd'   => $minute_add,
			'player'      => $player_id,
			'assistant'   => '',
			'playerOut'   => '',
			'card'        => $card_type,
			'ownGoal'     => '',
			'fromPenalty' => '',
			'id'          => (int) round( microtime( true ) * 1000 ),
			'comment'     => '',
			'sorting'     => '',
		];

		if ( ! empty( $temp_players ) ) {
			update_post_meta( $game_id, '_anwpfl_match_temp_players', wp_slash( wp_json_encode( $temp_players ) ) );
		}

		$game_events = wp_list_sort(
			$game_events,
			[
				'minute'    => 'ASC',
				'minuteAdd' => 'ASC',
				'sorting'   => 'ASC',
			]
		);

		anwp_fl()->match->update( $game_id, [ 'match_events' => wp_json_encode( $game_events ) ] );

		/*
		|--------------------------------------------------------------------
		| Update player stats
		|--------------------------------------------------------------------
		*/
		anwp_fl()->match->save_player_statistics( $game_data, $game_events );

		$post_obj = get_post( $game_id );

		$import_status['result']     = 'success';
		$import_status['post_title'] = $post_obj->post_title;
		$import_status['post_url']   = get_permalink( $post_obj );
		$import_status['post_edit']  = get_edit_post_link( $post_obj );

		return $import_status;
	}

	/**
	 * Import Goals.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_lineups( array $params, array $import_status ): array {
		global $wpdb;

		$row_data = $params['row_data'];
		$game_id  = '';

		if ( ! empty( $row_data['match_id'] ) ) {
			if ( 'anwp_match' === get_post_type( absint( $row_data['match_id'] ) ) ) {
				$game_id = absint( $row_data['match_id'] );
			}
		} elseif ( ! empty( $row_data['match_external_id'] ) ) {
			$maybe_game_id = anwp_fl()->match->get_match_id_by_external_id( $row_data['match_external_id'] );

			if ( ! empty( $maybe_game_id ) ) {
				$game_id = absint( $maybe_game_id );
			}
		}

		if ( empty( $game_id ) || 'anwp_match' !== get_post_type( absint( $game_id ) ) ) {
			$import_status['post_title'] = 'Invalid Match ID or External Match ID';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Club ID
		|--------------------------------------------------------------------
		*/
		$club_id = '';

		if ( ! empty( $row_data['club_id'] ) ) {
			$club_id = absint( $row_data['club_id'] );
		} elseif ( ! empty( $row_data['club_external_id'] ) ) {
			$club_id = absint( anwp_fl()->club->get_club_id_by_external_id( $row_data['club_external_id'] ) );
		}

		if ( empty( $club_id ) || 'anwp_club' !== get_post_type( absint( $club_id ) ) ) {
			$import_status['post_title'] = 'Invalid Club ID or External Club ID';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Check Player ID
		|--------------------------------------------------------------------
		*/
		$player_id = '';

		if ( ! empty( $row_data['player_id'] ) ) {
			$player_id = absint( $row_data['player_id'] );
		} elseif ( ! empty( $row_data['player_external_id'] ) ) {
			$player_id = absint( anwp_fl()->player->get_player_id_by_external_id( $row_data['player_external_id'] ) );
		}

		if ( empty( $player_id ) && ! empty( $row_data['player_temp'] ) ) {
			$player_id = 'temp__' . $row_data['player_temp'];
		} elseif ( empty( $player_id ) || 'anwp_player' !== get_post_type( absint( $player_id ) ) ) {
			$import_status['post_title'] = 'Invalid Player Data';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Prepare data
		|--------------------------------------------------------------------
		*/
		$starting      = AnWP_Football_Leagues::string_to_bool( $row_data['starting'] ?? '' );
		$is_captain    = AnWP_Football_Leagues::string_to_bool( $row_data['is_captain'] ?? '' );
		$player_number = sanitize_text_field( $row_data['number'] ?? '' );

		$lineups_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->anwpfl_lineups WHERE `match_id` = %d",
				$game_id
			),
			ARRAY_A
		) ? : [];

		$lineups_data = wp_parse_args(
			$lineups_data,
			[
				'home_line_up'   => '',
				'away_line_up'   => '',
				'home_subs'      => '',
				'away_subs'      => '',
				'custom_numbers' => '',
				'captain_home'   => '',
				'captain_away'   => '',
			]
		);

		$lineups_data['custom_numbers'] = json_decode( $lineups_data['custom_numbers'], true ) ?: [];

		$game_data    = anwp_fl()->match->get_game_data( $game_id );
		$temp_players = [];
		$is_home_team = absint( $club_id ) === absint( $game_data['home_club'] );

		if ( mb_strpos( $player_id, 'temp__' ) !== false ) {
			if ( empty( $temp_players ) ) {
				$temp_players = json_decode( wp_unslash( get_post_meta( $game_id, '_anwpfl_match_temp_players', true ) ), true ) ?: [];
			}

			$last_temp_id = $temp_players ? mb_substr( end( $temp_players )->id, 6 ) : 1;
			$player_temp  = mb_substr( $temp_players, 6 );
			$player_id    = 'temp__' . ( ++ $last_temp_id );

			$temp_players[] = [
				'id'       => $player_id,
				'club_id'  => absint( $club_id ),
				'country'  => '',
				'position' => '',
				'name'     => $player_temp,
				'context'  => $is_home_team ? 'home' : 'away',
			];
		}

		if ( $is_captain ) {
			if ( $is_home_team ) {
				$lineups_data['captain_home'] = $player_id;
			} else {
				$lineups_data['captain_away'] = $player_id;
			}
		}

		if ( $starting ) {
			if ( $is_home_team ) {
				$lineups_data['home_line_up'] .= ( $lineups_data['home_line_up'] ? ',' : '' ) . $player_id;
			} else {
				$lineups_data['away_line_up'] .= ( $lineups_data['away_line_up'] ? ',' : '' ) . $player_id;
			}
		} else {
			if ( $is_home_team ) {
				$lineups_data['home_subs'] .= ( $lineups_data['home_subs'] ? ',' : '' ) . $player_id;
			} else {
				$lineups_data['away_subs'] .= ( $lineups_data['away_subs'] ? ',' : '' ) . $player_id;
			}
		}

		if ( '' !== $player_number ) {
			$lineups_data['custom_numbers'][ $player_id ] = $player_number;
		}

		/*
		|--------------------------------------------------------------------
		| Save data
		|--------------------------------------------------------------------
		*/
		if ( ! empty( $temp_players ) ) {
			update_post_meta( $game_id, '_anwpfl_match_temp_players', wp_slash( wp_json_encode( $temp_players ) ) );
		}

		$wpdb->replace(
			$wpdb->anwpfl_lineups,
			[
				'match_id'       => $game_id,
				'home_line_up'   => $lineups_data['home_line_up'],
				'away_line_up'   => $lineups_data['away_line_up'],
				'home_subs'      => $lineups_data['home_subs'],
				'away_subs'      => $lineups_data['away_subs'],
				'custom_numbers' => wp_json_encode( $lineups_data['custom_numbers'] ),
				'captain_home'   => $lineups_data['captain_home'],
				'captain_away'   => $lineups_data['captain_away'],
			]
		);

		/*
		|--------------------------------------------------------------------
		| Update player stats
		|--------------------------------------------------------------------
		*/
		$game_data_updated = anwp_fl()->match->get_game_data( $game_id );
		$game_events       = json_decode( $game_data_updated['match_events'], true ) ?: [];

		anwp_fl()->match->save_player_statistics( $game_data_updated, $game_events );

		$post_obj = get_post( $game_id );

		$import_status['result']     = 'success';
		$import_status['post_title'] = $post_obj->post_title;
		$import_status['post_url']   = get_permalink( $post_obj );
		$import_status['post_edit']  = get_edit_post_link( $post_obj );

		return $import_status;
	}

	/**
	 * Import Goals.
	 *
	 * @param array $params
	 * @param array $import_status
	 *
	 * @return array
	 */
	protected function import_goals( array $params, array $import_status ): array {

		$row_data = $params['row_data'];
		$game_id  = '';

		if ( ! empty( $row_data['match_id'] ) ) {
			if ( 'anwp_match' === get_post_type( absint( $row_data['match_id'] ) ) ) {
				$game_id = absint( $row_data['match_id'] );
			}
		} elseif ( ! empty( $row_data['match_external_id'] ) ) {
			$maybe_game_id = anwp_fl()->match->get_match_id_by_external_id( $row_data['match_external_id'] );

			if ( ! empty( $maybe_game_id ) ) {
				$game_id = absint( $maybe_game_id );
			}
		}

		if ( empty( $game_id ) || 'anwp_match' !== get_post_type( absint( $game_id ) ) ) {
			$import_status['post_title'] = 'Invalid Match ID or External Match ID';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Club ID
		|--------------------------------------------------------------------
		*/
		$club_id = '';

		if ( ! empty( $row_data['club_id'] ) ) {
			$club_id = absint( $row_data['club_id'] );
		} elseif ( ! empty( $row_data['club_external_id'] ) ) {
			$club_id = absint( anwp_fl()->club->get_club_id_by_external_id( $row_data['club_external_id'] ) );
		}

		if ( empty( $club_id ) || 'anwp_club' !== get_post_type( absint( $club_id ) ) ) {
			$import_status['post_title'] = 'Invalid Club ID or External Club ID';

			return $import_status;
		}

		/*
		|--------------------------------------------------------------------
		| Check Player & Assistant ID
		|--------------------------------------------------------------------
		*/
		$player_id = '';

		if ( ! empty( $row_data['player_id'] ) ) {
			$player_id = absint( $row_data['player_id'] );
		} elseif ( ! empty( $row_data['player_external_id'] ) ) {
			$player_id = absint( anwp_fl()->player->get_player_id_by_external_id( $row_data['player_external_id'] ) );
		}

		if ( empty( $player_id ) && ! empty( $row_data['player_temp'] ) ) {
			$player_id = 'temp__' . $row_data['player_temp'];
		} elseif ( empty( $player_id ) || 'anwp_player' !== get_post_type( absint( $player_id ) ) ) {
			$player_id = '';
		}

		$assistant_id = '';

		if ( ! empty( $row_data['assistant_id'] ) ) {
			$assistant_id = absint( $row_data['assistant_id'] );
		} elseif ( ! empty( $row_data['assistant_external_id'] ) ) {
			$assistant_id = absint( anwp_fl()->player->get_player_id_by_external_id( $row_data['assistant_external_id'] ) );
		}

		if ( empty( $assistant_id ) && ! empty( $row_data['assistant_temp'] ) ) {
			$assistant_id = 'temp__' . $row_data['assistant_temp'];
		} elseif ( empty( $assistant_id ) || 'anwp_player' !== get_post_type( absint( $assistant_id ) ) ) {
			$assistant_id = '';
		}

		/*
		|--------------------------------------------------------------------
		| Prepare data
		|--------------------------------------------------------------------
		*/
		$own_goal     = AnWP_Football_Leagues::string_to_bool( $row_data['own_goal'] ?? '' );
		$from_penalty = AnWP_Football_Leagues::string_to_bool( $row_data['from_penalty'] ?? '' );
		$minute       = sanitize_text_field( $row_data['minute'] ?? '' );
		$minute_add   = sanitize_text_field( $row_data['minute_add'] ?? '' );

		$game_data    = anwp_fl()->match->get_game_data( $game_id );
		$game_events  = json_decode( $game_data['match_events'], true ) ?: [];
		$temp_players = [];
		$is_home_team = absint( $club_id ) === absint( $game_data['home_club'] );

		if ( mb_strpos( $player_id, 'temp__' ) !== false ) {
			if ( empty( $temp_players ) ) {
				$temp_players = json_decode( wp_unslash( get_post_meta( $game_id, '_anwpfl_match_temp_players', true ) ), true ) ?: [];
			}

			$last_temp_id = $temp_players ? mb_substr( end( $temp_players )->id, 6 ) : 1;
			$player_temp  = mb_substr( $temp_players, 6 );
			$player_id    = 'temp__' . ( ++ $last_temp_id );

			$temp_players[] = [
				'id'       => $player_id,
				'club_id'  => absint( $club_id ),
				'country'  => '',
				'position' => '',
				'name'     => $player_temp,
				'context'  => $is_home_team ? 'home' : 'away',
			];
		}

		if ( mb_strpos( $assistant_id, 'temp__' ) !== false ) {
			if ( empty( $temp_players ) ) {
				$temp_players = json_decode( wp_unslash( get_post_meta( $game_id, '_anwpfl_match_temp_players', true ) ), true ) ?: [];
			}

			$last_temp_id = $temp_players ? mb_substr( end( $temp_players )->id, 6 ) : 1;
			$player_temp  = mb_substr( $temp_players, 6 );
			$assistant_id = 'temp__' . ( ++ $last_temp_id );

			$temp_players[] = [
				'id'       => $assistant_id,
				'club_id'  => absint( $club_id ),
				'country'  => '',
				'position' => '',
				'name'     => $player_temp,
				'context'  => $is_home_team ? 'home' : 'away',
			];
		}

		/*
		|--------------------------------------------------------------------
		| Save data
		|--------------------------------------------------------------------
		*/
		$game_events[] = [
			'type'        => 'goal',
			'club'        => $club_id,
			'minute'      => $minute,
			'minuteAdd'   => $minute_add,
			'player'      => $player_id,
			'assistant'   => $assistant_id,
			'playerOut'   => '',
			'card'        => '',
			'ownGoal'     => $own_goal ? 'yes' : '',
			'fromPenalty' => $from_penalty ? 'yes' : '',
			'id'          => (int) round( microtime( true ) * 1000 ),
			'comment'     => '',
			'sorting'     => '',
		];

		if ( ! empty( $temp_players ) ) {
			update_post_meta( $game_id, '_anwpfl_match_temp_players', wp_slash( wp_json_encode( $temp_players ) ) );
		}

		$game_events = wp_list_sort(
			$game_events,
			[
				'minute'    => 'ASC',
				'minuteAdd' => 'ASC',
				'sorting'   => 'ASC',
			]
		);

		anwp_fl()->match->update( $game_id, [ 'match_events' => wp_json_encode( $game_events ) ] );

		/*
		|--------------------------------------------------------------------
		| Update player stats
		|--------------------------------------------------------------------
		*/
		anwp_fl()->match->save_player_statistics( $game_data, $game_events );

		$post_obj = get_post( $game_id );

		$import_status['result']     = 'success';
		$import_status['post_title'] = $post_obj->post_title;
		$import_status['post_url']   = get_permalink( $post_obj );
		$import_status['post_edit']  = get_edit_post_link( $post_obj );

		return $import_status;
	}
}
