<?php
/**
 * AnWP Football Leagues Upgrade.
 *
 * @since   0.7.0
 * @package AnWP_Football_Leagues
 */


/**
 * AnWP Football Leagues Upgrade class.
 */
class AnWPFL_Upgrade {

	/**
	 * Parent plugin class.
	 *
	 * @var    AnWP_Football_Leagues
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

		$this->version_upgrade();
	}

	/**
	 * Maybe run version upgrade
	 */
	public function version_upgrade() {
		$version_saved   = get_option( 'anwpfl_version', '0.1.0' );
		$version_current = AnWP_Football_Leagues::VERSION;

		if ( $version_saved === $version_current ) {
			return;
		}

		if ( version_compare( $version_saved, '0.7.3', '<' ) ) {
			$this->finish_upgrade();
		}

		if ( version_compare( $version_saved, '0.13.8', '<' ) ) {
			$this->upgrade_0_14();
			anwp_fl()->cache->flush_all_cache();
		}

		if ( version_compare( $version_saved, '0.14.14', '<' ) ) {
			delete_transient( 'FL-COMPETITIONS-LIST' );
		}

		/*
		|--------------------------------------------------------------------
		| Introduce Data Schema in v0.16
		|--------------------------------------------------------------------
		*/
		if ( version_compare( $version_saved, '0.16.0', '<' ) ) {
			update_option( 'anwpfl_data_schema', empty( $this->get_toolbox_updater_tasks( 'tasks' ) ) ? 16 : 15, true );
		}

		update_option( 'anwpfl_version', $version_current, true );
	}

	/**
	 * Initiate our hooks.
	 */
	public function hooks() {
		add_action( 'init', [ $this, 'update_db_check' ], 1 );
		add_action( 'rest_api_init', [ $this, 'add_rest_routes' ] );
	}

