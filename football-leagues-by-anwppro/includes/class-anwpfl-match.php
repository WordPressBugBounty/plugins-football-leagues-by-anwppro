<?php
/**
 * AnWP Football Leagues :: Match.
 *
 * @since   0.1.0
 * @package AnWP_Football_Leagues
 */

/**
 * AnWP Football Leagues :: Match post type class.
 *
 * phpcs:disable WordPress.NamingConventions
 *
 * @since 0.1.0
 */
class AnWPFL_Match extends AnWPFL_DB {

	/**
	 * @var AnWP_Football_Leagues
	 */
	protected $plugin = null;

	/**
	 * @var array
	 */
	public $game_default_stats = [
		'home_goals',
		'away_goals',
		'home_goals_half',
		'away_goals_half',
		'home_goals_ft',
		'away_goals_ft',
		'home_goals_e',
		'away_goals_e',
		'home_goals_p',
		'away_goals_p',
		'home_cards_y',
		'away_cards_y',
		'home_cards_yr',
		'away_cards_yr',
		'home_cards_r',
		'away_cards_r',
		'home_corners',
		'away_corners',
		'home_fouls',
		'away_fouls',
		'home_offsides',
		'away_offsides',
		'home_possession',
		'away_possession',
		'home_shots',
		'away_shots',
		'home_shots_on_goal',
		'away_shots_on_goal',
	];

	/**
	 * Constructor.
	 *
	 * @param  AnWP_Football_Leagues $plugin Main plugin object.
	 *
	 *@since  0.1.0
	 */
	public function __construct( AnWP_Football_Leagues $plugin ) {
		global $wpdb;

		$this->plugin = $plugin;

		$this->primary_key = 'match_id';
		$this->table_name  = $wpdb->anwpfl_matches;

		$this->register_post_type( $plugin );
		$this->hooks();
	}

