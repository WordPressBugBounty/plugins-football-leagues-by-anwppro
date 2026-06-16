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
			// phpcs:ignore WordPress.Security.NonceVerification
			$current_league_filter = empty( $_GET['_anwpfl_current_league'] ) ? '' : (int) $_GET['_anwpfl_current_league'];
			?>
			<div class="anwp-x-selector anwp-g-float-left" fl-x-data="selectorItem('league',true)" fl-x-cloak>
				<input fl-x-model.fill="selected" type="text" class="postform anwp-g-admin-list-input anwp-w-120"
					placeholder="<?php echo esc_attr__( 'League ID', 'anwp-football-leagues' ); ?>"
					name="_anwpfl_current_league" value="<?php echo esc_attr( $current_league_filter ); ?>"/>
				<button fl-x-on:click="openModal()" type="button" class="button fl-btn-icon anwp-mr-2 postform">
					<span class="dashicons dashicons-search"></span>
				</button>
			</div>

			<?php
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

				if ( 'anwp_competition' === $post_type && 'stage_secondary' === $post->post_status ) {
					$multistage_main_id = absint( anwp_fl()->competition->get_competition_list_row( (int) $post->ID )['multistage_main'] ?? 0 );

					if ( $multistage_main_id && wp_redirect( admin_url( 'post.php?post=' . $multistage_main_id . '&action=edit' ) ) ) { // phpcs:ignore
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

					add_meta_box(
						'anwp_competition_content_below',
						esc_html__( 'Bottom Content', 'anwp-football-leagues' ),
						[ $this, 'render_content_below_metabox' ],
						$post_type,
						'normal',
						'low'
					);

					add_meta_box(
						'anwp_competition_logo_big',
						esc_html__( 'Detailed Logo', 'anwp-football-leagues' ),
						[ $this, 'render_logo_big_metabox' ],
						$post_type,
						'side',
						'default'
					);

					if ( ! function_exists( 'anwp_fl_pro' ) ) {
						add_meta_box(
							'anwp_competition_layout',
							esc_html__( 'Layout', 'anwp-football-leagues' ),
							[ $this, 'render_layout_metabox' ],
							$post_type,
							'side',
							'default'
						);
					}
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

		// Block render until competitions migration is complete (0.18.0).
		// save_metabox() also silently no-ops in this state, so the editable form is misleading.
		if ( ! get_option( 'anwpfl_competitions_migrated' ) ) {
			AnWP_Football_Leagues::print_migration_required_panel(
				__( 'Competitions Data Migration Required', 'anwp-football-leagues' )
			);
			return;
		}

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
		| Pre-warm row_cache for main stage + secondaries
		|
		| get_secondary_competitions_list() runs a single SELECT * for all
		| secondaries and pre-warms row_cache + list_cache for every stage.
		| It also explicitly calls get_row($post_id) for the main stage, so
		| subsequent get_row() calls below are free cache hits.
		|
		| Returns SECONDARIES ONLY (plan claim verified 2026-04-08 against
		| class-anwpfl-competition.php:820 SELECT ... WHERE multistage_main).
		|--------------------------------------------------------------------
		*/
		$multi_stages = anwp_fl()->competition->get_secondary_competitions_list( (int) $post_id );

		/*
		|--------------------------------------------------------------------
		| Root Stage
		|--------------------------------------------------------------------
		*/
		$root_row = anwp_fl()->competition->get_row( (int) $post_id ) ?: [];

		$rounds = json_decode( (string) ( $root_row['stage_rounds'] ?? '' ) ) ?: [];
		$groups = json_decode( (string) ( $root_row['stage_groups'] ?? '' ) ) ?: [];

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

		// IMPORTANT: use `?:` (not `??`) for defaults — get_row() returns
		// empty strings for unset columns, not null. `??` would skip the default.
		$root_stage = [
			'root'                   => true,
			'stageId'                => absint( $post_id ),
			'rounds'                 => $rounds ?: [],
			'type'                   => ( $root_row['type'] ?? '' ) ?: 'round-robin',
			'formatRobin'            => ( $root_row['format_robin'] ?? '' ) ?: 'double',
			'formatKnockout'         => ( $root_row['format_knockout'] ?? '' ) ?: 'two',
			'stageOrder'             => absint( $root_row['stage_order'] ?? 0 ),
			'stageTitle'             => ( $root_row['stage_title'] ?? '' ) ?: ( 'post-new.php' === $pagenow ? esc_html__( 'Regular Season', 'anwp-football-leagues' ) : '' ),
			'isFriendly'             => ! empty( $root_row['is_friendly'] ) ? 1 : 0,
			'isFriendlySaved'        => ! empty( $root_row['is_friendly'] ) ? 1 : 0,
			'nextIdGroup'            => absint( $root_row['group_next_id'] ?? 0 ) ?: 2,
			'nextIdRound'            => absint( $root_row['round_next_id'] ?? 0 ) ?: 2,
		];

		$stages    = [ apply_filters( 'anwpfl/competition/stage-admin-app-data', $root_stage ) ];
		$stage_ids = [ absint( $post_id ) ];

		/*
		|--------------------------------------------------------------------
		| Secondary Stages
		|--------------------------------------------------------------------
		*/
		if ( 'main' === ( $root_row['multistage'] ?? '' ) ) {

			foreach ( $multi_stages as $stage_info ) {
				$stage_row = anwp_fl()->competition->get_row( (int) $stage_info['id'] );

				if ( ! $stage_row ) {
					continue;
				}

				$stage_rounds = json_decode( (string) ( $stage_row['stage_rounds'] ?? '' ) ) ?: [];
				$stage_groups = json_decode( (string) ( $stage_row['stage_groups'] ?? '' ) ) ?: [];

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
						'stageId'                => absint( $stage_row['competition_id'] ?? 0 ),
						'rounds'                 => $stage_rounds ?: [],
						'type'                   => (string) ( $stage_row['type'] ?? '' ),
						'formatRobin'            => (string) ( $stage_row['format_robin'] ?? '' ),
						'formatKnockout'         => (string) ( $stage_row['format_knockout'] ?? '' ),
						'stageOrder'             => absint( $stage_row['stage_order'] ?? 0 ),
						'stageTitle'             => (string) ( $stage_row['stage_title'] ?? '' ),
						'isFriendly'             => ! empty( $stage_row['is_friendly'] ) ? 1 : 0,
						'isFriendlySaved'        => ! empty( $stage_row['is_friendly'] ) ? 1 : 0,
						'nextIdGroup'            => absint( $stage_row['group_next_id'] ?? 0 ),
						'nextIdRound'            => absint( $stage_row['round_next_id'] ?? 0 ),
					]
				);

				$stage_ids[] = absint( $stage_row['competition_id'] ?? 0 );
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
			'logo_id'          => absint( $root_row['logo_id'] ?? 0 ),
			'logo'             => (string) ( $root_row['logo'] ?? '' ),
			'competitionOrder' => absint( $root_row['competition_order'] ?? 0 ),
			'leaguesList'      => $this->plugin->league->get_leagues_list(),
			'seasonsList'      => $this->plugin->season->get_seasons_list(),
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
	 * Render Bottom Content metabox (Phase 12: replaces CMB2 wysiwyg).
	 *
	 * @param WP_Post $post
	 *
	 * @since 0.18.0
	 */
	public function render_content_below_metabox( WP_Post $post ) {
		$row     = anwp_fl()->competition->get_row( (int) $post->ID );
		$content = $row['custom_content_below'] ?? '';

		wp_editor(
			$content,
			'anwp_custom_content_below',
			[
				'textarea_name' => 'anwp_custom_content_below',
				'textarea_rows' => 5,
				'media_buttons' => true,
				'teeny'         => false,
				'tinymce'       => true,
				'quicktags'     => true,
			]
		);
	}

	/**
	 * Render Detailed Logo side metabox.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @since 0.18.0
	 */
	public function render_logo_big_metabox( WP_Post $post ) {
		$row      = anwp_fl()->competition->get_row( (int) $post->ID );
		$logo_url = $row['logo_big'] ?? '';
		$logo_id  = absint( $row['logo_big_id'] ?? 0 );
		?>
		<p class="description" style="margin-bottom: 8px;">
			<?php esc_html_e( 'Used on the Competitions page and competition header. Recommended size: 120x120 px.', 'anwp-football-leagues' ); ?>
		</p>

		<div id="anwp-logo-big-wrapper">
			<div id="anwp-logo-big-preview" style="<?php echo $logo_url ? '' : 'display:none;'; ?>">
				<img src="<?php echo esc_url( $logo_url ); ?>" style="max-width:100%;height:auto;border:1px solid #ddd;padding:4px;" alt="">
				<p><a href="#" id="anwp-logo-big-remove"><?php esc_html_e( 'Remove Image', 'anwp-football-leagues' ); ?></a></p>
			</div>
			<button type="button" class="button" id="anwp-logo-big-select"><?php esc_html_e( 'Select Image', 'anwp-football-leagues' ); ?></button>

			<input type="hidden" name="_anwpfl_logo_big" id="anwp-logo-big-url" value="<?php echo esc_attr( $logo_url ); ?>">
			<input type="hidden" name="_anwpfl_logo_big_id" id="anwp-logo-big-id" value="<?php echo esc_attr( $logo_id ); ?>">
		</div>

		<script>
		( function() {
			document.addEventListener( 'DOMContentLoaded', function() {
				var frame;
				var selectBtn = document.getElementById( 'anwp-logo-big-select' );
				var removeBtn = document.getElementById( 'anwp-logo-big-remove' );
				var preview   = document.getElementById( 'anwp-logo-big-preview' );
				var urlInput  = document.getElementById( 'anwp-logo-big-url' );
				var idInput   = document.getElementById( 'anwp-logo-big-id' );

				if ( ! selectBtn ) return;

				selectBtn.addEventListener( 'click', function( e ) {
					e.preventDefault();

					if ( frame ) {
						frame.open();
						return;
					}

					frame = wp.media( { title: '<?php echo esc_js( __( 'Select Image', 'anwp-football-leagues' ) ); ?>', library: { type: 'image' }, button: { text: '<?php echo esc_js( __( 'Select Image', 'anwp-football-leagues' ) ); ?>' }, multiple: false } );

					frame.on( 'select', function() {
						var attachment = frame.state().get( 'selection' ).first().toJSON();

						if ( attachment && attachment.url ) {
							urlInput.value = attachment.url;
							idInput.value  = attachment.id;
							preview.querySelector( 'img' ).src = attachment.url;
							preview.style.display = '';
						}
					} );

					frame.open();
				} );

				if ( removeBtn ) {
					removeBtn.addEventListener( 'click', function( e ) {
						e.preventDefault();
						urlInput.value = '';
						idInput.value  = '';
						preview.style.display = 'none';
					} );
				}
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * Render Layout sidebar metabox.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @since 0.18.0
	 */
	public function render_layout_metabox( WP_Post $post ) {
		$row    = anwp_fl()->competition->get_row( (int) $post->ID );
		$layout = $row['tmpl_layout'] ?? '';
		?>

		<select name="_anwpfl_tmpl_layout" class="widefat">
			<option value="" <?php selected( $layout, '' ); ?>><?php esc_html_e( 'Default', 'anwp-football-leagues' ); ?></option>
			<option value="tabs" <?php selected( $layout, 'tabs' ); ?>><?php esc_html_e( 'Tabs', 'anwp-football-leagues' ); ?></option>
		</select>
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

		// Phase 9 migration gate: block saves until the migration has populated
		// the custom table. Until then, fall back to the pre-0.18 save path by
		// doing nothing here (prevents writing half-populated rows).
		if ( ! get_option( 'anwpfl_competitions_migrated' ) ) {
			return $post_id;
		}

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
		|
		| Phase 9: logo / logo_id are now written directly to the custom
		| table via sync_stage_to_table() which reads $post_data. The old
		| postmeta writes + deletes are no longer needed — an empty
		| $post_data['_anwpfl_logo'] upserts an empty string to the column,
		| which clears it.
		|--------------------------------------------------------------------
		*/

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
				if ( ! absint( $stage_to_delete ) || 'secondary' !== ( anwp_fl()->competition->get_competition_list_row( (int) $stage_to_delete )['multistage'] ?? '' ) ) {
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

			/*
			 * Phase 9: 13 postmeta writes previously at this location (rounds,
			 * groups, type, format_robin, format_knockout, competition_status,
			 * stage_title, stage_order, group_next_id, round_next_id, multistage,
			 * multistage_main) now flow directly into the custom table via
			 * sync_stage_to_table() which reads $stage_data. The sync handler
			 * rebuilds stage_groups/stage_rounds JSON from $stage_data['rounds']
			 * and derives multistage + multistage_main from $stage_data['root']
			 * + $post_id (passed as 4th action arg below).
			 */

			/**
			 * Fires after competition stage save.
			 *
			 * @param int   $stage_id        Stage post ID.
			 * @param array $stage_data      Vue form data for this stage.
			 * @param array $post_data       Raw $_POST data.
			 * @param int   $post_id         Parent (root) competition post ID.
			 *                               Phase 9: added as 4th arg so sync
			 *                               handler can derive multistage_main.
			 *
			 * @since 0.16.7
			 */
			do_action( 'anwpfl/competition-stage/after_save', $stage_id, $stage_data, $post_data, $post_id );
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
