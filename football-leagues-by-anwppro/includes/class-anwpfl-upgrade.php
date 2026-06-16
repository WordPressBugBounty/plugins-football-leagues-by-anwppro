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

		/*
		|--------------------------------------------------------------------
		| Introduce Data Schema in v0.16
		|--------------------------------------------------------------------
		*/
		if ( version_compare( $version_saved, '0.16.0', '<' ) ) {
			update_option( 'anwpfl_data_schema', empty( $this->get_toolbox_updater_tasks( 'tasks' ) ) ? 16 : 15, true );
		}

		/*
		|--------------------------------------------------------------------
		| New Caching System v0.16.16
		|--------------------------------------------------------------------
		*/
		if ( version_compare( $version_saved, '0.16.15.1', '<' ) ) {
			if ( wp_using_ext_object_cache() && 'no' !== AnWPFL_Options::get_value( 'cache_active' ) ) {
				anwp_fl()->cache->flush_all_cache_v1();
			}
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

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/migrate_clubs_to_table/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_migrate_clubs_to_table' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/migrate_squad_to_table/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_migrate_squad_to_table' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/migrate_competitions_to_table/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_migrate_competitions_to_table' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/migrate_standings_to_table/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_migrate_standings_to_table' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/cleanup_club_postmeta/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_cleanup_club_postmeta' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/cleanup_standing_postmeta/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_cleanup_standing_postmeta' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/cleanup_competition_postmeta/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_cleanup_competition_postmeta' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/api-toolbox-updater',
			'/cleanup_squad_postmeta/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'run_cleanup_squad_postmeta' ],
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
	 * Run task to migrate club data from postmeta to anwpfl_clubs table.
	 *
	 * Called via Toolbox Updater REST endpoint. Receives club IDs from frontend.
	 * Idempotent: safe to re-run (INSERT ... ON DUPLICATE KEY UPDATE).
	 * Does NOT delete postmeta - cleanup is a separate step.
	 *
	 * @since 0.17.3
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function run_migrate_clubs_to_table( WP_REST_Request $request ) {

		if ( AnWP_Football_Leagues::is_premium_too_old() ) {
			return new WP_Error(
				'anwpfl_premium_too_old',
				__( 'AnWP Football Leagues Premium must be updated to 0.18.0 or higher before running data migration.', 'anwp-football-leagues' ),
				[ 'status' => 412 ]
			);
		}

		global $wpdb;

		$club_ids = array_map( 'absint', $request->get_param( 'subtasks' ) );

		if ( empty( $club_ids ) ) {
			return rest_ensure_response( [] );
		}

		// Prime meta cache for entire batch (1 query instead of N).
		update_meta_cache( 'post', $club_ids );

		$table = $wpdb->prefix . 'anwpfl_clubs';

		// Fetch post data for the batch.
		$ids_placeholder = implode( ',', array_fill( 0, count( $club_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders
		$clubs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_name FROM $wpdb->posts WHERE ID IN ($ids_placeholder)",
				$club_ids
			),
			OBJECT_K
		);

		foreach ( $club_ids as $club_id ) {
			if ( empty( $clubs[ $club_id ] ) ) {
				continue;
			}

			$club = $clubs[ $club_id ];
			$meta = get_post_meta( $club_id );

			// Helper to get single meta value with default.
			$m = function ( $key, $default = '' ) use ( $meta ) {
				return isset( $meta[ $key ][0] ) ? $meta[ $key ][0] : $default;
			};

			// --- Flat columns ---
			$is_national_team = 'yes' === $m( '_anwpfl_is_national_team' ) ? 1 : 0;
			$club_duplicates  = 'yes' === $m( '_anwpfl_club_duplicates' ) ? 1 : 0;
			$stadium_id       = absint( $m( '_anwpfl_stadium' ) );
			$logo             = $m( '_anwpfl_logo' );
			$logo_id          = absint( $m( '_anwpfl_logo_id' ) );
			$logo_big         = $m( '_anwpfl_logo_big' );
			$logo_big_id      = absint( $m( '_anwpfl_logo_big_id' ) );

			// --- club_details JSON ---
			$gallery = maybe_unserialize( $m( '_anwpfl_gallery' ) );
			if ( ! is_array( $gallery ) ) {
				$gallery = [];
			}

			$subteam_list = maybe_unserialize( $m( '_anwpfl_subteam_list' ) );
			if ( ! is_array( $subteam_list ) ) {
				$subteam_list = [];
			}

			$club_details = wp_json_encode( [
				'address'          => $m( '_anwpfl_address' ),
				'website'          => $m( '_anwpfl_website' ),
				'founded'          => $m( '_anwpfl_founded' ),
				'club_kit'         => $m( '_anwpfl_club_kit' ),
				'club_kit_id'      => absint( $m( '_anwpfl_club_kit_id' ) ),
				'gallery'          => $gallery,
				'gallery_notes'    => $m( '_anwpfl_gallery_notes' ),
				'root_team_title'  => $m( '_anwpfl_root_team_title' ),
				'subteam_list'     => $subteam_list,
			] );

			// --- club_social JSON ---
			$club_social = wp_json_encode( [
				'twitter'   => $m( '_anwpfl_twitter' ),
				'facebook'  => $m( '_anwpfl_facebook' ),
				'youtube'   => $m( '_anwpfl_youtube' ),
				'instagram' => $m( '_anwpfl_instagram' ),
				'linkedin'  => $m( '_anwpfl_linkedin' ),
				'tiktok'    => $m( '_anwpfl_tiktok' ),
				'vk'        => $m( '_anwpfl_vk' ),
			] );

			// --- club_custom JSON ---
			$custom_fields = maybe_unserialize( $m( '_anwpfl_custom_fields' ) );
			if ( ! is_array( $custom_fields ) ) {
				$custom_fields = [];
			}

			$club_custom = wp_json_encode( [
				'custom_title_1' => $m( '_anwpfl_custom_title_1' ),
				'custom_value_1' => $m( '_anwpfl_custom_value_1' ),
				'custom_title_2' => $m( '_anwpfl_custom_title_2' ),
				'custom_value_2' => $m( '_anwpfl_custom_value_2' ),
				'custom_title_3' => $m( '_anwpfl_custom_title_3' ),
				'custom_value_3' => $m( '_anwpfl_custom_value_3' ),
				'custom_fields'  => (object) $custom_fields,
			] );

			// --- Longtext columns ---
			$description          = $m( '_anwpfl_description' );
			$custom_content_below = $m( '_anwpfl_custom_content_below' );

			// --- Upsert (core columns only) ---
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $table
						(club_id, title, post_name, abbr, city, nationality, stadium_id,
						 is_national_team, club_duplicates, club_external_id, subteams,
						 root_team, root_type, logo, logo_id, logo_big, logo_big_id,
						 main_color, club_details, club_social, club_custom,
						 description, custom_content_below,
						 squad, squad_staff, squad_seasons, squad_staff_seasons)
					VALUES (%d, %s, %s, %s, %s, %s, %d,
						%d, %d, %s, %s,
						%d, %s, %s, %d, %s, %d,
						%s, %s, %s, %s,
						%s, %s,
						'', '', '', '')
					ON DUPLICATE KEY UPDATE
						title = VALUES(title),
						post_name = VALUES(post_name),
						abbr = VALUES(abbr),
						city = VALUES(city),
						nationality = VALUES(nationality),
						stadium_id = VALUES(stadium_id),
						is_national_team = VALUES(is_national_team),
						club_duplicates = VALUES(club_duplicates),
						club_external_id = VALUES(club_external_id),
						subteams = VALUES(subteams),
						root_team = VALUES(root_team),
						root_type = VALUES(root_type),
						logo = VALUES(logo),
						logo_id = VALUES(logo_id),
						logo_big = VALUES(logo_big),
						logo_big_id = VALUES(logo_big_id),
						main_color = VALUES(main_color),
						club_details = VALUES(club_details),
						club_social = VALUES(club_social),
						club_custom = VALUES(club_custom),
						description = VALUES(description),
						custom_content_below = VALUES(custom_content_below)",
					$club_id,
					$club->post_title,
					$club->post_name,
					$m( '_anwpfl_abbr' ),
					$m( '_anwpfl_city' ),
					$m( '_anwpfl_nationality' ),
					$stadium_id,
					$is_national_team,
					$club_duplicates,
					$m( '_anwpfl_club_external_id' ),
					$m( '_anwpfl_subteams' ),
					absint( $m( '_anwpfl_root_team' ) ),
					$m( '_anwpfl_root_type' ),
					$logo,
					$logo_id,
					$logo_big,
					$logo_big_id,
					$m( '_anwpfl_main_color' ),
					$club_details,
					$club_social,
					$club_custom,
					$description,
					$custom_content_below
				)
			);

			if ( false === $result ) {
				return new WP_Error( 'anwp_rest_error', 'Upsert Error - Club ID:' . $club_id, [ 'status' => 400 ] );
			}

			/**
			 * Fires after a club row is upserted during migration.
			 * Premium hooks here to populate its columns in the same pass.
			 *
			 * @since 0.17.3
			 *
			 * @param int   $club_id Club post ID.
			 * @param array $meta    All postmeta for the club (already cached).
			 */
			do_action( 'anwpfl/upgrade/club_row_migrated', $club_id, $meta );
		}

		return rest_ensure_response( [] );
	}

	/**
	 * Migrate squad/staff data from postmeta to anwpfl_clubs table columns.
	 *
	 * Reads _anwpfl_squad, _anwpfl_staff, _anwpfl_squad_display postmeta,
	 * picks the active season's roster as the flat squad, and stores the full
	 * season-keyed archive in squad_seasons/squad_staff_seasons.
	 *
	 * @since 0.18.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_migrate_squad_to_table( WP_REST_Request $request ) {

		global $wpdb;

		$club_ids = array_map( 'absint', $request->get_param( 'subtasks' ) );

		if ( empty( $club_ids ) ) {
			return rest_ensure_response( [] );
		}

		// Prime meta cache for entire batch.
		update_meta_cache( 'post', $club_ids );

		// Determine the active season ID.
		$active_season_id = anwp_fl()->get_active_season();

		// Fallback: find season whose title equals current year.
		$year_season_id = 0;
		$current_year   = date( 'Y' );

		foreach ( anwp_fl()->season->get_seasons_options() as $season_id => $season_title ) {
			if ( (string) $season_title === $current_year ) {
				$year_season_id = $season_id;
				break;
			}
		}

		$table = $wpdb->prefix . 'anwpfl_clubs';

		foreach ( $club_ids as $club_id ) {
			$meta = get_post_meta( $club_id );

			// Helper to get single meta value.
			$m = function ( $key ) use ( $meta ) {
				return isset( $meta[ $key ][0] ) ? $meta[ $key ][0] : '';
			};

			// --- Squad ---
			$squad_raw    = json_decode( $m( '_anwpfl_squad' ), true );
			$squad_active = '';
			$squad_all    = '';

			if ( is_array( $squad_raw ) && ! empty( $squad_raw ) ) {
				$squad_all = wp_json_encode( $squad_raw );

				// Pick active roster: active season > current year season > empty.
				$active_key = 's:' . $active_season_id;
				$year_key   = $year_season_id ? 's:' . $year_season_id : '';

				if ( ! empty( $squad_raw[ $active_key ] ) ) {
					$squad_active = wp_json_encode( $squad_raw[ $active_key ] );
				} elseif ( $year_key && ! empty( $squad_raw[ $year_key ] ) ) {
					$squad_active = wp_json_encode( $squad_raw[ $year_key ] );
				}
			}

			// --- Staff ---
			$staff_raw    = json_decode( $m( '_anwpfl_staff' ), true );
			$staff_active = '';
			$staff_all    = '';

			if ( is_array( $staff_raw ) && ! empty( $staff_raw ) ) {
				$staff_all = wp_json_encode( $staff_raw );

				$active_key = 's:' . $active_season_id;
				$year_key   = $year_season_id ? 's:' . $year_season_id : '';

				if ( ! empty( $staff_raw[ $active_key ] ) ) {
					$staff_active = wp_json_encode( $staff_raw[ $active_key ] );
				} elseif ( $year_key && ! empty( $staff_raw[ $year_key ] ) ) {
					$staff_active = wp_json_encode( $staff_raw[ $year_key ] );
				}
			}

			// --- Squad display (group toggle) ---
			$display_raw = json_decode( $m( '_anwpfl_squad_display' ), true );
			$squad_group = 1;

			if ( is_array( $display_raw ) && ! empty( $display_raw ) ) {
				// Use active season key, fall back to most recent season key.
				$display_season = $display_raw[ $active_season_id ] ?? $display_raw[ $year_season_id ] ?? null;

				if ( null === $display_season ) {
					$keys           = array_keys( $display_raw );
					$display_season = $display_raw[ end( $keys ) ] ?? null;
				}

				if ( is_array( $display_season ) && isset( $display_season['group'] ) ) {
					$squad_group = $display_season['group'] ? 1 : 0;
				}
			}

			// --- UPDATE existing row ---
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update(
				$table,
				[
					'squad'               => $squad_active,
					'squad_staff'         => $staff_active,
					'squad_seasons'       => $squad_all,
					'squad_staff_seasons' => $staff_all,
					'squad_group'         => $squad_group,
				],
				[ 'club_id' => $club_id ],
				[ '%s', '%s', '%s', '%s', '%d' ],
				[ '%d' ]
			);
		}

		return rest_ensure_response( [] );
	}

	/**
	 * Batch delete club postmeta that has been migrated to the anwpfl_clubs table.
	 *
	 * Deletes up to 500 postmeta rows per request. Returns remaining count
	 * so the caller can loop until complete.
	 *
	 * @since 0.18.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_cleanup_club_postmeta() {
		global $wpdb;

		if ( ! get_option( 'anwpfl_clubs_migrated' ) ) {
			return new WP_Error(
				'anwpfl_not_migrated',
				'Club migration has not been completed yet.',
				[ 'status' => 400 ]
			);
		}

		$meta_keys    = self::get_club_postmeta_keys();
		$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
		$batch_size   = 500;

		// Get meta_ids to delete (batch).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT pm.meta_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = 'anwp_club'
			AND pm.meta_key IN ($placeholders)
			LIMIT %d",
			...array_merge( $meta_keys, [ $batch_size ] )
		) );

		if ( empty( $meta_ids ) ) {
			update_option( 'anwpfl_clubs_postmeta_cleaned', 1, true );

			return rest_ensure_response( [
				'remaining' => 0,
				'deleted'   => 0,
				'completed' => true,
			] );
		}

		$meta_ids      = array_map( 'absint', $meta_ids );
		$ids_in        = implode( ',', $meta_ids );
		$deleted_count = count( $meta_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_id IN ($ids_in)" );

		// Count remaining.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$remaining = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = 'anwp_club'
			AND pm.meta_key IN ($placeholders)",
			...$meta_keys
		) );

		if ( 0 === $remaining ) {
			update_option( 'anwpfl_clubs_postmeta_cleaned', 1, true );
		}

		return rest_ensure_response( [
			'remaining' => $remaining,
			'deleted'   => $deleted_count,
			'completed' => 0 === $remaining,
		] );
	}

	/**
	 * Batch delete standing postmeta that has been migrated to anwpfl_standings.
	 *
	 * Deletes up to 500 postmeta rows per request. Returns remaining count
	 * so the caller can loop until complete. Also cleans up dynamic matchweek
	 * cache keys (_anwpfl_table_main_*) that are no longer written.
	 *
	 * @since 0.18.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_cleanup_standing_postmeta() {
		global $wpdb;

		if ( ! get_option( 'anwpfl_standings_migrated' ) ) {
			return new WP_Error(
				'anwpfl_not_migrated',
				'Standing migration has not been completed yet.',
				[ 'status' => 400 ]
			);
		}

		$meta_keys    = self::get_standing_postmeta_keys();
		$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
		$batch_size   = 500;

		// Get meta_ids to delete (batch): static keys + dynamic matchweek cache keys.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT pm.meta_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = 'anwp_standing'
			AND ( pm.meta_key IN ($placeholders) OR pm.meta_key LIKE %s )
			LIMIT %d",
			...array_merge( $meta_keys, [ '_anwpfl_table_main_%', $batch_size ] )
		) );

		if ( empty( $meta_ids ) ) {
			update_option( 'anwpfl_standings_postmeta_cleaned', 1, true );

			return rest_ensure_response( [
				'remaining' => 0,
				'deleted'   => 0,
				'completed' => true,
			] );
		}

		$meta_ids      = array_map( 'absint', $meta_ids );
		$ids_in        = implode( ',', $meta_ids );
		$deleted_count = count( $meta_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_id IN ($ids_in)" );

		// Count remaining.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$remaining = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = 'anwp_standing'
			AND ( pm.meta_key IN ($placeholders) OR pm.meta_key LIKE %s )",
			...array_merge( $meta_keys, [ '_anwpfl_table_main_%' ] )
		) );

		if ( 0 === $remaining ) {
			update_option( 'anwpfl_standings_postmeta_cleaned', 1, true );
		}

		return rest_ensure_response( [
			'remaining' => $remaining,
			'deleted'   => $deleted_count,
			'completed' => 0 === $remaining,
		] );
	}

	/**
	 * Batch delete competition postmeta that has been migrated to anwpfl_competitions.
	 *
	 * Deletes up to 500 postmeta rows per request. Returns remaining count
	 * so the caller can loop until complete. Excludes CMB2-managed keys that
	 * are still read from postmeta on save (see Phase 9 carve-out list),
	 * _anwpfl_cloned (audit trail), and _anwpfl_api_wizard_competition_id
	 * (actively used by API import wizard).
	 *
	 * @since 0.18.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_cleanup_competition_postmeta() {
		global $wpdb;

		if ( ! get_option( 'anwpfl_competitions_migrated' ) ) {
			return new WP_Error(
				'anwpfl_not_migrated',
				'Competition migration has not been completed yet.',
				[ 'status' => 400 ]
			);
		}

		$meta_keys    = self::get_competition_postmeta_keys();
		$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
		$batch_size   = 500;

		// Get meta_ids to delete (batch).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT pm.meta_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = 'anwp_competition'
			AND pm.meta_key IN ($placeholders)
			LIMIT %d",
			...array_merge( $meta_keys, [ $batch_size ] )
		) );

		if ( empty( $meta_ids ) ) {
			update_option( 'anwpfl_competitions_postmeta_cleaned', 1, true );

			return rest_ensure_response( [
				'remaining' => 0,
				'deleted'   => 0,
				'completed' => true,
			] );
		}

		$meta_ids      = array_map( 'absint', $meta_ids );
		$ids_in        = implode( ',', $meta_ids );
		$deleted_count = count( $meta_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_id IN ($ids_in)" );

		// Count remaining.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$remaining = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = 'anwp_competition'
			AND pm.meta_key IN ($placeholders)",
			...$meta_keys
		) );

		if ( 0 === $remaining ) {
			update_option( 'anwpfl_competitions_postmeta_cleaned', 1, true );
		}

		return rest_ensure_response( [
			'remaining' => $remaining,
			'deleted'   => $deleted_count,
			'completed' => 0 === $remaining,
		] );
	}

	/**
	 * Batch delete squad postmeta that has been migrated to anwpfl_clubs table.
	 *
	 * Deletes up to 500 postmeta rows per request. Returns remaining count
	 * so the caller can loop until complete. Targets the three squad keys
	 * (`_anwpfl_squad`, `_anwpfl_staff`, `_anwpfl_squad_display`) that were
	 * intentionally excluded from the club cleanup task because the active
	 * roster migration had not shipped yet.
	 *
	 * @since 0.18.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_cleanup_squad_postmeta() {
		global $wpdb;

		if ( ! get_option( 'anwpfl_squad_migrated' ) ) {
			return new WP_Error(
				'anwpfl_not_migrated',
				'Squad migration has not been completed yet.',
				[ 'status' => 400 ]
			);
		}

		$meta_keys    = self::get_squad_postmeta_keys();
		$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
		$batch_size   = 500;

		// Get meta_ids to delete (batch).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT pm.meta_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = 'anwp_club'
			AND pm.meta_key IN ($placeholders)
			LIMIT %d",
			...array_merge( $meta_keys, [ $batch_size ] )
		) );

		if ( empty( $meta_ids ) ) {
			update_option( 'anwpfl_squad_postmeta_cleaned', 1, true );

			return rest_ensure_response( [
				'remaining' => 0,
				'deleted'   => 0,
				'completed' => true,
			] );
		}

		$meta_ids      = array_map( 'absint', $meta_ids );
		$ids_in        = implode( ',', $meta_ids );
		$deleted_count = count( $meta_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_id IN ($ids_in)" );

		// Count remaining.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$remaining = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = 'anwp_club'
			AND pm.meta_key IN ($placeholders)",
			...$meta_keys
		) );

		if ( 0 === $remaining ) {
			update_option( 'anwpfl_squad_postmeta_cleaned', 1, true );
		}

		return rest_ensure_response( [
			'remaining' => $remaining,
			'deleted'   => $deleted_count,
			'completed' => 0 === $remaining,
		] );
	}

	/**
	 * Run task to migrate competition data from postmeta to anwpfl_competitions table.
	 *
	 * Called via Toolbox Updater REST endpoint. Receives competition IDs from frontend.
	 * Reuses get_competition_row_from_postmeta() which maps all postmeta keys to table
	 * columns and fires anwpfl/competition/postmeta_row filter for premium columns.
	 * Idempotent: safe to re-run (INSERT ... ON DUPLICATE KEY UPDATE via upsert()).
	 * Does NOT delete postmeta - cleanup is a separate step.
	 *
	 * @since 0.18.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function run_migrate_competitions_to_table( WP_REST_Request $request ) {

		if ( AnWP_Football_Leagues::is_premium_too_old() ) {
			return new WP_Error(
				'anwpfl_premium_too_old',
				__( 'AnWP Football Leagues Premium must be updated to 0.18.0 or higher before running data migration.', 'anwp-football-leagues' ),
				[ 'status' => 412 ]
			);
		}

		$competition_ids = array_map( 'absint', $request->get_param( 'subtasks' ) );

		if ( empty( $competition_ids ) ) {
			return rest_ensure_response( [] );
		}

		// Prime meta cache for entire batch (1 query instead of N).
		update_meta_cache( 'post', $competition_ids );

		// Prime term cache for entire batch (avoids 2N term queries from get_the_terms()
		// inside get_competition_row_from_postmeta()).
		update_object_term_cache( $competition_ids, 'anwp_competition' );

		foreach ( $competition_ids as $competition_id ) {
			$row = anwp_fl()->competition->get_competition_row_from_postmeta( $competition_id );

			if ( ! $row ) {
				continue;
			}

			// Secondary stages inherit competition_order from parent.
			// The postmeta method reads the secondary's own meta (empty → 0),
			// so override with parent's value (cascade handles pre-/post-migration).
			if ( absint( $row['multistage_main'] ) ) {
				$parent_order             = anwp_fl()->competition->get_competition_list_row( (int) $row['multistage_main'] )['competition_order'] ?? 0;
				$row['competition_order'] = absint( $parent_order );
			}

			$update_columns = array_diff( array_keys( $row ), [ 'competition_id' ] );

			$result = anwp_fl()->competition->upsert( $competition_id, $row, $update_columns );

			if ( false === $result ) {
				return new WP_Error( 'anwp_rest_error', 'Upsert Error - Competition ID:' . $competition_id, [ 'status' => 400 ] );
			}
		}

		return rest_ensure_response( [] );
	}

	/**
	 * Run task to migrate standing postmeta to the "standings" table.
	 *
	 * Called via Toolbox Updater REST endpoint. Receives standing IDs from frontend.
	 * Reuses AnWPFL_Standing::get_standing_row_from_postmeta() which maps all postmeta
	 * keys to table columns and fires anwpfl/standing/row_from_postmeta filter for
	 * premium columns.
	 *
	 * Idempotent: safe to re-run (INSERT ... ON DUPLICATE KEY UPDATE via upsert()).
	 * Also recovers partial rows left behind by Phase 2's async calculate_standing()
	 * writes - those rows have only competition_id/group_id/table_main/last_recalc/
	 * last_round populated; this pass fills the remaining config columns.
	 *
	 * Does NOT delete postmeta - cleanup is handled separately by Phase 7.
	 *
	 * @since 0.18.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function run_migrate_standings_to_table( WP_REST_Request $request ) {

		if ( AnWP_Football_Leagues::is_premium_too_old() ) {
			return new WP_Error(
				'anwpfl_premium_too_old',
				__( 'AnWP Football Leagues Premium must be updated to 0.18.0 or higher before running data migration.', 'anwp-football-leagues' ),
				[ 'status' => 412 ]
			);
		}

		$standing_ids = array_map( 'absint', $request->get_param( 'subtasks' ) );

		if ( empty( $standing_ids ) ) {
			return rest_ensure_response( [] );
		}

		// Prime meta cache for the full batch (1 query instead of 32*N).
		update_meta_cache( 'post', $standing_ids );

		foreach ( $standing_ids as $standing_id ) {
			$row = anwp_fl()->standing->get_standing_row_from_postmeta( $standing_id );

			if ( ! $row ) {
				continue; // un-fixed standing, skip silently.
			}

			// Exclude PK from update columns (matches competitions precedent).
			$update_columns = array_diff( array_keys( $row ), [ 'standing_id' ] );

			$result = anwp_fl()->standing->upsert( $standing_id, $row, $update_columns );

			if ( false === $result ) {
				return new WP_Error(
					'anwp_rest_error',
					'Upsert Error - Standing ID:' . $standing_id,
					[ 'status' => 400 ]
				);
			}
		}

		// Mark migration complete so get_toolbox_updater_tasks() stops re-queueing.
		// Postmeta-based detection (unlike competitions' LEFT JOIN IS NULL) never
		// empties because Phase 3 doesn't delete postmeta. Setting the flag here
		// after a successful batch is safe: Vue keeps processing from its in-memory
		// ID list, and subsequent get_toolbox_updater_tasks() calls short-circuit.
		$was_already_migrated = get_option( 'anwpfl_standings_migrated' );
		update_option( 'anwpfl_standings_migrated', 1, true );

		// Flush page/object cache once on the 0 -> 1 transition so admin notices
		// and the toolbox UI clear immediately on cache-enabled sites (LiteSpeed,
		// WP Rocket, etc.). Subsequent batches keep the flag at 1 and skip flush.
		if ( ! $was_already_migrated ) {
			anwp_fl()->cache->flush_all_cache();
		}

		return rest_ensure_response( [] );
	}

	/**
	 * Get the list of club postmeta keys that were migrated to anwpfl_clubs table.
	 *
	 * Includes both core and premium keys. Squad-related keys (_anwpfl_squad,
	 * _anwpfl_staff, _anwpfl_squad_display) are excluded - they are still in use.
	 *
	 * @since 0.18.0
	 *
	 * @return string[]
	 */
	public static function get_club_postmeta_keys(): array {
		return [
			// Flat columns.
			'_anwpfl_abbr',
			'_anwpfl_city',
			'_anwpfl_nationality',
			'_anwpfl_stadium',
			'_anwpfl_is_national_team',
			'_anwpfl_club_duplicates',
			'_anwpfl_club_external_id',
			'_anwpfl_subteams',
			'_anwpfl_root_team',
			'_anwpfl_root_type',
			'_anwpfl_logo',
			'_anwpfl_logo_id',
			'_anwpfl_logo_big',
			'_anwpfl_logo_big_id',
			'_anwpfl_main_color',
			'_anwpfl_description',
			'_anwpfl_custom_content_below',
			// club_details JSON.
			'_anwpfl_address',
			'_anwpfl_website',
			'_anwpfl_founded',
			'_anwpfl_club_kit',
			'_anwpfl_club_kit_id',
			'_anwpfl_gallery',
			'_anwpfl_gallery_notes',
			'_anwpfl_root_team_title',
			'_anwpfl_subteam_list',
			// club_social JSON.
			'_anwpfl_twitter',
			'_anwpfl_facebook',
			'_anwpfl_youtube',
			'_anwpfl_instagram',
			'_anwpfl_linkedin',
			'_anwpfl_tiktok',
			'_anwpfl_vk',
			// club_custom JSON.
			'_anwpfl_custom_title_1',
			'_anwpfl_custom_value_1',
			'_anwpfl_custom_title_2',
			'_anwpfl_custom_value_2',
			'_anwpfl_custom_title_3',
			'_anwpfl_custom_value_3',
			'_anwpfl_custom_fields',
			// Premium: club_shirts JSON.
			'_anwpfl_shirt_home',
			'_anwpfl_shirt_away',
			'_anwpfl_shirt_home_color',
			'_anwpfl_shirt_away_color',
			'_anwpfl_number_shirt_home_color',
			'_anwpfl_number_shirt_away_color',
			'_anwpfl_number_shirt_home_stroke_color',
			'_anwpfl_number_shirt_away_stroke_color',
			'_anwpfl_match_scoreboard_image',
			'_anwpfl_match_scoreboard_image_id',
			// Premium: other columns.
			'_fl_pro_trophies',
			'_anwpfl_role_club_captain',
			'_anwpfl_report_email',
		];
	}

	/**
	 * Get the list of standing postmeta keys that were migrated to anwpfl_standings table.
	 *
	 * Includes both core and premium keys. Returns the full 32-key list so the
	 * Toolbox Cleanup tab can batch-delete all orphaned standing postmeta.
	 *
	 * @since 0.18.0
	 *
	 * @return string[]
	 */
	public static function get_standing_postmeta_keys(): array {
		return [
			// Core columns.
			'_anwpfl_fixed',
			'_anwpfl_last_recalc',
			'_anwpfl_competition',
			'_anwpfl_competition_group',
			'_anwpfl_points_win',
			'_anwpfl_points_draw',
			'_anwpfl_points_loss',
			'_anwpfl_ranking_rules_current',
			'_anwpfl_manual_ordering',
			'_anwpfl_is_initial_data_active',
			'_anwpfl_table_last_round',
			'_anwpfl_table_notes',
			'_anwpfl_table_colors',
			'_anwpfl_points_initial',
			'_anwpfl_table_initial',
			'_anwpfl_table_main',
			// Premium columns.
			'_anwpfl_manual_filling',
			'_anwpfl_conferences_support',
			'_anwpfl_table_lazy',
			'_anwpfl_table_date_start',
			'_anwpfl_table_date_end',
			'_anwpfl_arrows_dynamic_ranking',
			'_anwpfl_arrows_dynamic_ranking_data',
			'_anwpfl_h2h_apply_after_all_games',
			'_anwpfl_club_conferences',
			'_anwpfl_columns_order',
			'_anwpfl_columns_order_sm',
			'_anwpfl_columns_order_xs',
			'_anwpfl_columns_mini_order',
			'_anwpfl_columns_mini_order_sm',
			'_anwpfl_table_main_home',
			'_anwpfl_table_main_away',
			// Orphan from a removed feature - zero PHP read paths in core or premium. Confirmed empty rows on
			// migrated installs (e.g. 22 rows on a2 post-migration). Safe to sweep via standing cleanup task.
			'_anwpfl_matrix_results',
		];
	}

	/**
	 * Get the list of competition postmeta keys that were migrated to anwpfl_competitions table.
	 *
	 * Includes both core and premium keys. Phase 12 added 8 former CMB2-managed keys
	 * after CMB2 removal. Only _anwpfl_cloned (write-once audit trail) and
	 * _anwpfl_api_wizard_competition_id (actively used by API import wizard) are excluded.
	 *
	 * @since 0.18.0
	 *
	 * @return string[]
	 */
	public static function get_competition_postmeta_keys(): array {
		return [
			// Core columns.
			'_anwpfl_type',
			'_anwpfl_format_robin',
			'_anwpfl_format_knockout',
			'_anwpfl_competition_status',
			'_anwpfl_multistage',
			'_anwpfl_multistage_main',
			'_anwpfl_stage_title',
			'_anwpfl_stage_order',
			'_anwpfl_logo',
			'_anwpfl_logo_id',
			'_anwpfl_group_next_id',
			'_anwpfl_round_next_id',
			'_anwpfl_groups',
			'_anwpfl_rounds',
			// Core columns (Phase 12 - former CMB2).
			'_anwpfl_competition_order',
			'_anwpfl_tmpl_layout',
			'_anwpfl_custom_content_below',
			'_anwpfl_logo_big',
			'_anwpfl_logo_big_id',
			// Premium columns.
			'_anwpfl_matchweek_current',
			'_anwpfl_bracket',
			'_anwpfl_bracket_layout_active',
			'_anwpfl_bracket_options',
			// Premium columns (Phase 12 - former CMB2).
			'_anwpfl_role_competition_supervisor',
			'_anwpfl_matchweeks_as_slides',
			'_anwpfl_lazy',
		];
	}

	/**
	 * Get the list of squad postmeta keys that were migrated to anwpfl_clubs table.
	 *
	 * These three keys were excluded from get_club_postmeta_keys() because the
	 * active roster migration had not shipped when club cleanup was first added.
	 * Now that migrate_squad_to_table has shipped (Phase 0-4), they can be removed.
	 *
	 * @since 0.18.0
	 *
	 * @return string[]
	 */
	public static function get_squad_postmeta_keys(): array {
		return [
			'_anwpfl_squad',
			'_anwpfl_staff',
			'_anwpfl_squad_display',
		];
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
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `competition_id` bigint(20) UNSIGNED NOT NULL,
  `main_stage_id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED NOT NULL,
  `league_id` bigint(20) UNSIGNED NOT NULL,
  `home_club` bigint(20) UNSIGNED NOT NULL,
  `away_club` bigint(20) UNSIGNED NOT NULL,
  `kickoff` datetime NOT NULL default '0000-00-00 00:00:00',
  `kickoff_gmt` datetime NOT NULL default '0000-00-00 00:00:00',
  `finished` tinyint(1) NOT NULL DEFAULT '0',
  `extra` tinyint(1) NOT NULL DEFAULT '0',
  `attendance` int(10) NOT NULL DEFAULT '0',
  `aggtext` varchar(250) NOT NULL DEFAULT '',
  `stadium_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `match_week` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `home_goals` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `away_goals` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `home_goals_half` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_goals_half` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_goals_ft` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_goals_ft` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_goals_e` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_goals_e` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_goals_p` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_goals_p` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_cards_y` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_cards_y` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_cards_yr` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_cards_yr` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_cards_r` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_cards_r` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_corners` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_corners` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_fouls` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_fouls` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_offsides` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_offsides` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_possession` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_possession` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_shots` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_shots` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `home_shots_on_goal` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `away_shots_on_goal` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `special_status` varchar(20) NOT NULL DEFAULT '',
  `game_status` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `coach_home` varchar(100) NOT NULL DEFAULT '',
  `coach_away` varchar(100) NOT NULL DEFAULT '',
  `referee` varchar(100) NOT NULL DEFAULT '',
  `match_events` longtext NOT NULL,
  `stats_home_club` text NOT NULL,
  `stats_away_club` text NOT NULL,
  `extra_info` longtext NOT NULL,
  PRIMARY KEY  (match_id),
  KEY `competition_id` (`competition_id`),
  KEY `main_stage_id` (`main_stage_id`),
  KEY `finished` (`finished`),
  KEY `game_status` (`game_status`),
  KEY `home_club` (`home_club`),
  KEY `away_club` (`away_club`),
  KEY `kickoff` (`kickoff`),
  KEY `stadium_id` (`stadium_id`),
  KEY `coach_home` (`coach_home`),
  KEY `coach_away` (`coach_away`),
  KEY `referee` (`referee`),
  KEY `group_id` (`group_id`)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_players (
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `player_id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `time_in` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `time_out` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `appearance` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `goals` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `goals_penalty` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `goals_own` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `goals_conceded` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `assist` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `card_y` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `card_yr` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `card_r` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`match_id`,`player_id`),
  KEY `player_id` (`player_id`),
  KEY `match_id` (`match_id`),
  KEY `club_id` (`club_id`)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_player_data (
  `player_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(250) NOT NULL DEFAULT '',
  `short_name` varchar(250) NOT NULL DEFAULT '',
  `full_name` varchar(250) NOT NULL DEFAULT '',
  `weight` varchar(50) NOT NULL DEFAULT '',
  `height` varchar(50) NOT NULL DEFAULT '',
  `position` varchar(10) NOT NULL DEFAULT '',
  `team_id` bigint(20) UNSIGNED NOT NULL,
  `national_team` bigint(20) UNSIGNED NOT NULL,
  `nationality` varchar(50) NOT NULL DEFAULT '',
  `nationality_extra` varchar(50) NOT NULL DEFAULT '',
  `place_of_birth` varchar(100) NOT NULL DEFAULT '',
  `country_of_birth` varchar(100) NOT NULL DEFAULT '',
  `date_of_birth` date NOT NULL default '0000-00-00',
  `date_of_death` date NOT NULL default '0000-00-00',
  `player_external_id` varchar(50) NOT NULL DEFAULT '',
  `photo` varchar(250) NOT NULL DEFAULT '',
  `photo_sm` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (player_id),
  KEY `position` (`position`),
  KEY `team_id` (`team_id`),
  KEY `national_team` (`national_team`),
  KEY `nationality` (`nationality`)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_missing_players (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `player_id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(20) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `match_id` (`match_id`),
  KEY `player_id` (`player_id`),
  KEY `club_id` (`club_id`)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_players_manual_stats (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED NOT NULL,
  `competition_id` bigint(20) UNSIGNED NOT NULL,
  `competition_text` varchar(200) NOT NULL DEFAULT '',
  `competition_type` varchar(10) NOT NULL DEFAULT '',
  `played` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `started` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `sub_in` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `minutes` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `card_y` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `card_yr` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `card_r` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `goals` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `goals_penalty` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `assists` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `own_goals` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `goals_conceded` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  `clean_sheets` smallint(8) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `player_id` (`player_id`),
  KEY `season_id` (`season_id`)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_lineups (
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `home_line_up` varchar(1500) DEFAULT '' NOT NULL,
  `away_line_up` varchar(1500) DEFAULT '' NOT NULL,
  `home_subs` varchar(1000) DEFAULT '' NOT NULL,
  `away_subs` varchar(1000) DEFAULT '' NOT NULL,
  `custom_numbers` text NOT NULL,
  `captain_home` varchar(100) NOT NULL DEFAULT '',
  `captain_away` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY  (`match_id`)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_clubs (
  club_id bigint(20) UNSIGNED NOT NULL,
  title varchar(200) NOT NULL DEFAULT '',
  post_name varchar(200) NOT NULL DEFAULT '',
  abbr varchar(50) NOT NULL DEFAULT '',
  city varchar(200) NOT NULL DEFAULT '',
  nationality varchar(10) NOT NULL DEFAULT '',
  stadium_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  is_national_team tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  club_duplicates tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  club_external_id varchar(50) NOT NULL DEFAULT '',
  subteams varchar(20) NOT NULL DEFAULT '',
  root_team bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  root_type varchar(20) NOT NULL DEFAULT '',
  logo varchar(500) NOT NULL DEFAULT '',
  logo_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  logo_big varchar(500) NOT NULL DEFAULT '',
  logo_big_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  main_color varchar(20) NOT NULL DEFAULT '',
  club_details longtext NOT NULL,
  club_social longtext NOT NULL,
  club_custom longtext NOT NULL,
  description longtext NOT NULL,
  custom_content_below longtext NOT NULL,
  squad text NOT NULL,
  squad_staff text NOT NULL,
  squad_seasons longtext NOT NULL,
  squad_staff_seasons longtext NOT NULL,
  squad_group tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY  (club_id),
  KEY nationality (nationality),
  KEY stadium_id (stadium_id),
  KEY is_national_team (is_national_team),
  KEY club_external_id (club_external_id),
  KEY root_team (root_team)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_competitions (
  `competition_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `league_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `league_text` varchar(200) NOT NULL DEFAULT '',
  `season_ids` varchar(200) NOT NULL DEFAULT '',
  `season_text` varchar(500) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT '',
  `format_robin` varchar(20) NOT NULL DEFAULT '',
  `format_knockout` varchar(20) NOT NULL DEFAULT '',
  `is_friendly` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `multistage` varchar(20) NOT NULL DEFAULT '',
  `multistage_main` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `stage_title` varchar(200) NOT NULL DEFAULT '',
  `stage_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `competition_order` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `logo` varchar(500) NOT NULL DEFAULT '',
  `logo_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `logo_big` varchar(500) NOT NULL DEFAULT '',
  `logo_big_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `tmpl_layout` varchar(20) NOT NULL DEFAULT '',
  `group_next_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `round_next_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `stage_groups` text NOT NULL,
  `stage_rounds` text NOT NULL,
  `custom_content_below` longtext NOT NULL,
  PRIMARY KEY  (`competition_id`),
  KEY `multistage_main` (`multistage_main`),
  KEY `competition_order` (`competition_order`),
  KEY `league_id` (`league_id`)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}anwpfl_standings (
  `standing_id` bigint(20) UNSIGNED NOT NULL,
  `competition_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `group_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `points_win` tinyint(3) NOT NULL DEFAULT '3',
  `points_draw` tinyint(3) NOT NULL DEFAULT '1',
  `points_loss` tinyint(3) NOT NULL DEFAULT '0',
  `ranking_rules` varchar(500) NOT NULL DEFAULT '',
  `manual_ordering` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `is_initial_data_active` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `last_recalc` datetime DEFAULT NULL,
  `last_round` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `table_notes` text NOT NULL,
  `table_colors` text NOT NULL,
  `points_initial` text NOT NULL,
  `table_initial` longtext NOT NULL,
  `table_main` longtext NOT NULL,
  PRIMARY KEY  (`standing_id`),
  KEY `competition_group` (`competition_id`, `group_id`)
) $charset_collate;
";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Skip dbDelta when every expected column + named index is already in
		// the live schema. Avoids cosmetic "Duplicate column/key name" warnings
		// on re-upgrade over an already-migrated database (e.g. clone from
		// production). Silent-failure catalog #29. Phase 4 of beta-5 cleanup.
		if ( ! self::dbdelta_schema_is_present( $sql ) ) {
			dbDelta( $sql );
		}

		$success = empty( $wpdb->last_error );

		// Layer 2: post-dbDelta verification. Some MySQL configs / dbDelta paths
		// don't surface CREATE TABLE failures via $wpdb->last_error, so check
		// information_schema directly. If any FL table is missing, do NOT bump
		// db_version - next page load will retry dbDelta. Silent-failure catalog #29.
		$tables_to_verify = apply_filters(
			'anwpfl/upgrade/tables_to_verify',
			[
				$wpdb->prefix . 'anwpfl_matches',
				$wpdb->prefix . 'anwpfl_players',
				$wpdb->prefix . 'anwpfl_player_data',
				$wpdb->prefix . 'anwpfl_missing_players',
				$wpdb->prefix . 'anwpfl_players_manual_stats',
				$wpdb->prefix . 'anwpfl_lineups',
				$wpdb->prefix . 'anwpfl_clubs',
				$wpdb->prefix . 'anwpfl_competitions',
				$wpdb->prefix . 'anwpfl_standings',
			]
		);

		$missing_tables = [];

		foreach ( $tables_to_verify as $table_to_verify ) {
			$table_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT 1 FROM information_schema.tables WHERE table_schema = %s AND table_name = %s LIMIT 1',
					DB_NAME,
					$table_to_verify
				)
			);

			if ( ! $table_exists ) {
				$missing_tables[] = $table_to_verify;
			}
		}

		if ( ! empty( $missing_tables ) ) {
			// Rate-limit log noise: emit at most once per hour per process.
			$log_throttle_key = 'anwpfl_dbdelta_verify_failure_logged';

			if ( ! get_transient( $log_throttle_key ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[FL] dbDelta did not create tables: ' . implode( ', ', $missing_tables ) );
				set_transient( $log_throttle_key, 1, HOUR_IN_SECONDS );
			}

			$success = false;
		}

		$saved_db_version = absint( get_option( 'anwpfl_db_version' ) );

		if ( $saved_db_version < AnWP_Football_Leagues::DB_VERSION && $success ) {
			update_option( 'anwpfl_db_version', AnWP_Football_Leagues::DB_VERSION, true );
		}

		// Trigger auto-detect for migration flags. On fresh installs (no CPT data) this
		// inline-sets anwpfl_{clubs,squad,competitions,standings}_migrated so admin notices
		// and gates don't fire pre-Toolbox-visit. No-op once flags are set.
		if ( $success ) {
			$this->get_toolbox_updater_tasks();
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

		// Remove columns from `anwpfl_players` table.
		$players_table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM information_schema.tables WHERE table_schema = %s AND table_name = %s LIMIT 1',
				DB_NAME,
				$wpdb->prefix . 'anwpfl_players'
			)
		);

		if ( $players_table_exists ) {

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

		// Replace competition_status with new game_status.
		$matches_table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM information_schema.tables WHERE table_schema = %s AND table_name = %s LIMIT 1',
				DB_NAME,
				$wpdb->prefix . 'anwpfl_matches'
			)
		);

		if ( $matches_table_exists ) {
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

			/*
			|--------------------------------------------------------------------
			| v0.18.0 - DB_VERSION 49 - cleanup duplicate missing_players rows
			| (FL-BUG-023: api_update_injuries inserted same player twice when
			| API response contained duplicate entries)
			|--------------------------------------------------------------------
			*/
			if ( $saved_db_version < 49 ) {
				$wpdb->query(
					"DELETE m1 FROM {$wpdb->prefix}anwpfl_missing_players m1
					INNER JOIN {$wpdb->prefix}anwpfl_missing_players m2
					WHERE m1.id > m2.id
						AND m1.match_id = m2.match_id
						AND m1.player_id = m2.player_id
						AND m1.club_id = m2.club_id
						AND m1.reason = m2.reason
						AND m1.comment = m2.comment"
				); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		return $success;
	}

	/**
	 * Check whether the live database already has every column and named index
	 * declared in $sql (a multi-table CREATE TABLE block).
	 *
	 * Used to short-circuit dbDelta() when re-installing/upgrading over an
	 * already-migrated database. Silences cosmetic "Duplicate column name" /
	 * "Duplicate key name" warnings emitted on some MySQL configurations
	 * (e.g. SpinupWP per silent-failure catalog #29) when dbDelta re-runs
	 * ALTER statements for columns/indexes that already exist.
	 *
	 * The expected manifest is parsed from $sql itself - the same string
	 * passed to dbDelta() - so it cannot drift from the actual schema.
	 *
	 * Used by both core and premium upgrade routines (public static so
	 * AnWPFL_Premium_Upgrade::update_db() can call it).
	 *
	 * Trade-off: compares column NAMES and index NAMES only, not column TYPES
	 * or DEFAULT values. If a future migration changes a column type via
	 * dbDelta (varchar(200) -> varchar(500)), callers must pass
	 * $force_dbdelta = true to bypass this gate.
	 *
	 * @param string $sql           The full multi-table CREATE TABLE SQL.
	 * @param bool   $force_dbdelta If true, gate always returns false (caller
	 *                              should run dbDelta normally).
	 *
	 * @return bool True if every expected column + named index is present in
	 *              the live database. False if anything is missing or the
	 *              manifest extraction failed (caller should run dbDelta).
	 */
	public static function dbdelta_schema_is_present( string $sql, bool $force_dbdelta = false ): bool {
		if ( $force_dbdelta ) {
			return false;
		}

		$expected = self::parse_create_table_manifest( $sql );

		if ( empty( $expected ) ) {
			return false;
		}

		// Sanity gate: every parsed table must declare >= 5 columns (FL tables
		// all have >= 6). Defends against a regex regression silently making
		// the gate always-skip-dbDelta. Silent Failure Catalog #31-style guard.
		foreach ( $expected as $table_manifest ) {
			if ( count( $table_manifest['columns'] ) < 5 ) {
				return false;
			}
		}

		global $wpdb;

		$table_names  = array_keys( $expected );
		$placeholders = implode( ', ', array_fill( 0, count( $table_names ), '%s' ) );
		$args         = array_merge( [ DB_NAME ], $table_names );

		// Explicit aliases: information_schema column names are uppercase on
		// MariaDB (TABLE_NAME, COLUMN_NAME) and mixed-case on MySQL. Aliases
		// normalize to lowercase keys in $wpdb->get_results( ARRAY_A ).
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$actual_columns = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT table_name AS tn, column_name AS cn FROM information_schema.columns WHERE table_schema = %s AND table_name IN ( $placeholders )",
				$args
			),
			ARRAY_A
		);

		$actual_indexes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT table_name AS tn, index_name AS idn FROM information_schema.statistics WHERE table_schema = %s AND table_name IN ( $placeholders )",
				$args
			),
			ARRAY_A
		);
		// phpcs:enable

		if ( null === $actual_columns || null === $actual_indexes ) {
			return false;
		}

		$actual = [];

		foreach ( $actual_columns as $row ) {
			$actual[ $row['tn'] ]['columns'][ strtolower( $row['cn'] ) ] = true;
		}

		foreach ( $actual_indexes as $row ) {
			$actual[ $row['tn'] ]['indexes'][ strtolower( $row['idn'] ) ] = true;
		}

		foreach ( $expected as $table_name => $table_manifest ) {
			if ( empty( $actual[ $table_name ]['columns'] ) ) {
				return false;
			}

			foreach ( $table_manifest['columns'] as $column_name ) {
				if ( ! isset( $actual[ $table_name ]['columns'][ $column_name ] ) ) {
					return false;
				}
			}

			foreach ( array_keys( $table_manifest['indexes'] ) as $index_name ) {
				if ( ! isset( $actual[ $table_name ]['indexes'][ $index_name ] ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Parse a multi-table CREATE TABLE SQL string into an expected-schema
	 * manifest: { table_name => [ columns => [lowercase names], indexes => [name => true] ] }.
	 *
	 * Handles mixed backtick / no-backtick column and index naming styles.
	 * PRIMARY KEY is recorded under the index name 'primary' (matching
	 * MySQL's information_schema convention). Composite indexes are recorded
	 * by name only.
	 *
	 * Returns empty array if no CREATE TABLE blocks are recognized.
	 *
	 * @param string $sql
	 *
	 * @return array
	 */
	private static function parse_create_table_manifest( string $sql ): array {
		$manifest = [];

		$statements = preg_split( '/;\s*[\r\n]/', $sql );

		if ( ! is_array( $statements ) ) {
			return [];
		}

		foreach ( $statements as $stmt ) {
			$stmt = trim( $stmt );

			if ( '' === $stmt ) {
				continue;
			}

			if ( ! preg_match( '/^CREATE\s+TABLE\s+`?(\w+)`?\s*\((.*)\)[^)]*$/is', $stmt, $m ) ) {
				continue;
			}

			$table_name = $m[1];
			$body       = $m[2];
			$columns    = [];
			$indexes    = [];

			$lines = preg_split( '/,\s*[\r\n]+/', $body );

			if ( ! is_array( $lines ) ) {
				continue;
			}

			foreach ( $lines as $line ) {
				$line = trim( $line );

				if ( '' === $line ) {
					continue;
				}

				if ( preg_match( '/^PRIMARY\s+KEY\s*\(/i', $line ) ) {
					$indexes['primary'] = true;
					continue;
				}

				if ( preg_match( '/^(?:UNIQUE\s+|FULLTEXT\s+|SPATIAL\s+)?(?:KEY|INDEX)\s+`?(\w+)`?\s*\(/i', $line, $idx ) ) {
					$indexes[ strtolower( $idx[1] ) ] = true;
					continue;
				}

				if ( preg_match( '/^`?(\w+)`?\s+\w/', $line, $col ) ) {
					$columns[] = strtolower( $col[1] );
				}
			}

			if ( ! empty( $columns ) ) {
				$manifest[ $table_name ] = [
					'columns' => $columns,
					'indexes' => $indexes,
				];
			}
		}

		return $manifest;
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
		| Migrate Club data to clubs table
		|--------------------------------------------------------------------
		*/
		$clubs_table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM information_schema.tables WHERE table_schema = %s AND table_name = %s LIMIT 1',
				DB_NAME,
				$wpdb->prefix . 'anwpfl_clubs'
			)
		);

		if ( $clubs_table_exists ) {
			$ids_to_migrate = $wpdb->get_col(
				"SELECT p.ID
				FROM $wpdb->posts p
				LEFT JOIN {$wpdb->prefix}anwpfl_clubs c ON p.ID = c.club_id
				WHERE p.post_type = 'anwp_club'
					AND p.post_status IN ('publish', 'draft', 'pending', 'private')
					AND c.club_id IS NULL"
			);

			if ( ! empty( $ids_to_migrate ) ) {
				$tasks[] = [
					'status'      => 'pending',
					'total'       => count( $ids_to_migrate ),
					'order'       => 30,
					'title'       => 'Migrate club data to "clubs" table',
					'slug'        => 'migrate_clubs_to_table',
					'description' => 'Copy club data from postmeta to the new clubs table.',
					'subtasks'    => array_values( $ids_to_migrate ),
				];
			} elseif ( ! get_option( 'anwpfl_clubs_migrated' ) ) {
				update_option( 'anwpfl_clubs_migrated', 1, true );
				anwp_fl()->cache->flush_all_cache();
			}
		}

		/*
		|--------------------------------------------------------------------
		| Migrate Squad/Staff data to clubs table columns
		|--------------------------------------------------------------------
		*/
		if ( get_option( 'anwpfl_clubs_migrated' ) && ! get_option( 'anwpfl_squad_migrated' ) ) {
			// Find clubs that have squad or staff postmeta but empty archive columns.
			// Uses archive columns (not active) because active squad can legitimately
			// stay empty when no season key matches during migration.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$squad_ids_to_migrate = $wpdb->get_col(
				"SELECT DISTINCT c.club_id
				FROM {$wpdb->prefix}anwpfl_clubs c
				INNER JOIN $wpdb->postmeta pm ON c.club_id = pm.post_id
					AND pm.meta_key IN ('_anwpfl_squad', '_anwpfl_staff')
				WHERE c.squad_seasons = ''
					AND c.squad_staff_seasons = ''
					AND pm.meta_value != ''"
			);

			if ( ! empty( $squad_ids_to_migrate ) ) {
				$tasks[] = [
					'status'      => 'pending',
					'total'       => count( $squad_ids_to_migrate ),
					'order'       => 35,
					'title'       => 'Migrate squad data to "clubs" table',
					'slug'        => 'migrate_squad_to_table',
					'description' => 'Copy squad/staff rosters from postmeta to clubs table columns.',
					'subtasks'    => array_values( $squad_ids_to_migrate ),
				];
			} else {
				update_option( 'anwpfl_squad_migrated', 1, true );
				anwp_fl()->cache->flush_all_cache();
			}
		}

		/*
		|--------------------------------------------------------------------
		| Migrate Player's Meta to player_data table
		|--------------------------------------------------------------------
		*/
		$player_data_table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM information_schema.tables WHERE table_schema = %s AND table_name = %s LIMIT 1',
				DB_NAME,
				$wpdb->prefix . 'anwpfl_player_data'
			)
		);

		if ( $player_data_table_exists ) {
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
		| Migrate Competition data to competitions table
		|--------------------------------------------------------------------
		*/
		$competitions_table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM information_schema.tables WHERE table_schema = %s AND table_name = %s LIMIT 1',
				DB_NAME,
				$wpdb->prefix . 'anwpfl_competitions'
			)
		);

		if ( $competitions_table_exists ) {
			$ids_to_migrate = $wpdb->get_col(
				"SELECT p.ID
				FROM $wpdb->posts p
				LEFT JOIN {$wpdb->prefix}anwpfl_competitions c ON p.ID = c.competition_id
				WHERE p.post_type = 'anwp_competition'
					AND p.post_status IN ('publish', 'stage_secondary', 'draft')
					AND c.competition_id IS NULL"
			);

			if ( ! empty( $ids_to_migrate ) ) {
				$tasks[] = [
					'status'      => 'pending',
					'total'       => count( $ids_to_migrate ),
					'order'       => 45,
					'title'       => 'Migrate competition data to "competitions" table',
					'slug'        => 'migrate_competitions_to_table',
					'description' => 'Copy competition data from postmeta to the new competitions table.',
					'subtasks'    => array_values( $ids_to_migrate ),
				];
			} elseif ( ! get_option( 'anwpfl_competitions_migrated' ) ) {
				update_option( 'anwpfl_competitions_migrated', 1, true );
				anwp_fl()->cache->flush_all_cache();
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
		| Migrate Standing data to standings table
		|--------------------------------------------------------------------
		*/
		$standings_table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM information_schema.tables WHERE table_schema = %s AND table_name = %s LIMIT 1',
				DB_NAME,
				$wpdb->prefix . 'anwpfl_standings'
			)
		);

		if ( ! get_option( 'anwpfl_standings_migrated' ) && $standings_table_exists ) {

			/*
			 * Detect via `_anwpfl_fixed` postmeta, NOT via LEFT JOIN on the table.
			 *
			 * Reason: calculate_standing() (Phase 2) runs on match save and writes
			 * only competition_id/group_id/table_main/last_recalc/last_round. Every
			 * other column stays at MySQL column defaults. A naive LEFT JOIN IS NULL
			 * skips these partial rows and the Phase 4 read switch would then serve
			 * defaults for points_win / ranking_rules / table_notes / etc.
			 *
			 * Instead, queue every fixed+published standing and let the upsert run
			 * once. The upsert is idempotent - already-complete rows get a no-op
			 * UPDATE via ON DUPLICATE KEY UPDATE, partial rows get their config
			 * columns filled in, missing rows get created. After the task drains,
			 * the anwpfl_standings_migrated flag short-circuits this block so the
			 * task never re-queues.
			 *
			 * post_status is restricted to 'publish' to match get_standings() at
			 * class-anwpfl-standing.php:2159. Draft clones without _anwpfl_fixed
			 * postmeta would be skipped by the helper anyway (returns null when
			 * $require_fixed=true), and including draft would cause the task to
			 * loop indefinitely on those un-fixed drafts.
			 */
			$ids_to_migrate = $wpdb->get_col(
				"SELECT pm.post_id
				FROM $wpdb->postmeta pm
				INNER JOIN $wpdb->posts p ON p.ID = pm.post_id
				WHERE pm.meta_key = '_anwpfl_fixed'
					AND pm.meta_value = 'true'
					AND p.post_type = 'anwp_standing'
					AND p.post_status = 'publish'"
			);

			if ( ! empty( $ids_to_migrate ) ) {
				$tasks[] = [
					'status'      => 'pending',
					'total'       => count( $ids_to_migrate ),
					'order'       => 55,
					'title'       => 'Migrate standings to "standings" table',
					'slug'        => 'migrate_standings_to_table',
					'description' => 'Copy standing config from postmeta to the new standings table.',
					'subtasks'    => array_values( $ids_to_migrate ),
				];
			} else {
				update_option( 'anwpfl_standings_migrated', 1, true );
				anwp_fl()->cache->flush_all_cache();
			}
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
