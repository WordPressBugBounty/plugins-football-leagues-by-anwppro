<?php
/**
 * AnWP Football Leagues :: Club.
 *
 * @since   0.1.0
 * @package AnWP_Football_Leagues
 */

require_once dirname( __FILE__ ) . '/../vendor/cpt-core/CPT_Core.php';

/**
 * AnWP Football Leagues :: Club post type class.
 *
 * @since 0.1.0
 *
 * @see   https://github.com/WebDevStudios/CPT_Core
 */
class AnWPFL_Club extends CPT_Core {

	/**
	 * Parent plugin class.
	 *
	 * @var AnWP_Football_Leagues
	 * @since  0.1.0
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 * Register Custom Post Types.
	 *
	 * See documentation in CPT_Core, and in wp-includes/post.php.
	 *
	 * @param  AnWP_Football_Leagues $plugin Main plugin object.
	 *
	 * @since  0.1.0
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		$permalink_structure = $plugin->options->get_permalink_structure();
		$permalink_slug      = empty( $permalink_structure['club'] ) ? 'club' : $permalink_structure['club'];

		// Register this cpt.
		parent::__construct(
			[ // array with Singular, Plural, and Registered name
				esc_html__( 'Club', 'anwp-football-leagues' ),
				esc_html__( 'Clubs', 'anwp-football-leagues' ),
				'anwp_club',
			],
			[
				'supports'            => [
					'title',
					'comments',
				],
				'show_in_menu'        => true,
				'rewrite'             => [ 'slug' => $permalink_slug ],
				'menu_position'       => 35,
				'show_in_rest'        => true,
				'rest_base'           => 'anwp_clubs',
				'menu_icon'           => 'dashicons-shield',
				'public'              => true,
				'exclude_from_search' => 'hide' === AnWPFL_Options::get_value( 'display_front_end_search_club' ),
				'labels'              => [
					'all_items'    => esc_html__( 'Clubs', 'anwp-football-leagues' ),
					'add_new'      => esc_html__( 'Add New Club', 'anwp-football-leagues' ),
					'add_new_item' => esc_html__( 'Add New Club', 'anwp-football-leagues' ),
					'edit_item'    => esc_html__( 'Edit Club', 'anwp-football-leagues' ),
					'new_item'     => esc_html__( 'New Club', 'anwp-football-leagues' ),
					'view_item'    => esc_html__( 'View Club', 'anwp-football-leagues' ),
				],
			]
		);
	}

	/**
	 * Filter CPT title entry placeholder text
	 *
	 * @param  string $title Original placeholder text
	 *
	 * @return string        Modified placeholder text
	 */
	public function title( $title ) {

		$screen = get_current_screen();
		if ( isset( $screen->post_type ) && $screen->post_type === $this->post_type ) {
			return esc_html__( 'Club Title', 'anwp-football-leagues' );
		}

		return $title;
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 0.1.0 Created
	 * @since 0.2.0 Added rest route
	 */
	public function hooks() {

		add_action( 'rest_api_init', [ $this, 'add_rest_routes' ] );

		// Create CMB2 Metabox
		add_action( 'cmb2_admin_init', [ $this, 'init_cmb2_metaboxes' ] );

		// Create & save Squad metabox
		add_action( 'load-post.php', [ $this, 'init_metaboxes' ] );
		add_action( 'load-post-new.php', [ $this, 'init_metaboxes' ] );
		add_action( 'save_post', [ $this, 'save_metabox' ], 10, 2 );

		add_action( 'restrict_manage_posts', [ $this, 'add_more_filters' ] );
		add_filter( 'pre_get_posts', [ $this, 'handle_custom_filter' ] );

		// Render Custom Content below
		add_action(
			'anwpfl/tmpl-club/after_wrapper',
			function ( $club ) {

				$content_below = get_post_meta( $club->ID, '_anwpfl_custom_content_below', true );

				if ( trim( $content_below ) ) {
					echo '<div class="anwp-b-wrap mt-4">' . do_shortcode( $content_below ) . '</div>';
				}
			}
		);
	}

	/**
	 * Fires before the Filter button on the Posts and Pages list tables.
	 *
	 * The Filter button allows sorting by date and/or category on the
	 * Posts list table, and sorting by date on the Pages list table.
	 *
	 * @param string $post_type The post type slug.
	 */
	public function add_more_filters( $post_type ) {

		if ( 'anwp_club' === $post_type ) {

			ob_start();

			/*
			|--------------------------------------------------------------------
			| Filter By Country
			|--------------------------------------------------------------------
			*/
			$countries      = anwp_football_leagues()->data->cb_get_countries();
			$current_filter = empty( $_GET['_anwpfl_nationality'] ) ? '' : sanitize_text_field( $_GET['_anwpfl_nationality'] ); // phpcs:ignore
			?>
			<select name='_anwpfl_nationality' id='anwp_nationality' class='postform'>
				<option value=''>- <?php echo esc_html__( 'select country', 'anwp-football-leagues' ); ?> -</option>
				<?php foreach ( $countries as $country_code => $country_title ) : ?>
					<option value="<?php echo esc_attr( $country_code ); ?>" <?php selected( $country_code, $current_filter ); ?>>
						<?php echo esc_html( $country_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo ob_get_clean();
		}
	}

	/**
	 * Handle custom filter.
	 *
	 * @param WP_Query $query
	 */
	public function handle_custom_filter( $query ) {

		global $post_type, $pagenow;

		// Check main query in admin
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'edit.php' !== $pagenow || 'anwp_club' !== $post_type ) {
			return;
		}

		$sub_query = [];

		/*
		|--------------------------------------------------------------------
		| Find Duplicates
		|--------------------------------------------------------------------
		*/
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['_anwpfl_club_duplicates'] ) ) {
			$sub_query[] =
				[
					'key'   => '_anwpfl_club_duplicates',
					'value' => 'yes',
				];
		}

		/*
		|--------------------------------------------------------------------
		| Filter By Season
		|--------------------------------------------------------------------
		*/
		// phpcs:ignore WordPress.Security.NonceVerification
		$filter_by_country = empty( $_GET['_anwpfl_nationality'] ) ? '' : sanitize_text_field( $_GET['_anwpfl_nationality'] );

		if ( $filter_by_country ) {
			$sub_query[] =
				[
					'key'   => '_anwpfl_nationality',
					'value' => $filter_by_country,
				];
		}

