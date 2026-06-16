<?php
/**
 * AnWP Football Leagues :: Club.
 *
 * @since   0.1.0
 * @package AnWP_Football_Leagues
 */

/**
 * AnWP Football Leagues :: Club post type class.
 *
 * @since 0.1.0
 */
class AnWPFL_Club extends AnWPFL_DB {

	/**
	 * Parent plugin class.
	 *
	 * @var AnWP_Football_Leagues
	 * @since  0.1.0
	 */
	protected $plugin = null;

	/**
	 * Light per-request cache for list views (standings, match lists, dropdowns).
	 * Holds 11 core display columns per club. Separate from base $row_cache
	 * (full rows) to avoid loading JSON blobs for list views.
	 *
	 * @since 0.17.3
	 * @var array<int, array>
	 */
	protected array $list_cache = [];

	/**
	 * Flag: Vue editor already wrote club data in save_metabox().
	 * Prevents sync_club_to_table() from overwriting with stale postmeta.
	 */
	protected bool $vue_save_handled = false;

	/**
	 * Per-request record of which source produced the squad on the last
	 * tmpl_prepare_club_squad() call. Keyed by "{club_id}:{season_id}".
	 * Templates read this via get_last_squad_source() to render the
	 * "Active Squad" / "{Season} season" / fallback label above the player list.
	 *
	 * @var array<string, array{source:string, season_id:int}>
	 */
	private static array $last_squad_source = [];

	/**
	 * Mark the next save_post_anwp_club as already-handled by an external writer.
	 *
	 * Suppresses the postmeta-fallback branch in sync_club_to_table() so the
	 * caller's direct upsert is not clobbered. The caller is responsible for
	 * writing the row itself via $this->upsert(). Used by the batch importer
	 * and (planned) API import path that write directly to anwpfl_clubs
	 * instead of going through postmeta + the sync bridge.
	 */
	public function flag_external_save_handled(): void {
		$this->vue_save_handled = true;
	}

	/**
	 * Constructor.
	 *
	 * @param AnWP_Football_Leagues $plugin Main plugin object.
	 *
	 * @since  0.1.0
	 */
	public function __construct( $plugin ) {
		global $wpdb;

		$this->plugin      = $plugin;
		$this->table_name  = $wpdb->anwpfl_clubs;
		$this->primary_key = 'club_id';

		$this->hooks();
	}

	/**
	 * Register the Club CPT.
	 *
	 * @since 0.17.3
	 */
	public function register_post_type() {
		$permalink_structure = $this->plugin->options->get_permalink_structure();
		$permalink_slug      = empty( $permalink_structure['club'] ) ? 'club' : $permalink_structure['club'];

		register_post_type(
			'anwp_club',
			[
				'labels'              => [
					'name'               => esc_html__( 'Clubs', 'anwp-football-leagues' ),
					'singular_name'      => esc_html__( 'Club', 'anwp-football-leagues' ),
					'all_items'          => esc_html__( 'Clubs', 'anwp-football-leagues' ),
					'add_new'            => esc_html__( 'Add New Club', 'anwp-football-leagues' ),
					'add_new_item'       => esc_html__( 'Add New Club', 'anwp-football-leagues' ),
					'edit_item'          => esc_html__( 'Edit Club', 'anwp-football-leagues' ),
					'new_item'           => esc_html__( 'New Club', 'anwp-football-leagues' ),
					'view_item'          => esc_html__( 'View Club', 'anwp-football-leagues' ),
					'search_items'       => esc_html__( 'Search Clubs', 'anwp-football-leagues' ),
					'not_found'          => esc_html__( 'No Clubs found', 'anwp-football-leagues' ),
					'not_found_in_trash' => esc_html__( 'No Clubs found in trash', 'anwp-football-leagues' ),
				],
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=anwp_player',
				'has_archive'         => true,
				'rewrite'             => [ 'slug' => $permalink_slug ],
				'show_in_rest'        => true,
				'rest_base'           => 'anwp_clubs',
				'menu_icon'           => 'dashicons-shield',
				'exclude_from_search' => 'hide' === AnWPFL_Options::get_value( 'display_front_end_search_club' ),
				'supports'            => [
					'title',
					'comments',
				],
			]
		);
	}

	/**
	 * Filter post updated messages for the Club CPT.
	 *
	 * @since 0.17.3
	 *
	 * @param array $messages Post updated messages.
	 *
	 * @return array
	 */
	public function messages( $messages ) {
		global $post, $post_ID;

		$messages['anwp_club'] = [
			0  => '',
			1  => sprintf( '%s <a href="%s">%s</a>', esc_html__( 'Club updated.', 'anwp-football-leagues' ), esc_url( get_permalink( $post_ID ) ), esc_html__( 'View Club', 'anwp-football-leagues' ) ),
			2  => esc_html__( 'Custom field updated.', 'anwp-football-leagues' ),
			3  => esc_html__( 'Custom field deleted.', 'anwp-football-leagues' ),
			4  => esc_html__( 'Club updated.', 'anwp-football-leagues' ),
			// translators: %s: revision date
			5  => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Club restored to revision from %s', 'anwp-football-leagues' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification
			6  => sprintf( '%s <a href="%s">%s</a>', esc_html__( 'Club published.', 'anwp-football-leagues' ), esc_url( get_permalink( $post_ID ) ), esc_html__( 'View Club', 'anwp-football-leagues' ) ),
			7  => esc_html__( 'Club saved.', 'anwp-football-leagues' ),
			8  => sprintf( '%s <a target="_blank" href="%s">%s</a>', esc_html__( 'Club submitted.', 'anwp-football-leagues' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ), esc_html__( 'Preview Club', 'anwp-football-leagues' ) ),
			// translators: %1$s: scheduled date
			9  => sprintf( esc_html__( 'Club scheduled for: %1$s.', 'anwp-football-leagues' ), '<strong>' . date_i18n( esc_html__( 'M j, Y @ G:i', 'anwp-football-leagues' ), strtotime( $post->post_date ) ) . '</strong>' ),
			10 => sprintf( '%s <a target="_blank" href="%s">%s</a>', esc_html__( 'Club draft updated.', 'anwp-football-leagues' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ), esc_html__( 'Preview Club', 'anwp-football-leagues' ) ),
		];

		return $messages;
	}