	/**
	 * Register REST routes.
	 */
	public function add_rest_routes() {

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/get_toolbox_updater_tasks/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_toolbox_updater_tasks' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/move_player_meta__anwpfl_player_data/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_move_player_meta_anwpfl_player_data' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/migrate_games_from_meta/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_migrate_games_from_meta' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/migrate_lineups_from_meta/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_migrate_lineups_from_meta' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl',
			'/api-toolbox-updater-hide/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'hide_migrate_notice' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/**
	 * Finishing Upgrade
	 */
	public function finish_upgrade() {

		add_action( 'shutdown', 'flush_rewrite_rules' );
	}

	/**
	 * v0.14.0
	 */
	public function upgrade_0_14() {

		$customizer_settings = [];

		if ( 'yes' === AnWPFL_Options::get_value( 'load_alternative_page_layout' ) ) {
			$customizer_settings['general'] = [];

			$customizer_settings['general']['load_alternative_page_layout'] = 'yes';
		}

		if ( 'no' === AnWPFL_Options::get_value( 'hide_post_titles' ) ) {
			if ( ! isset( $customizer_settings['general'] ) ) {
				$customizer_settings['general'] = [];
			}

			$customizer_settings['general']['hide_post_titles'] = 'no';
		}

		if ( 'no' === AnWPFL_Options::get_value( 'show_default_club_logo' ) ) {
			if ( ! isset( $customizer_settings['club'] ) ) {
				$customizer_settings['club'] = [];
			}

			$customizer_settings['club']['show_default_club_logo'] = 'no';
		}

		if ( AnWPFL_Options::get_value( 'default_club_logo' ) ) {
			if ( ! isset( $customizer_settings['club'] ) ) {
				$customizer_settings['club'] = [];
			}

			$customizer_settings['club']['default_club_logo'] = AnWPFL_Options::get_value( 'default_club_logo' );
		}

		if ( AnWPFL_Options::get_value( 'club_squad_layout' ) ) {
			if ( ! isset( $customizer_settings['squad'] ) ) {
				$customizer_settings['squad'] = [];
			}

			$customizer_settings['squad']['club_squad_layout'] = AnWPFL_Options::get_value( 'club_squad_layout' );
		}

		if ( 'yes' === AnWPFL_Options::get_value( 'standing_font_mono' ) ) {
			if ( ! isset( $customizer_settings['standing'] ) ) {
				$customizer_settings['standing'] = [];
			}

			$customizer_settings['standing']['standing_font_mono'] = 'yes';
		}

		if ( 'no' === AnWPFL_Options::get_value( 'use_abbr_in_standing_mini' ) ) {
			if ( ! isset( $customizer_settings['standing'] ) ) {
				$customizer_settings['standing'] = [];
			}

			$customizer_settings['standing']['use_abbr_in_standing_mini'] = 'no';
		}

		if ( 'hide' === AnWPFL_Options::get_value( 'fixture_flip_countdown' ) ) {
			if ( ! isset( $customizer_settings['match'] ) ) {
				$customizer_settings['match'] = [];
			}

			$customizer_settings['match']['fixture_flip_countdown'] = 'hide';
		}

		if ( 'yes' === AnWPFL_Options::get_value( 'match_slim_stadium_show' ) || ( ! empty( AnWPFL_Options::get_value( 'match_slim_bottom_line' ) ) && in_array( 'stadium', AnWPFL_Options::get_value( 'match_slim_bottom_line' ), true ) ) ) {
			if ( ! isset( $customizer_settings['match_list'] ) ) {
				$customizer_settings['match_list'] = [];
			}

			if ( ! isset( $customizer_settings['match_list']['match_slim_bottom_line'] ) ) {
				$customizer_settings['match_list']['match_slim_bottom_line'] = [];
			}

			$customizer_settings['match_list']['match_slim_bottom_line']['stadium'] = true;
		}

		if ( ! empty( AnWPFL_Options::get_value( 'match_slim_bottom_line' ) ) ) {
			if ( ! isset( $customizer_settings['match_list'] ) ) {
				$customizer_settings['match_list'] = [];
			}

			if ( ! isset( $customizer_settings['match_list']['match_slim_bottom_line'] ) ) {
				$customizer_settings['match_list']['match_slim_bottom_line'] = [];
			}

			if ( in_array( 'referee', AnWPFL_Options::get_value( 'match_slim_bottom_line' ), true ) ) {
				$customizer_settings['match_list']['match_slim_bottom_line']['referee'] = true;
			}

			if ( in_array( 'referee_fourth', AnWPFL_Options::get_value( 'match_slim_bottom_line' ), true ) ) {
				$customizer_settings['match_list']['match_slim_bottom_line']['referee_fourth'] = true;
			}

			if ( in_array( 'referee_assistants', AnWPFL_Options::get_value( 'match_slim_bottom_line' ), true ) ) {
				$customizer_settings['match_list']['match_slim_bottom_line']['referee_assistants'] = true;
			}
		}

		if ( 'desc' === AnWPFL_Options::get_value( 'competition_matchweeks_order' ) ) {
			if ( ! isset( $customizer_settings['competition'] ) ) {
				$customizer_settings['competition'] = [];
			}

			$customizer_settings['competition']['competition_matchweeks_order'] = 'desc';
		}

		if ( 'desc' === AnWPFL_Options::get_value( 'competition_rounds_order' ) ) {
			if ( ! isset( $customizer_settings['competition'] ) ) {
				$customizer_settings['competition'] = [];
			}

			$customizer_settings['competition']['competition_rounds_order'] = 'desc';
		}

		if ( AnWPFL_Options::get_value( 'default_player_photo' ) ) {
			if ( ! isset( $customizer_settings['player'] ) ) {
				$customizer_settings['player'] = [];
			}

			$customizer_settings['player']['default_player_photo'] = AnWPFL_Options::get_value( 'default_player_photo' );
		}

		if ( 'hide' === AnWPFL_Options::get_value( 'player_render_main_photo_caption' ) ) {
			if ( ! isset( $customizer_settings['player'] ) ) {
				$customizer_settings['player'] = [];
			}

			$customizer_settings['player']['player_render_main_photo_caption'] = 'hide';
		}

		if ( 'full' === AnWPFL_Options::get_value( 'player_opposite_club_name' ) ) {
			if ( ! isset( $customizer_settings['player'] ) ) {
				$customizer_settings['player'] = [];
			}

			$customizer_settings['player']['player_opposite_club_name'] = 'full';
		}

		if ( ! empty( $customizer_settings ) ) {
			update_option( 'anwp-fl-customizer', $customizer_settings );
		}
	}

	/**
	 * Check Plugin's DB version.
	 *
	 * @since 0.3.0 (2018-01-30)
	 */
	public function update_db_check() {
		if ( (int) get_option( 'anwpfl_db_version' ) < AnWP_Football_Leagues::DB_VERSION ) {
			$this->update_db();
		}
	}

	/**
	 * Update plugin DB
	 *
	 * @since 0.3.0 (2018-01-30)
	 */
	public function update_db(): bool {

		global $wpdb;

		$charset_collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$charset_collate = $wpdb->get_charset_collate();
		}

		/*
		Game Status (game_status):
			0 - friendly
			1 - official
			2 - friendly (OLD)
		*/

		$sql = "
CREATE TABLE {$wpdb->prefix}anwpfl_matches (
  match_id bigint(20) UNSIGNED NOT NULL,
  competition_id bigint(20) UNSIGNED NOT NULL,
  main_stage_id bigint(20) UNSIGNED NOT NULL,
  group_id bigint(20) UNSIGNED NOT NULL,
  season_id bigint(20) UNSIGNED NOT NULL,
  league_id bigint(20) UNSIGNED NOT NULL,
  home_club bigint(20) UNSIGNED NOT NULL,
  away_club bigint(20) UNSIGNED NOT NULL,
  kickoff datetime NOT NULL default '0000-00-00 00:00:00',
  kickoff_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  finished tinyint(1) NOT NULL DEFAULT '0',
  extra tinyint(1) NOT NULL DEFAULT '0',
  attendance int(10) NOT NULL DEFAULT '0',
  aggtext varchar(250) NOT NULL DEFAULT '',
  stadium_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  match_week tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  priority tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  home_goals tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  away_goals tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  home_goals_half tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_goals_half tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_goals_ft tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_goals_ft tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_goals_e tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_goals_e tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_goals_p tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_goals_p tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_cards_y tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_cards_y tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_cards_yr tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_cards_yr tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_cards_r tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_cards_r tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_corners tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_corners tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_fouls tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_fouls tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_offsides tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_offsides tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_possession tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_possession tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_shots tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_shots tinyint(3) UNSIGNED NULL DEFAULT NULL,
  home_shots_on_goal tinyint(3) UNSIGNED NULL DEFAULT NULL,
  away_shots_on_goal tinyint(3) UNSIGNED NULL DEFAULT NULL,
  special_status varchar(20) NOT NULL DEFAULT '',
  game_status tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  coach_home varchar(100) NOT NULL DEFAULT '',
  coach_away varchar(100) NOT NULL DEFAULT '',
  referee varchar(100) NOT NULL DEFAULT '',
  match_events longtext NOT NULL,
  stats_home_club text NOT NULL,
  stats_away_club text NOT NULL,
  extra_info longtext NOT NULL,
  PRIMARY KEY  (match_id),
  KEY competition_id (competition_id),
  KEY main_stage_id (main_stage_id),
  KEY finished (finished),
  KEY game_status (game_status),
  KEY home_club (home_club),
  KEY away_club (away_club),
  KEY kickoff (kickoff),
  KEY stadium_id (stadium_id),
  KEY coach_home (coach_home),
  KEY coach_away (coach_away),
  KEY referee (referee),
  KEY group_id (group_id)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_players (
  match_id bigint(20) UNSIGNED NOT NULL,
  player_id bigint(20) UNSIGNED NOT NULL,
  club_id bigint(20) UNSIGNED NOT NULL,
  time_in tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  time_out tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  appearance tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  goals tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  goals_penalty tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  goals_own tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  goals_conceded tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  assist tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  card_y tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  card_yr tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  card_r tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (match_id,player_id),
  KEY player_id (player_id),
  KEY match_id (match_id),
  KEY club_id (club_id)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_player_data (
  player_id bigint(20) UNSIGNED NOT NULL,
  name varchar(250) NOT NULL DEFAULT '',
  short_name varchar(250) NOT NULL DEFAULT '',
  full_name varchar(250) NOT NULL DEFAULT '',
  weight varchar(50) NOT NULL DEFAULT '',
  height varchar(50) NOT NULL DEFAULT '',
  position varchar(10) NOT NULL DEFAULT '',
  team_id bigint(20) UNSIGNED NOT NULL,
  national_team bigint(20) UNSIGNED NOT NULL,
  nationality varchar(50) NOT NULL DEFAULT '',
  nationality_extra varchar(50) NOT NULL DEFAULT '',
  place_of_birth varchar(100) NOT NULL DEFAULT '',
  country_of_birth varchar(100) NOT NULL DEFAULT '',
  date_of_birth date NOT NULL default '0000-00-00',
  date_of_death date NOT NULL default '0000-00-00',
  player_external_id varchar(50) NOT NULL DEFAULT '',
  photo varchar(250) NOT NULL DEFAULT '',
  photo_sm varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (player_id),
  KEY position (position),
  KEY team_id (team_id),
  KEY national_team (national_team),
  KEY nationality (nationality)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_missing_players (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  match_id bigint(20) UNSIGNED NOT NULL,
  player_id bigint(20) UNSIGNED NOT NULL,
  club_id bigint(20) UNSIGNED NOT NULL,
  reason varchar(20) NOT NULL DEFAULT '',
  comment text NOT NULL,
  PRIMARY KEY  (id),
  KEY match_id (match_id),
  KEY player_id (player_id),
  KEY club_id (club_id)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_players_manual_stats (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  player_id bigint(20) UNSIGNED NOT NULL,
  season_id bigint(20) UNSIGNED NOT NULL,
  competition_id bigint(20) UNSIGNED NOT NULL,
  competition_text varchar(200) NOT NULL DEFAULT '',
  competition_type varchar(10) NOT NULL DEFAULT '',
  played smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  started smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  sub_in smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  minutes smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  card_y smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  card_yr smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  card_r smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  goals smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  goals_penalty smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  assists smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  own_goals smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  goals_conceded smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  clean_sheets smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (id),
  KEY player_id (player_id),
  KEY season_id (season_id)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_lineups (
  match_id bigint(20) UNSIGNED NOT NULL,
  home_line_up varchar(1500) DEFAULT '' NOT NULL,
  away_line_up varchar(1500) DEFAULT '' NOT NULL,
  home_subs varchar(1000) DEFAULT '' NOT NULL,
  away_subs varchar(1000) DEFAULT '' NOT NULL,
  custom_numbers text NOT NULL,
  captain_home varchar(100) NOT NULL DEFAULT '',
  captain_away varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY  (match_id)
) $charset_collate;
";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		$success = empty( $wpdb->last_error );

		$saved_db_version = absint( get_option( 'anwpfl_db_version' ) );

		if ( $saved_db_version < AnWP_Football_Leagues::DB_VERSION && $success ) {
			update_option( 'anwpfl_db_version', AnWP_Football_Leagues::DB_VERSION, true );
		}

		/*
		|--------------------------------------------------------------------
		| v0.16.0
		|--------------------------------------------------------------------
		*/
		// Check if game migration is needed
			/*
			Remove meta
			_anwpfl_match_stats
			_anwpfl_aggtext
			_anwpfl_competition
			_anwpfl_competition_group
			_anwpfl_league
			_anwpfl_season
			_anwpfl_club_home
			_anwpfl_club_away
			_anwpfl_stadium
			_anwpfl_attendance
			_anwpfl_matchweek
			_anwpfl_match_goals_away
			_anwpfl_match_goals_home
			_anwpfl_match_datetime
			_anwpfl_priority
			_anwpfl_special_status
			_anwpfl_match_id
			_anwpfl_fixed
			_anwpfl_extra_time
			_anwpfl_status
			*/

			/*
			Migrate meta & delete
			_anwpfl_coach_home
			_anwpfl_coach_away
			_anwpfl_referee
			_anwpfl_match_events
			_anwpfl_stats_home_club
			_anwpfl_stats_away_club
			*/

			/*
			New Tables
			>> Predictions (DONE)
				_anwpfl_prediction_advice
				_anwpfl_prediction_percent
				_anwpfl_prediction_comparison

			>> Formations (DONE)
				_anwpfl_match_formation
				_anwpfl_home_club_shirt
				_anwpfl_away_club_shirt
				_anwpfl_match_formation_extra

			>> lineups
				_anwpfl_players_home_line_up
				_anwpfl_players_away_line_up
				_anwpfl_players_home_subs
				_anwpfl_players_away_subs
				_anwpfl_match_custom_numbers
				_anwpfl_captain_home
				_anwpfl_captain_away
			*/

			/*
			Adopt meta
			_anwpfl_fixed >> check table record exists
			_anwpfl_extra_time _anwpfl_penalty >> extra (0 - none, 1 - extra time, 2 - penalty, 3 - penalty without extra time)
			_anwpfl_status >> finished
			*/

			/*
			Clear meta
			_anwpfl_prediction_advice
			*/

			/*
			ODDS ????
			*/

			/*
			Database field => postmeta OLD

			'home_goals'         => goalsH,
			'away_goals'         => goalsA,
			'home_goals_half'    => goals1H,
			'away_goals_half'    => goals1A,
			'home_goals_ft'      => goalsFTH,
			'away_goals_ft'      => goalsFTA,
			'home_goals_e'       => extraTimeH,
			'away_goals_e'       => extraTimeA,
			'home_goals_p'       => penaltyH,
			'away_goals_p'       => penaltyA,
			'home_cards_y'       => yellowCardsH,
			'away_cards_y'       => yellowCardsA,
			'home_cards_yr'      => yellow2RCardsH,
			'away_cards_yr'      => yellow2RCardsA,
			'home_cards_r'       => redCardsH,
			'away_cards_r'       => redCardsA,
			'home_corners'       => cornersH,
			'away_corners'       => cornersA,
			'home_fouls'         => foulsH,
			'away_fouls'         => foulsA,
			'home_offsides'      => offsidesH,
			'away_offsides'      => offsidesA,
			'home_possession'    => possessionH,
			'away_possession'    => possessionA,
			'home_shots'         => shotsH,
			'away_shots'         => shotsA,
			'home_shots_on_goal' => shotsOnGoalsH,
			'away_shots_on_goal' => shotsOnGoalsA,
			*/

		// Remove columns from `anwpfl_players` table
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}anwpfl_players';" ) ) {

			// season_id
			if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}anwpfl_players` LIKE 'season_id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}anwpfl_players DROP COLUMN `season_id`;" );
			}

			// competition_id
			if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}anwpfl_players` LIKE 'competition_id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}anwpfl_players DROP COLUMN `competition_id`;" );
			}

			// main_stage_id
			if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}anwpfl_players` LIKE 'main_stage_id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}anwpfl_players DROP COLUMN `main_stage_id`;" );
			}

			// competition_status
			if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}anwpfl_players` LIKE 'competition_status';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}anwpfl_players DROP COLUMN `competition_status`;" );
			}

