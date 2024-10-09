<?php
/**
 * AnWP Football Leagues :: Competition Admin.
 *
 * @package AnWP_Football_Leagues
 */

class AnWPFL_Competition_Admin {

	/**
	 * @var AnWP_Football_Leagues
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @param AnWP_Football_Leagues $plugin Main plugin object.
	 *
	 * @since  0.1.0
	 */
	public function __construct( AnWP_Football_Leagues $plugin ) {
		$this->plugin = $plugin;

		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {
		add_action( 'add_meta_boxes_anwp_competition', [ $this, 'remove_term_metaboxes' ] );

		add_action( 'load-post.php', [ $this, 'init_metaboxes' ] );
		add_action( 'load-post-new.php', [ $this, 'init_metaboxes' ] );
		add_action( 'save_post_anwp_competition', [ $this, 'save_metabox' ], 10, 2 );

		// Admin Table filters
		add_filter( 'disable_months_dropdown', [ $this, 'disable_months_dropdown' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ $this, 'add_more_filters' ] );
		add_filter( 'pre_get_posts', [ $this, 'handle_custom_filter' ] );
	}

	/**
	 * Filters whether to remove the 'Months' drop-down from the post list table.
	 *
	 * @param bool   $disable   Whether to disable the drop-down. Default false.
	 * @param string $post_type The post type.
	 *
	 * @return bool
	 */
	public function disable_months_dropdown( bool $disable, string $post_type ): bool {

		return 'anwp_competition' === $post_type ? true : $disable;
	}


	/**
	 * Fires before the Filter button on the Posts and Pages list tables.
	 *
	 * The Filter button allows sorting by date and/or category on the
	 * Posts list table, and sorting by date on the Pages list table.
	 *
	 * @param string $post_type The post type slug.
	 */
	public function add_more_filters( string $post_type ) {

		if ( 'anwp_competition' === $post_type ) {

			ob_start();

			/*
			|--------------------------------------------------------------------
			| Filter By League
			|--------------------------------------------------------------------
			*/
			$leagues = get_terms(
				[
					'taxonomy'   => 'anwp_league',
					'hide_empty' => false,
				]
			);

			if ( ! is_wp_error( $leagues ) && ! empty( $leagues ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification
				$current_league_filter = empty( $_GET['_anwpfl_current_league'] ) ? '' : (int) $_GET['_anwpfl_current_league'];
				?>

				<select name='_anwpfl_current_league' id='anwp_league_filter' class='postform'>
					<option value=''><?php echo esc_html__( 'All Leagues', 'anwp-football-leagues' ); ?></option>
					<?php foreach ( $leagues as $league ) : ?>
						<option value="<?php echo esc_attr( $league->term_id ); ?>" <?php selected( $league->term_id, $current_league_filter ); ?>>
							- <?php echo esc_html( $league->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php
			}

			/*
			|--------------------------------------------------------------------
			| Filter By Season
			|--------------------------------------------------------------------
			*/
			$seasons = get_terms(
				[
					'taxonomy'   => 'anwp_season',
					'hide_empty' => false,
				]
			);

			if ( ! is_wp_error( $seasons ) && ! empty( $seasons ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification
				$current_season_filter = empty( $_GET['_anwpfl_current_season'] ) ? '' : (int) $_GET['_anwpfl_current_season'];
				?>

				<select name='_anwpfl_current_season' id='anwp_season_filter' class='postform'>
					<option value=''><?php echo esc_html__( 'All Seasons', 'anwp-football-leagues' ); ?></option>
					<?php foreach ( $seasons as $season ) : ?>
						<option value="<?php echo esc_attr( $season->term_id ); ?>" <?php selected( $season->term_id, $current_season_filter ); ?>>
							- <?php echo esc_html( $season->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo ob_get_clean();
		}
	}

	/**
	 * Handle custom filter.
	 *
	 * @param WP_Query $query
	 */
	public function handle_custom_filter( WP_Query $query ) {
		global $post_type, $pagenow;

		// Check main query in admin
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'edit.php' !== $pagenow || 'anwp_competition' !== $post_type ) {
			return;
		}

		$tax_query = [];

		/*
		|--------------------------------------------------------------------
		| Filter By Season
		|--------------------------------------------------------------------
		*/
		// phpcs:ignore WordPress.Security.NonceVerification
		$filter_by_season = empty( $_GET['_anwpfl_current_season'] ) ? '' : intval( $_GET['_anwpfl_current_season'] );

		if ( $filter_by_season ) {
			$tax_query[] =
				[
					'taxonomy' => 'anwp_season',
					'terms'    => $filter_by_season,
				];
		}

		/*
		|--------------------------------------------------------------------
		| Filter By League
		|--------------------------------------------------------------------
		*/
		// phpcs:ignore WordPress.Security.NonceVerification
		$filter_by_league = empty( $_GET['_anwpfl_current_league'] ) ? '' : intval( $_GET['_anwpfl_current_league'] );

		if ( $filter_by_league ) {
			$tax_query[] =
				[
					'taxonomy' => 'anwp_league',
					'terms'    => $filter_by_league,
				];
		}

		/*
		|--------------------------------------------------------------------
		| Join All values to main query
		|--------------------------------------------------------------------
		*/
		if ( ! empty( $tax_query ) ) {
			$query->set(
				'tax_query',
				[
					array_merge( [ 'relation' => 'and' ], $tax_query ),
				]
			);
		}
	}

	/**
	 * Meta box initialization.
	 *
	 * @since  0.2.0 (2017-12-07)
	 */
	public function init_metaboxes() {
		add_action(
			'add_meta_boxes',
			function ( string $post_type, WP_Post $post ) {

				if ( 'anwp_competition' === $post_type && 'stage_secondary' === $post->post_status && absint( get_post_meta( $post->ID, '_anwpfl_multistage_main', true ) ) ) {
					if ( wp_redirect( admin_url( 'post.php?post=' . intval( get_post_meta( $post->ID, '_anwpfl_multistage_main', true ) ) . '&action=edit' ) ) ) { // phpcs:ignore
						exit;
					}
				} elseif ( 'anwp_competition' === $post_type ) {
					add_meta_box(
						'anwpfl_competition_stage',
						esc_html__( 'Competition Stages', 'anwp-football-leagues' ),
						[ $this, 'render_metabox' ],
						$post_type,
						'normal',
						'high'
					);

					add_meta_box(
						'anwp_competition_tutorials_metabox',
						esc_html__( 'Related Tutorials', 'anwp-football-leagues' ),
						[ $this, 'render_tutorials_metabox' ],
						$post_type,
						'side',
						'low'
					);
				}
			},
			10,
			2
		);
	}

	/**
	 * Render Meta Box content for Competition Stages.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @since  0.2.0 (2017-10-28)
	 */
	public function render_metabox( WP_Post $post ) {

		global $wpdb;
		global $pagenow;

		$app_id  = apply_filters( 'anwpfl/competition/vue_app_id', 'fl-app-tournament' );
		$post_id = get_the_ID();

		// League & Season
		$league_arr = wp_get_post_terms( $post_id, 'anwp_league', [ 'fields' => 'ids' ] );
		$season_arr = wp_get_post_terms( $post_id, 'anwp_season', [ 'fields' => 'ids' ] );

		$countries_options = [];

		foreach ( $this->plugin->data->cb_get_countries() as $country_code => $country_name ) { // todo move to Data
			$countries_options[] = [
				'label' => $country_name,
				'code'  => $country_code,
			];
		}

		// Get single stage games
		$games_qty = $wpdb->get_var(
			$wpdb->prepare(
				"
					SELECT COUNT(*) as qty
					FROM $wpdb->anwpfl_matches
					WHERE main_stage_id = %d
					",
				$post_id
			)
		);

		/*
		|--------------------------------------------------------------------
		| Root Stage
		|--------------------------------------------------------------------
		*/
		$rounds = json_decode( get_post_meta( $post_id, '_anwpfl_rounds', true ) ) ?: [];
		$groups = json_decode( get_post_meta( $post_id, '_anwpfl_groups', true ) ) ?: [];

		if ( empty( $rounds ) ) {
			$rounds = [ (object) [ 'id' => 1, 'title' => '' ] ]; // phpcs:ignore
		}

		if ( empty( $groups ) ) {
			$groups = [
				(object) [
					'id'    => 1,
					'title' => '',
					'round' => 1,
					'clubs' => [],
				],
			]; // phpcs:ignore
		}

		foreach ( $rounds as $round ) {
			$round->groups = [];

			foreach ( $groups as $group ) {
				if ( absint( $group->round ) === absint( $round->id ) || ( empty( $group->round ) && 1 === absint( $round->id ) ) ) {
					$round->groups[] = $group;
				}
			}
		}

		$root_stage = [
			'root'                   => true,
			'stageId'                => absint( $post_id ),
			'rounds'                 => $rounds ?: [],
			'type'                   => get_post_meta( $post_id, '_anwpfl_type', true ) ?: 'round-robin',
			'formatRobin'            => get_post_meta( $post_id, '_anwpfl_format_robin', true ) ?: 'double',
			'formatKnockout'         => get_post_meta( $post_id, '_anwpfl_format_knockout', true ) ?: 'two',
			'stageOrder'             => (int) get_post_meta( $post_id, '_anwpfl_stage_order', true ),
			'stageTitle'             => get_post_meta( $post_id, '_anwpfl_stage_title', true ) ?: ( 'post-new.php' === $pagenow ? esc_html__( 'Regular Season', 'anwp-football-leagues' ) : '' ),
			'competitionStatus'      => get_post_meta( $post_id, '_anwpfl_competition_status', true ),
			'competitionStatusSaved' => get_post_meta( $post_id, '_anwpfl_competition_status', true ),
			'nextIdGroup'            => absint( get_post_meta( $post_id, '_anwpfl_group_next_id', true ) ) ?: 2,
			'nextIdRound'            => absint( get_post_meta( $post_id, '_anwpfl_round_next_id', true ) ) ?: 2,
		];

		$stages    = [ apply_filters( 'anwpfl/competition/stage-admin-app-data', $root_stage ) ];
		$stage_ids = [ absint( $post_id ) ];

		/*
		|--------------------------------------------------------------------
		| Secondary Stages
		|--------------------------------------------------------------------
		*/
		if ( 'main' === get_post_meta( $post_id, '_anwpfl_multistage', true ) ) {
			$args = [
				'post_type'        => 'anwp_competition',
				'posts_per_page'   => - 1,
				'suppress_filters' => false,
				'post_status'      => [ 'publish', 'stage_secondary' ],
				'meta_key'         => '_anwpfl_multistage_main',
				'meta_value'       => (int) $post_id,
			];

			$stages_query = new WP_Query( $args );

			if ( $stages_query->have_posts() ) {

				/** @var $p WP_Post */
				foreach ( $stages_query->get_posts() as $p ) {

					$stage_rounds = json_decode( get_post_meta( $p->ID, '_anwpfl_rounds', true ) ) ?: [];
					$stage_groups = json_decode( get_post_meta( $p->ID, '_anwpfl_groups', true ) ) ?: [];

					if ( empty( $stage_rounds ) ) {
						$stage_rounds = [ (object) [ 'id' => 1, 'title' => '' ] ]; // phpcs:ignore
					}

					foreach ( $stage_rounds as $stage_round ) {
						$stage_round->groups = [];

						foreach ( $stage_groups as $stage_group ) {
							if ( absint( $stage_group->round ) === absint( $stage_round->id ) || ( empty( $stage_group->round ) && 1 === absint( $stage_round->id ) ) ) {
								$stage_round->groups[] = $stage_group;
							}
						}
					}

					$stages[] = apply_filters(
						'anwpfl/competition/stage-admin-app-data',
						[
							'root'                   => false,
							'stageId'                => absint( $p->ID ),
							'rounds'                 => $stage_rounds ?: [],
							'type'                   => get_post_meta( $p->ID, '_anwpfl_type', true ),
							'formatRobin'            => get_post_meta( $p->ID, '_anwpfl_format_robin', true ),
							'formatKnockout'         => get_post_meta( $p->ID, '_anwpfl_format_knockout', true ),
							'stageOrder'             => absint( get_post_meta( $p->ID, '_anwpfl_stage_order', true ) ),
							'stageTitle'             => get_post_meta( $p->ID, '_anwpfl_stage_title', true ),
							'competitionStatus'      => get_post_meta( $p->ID, '_anwpfl_competition_status', true ),
							'competitionStatusSaved' => get_post_meta( $p->ID, '_anwpfl_competition_status', true ),
							'nextIdGroup'            => absint( get_post_meta( $p->ID, '_anwpfl_group_next_id', true ) ),
							'nextIdRound'            => absint( get_post_meta( $p->ID, '_anwpfl_round_next_id', true ) ),
						]
					);

					$stage_ids[] = absint( $p->ID );
				}
			}
		}

		$stages = wp_list_sort( $stages, 'stageOrder' );

		/*
		|--------------------------------------------------------------------
		| Teams
		|--------------------------------------------------------------------
		*/
		$teams = [];

		foreach ( $this->plugin->club->get_clubs_list() as $team ) {
			$teams[ $team->id ] = [
				'id'      => $team->id,
				'title'   => $team->title,
				'logo'    => $team->logo,
				'country' => anwp_fl()->club->get_team_country( $team->id ),
			];
		}

		/*
		|--------------------------------------------------------------------
		| App Data
		|--------------------------------------------------------------------
		*/
		$app_data = [
			'logo_id'          => get_post_meta( $post_id, '_anwpfl_logo_id', true ),
			'logo'             => get_post_meta( $post_id, '_anwpfl_logo', true ),
			'leaguesList'      => $this->plugin->league->get_leagues_list(),
			'seasonsList'      => $this->plugin->season->get_seasons_list(),
			'competitionOrder' => (int) get_post_meta( $post_id, '_anwpfl_competition_order', true ),
			'clubsList'        => $teams,
			'countriesList'    => $countries_options,
			'leagueId'         => empty( $league_arr ) || empty( $league_arr[0] ) ? '' : intval( $league_arr[0] ),
			'seasonIds'        => empty( $season_arr ) ? '' : implode( ',', $season_arr ),
			'gamesQty'         => $games_qty ?: 0,
			'rest_root'        => esc_url_raw( rest_url() ),
			'rest_nonce'       => wp_create_nonce( 'wp_rest' ),
			'spinner_url'      => admin_url( 'images/spinner.gif' ),
			'stages'           => $stages,
			'stageGames'       => $this->get_stage_games( $stage_ids ),
		];

		$l10n = anwp_fl()->helper->recursive_entity_decode( anwp_fl()->data->get_l10n_admin() );

		// Add nonce for security and authentication.
		wp_nonce_field( 'anwp_save_metabox_' . $post->ID, 'anwp_metabox_nonce' );
		?>
		<script type="text/javascript">
			window._flTournament = <?php echo wp_json_encode( $app_data ); ?>;
			window._flTournamentL10n = <?php echo wp_json_encode( $l10n ); ?>;
		</script>
		<div class="anwp-b-wrap anwpfl-competition_stage-metabox-wrapper">
			<div id="<?php echo esc_attr( $app_id ); ?>"></div>
			<div class="anwp-publish-click-proxy-wrapper mt-3">
				<input class="button button-primary button-large mt-0 px-5" id="anwp-publish-click-proxy" type="button"
					value="<?php esc_html_e( 'Save', 'anwp-football-leagues' ); ?>">
				<span class="spinner mt-2"></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Get stage games grouped by status
	 *
	 * @param array $stage_ids
	 *
	 * @return array
	 */
	public function get_stage_games( array $stage_ids ): array {

		global $wpdb;

		$stage_ids = wp_parse_id_list( $stage_ids );
		$output    = [];

		foreach ( $stage_ids as $stage_id ) {
			$games = $wpdb->get_row(
				$wpdb->prepare(
					"
					SELECT SUM( CASE WHEN game_status = 1 THEN 1 ELSE 0 END ) as official, SUM( CASE WHEN ( game_status = 0 OR game_status = 2 ) THEN 1 ELSE 0 END ) as friendly
					FROM $wpdb->anwpfl_matches
					WHERE competition_id = %d
					GROUP BY competition_id
					",
					$stage_id
				),
				ARRAY_A
			) ?: [];

			$output[ $stage_id ] = [
				'official' => absint( $games['official'] ?? 0 ),
				'friendly' => absint( $games['friendly'] ?? 0 ),
			];
		}

		return $output;
	}

	/**
	 * Render the metabox to list related tutorials.
	 *
	 * @since 0.10.10
	 */
	public function render_tutorials_metabox() {

		ob_start();

		/**
		 * Fires at the beginning of tutorial metabox (admin - side).
		 *
		 * @since 0.10.10
		 */
		do_action( 'anwpfl/competition/before_tutorial_metabox' );
		?>
		<p>
			<span class="dashicons dashicons-book-alt"></span>
			<a href="https://anwppro.userecho.com/knowledge-bases/2/articles/236-how-to-create-round-robin-competition-like-regular-season" target="_blank">
				<?php echo esc_html__( 'How to Create Round-Robin Competition', 'anwp-football-leagues' ); ?>
			</a>
		</p>
		<p>
			<span class="dashicons dashicons-book-alt"></span>
			<a href="https://anwppro.userecho.com/knowledge-bases/2/articles/237-how-to-create-knockout-competition-like-national-cups" target="_blank">
				<?php echo esc_html__( 'How to Create Knockout Competition', 'anwp-football-leagues' ); ?>
			</a>
		</p>
		<p>
			<span class="dashicons dashicons-book-alt"></span>
			<a href="https://anwppro.userecho.com/knowledge-bases/2/articles/71-how-to-create-competition-with-multiple-stages-v1" target="_blank">
				<?php echo esc_html__( 'How to Create Multistage Competition', 'anwp-football-leagues' ); ?>
			</a>
		</p>
		<?php
		/**
		 * Fires at the end of tutorial metabox (admin - side).
		 *
		 * @since 0.10.10
		 */
		do_action( 'anwpfl/competition/after_tutorial_metabox' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo ob_get_clean();
	}

	/**
	 * Remove term metaboxes.
	 *
	 * @since 0.10.0
	 */
	public function remove_term_metaboxes() {
		remove_meta_box( 'tagsdiv-anwp_league', 'anwp_competition', 'side' );
		remove_meta_box( 'tagsdiv-anwp_season', 'anwp_competition', 'side' );
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int     $post_id The ID of the post being saved.
	 * @param WP_Post $post    Post object.
	 *
	 * @return int
	 */
	public function save_metabox( int $post_id, WP_Post $post ): int {

		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['anwp_metabox_nonce'] ) ) {
			return $post_id;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['anwp_metabox_nonce'], 'anwp_save_metabox_' . $post_id ) ) {
			return $post_id;
		}

		// Check post type
		if ( 'anwp_competition' !== $_POST['post_type'] ) {
			return $post_id;
		}

		/*
		 * If this is an autosave, our form has not been submitted,
		 * so we don't want to do anything.
		 */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// check if there was a multisite switch before
		if ( is_multisite() && ms_is_switched() ) {
			return $post_id;
		}

		if ( defined( 'ANWPFL_SAVING_COMPETITION' ) && ANWPFL_SAVING_COMPETITION ) {
			return $post_id;
		}

		if ( 'stage_secondary' === $post->post_status ) {
			return $post_id;
		}

		/* OK, it's safe for us to save the data now. */

		/** ---------------------------------------
		 * Save Competition
		 * ---------------------------------------*/

		define( 'ANWPFL_SAVING_COMPETITION', true );

		$post_data        = wp_unslash( $_POST );
		$competition_prev = anwp_fl()->competition->get_competition_data( $post_id );

		/*
		|--------------------------------------------------------------------
		| League & Seasons
		|--------------------------------------------------------------------
		*/
		$league_id = absint( $post_data['_anwpfl_league_id'] ?? 0 );

		if ( $league_id && absint( $competition_prev['league_id'] ?? '' ) !== $league_id ) {
			wp_set_object_terms( $post_id, $league_id, 'anwp_league' );
		}

		$season_ids = wp_parse_id_list( $post_data['_anwpfl_season_ids'] ?? '' );

		if ( ! empty( $season_ids ) && wp_parse_id_list( $competition_prev['season_ids'] ?? '' ) !== $season_ids ) {
			wp_set_object_terms( $post_id, $season_ids, 'anwp_season' );
		}

		/*
		|--------------------------------------------------------------------
		| Logo
		|--------------------------------------------------------------------
		*/
		if ( absint( $post_data['_anwpfl_logo_id'] ?? '' ) ) {
			update_post_meta( $post_id, '_anwpfl_logo', sanitize_text_field( $post_data['_anwpfl_logo'] ?? '' ) );
			update_post_meta( $post_id, '_anwpfl_logo_id', sanitize_text_field( $post_data['_anwpfl_logo_id'] ?? '' ) );
		} elseif ( $competition_prev['logo'] ?? '' ) {
			delete_post_meta( $post_id, '_anwpfl_logo' );
			delete_post_meta( $post_id, '_anwpfl_logo_id' );
		}

		/*
		|--------------------------------------------------------------------
		| Save Stages
		|--------------------------------------------------------------------
		*/
		$stages_data = json_decode( $post_data['_fl_stages_data'] );
		$stages_add  = $post_data['_fl_stages_added'] ? explode( '|', $post_data['_fl_stages_added'] ) : [];
		$stages_del  = $post_data['_fl_stages_removed'] ? explode( '|', $post_data['_fl_stages_removed'] ) : [];

		// Remove
		if ( ! empty( $stages_del ) && is_array( $stages_del ) ) {
			foreach ( $stages_del as $stage_to_delete ) {
				if ( ! absint( $stage_to_delete ) || 'secondary' !== get_post_meta( $stage_to_delete, '_anwpfl_multistage', true ) ) {
					continue;
				}

				if ( ! empty( wp_list_filter( $stages_data, [ 'stageId' => $stage_to_delete ] ) ) ) {
					continue;
				}

				$post_to_delete = get_post( $stage_to_delete );

				if ( ( $post_to_delete instanceof WP_Post ) && 'anwp_competition' === $post_to_delete->post_type ) {
					wp_delete_post( $post_to_delete->ID, true );
				}
			}
		}

		$order_num = 1;

		foreach ( $stages_data as $stage_data ) {
			$stage_data->order = $order_num++;
		}

		foreach ( $stages_data as $stage_data_obj ) {

			$is_stage_new = false;

			$stage_data = wp_parse_args(
				$stage_data_obj,
				[
					'stageId' => '',
					'order'   => 0,
					'root'    => false,
					'rounds'  => [],
				]
			);

			if ( empty( $stage_data['stageId'] ) ) {
				continue;
			}

			$new_stage_title = '';

			if ( $league_id ) {
				$new_stage_title = sanitize_text_field( get_term( $league_id )->name );

				if ( ! empty( $season_ids ) && absint( $season_ids[0] ) ) {
					$new_stage_title .= ' ' . sanitize_text_field( get_term( $season_ids[0] )->name );
				}
			}

			$stage_id = '';

			if ( in_array( $stage_data['stageId'], $stages_add, true ) ) {

				// Add New Stage
				$stage_id = wp_insert_post(
					[
						'post_type'   => 'anwp_competition',
						'post_title'  => sanitize_text_field( $new_stage_title . ' - ' . $stage_data['stageTitle'] ),
						'post_status' => 'stage_secondary',
						'menu_order'  => $stage_data['order'],
					]
				);

				$is_stage_new = true;
			} elseif ( absint( $stage_data['stageId'] ) ) {
				$stage_id = absint( $stage_data['stageId'] );
			}

			if ( empty( $stage_id ) ) {
				continue;
			}

			// Set default title
			if ( $stage_data['root'] && empty( $post_data['post_title'] ) && 'publish' === $post->post_status && $new_stage_title ) {
				wp_update_post(
					[
						'ID'         => $post_id,
						'post_title' => $new_stage_title,
					]
				);
			} elseif ( ! $stage_data['root'] && $new_stage_title && ! $is_stage_new ) {
				$stage_post_title = sanitize_text_field( $new_stage_title . ' - ' . $stage_data['stageTitle'] );

				if ( get_post( $stage_id )->post_title !== $stage_post_title ) {
					wp_update_post(
						[
							'ID'         => $stage_id,
							'post_title' => $stage_post_title,
						]
					);
				}
			}

			// Update Season and Leagues
			if ( ! $stage_data['root'] ) {
				if ( $league_id ) {
					wp_set_object_terms( $stage_id, $league_id, 'anwp_league' );
				}

				if ( ! empty( $season_ids ) ) {
					wp_set_object_terms( $stage_id, $season_ids, 'anwp_season' );
				}
			}

			// Groups and rounds
			$rounds = [];
			$groups = [];

			foreach ( $stage_data['rounds'] as $round_data ) {
				$rounds[] = [
					'id'    => $round_data->id,
					'title' => $round_data->title,
				];

				$groups = array_merge( $groups, $round_data->groups );
			}

			update_post_meta( $stage_id, '_anwpfl_rounds', wp_slash( wp_json_encode( $rounds ) ) );
			update_post_meta( $stage_id, '_anwpfl_groups', wp_slash( wp_json_encode( $groups ) ) );

			// General Data
			update_post_meta( $stage_id, '_anwpfl_type', sanitize_key( $stage_data['type'] ?? '' ) );
			update_post_meta( $stage_id, '_anwpfl_format_robin', sanitize_key( $stage_data['formatRobin'] ?? '' ) );
			update_post_meta( $stage_id, '_anwpfl_format_knockout', sanitize_key( $stage_data['formatKnockout'] ?? '' ) );
			update_post_meta( $stage_id, '_anwpfl_competition_status', sanitize_key( $stage_data['competitionStatus'] ?? '' ) );
			update_post_meta( $stage_id, '_anwpfl_stage_title', sanitize_text_field( $stage_data['stageTitle'] ?? '' ) );
			update_post_meta( $stage_id, '_anwpfl_stage_order', absint( $stage_data['order'] ?? 0 ) );
			update_post_meta( $stage_id, '_anwpfl_group_next_id', absint( $stage_data['nextIdGroup'] ?? 0 ) );
			update_post_meta( $stage_id, '_anwpfl_round_next_id', absint( $stage_data['nextIdRound'] ?? 0 ) );

			// Multistage
			update_post_meta( $stage_id, '_anwpfl_multistage', $stage_data['root'] ? ( count( $stages_data ) > 1 ? 'main' : '' ) : 'secondary' ); // (empty)|main|secondary
			update_post_meta( $stage_id, '_anwpfl_multistage_main', $stage_data['root'] ? '' : $post_id );

			/**
			 * Fires after competition stage save.
			 *
			 * @param int   $stage_id
			 * @param array $stage_data
			 * @param array $post_data
			 *
			 * @since 0.16.7
			 */
			do_action( 'anwpfl/competition-stage/after_save', $stage_id, $stage_data, $post_data );
		}

		/**
		 * Fires after competition save.
		 *
		 * @param WP_Post $post
		 * @param array   $stages_data
		 * @param array   $post_data
		 *
		 * @since 0.16.7
		 */
		do_action( 'anwpfl/competition-stages/after_save', $post, $stages_data, $post_data );

		return $post_id;
	}
}