	/**
	 * Filter bulk post updated messages for the Club CPT.
	 *
	 * @since 0.17.3
	 *
	 * @param array $bulk_messages Bulk updated messages.
	 * @param array $bulk_counts   Counts of updated posts.
	 *
	 * @return array
	 */
	public function bulk_messages( $bulk_messages, $bulk_counts ) {
		$bulk_messages['anwp_club'] = [
			// translators: %s: number of clubs
			'updated'   => sprintf( _n( '%s Club updated.', '%s Clubs updated.', $bulk_counts['updated'], 'anwp-football-leagues' ), $bulk_counts['updated'] ),
			// translators: %s: number of clubs
			'locked'    => sprintf( _n( '%s Club not updated, somebody is editing it.', '%s Clubs not updated, somebody is editing them.', $bulk_counts['locked'], 'anwp-football-leagues' ), $bulk_counts['locked'] ),
			// translators: %s: number of clubs
			'deleted'   => sprintf( _n( '%s Club permanently deleted.', '%s Clubs permanently deleted.', $bulk_counts['deleted'], 'anwp-football-leagues' ), $bulk_counts['deleted'] ),
			// translators: %s: number of clubs
			'trashed'   => sprintf( _n( '%s Club moved to the Trash.', '%s Clubs moved to the Trash.', $bulk_counts['trashed'], 'anwp-football-leagues' ), $bulk_counts['trashed'] ),
			// translators: %s: number of clubs
			'untrashed' => sprintf( _n( '%s Club restored from the Trash.', '%s Clubs restored from the Trash.', $bulk_counts['untrashed'], 'anwp-football-leagues' ), $bulk_counts['untrashed'] ),
		];

		return $bulk_messages;
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
		if ( isset( $screen->post_type ) && 'anwp_club' === $screen->post_type ) {
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

		// CPT registration and admin hooks (previously handled by CPT_Core).
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_filter( 'post_updated_messages', [ $this, 'messages' ] );
		add_filter( 'bulk_post_updated_messages', [ $this, 'bulk_messages' ], 10, 2 );
		add_filter( 'manage_edit-anwp_club_columns', [ $this, 'columns' ] );
		add_filter( 'manage_edit-anwp_club_sortable_columns', [ $this, 'sortable_columns' ] );
		add_action( 'manage_posts_custom_column', [ $this, 'columns_display' ], 10, 2 );
		add_filter( 'enter_title_here', [ $this, 'title' ] );

		add_action( 'rest_api_init', [ $this, 'add_rest_routes' ] );

		// Create & save Squad metabox
		add_action( 'load-post.php', [ $this, 'init_metaboxes' ] );
		add_action( 'load-post-new.php', [ $this, 'init_metaboxes' ] );
		add_action( 'save_post', [ $this, 'save_metabox' ], 10, 2 );

		add_action( 'restrict_manage_posts', [ $this, 'add_more_filters' ] );
		add_filter( 'pre_get_posts', [ $this, 'handle_custom_filter' ] );

		// Sync club data to custom table after CMB2 saves (priority 10).
		add_action( 'save_post_anwp_club', [ $this, 'sync_club_to_table' ], 999 );

		// Remove club row on permanent delete.
		add_action( 'delete_post', [ $this, 'on_club_delete' ] );

		// Render Custom Content below
		add_action(
			'anwpfl/tmpl-club/after_wrapper',
			function ( $club ) {
				$row = anwp_fl()->club->get_row( (int) $club->ID );

				$content_below = $row['custom_content_below'] ?? '';

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

		// Block render until clubs migration is complete (0.18.0).
		// save_metabox() also silently no-ops in this state, so the editable form is misleading.
		if ( ! get_option( 'anwpfl_clubs_migrated' ) ) {
			AnWP_Football_Leagues::print_migration_required_panel(
				__( 'Clubs Data Migration Required', 'anwp-football-leagues' )
			);
			return;
		}

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
							<svg class="anwp-icon anwp-icon--feather anwp-icon--s16"><use href="#icon-save"></use></svg>
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
									'icon'  => 'server',
									'label' => __( 'Custom Fields', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-custom_fields-metabox',
								],
								[
									'icon'  => 'note',
									'label' => __( 'Description', 'anwp-football-leagues' ),
									'slug'  => 'anwp-fl-desc-metabox',
								],
								[
									'icon'  => 'repo-push',
									'label' => __( 'Additional Content', 'anwp-football-leagues' ),
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
						// -----------------------------------------------
						// Vue Club Editor (replaces CMB2 form)
						// -----------------------------------------------
						$club_row  = $this->get_row( $post->ID );
						$club_data = $club_row ? $club_row : [];

						// Decode JSON columns for the Vue app.
						foreach ( [ 'club_details', 'club_social', 'club_custom', 'club_shirts', 'club_roles', 'report_email' ] as $json_col ) {
							if ( ! empty( $club_data[ $json_col ] ) && is_string( $club_data[ $json_col ] ) ) {
								$club_data[ $json_col ] = json_decode( $club_data[ $json_col ], true );
							}
						}

						// Custom field names from plugin settings.
						$custom_field_names = AnWPFL_Options::get_value( 'club_custom_fields' ) ?: [];

						$club_edit_data = [
							'club_id'   => $post->ID,
							'club_data' => $club_data,
							'options'   => [
								'countries'          => anwp_fl()->data->cb_get_countries(),
								'stadiums'           => anwp_fl()->stadium->get_stadiums_options(),
								'root_clubs'         => $this->get_root_clubs_options(),
								'all_clubs'          => $this->get_clubs_options(),
								'custom_field_names' => $custom_field_names,
							],
							'l10n' => [
								'general'        => __( 'General', 'anwp-football-leagues' ),
								'media'          => __( 'Media', 'anwp-football-leagues' ),
								'social'         => __( 'Social', 'anwp-football-leagues' ),
								'subteams'       => __( 'Subteams', 'anwp-football-leagues' ),
								'custom_fields'  => __( 'Custom Fields', 'anwp-football-leagues' ),
								'description'        => __( 'Description', 'anwp-football-leagues' ),
								'additional_content' => __( 'Additional Content', 'anwp-football-leagues' ),
								'abbreviation'   => __( 'Abbreviation', 'anwp-football-leagues' ),
								'city'           => __( 'City', 'anwp-football-leagues' ),
								'nationality'    => __( 'Nationality', 'anwp-football-leagues' ),
								'stadium'        => __( 'Stadium', 'anwp-football-leagues' ),
								'address'        => __( 'Address', 'anwp-football-leagues' ),
								'website'        => __( 'Website', 'anwp-football-leagues' ),
								'founded'        => __( 'Founded', 'anwp-football-leagues' ),
								'external_id'    => __( 'External ID', 'anwp-football-leagues' ),
								'national_team'  => __( 'National Team', 'anwp-football-leagues' ),
								'title'          => __( 'Title', 'anwp-football-leagues' ),
								'value'          => __( 'Value', 'anwp-football-leagues' ),
								'logo'           => __( 'Logo', 'anwp-football-leagues' ),
								'logo_big'       => __( 'Logo (Big)', 'anwp-football-leagues' ),
								'club_kit'       => __( 'Club Kit', 'anwp-football-leagues' ),
								'main_color'     => __( 'Main Color', 'anwp-football-leagues' ),
								'gallery'        => __( 'Gallery', 'anwp-football-leagues' ),
								'gallery_notes'  => __( 'Gallery Notes', 'anwp-football-leagues' ),
								'select_image'   => __( 'Select Image', 'anwp-football-leagues' ),
								'select_images'  => __( 'Select Images', 'anwp-football-leagues' ),
								'add_images'     => __( 'Add Images', 'anwp-football-leagues' ),
								'add_to_gallery' => __( 'Add to Gallery', 'anwp-football-leagues' ),
								'remove'         => __( 'Remove', 'anwp-football-leagues' ),
								'clear'          => __( 'Clear', 'anwp-football-leagues' ),
								'none'           => __( 'None', 'anwp-football-leagues' ),
								'add'            => __( 'Add', 'anwp-football-leagues' ),
								'subteam_status' => __( 'Subteam Status', 'anwp-football-leagues' ),
								'root_team'      => __( 'Root Team', 'anwp-football-leagues' ),
								'subteam'        => __( 'Subteam', 'anwp-football-leagues' ),
								'root_type'      => __( 'Root Type', 'anwp-football-leagues' ),
								'team'           => __( 'Team', 'anwp-football-leagues' ),
								'summary'        => __( 'Summary', 'anwp-football-leagues' ),
								'root_team_title' => __( 'Root Team Title', 'anwp-football-leagues' ),
								'subteam_list'   => __( 'Subteam List', 'anwp-football-leagues' ),
							],
						];

						/**
						 * Filter club editor window data.
						 * Used by premium to inject additional options and tab definitions.
						 *
						 * @since 0.17.3
						 *
						 * @param array   $club_edit_data Club editor data.
						 * @param WP_Post $post           Club post object.
						 */
						$club_edit_data = apply_filters( 'anwpfl/club-editor/window-data', $club_edit_data, $post );
						?>
						<script type="text/javascript">
							window._anwpClubEdit = <?php echo wp_json_encode( $club_edit_data ); ?>;
						</script>
						<input type="hidden" name="anwp_club_data" id="anwp_club_data">
						<div id="fl-app-club-editor"></div>

						<?php // --- WYSIWYG: Info (description) --- ?>
						<div id="anwp-fl-desc-metabox" class="anwp-mb-4">
							<div class="anwp-border anwp-border-gray-500">
								<div class="anwp-border-bottom anwp-border-gray-500 bg-white d-flex align-items-center px-3 py-2 anwp-text-gray-700 anwp-font-semibold">
									<svg class="anwp-icon anwp-icon--octi anwp-icon--s16 mr-2 anwp-flex-none anwp-fill-current">
										<use href="#icon-note"></use>
									</svg>
									<?php echo esc_html__( 'Description', 'anwp-football-leagues' ); ?>
								</div>
								<div class="bg-white p-3">
									<?php
									wp_editor(
										$club_data['description'] ?? '',
										'anwp_club_description',
										[
											'textarea_name' => 'anwp_club_description',
											'textarea_rows' => 10,
											'media_buttons' => true,
										]
									);
									?>
								</div>
							</div>
						</div>

						<?php // --- WYSIWYG: Bottom Content --- ?>
						<div id="anwp-fl-bottom_content-metabox" class="anwp-mb-4">
							<div class="anwp-border anwp-border-gray-500">
								<div class="anwp-border-bottom anwp-border-gray-500 bg-white d-flex align-items-center px-3 py-2 anwp-text-gray-700 anwp-font-semibold">
									<svg class="anwp-icon anwp-icon--octi anwp-icon--s16 mr-2 anwp-flex-none anwp-fill-current">
										<use href="#icon-repo-push"></use>
									</svg>
									<?php echo esc_html__( 'Additional Content', 'anwp-football-leagues' ); ?>
								</div>
								<div class="bg-white p-3">
									<?php
									wp_editor(
										$club_data['custom_content_below'] ?? '',
										'anwp_club_bottom_content',
										[
											'textarea_name' => 'anwp_club_bottom_content',
											'textarea_rows' => 10,
											'media_buttons' => true,
										]
									);
									?>
								</div>
							</div>
						</div>

						<?php
						// -----------------------------------------------
						// Squad app data (unchanged from before)
						// -----------------------------------------------
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
							'active_canonical_tooltip'      => esc_html__( 'Canonical roster - shortcodes default to this', 'anwp-football-leagues' ),
							'active_squad'                  => esc_html__( 'Active Squad', 'anwp-football-leagues' ),
							'active_staff'                  => esc_html__( 'Active Staff', 'anwp-football-leagues' ),
							'add_season_tab'                => esc_html__( 'Add season', 'anwp-football-leagues' ),
							'append_active_to_season'       => esc_html__( 'Append Active to this', 'anwp-football-leagues' ),
							'append_to_active'              => esc_html__( 'Append to active', 'anwp-football-leagues' ),
							'clear'                         => esc_html__( 'Clear', 'anwp-football-leagues' ),
							'confirm'                       => esc_html__( 'Confirm', 'anwp-football-leagues' ),
							'copy_active_to_season'         => esc_html__( 'Save as season...', 'anwp-football-leagues' ),
							'copy_season_to_active'         => esc_html__( 'Copy this to Active', 'anwp-football-leagues' ),
							'delete_season_tab'             => esc_html__( 'Delete season tab', 'anwp-football-leagues' ),
							'delete_season_tab_confirm'     => esc_html__( 'Remove this season from the archive?', 'anwp-football-leagues' ),
							'more_actions'                  => esc_html__( 'More actions', 'anwp-football-leagues' ),
							'no_players_in_squad'           => esc_html__( 'No players in squad', 'anwp-football-leagues' ),
							'replace_season_from_active'    => esc_html__( 'Replace from Active', 'anwp-football-leagues' ),
							'scroll_left'                   => esc_html__( 'Scroll left', 'anwp-football-leagues' ),
							'scroll_right'                  => esc_html__( 'Scroll right', 'anwp-football-leagues' ),
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
							'player_name'                   => esc_html__( 'Player Name', 'anwp-football-leagues' ),
							'position'                      => esc_html__( 'Position', 'anwp-football-leagues' ),
							'prev'                          => esc_html__( 'Prev', 'anwp-football-leagues' ),
							'really_want_delete_from_squad' => esc_html__( 'Do you really want to delete from current squad', 'anwp-football-leagues' ),
							'remove'                        => esc_html__( 'Remove', 'anwp-football-leagues' ),
							'save'                          => esc_html__( 'Save', 'anwp-football-leagues' ),
							'saved'                         => esc_html__( 'Saved', 'anwp-football-leagues' ),
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
								$positions[ substr( $position, 0, 1 ) ] = anwp_fl()->get_option_value( 'text_single_' . $position );
							}
						}

						// Read squad data from custom table (with pre-migration postmeta fallback).
						$club_row             = $this->get_row( $post->ID );
						$squad_active         = '';
						$staff_active         = '';
						$squad_seasons_json   = '';
						$staff_seasons_json   = '';
						$squad_display_group  = 1;

						if ( $club_row ) {
							$squad_active        = $club_row['squad'] ?? '';
							$staff_active        = $club_row['squad_staff'] ?? '';
							$squad_seasons_json  = $club_row['squad_seasons'] ?? '';
							$staff_seasons_json  = $club_row['squad_staff_seasons'] ?? '';
							$squad_display_group = isset( $club_row['squad_group'] ) ? (int) $club_row['squad_group'] : 1;
						}

						// Pre-migration fallback: read active roster from postmeta (active season key).
						if ( ! get_option( 'anwpfl_squad_migrated' ) ) {
							if ( '' === $squad_active ) {
								$squad_active = $this->get_squad_from_postmeta( $post->ID );
							}

							if ( '' === $staff_active ) {
								$staff_active = $this->get_staff_from_postmeta( $post->ID );
							}

							// Surface the legacy postmeta archive so the admin can browse / migrate
							// per-season squads even before the table migration runs.
							if ( '' === $squad_seasons_json ) {
								$legacy_squad = get_post_meta( $post->ID, '_anwpfl_squad', true );

								if ( $legacy_squad ) {
									$squad_seasons_json = $legacy_squad;
								}
							}

							if ( '' === $staff_seasons_json ) {
								$legacy_staff = get_post_meta( $post->ID, '_anwpfl_staff', true );

								if ( $legacy_staff ) {
									$staff_seasons_json = $legacy_staff;
								}
							}
						}

						$squad_data = [
							'club_squad'          => $squad_active,
							'club_staff'          => $staff_active,
							'club_squad_seasons'  => $squad_seasons_json,
							'club_staff_seasons'  => $staff_seasons_json,
							'club_squad_display'  => $squad_display_group,
							'currentClub'         => $post->ID,
							'players'             => $this->plugin->player->get_player_obj_list(),
							'staffs'              => $this->plugin->staff->get_staff_list(),
							'default_photo'       => anwp_fl()->helper->get_default_player_photo(),
							'clubs_map'           => $this->get_clubs_options(),
							'l10n'                => anwp_fl()->helper->entity_decode( $l10n ),
							'positions'           => $positions,
							'seasons_list'        => $this->plugin->season->get_seasons_list(),
							'loader'              => includes_url( 'js/tinymce/skins/lightgray/img/loader.gif' ),
							'rest_root'           => esc_url_raw( rest_url() ),
							'rest_nonce'          => wp_create_nonce( 'wp_rest' ),
							'squad_status'        => AnWPFL_Options::get_value( 'custom_squad_status' ) ? : [],
						];

						?>
						<div id="anwp-fl-squad-metabox" class="mt-4">
							<script type="text/javascript">
								window._AnWP_FL_Squad_Data = <?php echo wp_json_encode( apply_filters( 'anwpfl/club/squad_app_data', $squad_data ) ); ?>;
							</script>
							<div id="fl-app-squad"></div>
						</div>

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
							<use href="#icon-jersey"></use>
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

		// Block saves until clubs migration is complete.
		if ( ! get_option( 'anwpfl_clubs_migrated' ) ) {
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
		 * Save Squad/Staff Data
		 *
		 * @since 0.2.0
		 * ---------------------------------------*/

		if ( get_option( 'anwpfl_squad_migrated' ) ) {
			// Post-migration: write to custom table.
			$squad_update = [];

			if ( isset( $_POST['_anwpfl_squad'] ) ) {
				$squad_update['squad'] = wp_json_encode( json_decode( wp_unslash( $_POST['_anwpfl_squad'] ) ) ) ?: '';
			}

			if ( isset( $_POST['_anwpfl_staff'] ) ) {
				$squad_update['squad_staff'] = wp_json_encode( json_decode( wp_unslash( $_POST['_anwpfl_staff'] ) ) ) ?: '';
			}

			if ( isset( $_POST['_anwpfl_squad_seasons'] ) ) {
				$squad_update['squad_seasons'] = wp_json_encode( json_decode( wp_unslash( $_POST['_anwpfl_squad_seasons'] ) ) ) ?: '';
			}

			if ( isset( $_POST['_anwpfl_staff_seasons'] ) ) {
				$squad_update['squad_staff_seasons'] = wp_json_encode( json_decode( wp_unslash( $_POST['_anwpfl_staff_seasons'] ) ) ) ?: '';
			}

			if ( isset( $_POST['_anwpfl_squad_display'] ) ) {
				$display_data = json_decode( wp_unslash( $_POST['_anwpfl_squad_display'] ), true );
				$squad_update['squad_group'] = ! empty( $display_data['group'] ) ? 1 : 0;
			}

			if ( ! empty( $squad_update ) ) {
				global $wpdb;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->update(
					$wpdb->prefix . 'anwpfl_clubs',
					$squad_update,
					[ 'club_id' => $post_id ]
				);

				// Invalidate per-request cache.
				$this->invalidate_cache( $post_id );
			}
		} else {
			// Pre-migration: reconstruct legacy season-keyed postmeta from new POST shape.
			//
			// The new Vue UI sends a flat active roster (`_anwpfl_squad`) plus the full
			// season archive (`_anwpfl_squad_seasons`). The legacy postmeta format is a
			// single season-keyed object. We merge: take the archive Vue sent and overlay
			// the active roster under the active season key. Without this merge, saving
			// pre-migration would wipe every season other than active because the flat
			// active array would replace the legacy object.
			$active_season_id = (int) anwp_fl()->get_active_season();

			// --- Squad ---
			if ( isset( $_POST['_anwpfl_squad_seasons'] ) || isset( $_POST['_anwpfl_squad'] ) ) {

				$seasons_obj = [];

				if ( isset( $_POST['_anwpfl_squad_seasons'] ) ) {
					$decoded = json_decode( wp_unslash( $_POST['_anwpfl_squad_seasons'] ), true );

					if ( is_array( $decoded ) ) {
						$seasons_obj = $decoded;
					}
				}

				if ( $active_season_id && isset( $_POST['_anwpfl_squad'] ) ) {
					$active_arr = json_decode( wp_unslash( $_POST['_anwpfl_squad'] ), true );

					if ( is_array( $active_arr ) ) {
						$seasons_obj[ 's:' . $active_season_id ] = $active_arr;
					}
				}

				$encoded = wp_json_encode( $seasons_obj );

				if ( false !== $encoded ) {
					update_post_meta( $post_id, '_anwpfl_squad', wp_slash( $encoded ) );
				}
			}

			// --- Staff ---
			if ( isset( $_POST['_anwpfl_staff_seasons'] ) || isset( $_POST['_anwpfl_staff'] ) ) {

				$seasons_obj = [];

				if ( isset( $_POST['_anwpfl_staff_seasons'] ) ) {
					$decoded = json_decode( wp_unslash( $_POST['_anwpfl_staff_seasons'] ), true );

					if ( is_array( $decoded ) ) {
						$seasons_obj = $decoded;
					}
				}

				if ( $active_season_id && isset( $_POST['_anwpfl_staff'] ) ) {
					$active_arr = json_decode( wp_unslash( $_POST['_anwpfl_staff'] ), true );

					if ( is_array( $active_arr ) ) {
						$seasons_obj[ 's:' . $active_season_id ] = $active_arr;
					}
				}

				$encoded = wp_json_encode( $seasons_obj );

				if ( false !== $encoded ) {
					update_post_meta( $post_id, '_anwpfl_staff', wp_slash( $encoded ) );
				}
			}

			// --- Squad display: keep legacy season-keyed postmeta intact, set active key. ---
			if ( $active_season_id && isset( $_POST['_anwpfl_squad_display'] ) ) {
				$display_data = json_decode( wp_unslash( $_POST['_anwpfl_squad_display'] ), true );

				if ( is_array( $display_data ) && isset( $display_data['group'] ) ) {
					$existing = json_decode( get_post_meta( $post_id, '_anwpfl_squad_display', true ), true );
					$existing = is_array( $existing ) ? $existing : [];

					$existing[ $active_season_id ] = [ 'group' => (bool) $display_data['group'] ];

					$encoded = wp_json_encode( $existing );

					if ( false !== $encoded ) {
						update_post_meta( $post_id, '_anwpfl_squad_display', wp_slash( $encoded ) );
					}
				}
			}
		}

		/** ---------------------------------------
		 * Save Vue Club Editor data
		 *
		 * @since 0.17.3
		 * ---------------------------------------*/

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! empty( $_POST['anwp_club_data'] ) ) {
			$club_json = json_decode( wp_unslash( $_POST['anwp_club_data'] ), true );

			if ( is_array( $club_json ) && (int) ( $club_json['club_id'] ?? 0 ) === $post_id ) {
				$this->save_vue_club_data( $post_id, $club_json );
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
	 * Save club data from Vue editor JSON payload.
	 *
	 * Sanitizes all fields and writes directly to anwpfl_clubs via upsert().
	 *
	 * @since 0.17.3
	 *
	 * @param int   $post_id   Club post ID.
	 * @param array $club_json Decoded JSON from $_POST['anwp_club_data'].
	 */
	private function save_vue_club_data( int $post_id, array $club_json ): void {
		$data = [
			'club_id' => $post_id,
		];

		// --- Flat string columns ---
		$data['abbr']             = sanitize_text_field( $club_json['abbr'] ?? '' );
		$data['city']             = sanitize_text_field( $club_json['city'] ?? '' );
		$data['nationality']      = sanitize_text_field( $club_json['nationality'] ?? '' );
		$data['club_external_id'] = sanitize_text_field( $club_json['club_external_id'] ?? '' );
		$data['main_color']       = sanitize_hex_color( $club_json['main_color'] ?? '' ) ?: '';
		$data['subteams']         = sanitize_text_field( $club_json['subteams'] ?? '' );
		$data['root_type']        = sanitize_text_field( $club_json['root_type'] ?? '' );

		// --- Flat integer columns ---
		$data['stadium_id']       = absint( $club_json['stadium_id'] ?? 0 );
		$data['is_national_team'] = absint( $club_json['is_national_team'] ?? 0 ) ? 1 : 0;
		$data['root_team']        = absint( $club_json['root_team'] ?? 0 );
		$data['logo_id']          = absint( $club_json['logo_id'] ?? 0 );
		$data['logo_big_id']      = absint( $club_json['logo_big_id'] ?? 0 );

		// --- URL columns ---
		$data['logo']     = esc_url_raw( $club_json['logo'] ?? '' );
		$data['logo_big'] = esc_url_raw( $club_json['logo_big'] ?? '' );

		// --- WYSIWYG from wp_editor (not from Vue JSON) ---
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in save_metabox().
		$data['description']          = wp_kses_post( wp_unslash( $_POST['anwp_club_description'] ?? '' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data['custom_content_below'] = wp_kses_post( wp_unslash( $_POST['anwp_club_bottom_content'] ?? '' ) );

		// --- club_details JSON ---
		$club_details         = $club_json['club_details'] ?? [];
		$data['club_details'] = wp_json_encode( [
			'address'         => sanitize_text_field( $club_details['address'] ?? '' ),
			'website'         => esc_url_raw( $club_details['website'] ?? '' ),
			'founded'         => sanitize_text_field( $club_details['founded'] ?? '' ),
			'club_kit'        => esc_url_raw( $club_details['club_kit'] ?? '' ),
			'club_kit_id'     => absint( $club_details['club_kit_id'] ?? 0 ),
			'gallery'         => array_map( 'esc_url_raw', (array) ( $club_details['gallery'] ?? [] ) ),
			'gallery_notes'   => sanitize_textarea_field( $club_details['gallery_notes'] ?? '' ),
			'root_team_title' => sanitize_text_field( $club_details['root_team_title'] ?? '' ),
			'subteam_list'    => $this->sanitize_subteam_list( $club_details['subteam_list'] ?? [] ),
		] );

		// --- club_social JSON ---
		$club_social         = $club_json['club_social'] ?? [];
		$data['club_social'] = wp_json_encode( array_map( 'esc_url_raw', [
			'twitter'   => $club_social['twitter'] ?? '',
			'facebook'  => $club_social['facebook'] ?? '',
			'youtube'   => $club_social['youtube'] ?? '',
			'instagram' => $club_social['instagram'] ?? '',
			'linkedin'  => $club_social['linkedin'] ?? '',
			'tiktok'    => $club_social['tiktok'] ?? '',
			'vk'        => $club_social['vk'] ?? '',
		] ) );

		// --- club_custom JSON ---
		$club_custom         = $club_json['club_custom'] ?? [];
		$data['club_custom'] = wp_json_encode( [
			'custom_title_1' => sanitize_text_field( $club_custom['custom_title_1'] ?? '' ),
			'custom_value_1' => sanitize_text_field( $club_custom['custom_value_1'] ?? '' ),
			'custom_title_2' => sanitize_text_field( $club_custom['custom_title_2'] ?? '' ),
			'custom_value_2' => sanitize_text_field( $club_custom['custom_value_2'] ?? '' ),
			'custom_title_3' => sanitize_text_field( $club_custom['custom_title_3'] ?? '' ),
			'custom_value_3' => sanitize_text_field( $club_custom['custom_value_3'] ?? '' ),
			'custom_fields'  => (object) array_map( 'sanitize_text_field', (array) ( $club_custom['custom_fields'] ?? [] ) ),
		] );

		// --- Determine update columns ---
		$update_columns = [
			'abbr', 'city', 'nationality', 'stadium_id', 'is_national_team',
			'club_external_id', 'subteams', 'root_team', 'root_type', 'logo', 'logo_id',
			'logo_big', 'logo_big_id', 'main_color', 'description', 'custom_content_below',
			'club_details', 'club_social', 'club_custom',
		];

		// Premium columns are sanitized and added via this filter.
		$data           = apply_filters( 'anwpfl/club/save_data', $data, $club_json );
		$update_columns = apply_filters( 'anwpfl/club/save_update_columns', $update_columns, $data );

		$this->upsert( $post_id, $data, $update_columns );
		$this->vue_save_handled = true;

		// Clear per-request caches.
		unset( $this->list_cache[ $post_id ] );
	}

	/**
	 * Sanitize subteam list entries.
	 *
	 * Full implementation in Phase 8.4 (TabSubteams).
	 *
	 * @since 0.17.3
	 *
	 * @param array $list Raw subteam list from Vue.
	 * @return array Sanitized list.
	 */
	private function sanitize_subteam_list( array $list ): array {
		$sanitized = [];

		foreach ( $list as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$sanitized[] = [
				'title'   => sanitize_text_field( $item['title'] ?? '' ),
				'club_id' => absint( $item['club_id'] ?? 0 ),
			];
		}

		return $sanitized;
	}

	/**
	 * Allowed HTML tags for SVG shirt content.
	 *
	 * Used with wp_kses() to sanitize SVG markup from admin input.
	 * Admin-only (manage_options capability required).
	 *
	 * @since 0.17.3
	 *
	 * @return array Allowed tags and attributes for wp_kses().
	 */
	public static function allowed_svg_tags(): array {
		return [
			'svg'      => [
				'xmlns'       => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
				'fill'        => true,
				'class'       => true,
				'style'       => true,
				'id'          => true,
				'version'     => true,
				'xml:space'   => true,
			],
			'g'        => [
				'fill'        => true,
				'stroke'      => true,
				'stroke-width' => true,
				'transform'   => true,
				'id'          => true,
				'class'       => true,
				'style'       => true,
			],
			'path'     => [
				'd'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'style'        => true,
				'id'           => true,
				'transform'    => true,
			],
			'rect'     => [
				'x'      => true, 'y'      => true,
				'width'  => true, 'height' => true,
				'rx'     => true, 'ry'     => true,
				'fill'   => true, 'stroke' => true,
				'stroke-width' => true, 'class' => true,
				'style'  => true, 'id'     => true,
				'transform' => true,
			],
			'circle'   => [
				'cx'     => true, 'cy' => true, 'r' => true,
				'fill'   => true, 'stroke' => true,
				'stroke-width' => true, 'class' => true,
				'style'  => true, 'id' => true,
			],
			'ellipse'  => [
				'cx'     => true, 'cy' => true,
				'rx'     => true, 'ry' => true,
				'fill'   => true, 'stroke' => true,
				'stroke-width' => true, 'class' => true,
				'style'  => true, 'id' => true,
			],
			'line'     => [
				'x1'     => true, 'y1' => true,
				'x2'     => true, 'y2' => true,
				'stroke' => true, 'stroke-width' => true,
				'class'  => true, 'style' => true,
			],
			'polygon'  => [
				'points' => true, 'fill' => true,
				'stroke' => true, 'stroke-width' => true,
				'class'  => true, 'style' => true,
			],
			'polyline' => [
				'points' => true, 'fill' => true,
				'stroke' => true, 'stroke-width' => true,
				'class'  => true, 'style' => true,
			],
			'text'     => [
				'x'           => true, 'y'      => true,
				'font-size'   => true, 'fill'   => true,
				'text-anchor' => true, 'class'  => true,
				'style'       => true, 'id'     => true,
				'transform'   => true,
			],
			'tspan'    => [
				'x'     => true, 'y'    => true, 'dx' => true, 'dy' => true,
				'fill'  => true, 'class' => true, 'style' => true,
			],
			'defs'     => [],
			'clippath' => [ 'id' => true ],
			'use'      => [
				'href'      => true,
				'xlink:href' => true,
				'x'         => true,
				'y'         => true,
				'width'     => true,
				'height'    => true,
			],
		];
	}

	/**
	 * Get list of club objects.
	 *
	 * @since 0.2.0 (2017-11-29)
	 * @return array $output_data - Array of clubs data (id, title, logo)
	 */
	public function get_clubs_list() {

		global $wpdb;

		// SELECT the full light-column set (not just display fields) so we can populate
		// $list_cache in the same pass. Any caller that later invokes get_team_country(),
		// get_club_logo_by_id(), get_club() etc. inside a foreach hits cache instead of
		// firing one get_row() SELECT per club. See CLAUDE.md silent-failure #21 and the
		// 2,534-query Add/Edit Competition incident (2026-04-15).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT club_id, title, post_name, abbr, nationality, is_national_team, logo, logo_big, logo_id, logo_big_id, main_color FROM $this->table_name ORDER BY title ASC",
			ARRAY_A
		);

		$output_data = [];

		foreach ( $rows as $row ) {
			$club_id                      = (int) $row['club_id'];
			$this->list_cache[ $club_id ] = $row;

			$output_data[] = (object) [
				'id'    => $club_id,
				'title' => $row['title'],
				'logo'  => $row['logo'] ?: $row['logo_big'],
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

		$club_id = (int) $data['club'];

		if ( ! $club_id ) {
			return $output_data;
		}

		$row = $this->get_row( $club_id );

		if ( ! $row ) {
			return $output_data;
		}

		$season_id = absint( $data['id'] ?? 0 );

		if ( $season_id ) {
			// Specific season: archive -> stats-derived -> active fallback.
			$seasons   = json_decode( $row['squad_seasons'] ?? '', true ) ?: [];
			$squad_arr = $seasons[ 's:' . $season_id ] ?? [];

			if ( empty( $squad_arr ) ) {
				$squad_arr = $this->derive_roster_from_stats( $club_id, $season_id );
			}

			if ( empty( $squad_arr ) ) {
				// Last resort: active roster (with pre-migration postmeta fallback).
				$squad_json = $row['squad'] ?? '';

				if ( '' === $squad_json && ! get_option( 'anwpfl_squad_migrated' ) ) {
					$squad_json = $this->get_squad_from_postmeta( $club_id );
				}

				$squad_arr = json_decode( $squad_json, true ) ?: [];
			}

			$output_data = array_map( static fn( $a ) => (object) $a, $squad_arr );
		} else {
			$squad_json = $row['squad'] ?? '';

			// Pre-migration fallback: read from postmeta.
			if ( '' === $squad_json && ! get_option( 'anwpfl_squad_migrated' ) ) {
				$squad_json = $this->get_squad_from_postmeta( $club_id );
			}

			// Decode as objects to preserve caller contract ($player->id, etc.).
			$output_data = json_decode( $squad_json ) ?: [];
		}

		if ( empty( $output_data ) ) {
			return $output_data;
		}

		if ( 'short' === $output ) {
			return $output_data;
		}

		$players_data = anwp_fl()->player->get_players_by_ids( wp_list_pluck( $output_data, 'id' ) );

		foreach ( $output_data as $player ) {
			$player->name        = $players_data[ $player->id ]['name'] ?? '';
			$player->name_short  = $players_data[ $player->id ]['short_name'] ?? '';
			$player->nationality = $players_data[ $player->id ]['nationality'] ?? '';
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

		$club_id = (int) $data['club'];

		if ( ! $club_id ) {
			return $output_data;
		}

		$row = $this->get_row( $club_id );

		if ( ! $row ) {
			return $output_data;
		}

		$staff_json = $row['squad_staff'] ?? '';

		// Pre-migration fallback: read from postmeta.
		if ( '' === $staff_json && ! get_option( 'anwpfl_squad_migrated' ) ) {
			$staff_json = $this->get_staff_from_postmeta( $club_id );
		}

		$season_staff = json_decode( $staff_json ) ?: [];

		if ( ! empty( $season_staff ) ) {
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

		static $options = null;

		if ( null !== $options ) {
			return $options;
		}

		global $wpdb;

		// SELECT light columns (not just title) to populate $list_cache during the same
		// pass. The FL Selector modal REST endpoint iterates this and calls
		// get_club_logo_by_id() per club - without the cache priming below it fired
		// N+1 get_row() SELECTs. See silent-failure #21 and helper.php:1122.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT club_id, title, post_name, abbr, nationality, is_national_team, logo, logo_big, logo_id, logo_big_id, main_color FROM $this->table_name ORDER BY title ASC",
			ARRAY_A
		);

		$options = [];

		foreach ( $rows as $row ) {
			$club_id                      = (int) $row['club_id'];
			$this->list_cache[ $club_id ] = $row;

			$options[ $club_id ] = $row['title'];
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

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT club_id, title FROM $this->table_name WHERE subteams = 'root' ORDER BY title ASC",
			ARRAY_A
		);

		$options = [];

		foreach ( $rows as $row ) {
			$options[ (int) $row['club_id'] ] = $row['title'];
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

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT club_id, title FROM $this->table_name WHERE is_national_team = 1 ORDER BY title ASC",
			ARRAY_A
		);

		$options = [];

		foreach ( $rows as $row ) {
			$options[ (int) $row['club_id'] ] = $row['title'];
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

		if ( ! $team_id ) {
			return '';
		}

		$row = $this->get_club_list_row( $team_id );

		return $row ? ( $row['nationality'] ?? '' ) : '';
	}

	/**
	 * Get all clubs data.
	 *
	 * @deprecated 0.17.3 Use warm_clubs() + get_club_list_row() instead.
	 *
	 * @return array
	 */
	public function get_all_clubs_data() {

		_deprecated_function( __METHOD__, '0.17.3', 'warm_clubs() + get_club_list_row()' );

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT club_id, title, post_name, abbr, logo, logo_big FROM $this->table_name ORDER BY title ASC",
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return [];
		}

		$use_club_abbr  = apply_filters( 'anwpfl/club/use_club_abbr', true );
		$permalink_slug = $this->plugin->options->get_permalink_structure()['club'] ?? 'club';
		$base_url       = home_url( '/' . $permalink_slug . '/' );

		$output = [];

		foreach ( $rows as $row ) {
			$output[ (int) $row['club_id'] ] = [
				'title'    => $row['title'],
				'abbr'     => ( ! $use_club_abbr || empty( $row['abbr'] ) ) ? $row['title'] : $row['abbr'],
				'link'     => $base_url . $row['post_name'] . '/',
				'logo_big' => $row['logo_big'] ?? '',
				'logo'     => $row['logo'] ?? '',
			];
		}

		return $output;
	}

	/**
	 * Pre-migration fallback: read squad JSON for active season from postmeta.
	 *
	 * @since 0.18.0
	 *
	 * @param int $club_id
	 *
	 * @return string JSON string of active season's squad array, or ''.
	 */
	private function get_squad_from_postmeta( $club_id ) {
		$raw = json_decode( get_post_meta( $club_id, '_anwpfl_squad', true ), true );

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return '';
		}

		$active_key = 's:' . anwp_fl()->get_active_season();

		if ( ! empty( $raw[ $active_key ] ) ) {
			return wp_json_encode( $raw[ $active_key ] );
		}

		return '';
	}

	/**
	 * Pre-migration fallback: read staff JSON for active season from postmeta.
	 *
	 * @since 0.18.0
	 *
	 * @param int $club_id
	 *
	 * @return string JSON string of active season's staff array, or ''.
	 */
	private function get_staff_from_postmeta( $club_id ) {
		$raw = json_decode( get_post_meta( $club_id, '_anwpfl_staff', true ), true );

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return '';
		}

		$active_key = 's:' . anwp_fl()->get_active_season();

		if ( ! empty( $raw[ $active_key ] ) ) {
			return wp_json_encode( $raw[ $active_key ] );
		}

		return '';
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
	public function tmpl_prepare_club_squad( $club_id, $season_id = 0, $extended = false ) {

		$row   = $this->get_row( (int) $club_id );
		$squad = [];

		if ( ! $row ) {
			return $squad;
		}

		$club_id          = (int) $club_id;
		$season_id        = (int) $season_id;
		$active_season_id = (int) anwp_fl()->get_active_season();
		$is_active_view   = ( 0 === $season_id || $season_id === $active_season_id );
		$source           = '';

		if ( $is_active_view ) {
			// Default view OR user explicitly picked the configured Active Season.
			$squad_json = $row['squad'] ?? '';

			// Pre-migration fallback: read from postmeta.
			if ( '' === $squad_json && ! get_option( 'anwpfl_squad_migrated' ) ) {
				$squad_json = $this->get_squad_from_postmeta( $club_id );
			}

			$squad_arr = json_decode( $squad_json, true ) ?: [];
			$source    = 'active';
		} else {
			// Specific historical season requested: read from archive.
			$seasons   = json_decode( $row['squad_seasons'] ?? '', true ) ?: [];
			$squad_arr = $seasons[ 's:' . $season_id ] ?? [];
			$source    = $squad_arr ? 'season' : '';

			// Archive empty: check current-year shortcut. Useful when active_season
			// setting differs from the team's current-year season (e.g. national teams
			// using a single calendar-year season while club football spans two years).
			if ( empty( $squad_arr ) ) {
				$season_term = get_term( $season_id, 'anwp_season' );

				if ( $season_term && ! is_wp_error( $season_term ) && str_contains( (string) $season_term->name, gmdate( 'Y' ) ) ) {
					$squad_arr = json_decode( $row['squad'] ?? '', true ) ?: [];
					$source    = 'active_current_year';
				}
			}

			// Still empty: stats fallback ONLY when squad_seasons is entirely empty
			// (no archived data at all for this club). Clubs with partial archive
			// fall through to active squad - admin removed the season intentionally,
			// stats would resurface deleted data.
			if ( empty( $squad_arr ) && empty( $seasons ) ) {
				$squad_arr = $this->derive_roster_from_stats( $club_id, $season_id );
				$source    = $squad_arr ? 'stats' : '';
			}

			// Last resort: fall back to active squad.
			if ( empty( $squad_arr ) ) {
				$squad_arr = json_decode( $row['squad'] ?? '', true ) ?: [];
				$source    = $squad_arr ? 'active_fallback' : '';
			}
		}

		// Record source for the template label (read via get_last_squad_source()).
		self::$last_squad_source[ $club_id . ':' . $season_id ] = [
			'source'    => $source,
			'season_id' => $season_id,
		];

		if ( empty( $squad_arr ) ) {
			return $squad;
		}

		foreach ( $squad_arr as $s ) {
			$squad[ $s['id'] ] = [
				'position' => $s['position'] ?? '',
				'number'   => $s['number'] ?? '',
				'status'   => $s['status'] ?? '',
			];

			if ( $extended ) {
				$squad[ $s['id'] ]['name']        = '';
				$squad[ $s['id'] ]['photo']       = '';
				$squad[ $s['id'] ]['nationality'] = [];
				$squad[ $s['id'] ]['age']         = '';
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
	 * Read which source the last tmpl_prepare_club_squad() call used for a given
	 * club + season. Templates use this to render an "Active Squad" / "{Season} season"
	 * label above the player list.
	 *
	 * @param int $club_id   Club post ID.
	 * @param int $season_id Season term ID (0 for default view).
	 *
	 * @return array{source:string, season_id:int}|null
	 *               source ∈ '', 'season', 'active', 'active_current_year', 'stats', 'active_fallback'.
	 *               Returns null if tmpl_prepare_club_squad has not been called for this pair this request.
	 */
	public static function get_last_squad_source( int $club_id, int $season_id ): ?array {
		$key = $club_id . ':' . $season_id;
		return self::$last_squad_source[ $key ] ?? null;
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
	public function tmpl_prepare_club_staff( $club_id, $season_id = 0 ) {

		$row   = $this->get_row( (int) $club_id );
		$staff = [];

		if ( ! $row ) {
			return $staff;
		}

		if ( $season_id ) {
			// Specific season: read from archive.
			$seasons   = json_decode( $row['squad_staff_seasons'] ?? '', true ) ?: [];
			$staff_arr = $seasons[ 's:' . (int) $season_id ] ?? [];

			// Fall back to active staff if season not found in archive.
			if ( empty( $staff_arr ) ) {
				$staff_arr = json_decode( $row['squad_staff'] ?? '', true ) ?: [];
			}
		} else {
			// No season: read active staff.
			$staff_json = $row['squad_staff'] ?? '';

			// Pre-migration fallback: read from postmeta.
			if ( '' === $staff_json && ! get_option( 'anwpfl_squad_migrated' ) ) {
				$staff_json = $this->get_staff_from_postmeta( $club_id );
			}

			$staff_arr = json_decode( $staff_json, true ) ?: [];
		}

		if ( empty( $staff_arr ) ) {
			return $staff;
		}

		foreach ( $staff_arr as $s ) {
			$staff[ $s['id'] ] = [
				'job'         => $s['job'] ?? '',
				'grouping'    => $s['grouping'] ?? 'no',
				'group'       => $s['group'] ?? '',
				'name'        => '',
				'photo'       => '',
				'age'         => '',
				'nationality' => [],
				'link'        => '',
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

		$link_map = $this->plugin->helper->get_permalinks_by_ids( array_keys( $staff ), 'anwp_staff' );

		foreach ( $staff as $staff_id => &$staff_member_data ) {
			$staff_member_data['link'] = $link_map[ $staff_id ] ?? '';
		}
		unset( $staff_member_data );

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

		if ( ! $club_id ) {
			return '';
		}

		$row = $this->get_club_list_row( $club_id );

		return $row ? $row['title'] : '';
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

		if ( ! $club_id ) {
			return '';
		}

		$row = $this->get_club_list_row( $club_id );

		if ( ! $row || empty( $row['post_name'] ) ) {
			return '';
		}

		$permalink_slug = $this->plugin->options->get_permalink_structure()['club'] ?? 'club';

		return home_url( '/' . $permalink_slug . '/' . $row['post_name'] . '/' );
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

		if ( ! $club_id ) {
			return '';
		}

		$row = $this->get_club_list_row( $club_id );

		if ( ! $row ) {
			return '';
		}

		$primary   = $small ? 'logo' : 'logo_big';
		$secondary = $small ? 'logo_big' : 'logo';

		return ! empty( $row[ $primary ] ) ? $row[ $primary ] : ( $row[ $secondary ] ?? '' );
	}

	/**
	 * Registers admin columns to display. Hooked in via hooks().
	 *
	 * @param array $columns Array of registered column names/labels.
	 *
	 * @return array          Modified array.
	 */
	public function sortable_columns( $sortable_columns ) {

		return array_merge( $sortable_columns, [ 'club_id' => 'ID' ] );
	}

	/**
	 * Registers admin columns to display. Hooked in via hooks().
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
	 * Handles admin column display. Hooked in via hooks().
	 *
	 * @param array   $column  Column currently being rendered.
	 * @param integer $post_id ID of post to display column for.
	 *
	 * @since  0.1.0
	 */
	public function columns_display( $column, $post_id ) {

		$row = $this->get_club_list_row( (int) $post_id );

		switch ( $column ) {
			case 'anwpfl_club_logo':
				$logo = $row['logo'] ?? '';

				if ( $logo ) {
					printf( '<img src="%s" class="anwp-admin-table-club-logo" alt="club logo">', esc_url( $logo ) );
				}
				break;

			case 'club_id':
				echo (int) $post_id;
				break;

			case 'anwpfl_club_color':
				$team_color = $row['main_color'] ?? '';

				if ( $team_color ) {
					echo '<div style="background-color: ' . esc_attr( $team_color ) . '; width: 30px; height: 50px;">&nbsp;</div>';
				}
				break;

			case 'club_games':
				$counts    = $this->get_admin_list_game_counts();
				$games_num = $counts[ (int) $post_id ] ?? 0;

				if ( $games_num ) {
					echo '<a target="_blank" href="' . admin_url( 'edit.php?post_type=anwp_match&&post_status=all&_anwpfl_current_club=' . absint( $post_id ) ) . '">' . absint( $games_num ) . '</a>';
				} else {
					echo 0;
				}
				break;
		}
	}

	/**
	 * Per-request cache of admin-list game counts (club_id => count).
	 *
	 * @since 0.18.2
	 * @var int[]
	 */
	private array $admin_list_game_counts = [];

	/**
	 * Warm per-club match counts for the admin club list "Games" column.
	 *
	 * One GROUP BY query replaces the per-row
	 * tmpl_get_competition_matches_extended() call (4 queries × N clubs).
	 *
	 * Reads from $wpdb->anwpfl_matches directly: a match counts for a club
	 * when it appears as either home_club or away_club.
	 *
	 * @since 0.18.2
	 *
	 * @param int[] $club_ids Visible club post IDs.
	 */
	public function warm_admin_list_game_counts( array $club_ids ): void {
		$club_ids = array_unique( array_filter( array_map( 'absint', $club_ids ) ) );

		if ( empty( $club_ids ) ) {
			return;
		}

		global $wpdb;

		$format = implode( ', ', array_fill( 0, count( $club_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT club_id, SUM(cnt) AS total FROM (
					SELECT home_club AS club_id, COUNT(*) AS cnt FROM {$wpdb->anwpfl_matches} WHERE home_club IN ({$format}) GROUP BY home_club
					UNION ALL
					SELECT away_club AS club_id, COUNT(*) AS cnt FROM {$wpdb->anwpfl_matches} WHERE away_club IN ({$format}) GROUP BY away_club
				) t GROUP BY club_id",
				array_merge( $club_ids, $club_ids )
			),
			ARRAY_A
		);
		// phpcs:enable

		foreach ( $club_ids as $club_id ) {
			if ( ! isset( $this->admin_list_game_counts[ $club_id ] ) ) {
				$this->admin_list_game_counts[ $club_id ] = 0;
			}
		}

		foreach ( (array) $rows as $row ) {
			$this->admin_list_game_counts[ (int) $row['club_id'] ] = (int) $row['total'];
		}
	}

	/**
	 * Get per-request admin-list game counts (club_id => count).
	 *
	 * @since 0.18.2
	 *
	 * @return int[]
	 */
	public function get_admin_list_game_counts(): array {
		return $this->admin_list_game_counts;
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

		if ( ! $club_id ) {
			return '';
		}

		$row = $this->get_club_list_row( $club_id );

		if ( ! $row ) {
			return '';
		}

		$use_club_abbr = apply_filters( 'anwpfl/club/use_club_abbr', true );

		if ( ! $use_club_abbr || empty( $row['abbr'] ) ) {
			return $row['title'];
		}

		return $row['abbr'];
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

		$row = $this->get_club_list_row( (int) $club_id );

		return $row && (bool) $row['is_national_team'];
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

		$club_id = (int) $club_id;

		if ( ! $club_id ) {
			return false;
		}

		$row = $this->get_club_list_row( $club_id );

		if ( ! $row ) {
			return false;
		}

		$use_club_abbr  = apply_filters( 'anwpfl/club/use_club_abbr', true );
		$permalink_slug = $this->plugin->options->get_permalink_structure()['club'] ?? 'club';

		return (object) [
			'title'    => $row['title'],
			'abbr'     => ( ! $use_club_abbr || empty( $row['abbr'] ) ) ? $row['title'] : $row['abbr'],
			'link'     => home_url( '/' . $permalink_slug . '/' . $row['post_name'] . '/' ),
			'logo'     => $row['logo'] ?? '',
			'logo_big' => $row['logo_big'] ?? '',
		];
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

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT club_id FROM $this->table_name WHERE club_external_id = %s",
				$external_id
			)
		);
	}

	/**
	 * Get Club ID by title.
	 *
	 * @param string $title Club title to search for.
	 *
	 * @return int Club ID or 0 if not found.
	 * @since 0.17.2
	 */
	public function get_club_id_by_title( $title ) {

		if ( empty( $title ) ) {
			return 0;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$club_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT club_id FROM $this->table_name WHERE title = %s",
				trim( $title )
			)
		);

		return $club_id ? (int) $club_id : 0;
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
	public function get_squad_display_options( $club_id, $season_id = 0 ) {

		$default_data = (object) [
			'group' => true,
		];

		$row = $this->get_row( (int) $club_id );

		if ( $row && '' !== ( $row['squad_group'] ?? '' ) ) {
			return (object) [
				'group' => (bool) $row['squad_group'],
			];
		}

		// Pre-migration fallback: read from postmeta.
		if ( ! get_option( 'anwpfl_squad_migrated' ) ) {
			$saved_options = json_decode( get_post_meta( $club_id, '_anwpfl_squad_display', true ) );
			$active_id     = (int) anwp_fl()->get_active_season();

			if ( ! empty( $saved_options ) && isset( $saved_options->{$active_id} ) && is_object( $saved_options->{$active_id} ) ) {
				return $saved_options->{$active_id};
			}
		}

		return $default_data;
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

		$row = $this->get_row( (int) $club_id );

		if ( $row && 'summary' === $row['root_type'] ) {
			$details      = json_decode( $row['club_details'] ?? '', true );
			$subteam_list = $details['subteam_list'] ?? [];

			if ( ! empty( $subteam_list ) && is_array( $subteam_list ) ) {
				$club_ids = [];

				foreach ( $subteam_list as $subteam_item ) {
					// Read both 'club_id' (new shape from Vue editor + sanitize_
					// subteam_list since 0.17.3) and 'subteam' (legacy postmeta
					// shape) so this returns IDs regardless of when the parent
					// club was last saved.
					$sub_id = absint( $subteam_item['club_id'] ?? ( $subteam_item['subteam'] ?? 0 ) );

					if ( $sub_id ) {
						$club_ids[] = $sub_id;
					}
				}

				$output = $array_output ? $club_ids : implode( ',', $club_ids );
			}
		}

		return empty( $output ) ? $club_id : $output;
	}

	/*
	|--------------------------------------------------------------------------
	| Custom Table: Cache & Warm Methods
	|--------------------------------------------------------------------------
	| Per-request cache infrastructure for anwpfl_clubs table.
	| Two cache levels: $list_cache (light, 11 cols) and base $row_cache (full).
	|
	| @since 0.17.3
	*/

	/**
	 * Bulk warm light club data for list views.
	 *
	 * Fetches 11 core display columns into $list_cache. Falls back to
	 * postmeta for IDs not yet in the custom table (mid-migration).
	 *
	 * @since 0.17.3
	 *
	 * @param int[] $ids Club post IDs.
	 */
	public function warm_clubs( array $ids ): void {
		$ids = array_unique( array_filter( array_map( 'absint', $ids ) ) );

		// Filter out IDs already in list_cache.
		$missing = array_diff( $ids, array_keys( $this->list_cache ) );

		if ( empty( $missing ) ) {
			return;
		}

		$rows = $this->fetch_rows(
			$missing,
			'title, post_name, abbr, nationality, is_national_team, logo, logo_big, logo_id, logo_big_id, main_color'
		);

		foreach ( $rows as $row ) {
			$this->list_cache[ (int) $row['club_id'] ] = $row;
		}

		// Fallback: postmeta for IDs not in the table (mid-migration).
		$still_missing = array_diff( $missing, array_keys( $rows ) );

		if ( ! empty( $still_missing ) ) {
			update_meta_cache( 'post', $still_missing );

			foreach ( $still_missing as $club_id ) {
				$row = $this->get_club_row_from_postmeta( $club_id );

				if ( $row ) {
					$this->list_cache[ $club_id ] = $row;
				}
			}
		}
	}

	/**
	 * Bulk warm full club data (all columns including JSON blobs).
	 *
	 * Populates both base $row_cache and $list_cache.
	 *
	 * @since 0.17.3
	 *
	 * @param int[] $ids Club post IDs.
	 */
	public function warm_clubs_full( array $ids ): void {
		$ids = array_unique( array_filter( array_map( 'absint', $ids ) ) );

		if ( empty( $ids ) ) {
			return;
		}

		$rows = $this->fetch_rows( $ids );

		foreach ( $rows as $row ) {
			$club_id = (int) $row['club_id'];
			$this->cache_row( $club_id, $row );
			$this->list_cache[ $club_id ] = $row;
		}

		// Fallback: postmeta for IDs not in the table (mid-migration).
		$still_missing = array_diff( $ids, array_keys( $rows ) );

		if ( ! empty( $still_missing ) ) {
			update_meta_cache( 'post', $still_missing );

			foreach ( $still_missing as $club_id ) {
				$row = $this->get_club_row_from_postmeta( $club_id );

				if ( $row ) {
					$this->cache_row( $club_id, $row );
					$this->list_cache[ $club_id ] = $row;
				}
			}
		}
	}

	/**
	 * Get light club row from list cache or base row cache.
	 *
	 * @since 0.17.3
	 *
	 * @param int $id Club post ID.
	 *
	 * @return array|null
	 */
	public function get_club_list_row( int $id ): ?array {
		$row = $this->list_cache[ $id ] ?? $this->get_cached_row( $id );

		if ( null !== $row ) {
			return $row;
		}

		// Auto-fetch on cache miss (single club access without prior warm).
		$row = $this->get_row( $id );

		if ( $row ) {
			$this->list_cache[ $id ] = $row;
		}

		return $row;
	}

	/**
	 * Get a full club row by ID.
	 *
	 * Overrides base to add postmeta fallback during migration.
	 * No negative caching — null stays uncached so next call retries.
	 *
	 * @since 0.17.3
	 *
	 * @param int $id Club post ID.
	 *
	 * @return array|null
	 */
	public function get_row( int $id ): ?array {
		if ( $this->has_cached_row( $id ) ) {
			return $this->get_cached_row( $id );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $this->table_name WHERE $this->primary_key = %d",
				$id
			),
			ARRAY_A
		);

		if ( $row ) {
			$this->cache_row( $id, $row );
			return $row;
		}

		// Fallback: build row from postmeta (pre-migration or mid-migration).
		$row = $this->get_club_row_from_postmeta( $id );

		// Cache null result too — prevents re-querying for non-existent IDs
		// within the same request (admin hooks speculatively call get_row()
		// for every post ID in the list).
		$this->cache_row( $id, $row );

		return $row;
	}

	/**
	 * Build a club row object from post + postmeta.
	 *
	 * Migration safety net: returns an array with the same keys
	 * as a real anwpfl_clubs row. Used by get_row() and warm methods
	 * when a club hasn't been migrated to the custom table yet.
	 *
	 * @since 0.17.3
	 *
	 * @param int $club_id Club post ID.
	 *
	 * @return array|null Row-shaped array or null if post doesn't exist.
	 */
	protected function get_club_row_from_postmeta( int $club_id ): ?array {
		$post = get_post( $club_id );

		if ( ! $post || 'anwp_club' !== $post->post_type ) {
			return null;
		}

		$meta = get_post_meta( $club_id );

		$m = function ( $key, $default = '' ) use ( $meta ) {
			return isset( $meta[ $key ][0] ) ? $meta[ $key ][0] : $default;
		};

		$gallery = maybe_unserialize( $m( '_anwpfl_gallery' ) );
		if ( ! is_array( $gallery ) ) {
			$gallery = [];
		}

		$subteam_list = maybe_unserialize( $m( '_anwpfl_subteam_list' ) );
		if ( ! is_array( $subteam_list ) ) {
			$subteam_list = [];
		}

		$custom_fields = maybe_unserialize( $m( '_anwpfl_custom_fields' ) );
		if ( ! is_array( $custom_fields ) ) {
			$custom_fields = [];
		}

		return [
			'club_id'               => $club_id,
			'title'                 => $post->post_title,
			'post_name'             => $post->post_name,
			'abbr'                  => $m( '_anwpfl_abbr' ),
			'city'                  => $m( '_anwpfl_city' ),
			'nationality'           => $m( '_anwpfl_nationality' ),
			'stadium_id'            => absint( $m( '_anwpfl_stadium' ) ),
			'is_national_team'      => 'yes' === $m( '_anwpfl_is_national_team' ) ? 1 : 0,
			'club_duplicates'       => 'yes' === $m( '_anwpfl_club_duplicates' ) ? 1 : 0,
			'club_external_id'      => $m( '_anwpfl_club_external_id' ),
			'subteams'              => $m( '_anwpfl_subteams' ),
			'root_team'             => absint( $m( '_anwpfl_root_team' ) ),
			'root_type'             => $m( '_anwpfl_root_type' ),
			'logo'                  => $m( '_anwpfl_logo' ),
			'logo_id'               => absint( $m( '_anwpfl_logo_id' ) ),
			'logo_big'              => $m( '_anwpfl_logo_big' ),
			'logo_big_id'           => absint( $m( '_anwpfl_logo_big_id' ) ),
			'main_color'            => $m( '_anwpfl_main_color' ),
			'club_details'          => wp_json_encode( [
				'address'         => $m( '_anwpfl_address' ),
				'website'         => $m( '_anwpfl_website' ),
				'founded'         => $m( '_anwpfl_founded' ),
				'club_kit'        => $m( '_anwpfl_club_kit' ),
				'club_kit_id'     => absint( $m( '_anwpfl_club_kit_id' ) ),
				'gallery'         => $gallery,
				'gallery_notes'   => $m( '_anwpfl_gallery_notes' ),
				'root_team_title' => $m( '_anwpfl_root_team_title' ),
				'subteam_list'    => $subteam_list,
			] ),
			'club_social'           => wp_json_encode( [
				'twitter'   => $m( '_anwpfl_twitter' ),
				'facebook'  => $m( '_anwpfl_facebook' ),
				'youtube'   => $m( '_anwpfl_youtube' ),
				'instagram' => $m( '_anwpfl_instagram' ),
				'linkedin'  => $m( '_anwpfl_linkedin' ),
				'tiktok'    => $m( '_anwpfl_tiktok' ),
				'vk'        => $m( '_anwpfl_vk' ),
			] ),
			'club_custom'           => wp_json_encode( [
				'custom_title_1' => $m( '_anwpfl_custom_title_1' ),
				'custom_value_1' => $m( '_anwpfl_custom_value_1' ),
				'custom_title_2' => $m( '_anwpfl_custom_title_2' ),
				'custom_value_2' => $m( '_anwpfl_custom_value_2' ),
				'custom_title_3' => $m( '_anwpfl_custom_title_3' ),
				'custom_value_3' => $m( '_anwpfl_custom_value_3' ),
				'custom_fields'  => (object) $custom_fields,
			] ),
			'description'           => $m( '_anwpfl_description' ),
			'custom_content_below'  => $m( '_anwpfl_custom_content_below' ),
			'squad'                 => '',
			'squad_staff'           => '',
			'squad_seasons'         => '',
			'squad_staff_seasons'   => '',
			'squad_group'           => 1,
		];
	}

	/*
	|--------------------------------------------------------------------------
	| Custom Table: Write Hooks
	|--------------------------------------------------------------------------
	| Dual-write sync: CMB2 writes postmeta, then sync hook copies to table.
	|
	| @since 0.17.3
	*/

	/**
	 * Sync title and post_name from wp_posts to anwpfl_clubs custom table.
	 *
	 * Hooked on save_post_anwp_club at priority 999. Club data fields are
	 * written directly by save_vue_club_data() - this hook only keeps
	 * title/post_name in sync (WordPress may change slug on save).
	 *
	 * @since 0.17.3
	 *
	 * @param int $post_id Club post ID.
	 */
	public function sync_club_to_table( int $post_id ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post || 'anwp_club' !== $post->post_type ) {
			return;
		}

		if ( $this->vue_save_handled ) {
			// Vue editor already wrote club data to the table in save_metabox().
			// Only sync title/post_name (WP may have updated slug).
			$this->upsert(
				$post_id,
				[
					'club_id'   => $post_id,
					'title'     => $post->post_title,
					'post_name' => $post->post_name,
				],
				[ 'title', 'post_name' ]
			);
		} else {
			// Non-Vue save (batch import, API import, site migration).
			// These write to postmeta — full sync from postmeta to custom table.
			$row = $this->get_club_row_from_postmeta( $post_id );
			$this->upsert( $post_id, $row, array_keys( $row ) );
		}

		unset( $this->list_cache[ $post_id ] );
	}

	/**
	 * Remove club row from custom table on permanent delete.
	 *
	 * Hooked on delete_post (fires before post is removed from DB).
	 * Trashed/drafted clubs keep their row — only permanent deletes remove it.
	 *
	 * @since 0.17.3
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_club_delete( int $post_id ): void {
		if ( 'anwp_club' !== get_post_type( $post_id ) ) {
			return;
		}

		$this->delete( $post_id );
		unset( $this->list_cache[ $post_id ] );
	}

	/**
	 * Clear all per-request club caches.
	 *
	 * Resets $list_cache (instance) and $row_cache (base, this table only).
	 * Called by AnWPFL_Cache when flushing club caches.
	 *
	 * @since 0.17.3
	 */
	public function reset_list_cache(): void {
		$this->list_cache = [];
		$this->reset_cache();
	}

	/**
	 * Derive a historical roster for (club, season) from match participation data.
	 *
	 * Used as a fallback by squad/season consumers when `anwpfl_clubs.squad_seasons[s:N]`
	 * is empty for the requested season. Returns roster shape compatible with the active
	 * squad: `[ ['id', 'number', 'position', 'status'], ... ]` sorted by position
	 * priority (g/d/m/f) then jersey number ascending.
	 *
	 * Sources:
	 * - `anwpfl_lineups` - starters + named bench (covers players who didn't get a stats row).
	 * - `anwpfl_players` - augments with players that recorded stats but were absent from the
	 *   lineup blob (rare; malformed data path).
	 * - `anwpfl_lineups.custom_numbers` - jersey number, most recent non-empty match wins.
	 * - `anwpfl_player_data.position` - current position. Per-match historical position is
	 *   not stored anywhere, so derived rosters always carry the player's *current* position.
	 *
	 * `status` is always `''` - loan / trial / left flags can't be recovered from stats data.
	 *
	 * @since 0.18.0
	 *
	 * @param int $club_id   Club post ID.
	 * @param int $season_id Season post ID.
	 *
	 * @return array
	 */
	public function derive_roster_from_stats( int $club_id, int $season_id ): array {

		if ( $club_id < 1 || $season_id < 1 ) {
			return [];
		}

		static $cache = [];

		if ( isset( $cache[ $club_id ][ $season_id ] ) ) {
			return $cache[ $club_id ][ $season_id ];
		}

		$cache_key   = 'FL-DERIVED-ROSTER__' . $club_id . '__' . $season_id;
		$cached_data = anwp_fl()->cache->get( $cache_key, 'anwp_match', false );

		if ( false !== $cached_data ) {
			$cache[ $club_id ][ $season_id ] = $cached_data;
			return $cached_data;
		}

		global $wpdb;

		// 1. Pull lineup rows for finished matches in this season for this club, newest first.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT m.match_id, m.home_club, m.away_club,
				       l.home_line_up, l.away_line_up, l.home_subs, l.away_subs, l.custom_numbers
				FROM {$wpdb->prefix}anwpfl_matches AS m
				LEFT JOIN {$wpdb->prefix}anwpfl_lineups AS l ON l.match_id = m.match_id
				WHERE m.season_id = %d
					AND m.finished = 1
					AND ( m.home_club = %d OR m.away_club = %d )
				ORDER BY m.kickoff DESC
				",
				$season_id,
				$club_id,
				$club_id
			)
		);

		if ( empty( $rows ) ) {
			$cache[ $club_id ][ $season_id ] = [];
			return [];
		}

		$player_ids  = [];
		$number_pick = []; // player_id => jersey number (most recent non-empty wins).
		$match_ids   = [];

		foreach ( $rows as $row ) {
			$match_ids[] = (int) $row->match_id;

			$is_home  = (int) $row->home_club === $club_id;
			$list_csv = $is_home
				? trim( $row->home_line_up . ',' . $row->home_subs, ',' )
				: trim( $row->away_line_up . ',' . $row->away_subs, ',' );

			$club_player_ids = wp_parse_id_list( $list_csv );

			foreach ( $club_player_ids as $pid ) {
				$player_ids[ $pid ] = true;
			}

			// custom_numbers covers BOTH teams; only walk this club's IDs.
			if ( ! empty( $row->custom_numbers ) ) {
				$numbers = json_decode( $row->custom_numbers, true );

				if ( is_array( $numbers ) ) {
					foreach ( $club_player_ids as $pid ) {
						if ( isset( $number_pick[ $pid ] ) ) {
							continue; // newest already taken (rows ordered DESC).
						}

						if ( isset( $numbers[ $pid ] ) && '' !== (string) $numbers[ $pid ] ) {
							$number_pick[ $pid ] = (string) $numbers[ $pid ];
						}
					}
				}
			}
		}

		// 2. Augment with stats-only participants (rare: malformed lineup blob).
		if ( ! empty( $match_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $match_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$extra_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT DISTINCT player_id
					FROM {$wpdb->prefix}anwpfl_players
					WHERE club_id = %d
						AND match_id IN ( {$placeholders} )
					",
					array_merge( [ $club_id ], $match_ids )
				)
			);
			// phpcs:enable

			foreach ( wp_parse_id_list( $extra_ids ) as $pid ) {
				$player_ids[ $pid ] = true;
			}
		}

		if ( empty( $player_ids ) ) {
			$cache[ $club_id ][ $season_id ] = [];
			return [];
		}

		// 3. Pull current position from anwpfl_player_data (no per-match historical position exists).
		$ids_list     = array_keys( $player_ids );
		$placeholders = implode( ',', array_fill( 0, count( $ids_list ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$position_map = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT player_id, position
				FROM {$wpdb->prefix}anwpfl_player_data
				WHERE player_id IN ( {$placeholders} )
				",
				$ids_list
			),
			OBJECT_K
		);
		// phpcs:enable

		// 4. Build roster shape, sorted by position priority then jersey number.
		$position_order = [
			'g' => 1,
			'd' => 2,
			'm' => 3,
			'f' => 4,
		];

		$roster = [];

		foreach ( $ids_list as $pid ) {
			$position = isset( $position_map[ $pid ]->position ) ? (string) $position_map[ $pid ]->position : '';
			$roster[] = [
				'id'       => (int) $pid,
				'number'   => $number_pick[ $pid ] ?? '',
				'position' => $position,
				'status'   => '',
			];
		}

		usort(
			$roster,
			static function ( $a, $b ) use ( $position_order ) {
				$pa = $position_order[ $a['position'] ] ?? 99;
				$pb = $position_order[ $b['position'] ] ?? 99;

				if ( $pa !== $pb ) {
					return $pa - $pb;
				}

				$na = '' === $a['number'] ? PHP_INT_MAX : (int) $a['number'];
				$nb = '' === $b['number'] ? PHP_INT_MAX : (int) $b['number'];

				return $na - $nb;
			}
		);

		anwp_fl()->cache->set( $cache_key, $roster, 'anwp_match' );
		$cache[ $club_id ][ $season_id ] = $roster;

		return $roster;
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get squad seasons ids.
	 *
	 * @deprecated 0.18.0 Roster is now flat (not season-keyed). Derive season lists from match data instead.
	 *
	 * @param int $club_id
	 *
	 * @return array
	 * @since 0.11.6
	 */
	public function get_club_squad_season_ids( $club_id ) {

		_deprecated_function( __METHOD__, '0.18.0' );

		return [];
	}
}