		/*
		|--------------------------------------------------------------------
		| Join All values to main query
		|--------------------------------------------------------------------
		*/
		if ( ! empty( $sub_query ) ) {
			$query->set(
				'meta_query',
				[
					$sub_query,
				]
			);
		}
	}

	/**
	 * Register REST routes.
	 *
	 * @since 0.9.2
	 */
	public function add_rest_routes() {
		register_rest_route(
			'anwpfl/v1',
			'/get-clubs-list/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_clubs_list' ],
				'permission_callback' => function () {
					return true;
				},
			]
		);

		register_rest_route(
			'anwpfl/v1',
			'/get-club-season-players/(?P<id>\d+)/(?P<club>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_club_season_players' ],
				'permission_callback' => function () {
					return true;
				},
			]
		);

		register_rest_route(
			'anwpfl/v1',
			'/get-club-season-staff/(?P<id>\d+)/(?P<club>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_club_season_staff' ],
				'permission_callback' => function () {
					return true;
				},
			]
		);
	}

	/**
	 * Meta box initialization.
	 *
	 * @since  0.2.0 (2018-01-10)
	 */
	public function init_metaboxes() {
		add_action(
			'add_meta_boxes',
			function ( $post_type ) {

				if ( 'anwp_club' === $post_type ) {
					add_meta_box(
						'anwpfl_club_squad',
						esc_html__( 'Club Squad', 'anwp-football-leagues' ),
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
	 * Render Meta Box content for Competition Stages.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @since  0.2.0 (2018-01-10)
	 */
	public function render_metabox( $post ) {

		// Check Season exists
		$seasons = (int) get_terms(
			[
				'taxonomy'   => 'anwp_season',
				'hide_empty' => false,
				'fields'     => 'count',
			]
		);

		if ( $seasons ) :

			$is_menu_collapsed = 'yes' === get_user_setting( 'anwp-fl-collapsed-menu' );

			// Add nonce for security and authentication.
			wp_nonce_field( 'anwp_save_metabox_' . $post->ID, 'anwp_metabox_nonce' );
			?>
			<div class="anwp-b-wrap anwpfl-squad-metabox-wrapper">
				<div class="d-flex mt-2" id="anwp-fl-metabox-page-nav">
					<div class="anwp-fl-menu-wrapper mr-3 d-none d-md-block sticky-top align-self-start anwp-flex-none <?php echo esc_attr( $is_menu_collapsed ? 'anwp-fl-collapsed-menu' : '' ); ?>" style="top: 50px;">

						<button id="anwp-fl-publish-click-proxy" class="w-100 button button-primary py-2 mb-4 d-flex align-items-center justify-content-center" type="submit">
							<svg class="anwp-icon anwp-icon--feather anwp-icon--s16"><use xlink:href="#icon-save"></use></svg>
							<span class="ml-2"><?php echo esc_html__( 'Save', 'anwp-football-leagues' ); ?></span>
							<span class="spinner m-0"></span>
						</button>

						<ul class="m-0 p-0 list-unstyled">

						<?php
							$nav_items = [
								[
									'icon'  => 'gear',
									'label' => __( 'General', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-general-metabox',
								],
								[
									'icon'  => 'device-camera',
									'label' => __( 'Media', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-media-metabox',
								],
								[
									'icon'  => 'repo-forked',
									'label' => __( 'Social', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-social-metabox',
								],
								[
									'icon'  => 'database',
									'label' => __( 'Subteams', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-subteams-metabox',
								],
								[
									'icon'  => 'note',
									'label' => __( 'Info', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-desc-metabox',
								],
								[
									'icon'  => 'server',
									'label' => __( 'Custom Fields', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-custom_fields-metabox',
								],
								[
									'icon'  => 'repo-push',
									'label' => __( 'Bottom Content', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-bottom_content-metabox',
								],
								[
									'icon'  => 'jersey',
									'label' => __( 'Squad', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-squad-metabox',
								],
								[
									'icon'  => 'organization',
									'label' => __( 'Staff', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-staff-metabox',
								],
							];

							/**
							 * Modify metabox nav items
							 *
							 * @since 0.12.6
							 */
							$nav_items = apply_filters( 'anwpfl/club/metabox_nav_items', $nav_items );

							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo anwp_football_leagues()->helper->create_metabox_navigation( $nav_items );

							/**
							 * Fires at the bottom of Metabox Nav.
							 *
							 * @since 0.12.6
							 */
							do_action( 'anwpfl/club/metabox_nav_bottom' );
							?>
						</ul>
					</div>

					<div class="flex-grow-1 anwp-min-width-0 mb-4">

						<?php
						cmb2_metabox_form( 'anwp_club_info_metabox' );

						$l10n = [
							'append_to_the'                 => esc_html__( 'Append to the', 'anwp-football-leagues' ),
							'actions'                       => esc_html__( 'Actions', 'anwp-football-leagues' ),
							'are_you_sure'                  => esc_html__( 'Are you sure?', 'anwp-football-leagues' ),
							'attach_person_to_staff'        => esc_html__( 'Attach Person to Staff', 'anwp-football-leagues' ),
							'attach_player_to_squad'        => esc_html__( 'Attach Player to Squad', 'anwp-football-leagues' ),
							'attach_to_squad'               => esc_html__( 'Attach to squad', 'anwp-football-leagues' ),
							'bottom'                        => esc_html__( 'bottom', 'anwp-football-leagues' ),
							'cancel'                        => esc_html__( 'Cancel', 'anwp-football-leagues' ),
							'close'                         => esc_html__( 'Close', 'anwp-football-leagues' ),
							'club_players'                  => esc_html__( 'club players', 'anwp-football-leagues' ),
							'club_squad'                    => esc_html__( 'Club Squad', 'anwp-football-leagues' ),
							'club_staff'                    => esc_html__( 'Club Staff', 'anwp-football-leagues' ),
							'confirm_delete'                => esc_html__( 'Confirm Delete', 'anwp-football-leagues' ),
							'change_current_club'           => esc_html__( 'Change Current Club', 'anwp-football-leagues' ),
							'change_current_club_to'        => esc_html__( 'Change current club to', 'anwp-football-leagues' ),
							'create_new_transfer'           => esc_html__( 'Create New Transfer', 'anwp-football-leagues' ),
							'current_club'                  => esc_html__( 'Current Club', 'anwp-football-leagues' ),
							'delete'                        => esc_html__( 'Delete', 'anwp-football-leagues' ),
							'group_player_ignore_without'   => esc_html__( 'Group Players by Position. Players without position will be ignored.', 'anwp-football-leagues' ),
							'in_club'                       => esc_html__( 'in club', 'anwp-football-leagues' ),
							'job'                           => esc_html__( 'Job', 'anwp-football-leagues' ),
							'left_club'                     => esc_html__( 'left club', 'anwp-football-leagues' ),
							'next'                          => esc_html__( 'Next', 'anwp-football-leagues' ),
							'no'                            => esc_html__( 'No', 'anwp-football-leagues' ),
							'none'                          => esc_html__( 'none', 'anwp-football-leagues' ),
							'number'                        => esc_html__( 'Number', 'anwp-football-leagues' ),
							'number_of_players_in_squad'    => esc_html__( 'Number of players in Squad', 'anwp-football-leagues' ),
							'on_loan'                       => esc_html__( 'on loan', 'anwp-football-leagues' ),
							'on_trial'                      => esc_html__( 'on trial', 'anwp-football-leagues' ),
							'other_seasons'                 => esc_html__( 'Other Seasons', 'anwp-football-leagues' ),
							'player_name'                   => esc_html__( 'Player Name', 'anwp-football-leagues' ),
							'position'                      => esc_html__( 'Position', 'anwp-football-leagues' ),
							'prev'                          => esc_html__( 'Prev', 'anwp-football-leagues' ),
							'really_want_delete_from_squad' => esc_html__( 'Do you really want to delete from current squad', 'anwp-football-leagues' ),
							'remove'                        => esc_html__( 'Remove', 'anwp-football-leagues' ),
							'saving_data_error'             => esc_html__( 'Saving Data Error', 'anwp-football-leagues' ),
							'search_by_name'                => esc_html__( 'search by name', 'anwp-football-leagues' ),
							'season'                        => esc_html__( 'Season', 'anwp-football-leagues' ),
							'select'                        => esc_html__( 'Select', 'anwp-football-leagues' ),
							'staff_name'                    => esc_html__( 'Staff Name', 'anwp-football-leagues' ),
							'status'                        => esc_html__( 'Status', 'anwp-football-leagues' ),
							'top'                           => esc_html__( 'top', 'anwp-football-leagues' ),
							'transfers_history'             => esc_html__( 'Transfers History', 'anwp-football-leagues' ),
							'use_separate_group'            => esc_html__( 'Use separate group', 'anwp-football-leagues' ),
							'yes'                           => esc_html__( 'Yes', 'anwp-football-leagues' ),
						];

						$positions = $this->plugin->data->get_positions();

						foreach ( [ 'goalkeeper', 'defender', 'midfielder', 'forward' ] as $position ) {
							if ( anwp_fl()->get_option_value( 'text_single_' . $position ) ) {
								$positions[ mb_substr( $position, 0, 1 ) ] = anwp_fl()->get_option_value( 'text_single_' . $position );
							}
						}

						$squad_data = [
							'club_squad'         => get_post_meta( $post->ID, '_anwpfl_squad', true ),
							'club_staff'         => get_post_meta( $post->ID, '_anwpfl_staff', true ),
							'club_squad_display' => get_post_meta( $post->ID, '_anwpfl_squad_display', true ),
							'currentClub'        => $post->ID,
							'players'            => $this->plugin->player->get_player_obj_list(),
							'staffs'             => $this->plugin->staff->get_staff_list(),
							'default_photo'      => anwp_fl()->helper->get_default_player_photo(),
							'clubs_map'          => $this->get_clubs_options(),
							'l10n'               => anwp_fl()->helper->entity_decode( $l10n ),
							'positions'          => $positions,
							'seasons_list'       => $this->plugin->season->get_seasons_list(),
							'loader'             => includes_url( 'js/tinymce/skins/lightgray/img/loader.gif' ),
							'rest_root'          => esc_url_raw( rest_url() ),
							'rest_nonce'         => wp_create_nonce( 'wp_rest' ),
							'squad_status'       => AnWPFL_Options::get_value( 'custom_squad_status' ) ? : [],
						];

						?>
						<script type="text/javascript">
							window._AnWP_FL_Squad_Data = <?php echo wp_json_encode( apply_filters( 'anwpfl/club/squad_app_data', $squad_data ) ); ?>;
						</script>
						<div id="fl-app-squad"></div>

						<?php
						/**
						 * Fires at the bottom of Metabox.
						 *
						 * @since 0.12.6
						 */
						do_action( 'anwpfl/club/metabox_bottom', $post );
						?>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="anwp-b-wrap">
				<div class="anwp-border anwp-border-gray-500">
					<div class="anwp-border-bottom anwp-border-gray-500 bg-white d-flex align-items-center px-3 py-2 anwp-text-gray-700 anwp-font-semibold">
						<svg class="anwp-icon anwp-icon--octi anwp-icon--s16 mr-2 anwp-fill-current">
							<use xlink:href="#icon-jersey"></use>
						</svg>
						<?php echo esc_html__( 'Club Squad', 'anwp-football-leagues' ); ?>
					</div>

					<div class="bg-white p-3">
						<div class="alert alert-warning my-0" role="alert">
							<?php echo esc_html__( 'Please, create a season first.', 'anwp-football-leagues' ); ?>
						</div>
					</div>
				</div>
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
	 * @return bool|int
	 */
	public function save_metabox( $post_id ) {

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
		if ( 'anwp_club' !== $_POST['post_type'] ) {
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
		 * Save Standing Data
		 *
		 * @since 0.2.0
		 * ---------------------------------------*/

		// Prepare data & Encode with some WP sanitization
		foreach ( [ '_anwpfl_squad', '_anwpfl_staff', '_anwpfl_squad_display' ] as $field_slug ) {

			if ( ! isset( $_POST[ $field_slug ] ) ) {
				continue;
			}

			$field_data = wp_json_encode( json_decode( wp_unslash( $_POST[ $field_slug ] ) ) );

			if ( $field_data ) {
				update_post_meta( $post_id, $field_slug, wp_slash( $field_data ) );
			}
		}

		/**
		 * Trigger on save club data.
		 *
		 * @param array $post_id
		 * @param array $_POST
		 *
		 * @since 0.12.6
		 */
		do_action( 'anwpfl/club/on_save', $post_id, $_POST );

		return $post_id;
	}

	/**
	 * Create CMB2 metaboxes
	 *
	 * @since 0.2.0 (2017-11-19)
	 */
	public function init_cmb2_metaboxes() {

		// Start with an underscore to hide fields from custom fields list
		$prefix = '_anwpfl_';

		/**
		 * Initiate the metabox
		 */
		$cmb = new_cmb2_box(
			[
				'id'              => 'anwp_club_info_metabox',
				'object_types'    => [ 'anwp_club' ],
				'context'         => 'advanced',
				'priority'        => 'high',
				'classes'         => 'anwp-b-wrap',
				'save_button'     => '',
				'show_names'      => true,
				'remove_box_wrap' => true,
			]
		);

		// Abbreviation
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Abbreviation', 'anwp-football-leagues' ),
				'id'         => $prefix . 'abbr',
				'type'       => 'text',
				'before_row' => anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'gear',
						'label' => __( 'General', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-general-metabox',
					]
				),
			]
		);

		$cmb->add_field(
			[
				'name' => esc_html__( 'City', 'anwp-football-leagues' ),
				'id'   => $prefix . 'city',
				'type' => 'text',
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Country', 'anwp-football-leagues' ),
				'id'         => $prefix . 'nationality',
				'type'       => 'anwp_fl_select',
				'options_cb' => [ $this->plugin->data, 'cb_get_countries' ],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Home Stadium', 'anwp-football-leagues' ),
				'id'         => $prefix . 'stadium',
				'options_cb' => [ $this->plugin->stadium, 'get_stadiums_options' ],
				'type'       => 'anwp_fl_select',
				'attributes' => [
					'placeholder' => esc_html__( '- not selected -', 'anwp-football-leagues' ),
				],
			]
		);

		$cmb->add_field(
			[
				'name' => esc_html__( 'Address', 'anwp-football-leagues' ),
				'id'   => $prefix . 'address',
				'type' => 'text',
			]
		);

		$cmb->add_field(
			[
				'name' => esc_html__( 'Website', 'anwp-football-leagues' ),
				'id'   => $prefix . 'website',
				'type' => 'text',
			]
		);

		$cmb->add_field(
			[
				'name' => esc_html__( 'Founded', 'anwp-football-leagues' ),
				'id'   => $prefix . 'founded',
				'type' => 'text',
			]
		);

		$cmb->add_field(
			[
				'name'        => esc_html__( 'External ID', 'anwp-football-leagues' ),
				'id'          => $prefix . 'club_external_id',
				'type'        => 'text',
				'description' => esc_html__( 'Used on Data Import', 'anwp-football-leagues' ),
			]
		);

		$cmb->add_field(
			[
				'name'      => esc_html__( 'National Team', 'anwp-football-leagues' ),
				'id'        => $prefix . 'is_national_team',
				'type'      => 'select',
				'default'   => '',
				'options'   => [
					''    => esc_html__( 'no', 'anwp-football-leagues' ),
					'yes' => esc_html__( 'yes', 'anwp-football-leagues' ),
				],
				'after_row' => '</div></div>',
			]
		);

		// Club Logo Small
		$cmb->add_field(
			[
				'name'         => esc_html__( 'Logo Small', 'anwp-football-leagues' ),
				'id'           => $prefix . 'logo',
				'type'         => 'file',
				'before_row'   => anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'device-camera',
						'label' => __( 'Media', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-media-metabox',
					]
				),
				'options'      => [
					'url' => false,
				],
				'query_args'   => [
					'type' => 'image',
				],
				'preview_size' => 'medium',
			]
		);

		// Club Logo Big
		$cmb->add_field(
			[
				'name'         => esc_html__( 'Logo Big', 'anwp-football-leagues' ),
				'id'           => $prefix . 'logo_big',
				'type'         => 'file',
				'options'      => [
					'url' => false,
				],
				'query_args'   => [
					'type' => 'image',
				],
				'preview_size' => 'medium',
			]
		);

		// Club Kit
		$cmb->add_field(
			[
				'name'         => esc_html__( 'Club Kit Photo', 'anwp-football-leagues' ),
				'id'           => $prefix . 'club_kit',
				'type'         => 'file',
				'options'      => [
					'url' => false,
				],
				'query_args'   => [
					'type' => 'image',
				],
				'preview_size' => 'medium',
			]
		);

		$cmb->add_field(
			[
				'name'    => esc_html__( 'Club Main Color', 'anwp-football-leagues' ),
				'id'      => $prefix . 'main_color',
				'type'    => 'colorpicker',
				'default' => '',
			]
		);

		// Photo
		$cmb->add_field(
			[
				'name'         => esc_html__( 'Gallery', 'anwp-football-leagues' ),
				'id'           => $prefix . 'gallery',
				'type'         => 'file_list',
				'options'      => [
					'url' => false, // Hide the text input for the url
				],
				// query_args are passed to wp.media's library query.
				'query_args'   => [
					'type' => 'image',
				],
				'preview_size' => 'medium', // Image size to use when previewing in the admin.
			]
		);

		// Notes
		$cmb->add_field(
			[
				'name'      => esc_html__( 'Text below gallery', 'anwp-football-leagues' ),
				'id'        => $prefix . 'gallery_notes',
				'type'      => 'textarea_small',
				'after_row' => '</div></div>',
			]
		);

		/*
		|--------------------------------------------------------------------
		| Social Tab
		|--------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Twitter', 'anwp-football-leagues' ),
				'id'         => $prefix . 'twitter',
				'before_row' => anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'repo-forked',
						'label' => __( 'Social', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-social-metabox',
					]
				),
				'type'       => 'text_url',
				'protocols'  => [ 'http', 'https' ],
			]
		);

		$cmb->add_field(
			[
				'name'      => esc_html__( 'Facebook', 'anwp-football-leagues' ),
				'id'        => $prefix . 'facebook',
				'type'      => 'text_url',
				'protocols' => [ 'http', 'https' ],
			]
		);

		$cmb->add_field(
			[
				'name'      => esc_html__( 'YouTube', 'anwp-football-leagues' ),
				'id'        => $prefix . 'youtube',
				'type'      => 'text_url',
				'protocols' => [ 'http', 'https' ],
			]
		);

		$cmb->add_field(
			[
				'name'      => esc_html__( 'LinkedIn', 'anwp-football-leagues' ),
				'id'        => $prefix . 'linkedin',
				'type'      => 'text_url',
				'protocols' => [ 'http', 'https' ],
			]
		);

		$cmb->add_field(
			[
				'name'      => esc_html__( 'TikTok', 'anwp-football-leagues' ),
				'id'        => $prefix . 'tiktok',
				'type'      => 'text_url',
				'protocols' => [ 'http', 'https' ],
			]
		);

		$cmb->add_field(
			[
				'name'      => esc_html__( 'VKontakte', 'anwp-football-leagues' ),
				'id'        => $prefix . 'vk',
				'type'      => 'text_url',
				'protocols' => [ 'http', 'https' ],
			]
		);

		$cmb->add_field(
			[
				'name'      => esc_html__( 'Instagram', 'anwp-football-leagues' ),
				'id'        => $prefix . 'instagram',
				'type'      => 'text_url',
				'protocols' => [ 'http', 'https' ],
				'after_row' => '</div></div>',
			]
		);

		/*
		|--------------------------------------------------------------------
		| Subteams
		|--------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Subteam Status', 'anwp-football-leagues' ),
				'id'         => $prefix . 'subteams',
				'before_row' => anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'repo-forked',
						'label' => __( 'Subteams', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-subteams-metabox',
					]
				),
				'type'       => 'select',
				'default'    => '',
				'attributes' => [
					'class'     => 'cmb2_select anwp-fl-parent-of-dependent',
					'data-name' => $prefix . 'subteams',
				],
				'options'    => [
					''        => esc_html__( 'no', 'anwp-football-leagues' ),
					'root'    => esc_html__( 'root team / summary', 'anwp-football-leagues' ),
					'subteam' => esc_html__( 'subteam', 'anwp-football-leagues' ),
				],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Root Team', 'anwp-football-leagues' ),
				'id'         => $prefix . 'root_team',
				'options_cb' => [ $this->plugin->club, 'get_root_clubs_options' ],
				'type'       => 'anwp_fl_select',
				'before_row' => '<div class="cmb-row"><div class="anwp-fl-dependent-field" data-parent="' . $prefix . 'subteams" data-action="show" data-value="subteam">',
				'after_row'  => '</div></div>',
				'attributes' => [
					'placeholder' => esc_html__( '- not selected -', 'anwp-football-leagues' ),
				],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Root Page Type', 'anwp-football-leagues' ),
				'id'         => $prefix . 'root_type',
				'before_row' => '<div class="cmb-row"><div class="anwp-fl-dependent-field" data-parent="' . $prefix . 'subteams" data-action="show" data-value="root">',
				'after_row'  => '</div></div>',
				'type'       => 'select',
				'default'    => 'team',
				'options'    => [
					'team'    => esc_html__( 'root team', 'anwp-football-leagues' ),
					'summary' => esc_html__( 'summary page', 'anwp-football-leagues' ),
				],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Root Team Title', 'anwp-football-leagues' ),
				'id'         => $prefix . 'root_team_title',
				'type'       => 'text',
				'before_row' => '<div class="cmb-row"><div class="anwp-fl-dependent-field" data-parent="' . $prefix . 'subteams" data-action="show" data-value="root">',
				'after_row'  => '</div></div>',
			]
		);

		$group_field_id = $cmb->add_field(
			[
				'id'           => $prefix . 'subteam_list',
				'type'         => 'group',
				'repeatable'   => true,
				'before_group' => '<div class="cmb-row"><div class="anwp-fl-dependent-field" data-parent="' . $prefix . 'subteams" data-action="show" data-value="root">',
				'after_group'  => '</div></div>',
				'options'      => [
					'group_title'   => __( 'Subteam {#}', 'anwp-football-leagues' ),
					'add_button'    => __( 'Add Another Subteam', 'anwp-football-leagues' ),
					'remove_button' => __( 'Remove Subteam', 'anwp-football-leagues' ),
					'sortable'      => true,

				],
			]
		);

		$cmb->add_group_field(
			$group_field_id,
			[
				'name' => __( 'Subteam Title', 'anwp-football-leagues' ),
				'id'   => 'title',
				'type' => 'text',
			]
		);

		$cmb->add_group_field(
			$group_field_id,
			[
				'name'       => esc_html__( 'Subteam', 'anwp-football-leagues' ),
				'id'         => 'subteam',
				'options_cb' => [ $this->plugin->club, 'get_clubs_options' ],
				'type'       => 'anwp_fl_select',
				'attributes' => [
					'placeholder' => esc_html__( '- not selected -', 'anwp-football-leagues' ),
				],
			]
		);

		/*
		|--------------------------------------------------------------------
		| Info Tab
		|--------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'            => esc_html__( 'Description', 'anwp-football-leagues' ),
				'id'              => $prefix . 'description',
				'type'            => 'wysiwyg',
				'options'         => [
					'wpautop'       => true,
					'media_buttons' => true,
					'textarea_name' => 'anwp_club_description_input',
					'textarea_rows' => 8,
					'teeny'         => false, // output the minimal editor config used in Press This
					'dfw'           => false, // replace the default fullscreen with DFW (needs specific css)
					'tinymce'       => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
					'quicktags'     => true, // load Quicktags, can be used to pass settings directly to Quicktags using an array()
				],
				'show_names'      => false,
				'sanitization_cb' => [ anwp_fl()->helper, 'sanitize_cmb2_fl_text' ],
				'before_row'      => '</div></div>' . anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'note',
						'label' => __( 'Info', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-desc-metabox',
					]
				),
				'after_row'       => '</div></div>',
			]
		);

		/*
		|--------------------------------------------------------------------
		| Custom Fields
		|--------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Title', 'anwp-football-leagues' ) . ' #1',
				'id'         => $prefix . 'custom_title_1',
				'before_row' => anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'server',
						'label' => __( 'Custom Fields', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-custom_fields-metabox',
					]
				),
				'type'       => 'text',
			]
		);

		$cmb->add_field(
			[
				'name' => esc_html__( 'Value', 'anwp-football-leagues' ) . ' #1',
				'id'   => $prefix . 'custom_value_1',
				'type' => 'text',
			]
		);

		$cmb->add_field(
			[
				'name' => esc_html__( 'Title', 'anwp-football-leagues' ) . ' #2',
				'id'   => $prefix . 'custom_title_2',
				'type' => 'text',
			]
		);

		$cmb->add_field(
			[
				'name' => esc_html__( 'Value', 'anwp-football-leagues' ) . ' #2',
				'id'   => $prefix . 'custom_value_2',
				'type' => 'text',
			]
		);

		$cmb->add_field(
			[
				'name' => esc_html__( 'Title', 'anwp-football-leagues' ) . ' #3',
				'id'   => $prefix . 'custom_title_3',
				'type' => 'text',
			]
		);

		$cmb->add_field(
			[
				'name' => esc_html__( 'Value', 'anwp-football-leagues' ) . ' #3',
				'id'   => $prefix . 'custom_value_3',
				'type' => 'text',
			]
		);

		// Dynamic Custom Fields
		$cmb->add_field(
			[
				'name'        => esc_html__( 'Dynamic Custom Fields', 'anwp-football-leagues' ),
				'id'          => $prefix . 'custom_fields',
				'type'        => 'anwp_fl_custom_fields',
				'option_slug' => 'club_custom_fields',
				'after_row'   => '</div></div>',
				'before_row'  => '<hr>',
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Content', 'anwp-football-leagues' ),
				'id'         => $prefix . 'custom_content_below',
				'type'       => 'wysiwyg',
				'options'    => [
					'wpautop'       => true,
					'media_buttons' => true, // show insert/upload button(s)
					'textarea_name' => 'anwp_custom_content_below',
					'textarea_rows' => 5,
					'teeny'         => false, // output the minimal editor config used in Press This
					'dfw'           => false, // replace the default fullscreen with DFW (needs specific css)
					'tinymce'       => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
					'quicktags'     => true, // load Quicktags, can be used to pass settings directly to Quicktags using an array()
				],
				'show_names' => false,
				'before_row' => anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'repo-push',
						'label' => __( 'Bottom Content', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-bottom_content-metabox',
					]
				),
				'after_row'  => '</div></div>',
			]
		);

		/**
		 * Adds extra fields to the metabox.
		 *
		 * @since 0.9.0
		 */
		$extra_fields = apply_filters( 'anwpfl/cmb2_tabs_content/club', [] );

		if ( ! empty( $extra_fields ) && is_array( $extra_fields ) ) {
			foreach ( $extra_fields as $field ) {
				$cmb->add_field( $field );
			}
		}
	}

	/**
	 * Get list of club objects.
	 *
	 * @since 0.2.0 (2017-11-29)
	 * @return array $output_data - Array of clubs data (id, title, logo)
	 */
	public function get_clubs_list() {

		$output_data = [];

		foreach ( $this->get_all_clubs_data() as $club_id => $club_data ) {
			$output_data[] = (object) [
				'id'    => $club_id,
				'title' => $club_data['title'],
				'logo'  => $club_data['logo'] ?: $club_data['logo_big'],
			];
		}

		return $output_data;
	}

	/**
	 * Get club players for selected competition.
	 * Callback for the rest route "/get-club-season-players/<competition_id>".
	 *
	 * @param array $data - WP_REST_Request
	 *
	 * @since 0.2.0 (2018-01-20)
	 * @return array $output_data -
	 */
	public function get_club_season_players( $data, $output = '' ) {

		$output_data = [];

		// Prepare data
		$club_id   = (int) $data['club'];
		$season_id = (int) $data['id'];

		// Check season id assigned
		if ( ! $season_id || ! $club_id ) {
			return $output_data;
		}

		// Get club squad meta (for all seasons)
		$squad_all = get_post_meta( $club_id, '_anwpfl_squad', true );

		if ( $squad_all ) {
			$squad_all = json_decode( $squad_all );
		}

		if ( ! empty( $squad_all->{'s:' . $season_id} ) ) {
			$output_data = $squad_all->{'s:' . $season_id};

			if ( 'short' === $output ) {
				return $output_data;
			}

			$players_data = anwp_fl()->player->get_players_by_ids( wp_list_pluck( $output_data, 'id' ) );

			foreach ( $output_data as $player ) {
				$player->name        = $players_data[ $player->id ]['name'] ?? '';
				$player->name_short  = $players_data[ $player->id ]['short_name'] ?? '';
				$player->nationality = $players_data[ $player->id ]['nationality'] ?? '';
			}
		}

		return $output_data;
	}

	/**
	 * Get club staff for selected competition.
	 * Callback for the rest route "/get-club-season-staff/<competition_id>".
	 *
	 * @param array $data - WP_REST_Request
	 *
	 * @since 0.7.3 (2018-09-19)
	 * @return array $output_data -
	 */
	public function get_club_season_staff( $data ) {

		$output_data = [];

		// Prepare data
		$club_id   = (int) $data['club'];
		$season_id = (int) $data['id'];

		// Check season id assigned
		if ( ! $season_id || ! $club_id ) {
			return $output_data;
		}

		// Get club staff meta (for all seasons)
		$staff = get_post_meta( $club_id, '_anwpfl_staff', true );

		if ( $staff ) {
			$staff = json_decode( $staff );
		}

		if ( ! empty( $staff->{'s:' . $season_id} ) ) {
			$season_staff = $staff->{'s:' . $season_id};

			// Add Staff names
			$staff_map = [];

			foreach ( $this->plugin->staff->get_staff_list() as $staff_member ) {
				$staff_map[ $staff_member->id ] = $staff_member->name;
			}

			foreach ( $season_staff as $member ) {
				$output_data[ $member->id ] = empty( $staff_map[ $member->id ] ) ? '' : $staff_map[ $member->id ];
			}
		}

		return $output_data;
	}

	/**
	 * Helper function, returns clubs map with id and title
	 *
	 * @since 0.2.0 (2018-01-09)
	 * @return array $output_data - Array of clubs data (id => title)
	 */
	public function get_clubs_options() {

		$options = [];

		foreach ( $this->get_all_clubs_data() as $club_id => $club_data ) {
			$options[ $club_id ] = $club_data['title'];
		}

		return $options;
	}

	/**
	 * Helper function, returns clubs map with id and title
	 *
	 * @since 0.15.0
	 * @return array $output_data - Array of clubs data (id => title)
	 */
	public function get_root_clubs_options() {

		global $wpdb;

		$root_ids = $wpdb->get_col(
			"
			SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = '_anwpfl_subteams' AND meta_value = 'root'
			"
		);

		$options = [];

		foreach ( $this->get_all_clubs_data() as $club_id => $club_data ) {
			if ( in_array( (string) $club_id, $root_ids, true ) ) {
				$options[ $club_id ] = $club_data['title'];
			}
		}

		return $options;
	}

	/**
	 * Helper function, returns national Teams map with id and title
	 *
	 * @since 0.12.2
	 * @return array $output_data - Array of clubs data (id => title)
	 */
	public function get_national_team_options() {

		static $options = null;

		if ( null === $options ) {
			global $wpdb;

			$clubs = $wpdb->get_results(
				"
				SELECT m.post_id, p.post_title
				FROM $wpdb->postmeta m
				LEFT JOIN $wpdb->posts p ON m.post_id = p.ID
				WHERE m.meta_key = '_anwpfl_is_national_team' AND m.meta_value = 'yes'
				"
			);

			foreach ( $clubs as $club ) {
				$options[ $club->post_id ] = $club->post_title;
			}
		}

		return $options;
	}

	/**
	 * Get team country
	 *
	 * @param int $team_id
	 *
	 * @return string
	 */
	public function get_team_country( int $team_id ): string {

		static $options = null;

		if ( null === $options ) {
			global $wpdb;

			$team_countries = $wpdb->get_results(
				"
				SELECT post_id, meta_value as country
				FROM $wpdb->postmeta
				WHERE meta_key = '_anwpfl_nationality' AND meta_value != ''
				",
				OBJECT_K
			);

			foreach ( $team_countries as $team_country ) {
				$options[ $team_country->post_id ] = $team_country->country;
			}
		}

		return $options[ $team_id ] ?? '';
	}

	/**
	 * Get all players from DB.
	 *
	 * @return array
	 */
	public function get_all_clubs_data() {

		$cache_key   = 'FL-CLUBS-LIST';
		$cached_data = anwp_fl()->cache->get( $cache_key );

		if ( $cached_data ) {
			return $cached_data;
		}

		static $output = null;

		if ( null === $output ) {

			$output = [];

			global $wpdb;

			/*
			|--------------------------------------------------------------------
			| Logo
			|--------------------------------------------------------------------
			*/
			$logos = $wpdb->get_results(
				"
				SELECT post_id, meta_value
				FROM $wpdb->postmeta
				WHERE meta_key = '_anwpfl_logo' AND meta_value != ''
				",
				OBJECT_K
			);

			/*
			|--------------------------------------------------------------------
			| Logo Big
			|--------------------------------------------------------------------
			*/
			$logos_big = $wpdb->get_results(
				"
				SELECT post_id, meta_value
				FROM $wpdb->postmeta
				WHERE meta_key = '_anwpfl_logo_big' AND meta_value != ''
				",
				OBJECT_K
			);

			/*
			|--------------------------------------------------------------------
			| Get Abbreviations
			|--------------------------------------------------------------------
			*/
			$abbrs = $wpdb->get_results(
				"
				SELECT post_id, meta_value as abbr
				FROM $wpdb->postmeta
				WHERE meta_key = '_anwpfl_abbr' AND meta_value != ''
				",
				OBJECT_K
			);

			$use_club_abbr = apply_filters( 'anwpfl/club/use_club_abbr', true );

			if ( $this->plugin->cache->simple_link_building ) {
				$all_clubs = $wpdb->get_results(
					"
					SELECT p.post_title, p.post_name, p.ID
					FROM $wpdb->posts p
					WHERE p.post_status = 'publish' AND p.post_type = 'anwp_club'
					ORDER BY p.post_title ASC
					",
					ARRAY_A
				) ?: [];

				$permalink_slug = $this->plugin->options->get_permalink_structure()['club'] ?? 'club';
				$base_url       = get_site_url( null, '/' . $permalink_slug . '/' );

				foreach ( $all_clubs as $club_data ) {
					$output[ $club_data['ID'] ] = [
						'title'    => $club_data['post_title'],
						'abbr'     => ( ! $use_club_abbr || empty( $abbrs[ $club_data['ID'] ]->abbr ) ) ? $club_data['post_title'] : $abbrs[ $club_data['ID'] ]->abbr,
						'link'     => $base_url . $club_data['post_name'] . '/',
						'logo_big' => empty( $logos_big[ $club_data['ID'] ]->meta_value ) ? '' : $logos_big[ $club_data['ID'] ]->meta_value,
						'logo'     => empty( $logos[ $club_data['ID'] ]->meta_value ) ? '' : $logos[ $club_data['ID'] ]->meta_value,
					];
				}
			} else {
				$all_clubs = get_posts(
					[
						'numberposts'      => -1,
						'post_type'        => 'anwp_club',
						'suppress_filters' => false,
						'post_status'      => 'publish',
						'order'            => 'ASC',
						'orderby'          => 'title',
						'cache_results'    => false,
					]
				);

				if ( empty( $all_clubs ) ) {
					return [];
				}

				foreach ( $all_clubs as $club_obj ) {
					$output[ $club_obj->ID ] = [
						'title'    => $club_obj->post_title,
						'abbr'     => ( ! $use_club_abbr || empty( $abbrs[ $club_obj->ID ]->abbr ) ) ? $club_obj->post_title : $abbrs[ $club_obj->ID ]->abbr,
						'link'     => get_permalink( $club_obj ),
						'logo_big' => empty( $logos_big[ $club_obj->ID ]->meta_value ) ? '' : $logos_big[ $club_obj->ID ]->meta_value,
						'logo'     => empty( $logos[ $club_obj->ID ]->meta_value ) ? '' : $logos[ $club_obj->ID ]->meta_value,
					];
				}
			}

			/*
			|--------------------------------------------------------------------
			| Save transient
			|--------------------------------------------------------------------
			*/
			if ( ! empty( $output ) ) {
				anwp_fl()->cache->set( $cache_key, $output );
			}
		}

		return $output;
	}

	/**
	 * Get squad seasons ids
	 *
	 * @param int $club_id
	 *
	 * @return array $output_data
	 * @since 0.11.6
	 */
	public function get_club_squad_season_ids( $club_id ) {

		// Get squad data
		$squad = json_decode( get_post_meta( $club_id, '_anwpfl_squad', true ) );

		if ( empty( $squad ) ) {
			return [];
		}

		$squad_seasons = [];

		foreach ( $squad as $squad_slug => $squad_data ) {
			if ( empty( $squad_data ) ) {
				continue;
			}

			$squad_seasons[] = str_replace( 's:', '', $squad_slug );
		}

		return $squad_seasons ? : [];
	}

	/**
	 * Helper function to prepare club squad.
	 *
	 * @param int  $club_id
	 * @param int  $season_id
	 * @param bool $extended
	 *
	 * @return array
	 * @since 0.3.0 (2018-02-08)
	 */
	public function tmpl_prepare_club_squad( $club_id, $season_id, $extended = false ) {

		$squad_raw = json_decode( get_post_meta( $club_id, '_anwpfl_squad', true ) );

		$season_slug = 's:' . (int) $season_id;
		$squad       = [];

		if ( null === $squad_raw || ! isset( $squad_raw->{$season_slug} ) || ! is_array( $squad_raw->{$season_slug} ) ) {
			return $squad;
		}

		foreach ( $squad_raw->{$season_slug} as $s ) {
			$squad[ $s->id ] = [
				'position' => $s->position ?? '',
				'number'   => $s->number ?? '',
				'status'   => $s->status ?? '',
			];

			if ( $extended ) {
				$squad[ $s->id ]['name']        = '';
				$squad[ $s->id ]['photo']       = '';
				$squad[ $s->id ]['nationality'] = [];
				$squad[ $s->id ]['age']         = '';
			}
		}

		if ( $extended ) {
			$players = anwp_football_leagues()->player->get_players_by_ids( array_keys( $squad ) );

			foreach ( $players as $player ) {
				try {
					$birth_date_obj = DateTime::createFromFormat( 'Y-m-d', $player['date_of_birth'] );
					$age            = $birth_date_obj ? $birth_date_obj->diff( new DateTime() )->y : '-';
				} catch ( Exception $exception ) {
					$age = '';
				}

				$squad[ $player['player_id'] ]['name']          = $player['name'];
				$squad[ $player['player_id'] ]['photo']         = $player['photo'];
				$squad[ $player['player_id'] ]['nationality']   = $player['nationality'];
				$squad[ $player['player_id'] ]['nationalities'] = $player['nationalities'];
				$squad[ $player['player_id'] ]['age']           = $age;
				$squad[ $player['player_id'] ]['age2']          = $player['date_of_birth'];
				$squad[ $player['player_id'] ]['link']          = $player['link'];
			}
		}

		return $squad;
	}

	/**
	 * Helper function to prepare club staff.
	 *
	 * @param int $club_id
	 * @param int $season_id
	 *
	 * @return array
	 * @throws Exception Emits Exception in case of an error.
	 * @since 0.7.0 (2018-09-06)
	 */
	public function tmpl_prepare_club_staff( $club_id, $season_id ) {

		$staff_raw = json_decode( get_post_meta( $club_id, '_anwpfl_staff', true ) );

		$season_slug = 's:' . (int) $season_id;
		$staff       = [];

		if ( null === $staff_raw || ! isset( $staff_raw->{$season_slug} ) || ! is_array( $staff_raw->{$season_slug} ) ) {
			return $staff;
		}

		foreach ( $staff_raw->{$season_slug} as $s ) {
			$staff[ $s->id ] = [
				'job'         => isset( $s->job ) ? $s->job : '',
				'grouping'    => isset( $s->grouping ) ? $s->grouping : 'no',
				'group'       => isset( $s->group ) ? $s->group : '',
				'name'        => '',
				'photo'       => '',
				'age'         => '',
				'nationality' => [],
			];
		}

		// Add Extra data
		$members_query = get_posts(
			[
				'post_type'   => 'anwp_staff',
				'post_status' => 'publish',
				'include'     => array_keys( $staff ),
			]
		);

		foreach ( $members_query as $member ) {

			if ( isset( $staff[ $member->ID ] ) ) {

				$birth_date_obj = DateTime::createFromFormat( 'Y-m-d', $member->_anwpfl_date_of_birth );
				$age            = $birth_date_obj ? $birth_date_obj->diff( new DateTime() )->y : '-';

				$staff[ $member->ID ]['name']        = $member->post_title;
				$staff[ $member->ID ]['photo']       = $member->_anwpfl_photo;
				$staff[ $member->ID ]['nationality'] = maybe_unserialize( $member->_anwpfl_nationality );
				$staff[ $member->ID ]['age']         = $age;
				$staff[ $member->ID ]['age2']        = $member->_anwpfl_date_of_birth;
			}
		}

		return $staff;
	}

	/**
	 * Get Club title by id
	 *
	 * @param int $club_id - Club id
	 *
	 * @since 0.3.0 (2018-01-29)
	 * @return string - Club title
	 */
	public function get_club_title_by_id( $club_id ) {

		$club_id = (int) $club_id;

		// Check and validate id
		if ( ! $club_id ) {
			return '';
		}

		$clubs_options = $this->get_clubs_options();

		return empty( $clubs_options[ $club_id ] ) ? '' : $clubs_options[ $club_id ];
	}

	/**
	 * Get Club link by id
	 *
	 * @param int $club_id - Club id
	 *
	 * @return string - Club link
	 * @since 0.7.5 (2018-10-10)
	 */
	public function get_club_link_by_id( $club_id ) {

		$club_id = (int) $club_id;

		// Check and validate id
		if ( ! $club_id ) {
			return '';
		}

		return empty( $this->get_all_clubs_data()[ $club_id ]['link'] ) ? '' : $this->get_all_clubs_data()[ $club_id ]['link'];
	}

	/**
	 * Get Club logo by id
	 *
	 * @param int  $club_id - Club id
	 * @param bool $small
	 *
	 * @return string - Club logo or empty string
	 * @since 0.3.0 (2018-02-01)
	 */
	public function get_club_logo_by_id( $club_id, $small = true ) {

		$club_id = (int) $club_id;

		// Check and validate id
		if ( ! $club_id ) {
			return '';
		}

		$logo_var = $small ? 'logo' : 'logo_big';

		if ( ! empty( $this->get_all_clubs_data()[ $club_id ][ $logo_var ] ) ) {
			return $this->get_all_clubs_data()[ $club_id ][ $logo_var ];
		}

		$logo_var = $small ? 'logo_big' : 'logo';

		return empty( $this->get_all_clubs_data()[ $club_id ][ $logo_var ] ) ? '' : $this->get_all_clubs_data()[ $club_id ][ $logo_var ];
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @param array $columns Array of registered column names/labels.
	 *
	 * @return array          Modified array.
	 */
	public function sortable_columns( $sortable_columns ) {

		return array_merge( $sortable_columns, [ 'club_id' => 'ID' ] );
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @param array $columns Array of registered column names/labels.
	 *
	 * @return array          Modified array.
	 * @since  0.1.0
	 */
	public function columns( $columns ) {
		// Add new columns
		$new_columns = [
			'anwpfl_club_logo'  => esc_html__( 'Logo', 'anwp-football-leagues' ),
			'club_id'           => esc_html__( 'ID', 'anwp-football-leagues' ),
			'club_games'        => esc_html__( 'Games', 'anwp-football-leagues' ),
			'anwpfl_club_color' => esc_html__( 'Club Color', 'anwp-football-leagues' ),
		];

		// Merge old and new columns
		$columns = array_merge( $new_columns, $columns );

		// Change columns order
		$new_columns_order = [
			'cb',
			'title',
			'anwpfl_club_logo',
			'anwpfl_club_color',
			'club_games',
			'comments',
			'date',
			'club_id',
		];

		$new_columns = [];

		foreach ( $new_columns_order as $c ) {

			if ( isset( $columns[ $c ] ) ) {
				$new_columns[ $c ] = $columns[ $c ];
			}
		}

		return $new_columns;
	}

	/**
	 * Handles admin column display. Hooked in via CPT_Core.
	 *
	 * @param array   $column  Column currently being rendered.
	 * @param integer $post_id ID of post to display column for.
	 *
	 * @since  0.1.0
	 */
	public function columns_display( $column, $post_id ) {
		switch ( $column ) {
			case 'anwpfl_club_logo':
				$logo = get_post_meta( $post_id, '_anwpfl_logo', true );

				if ( $logo ) {
					printf( '<img src="%s" class="anwp-admin-table-club-logo" alt="club logo">', esc_url( $logo ) );
				}
				break;

			case 'club_id':
				echo (int) $post_id;
				break;

			case 'anwpfl_club_color':
				$team_color = get_post_meta( $post_id, '_anwpfl_main_color', true );
				if ( $team_color ) {
					echo '<div style="background-color: ' . esc_attr( $team_color ) . '; width: 30px; height: 50px;">&nbsp;</div>';
				}
				break;

			case 'club_games':
				$games_num = count( anwp_football_leagues()->competition->tmpl_get_competition_matches_extended( [ 'filter_by_clubs' => $post_id ], 'ids' ) );

				if ( absint( $games_num ) ) {
					echo '<a target="_blank" href="' . admin_url( 'edit.php?post_type=anwp_match&&post_status=all&_anwpfl_current_club=' . absint( $post_id ) ) . '">' . absint( $games_num ) . '</a>';
				} else {
					echo 0;
				}
				break;
		}
	}

	/**
	 * Method returns array of all clubs for TinyMCE listbox.
	 *
	 * @return array
	 * @since 0.5.4 (2018-03-29)
	 */
	public function mce_get_club_options() {

		$options = [];
		$clubs   = $this->get_clubs_options();

		if ( empty( $clubs ) || ! is_array( $clubs ) ) {
			return $options;
		}

		foreach ( $clubs as $club_id => $club_title ) {
			$options[] = (object) [
				'text'  => $club_title,
				'value' => $club_id,
			];
		}

		return $options;
	}

	/**
	 * Get Club abbr by id
	 *
	 * @param int $club_id - Club id
	 *
	 * @return string - Club title
	 * @since 0.10.9
	 */
	public function get_club_abbr_by_id( $club_id ) {

		$club_id = (int) $club_id;

		// Check and validate id
		if ( ! $club_id ) {
			return '';
		}

		return empty( $this->get_all_clubs_data()[ $club_id ]['abbr'] ) ? '' : $this->get_all_clubs_data()[ $club_id ]['abbr'];
	}

	/**
	 * Check national team
	 *
	 * @param int $club_id - Club id
	 *
	 * @return bool
	 * @since 0.13.2
	 */
	public function is_national_team( $club_id ) {

		if ( ! absint( $club_id ) ) {
			return false;
		}

		return 'yes' === get_post_meta( $club_id, '_anwpfl_is_national_team', true );
	}

	/**
	 * Get Club data
	 *
	 * @param int $club_id Club ID
	 *
	 * @return (object) [ // <pre>
	 *        'title'     => (string),
	 *        'abbr'      => (string),
	 *        'link'      => (string),
	 *        'logo_big'  => (string),
	 *        'logo'      => (string),
	 * ]|bool
	 * @since 0.13.7
	 */
	public function get_club( $club_id ) {

		static $clubs_cache = null;

		if ( null === $clubs_cache ) {
			$clubs_cache = $this->get_all_clubs_data();
		}

		if ( empty( $clubs_cache ) || ! absint( $club_id ) ) {
			return false;
		}

		static $output_data = [];

		if ( isset( $output_data[ $club_id ] ) ) {
			return $output_data[ $club_id ];
		}

		$club_cached_obj = isset( $clubs_cache[ $club_id ] ) ? $clubs_cache[ $club_id ] : false;

		if ( empty( $club_cached_obj ) ) {
			return false;
		}

		$output_data[ $club_id ] = (object) $club_cached_obj;

		return $output_data[ $club_id ];
	}

	/**
	 * Get Post ID by External id
	 *
	 * @param $external_id
	 *
	 * @return string|null
	 * @since 0.14.0
	 */
	public function get_club_id_by_external_id( $external_id ) {

		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = '_anwpfl_club_external_id' AND meta_value = %s
				",
				$external_id
			)
		);
	}

	/**
	 * Get squad display options
	 *
	 * @param int $club_id
	 * @param int $season_id
	 *
	 * @return object
	 * @since 0.14.5
	 */
	public function get_squad_display_options( $club_id, $season_id ) {

		$saved_options = json_decode( get_post_meta( $club_id, '_anwpfl_squad_display', true ) );
		$default_data  = (object) [
			'group' => true,
		];

		$season_slug = (int) $season_id;

		if ( empty( $saved_options ) || ! isset( $saved_options->{$season_slug} ) || ! is_object( $saved_options->{$season_slug} ) ) {
			return $default_data;
		}

		return $saved_options->{$season_slug};
	}

	/**
	 * Get subteam ids
	 *
	 * @param int $club_id
	 *
	 * @return string|int|array
	 * @since 0.15.0
	 */
	public function get_subteam_ids( $club_id, $array_output = false ) {

		if ( 'summary' === get_post_meta( $club_id, '_anwpfl_root_type', true ) ) {
			$subteam_list = get_post_meta( $club_id, '_anwpfl_subteam_list', true );

			if ( ! empty( $subteam_list ) && is_array( $subteam_list ) ) {
				$club_ids = [];

				foreach ( $subteam_list as $subteam_item ) {
					$club_ids[] = $subteam_item['subteam'];
				}

				$output = $array_output ? $club_ids : implode( ',', $club_ids );
			}
		}

		return empty( $output ) ? $club_id : $output;
	}
}