			// league_id
			if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}anwpfl_players` LIKE 'league_id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}anwpfl_players DROP COLUMN `league_id`;" );
			}
		}

		// Replace competition_status with new game_status
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}anwpfl_matches';" ) ) {
			if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}anwpfl_matches` LIKE 'competition_status';" ) ) {
				if ( false !== $wpdb->query( "UPDATE {$wpdb->prefix}anwpfl_matches SET game_status = '2' WHERE competition_status = 'friendly';" ) ) {
					$wpdb->query( "ALTER TABLE {$wpdb->prefix}anwpfl_matches DROP COLUMN `competition_status`;" );
				}
			}
		}

		if ( $saved_db_version && $saved_db_version < AnWP_Football_Leagues::DB_VERSION ) {
			$query = "
				ALTER TABLE $wpdb->anwpfl_matches
				MODIFY COLUMN home_goals_half tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_goals_half tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_goals_ft tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_goals_ft tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_goals_e tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_goals_e tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_goals_p tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_goals_p tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_cards_y tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_cards_y tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_cards_yr tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_cards_yr tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_cards_r tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_cards_r tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_corners tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_corners tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_fouls tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_fouls tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_offsides tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_offsides tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_possession tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_possession tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_shots tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_shots tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN home_shots_on_goal tinyint(3) UNSIGNED NULL DEFAULT NULL,
				MODIFY COLUMN away_shots_on_goal tinyint(3) UNSIGNED NULL DEFAULT NULL
			";
			$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			/*
			|--------------------------------------------------------------------
			| v0.16.7 - main_stage_id is equal competition_id in single-stage tournaments
			|--------------------------------------------------------------------
			*/
			if ( absint( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->anwpfl_matches WHERE main_stage_id = 0;" ) ?: 0 ) ) {
				$query__0_16_7 = "UPDATE $wpdb->anwpfl_matches SET main_stage_id = competition_id WHERE main_stage_id = 0;";
				$wpdb->query( $query__0_16_7 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		return $success;
	}

	/**
	 * Force hide v0.16.0 migration notice.
	 */
	public function hide_migrate_notice() {
		if ( 15 === absint( get_option( 'anwpfl_data_schema' ) ) ) {
			anwp_fl()->cache->flush_all_cache();
			update_option( 'anwpfl_data_schema', 16, true );
		}

		return rest_ensure_response( [] );
	}

	public function get_toolbox_updater_tasks( $output = '' ) {
		global $wpdb;

		$tasks = [];

		/*
		|--------------------------------------------------------------------
		| Migrate Player's Meta to player_data table
		|--------------------------------------------------------------------
		*/
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}anwpfl_player_data';" ) ) {
			$posts_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'anwp_player' AND post_status != 'auto-draft';" );
			$data_ids  = $wpdb->get_col( "SELECT player_id FROM {$wpdb->prefix}anwpfl_player_data;" );

			$ids_to_migrate = array_diff( $posts_ids, $data_ids );

			if ( count( $ids_to_migrate ) ) {
				$tasks[] = [
					'status'      => 'pending',
					'total'       => count( $ids_to_migrate ),
					'order'       => 40,
					'title'       => 'Move player meta data to a new "player_data" table',
					'slug'        => 'move_player_meta__anwpfl_player_data',
					'description' => 'Move player meta data from "postmeta" table to "player_data" table.',
					'subtasks'    => array_values( $ids_to_migrate ),
				];
			}
		}

		/*
		|--------------------------------------------------------------------
		| Migrate Game meta to dedicated table
		|--------------------------------------------------------------------
		*/
		$games_to_migrate_qty = intval( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE `meta_key` = '_anwpfl_match_datetime';" ) );

		if ( $games_to_migrate_qty ) {
			$games_to_migrate = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE `meta_key` = '_anwpfl_match_datetime';" );

			$tasks[] = [
				'status'      => 'pending',
				'total'       => count( $games_to_migrate ),
				'order'       => 50,
				'title'       => 'Migrate game meta to "matches" table',
				'slug'        => 'migrate_games_from_meta',
				'description' => 'Move game metadata from "postmeta" to "matches" table.',
				'subtasks'    => array_values( $games_to_migrate ),
			];
		}

		/*
		|--------------------------------------------------------------------
		| Migrate Game lineups to dedicated table
		|--------------------------------------------------------------------
		*/
		$lineups_to_migrate = $wpdb->get_col( "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE `meta_key` IN( '_anwpfl_players_home_line_up', '_anwpfl_players_away_line_up', '_anwpfl_players_home_subs', '_anwpfl_players_away_subs', '_anwpfl_match_custom_numbers', '_anwpfl_captain_home', '_anwpfl_captain_away' );" );

		if ( count( $lineups_to_migrate ) ) {
			$tasks[] = [
				'status'      => 'pending',
				'total'       => count( $lineups_to_migrate ),
				'order'       => 70,
				'title'       => 'Migrate lineups meta to "lineups" table',
				'slug'        => 'migrate_lineups_from_meta',
				'description' => 'Move game lineups metadata from "postmeta" to "lineups" table.',
				'subtasks'    => array_values( $lineups_to_migrate ),
			];
		}

		/*
		|--------------------------------------------------------------------
		| Output
		|--------------------------------------------------------------------
		*/
		$updater_tasks = wp_list_sort( apply_filters( 'anwpfl/toolbox-updater/get_updater_tasks', $tasks ), 'order' );

		if ( 15 === absint( get_option( 'anwpfl_data_schema' ) ) && empty( $updater_tasks ) ) {
			anwp_fl()->cache->flush_all_cache();
			update_option( 'anwpfl_data_schema', 16, true );
		}

		do_action( 'anwpfl/toolbox-updater/after_get_updater_tasks', $updater_tasks );

		if ( 'tasks' === $output ) {
			return $updater_tasks;
		}

		return rest_ensure_response( [ 'tasks' => $updater_tasks ] );
	}

	/**
	 * Run task to migrate meta to "player_data" table.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function run_move_player_meta_anwpfl_player_data( WP_REST_Request $request ) {
		global $wpdb;

		$player_ids = array_map( 'absint', $request->get_param( 'subtasks' ) );

		if ( empty( $player_ids ) ) {
			return rest_ensure_response( [] );
		}

		foreach ( $player_ids as $player_id ) {
			if ( empty( $player_id ) ) {
				return rest_ensure_response( [] );
			}

			$player_obj = get_post( $player_id );

			/*
			|--------------------------------------------------------------------
			| Prepare non-standard data
			|--------------------------------------------------------------------
			*/
			$nationality     = '';
			$nationality_ext = '';
			$nationality_raw = maybe_unserialize( get_post_meta( $player_id, '_anwpfl_nationality', true ) );

			if ( is_array( $nationality_raw ) && count( $nationality_raw ) ) {
				$nationality = array_shift( $nationality_raw );

				if ( count( $nationality_raw ) ) {
					$nationality_ext = '%' . implode( '%', $nationality_raw ) . '%';
				}
			}

			$photo     = '';
			$photo_raw = get_post_meta( $player_id, '_anwpfl_photo', true );

			if ( ! empty( $photo_raw ) ) {
				$photo_raw  = wp_make_link_relative( $photo_raw );
				$upload_dir = wp_make_link_relative( wp_upload_dir()['baseurl'] );

				$photo = str_ireplace( $upload_dir, '', $photo_raw );
			}

			/*
			|--------------------------------------------------------------------
			| Insert Data
			|--------------------------------------------------------------------
			*/
			$insert_data = [
				'player_id'          => $player_id,
				'name'               => $player_obj->post_title,
				'short_name'         => get_post_meta( $player_id, '_anwpfl_short_name', true ),
				'full_name'          => get_post_meta( $player_id, '_anwpfl_full_name', true ),
				'weight'             => get_post_meta( $player_id, '_anwpfl_weight', true ),
				'height'             => get_post_meta( $player_id, '_anwpfl_height', true ),
				'position'           => get_post_meta( $player_id, '_anwpfl_position', true ),
				'team_id'            => get_post_meta( $player_id, '_anwpfl_current_club', true ),
				'national_team'      => get_post_meta( $player_id, '_anwpfl_national_team', true ),
				'nationality'        => $nationality,
				'nationality_extra'  => $nationality_ext,
				'place_of_birth'     => get_post_meta( $player_id, '_anwpfl_place_of_birth', true ),
				'country_of_birth'   => get_post_meta( $player_id, '_anwpfl_country_of_birth', true ),
				'date_of_birth'      => get_post_meta( $player_id, '_anwpfl_date_of_birth', true ),
				'date_of_death'      => get_post_meta( $player_id, '_anwpfl_date_of_death', true ),
				'player_external_id' => get_post_meta( $player_id, '_anwpfl_player_external_id', true ),
				'photo'              => $photo,
			];

			if ( ! $wpdb->insert( $wpdb->prefix . 'anwpfl_player_data', $insert_data ) ) {
				return new WP_Error( 'anwp_rest_error', 'Insert Data Error', [ 'status' => 400 ] );
			}

			delete_post_meta( $player_id, '_anwpfl_short_name' );
			delete_post_meta( $player_id, '_anwpfl_full_name' );
			delete_post_meta( $player_id, '_anwpfl_weight' );
			delete_post_meta( $player_id, '_anwpfl_height' );
			delete_post_meta( $player_id, '_anwpfl_position' );
			delete_post_meta( $player_id, '_anwpfl_current_club' );
			delete_post_meta( $player_id, '_anwpfl_national_team' );
			delete_post_meta( $player_id, '_anwpfl_place_of_birth' );
			delete_post_meta( $player_id, '_anwpfl_country_of_birth' );
			delete_post_meta( $player_id, '_anwpfl_date_of_birth' );
			delete_post_meta( $player_id, '_anwpfl_date_of_death' );
			delete_post_meta( $player_id, '_anwpfl_player_external_id' );
			delete_post_meta( $player_id, '_anwpfl_nationality' );
			delete_post_meta( $player_id, '_anwpfl_photo' );
			delete_post_meta( $player_id, '_anwpfl_photo_id' );
		}

		return rest_ensure_response( [] );
	}

	/**
	 * Run task to migrate game meta to "matches" table.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function run_migrate_games_from_meta( WP_REST_Request $request ) {
		global $wpdb;

		$game_ids = array_map( 'absint', $request->get_param( 'subtasks' ) );

		if ( empty( $game_ids ) ) {
			return rest_ensure_response( [] );
		}

		foreach ( $game_ids as $game_id ) {
			$game_obj = get_post( $game_id );

			if ( empty( $game_obj->ID ) || $game_obj->ID !== $game_id ) {
				return rest_ensure_response( [] );
			}

			/*
			|--------------------------------------------------------------------
			| Prepare non-standard data
			|--------------------------------------------------------------------
			*/
			$extra_penalty = 0;
			$fl_extra_time = get_post_meta( $game_id, '_anwpfl_extra_time', true );
			$fl_penalty    = get_post_meta( $game_id, '_anwpfl_penalty', true );

			if ( 'yes' === $fl_extra_time && 'yes' === $fl_penalty ) {
				$extra_penalty = 2;
			} elseif ( 'yes' === $fl_penalty ) {
				$extra_penalty = 3;
			} elseif ( 'yes' === $fl_extra_time ) {
				$extra_penalty = 1;
			}

			/*
			|--------------------------------------------------------------------
			| Insert Data
			|--------------------------------------------------------------------
			*/
			$update_data = [
				'extra'           => $extra_penalty,
				'coach_home'      => get_post_meta( $game_id, '_anwpfl_coach_home', true ),
				'coach_away'      => get_post_meta( $game_id, '_anwpfl_coach_away', true ),
				'referee'         => get_post_meta( $game_id, '_anwpfl_referee', true ),
				'match_events'    => get_post_meta( $game_id, '_anwpfl_match_events', true ),
				'stats_home_club' => get_post_meta( $game_id, '_anwpfl_stats_home_club', true ),
				'stats_away_club' => get_post_meta( $game_id, '_anwpfl_stats_away_club', true ),
			];

			$match_stats = get_post_meta( $game_id, '_anwpfl_match_stats', true ) ? json_decode( get_post_meta( $game_id, '_anwpfl_match_stats', true ), true ) : [];
			$stat_map    = [
				'home_goals_half'    => 'goals1H',
				'away_goals_half'    => 'goals1A',
				'home_goals_ft'      => 'goalsFTH',
				'away_goals_ft'      => 'goalsFTA',
				'home_goals_e'       => 'extraTimeH',
				'away_goals_e'       => 'extraTimeA',
				'home_goals_p'       => 'penaltyH',
				'away_goals_p'       => 'penaltyA',
				'home_cards_y'       => 'yellowCardsH',
				'away_cards_y'       => 'yellowCardsA',
				'home_cards_yr'      => 'yellow2RCardsH',
				'away_cards_yr'      => 'yellow2RCardsA',
				'home_cards_r'       => 'redCardsH',
				'away_cards_r'       => 'redCardsA',
				'home_corners'       => 'cornersH',
				'away_corners'       => 'cornersA',
				'home_fouls'         => 'foulsH',
				'away_fouls'         => 'foulsA',
				'home_offsides'      => 'offsidesH',
				'away_offsides'      => 'offsidesA',
				'home_possession'    => 'possessionH',
				'away_possession'    => 'possessionA',
				'home_shots'         => 'shotsH',
				'away_shots'         => 'shotsA',
				'home_shots_on_goal' => 'shotsOnGoalsH',
				'away_shots_on_goal' => 'shotsOnGoalsA',
			];

			if ( ! empty( $match_stats ) ) {
				foreach ( $stat_map as $stat_slug => $old_stat_slug ) {
					$update_data[ $stat_slug ] = isset( $match_stats[ $old_stat_slug ] ) && '' !== $match_stats[ $old_stat_slug ] ? $match_stats[ $old_stat_slug ] : null;
				}
			}

			if ( false === $wpdb->update( $wpdb->prefix . 'anwpfl_matches', $update_data, [ 'match_id' => $game_id ] ) ) {
				if ( 'anwp_match' === get_post_type( $game_id ) ) {
					return new WP_Error( 'anwp_rest_error', 'Update Data Error - ID:' . absint( $game_id ) . ' (' . get_post_status( $game_id ) . ') - Data: ' . wp_json_encode( $update_data ), [ 'status' => 400 ] );
				}
			}

			delete_post_meta( $game_id, '_anwpfl_aggtext' );
			delete_post_meta( $game_id, '_anwpfl_attendance' );
			delete_post_meta( $game_id, '_anwpfl_club_away' );
			delete_post_meta( $game_id, '_anwpfl_club_home' );
			delete_post_meta( $game_id, '_anwpfl_coach_away' );
			delete_post_meta( $game_id, '_anwpfl_coach_home' );
			delete_post_meta( $game_id, '_anwpfl_competition' );
			delete_post_meta( $game_id, '_anwpfl_competition_group' );
			delete_post_meta( $game_id, '_anwpfl_extra_time' );
			delete_post_meta( $game_id, '_anwpfl_fixed' );
			delete_post_meta( $game_id, '_anwpfl_league' );
			delete_post_meta( $game_id, '_anwpfl_match_datetime' );
			delete_post_meta( $game_id, '_anwpfl_match_events' );
			delete_post_meta( $game_id, '_anwpfl_match_goals_away' );
			delete_post_meta( $game_id, '_anwpfl_match_goals_home' );
			delete_post_meta( $game_id, '_anwpfl_match_id' );
			delete_post_meta( $game_id, '_anwpfl_match_stats' );
			delete_post_meta( $game_id, '_anwpfl_matchweek' );
			delete_post_meta( $game_id, '_anwpfl_penalty' );
			delete_post_meta( $game_id, '_anwpfl_priority' );
			delete_post_meta( $game_id, '_anwpfl_referee' );
			delete_post_meta( $game_id, '_anwpfl_season' );
			delete_post_meta( $game_id, '_anwpfl_special_status' );
			delete_post_meta( $game_id, '_anwpfl_stadium' );
			delete_post_meta( $game_id, '_anwpfl_stats_away_club' );
			delete_post_meta( $game_id, '_anwpfl_stats_home_club' );
			delete_post_meta( $game_id, '_anwpfl_status' );
		}

		return rest_ensure_response( [] );
	}

	/**
	 * Run task to migrate lineups meta to "lineups" table.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function run_migrate_lineups_from_meta( WP_REST_Request $request ) {
		global $wpdb;

		$game_ids = array_map( 'absint', $request->get_param( 'subtasks' ) );

		if ( empty( $game_ids ) ) {
			return rest_ensure_response( [] );
		}

		foreach ( $game_ids as $game_id ) {
			$game_obj = get_post( $game_id );

			if ( empty( $game_obj->ID ) || $game_obj->ID !== $game_id ) {
				if ( empty( $game_obj->ID ) ) {
					delete_post_meta( $game_id, '_anwpfl_players_home_line_up' );
					delete_post_meta( $game_id, '_anwpfl_players_away_line_up' );
					delete_post_meta( $game_id, '_anwpfl_players_home_subs' );
					delete_post_meta( $game_id, '_anwpfl_players_away_subs' );
					delete_post_meta( $game_id, '_anwpfl_match_custom_numbers' );
					delete_post_meta( $game_id, '_anwpfl_captain_home' );
					delete_post_meta( $game_id, '_anwpfl_captain_away' );
				}

				return rest_ensure_response( [] );
			}

			/*
			|--------------------------------------------------------------------
			| Insert Data
			|--------------------------------------------------------------------
			*/
			$insert_data = [
				'match_id'       => $game_id,
				'home_line_up'   => get_post_meta( $game_id, '_anwpfl_players_home_line_up', true ),
				'away_line_up'   => get_post_meta( $game_id, '_anwpfl_players_away_line_up', true ),
				'home_subs'      => get_post_meta( $game_id, '_anwpfl_players_home_subs', true ),
				'away_subs'      => get_post_meta( $game_id, '_anwpfl_players_away_subs', true ),
				'custom_numbers' => get_post_meta( $game_id, '_anwpfl_match_custom_numbers', true ),
				'captain_home'   => get_post_meta( $game_id, '_anwpfl_captain_home', true ),
				'captain_away'   => get_post_meta( $game_id, '_anwpfl_captain_away', true ),
			];

			if ( false === $wpdb->insert( $wpdb->anwpfl_lineups, $insert_data ) ) {
				$is_empty = empty( $insert_data['home_line_up'] ) && empty( $insert_data['away_line_up'] )
				            && empty( $insert_data['home_subs'] ) && empty( $insert_data['away_subs'] )
				            && empty( $insert_data['captain_home'] ) && empty( $insert_data['captain_away'] )
				            && ( empty( $insert_data['custom_numbers'] ) || '{}' === $insert_data['custom_numbers'] );

				if ( ! $is_empty ) {
					$maybe_saved = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT match_id FROM $wpdb->anwpfl_lineups WHERE `match_id` = %d",
							$game_id
						)
					) ?: 0;

					if ( absint( $maybe_saved ) === absint( $game_id ) ) {
						unset( $insert_data['match_id'] );

						if ( false === $wpdb->update( $wpdb->anwpfl_lineups, $insert_data, [ 'match_id' => absint( $maybe_saved ) ] ) ) {
							return new WP_Error( 'anwp_rest_error', 'Update Data Error - ID:' . absint( $game_id ) . ' (' . get_post_status( $game_id ) . ') - Data: ' . wp_json_encode( $insert_data ), [ 'status' => 400 ] );
						}
					} elseif ( 'anwp_match' === get_post_type( $game_id ) ) {
						return new WP_Error( 'anwp_rest_error', 'Insert Data Error - ID:' . absint( $game_id ) . ' (' . get_post_status( $game_id ) . ') - Data: ' . wp_json_encode( $insert_data ), [ 'status' => 400 ] );
					}
				}
			}

			delete_post_meta( $game_id, '_anwpfl_players_home_line_up' );
			delete_post_meta( $game_id, '_anwpfl_players_away_line_up' );
			delete_post_meta( $game_id, '_anwpfl_players_home_subs' );
			delete_post_meta( $game_id, '_anwpfl_players_away_subs' );
			delete_post_meta( $game_id, '_anwpfl_match_custom_numbers' );
			delete_post_meta( $game_id, '_anwpfl_captain_home' );
			delete_post_meta( $game_id, '_anwpfl_captain_away' );
		}

		return rest_ensure_response( [] );
	}
}