	/**
	 * Register Custom Post Type
	 *
	 * @param AnWP_Football_Leagues $plugin Main plugin object.
	 *
	 * @since 0.16.0
	 */
	public function register_post_type( AnWP_Football_Leagues $plugin ) {
		$permalink_structure = $plugin->options->get_permalink_structure();
		$permalink_slug      = empty( $permalink_structure['match'] ) ? 'match' : $permalink_structure['match'];

		// Register this CPT.
		$labels = [
			'name'               => _x( 'Matches', 'Post type general name', 'anwp-football-leagues' ),
			'singular_name'      => _x( 'Match', 'Post type singular name', 'anwp-football-leagues' ),
			'menu_name'          => _x( 'Matches', 'Admin Menu text', 'anwp-football-leagues' ),
			'name_admin_bar'     => _x( 'Match', 'Add New on Toolbar', 'anwp-football-leagues' ),
			'add_new'            => __( 'Add New Match', 'anwp-football-leagues' ),
			'add_new_item'       => __( 'Add New Match', 'anwp-football-leagues' ),
			'new_item'           => __( 'New Match', 'anwp-football-leagues' ),
			'edit_item'          => __( 'Edit Match', 'anwp-football-leagues' ),
			'view_item'          => __( 'View Match', 'anwp-football-leagues' ),
			'all_items'          => __( 'All Matches', 'anwp-football-leagues' ),
			'search_items'       => __( 'Search Matches', 'anwp-football-leagues' ),
			'not_found'          => __( 'No matches found.', 'anwp-football-leagues' ),
			'not_found_in_trash' => __( 'No matches found in Trash.', 'anwp-football-leagues' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 36,
			'menu_icon'           => $plugin::SVG_VS,
			'query_var'           => true,
			'rewrite'             => [ 'slug' => $permalink_slug ],
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'exclude_from_search' => 'hide' === AnWPFL_Options::get_value( 'display_front_end_search_match' ),
			'supports'            => [ 'comments' ],
		];

		register_post_type( 'anwp_match', $args );
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {

		add_action( 'load-post.php', [ $this, 'init_metaboxes' ] );
		add_action( 'load-post-new.php', [ $this, 'init_metaboxes' ] );
		add_action( 'save_post', [ $this, 'save_metabox' ], 10, 2 );

		// Remove stats on post delete
		add_action( 'delete_post', [ $this, 'on_match_delete' ] );

		// Render Custom Content below
		add_action(
			'anwpfl/tmpl-match/after_wrapper',
			function ( $match_post ) {
				$content_below = get_post_meta( $match_post->ID, '_anwpfl_custom_content_below', true );

				if ( trim( $content_below ) ) {
					echo '<div class="anwp-b-wrap mt-4">' . do_shortcode( $content_below ) . '</div>';
				}
			}
		);

		add_action( 'rest_api_init', [ $this, 'add_rest_routes' ] );
	}

	public function add_rest_routes() {
		register_rest_route(
			'anwpfl/v1',
			'/games/load_more_games/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'load_more_games' ],
				'permission_callback' => function () {
					return true;
				},
			]
		);

		register_rest_route(
			'anwpfl',
			'/competition/fix_game_status',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'fix_game_status' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/**
	 * Fires before removing a post.
	 *
	 * @param int $post_ID Post ID.
	 */
	public function on_match_delete( int $post_ID ) {
		if ( 'anwp_match' === get_post_type( $post_ID ) ) {
			$this->remove_match_statistics( $post_ID );
			$this->remove_match_missing_players( $post_ID );
		}
	}

	/**
	 * Meta box initialization.
	 *
	 * @since  0.2.0 (2018-01-18)
	 */
	public function init_metaboxes() {
		add_action(
			'add_meta_boxes',
			function ( $post_type ) {

				if ( 'anwp_match' === $post_type ) {
					add_meta_box(
						'anwpfl_match_data',
						esc_html__( 'Match Data', 'anwp-football-leagues' ),
						[ $this, 'render_metabox' ],
						$post_type,
						'normal',
						'high'
					);
				}
			}
		);
	}

	/**
	 * Render Meta Box content for Match Data.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @since  0.2.0 (2018-01-18)
	 */
	public function render_metabox( WP_Post $post ) {

		// Error message on competition not exists
		if ( ! $this->plugin->competition->get_competitions_data() ) {
			echo '<div class="anwp-b-wrap"><div class="my-3 alert alert-warning">' . esc_html__( 'Please, create a Competition first.', 'anwp-football-leagues' ) . '</div></div>';

			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$setup_match = isset( $_GET['setup-match-header'] ) && 'yes' === $_GET['setup-match-header'];

		// Add nonce for security and authentication.
		wp_nonce_field( 'anwp_save_metabox_' . $post->ID, 'anwp_metabox_nonce' );

		$app_id    = apply_filters( 'anwpfl/match/vue_app_id', 'fl-app-match' );
		$game_data = anwp_fl()->match->get_game_data( $post->ID );

		if ( ! $setup_match && ! empty( $game_data ) ) :
			$home_id = $game_data['home_club'];
			$away_id = $game_data['away_club'];

			$home_title = $this->plugin->club->get_club_title_by_id( $home_id );
			$away_title = $this->plugin->club->get_club_title_by_id( $away_id );

			$home_logo = $this->plugin->club->get_club_logo_by_id( $home_id, false );
			$away_logo = $this->plugin->club->get_club_logo_by_id( $away_id, false );

			$competition_id = $game_data['competition_id'];
			$season_id      = $game_data['season_id'];

			$season = get_term( $season_id, 'anwp_season' );
			$league = get_term( $game_data['league_id'], 'anwp_league' );

			// Check for a Round title
			$is_knockout = 'knockout' === get_post_meta( $competition_id, '_anwpfl_type', true );
			$matchweek   = $game_data['match_week'] ?: '';
			$round_title = $is_knockout ? $this->plugin->competition->get_round_title( $competition_id, $matchweek ) : '';

			$is_menu_collapsed = 'yes' === get_user_setting( 'anwp-fl-collapsed-menu' );
			?>
			<div class="anwp-b-wrap anwpfl-match-metabox-wrapper">

				<div class="mb-3 border border-success bg-light px-3 py-2">
					<div class="d-flex flex-wrap align-items-center">
						<b class="mr-1"><?php echo esc_html__( 'Competition', 'anwp-football-leagues' ); ?>:</b> <span><?php echo esc_html( get_the_title( $competition_id ) ); ?></span>

						<?php if ( ! empty( $league->name ) ) : ?>
							<span class="text-muted small mx-2">|</span>
							<b class="mr-1"><?php echo esc_html__( 'League', 'anwp-football-leagues' ); ?>:</b> <span><?php echo esc_html( $league->name ); ?></span>
						<?php endif; ?>

						<?php if ( ! empty( $season->name ) ) : ?>
							<span class="text-muted small mx-2">|</span>
							<b class="mr-1"><?php echo esc_html__( 'Season', 'anwp-football-leagues' ); ?>:</b> <span><?php echo esc_html( $season->name ); ?></span>
						<?php endif; ?>

						<?php if ( $round_title ) : ?>
							<span class="text-muted small mx-2">|</span>
							<b class="mr-1"><?php echo esc_html__( 'Round', 'anwp-football-leagues' ); ?>:</b> <span><?php echo esc_html( $round_title ); ?></span>
						<?php endif; ?>

						<a class="ml-auto anwp-text-red-700" href="<?php echo esc_url( get_delete_post_link( $post ) ); ?>"><?php echo esc_html__( 'Delete Game', 'anwp-football-leagues' ); ?></a>
						<span class="text-muted small mx-3">|</span>
						<a target="_blank" href="<?php echo esc_url( admin_url( 'post.php?post=' . $post->ID . '&action=edit&setup-match-header=yes' ) ); ?>"><?php echo esc_html__( 'Edit Structure', 'anwp-football-leagues' ); ?></a>
					</div>

					<div class="d-flex flex-wrap pt-2 mt-2 align-items-center border-top">
						<div class="match__club-wrapper--header d-flex align-items-center py-3">
							<?php if ( $home_logo ) : ?>
								<div class="club-logo__cover" style="background-image: url('<?php echo esc_attr( $home_logo ); ?>')"></div>
							<?php endif; ?>
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $home_id ) . '&action=edit' ) ); ?>" target="_blank"
								data-anwpfl_tippy data-tippy-content="<?php echo esc_attr__( 'Edit Club', 'anwp-football-leagues' ); ?>"
								class="text-decoration-none mx-3 d-inline-block anwp-text-xl anwp-text-gray-800"><?php echo esc_html( $home_title ); ?></a>
						</div>

						<div class="match__scores-number-wrapper mx-3">
							<div class="anwp-text-gray-500 anwp-text-base d-inline-block my-0">-</div>
						</div>

						<div class="match__club-wrapper--header d-flex flex-sm-row-reverse align-items-center py-3">
							<?php if ( $away_logo ) : ?>
								<div class="club-logo__cover club-logo__cover--xlarge d-block" style="background-image: url('<?php echo esc_attr( $away_logo ); ?>')"></div>
							<?php endif; ?>
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $away_id ) . '&action=edit' ) ); ?>" target="_blank"
								data-anwpfl_tippy data-tippy-content="<?php echo esc_attr__( 'Edit Club', 'anwp-football-leagues' ); ?>"
								class="text-decoration-none mx-3 d-inline-block anwp-text-xl anwp-text-gray-800"><?php echo esc_html( $away_title ); ?></a>
						</div>
					</div>

					<?php if ( empty( $league->name ) || empty( $season->name ) || empty( get_post( $competition_id )->post_title ) ) : ?>
						<div class="my-2 p-3 anwp-bg-orange-200 anwp-border anwp-border-orange-800 anwp-text-orange-900 d-flex align-items-center">
							<svg class="anwp-icon anwp-icon--octi anwp-icon--s24 mr-2 anwp-fill-current">
								<use xlink:href="#icon-alert"></use>
							</svg>
							<div>
								<?php echo esc_html__( 'Your Match Structure is invalid.', 'anwp-football-leagues' ); ?><br>
								<?php echo esc_html__( 'Match should have published Competition, Season and League.', 'anwp-football-leagues' ); ?>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<div class="d-sm-flex mt-2" id="anwp-fl-metabox-page-nav">
					<div class="anwp-fl-menu-wrapper mr-3 d-block align-self-start anwp-flex-none <?php echo esc_attr( $is_menu_collapsed ? 'anwp-fl-collapsed-menu' : '' ); ?>" style="top: 50px;">

						<button class="w-100 button button-primary py-2 mb-4 d-flex align-items-center justify-content-center" type="submit">
							<svg class="anwp-icon anwp-icon--feather anwp-icon--s16 anwp-flex-none"><use xlink:href="#icon-save"></use></svg>
							<span class="ml-2 anwp-fl-save-label"><?php echo esc_html__( 'Save', 'anwp-football-leagues' ); ?></span>
							<span class="spinner m-0"></span>
						</button>

						<ul class="m-0 p-0 list-unstyled d-none d-sm-block">
							<?php
							$nav_items = [
								[
									'icon'  => 'gear',
									'label' => _x( 'Basic Information', 'submenu', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-general-metabox',
								],
								[
									'icon'  => 'graph',
									'label' => _x( 'Scores & Match Stats', 'submenu', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-game-statistics-metabox',
								],
								[
									'icon'  => 'jersey',
									'label' => _x( 'Lineups', 'submenu', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-players-metabox',
								],
								[
									'icon'  => 'pulse',
									'label' => __( 'Match Events', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-game-events-metabox',
								],
								[
									'icon'  => 'x',
									'label' => __( 'Missing Players', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-game-sidelines-metabox',
								],
								[
									'icon'  => 'organization',
									'label' => __( 'Referee', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-referee-metabox',
								],
								[
									'icon'  => 'note',
									'label' => _x( 'Text Content', 'submenu', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-summary-metabox',
								],
								[
									'icon'  => 'device-camera',
									'label' => __( 'Media', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-media-match-metabox',
								],
								[
									'icon'  => 'watch',
									'label' => __( 'Match Duration', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-timing-match-metabox',
								],
								[
									'icon'  => 'law',
									'label' => __( 'Custom Outcome', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-outcome-match-metabox',
								],
							];

							/**
							 * Modify metabox nav items
							 *
							 * @since 0.12.7
							 */
							$nav_items = apply_filters( 'anwpfl/match/metabox_nav_items', $nav_items );

							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo anwp_fl()->helper->create_metabox_navigation( $nav_items );

							/**
							 * Fires at the bottom of Metabox Nav.
							 *
							 * @since 0.12.7
							 */
							do_action( 'anwpfl/match/metabox_nav_bottom' );
							?>
						</ul>

					</div>
					<div class="flex-grow-1 anwp-min-width-0 mb-4">

						<?php
						$post_id          = $post->ID;
						$competition_type = get_post_meta( $competition_id, '_anwpfl_type', true );

						/*
						|--------------------------------------------------------------------
						| Prepare Clubs Data
						|--------------------------------------------------------------------
						*/
						$club_home_id = $game_data['home_club'];
						$club_away_id = $game_data['away_club'];

						$club_home = (object) [
							'id'    => $club_home_id,
							'logo'  => $this->plugin->club->get_club_logo_by_id( $club_home_id ),
							'title' => $this->plugin->club->get_club_title_by_id( $club_home_id ),
						];

						$club_away = (object) [
							'id'    => $club_away_id,
							'logo'  => $this->plugin->club->get_club_logo_by_id( $club_away_id ),
							'title' => $this->plugin->club->get_club_title_by_id( $club_away_id ),
						];

						/*
						|--------------------------------------------------------------------
						| Prepare players
						|--------------------------------------------------------------------
						*/
						$home_players = $this->plugin->club->get_club_season_players(
							[
								'club' => $club_home_id,
								'id'   => $season_id,
							],
							'short'
						);

						$away_players = $this->plugin->club->get_club_season_players(
							[
								'club' => $club_away_id,
								'id'   => $season_id,
							],
							'short'
						);

						$home_lineup = $game_data['home_line_up'];
						$away_lineup = $game_data['away_line_up'];
						$home_subs   = $game_data['home_subs'];
						$away_subs   = $game_data['away_subs'];

						/*
						|--------------------------------------------------------------------
						| Players in Squad + Number
						|--------------------------------------------------------------------
						*/
						$home_squad_numbers = [];
						$away_squad_numbers = [];
						$squad_position_map = [];

						foreach ( $home_players as $player ) {
							$home_squad_numbers[ $player->id ] = isset( $player->number ) ? $player->number : '';

							if ( ! empty( $player->position ) ) {
								$squad_position_map[ $player->id ] = $player->position;
							}
						}

						foreach ( $away_players as $player ) {
							$away_squad_numbers[ $player->id ] = isset( $player->number ) ? $player->number : '';

							if ( ! empty( $player->position ) ) {
								$squad_position_map[ $player->id ] = $player->position;
							}
						}

						/*
						|--------------------------------------------------------------------
						| Populate Data
						|--------------------------------------------------------------------
						*/
						$match_l10n = [
							'additional_referees'   => esc_html__( 'Additional referees', 'anwp-football-leagues' ),
							'add_another_referee'   => esc_html__( 'Add Another Referee', 'anwp-football-leagues' ),
							'add_coach'             => esc_html__( 'Add Coach', 'anwp-football-leagues' ),
							'add_temporary_referee' => esc_html__( 'Add Temporary Referee', 'anwp-football-leagues' ),
							'add_temporary_coach'   => esc_html__( 'Add Temporary Coach', 'anwp-football-leagues' ),
							'add_player_as_text'    => esc_html__( 'Add player as text string without creating its profile in the site database.', 'anwp-football-leagues' ),
							'add_referee_as_text'   => esc_html__( 'Add referee as text string without creating its profile in the site database.', 'anwp-football-leagues' ),
							'add_coach_as_text'     => esc_html__( 'Add coach as text string without creating its profile in the site database.', 'anwp-football-leagues' ),
							'assistant_1'           => esc_html__( 'Assistant 1', 'anwp-football-leagues' ),
							'assistant_2'           => esc_html__( 'Assistant 2', 'anwp-football-leagues' ),
							'captain'               => esc_html__( 'Captain', 'anwp-football-leagues' ),
							'coach_name'            => esc_html__( 'Coach Name', 'anwp-football-leagues' ),
							'close'                 => esc_html__( 'Close', 'anwp-football-leagues' ),
							'club_players'          => esc_html__( 'club players', 'anwp-football-leagues' ),
							'club_squad'            => esc_html__( 'Club Squad', 'anwp-football-leagues' ),
							'club_coach'            => esc_html__( 'club coach', 'anwp-football-leagues' ),
							'custom_number'         => esc_html__( 'Custom Number', 'anwp-football-leagues' ),
							'delete'                => esc_html__( 'Delete', 'anwp-football-leagues' ),
							'nationality'           => esc_html__( 'Nationality', 'anwp-football-leagues' ),
							'next'                  => esc_html__( 'Next', 'anwp-football-leagues' ),
							'prev'                  => esc_html__( 'Prev', 'anwp-football-leagues' ),
							'referee'               => esc_html__( 'Referee', 'anwp-football-leagues' ),
							'referee_name'          => esc_html__( 'Referee Name', 'anwp-football-leagues' ),
							'referee_fourth'        => esc_html__( 'Fourth official', 'anwp-football-leagues' ),
							'remove_referee'        => esc_html__( 'Remove Referee', 'anwp-football-leagues' ),
							'role'                  => esc_html__( 'Role', 'anwp-football-leagues' ),
							'saved_referees'        => esc_html__( 'Saved Referees', 'anwp-football-leagues' ),
							'saved_players'         => esc_html__( 'Saved Players', 'anwp-football-leagues' ),
							'saved_coaches'         => esc_html__( 'Saved Coaches', 'anwp-football-leagues' ),
							'search_by_name'        => esc_html__( 'search by name', 'anwp-football-leagues' ),
							'select'                => esc_html__( 'Select', 'anwp-football-leagues' ),
							'select_saved_players'  => esc_html__( 'Select from the list of players saved on the site.', 'anwp-football-leagues' ),
							'select_saved_site'     => esc_html__( 'Select from the list of referees saved on the site.', 'anwp-football-leagues' ),
							'select_saved_coach'    => esc_html__( 'Select from the list of coaches saved on the site.', 'anwp-football-leagues' ),
							'select_referee'        => esc_html__( 'Select Referee', 'anwp-football-leagues' ),
							'select_player'         => esc_html__( 'Select Player', 'anwp-football-leagues' ),
							'squad_number'          => esc_html__( 'Squad Number', 'anwp-football-leagues' ),
							'starting_line_up'      => esc_html__( 'Starting LineUp', 'anwp-football-leagues' ),
							'substitutes'           => esc_html__( 'Substitutes', 'anwp-football-leagues' ),
							'temporary_players'     => esc_html__( 'Temporary Player', 'anwp-football-leagues' ),
							'temporary_referee'     => esc_html__( 'Temporary Referee', 'anwp-football-leagues' ),
							'temporary_coach'       => esc_html__( 'Temporary Coach', 'anwp-football-leagues' ),
							'add_temp_player'       => esc_html__( 'add temporary Player (as text string without saving into database)', 'anwp-football-leagues' ),
						];

						$game_stats = [];
						foreach ( anwp_fl()->match->game_default_stats as $game_stat_slug ) {
							$game_stats[ $game_stat_slug ] = $game_data[ $game_stat_slug ] ?? '';
						}

						/*
						|--------------------------------------------------------------------
						| Populate Data
						|--------------------------------------------------------------------
						*/
						$data = [
							'stadiumDefault'       => anwp_fl()->stadium->get_stadium_id_by_club( $club_home_id ),
							'l10n'                 => anwp_fl()->helper->recursive_entity_decode( array_merge( $match_l10n, anwp_fl()->data->get_l10n_admin() ) ),
							'l10n_datepicker'      => anwp_fl()->data->get_vue_datepicker_locale(),
							'optionsStadium'       => $this->plugin->stadium->get_stadiums(),
							'default_photo'        => anwp_fl()->helper->get_default_player_photo(),
							'optionsPlayers'       => $this->plugin->player->get_player_obj_list( $squad_position_map ),
							'optionsStaff'         => $this->plugin->staff->get_staff_list(),
							'optionsSpecialStatus' => $this->plugin->data->get_game_special_statuses(),
							'optionsReferees'      => $this->plugin->referee->get_referee_list(),
							'optionsClubMap'       => $this->plugin->club->get_clubs_options(),
							'squadHomeNumbers'     => empty( $home_squad_numbers ) ? (object) [] : $home_squad_numbers,
							'squadHomeOrder'       => array_keys( $home_squad_numbers ),
							'squadAwayNumbers'     => empty( $away_squad_numbers ) ? (object) [] : $away_squad_numbers,
							'squadAwayOrder'       => array_keys( $away_squad_numbers ),
							'staffHomeAll'         => array_keys(
								$this->plugin->club->get_club_season_staff(
									[
										'club' => $club_home_id,
										'id'   => $season_id,
									]
								)
							),
							'staffAwayAll'         => array_keys(
								$this->plugin->club->get_club_season_staff(
									[
										'club' => $club_away_id,
										'id'   => $season_id,
									]
								)
							),
							'finished'             => (string) $game_data['finished'],
							'datetime'             => $game_data['kickoff'] && '0000-00-00 00:00:00' !== $game_data['kickoff'] ? $game_data['kickoff'] : '',
							'stadium'              => absint( $game_data['stadium_id'] ) ?: '',
							'competitionType'      => $competition_type,
							'matchWeek'            => $game_data['match_week'] ?: '',
							'clubHome'             => $club_home,
							'clubAway'             => $club_away,
							'stats'                => $game_stats,
							'attendance'           => $game_data['attendance'] ?: '',
							'special_status'       => $game_data['special_status'],
							'aggtext'              => $game_data['aggtext'],
							'extraTime'            => absint( $game_data['extra'] ) > 0 && absint( $game_data['extra'] ) < 3 ? 'yes' : '',
							'penalty'              => absint( $game_data['extra'] ) > 1 ? 'yes' : '',
							'playersHomeStart'     => $home_lineup,
							'playersHomeSubs'      => $home_subs,
							'playersAwayStart'     => $away_lineup,
							'playersAwaySubs'      => $away_subs,
							'coachHome'            => $game_data['coach_home'],
							'coachAway'            => $game_data['coach_away'],
							'tempCoach'            => get_post_meta( $post_id, '_anwpfl_temp_coach', true ),
							'captainHomeId'        => $game_data['captain_home'] ?? '',
							'captainAwayId'        => $game_data['captain_away'] ?? '',
							'matchEvents'          => $game_data['match_events'],
							'missingPlayers'       => wp_json_encode( $this->get_game_missed_players( $post_id ) ),
							'customNumbers'        => $game_data['custom_numbers'],
							'tempPlayers'          => get_post_meta( $post_id, '_anwpfl_match_temp_players', true ),
							'matchID'              => $post_id,
							'optionsPositions'     => anwp_fl()->data->get_positions(),
							'optionsCountries'     => anwp_fl()->data->cb_get_countries(),
							'referee'              => $game_data['referee'],
							'assistant_1'          => get_post_meta( $post_id, '_anwpfl_assistant_1', true ),
							'assistant_2'          => get_post_meta( $post_id, '_anwpfl_assistant_2', true ),
							'referee_fourth'       => get_post_meta( $post_id, '_anwpfl_referee_fourth', true ),
							'additional_referees'  => get_post_meta( $post_id, '_anwpfl_additional_referees', true ),
							'tempReferees'         => get_post_meta( $post_id, '_anwpfl_temp_referees', true ),
						];

						/**
						 * Filters a match data to localize.
						 *
						 * @param array $data    Match data
						 * @param int   $post_id Match Post ID
						 * @param array $game_data
						 *
						 * @since 0.7.4
						 */
						$data = apply_filters( 'anwpfl/match/data_to_localize', $data, $post_id, $game_data );
						?>

						<script type="text/javascript">
							window.anwpMatch = <?php echo wp_json_encode( $data ); ?>;
						</script>

						<div id="<?php echo esc_attr( $app_id ); ?>"></div>

						<?php cmb2_metabox_form( 'anwp_match_metabox' ); ?>

						<?php
						/**
						 * Fires at the bottom of Metabox.
						 *
						 * @since 0.12.7
						 */
						do_action( 'anwpfl/match/metabox_bottom' );
						?>
					</div>
				</div>

				<input type="hidden" name="_fl_game_save" value="fixed">

				<?php
				/**
				 * Fires at the bottom of Match edit form.
				 *
				 * @since 0.10.0
				 */
				do_action( 'anwpfl/match/edit_form_bottom' );
				?>
			</div>
		<?php else : ?>
			<div class="anwp-b-wrap anwpfl-match-metabox-wrapper">
				<?php
				if ( $setup_match ) :

					$home_id = $game_data['home_club'];
					$away_id = $game_data['away_club'];

					$home_title = $this->plugin->club->get_club_title_by_id( $home_id );
					$away_title = $this->plugin->club->get_club_title_by_id( $away_id );

					$competition_id = $game_data['competition_id'];
					$season_id      = $game_data['season_id'];

					$season = get_term( $season_id, 'anwp_season' );
					$league = get_term( get_post_meta( $post->ID, '_anwpfl_league', true ), 'anwp_league' );

					// Check for a Round title
					$is_knockout = 'knockout' === get_post_meta( $competition_id, '_anwpfl_type', true );
					$matchweek   = $game_data['match_week'] ?: '';

					$round_title = $is_knockout ? $this->plugin->competition->get_round_title( $competition_id, $matchweek ) : '';
					?>
					<div class="my-2 p-3 anwp-bg-orange-200 anwp-border anwp-border-orange-800 anwp-text-orange-900 d-flex align-items-center">
						<svg class="anwp-icon anwp-icon--octi anwp-icon--s24 mr-2 anwp-fill-current">
							<use xlink:href="#icon-alert"></use>
						</svg>
						<div>
							<?php echo esc_html__( 'Use the Match Structure editing with caution.', 'anwp-football-leagues' ); ?><br>
							<?php echo esc_html__( 'Save Match data on the next step to recalculate statistic.', 'anwp-football-leagues' ); ?>
						</div>
					</div>
					<div class="my-2 p-3 anwp-bg-blue-200 anwp-border anwp-border-blue-800 anwp-text-blue-900">
						<h4 class="mt-0 mb-2 anwp-text-base"><?php echo esc_html__( 'Old Structure', 'anwp-football-leagues' ); ?></h4>
						<b class="mr-1"><?php echo esc_html__( 'Competition', 'anwp-football-leagues' ); ?>:</b>
						<span><?php echo esc_html( get_the_title( $competition_id ) ); ?></span>

						<?php if ( ! empty( $league->name ) ) : ?>
							<span class="text-muted small mx-2">|</span>
							<b class="mr-1"><?php echo esc_html__( 'League', 'anwp-football-leagues' ); ?>:</b>
							<span><?php echo esc_html( $league->name ); ?></span>
						<?php endif; ?>

						<?php if ( ! empty( $season->name ) ) : ?>
							<span class="text-muted small mx-2">|</span>
							<b class="mr-1"><?php echo esc_html__( 'Season', 'anwp-football-leagues' ); ?>:</b>
							<span><?php echo esc_html( $season->name ); ?></span>
						<?php endif; ?>

						<?php if ( $round_title ) : ?>
							<span class="text-muted small mx-2">|</span>
							<b class="mr-1"><?php echo esc_html__( 'Round', 'anwp-football-leagues' ); ?>:</b>
							<span><?php echo esc_html( $round_title ); ?></span>
						<?php endif; ?>
						<br>
						<?php echo esc_html( $home_title . ' - ' . $away_title ); ?>
					</div>
				<?php endif; ?>
				<?php
				$competition_id = $game_data['competition_id'] ?? '';
				$round          = '';

				if ( $competition_id && 'knockout' === get_post_meta( $competition_id, '_anwpfl_type', true ) ) {
					$round = $game_data['match_week'] ?: '';
				}

				$match_setup_data = [
					'seasons_list'  => anwp_fl()->season->get_seasons_list(),
					'clubs_list'    => anwp_fl()->club->get_clubs_list(),
					'competitions'  => anwp_fl()->competition->get_competitions(),
					'competitionId' => $competition_id,
					'activeSeason'  => anwp_fl()->get_active_season(),
					'season'        => $game_data['season_id'] ?? '',
					'group'         => $game_data['group_id'] ?? '',
					'round'         => $round,
					'clubHome'      => $game_data['home_club'] ?? '',
					'clubAway'      => $game_data['away_club'] ?? '',
					'setupMatch'    => 'yes' === ( $_GET['setup-match-header'] ?? '' ), //phpcs:ignore
				];
				?>
				<script type="text/javascript">
					window._anwp_FL_MatchSetup_Data = <?php echo wp_json_encode( $match_setup_data ); ?>;
					window._anwp_FL_MatchSetup_L10n = <?php echo wp_json_encode( anwp_fl()->helper->recursive_entity_decode( anwp_fl()->data->get_l10n_admin() ) ); ?>;
				</script>
				<div id="fl-app-match-setup"></div>
			</div>
			<?php
		endif;
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 *
	 * @since  0.2.0 (2017-12-10)
	 * @return int
	 */
	public function save_metabox( int $post_id ): int {

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
		if ( 'anwp_match' !== $_POST['post_type'] ) {
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

		/* OK, it's safe for us to save the data now. */

		/** ---------------------------------------
		 * Save Match Data
		 *
		 * @since 0.2.0
		 * ---------------------------------------*/
		global $wpdb;

		$post_data      = wp_unslash( $_POST );
		$game_save_mode = $post_data['_fl_game_save'] ?? '';
		$slug_update    = false;
		$data           = [];

		if ( 'setup' === $game_save_mode ) {

			$data['competition_id'] = $post_data['_anwpfl_match_competition'] ?? '';
			$data['season_id']      = $post_data['_anwpfl_match_season'] ?? '';
			$data['league_id']      = $post_data['_anwpfl_match_league'] ?? '';
			$data['match_week']     = $post_data['_anwpfl_match_round'] ?? '';
			$data['group_id']       = $post_data['_anwpfl_match_group'] ?? '';
			$data['home_club']      = $post_data['_anwpfl_match_home'] ?? '';
			$data['away_club']      = $post_data['_anwpfl_match_away'] ?? '';

			$data = array_map( 'absint', $data );

			if ( isset( $post_data['anwp-match-setup-submit'] ) && 'yes' === $post_data['anwp-match-setup-submit'] ) {
				if ( $data['competition_id'] && $data['season_id'] && $data['home_club'] && $data['away_club'] ) {
					if ( empty( $this->get_game_data( $post_id ) ) ) {
						$data['match_id'] = $post_id;

						if ( 'friendly' === get_post_meta( $data['competition_id'], '_anwpfl_competition_status', true ) ) {
							$data['game_status'] = 0;
						}

						$this->insert( $data );
					} else {
						$this->update( $post_id, $data );
					}
				}
			}
		} elseif ( 'fixed' === $game_save_mode ) {
			$game_data = $this->get_game_data( $post_id );

			if ( 'publish' !== get_post_status( $post_id ) ) {
				wp_publish_post( $post_id );
			}

			/*
			|--------------------------------------------------------------------
			| Match Data
			|--------------------------------------------------------------------
			*/
			$data['extra']          = absint( $post_data['_fl_extra'] ?? 0 );
			$data['finished']       = absint( $post_data['_fl_finished'] ?? 0 );
			$data['stadium_id']     = absint( $post_data['_anwpfl_stadium'] ?? 0 );
			$data['attendance']     = absint( $post_data['_anwpfl_attendance'] ?? 0 );
			$data['coach_home']     = sanitize_key( $post_data['_anwpfl_coach_home'] ?? '' );
			$data['coach_away']     = sanitize_key( $post_data['_anwpfl_coach_away'] ?? '' );
			$data['referee']        = sanitize_text_field( $post_data['_anwpfl_referee'] ?? '' );
			$data['aggtext']        = sanitize_text_field( $post_data['_anwpfl_aggtext'] ?? '' );
			$data['special_status'] = $data['finished'] ? '' : sanitize_text_field( $post_data['_anwpfl_special_status'] ?? '' );

			if ( ! $game_data['main_stage_id'] ) {
				if ( 'secondary' === get_post_meta( $game_data['competition_id'], '_anwpfl_multistage', true ) ) {
					$data['main_stage_id'] = get_post_meta( $game_data['competition_id'], '_anwpfl_multistage_main', true );
				} else {
					$data['main_stage_id'] = $game_data['competition_id'];
				}
			}

			/*
			|--------------------------------------------------------------------
			| Save MatchWeek for Round-Robin Competition
			| For Knockout MatchWeek = Round and is saved on match setup step
			|--------------------------------------------------------------------
			*/
			if ( 'round-robin' === get_post_meta( $game_data['competition_id'], '_anwpfl_type', true ) ) {
				$data['match_week'] = sanitize_text_field( $post_data['_anwpfl_matchweek'] ?? '' );
			}

			/*
			|--------------------------------------------------------------------
			|
			|--------------------------------------------------------------------
			*/
			$data['kickoff']  = sanitize_text_field( $post_data['_anwpfl_datetime'] ?? '' );
			$data['kickoff']  = $this->plugin->helper->validate_date( $data['kickoff'], 'Y-m-d H:i' ) ? ( $data['kickoff'] . ':00' ) : '0000-00-00 00:00:00';
			$data['priority'] = sanitize_text_field( $post_data['_anwpfl_match_priority'] ?? 0 );

			/*
			|--------------------------------------------------------------------
			| Complex fields with extra WP sanitization:
			| > Stats, Events & Custom Numbers
			|--------------------------------------------------------------------
			*/
			$game_stats = ( $post_data['_anwpfl_match_stats'] ?? [] ) ? json_decode( $post_data['_anwpfl_match_stats'], true ) : [];

			if ( ! empty( $game_stats ) ) {
				$game_stats = array_map(
					function ( $stat ) {
						return '' === $stat ? null : sanitize_text_field( $stat );
					},
					$game_stats
				);

				$data = array_merge( $data, $game_stats );
			}

			$game_events          = ( $post_data['_anwpfl_match_events'] ?? [] ) ? json_decode( $post_data['_anwpfl_match_events'], true ) : [];
			$data['match_events'] = $game_events ? wp_json_encode( $game_events ) : '';

			/*
			|--------------------------------------------------------------------
			| Update Data
			|--------------------------------------------------------------------
			*/
			$this->update( $post_id, $data );

			/*
			|--------------------------------------------------------------------
			| Lineups data
			|--------------------------------------------------------------------
			*/
			$lineups_data = [
				'home_line_up'   => sanitize_text_field( $post_data['_anwpfl_players_home_line_up'] ?? '' ),
				'away_line_up'   => sanitize_text_field( $post_data['_anwpfl_players_away_line_up'] ?? '' ),
				'home_subs'      => sanitize_text_field( $post_data['_anwpfl_players_home_subs'] ?? '' ),
				'away_subs'      => sanitize_text_field( $post_data['_anwpfl_players_away_subs'] ?? '' ),
				'captain_home'   => sanitize_text_field( $post_data['_anwpfl_captain_home'] ?? '' ),
				'captain_away'   => sanitize_text_field( $post_data['_anwpfl_captain_away'] ?? '' ),
				'custom_numbers' => ( $post_data['_anwpfl_match_custom_numbers'] ?? '' ) ? wp_json_encode( json_decode( $post_data['_anwpfl_match_custom_numbers'] ) ) : '',
			];

			$wpdb->replace( $wpdb->anwpfl_lineups, array_merge( $lineups_data, [ 'match_id' => $post_id ] ) );

			/*
			|--------------------------------------------------------------------
			| Temporary players (text fields)
			|--------------------------------------------------------------------
			*/
			$temp_players = isset( $post_data['_anwpfl_match_temp_players'] ) ? json_decode( stripslashes( $post_data['_anwpfl_match_temp_players'] ) ) : [];

			if ( empty( $temp_players ) ) {
				delete_post_meta( $post_id, '_anwpfl_match_temp_players' );
			} else {
				update_post_meta( $post_id, '_anwpfl_match_temp_players', wp_slash( wp_json_encode( $temp_players ) ) );
			}

			/*
			|--------------------------------------------------------------------
			| Temporary coaches (text fields)
			|--------------------------------------------------------------------
			*/
			$temp_coach = isset( $post_data['_anwpfl_temp_coach'] ) ? json_decode( stripslashes( $post_data['_anwpfl_temp_coach'] ) ) : false;

			if ( empty( $temp_coach ) || ( 'temp' !== $data['coach_home'] && 'temp' !== $data['coach_away'] ) ) {
				delete_post_meta( $post_id, '_anwpfl_temp_coach' );
			} else {
				update_post_meta( $post_id, '_anwpfl_temp_coach', anwp_fl()->helper->recursive_sanitize( $temp_coach ) );
			}

			/*
			|--------------------------------------------------------------------
			| Additional Referees
			|--------------------------------------------------------------------
			*/
			foreach (
				[
					'_anwpfl_assistant_1',
					'_anwpfl_assistant_2',
					'_anwpfl_referee_fourth',
					'_anwpfl_additional_referees',
					'_anwpfl_temp_referees',
				] as $referee_slug
			) {

				$is_empty = empty( $post_data[ $referee_slug ] );

				if ( '_anwpfl_additional_referees' === $referee_slug ) {
					$is_empty = '[]' === ( $post_data[ $referee_slug ] ?? '' ) || empty( $post_data[ $referee_slug ] );
				} elseif ( '_anwpfl_temp_referees' === $referee_slug ) {
					$is_empty = '{"referee":{},"assistant_1":{},"assistant_2":{},"referee_fourth":{},"additional_referees":{}}' === ( $post_data[ $referee_slug ] ?? '' ) || '{"referee":[],"assistant_1":[],"assistant_2":[],"referee_fourth":[],"additional_referees":[]}' === ( $post_data[ $referee_slug ] ?? '' ) || empty( $post_data[ $referee_slug ] );
				}

				if ( $is_empty ) {
					delete_post_meta( $post_id, $referee_slug );
				} elseif ( in_array( $referee_slug, [ '_anwpfl_temp_referees', '_anwpfl_additional_referees' ], true ) ) {
					$json_referees = json_decode( $post_data[ $referee_slug ], true );
					update_post_meta( $post_id, $referee_slug, $json_referees ? anwp_fl()->helper->recursive_sanitize( $json_referees ) : [] );
				} else {
					update_post_meta( $post_id, $referee_slug, sanitize_text_field( $post_data[ $referee_slug ] ) );
				}
			}

			$data = array_merge( $game_data, $data, $lineups_data );

			$this->save_player_statistics( $data, $game_events );

			$missing_players = ( $post_data['_anwpfl_missing_players'] ?? [] ) ? json_decode( $post_data['_anwpfl_missing_players'] ) : [];
			$this->save_missing_players( $missing_players, $post_id );

			/**
			 * Trigger on save match data.
			 *
			 * @param array $data Match data
			 * @param array $_POST
			 */
			do_action( 'anwpfl/match/on_save', $data, $post_data );

			// Recalculate standing
			$this->plugin->standing->calculate_standing_prepare( $post_id, $data['competition_id'], $data['group_id'] );

			$slug_update = true;
		}

		/**
		 * Update Match title and slug
		 */
		if ( $slug_update && ! empty( $data['home_club'] && ! empty( $data['away_club'] ) ) ) {

			/**
			 * Update Match title and slug.
			 *
			 * @since 0.3.0
			 */
			$post      = get_post( $post_id );
			$home_club = $this->plugin->club->get_club_title_by_id( $data['home_club'] );
			$away_club = $this->plugin->club->get_club_title_by_id( $data['away_club'] );

			if ( ! $home_club || ! $away_club ) {
				return $post_id;
			}

			if ( 'fixed' === $game_save_mode && trim( AnWPFL_Options::get_value( 'match_title_generator' ) ) ) {
				$match_title = $this->get_match_title_generated( $data, $home_club, $away_club );
			} else {
				$match_title_separator = AnWPFL_Options::get_value( 'match_title_separator', '-' );

				/**
				 * Filters a match title clubs separator.
				 *
				 * @since 0.10.1
				 *
				 * @param string  $match_title_separator Match title separator to be returned.
				 * @param WP_Post $post                  Match WP_Post object
				 * @param array   $data                  Match data
				 */
				$match_title_separator = apply_filters( 'anwpfl/match/title_separator_to_save', $match_title_separator, $post, $data );

				$match_title = sanitize_text_field( $home_club . ' ' . $match_title_separator . ' ' . $away_club );

				/**
				 * Filters a match title before save.
				 *
				 * @since 0.5.3
				 *
				 * @param string  $match_title Match title to be returned.
				 * @param string  $home_club   Home club title.
				 * @param string  $away_club   Away club title.
				 * @param WP_Post $post        Match WP_Post object
				 * @param array   $data        Match data
				 */
				$match_title = apply_filters( 'anwpfl/match/title_to_save', $match_title, $home_club, $away_club, $post, $data );
			}

			$match_slug = $this->get_match_slug_generated( $data, $home_club, $away_club, $post );

			// Rename Match (title and slug)
			if ( $post->post_name !== $match_slug || $post->post_title !== $match_title ) {

				remove_action( 'save_post', [ $this, 'save_metabox' ] );

				// update the post, which calls save_post again
				wp_update_post(
					[
						'ID'         => $post_id,
						'post_title' => $match_title,
						'post_name'  => $match_slug,
					]
				);

				// re-hook this function
				add_action( 'save_post', [ $this, 'save_metabox' ] );
			}
		}

		return $post_id;
	}

	/**
	 * Method removes match statistics from DB.
	 *
	 * @param int $match_id -
	 *
	 * @since 0.3.0 (2018-01-30)
	 */
	private function remove_match_statistics( int $match_id ) {
		global $wpdb;

		if ( $match_id ) {
			$game_data = $this->get_game_data( $match_id );

			$wpdb->delete( $wpdb->prefix . 'anwpfl_matches', [ 'match_id' => $match_id ] );
			$wpdb->delete( $wpdb->prefix . 'anwpfl_players', [ 'match_id' => $match_id ] );
			$wpdb->delete( $wpdb->prefix . 'anwpfl_lineups', [ 'match_id' => $match_id ] );

			// Recalculate standing
			$this->plugin->standing->calculate_standing_prepare( $match_id, ( $game_data['competition_id'] ?? 0 ), ( $game_data['group_id'] ?? 0 ) );
		}
	}

	/**
	 * Method saves player statistics into DB.
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
	 *
	 * @param array $match_data
	 * @param array $game_events
	 *
	 * @since 0.5.0 (2018-03-10)
	 * @return bool
	 */
	public function save_player_statistics( array $match_data, array $game_events ): bool {

		global $wpdb;

		// Get match duration
		// @since 0.7.5
		$minutes_full  = intval( get_post_meta( $match_data['match_id'], '_anwpfl_duration_full', true ) ?: 90 );
		$minutes_extra = $minutes_full + intval( get_post_meta( $match_data['match_id'], '_anwpfl_duration_extra', true ) ?: 30 );

		$players = [];

		foreach ( [ 'home_line_up', 'away_line_up', 'home_subs', 'away_subs' ] as $value ) {
			if ( ! empty( $match_data[ $value ] ) ) {
				foreach ( explode( ',', $match_data[ $value ] ) as $player_id ) {
					if ( intval( $player_id ) ) {
						$club_id  = in_array( $value, [ 'home_line_up', 'home_subs' ], true ) ? $match_data['home_club'] : $match_data['away_club'];
						$time_out = 0;

						if ( in_array( $value, [ 'home_line_up', 'away_line_up' ], true ) ) {
							$time_out = ( $match_data['extra'] > 0 && $match_data['extra'] < 3 ? $minutes_extra : $minutes_full );
						}

						$appearance = in_array( $value, [ 'home_line_up', 'away_line_up' ], true ) ? 1 : 0;

						$players[ (int) $player_id ] = [
							'match_id'       => (int) $match_data['match_id'],
							'player_id'      => (int) $player_id,
							'club_id'        => (int) $club_id,
							'time_in'        => 0,
							'time_out'       => $time_out,
							'appearance'     => $appearance,
							'goals'          => 0,
							'goals_own'      => 0,
							'goals_penalty'  => 0,
							'goals_conceded' => 0,
							'assist'         => 0,
							'card_y'         => 0,
							'card_yr'        => 0,
							'card_r'         => 0,
						];
					}
				}
			}
		}

		/**
		 * Add player stats when line ups not added
		 *
		 * @since 0.7.3 (2018-09-23)
		 */
		foreach ( $game_events as $e ) {
			if ( ! empty( $e['type'] ) && in_array( $e['type'], [ 'goal', 'card' ], true ) ) {
				if ( intval( $e['player'] ) && empty( $players[ $e['player'] ] ) ) {
					$players[ (int) $e['player'] ] = [
						'match_id'       => (int) $match_data['match_id'],
						'player_id'      => (int) $e['player'],
						'club_id'        => (int) $e['club'],
						'time_in'        => 0,
						'time_out'       => 0,
						'appearance'     => 0,
						'goals'          => 0,
						'goals_own'      => 0,
						'goals_penalty'  => 0,
						'goals_conceded' => 0,
						'assist'         => 0,
						'card_y'         => 0,
						'card_yr'        => 0,
						'card_r'         => 0,
					];
				}
			}
		}

		if ( empty( $players ) || ! is_array( $players ) ) {
			return false;
		}

		$mins_goals_home = [];
		$mins_goals_away = [];

		// Parse all events
		foreach ( $game_events as $e ) {
			if ( ! empty( $e['type'] ) && in_array( $e['type'], [ 'goal', 'card', 'substitute' ], true ) ) {

				switch ( $e['type'] ) {
					case 'goal':
						if ( ! empty( $e['player'] ) && ! empty( $players[ $e['player'] ] ) ) {

							if ( 'yes' === $e['ownGoal'] ) {
								$players[ $e['player'] ]['goals_own'] ++;
							} else {
								$players[ $e['player'] ]['goals'] ++;

								if ( 'yes' === $e['fromPenalty'] ) {
									$players[ $e['player'] ]['goals_penalty'] ++;
								}
							}
						}

						if ( ! empty( $e['assistant'] ) && ! empty( $players[ $e['assistant'] ] ) ) {
							$players[ $e['assistant'] ]['assist'] ++;
						}

						if ( $e['minute'] > 0 ) {
							if ( (int) $e['club'] === (int) $match_data['home_club'] ) {
								$mins_goals_home[] = $e['minute'];
							} elseif ( (int) $e['club'] === (int) $match_data['away_club'] ) {
								$mins_goals_away[] = $e['minute'];
							}
						}

						break;

					case 'card':
						if ( ! empty( $e['player'] ) && ! empty( $players[ $e['player'] ] ) && in_array( $e['card'], [ 'y', 'yr', 'r' ], true ) ) {
							$players[ $e['player'] ][ 'card_' . sanitize_key( $e['card'] ) ] ++;
						}

						// Reduce out time on red card
						// --
						// Fixed situation on getting red card on the bench
						// @since 0.6.5 (2018-08-17)
						if ( in_array( $e['card'], [ 'yr', 'r' ], true ) && ! empty( $players[ $e['player'] ] ) && ! in_array( $players[ $e['player'] ]['appearance'], [ 4, 2 ], true ) ) {
							$players[ $e['player'] ]['time_out'] = $e['minute'];
						}

						break;

					case 'substitute':
						// phpcs:disable WordPress.NamingConventions
						// IN
						if ( ! empty( $e['player'] ) && ! empty( $players[ $e['player'] ] ) && intval( $e['minute'] ) ) {
							$players[ $e['player'] ]['time_in']    = (int) $e['minute'];
							$players[ $e['player'] ]['appearance'] = 3;
							$players[ $e['player'] ]['time_out']   = $match_data['extra'] > 0 && $match_data['extra'] < 3 ? $minutes_extra : $minutes_full;

							if ( $minutes_full === (int) $players[ $e['player'] ]['time_in'] && $match_data['extra'] < 1 ) {
								$players[ $e['player'] ]['time_out'] = $minutes_full + 1;
							}
						}

						// OUT
						// info - appearance (0 - none; 1 - full match, 2 -subs out, 3 - subs in, 4 - subs in-out)
						if ( ! empty( $e['playerOut'] ) && ! empty( $players[ $e['playerOut'] ] ) && intval( $e['minute'] ) ) {
							$players[ $e['playerOut'] ]['time_out']   = (int) $e['minute'];
							$players[ $e['playerOut'] ]['appearance'] = $players[ $e['playerOut'] ]['time_in'] > 0 ? 4 : 2;
						}
						// phpcs:enable

						break;
				}
			}
		}

		// goals_conceded for goalkeepers
		$home_goalkeepers = anwp_fl()->player->filter_goalkeepers_from_squad( $match_data['home_line_up'], $match_data['home_subs'] );
		$away_goalkeepers = anwp_fl()->player->filter_goalkeepers_from_squad( $match_data['away_line_up'], $match_data['away_subs'] );

		foreach ( $home_goalkeepers as $h_p ) {
			foreach ( $mins_goals_away as $minute ) {
				if ( ! empty( $players[ $h_p ] ) && $players[ $h_p ]['time_in'] <= $minute && $players[ $h_p ]['time_out'] >= $minute ) {
					$players[ $h_p ]['goals_conceded'] ++;
				}
			}
		}

		foreach ( $away_goalkeepers as $a_p ) {
			foreach ( $mins_goals_home as $minute ) {
				if ( ! empty( $players[ $a_p ] ) && $players[ $a_p ]['time_in'] <= $minute && $players[ $a_p ]['time_out'] >= $minute ) {
					$players[ $a_p ]['goals_conceded'] ++;
				}
			}
		}

		/*
		|--------------------------------------------------------------------
		| Save data to DB
		|--------------------------------------------------------------------
		*/
		// Get existing records to use update insteadof insert
		$saved_records = $wpdb->get_col(
			$wpdb->prepare(
				"
					SELECT CONCAT( club_id, '-', player_id )
					FROM $wpdb->anwpfl_players
					WHERE match_id = %d
				",
				$match_data['match_id']
			)
		);

		$posted_records = [];

		foreach ( $players as $player_data ) {
			if ( ! absint( $player_data['player_id'] ) ) {
				continue;
			}

			$record_slug = $player_data['club_id'] . '-' . $player_data['player_id'];

			if ( in_array( $record_slug, $saved_records, true ) ) {
				$wpdb->update(
					$wpdb->anwpfl_players,
					$player_data,
					[
						'match_id'  => $player_data['match_id'],
						'player_id' => $player_data['player_id'],
						'club_id'   => $player_data['club_id'],
					]
				);
			} else {
				$wpdb->replace( $wpdb->anwpfl_players, $player_data );
			}

			$posted_records[] = $record_slug;
		}

		/*
		|--------------------------------------------------------------------
		| Remove missing players
		|--------------------------------------------------------------------
		*/
		$remove_players = array_diff( $saved_records, $posted_records );

		if ( ! empty( $remove_players ) ) {
			foreach ( $remove_players as $remove_slug ) {
				list( $del_club_id, $del_player_id ) = explode( '-', $remove_slug );

				$wpdb->delete(
					$wpdb->anwpfl_players,
					[
						'match_id'  => $match_data['match_id'],
						'player_id' => $del_player_id,
						'club_id'   => $del_club_id,
					]
				);
			}
		}

		return true;
	}

	/**
	 * Get match data.
	 *
	 * @param $match_id
	 *
	 * @since      0.6.1
	 * @return array/object
	 * @deprecated will be removed in v1.0
	 */
	public function get_match_data( $match_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT *
				FROM {$wpdb->prefix}anwpfl_matches
				WHERE match_id = %d
				",
				$match_id
			)
		);
	}

	/**
	 * Get game data.
	 *
	 * @param int $game_id
	 *
	 * @since 0.16.0
	 * @return array [
	 *  "match_id" => 461,
	 *  "competition_id" => 89,
	 *  "main_stage_id" => 89,
	 *  "group_id" => 1,
	 *  "season_id" => 10,
	 *  "league_id" => 11,
	 *  "home_club" => 59,
	 *  "away_club" => 79,
	 *  "kickoff" => "2023-05-27 21:00:00",
	 *  "finished" => 1,
	 *  "extra" => 0,
	 *  "attendance" => 0,
	 *  "aggtext" => "",
	 *  "stadium_id" => 57,
	 *  "match_week" => 37,
	 *  "priority" => 0,
	 *  "home_goals" => 1,
	 *  "away_goals" => 1,
	 *  "home_goals_half" => 1,
	 *  "away_goals_half" => 1,
	 *  "home_goals_ft" => 1,
	 *  "away_goals_ft" => 1,
	 *  "home_goals_e" => 0,
	 *  "away_goals_e" => 0,
	 *  "home_goals_p" => 0,
	 *  "away_goals_p" => 0,
	 *  "home_cards_y" => 1,
	 *  "away_cards_y" => 3,
	 *  "home_cards_yr" => 0,
	 *  "away_cards_yr" => 0,
	 *  "home_cards_r" => 0,
	 *  "away_cards_r" => 0,
	 *  "home_corners" => 6,
	 *  "away_corners" => 3,
	 *  "home_fouls" => 18,
	 *  "away_fouls" => 13,
	 *  "home_offsides" => 4,
	 *  "away_offsides" => 1,
	 *  "home_possession" => 61,
	 *  "away_possession" => 39,
	 *  "home_shots" => 10,
	 *  "away_shots" => 13,
	 *  "home_shots_on_goal" => 3,
	 *  "away_shots_on_goal" => 2,
	 *  "special_status" => "",
	 *  "kickoff_gmt" => "0000-00-00 00:00:00",
	 *  "game_status" => 1,
	 *  "match_events" => "[{\"type\":\"card\",\"club\":\"79\",\"minute\":2,\"minuteAdd\":\"\",\"player\":\"1599\",\"assistant\":\"\",\"playerOut\":\"\",\"card\":\"y\",\"ownGoal\":\"\",\"fromPenalty\":\"\",\"comment\":\"Tripping\"}]",
	 *  "stats_home_club" => "{\"11\":5,\"12\":2,\"13\":7,\"14\":3,\"15\":1,\"16\":477,\"17\":407}",
	 *  "stats_away_club" => "{\"11\":7,\"12\":4,\"13\":4,\"14\":9,\"15\":3,\"16\":302,\"17\":240}",
	 *  "coach_home" => "1313",
	 *  "coach_away" => "1379",
	 *  "referee" => "33965",
	 *  "home_line_up" => "",
	 *  "away_line_up" => "",
	 *  "home_subs" => "",
	 *  "away_subs" => "",
	 *  "custom_numbers" => "",
	 *  "captain_home" => "",
	 *  "captain_away" => "",
	 *  ]
	 */
	public function get_game_data( int $game_id ): array {
		global $wpdb;

		static $games = [];

		if ( ! empty( $games[ $game_id ] ) ) {
			return $games[ $game_id ];
		}

		$games[ $game_id ] = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT a.*, b.home_line_up, b.away_line_up, b.home_subs, b.away_subs, b.custom_numbers, b.captain_home, b.captain_away
				FROM {$wpdb->prefix}anwpfl_matches a
				LEFT JOIN {$wpdb->prefix}anwpfl_lineups b ON b.match_id = a.match_id
				WHERE a.match_id = %d
				",
				$game_id
			),
			ARRAY_A
		) ? : [];

		return $games[ $game_id ];
	}

	/**
	 * Get games by IDs
	 *
	 * @param array $ids
	 *
	 * @since 0.16.0
	 * @return array
	 */
	public function get_game_data_by_ids( array $ids ):array {
		global $wpdb;

		if ( empty( $ids ) ) {
			return [];
		}

		$placeholders = array_fill( 0, count( $ids ), '%s' );
		$format       = implode( ', ', $placeholders );

		$games = $wpdb->get_results(
		// phpcs:disable
			$wpdb->prepare(
				"
					SELECT *
					FROM $wpdb->anwpfl_matches
					WHERE match_id IN ({$format})
					",
				$ids
			)
		// phpcs:enable
		);

		$output = [];

		foreach ( $games as $game ) {
			$output[ absint( $game->match_id ) ] = (array) $game;
		}

		return $output;
	}

	/**
	 * Get match data.
	 *
	 * @param $data
	 * @param $args
	 * @param $context
	 * @param $layout
	 *
	 * @since 0.6.5
	 * @return array
	 */
	public function prepare_match_data_to_render( $data, $args = [], $context = 'shortcode', $layout = 'slim' ) {
		$args = wp_parse_args(
			$args,
			[
				'show_club_logos'     => 1,
				'show_match_datetime' => 1,
				'club_links'          => 1,
			]
		);

		$data = (array) $data;

		$game_data = wp_parse_args(
			$data,
			[
				'aggtext'         => '',
				'attendance'      => '',
				'away_club'       => '',
				'away_goals'      => '',
				'away_goals_e'    => '',
				'away_goals_ft'   => '',
				'away_goals_half' => '',
				'away_goals_p'    => '',
				'away_line_up'    => '',
				'away_subs'       => '',
				'captain_away'    => '',
				'captain_home'    => '',
				'coach_away'      => '',
				'coach_home'      => '',
				'competition_id'  => '',
				'custom_numbers'  => '',
				'extra'           => '',
				'events'          => [],
				'finished'        => '',
				'game_status'     => 1,
				'group_id'        => '',
				'home_club'       => '',
				'home_goals'      => '',
				'home_goals_e'    => '',
				'home_goals_ft'   => '',
				'home_goals_half' => '',
				'home_goals_p'    => '',
				'home_line_up'    => '',
				'home_subs'       => '',
				'kickoff'         => '',
				'kickoff_gmt'     => '',
				'league_id'       => '',
				'main_stage_id'   => '',
				'match_id'        => '',
				'parsed_events'   => [],
				'match_week'      => '',
				'permalink'       => '',
				'priority'        => 0,
				'referee'         => '',
				'season_id'       => '',
				'special_status'  => '',
				'stadium_id'      => '',
				'stage_title'     => '',
				'stats_away_club' => '',
				'stats_home_club' => '',
			]
		);

		if ( ! empty( $data['match_events'] ) && ! empty( json_decode( $data['match_events'] ) ) ) {
			$game_data['parsed_events'] = anwp_fl()->helper->parse_match_events( json_decode( $data['match_events'] ) );
			$game_data['events']        = json_decode( $data['match_events'] );
		}

		if ( ! empty( $game_data['custom_numbers'] ) && ! empty( json_decode( $game_data['custom_numbers'] ) ) ) {
			$game_data['custom_numbers'] = json_decode( $game_data['custom_numbers'] );
		}

		// Club logos
		$club_home_logo = '';
		$club_away_logo = '';

		if ( AnWP_Football_Leagues::string_to_bool( $args['show_club_logos'] ) ) {
			$club_home_logo = anwp_fl()->club->get_club_logo_by_id( $game_data['home_club'], 'slim' === $layout );
			$club_away_logo = anwp_fl()->club->get_club_logo_by_id( $game_data['away_club'], 'slim' === $layout );

			if ( empty( $club_home_logo ) ) {
				$club_home_logo = anwp_fl()->helper->get_default_club_logo();
			}

			if ( empty( $club_away_logo ) ) {
				$club_away_logo = anwp_fl()->helper->get_default_club_logo();
			}
		}

		// Stage title
		if ( absint( $game_data['main_stage_id'] ) !== absint( $game_data['competition_id'] ) ) {
			$game_data['stage_title'] = anwp_fl()->competition->get_competition( absint( $game_data['competition_id'] ) )->stage_title;
		}

		$game_data['show_match_datetime'] = AnWP_Football_Leagues::string_to_bool( $args['show_match_datetime'] );
		$game_data['context']             = $context;
		$game_data['permalink']           = $game_data['permalink'] ? : get_permalink( $game_data['match_id'] );
		$game_data['club_home_logo']      = $club_home_logo;
		$game_data['club_away_logo']      = $club_away_logo;
		$game_data['club_home_link']      = anwp_fl()->club->get_club_link_by_id( $game_data['home_club'] );
		$game_data['club_away_link']      = anwp_fl()->club->get_club_link_by_id( $game_data['away_club'] );
		$game_data['kickoff_c']           = date_i18n( 'c', strtotime( $game_data['kickoff'] ) );
		$game_data['kickoff_orig']        = isset( $game_data['kickoff_orig'] ) ? date_i18n( 'c', strtotime( $game_data['kickoff_orig'] ) ) : date_i18n( 'c', strtotime( $game_data['kickoff'] ) );
		$game_data['club_links']          = AnWP_Football_Leagues::string_to_bool( $args['club_links'] );
		$game_data['club_home_title']     = anwp_fl()->club->get_club_title_by_id( $game_data['home_club'] );
		$game_data['club_away_title']     = anwp_fl()->club->get_club_title_by_id( $game_data['away_club'] );

		// Date and time formats
		$custom_date_format = anwp_fl()->get_option_value( 'custom_match_date_format' );
		$custom_time_format = anwp_fl()->get_option_value( 'custom_match_time_format' );

		$game_data['match_date'] = date_i18n( $custom_date_format ?: 'j M Y', strtotime( $game_data['kickoff'] ) );
		$game_data['match_time'] = date( $custom_time_format ?: get_option( 'time_format' ), strtotime( $game_data['kickoff'] ) );

		// Set Club Abbr
		$game_data['club_home_abbr'] = anwp_fl()->club->get_club_abbr_by_id( $game_data['home_club'] ) ?: $game_data['club_home_title'];
		$game_data['club_away_abbr'] = anwp_fl()->club->get_club_abbr_by_id( $game_data['away_club'] ) ?: $game_data['club_away_title'];

		return $game_data;
	}

	/**
	 * Get event name from event object
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
	 *
	 * @param object $event
	 *
	 * @since 0.7.3
	 * @return string
	 */
	public function get_event_name_by_type( $event ): string {

		$name = '';

		if ( 'goal' === $event->type ) {
			$name = esc_html( AnWPFL_Text::get_value( 'match__event__goal', _x( 'Goal', 'match event', 'anwp-football-leagues' ) ) );

			if ( 'yes' === $event->ownGoal ) { // phpcs:ignore WordPress.NamingConventions
				$name = esc_html( AnWPFL_Text::get_value( 'match__event__goal_own', _x( 'Goal (own)', 'match event', 'anwp-football-leagues' ) ) );
			} elseif ( 'yes' === $event->fromPenalty ) { // phpcs:ignore WordPress.NamingConventions
				$name = esc_html( AnWPFL_Text::get_value( 'match__event__goal_from_penalty', _x( 'Goal (from penalty)', 'match event', 'anwp-football-leagues' ) ) );
			}
		} elseif ( 'substitute' === $event->type ) {
			$name = esc_html( AnWPFL_Text::get_value( 'match__event__substitute', _x( 'Substitute', 'match event', 'anwp-football-leagues' ) ) );
		} elseif ( 'card' === $event->type ) {
			$card_options = anwp_fl()->data->cards;
			$name         = $card_options[ $event->card ] ?? '';
		} elseif ( 'missed_penalty' === $event->type ) {
			$name = esc_html( AnWPFL_Text::get_value( 'match__event__missed_penalty', _x( 'Missed Penalty', 'match event', 'anwp-football-leagues' ) ) );
		}

		return $name;
	}

	/**
	 * Generate Match title
	 *
	 * @param array  $data
	 * @param string $home_club
	 * @param string $away_club
	 *
	 * @since 0.10.14
	 * @return string
	 */
	public function get_match_title_generated( array $data, string $home_club, string $away_club ): string {
		// %club_home% - %club_away% - %scores_home% - %scores_away% - %competition% - %kickoff%
		$match_title = trim( AnWPFL_Options::get_value( 'match_title_generator' ) );

		if ( false !== mb_strpos( $match_title, '%club_home%' ) ) {
			$match_title = str_ireplace( '%club_home%', $home_club, $match_title );
		}

		if ( false !== mb_strpos( $match_title, '%club_away%' ) ) {
			$match_title = str_ireplace( '%club_away%', $away_club, $match_title );
		}

		if ( false !== mb_strpos( $match_title, '%scores_home%' ) || false !== mb_strpos( $match_title, '%scores_away%' ) ) {
			$scores_home = '?';
			$scores_away = '?';

			if ( absint( $data['finished'] ) ) {
				$scores_home = absint( $data['home_goals'] );
				$scores_away = absint( $data['away_goals'] );
			}

			$match_title = str_ireplace( '%scores_home%', $scores_home, $match_title );
			$match_title = str_ireplace( '%scores_away%', $scores_away, $match_title );
		}

		if ( false !== mb_strpos( $match_title, '%competition%' ) ) {
			$competition_title = anwp_fl()->competition->get_competition( $data['competition_id'] )->title;
			$match_title       = str_ireplace( '%competition%', $competition_title, $match_title );
		}

		if ( false !== mb_strpos( $match_title, '%kickoff%' ) ) {
			$custom_date_format = anwp_fl()->get_option_value( 'custom_match_date_format' );
			$match_title        = str_ireplace( '%kickoff%', date_i18n( $custom_date_format ?: 'Y-m-d', get_date_from_gmt( $data['kickoff'], 'U' ) ), $match_title );
		}

		return sanitize_text_field( $match_title );
	}

	/**
	 * Generate Match slug
	 *
	 * @param array   $data
	 * @param string  $home_club
	 * @param string  $away_club
	 * @param WP_Post $post
	 *
	 * @since 0.10.15
	 * @return string
	 */
	public function get_match_slug_generated( array $data, string $home_club, string $away_club, WP_Post $post ): string {

		if ( 'slug' === AnWPFL_Options::get_value( 'match_slug_generated_with' ) ) {
			$match_slug = [ get_post_field( 'post_name', $data['home_club'] ), get_post_field( 'post_name', $data['away_club'] ) ];
		} else {
			$match_slug = [ $home_club, $away_club ];
		}

		if ( ! empty( $data['kickoff'] ) && '0000-00-00 00:00:00' !== $data['kickoff'] ) {
			$match_slug[] = explode( ' ', $data['kickoff'] )[0];
		}

		$match_slug = implode( ' ', $match_slug );

		/**
		 * Filters a match slug before save.
		 *
		 * @param string  $match_title Match slug to be returned.
		 * @param string  $home_club   Home club title.
		 * @param string  $away_club   Away club title.
		 * @param WP_Post $post        Match WP_Post object
		 * @param array   $data        Match data
		 *
		 * @since 0.5.3
		 *
		 */
		$match_slug = apply_filters( 'anwpfl/match/slug_to_save', $match_slug, $home_club, $away_club, $post, $data );

		// return slug unique
		return wp_unique_post_slug( sanitize_title_with_dashes( $match_slug ), $post->ID, $post->post_status, $post->post_type, $post->post_parent );
	}

	/**
	 * Get all Matches with video
	 *
	 * @return array
	 * @since 0.10.23
	 */
	public function get_matches_with_video() {

		static $ids = null;

		if ( null === $ids ) {
			global $wpdb;

			$ids = $wpdb->get_col(
				"
				SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = '_anwpfl_video_source' AND meta_value != ''
				"
			);

			$ids = array_unique( array_map( 'absint', $ids ) );
		}

		return $ids;
	}

	/**
	 * Get match outcome label
	 *
	 * @param object $data
	 * @param string $css_class
	 *
	 * @since 0.10.23
	 * @return string
	 */
	public function get_match_outcome_label( $data, string $css_class = '' ) {

		$outcome_id = absint( $data['outcome_id'] );
		$home_id    = absint( $data['home_club'] );
		$away_id    = absint( $data['away_club'] );

		if ( ! absint( $data['finished'] ) || ( $outcome_id !== $home_id && $outcome_id !== $away_id ) ) {
			return '<span class="anwp-fl-outcome-label ' . esc_attr( $css_class ) . '"></span>';
		}

		$labels_l10n = anwp_fl()->data->get_series();

		$result_class = 'anwp-bg-success';
		$result_code  = $labels_l10n['w'] ?? 'w';

		if ( $data['home_goals'] === $data['away_goals'] ) {
			$result_class = 'anwp-bg-warning';
			$result_code  = $labels_l10n['d'] ?? 'd';
		} elseif ( ( $home_id === $outcome_id && $data['home_goals'] < $data['away_goals'] ) || ( $outcome_id === $away_id && $data['home_goals'] > $data['away_goals'] ) ) {
			$result_class = 'anwp-bg-danger';
			$result_code  = $labels_l10n['l'] ?? 'l';
		}

		return '<span class="anwp-fl-outcome-label ' . $result_class . ' ' . esc_attr( $css_class ) . '">' . $result_code . '</span>';
	}

	/**
	 * Get match outcome label
	 *
	 * @param array $data
	 * @param bool  $is_home
	 *
	 * @since 0.16.11
	 * @return string
	 */
	public function get_outcome_color( array $data, bool $is_home = true ): string {

		$outcome_id = absint( $data['outcome_id'] );
		$home_id    = absint( $data['home_club'] );
		$away_id    = absint( $data['away_club'] );

		if ( ! absint( $data['finished'] ) ) {
			return '';
		}

		if ( ( $is_home && $outcome_id === $home_id ) || ( ! $is_home && $outcome_id === $away_id ) ) {
			$result_class = 'anwp-bg-success-light';

			if ( $data['home_goals'] === $data['away_goals'] ) {
				$result_class = 'anwp-bg-warning-light';
			} elseif ( ( $home_id === $outcome_id && $data['home_goals'] < $data['away_goals'] ) || ( $outcome_id === $away_id && $data['home_goals'] > $data['away_goals'] ) ) {
				$result_class = 'anwp-bg-danger-light';
			}

			return $result_class;
		}

		return '';
	}

	/**
	 * Get match outcome label
	 *
	 * @param array $data
	 * @param int   $match_id
	 *
	 * @since 0.11.4
	 * @return bool
	 */
	public function save_missing_players( $data, $match_id ) {

		global $wpdb;

		if ( ! absint( $match_id ) ) {
			return false;
		}

		$this->remove_match_missing_players( $match_id );

		/*
		|--------------------------------------------------------------------
		| Prepare data for save
		|--------------------------------------------------------------------
		*/
		$table = $wpdb->prefix . 'anwpfl_missing_players';

		foreach ( $data as $missing_player ) {

			if ( ! absint( $missing_player->player ) ) {
				continue;
			}

			// Prepare data to insert
			$data = [
				'reason'    => $missing_player->reason,
				'match_id'  => $match_id,
				'club_id'   => absint( $missing_player->club ),
				'player_id' => absint( $missing_player->player ),
				'comment'   => sanitize_textarea_field( $missing_player->comment ),
			];

			// Insert data to DB
			$wpdb->insert( $table, $data );
		}

		return true;
	}

	/**
	 * Remove match missing players.
	 *
	 * @param int $match_id
	 *
	 * @since 0.11.4
	 * @return bool
	 */
	public function remove_match_missing_players( int $match_id ): bool {
		global $wpdb;

		if ( ! absint( $match_id ) ) {
			return false;
		}

		$table = $wpdb->prefix . 'anwpfl_missing_players';

		return $wpdb->delete( $table, [ 'match_id' => $match_id ] );
	}

	/**
	 * Get Missed games by player ID and season ID.
	 *
	 * @param $player_id
	 * @param $season_id
	 *
	 * @return array
	 * @since 0.11.4
	 */
	public function get_player_missed_games_by_season( $player_id, $season_id ) {
		global $wpdb;

		if ( ! absint( $player_id ) || ( ! absint( $season_id ) && 'all' !== $season_id ) ) {
			return [];
		}

		// Get games with custom outcome
		$query = "
		SELECT p.match_id, p.player_id, p.club_id, p.reason, p.comment, m.kickoff, m.competition_id, m.main_stage_id, m.home_club, m.away_club, m.home_goals, m.away_goals
		FROM {$wpdb->prefix}anwpfl_missing_players p
		LEFT JOIN {$wpdb->prefix}anwpfl_matches m ON p.match_id = m.match_id
		";

		if ( 'all' === $season_id ) {
			$query .= $wpdb->prepare( ' WHERE p.player_id = %d', $player_id );
		} else {
			$query .= $wpdb->prepare( ' WHERE m.season_id = %d AND p.player_id = %d', $season_id, $player_id );
		}

		$query .= ' ORDER BY m.kickoff DESC';

		$matches = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL

		if ( empty( $matches ) ) {
			return [];
		}

		$data = [];

		// Get competition ids from matches
		$competition_ids = [];

		foreach ( $matches as $match ) {
			$competition_ids[] = $match->main_stage_id;
		}

		// Get competition data
		$competitions = get_posts(
			[
				'numberposts'      => - 1,
				'post_type'        => 'anwp_competition',
				'suppress_filters' => false,
				'post_status'      => [ 'publish', 'stage_secondary' ],
				'include'          => $competition_ids,
				'orderby'          => 'post__in',
			]
		);

		/** @var WP_Post $competition */
		foreach ( $competitions as $competition ) {

			if ( 'secondary' !== get_post_meta( $competition->ID, '_anwpfl_multistage', true ) ) {
				$data[ $competition->ID ] = [
					'title'   => $competition->post_title,
					'id'      => $competition->ID,
					'matches' => [],
					'logo'    => get_post_meta( $competition->ID, '_anwpfl_logo', true ),
					'order'   => get_post_meta( $competition->ID, '_anwpfl_competition_order', true ),
				];
			}
		}

		// Add matches to competitions
		foreach ( $matches as $match ) {
			$competition_index = (int) $match->main_stage_id;

			if ( isset( $data[ $competition_index ] ) ) {
				$data[ $competition_index ]['matches'][] = $match;
			}
		}

		usort(
			$data,
			function ( $a, $b ) {
				return $a['order'] - $b['order'];
			}
		);

		return $data;
	}

	/**
	 * Get Missed Players for the Game
	 *
	 * @param $game_id
	 *
	 * @return array
	 * @since 0.13.7
	 */
	public function get_game_missed_players( $game_id ) {
		global $wpdb;

		if ( ! absint( $game_id ) ) {
			return [];
		}

		// Get games with custom outcome
		$query = $wpdb->prepare(
			"
			SELECT player_id as player, club_id as club, reason, comment
			FROM {$wpdb->prefix}anwpfl_missing_players
			WHERE match_id = %d
			",
			$game_id
		);

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get game temporary players
	 *
	 * @param $game_id
	 *
	 * @return array
	 * @since 0.14.2
	 */
	public function get_temp_players( $game_id ) {

		static $players = [];

		if ( ! absint( $game_id ) ) {
			return [];
		}

		if ( ! isset( $players[ $game_id ] ) ) {

			$players[ $game_id ] = [];
			$game_players        = get_post_meta( $game_id, '_anwpfl_match_temp_players', true ) ? json_decode( get_post_meta( $game_id, '_anwpfl_match_temp_players', true ) ) : [];

			if ( ! empty( $game_players ) ) {
				foreach ( $game_players as $player ) {
					$players[ $game_id ][ $player->id ] = $player;
				}
			}
		}

		return $players[ $game_id ];
	}

	/**
	 * Get Post ID by External id
	 *
	 * @param $external_id
	 *
	 * @return string|null
	 * @since 0.14.2
	 */
	public function get_match_id_by_external_id( $external_id ) {

		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = '_anwpfl_match_external_id' AND meta_value = %s
				",
				$external_id
			)
		);
	}


	/**
	 * Get "load more" data
	 *
	 * @param $data
	 *
	 * @return string
	 * @since 0.15.0
	 */
	public function get_serialized_load_more_data( $data ) {

		$default_data = [
			'competition_id'        => '',
			'show_secondary'        => 0,
			'season_id'             => '',
			'league_id'             => '',
			'group_id'              => '',
			'type'                  => '',
			'limit'                 => 0,
			'date_from'             => '',
			'date_to'               => '',
			'stadium_id'            => '',
			'filter_by'             => '',
			'filter_values'         => '',
			'filter_by_clubs'       => '',
			'filter_by_matchweeks'  => '',
			'days_offset'           => '',
			'days_offset_to'        => '',
			'sort_by_date'          => '',
			'sort_by_matchweek'     => '',
			'club_links'            => true,
			'priority'              => '',
			'class'                 => '',
			'group_by'              => '',
			'group_by_header_style' => '',
			'show_club_logos'       => 1,
			'show_match_datetime'   => true,
			'competition_logo'      => '1',
			'exclude_ids'           => '',
			'include_ids'           => '',
			'outcome_id'            => '',
			'home_club'             => '',
			'away_club'             => '',
			'layout'                => 'slim',
			'header_class'          => '',
		];

		$options    = wp_parse_args( $data, $default_data );
		$output     = array_intersect_key( $options, $default_data );
		$min_output = [];

		foreach ( $output as $key => $value ) {
			if ( ! is_null( $value ) && '' !== $value ) {
				$min_output[ $key ] = $value;
			}
		}

		return wp_json_encode( $min_output );
	}

	/**
	 * Handle ajax request and provide games to load.
	 *
	 * @since 0.15.0
	 */
	public function load_more_games( WP_REST_Request $request ) {

		$args        = $request->get_params();
		$post_loaded = isset( $args['loaded'] ) ? absint( $args['loaded'] ) : 0;
		$post_qty    = isset( $args['qty'] ) ? absint( $args['qty'] ) : 0;

		// Parse with default values
		$args = (object) wp_parse_args(
			wp_unslash( $args ),
			[
				'competition_id'        => '',
				'show_secondary'        => 0,
				'season_id'             => '',
				'league_id'             => '',
				'group_id'              => '',
				'type'                  => '',
				'limit'                 => 0,
				'date_from'             => '',
				'date_to'               => '',
				'stadium_id'            => '',
				'filter_by'             => '',
				'filter_values'         => '',
				'filter_by_clubs'       => '',
				'filter_by_matchweeks'  => '',
				'days_offset'           => '',
				'days_offset_to'        => '',
				'sort_by_date'          => '',
				'sort_by_matchweek'     => '',
				'club_links'            => true,
				'priority'              => '',
				'class'                 => '',
				'group_by'              => '',
				'group_by_header_style' => '',
				'show_club_logos'       => 1,
				'show_match_datetime'   => true,
				'competition_logo'      => '1',
				'outcome_id'            => '',
				'exclude_ids'           => '',
				'include_ids'           => '',
				'home_club'             => '',
				'away_club'             => '',
				'layout'                => 'slim',
				'header_class'          => '',
			]
		);

		$data = [];

		foreach ( $args as $arg_key => $arg_value ) {
			$data[ $arg_key ] = sanitize_text_field( $arg_value );
		}

		$data['limit']  = $post_qty + 1;
		$data['offset'] = $post_loaded;

		// Get games
		$games = anwp_fl()->competition->tmpl_get_competition_matches_extended( $data );

		// Check next time "load more"
		$next_load = count( $games ) > $post_qty;

		if ( $next_load ) {
			array_pop( $games );
		}

		$group_current = isset( $args->group ) ? sanitize_text_field( $args->group ) : '';

		// Start output
		ob_start();

		foreach ( $games as $ii => $game ) {

			if ( '' !== $data['group_by'] ) {

				$group_text = '';

				// Check current group by value
				if ( 'stage' === $args->group_by && $group_current !== $game->competition_id ) {
					$group_text    = get_post_meta( $game->competition_id, '_anwpfl_stage_title', true );
					$group_current = $game->competition_id;
				} elseif ( 'competition' === $args->group_by && $group_current !== $game->competition_id ) {
					$group_text    = anwp_fl()->competition->get_competition_title( $game->competition_id );
					$group_current = $game->competition_id;
				} elseif ( 'matchweek' === $args->group_by && $group_current !== $game->match_week && '0' !== $game->match_week ) {
					$group_text    = anwp_fl()->competition->tmpl_get_matchweek_round_text( $game->match_week, $game->competition_id );
					$group_current = $game->match_week;
				} elseif ( 'day' === $args->group_by ) {
					$day_to_compare = date( 'Y-m-d', strtotime( $game->kickoff ) );

					if ( $day_to_compare !== $group_current ) {
						$group_text    = '0000-00-00 00:00:00' === $game->kickoff ? '&nbsp;' : date_i18n( anwp_fl()->get_option_value( 'custom_match_date_format' ) ?: 'j M Y', strtotime( $game->kickoff ) );
						$group_current = $day_to_compare;
					}
				} elseif ( 'month' === $args->group_by ) {
					$month_to_compare = date( 'Y-m', strtotime( $game->kickoff ) );

					if ( $month_to_compare !== $group_current ) {
						$group_text    = '0000-00-00 00:00:00' === $game->kickoff ? '&nbsp;' : date_i18n( 'M Y', strtotime( $game->kickoff ) );
						$group_current = $month_to_compare;
					}
				}

				if ( $group_text ) {
					if ( 'secondary' === $args->group_by_header_style ) {
						anwp_fl()->load_partial(
							[
								'text'  => esc_html( $group_text ),
								'class' => ' mt-4 mb-1',
							],
							'general/subheader'
						);
					} else {
						anwp_fl()->load_partial(
							[
								'text'  => esc_html( $group_text ),
								'class' => ' mt-4',
							],
							'general/header'
						);
					}
				}
			}

			// Get match data to render
			$game_data = anwp_fl()->match->prepare_match_data_to_render( $game, $args );

			$game_data['competition_logo'] = $args->competition_logo;
			$game_data['outcome_id']       = $args->outcome_id;

			anwp_fl()->load_partial( $game_data, 'match/match', $args->layout ?: 'slim' );
		}

		$html_output = ob_get_clean();

		wp_send_json_success(
			[
				'html'   => $html_output,
				'next'   => $next_load,
				'group'  => $group_current,
				'offset' => $post_loaded + count( $games ),
			]
		);
	}

	/**
	 * Handle ajax request and provide games to load.
	 */
	public function fix_game_status( WP_REST_Request $request ) {

		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_invalid', esc_html__( 'You have no rights to edit this Match.', 'anwp-football-leagues' ), [ 'status' => 400 ] );
		}

		$args = $request->get_params();

		$competition_id = absint( $args['stage_id'] ?? 0 );

		if ( ! absint( $competition_id ) ) {
			return new WP_Error( 'rest_invalid', esc_html__( 'Incorrect Stage ID', 'anwp-football-leagues' ), [ 'status' => 400 ] );
		}

		$game_status = get_post_meta( $competition_id, '_anwpfl_competition_status', true );
		$is_friendly = 'friendly' === $game_status;

		$games = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT SUM( CASE WHEN game_status = 1 THEN 1 ELSE 0 END ) as official, SUM( CASE WHEN ( game_status = 0 OR game_status = 2 ) THEN 1 ELSE 0 END ) as friendly
				FROM $wpdb->anwpfl_matches
				WHERE competition_id = %d
				GROUP BY competition_id
				",
				$competition_id
			),
			ARRAY_A
		);

		$number_of_incorrect_games = $is_friendly ? ( $games['official'] ?? 0 ) : ( $games['friendly'] ?? 0 );

		if ( empty( $number_of_incorrect_games ) ) {
			return new WP_Error( 'rest_invalid', esc_html__( 'Nothing to Fix', 'anwp-football-leagues' ), [ 'status' => 400 ] );
		}

		if ( $is_friendly ) {
			$wpdb->update(
				$wpdb->anwpfl_matches,
				[
					'game_status' => 0,
				],
				[
					'competition_id' => $competition_id,
					'game_status'    => 1,
				]
			);

		} else {
			$wpdb->update(
				$wpdb->anwpfl_matches,
				[
					'game_status' => 1,
				],
				[
					'competition_id' => $competition_id,
					'game_status'    => 0,
				]
			);

			$wpdb->update(
				$wpdb->anwpfl_matches,
				[
					'game_status' => 1,
				],
				[
					'competition_id' => $competition_id,
					'game_status'    => 2,
				]
			);
		}

		return rest_ensure_response( [ 'games' => ( anwp_fl()->competition_admin->get_stage_games( [ $competition_id ] )[ $competition_id ] ?? [] ) ] );
	}
}
