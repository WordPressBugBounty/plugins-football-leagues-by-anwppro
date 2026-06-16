<?php
/**
 * AnWP Football Leagues :: Competition.
 *
 * @since   0.1.0
 * @package AnWP_Football_Leagues
 */

/**
 * AnWP Football Leagues :: Competition post type class.
 *
 * @since 0.1.0
 */
class AnWPFL_Competition extends AnWPFL_DB {

	/**
	 * Parent plugin class.
	 *
	 * @var AnWP_Football_Leagues
	 * @since  0.1.0
	 */
	protected $plugin = null;

	/**
	 * Light per-request cache for list views (match lists, dropdowns, shortcode headers).
	 * Holds display columns per competition. Separate from base $row_cache
	 * (full rows) to avoid loading JSON blobs for list views.
	 *
	 * @since 0.18.0
	 * @var array<int, array>
	 */
	protected array $list_cache = [];

	/**
	 * Per-request cache for get_secondary_competitions_list() results.
	 *
	 * Keyed by main stage ID. Stores the pre-shaped array returned by the method.
	 * Invalidated by reset_list_cache() / reset_row_cache() for long-running scripts.
	 *
	 * @since 0.18.0
	 * @var array<int, array>
	 */
	private array $secondaries_cache = [];

	/**
	 * Mapped Data.
	 *
	 * @since  0.2.0
	 */
	protected $type_map;
	protected $format_robin_map;
	protected $format_knockout_map;

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
		$this->table_name  = $wpdb->anwpfl_competitions;
		$this->primary_key = 'competition_id';

		$this->hooks();

		// Prepare map data
		$this->type_map = [
			'round-robin' => esc_html__( 'Round Robin', 'anwp-football-leagues' ),
			'knockout'    => esc_html__( 'Knockout', 'anwp-football-leagues' ),
		];

		$this->format_robin_map = [
			'single' => esc_html_x( 'Single', 'round robin format', 'anwp-football-leagues' ),
			'double' => esc_html_x( 'Double', 'round robin format', 'anwp-football-leagues' ),
			'custom' => esc_html_x( 'Custom', 'round robin format', 'anwp-football-leagues' ),
		];

		$this->format_knockout_map = [
			'single' => esc_html__( 'single-leg', 'anwp-football-leagues' ),
			'two'    => esc_html__( 'two-legged', 'anwp-football-leagues' ),
		];
	}

	/**
	 * Register the Competition custom post type.
	 *
	 * @since 0.18.0
	 */
	public function register_post_type() {
		$permalink_structure = $this->plugin->options->get_permalink_structure();
		$permalink_slug      = empty( $permalink_structure['competition'] ) ? 'competition' : $permalink_structure['competition'];

		register_post_type(
			'anwp_competition',
			[
				'labels'              => [
					'name'               => esc_html__( 'Competitions', 'anwp-football-leagues' ),
					'singular_name'      => esc_html__( 'Competition', 'anwp-football-leagues' ),
					'all_items'          => esc_html__( 'Competitions', 'anwp-football-leagues' ),
					'add_new'            => esc_html__( 'Add New Competition', 'anwp-football-leagues' ),
					'add_new_item'       => esc_html__( 'Add New Competition', 'anwp-football-leagues' ),
					'edit_item'          => esc_html__( 'Edit Competition', 'anwp-football-leagues' ),
					'new_item'           => esc_html__( 'New Competition', 'anwp-football-leagues' ),
					'view_item'          => esc_html__( 'View Competition', 'anwp-football-leagues' ),
					'search_items'       => esc_html__( 'Search Competitions', 'anwp-football-leagues' ),
					'not_found'          => esc_html__( 'No Competitions found', 'anwp-football-leagues' ),
					'not_found_in_trash' => esc_html__( 'No Competitions found in trash', 'anwp-football-leagues' ),
				],
				'public'              => true,
				'show_in_menu'        => 'edit.php?post_type=anwp_competition',
				'menu_icon'           => $this->plugin::SVG_CUP,
				'show_in_rest'        => true,
				'rest_base'           => 'anwp_competitions',
				'exclude_from_search' => 'hide' === AnWPFL_Options::get_value( 'display_front_end_search_competition' ),
				'rewrite'             => [ 'slug' => $permalink_slug ],
				'supports'            => [
					'title',
					'comments',
				],
				'taxonomies'          => [
					'anwp_league',
					'anwp_season',
				],
			]
		);
	}

	/**
	 * Filter post updated messages for the Competition CPT.
	 *
	 * @since 0.18.0
	 *
	 * @param array $messages Post updated messages.
	 *
	 * @return array
	 */
	public function messages( $messages ) {
		global $post, $post_ID;

		$messages['anwp_competition'] = [
			0  => '',
			1  => sprintf( '%s <a href="%s">%s</a>', esc_html__( 'Competition updated.', 'anwp-football-leagues' ), esc_url( get_permalink( $post_ID ) ), esc_html__( 'View Competition', 'anwp-football-leagues' ) ),
			2  => esc_html__( 'Custom field updated.', 'anwp-football-leagues' ),
			3  => esc_html__( 'Custom field deleted.', 'anwp-football-leagues' ),
			4  => esc_html__( 'Competition updated.', 'anwp-football-leagues' ),
			// translators: %s: revision date
			5  => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Competition restored to revision from %s', 'anwp-football-leagues' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification
			6  => sprintf( '%s <a href="%s">%s</a>', esc_html__( 'Competition published.', 'anwp-football-leagues' ), esc_url( get_permalink( $post_ID ) ), esc_html__( 'View Competition', 'anwp-football-leagues' ) ),
			7  => esc_html__( 'Competition saved.', 'anwp-football-leagues' ),
			8  => sprintf( '%s <a target="_blank" href="%s">%s</a>', esc_html__( 'Competition submitted.', 'anwp-football-leagues' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ), esc_html__( 'Preview Competition', 'anwp-football-leagues' ) ),
			// translators: %1$s: scheduled date
			9  => sprintf( esc_html__( 'Competition scheduled for: %1$s.', 'anwp-football-leagues' ), '<strong>' . date_i18n( esc_html__( 'M j, Y @ G:i', 'anwp-football-leagues' ), strtotime( $post->post_date ) ) . '</strong>' ),
			10 => sprintf( '%s <a target="_blank" href="%s">%s</a>', esc_html__( 'Competition draft updated.', 'anwp-football-leagues' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ), esc_html__( 'Preview Competition', 'anwp-football-leagues' ) ),
		];

		return $messages;
	}

	/**
	 * Filter bulk post updated messages for the Competition CPT.
	 *
	 * @since 0.18.0
	 *
	 * @param array $bulk_messages Bulk updated messages.
	 * @param array $bulk_counts   Counts of updated posts.
	 *
	 * @return array
	 */
	public function bulk_messages( $bulk_messages, $bulk_counts ) {
		$bulk_messages['anwp_competition'] = [
			// translators: %s: number of competitions
			'updated'   => sprintf( _n( '%s Competition updated.', '%s Competitions updated.', $bulk_counts['updated'], 'anwp-football-leagues' ), $bulk_counts['updated'] ),
			// translators: %s: number of competitions
			'locked'    => sprintf( _n( '%s Competition not updated, somebody is editing it.', '%s Competitions not updated, somebody is editing them.', $bulk_counts['locked'], 'anwp-football-leagues' ), $bulk_counts['locked'] ),
			// translators: %s: number of competitions
			'deleted'   => sprintf( _n( '%s Competition permanently deleted.', '%s Competitions permanently deleted.', $bulk_counts['deleted'], 'anwp-football-leagues' ), $bulk_counts['deleted'] ),
			// translators: %s: number of competitions
			'trashed'   => sprintf( _n( '%s Competition moved to the Trash.', '%s Competitions moved to the Trash.', $bulk_counts['trashed'], 'anwp-football-leagues' ), $bulk_counts['trashed'] ),
			// translators: %s: number of competitions
			'untrashed' => sprintf( _n( '%s Competition restored from the Trash.', '%s Competitions restored from the Trash.', $bulk_counts['untrashed'], 'anwp-football-leagues' ), $bulk_counts['untrashed'] ),
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
		if ( isset( $screen->post_type ) && 'anwp_competition' === $screen->post_type ) {
			return esc_html__( 'Competition Title', 'anwp-football-leagues' );
		}

		return $title;
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {

		// CPT registration and admin hooks (previously handled by CPT_Core).
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_filter( 'post_updated_messages', [ $this, 'messages' ] );
		add_filter( 'bulk_post_updated_messages', [ $this, 'bulk_messages' ], 10, 2 );
		add_filter( 'manage_edit-anwp_competition_columns', [ $this, 'columns' ] );
		add_filter( 'manage_edit-anwp_competition_sortable_columns', [ $this, 'sortable_columns' ] );
		add_action( 'manage_posts_custom_column', [ $this, 'columns_display' ], 10, 2 );
		add_filter( 'enter_title_here', [ $this, 'title' ] );

		// Render Custom Content below (Phase 12: reads from custom table).
		add_action(
			'anwpfl/tmpl-competition/after_wrapper',
			function ( $competition_post_id ) {
				$row           = anwp_fl()->competition->get_row( (int) $competition_post_id );
				$content_below = $row['custom_content_below'] ?? '';

				if ( trim( $content_below ) ) {
					echo '<div class="anwp-b-wrap mt-4">' . do_shortcode( $content_below ) . '</div>';
				}
			}
		);

		// Clone Competition
		add_filter( 'post_row_actions', [ $this, 'modify_quick_actions' ], 10, 2 );
		add_action( 'wp_ajax_fl_clone_competition', [ $this, 'process_clone_competition' ] );
		add_action( 'admin_footer-edit.php', [ $this, 'include_admin_clone_competition_dialog' ], 99 );

		// Sync competition data to custom table after save (priority 999, after CMB2).
		// Fallback for non-admin saves (REST, programmatic wp_insert_post). Title/post_name only.
		add_action( 'save_post_anwp_competition', [ $this, 'sync_competition_to_table' ], 999 );

		// Full sync per stage after admin save. Fires after all meta + taxonomy written.
		// Phase 9: action hook now passes $parent_post_id as 4th arg so sync handler
		// can derive multistage_main without reading postmeta.
		add_action( 'anwpfl/competition-stage/after_save', [ $this, 'sync_stage_to_table' ], 10, 4 );

		// Remove competition row on permanent delete.
		add_action( 'delete_post', [ $this, 'on_competition_delete' ] );

		// Sync league/season taxonomy changes made outside save_metabox
		// (quick-edit, REST, programmatic wp_set_object_terms).
		add_action( 'set_object_terms', [ $this, 'sync_taxonomy_to_table' ], 10, 4 );

		// Sync league/season term renames to the custom table's *_text columns.
		add_action( 'edited_term', [ $this, 'sync_term_name_to_table' ], 10, 3 );
	}

	/**
	 * Filters the array of row action links on the Pages list table.
	 *
	 * @param array $actions
	 * @param WP_Post $post
	 *
	 * @return array
	 * @since 0.11.5
	 */
	public function modify_quick_actions( $actions, $post ) {

		if ( 'anwp_competition' === $post->post_type && current_user_can( 'edit_post', $post->ID ) ) {
			$actions['clone-competition'] = '<a data-competition-id="' . intval( $post->ID ) . '" class="anwp-fl-competition-clone-action" href="#">' . esc_html__( 'Clone', 'anwp-football-leagues' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Handle clone competition action.
	 *
	 * @since 0.11.5
	 */
	public function process_clone_competition() {

		// Check if our nonce is set.
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( 'Error : Unauthorized action' );
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax_anwpfl_nonce' ) ) {
			wp_send_json_error( 'Error : Unauthorized action' );
		}

		$competition_id = isset( $_POST['competition_id'] ) ? absint( $_POST['competition_id'] ) : 0;
		$season_id      = isset( $_POST['season_id'] ) ? absint( $_POST['season_id'] ) : 0;

		if ( ! $competition_id || ! $season_id || ! current_user_can( 'edit_post', $competition_id ) ) {
			wp_send_json_error( 'Error : Invalid Data' );
		}

		$cloned_id = wp_insert_post(
			[
				'post_type'   => 'anwp_competition',
				'post_status' => 'publish',
			]
		);

		if ( $cloned_id ) {

			// Phase 12: custom table upsert below copies all data. Only
			// _anwpfl_cloned is kept as postmeta (write-once audit trail).
			// Filter retained for third-party compatibility.
			$meta_fields_to_clone = [
				'_anwpfl_cloned',
			];

			/**
			 * Filter Competition Data to clone
			 *
			 * @param array $meta_fields_to_clone Clone data
			 * @param int   $competition_id       Standing ID
			 * @param int   $cloned_id            New Cloned Standing ID
			 *
			 * @since 0.11.5
			 */
			$meta_fields_to_clone = apply_filters( 'anwpfl/competition/fields_to_clone', $meta_fields_to_clone, $competition_id, $cloned_id );

			foreach ( $meta_fields_to_clone as $meta_key ) {

				$meta_value = get_post_meta( $competition_id, $meta_key, true );

				if ( '' !== $meta_value ) {
					$meta_value = maybe_unserialize( $meta_value );
					update_post_meta( $cloned_id, $meta_key, wp_slash( $meta_value ) );
				}
			}

			/*
			|--------------------------------------------------------------------
			| League
			|--------------------------------------------------------------------
			*/
			$league_id = wp_get_post_terms( $competition_id, 'anwp_league', [ 'fields' => 'ids' ] );

			if ( ! empty( $league_id ) && ! empty( $league_id[0] ) ) {
				wp_set_object_terms( $cloned_id, $league_id[0], 'anwp_league' );
			}

			/*
			|--------------------------------------------------------------------
			| Season
			|--------------------------------------------------------------------
			*/
			wp_set_object_terms( $cloned_id, $season_id, 'anwp_season' );

			/*
			|--------------------------------------------------------------------
			| Generate Post Title
			|--------------------------------------------------------------------
			*/
			$cloned_post_title = get_term( $league_id[0], 'anwp_league' )->name . ' ' . get_term( $season_id, 'anwp_season' )->name;

			if ( $cloned_post_title ) {
				wp_update_post(
					[
						'ID'         => $cloned_id,
						'post_title' => $cloned_post_title,
					]
				);
			}

			update_post_meta( $cloned_id, '_anwpfl_cloned', $competition_id );

			/*
			|--------------------------------------------------------------------
			| Copy custom-table row (Phase 8.2.A)
			|
			| Keep the meta clone loop above (filter compatibility with
			| `anwpfl/competition/fields_to_clone`) AND write the custom-table
			| row here. Phase 9 removes the save_metabox postmeta writes, so
			| the clone path must populate the table directly.
			|--------------------------------------------------------------------
			*/
			$source_row = $this->get_row( (int) $competition_id );

			if ( $source_row ) {
				// Strip PK / denormalized fields that must be set fresh for the clone.
				unset( $source_row['competition_id'] );
				unset( $source_row['post_name'] );
				unset( $source_row['league_id'] );
				unset( $source_row['league_text'] );
				unset( $source_row['season_ids'] );
				unset( $source_row['season_text'] );
				unset( $source_row['title'] );

				// Resolve league/season denormalized columns from the clone dialog
				// inputs. After Phase 10 adds the set_object_terms listener, this
				// will be redundant but harmless.
				$league_term = ( ! empty( $league_id[0] ) ) ? get_term( $league_id[0], 'anwp_league' ) : null;
				$season_term = get_term( $season_id, 'anwp_season' );

				$source_row['title']       = isset( $cloned_post_title ) ? (string) $cloned_post_title : '';
				$source_row['league_id']   = ! empty( $league_id[0] ) ? (int) $league_id[0] : 0;
				$source_row['league_text'] = ( $league_term && ! is_wp_error( $league_term ) ) ? (string) $league_term->name : '';
				$source_row['season_ids']  = $season_id ? ',' . (int) $season_id . ',' : '';
				$source_row['season_text'] = ( $season_term && ! is_wp_error( $season_term ) ) ? ',' . $season_term->name . ',' : '';

				$clone_data = array_merge(
					$source_row,
					[
						'competition_id' => (int) $cloned_id,
						'post_name'      => (string) get_post_field( 'post_name', $cloned_id ),
					]
				);

				$this->upsert( (int) $cloned_id, $clone_data, array_keys( $clone_data ) );
			}

			/*
			|--------------------------------------------------------------------
			| Clone Secondary Stages
			|--------------------------------------------------------------------
			*/

			if ( $source_row && 'main' === ( $source_row['multistage'] ?? '' ) ) {

				// Get all secondary stages
				$stages     = $this->get_secondary_competitions_list( $competition_id );
				$stages_ids = [];

				if ( ! empty( $stages ) ) {
					$stages_ids = wp_list_pluck( $stages, 'id' );
				}

				foreach ( $stages_ids as $stage_id ) {
					$cloned_stage_id = wp_insert_post(
						[
							'post_type'   => 'anwp_competition',
							'post_status' => 'stage_secondary',
						]
					);

					if ( $cloned_stage_id ) {

						// Phase 12: custom table upsert copies all data. Filter retained
						// for third-party compatibility.
						$meta_fields_to_clone = [];

						/**
						 * Filter Competition Data to clone
						 *
						 * @param array $meta_fields_to_clone Clone data
						 * @param int   $stage_id             Standing ID
						 * @param int   $cloned_stage_id      New Cloned Standing ID
						 *
						 * @since 0.11.5
						 */
						$meta_fields_to_clone = apply_filters( 'anwpfl/competition/fields_to_clone', $meta_fields_to_clone, $stage_id, $cloned_stage_id );

						foreach ( $meta_fields_to_clone as $meta_key ) {

							$meta_value = get_post_meta( $stage_id, $meta_key, true );

							if ( '' !== $meta_value ) {
								$meta_value = maybe_unserialize( $meta_value );
								update_post_meta( $cloned_stage_id, $meta_key, wp_slash( $meta_value ) );
							}
						}

						/*
						|--------------------------------------------------------------------
						| League
						|--------------------------------------------------------------------
						*/
						$league_id = wp_get_post_terms( $stage_id, 'anwp_league', [ 'fields' => 'ids' ] );

						if ( ! empty( $league_id ) && ! empty( $league_id[0] ) ) {
							wp_set_object_terms( $cloned_stage_id, $league_id[0], 'anwp_league' );
						}

						/*
						|--------------------------------------------------------------------
						| Season
						|--------------------------------------------------------------------
						*/
						wp_set_object_terms( $cloned_stage_id, $season_id, 'anwp_season' );

						/*
						|--------------------------------------------------------------------
						| Generate Post Title
						|--------------------------------------------------------------------
						*/
						$cloned_post_title = get_term( $league_id[0], 'anwp_league' )->name . ' ' . get_term( $season_id, 'anwp_season' )->name;

						if ( $cloned_post_title ) {
							wp_update_post(
								[
									'ID'         => $cloned_stage_id,
									'post_title' => $cloned_post_title,
								]
							);
						}

						update_post_meta( $cloned_stage_id, '_anwpfl_cloned', $stage_id );
						update_post_meta( $cloned_stage_id, '_anwpfl_multistage_main', $cloned_id );

						/*
						|--------------------------------------------------------------------
						| Copy custom-table row for secondary stage (Phase 8.2.B)
						|
						| CRITICAL: rewrite multistage_main to point at the NEW cloned
						| root, NOT the source's old main ID. Without this, cloned
						| secondaries would still point at the source main and
						| get_secondary_competitions_list() on the clone would return
						| nothing.
						|--------------------------------------------------------------------
						*/
						$source_stage_row = $this->get_row( (int) $stage_id );

						if ( $source_stage_row ) {
							unset( $source_stage_row['competition_id'] );
							unset( $source_stage_row['post_name'] );
							unset( $source_stage_row['league_id'] );
							unset( $source_stage_row['league_text'] );
							unset( $source_stage_row['season_ids'] );
							unset( $source_stage_row['season_text'] );
							unset( $source_stage_row['title'] );

							$stage_league_term = ( ! empty( $league_id[0] ) ) ? get_term( $league_id[0], 'anwp_league' ) : null;
							$stage_season_term = get_term( $season_id, 'anwp_season' );

							$source_stage_row['title']           = isset( $cloned_post_title ) ? (string) $cloned_post_title : '';
							$source_stage_row['league_id']       = ! empty( $league_id[0] ) ? (int) $league_id[0] : 0;
							$source_stage_row['league_text']     = ( $stage_league_term && ! is_wp_error( $stage_league_term ) ) ? (string) $stage_league_term->name : '';
							$source_stage_row['season_ids']      = $season_id ? ',' . (int) $season_id . ',' : '';
							$source_stage_row['season_text']     = ( $stage_season_term && ! is_wp_error( $stage_season_term ) ) ? ',' . $stage_season_term->name . ',' : '';
							$source_stage_row['multistage']      = 'secondary';
							$source_stage_row['multistage_main'] = (int) $cloned_id;

							$stage_clone_data = array_merge(
								$source_stage_row,
								[
									'competition_id' => (int) $cloned_stage_id,
									'post_name'      => (string) get_post_field( 'post_name', $cloned_stage_id ),
								]
							);

							$this->upsert( (int) $cloned_stage_id, $stage_clone_data, array_keys( $stage_clone_data ) );
						}
					}
				}
			}
		}

		wp_send_json_success( [ 'link' => admin_url( 'post.php?post=' . intval( $cloned_id ) . '&action=edit' ) ] );
	}

	/**
	 * Add SVG definitions to the admin footer.
	 *
	 * @since 0.11.5
	 */
	public function include_admin_clone_competition_dialog() {

		// Load styles and scripts (limit to competition page)
		$current_screen = get_current_screen();

		if ( ! empty( $current_screen->id ) && 'edit-anwp_competition' === $current_screen->id ) {
			?>
			<dialog id="anwp-fl-competition-clone-dialog">
				<div class="anwpfl-shortcode-dialog__header">
					<h3 style="margin: 0"><?php echo esc_html__( 'clone Competition', 'anwp-football-leagues' ); ?></h3>
				</div>
				<div style="padding: 10px 20px;">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="anwp-fl-clone-season-id"><?php echo esc_html__( 'Season', 'anwp-football-leagues' ); ?></label></th>
							<td>
								<select id="anwp-fl-clone-season-id">
									<?php foreach ( anwp_football_leagues()->season->get_seasons_options() as $season_id => $season_title ) : ?>
										<option value="<?php echo esc_attr( $season_id ); ?>"><?php echo esc_html( $season_title ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<div class="anwpfl-shortcode-dialog__footer">
					<button id="anwp-fl-competition-clone-dialog__cancel" class="button"><?php echo esc_html__( 'Close', 'anwp-football-leagues' ); ?></button>
					<button id="anwp-fl-competition-clone-dialog__clone" class="button button-primary"><?php echo esc_html__( 'Clone', 'anwp-football-leagues' ); ?></button>
					<span class="spinner"></span>
				</div>
			</dialog>
			<?php
		}
	}

	/**
	 * Method returns main multistage competitions.
	 *
	 * @since 0.4.2 (2018-02-16)
	 * @return array
	 */
	public function get_main_competition_options() {

		static $output_data = null;

		if ( null !== $output_data ) {
			return $output_data;
		}

		global $wpdb;

		// Primary path: direct table query (post-migration).
		$rows = $wpdb->get_results(
			"SELECT competition_id AS id, title
			FROM {$wpdb->anwpfl_competitions}
			WHERE multistage = 'main'
			ORDER BY title ASC"
		); // phpcs:ignore WordPress.DB.PreparedSQL

		// Fallback path: empty table (pre-migration state).
		// Iterate per-request cached get_competitions_data() output and filter main stages.
		if ( empty( $rows ) ) {
			$output_data = [];

			foreach ( $this->get_competitions_data() as $competition_data ) {
				if ( 'main' !== ( $competition_data['multistage'] ?? '' ) ) {
					continue;
				}

				$output_data[] = (object) [
					'id'    => absint( $competition_data['id'] ),
					'title' => (string) ( $competition_data['title'] ?? '' ),
				];
			}

			return $output_data;
		}

		$output_data = [];

		foreach ( $rows as $row ) {
			$output_data[] = (object) [
				'id'    => absint( $row->id ),
				'title' => (string) $row->title,
			];
		}

		return $output_data;
	}

	/**
	 * Method returns secondary competitions by main stage ID.
	 *
	 * @param int $main_id
	 *
	 * @since 0.4.2 (2018-02-17)
	 * @return array
	 */
	public function get_secondary_competitions_list( $main_id ) {

		$main_id = (int) $main_id;

		if ( ! $main_id ) {
			return [];
		}

		// Per-request cache: builder files call this on every section
		// (bracket, results_matrix, standings, matchweek_slides, matches_all)
		// for the same main_id. Return the cached array without re-querying.
		if ( isset( $this->secondaries_cache[ $main_id ] ) ) {
			return $this->secondaries_cache[ $main_id ];
		}

		global $wpdb;

		/*
		 * Pre-warm the main stage row so subsequent maybe_inherit_logo() calls
		 * on secondary stages hit row_cache instead of triggering a postmeta
		 * fallback fetch for the main. Builders/templates routinely call
		 * get_competition_list_row() on secondaries right after getting the
		 * stage list, and that cascade triggers parent logo lookup.
		 *
		 * @since 0.18.0
		 */
		$this->get_row( $main_id );

		/*
		 * Primary path: direct table query (post-migration).
		 *
		 * SELECT * (not just display cols) so Phase 7+ builder/template reads
		 * of `bracket`, `bracket_options`, `stage_rounds`, etc. for these
		 * stage IDs hit row_cache without issuing a second bulk SELECT.
		 *
		 * @since 0.18.0 Extended from 8-col SELECT to SELECT * + cache population.
		 */
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->anwpfl_competitions}
				WHERE multistage_main = %d
				ORDER BY stage_order ASC",
				$main_id
			),
			ARRAY_A
		);

		// Populate per-request caches so subsequent get_row() / get_competition_list_row()
		// calls against these stage IDs within the same request hit the cache.
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$stage_id = (int) $row['competition_id'];
				$this->cache_row( $stage_id, $row );
				$this->list_cache[ $stage_id ] = $row;
			}
		}

		// Fallback path: table has no rows for this main (pre-migration state).
		// Look up candidate IDs via postmeta, build rows via get_row() which has its own postmeta fallback.
		if ( empty( $rows ) ) {
			$candidate_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT p.ID
					FROM $wpdb->posts p
					INNER JOIN $wpdb->postmeta pm ON pm.post_id = p.ID AND pm.meta_key = '_anwpfl_multistage_main' AND pm.meta_value = %d
					WHERE p.post_type = 'anwp_competition' AND p.post_status IN ( 'publish', 'stage_secondary' )",
					$main_id
				)
			);

			foreach ( $candidate_ids as $cid ) {
				$candidate = $this->get_row( (int) $cid );

				if ( $candidate ) {
					$rows[] = $candidate;
					// get_row() populated row_cache; mirror into list_cache for the cascade.
					$this->list_cache[ (int) $cid ] = $candidate;
				}
			}

			// Sort fallback rows by stage_order (matches primary path ORDER BY).
			usort(
				$rows,
				function ( $a, $b ) {
					return (int) ( $a['stage_order'] ?? 0 ) - (int) ( $b['stage_order'] ?? 0 );
				}
			);
		}

		$stages = [];

		foreach ( $rows as $row ) {
			$groups        = json_decode( (string) ( $row['stage_groups'] ?? '' ) );
			$groups_number = is_array( $groups ) ? count( $groups ) : 0;

			$stages[] = [
				'title'           => (string) ( $row['title'] ?? '' ),
				'id'              => absint( $row['competition_id'] ?? 0 ),
				'order'           => (string) ( $row['stage_order'] ?? '' ),
				'stage_title'     => (string) ( $row['stage_title'] ?? '' ),
				'type'            => (string) ( $row['type'] ?? '' ),
				'format_robin'    => (string) ( $row['format_robin'] ?? '' ),
				'format_knockout' => (string) ( $row['format_knockout'] ?? '' ),
				'groups'          => $groups_number,
			];
		}

		$this->secondaries_cache[ $main_id ] = $stages;

		return $stages;
	}

	/**
	 * Get array of matches for competition template.
	 *
	 * @param int  $competition_id
	 * @param bool $multistage
	 *
	 * @since 0.3.0 (2018-02-07)
	 * @since 0.4.2 (2018-02-17) Modified according to new multistage workflow
	 * @return array|null|object
	 */
	public function tmpl_get_competition_matches( $competition_id, $multistage ) {

		global $wpdb;

		$matchweeks_order = ( 'desc' === anwp_football_leagues()->customizer->get_value( 'competition', 'competition_matchweeks_order' ) ) ? 'DESC' : 'ASC';

		// Get all matches
		if ( $multistage ) {
			$matches = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"
					SELECT *
					FROM {$wpdb->prefix}anwpfl_matches
					WHERE competition_id = %d
						OR main_stage_id = %d
					ORDER BY match_week {$matchweeks_order}, kickoff ASC
					",
					// phpcs:enable
					$competition_id,
					$competition_id
				)
			);
		} else {
			$matches = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"
					SELECT *
					FROM {$wpdb->prefix}anwpfl_matches
					WHERE competition_id = %d
					ORDER BY match_week {$matchweeks_order}, kickoff ASC
					",
					// phpcs:enable
					$competition_id
				)
			);
		}

		$ids   = wp_list_pluck( $matches, 'match_id' );
		$links = $this->plugin->helper->get_permalinks_by_ids( $ids, 'anwp_match' );

		foreach ( $matches as $match ) {
			$match->permalink = $links[ $match->match_id ] ?? '';
		}

		$this->warm_clubs_and_competitions_for_matches( $matches );

		return $matches;
	}

	/**
	 * Warm light club + competition caches for a match list.
	 *
	 * Pre-loads list_cache for every unique home_club, away_club, competition_id,
	 * and main_stage_id in the list. Collapses per-match get_row() cache misses
	 * in prepare_match_data_to_render() into two bulk SELECTs.
	 *
	 * @since 0.18.0
	 *
	 * @param array $matches Match rows (objects or arrays).
	 */
	private function warm_clubs_and_competitions_for_matches( array $matches ): void {
		if ( empty( $matches ) ) {
			return;
		}

		$club_ids = array_unique(
			array_merge(
				wp_list_pluck( $matches, 'home_club' ),
				wp_list_pluck( $matches, 'away_club' )
			)
		);

		anwp_fl()->club->warm_clubs( $club_ids );

		$comp_ids = array_unique(
			array_merge(
				wp_list_pluck( $matches, 'competition_id' ),
				wp_list_pluck( $matches, 'main_stage_id' )
			)
		);

		$this->warm_competitions( $comp_ids );
	}

	/**
	 * Get array of matches for widget and shortcode.
	 *
	 * @param object|array $options
	 * @param string $result
	 *
	 * @since 0.4.3 (2018-02-24)
	 * @return array|null|object
	 */
	public function tmpl_get_competition_matches_extended( $options, $result = '' ) {

		global $wpdb;

		$options = (object) wp_parse_args(
			$options,
			[
				'competition_id'       => '',
				'stage_id'             => '',
				'season_id'            => '',
				'league_id'            => '',
				'group_id'             => '',
				'stadium_id'           => '',
				'show_secondary'       => '',
				'type'                 => '',
				'filter_values'        => '',
				'filter_by'            => '',
				'filter_by_clubs'      => '',
				'filter_by_matchweeks' => '',
				'sort_by_date'         => '',
				'sort_by_matchweek'    => '',
				'limit'                => '',
				'days_offset'          => '',
				'days_offset_to'       => '',
				'priority'             => '',
				'date_from'            => '',
				'date_to'              => '',
				'exclude_ids'          => '',
				'include_ids'          => '',
				'group_by'             => '',
				'home_club'            => '',
				'away_club'            => '',
				'kickoff_to'           => '',
				'kickoff_from'         => '',
				'offset'               => '',
			]
		);

		// Per-request memoization. Pages with duplicate widget areas (e.g. home
		// page with same [anwpfl-matches] shortcode rendered in footer1 AND
		// footer3) would otherwise re-run the full query + permalink fetch +
		// cache-warming pipeline with identical args. Clone on return so
		// callers may mutate match objects without poisoning the cache.
		static $tmpl_matches_cache = [];

		$cache_key = md5( (string) wp_json_encode( [ (array) $options, $result ] ) );

		if ( array_key_exists( $cache_key, $tmpl_matches_cache ) ) {
			return $this->clone_cached_match_list( $tmpl_matches_cache[ $cache_key ] );
		}

		$query = "
		SELECT *
		FROM {$wpdb->prefix}anwpfl_matches
		WHERE 1=1
		";

		/**==================
		 * WHERE filter by competition
		 *================ */
		// Get competition to filter
		if ( 1 === absint( $options->show_secondary ) && ! empty( $options->competition_id ) ) {
			$competition_ids = wp_parse_id_list( $options->competition_id );
			$format          = implode( ', ', array_fill( 0, count( $competition_ids ), '%d' ) );

			$query .= $wpdb->prepare( " AND ( competition_id IN ({$format}) OR main_stage_id IN ({$format}) ) ", array_merge( $competition_ids, $competition_ids ) ); // phpcs:ignore
		} elseif ( ! empty( $options->competition_id ) ) {

			$competition_ids = wp_parse_id_list( $options->competition_id );
			$format          = implode( ', ', array_fill( 0, count( $competition_ids ), '%d' ) );

			$query .= $wpdb->prepare( " AND competition_id IN ({$format}) ", $competition_ids ); // phpcs:ignore
		}

		/**==================
		 * WHERE filter by stage
		 *================ */
		if ( ! empty( $options->stage_id ) ) {

			$stage_ids = wp_parse_id_list( $options->stage_id );
			$format    = implode( ', ', array_fill( 0, count( $stage_ids ), '%d' ) );

			$query .= $wpdb->prepare( " AND competition_id IN ({$format}) ", $stage_ids ); // phpcs:ignore
		}

		/**==================
		 * WHERE filter by season
		 *================ */
		if ( '' !== $options->season_id && '' === $options->competition_id ) {
			$query .= $wpdb->prepare( ' AND season_id = %d ', $options->season_id );
		}

		/**==================
		 * WHERE filter by league
		 *================ */
		if ( absint( $options->league_id ) ) {
			$query .= $wpdb->prepare( ' AND league_id = %d ', $options->league_id );
		}

		/**==================
		 * WHERE filter by group
		 *================ */
		if ( absint( $options->group_id ) ) {
			$query .= $wpdb->prepare( ' AND group_id = %d ', $options->group_id );
		}

		/**==================
		 * WHERE filter by stadium
		 *================ */
		if ( '' !== $options->stadium_id ) {
			$query .= $wpdb->prepare( ' AND stadium_id = %d ', $options->stadium_id );
		}

		/**==================
		 * WHERE filter by type
		 *================ */
		if ( '' !== $options->type ) {
			$query .= $wpdb->prepare( ' AND finished = %d ', 'result' === $options->type ? 1 : 0 );
		}

		/**==================
		 * WHERE filter by club
		 *================ */
		if ( 'club' === $options->filter_by && ! empty( $options->filter_values ) ) {

			$clubs  = wp_parse_id_list( $options->filter_values );
			$format = implode( ', ', array_fill( 0, count( $clubs ), '%d' ) );

			$query .= $wpdb->prepare( " AND ( home_club IN ({$format}) OR away_club IN ({$format}) ) ", array_merge( $clubs, $clubs ) ); // phpcs:ignore
		}

		if ( ! empty( $options->filter_by_clubs ) ) {

			$clubs  = wp_parse_id_list( $options->filter_by_clubs );
			$format = implode( ', ', array_fill( 0, count( $clubs ), '%d' ) );

			$query .= $wpdb->prepare( " AND ( home_club IN ({$format}) OR away_club IN ({$format}) ) ", array_merge( $clubs, $clubs ) ); // phpcs:ignore
		}

		if ( ! empty( $options->home_club ) ) {
			$query .= $wpdb->prepare( ' AND home_club = %d ', $options->home_club );
		}

		if ( ! empty( $options->away_club ) ) {
			$query .= $wpdb->prepare( ' AND away_club = %d ', $options->away_club );
		}

		/**==================
		 * WHERE filter by matchweek
		 *================ */
		if ( 'matchweek' === $options->filter_by && ! empty( $options->filter_values ) ) {
			if ( false !== strpos( $options->filter_values, '-', 1 ) ) {
				$range_values = explode( '-', $options->filter_values, 2 );

				if ( isset( $range_values[1] ) && absint( $range_values[1] ) > absint( $range_values[0] ) ) {
					$options->filter_values = range( absint( $range_values[0] ), absint( $range_values[1] ) );
				}
			}

			$rounds = wp_parse_id_list( $options->filter_values );
			$format = implode( ', ', array_fill( 0, count( $rounds ), '%d' ) );

			$query .= $wpdb->prepare( " AND match_week IN ({$format}) ", $rounds ); // phpcs:ignore
		}

		if ( ! empty( $options->filter_by_matchweeks ) ) {
			if ( false !== strpos( $options->filter_by_matchweeks, '-', 1 ) ) {
				$range_values = explode( '-', $options->filter_by_matchweeks, 2 );

				if ( isset( $range_values[1] ) && absint( $range_values[1] ) > absint( $range_values[0] ) ) {
					$options->filter_by_matchweeks = range( absint( $range_values[0] ), absint( $range_values[1] ) );
				}
			}

			$rounds = wp_parse_id_list( $options->filter_by_matchweeks );
			$format = implode( ', ', array_fill( 0, count( $rounds ), '%d' ) );

			$query .= $wpdb->prepare( " AND match_week IN ({$format}) ", $rounds ); // phpcs:ignore
		}

		/**==================
		 * WHERE filter by days offset
		 *================ */
		if ( ( $options->days_offset && is_numeric( $options->days_offset ) ) || '0' === $options->days_offset ) {

			$cur_date = apply_filters( 'anwpfl/config/localize_date_arg', true ) ? date_i18n( 'Y-m-d' ) : date( 'Y-m-d' ); // phpcs:ignore

			$days_offset = intval( $options->days_offset );

			if ( $days_offset < 0 ) {
				$query .= $wpdb->prepare( " AND kickoff >= DATE_SUB(%s, INTERVAL %d DAY) ", $cur_date, absint( $days_offset ) ); // phpcs:ignore
			} else {
				$query .= $wpdb->prepare( " AND kickoff >= DATE_ADD(%s, INTERVAL %d DAY) ", $cur_date, absint( $days_offset ) ); // phpcs:ignore
			}
		}

		/**==================
		 * WHERE filter by days offset to
		 *================ */
		if ( ( $options->days_offset_to && is_numeric( $options->days_offset_to ) ) || '0' === $options->days_offset_to ) {

			$days_offset_to = intval( $options->days_offset_to );

			if ( empty( $cur_date ) ) {
				$cur_date = apply_filters( 'anwpfl/config/localize_date_arg', true ) ? date_i18n( 'Y-m-d' ) : date( 'Y-m-d' ); // phpcs:ignore
			}

			if ( $days_offset_to < 0 ) {
				$query .= $wpdb->prepare( " AND kickoff < DATE_SUB(%s, INTERVAL %d DAY) ", $cur_date, absint( $days_offset_to ) ); // phpcs:ignore
			} else {
				$query .= $wpdb->prepare( " AND kickoff < DATE_ADD(%s, INTERVAL %d DAY) ", $cur_date, absint( $days_offset_to ) ); // phpcs:ignore
			}
		}

		/**==================
		 * WHERE filter by priority
		 *================ */
		if ( $options->priority ) {

			$match_priority = (int) $options->priority;

			if ( $match_priority ) {
				$query .= $wpdb->prepare( " AND priority >= %d ", absint( $match_priority ) ); // phpcs:ignore
			}
		}

		/**==================
		 * WHERE filter by date_to
		 *
		 * @since 0.10.3
		 *================ */
		if ( trim( $options->date_to ) ) {
			$date_to = explode( ' ', $options->date_to )[0];

			if ( anwp_football_leagues()->helper->validate_date( $date_to, 'Y-m-d' ) ) {
				$query .= $wpdb->prepare( ' AND kickoff <= %s ', $date_to . ' 23:59:59' );
			}
		}

		/**==================
		 * WHERE filter by date_from
		 * --
		 * @since 0.10.3
		 *================ */
		if ( trim( $options->date_from ) ) {
			$date_from = explode( ' ', $options->date_from )[0];

			if ( anwp_football_leagues()->helper->validate_date( $date_from, 'Y-m-d' ) ) {
				$query .= $wpdb->prepare( ' AND kickoff >= %s ', $date_from . ' 00:00:00' );
			}
		}

		/**==================
		 * WHERE filter by $options->kickoff_to
		 *
		 * @since 0.14.10
		 *================ */
		if ( trim( $options->kickoff_to ) && anwp_football_leagues()->helper->validate_date( $options->kickoff_to ) ) {
			$query .= $wpdb->prepare( ' AND kickoff <= %s ', $options->kickoff_to );
		}

		/**==================
		 * WHERE filter by date_from
		 * --
		 * @since 0.14.10
		 *================ */
		if ( trim( $options->kickoff_from ) && anwp_football_leagues()->helper->validate_date( $options->kickoff_from ) ) {
			$query .= $wpdb->prepare( ' AND kickoff >= %s ', $options->kickoff_from );
		}

		/**==================
		 * WHERE exclude ids
		 * --
		 * @since 0.10.17
		 *================ */
		if ( trim( $options->exclude_ids ) ) {
			$exclude_ids = wp_parse_id_list( $options->exclude_ids );

			if ( ! empty( $exclude_ids ) && is_array( $exclude_ids ) && count( $exclude_ids ) ) {

				// Prepare exclude format and placeholders
				$exclude_placeholders = array_fill( 0, count( $exclude_ids ), '%s' );
				$exclude_format       = implode( ', ', $exclude_placeholders );

				$query .= $wpdb->prepare( " AND match_id NOT IN ({$exclude_format})", $exclude_ids ); // phpcs:ignore
			}
		}

		/**==================
		 * WHERE include ids
		 * --
		 * @since 0.10.17
		 *================ */
		if ( trim( $options->include_ids ) ) {
			$include_ids = wp_parse_id_list( $options->include_ids );

			if ( ! empty( $include_ids ) && is_array( $include_ids ) && count( $include_ids ) ) {

				// Prepare include format and placeholders
				$include_placeholders = array_fill( 0, count( $include_ids ), '%s' );
				$include_format       = implode( ', ', $include_placeholders );

				$query .= $wpdb->prepare( " AND match_id IN ({$include_format})", $include_ids ); // phpcs:ignore
			}
		}

		/**==================
		 * ORDER BY matchweek match date
		 * --
		 * @since 0.8.5 added matchweek sorting
		 *================ */
		$options->sort_by_matchweek = $options->sort_by_matchweek && in_array( strtolower( $options->sort_by_matchweek ), [ 'asc', 'desc' ], true ) ? $options->sort_by_matchweek : '';

		if ( $options->sort_by_matchweek ) {

			$matchweek_order = strtoupper( sanitize_key( $options->sort_by_matchweek ) );

			if ( 'asc' === $options->sort_by_date ) {
				$query .= " ORDER BY match_week $matchweek_order, CASE WHEN kickoff = '0000-00-00 00:00:00' THEN 1 ELSE 0 END, kickoff ASC";
			} elseif ( 'desc' === $options->sort_by_date ) {
				$query .= " ORDER BY match_week $matchweek_order, CASE WHEN kickoff = '0000-00-00 00:00:00' THEN 1 ELSE 0 END, kickoff DESC";
			} else {
				$query .= " ORDER BY match_week $matchweek_order";
			}
		} else {
			if ( 'asc' === $options->sort_by_date ) {
				$query .= ' ORDER BY kickoff ASC, match_id ASC';
			} elseif ( 'desc' === $options->sort_by_date ) {
				$query .= ' ORDER BY kickoff DESC, match_id DESC';
			}
		}

		/*
		|--------------------------------------------------------------------
		| Limit
		|--------------------------------------------------------------------
		*/
		if ( '' !== $options->limit && $options->limit > 0 ) {
			if ( ! empty( $options->offset ) && absint( $options->offset ) ) {
				$query .= $wpdb->prepare( ' LIMIT %d,%d', $options->offset, $options->limit );
			} else {
				$query .= $wpdb->prepare( ' LIMIT %d', $options->limit );
			}
		}

		/*
		|--------------------------------------------------------------------
		| Safety LIMIT - prevent unbounded queries
		|--------------------------------------------------------------------
		*/
		if ( ! str_contains( $query, 'LIMIT' )
			&& empty( $options->competition_id )
			&& empty( $options->stage_id )
			&& empty( $options->season_id )
			&& empty( $options->league_id )
			&& empty( $options->include_ids )
			&& empty( $options->filter_by_clubs )
			&& empty( $options->home_club )
			&& empty( $options->away_club )
		) {
			$safety_limit = absint( apply_filters( 'anwpfl/competition/safety_query_limit', 200 ) );
			$query       .= $wpdb->prepare( ' LIMIT %d', $safety_limit );
		}

		$matches = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL

		/**==================
		 * Group by competition (sorting by competition id and ordering)
		 * --
		 * @since 0.10.18
		 *================ */
		if ( 'competition' === $options->group_by ) {
			$matches = $this->sort_matches_by_competition_order_and_id( $matches );
		}

		if ( 'stats' === $result ) {
			$tmpl_matches_cache[ $cache_key ] = $matches;

			return $matches;
		}

		// Populate Object Cache
		$ids = wp_list_pluck( $matches, 'match_id' );

		if ( 'ids' === $result ) {
			$tmpl_matches_cache[ $cache_key ] = $ids;

			return $ids;
		}

		$permalinks = $this->plugin->helper->get_permalinks_by_ids( $ids, 'anwp_match' );

		// Add extra data to match
		foreach ( $matches as $match ) {
			$match->permalink = $permalinks[ $match->match_id ] ?? '';
		}

		$this->warm_clubs_and_competitions_for_matches( $matches );

		$tmpl_matches_cache[ $cache_key ] = $matches;

		return $matches;
	}

	/**
	 * Return a shallow-cloned copy of a cached match list so callers may
	 * mutate the returned objects (e.g. attach `->permalink`) without
	 * poisoning the in-memory cache for later cache hits.
	 *
	 * Non-object entries (e.g. plain ID array for $result === 'ids') pass
	 * through unchanged.
	 *
	 * @param mixed $cached Cached value.
	 *
	 * @return mixed
	 */
	private function clone_cached_match_list( $cached ) {
		if ( ! is_array( $cached ) ) {
			return $cached;
		}

		return array_map(
			static function ( $entry ) {
				return is_object( $entry ) ? clone $entry : $entry;
			},
			$cached
		);
	}

	/**
	 * Sort Matches by competition order and ID
	 *
	 * @param $matches
	 *
	 * @return array
	 * @since 0.10.18
	 */
	private function sort_matches_by_competition_order_and_id( $matches ): array {
		if ( empty( $matches ) || ! is_array( $matches ) ) {
			return $matches;
		}

		static $competition_options = null;

		if ( null === $competition_options ) {
			$competition_options = [];
			foreach ( anwp_fl()->competition->get_competitions_data() as $competition ) {
				$competition_options[ $competition['id'] ] = [
					'title' => $competition['title'],
					'order' => $competition['competition_order'],
				];
			}
		}

		foreach ( $matches as $match_index => $match ) {
			$match->competition_order = isset( $competition_options[ $match->competition_id ] ) ? $competition_options[ $match->competition_id ]['order'] : 0;
			$match->competition_title = isset( $competition_options[ $match->competition_id ] ) ? $competition_options[ $match->competition_id ]['title'] : '';
			$match->pre_sort_index    = $match_index;
		}

		return wp_list_sort(
			$matches,
			[
				'competition_order' => 'ASC',
				'competition_title' => 'ASC',
				'competition_id'    => 'ASC',
				'pre_sort_index'    => 'ASC',
			]
		);
	}

	/**
	 * Get taxonomies data for selected competition.
	 *
	 * @param int $competition_id
	 *
	 * @since 0.3.0 (2018-02-07)
	 * @return array
	 */
	public function tmpl_get_competition_terms( $competition_id ) {

		$data = [
			'league_id'    => '',
			'league_title' => '',
			'season_id'    => [],
			'season_title' => [],
		];

		$terms = wp_get_post_terms( $competition_id, [ 'anwp_league', 'anwp_season' ] );

		if ( is_array( $terms ) ) {

			foreach ( $terms as $term ) {

				if ( 'anwp_league' === $term->taxonomy && $term->term_id ) {
					$data['league_id']    = $term->term_id;
					$data['league_title'] = $term->name;
				}

				if ( 'anwp_season' === $term->taxonomy ) {
					$data['season_id'][]    = $term->term_id;
					$data['season_title'][] = $term->name;
				}
			}
		}

		return $data;
	}

	/**
	 * Get list of sorted competitions.
	 *
	 * @param int   $competition_id
	 * @param bool  $multistage
	 * @param array $matches
	 *
	 * @since 0.3.0 (2018-02-07)
	 * @return array
	 */
	public function tmpl_get_prepared_competitions( $competition_id, $multistage, $matches ) {
		if ( $matches && is_array( $matches ) && $multistage ) {

			$competitions_ids = [];
			foreach ( $matches as $match ) {
				$competitions_ids[] = $match->competition_id;
			}

			$competitions_ids = array_values( array_unique( $competitions_ids ) );

			$competitions = get_posts(
				[
					'ignore_sticky_posts' => true,
					'numberposts'         => - 1,
					'order'               => 'ASC',
					'post_status'         => [ 'publish', 'stage_secondary' ],
					'orderby'             => 'ID',
					'post_type'           => 'anwp_competition',
					'post__in'            => $competitions_ids,
				]
			);
		} else {
			$competitions = [ get_post( $competition_id ) ];
		}

		// Warm display columns for all stages in one query (light warm - no JSON blobs needed for sort).
		$this->warm_competitions( wp_list_pluck( $competitions, 'ID' ) );

		$stage_order = [];
		foreach ( $competitions as $c ) {
			$stage_order[ $c->ID ] = absint( $this->get_competition_list_row( (int) $c->ID )['stage_order'] ?? 0 );
		}

		usort(
			$competitions,
			function ( $a, $b ) use ( $stage_order ) {

				if ( ! isset( $stage_order[ $b->ID ] ) || ! isset( $stage_order[ $a->ID ] ) ) {
					return 0;
				}

				return $stage_order[ $b->ID ] - $stage_order[ $a->ID ];
			}
		);

		return $competitions;
	}

	/**
	 * Get Standing for competition.
	 *
	 * @param $competition_id
	 * @param $group_id
	 *
	 * @return array
	 * @since 0.7.2 (2018-09-17) added $group_id parameter
	 */
	public function tmpl_get_competition_standings( $competition_id, $group_id ) {
		static $cache = [];

		$cache_key = $competition_id . '-' . $group_id;

		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		global $wpdb;

		// Indexed query on anwpfl_standings (competition_group KEY) replaces
		// the 2-JOIN meta_query on wp_postmeta. Results wrapped in (object)['ID']
		// to preserve the ->ID access pattern used by 16 callers + theme overrides.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT standing_id FROM {$wpdb->anwpfl_standings}
				 WHERE competition_id = %d AND group_id = %d LIMIT 1",
				$competition_id,
				$group_id
			)
		);

		$result = array_map(
			function ( $id ) {
				return (object) [ 'ID' => (int) $id ];
			},
			$ids
		);

		$cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Get list of clubs by selected competition and group.
	 *
	 * @param int   $competition_id
	 * @param mixed $group_arg Integer (ID for one group) or 'all'
	 *
	 * @since 0.3.0 (2018-01-31)
	 * @since 0.4.3 (2018-02-23) Added all option in group_id
	 * @return array
	 */
	public function get_competition_clubs( $competition_id, $group_arg ) {

		$row = $this->get_row( (int) $competition_id );

		if ( ! $row || empty( $row['stage_groups'] ) ) {
			return [];
		}

		$groups = json_decode( (string) $row['stage_groups'] );

		if ( empty( $groups ) || ! is_array( $groups ) ) {
			return [];
		}

		// @since 0.4.3 - Get all competition clubs ($group_id == 'all')
		if ( 'all' === $group_arg ) {
			$clubs = [];

			foreach ( $groups as $group ) {
				if ( ! empty( $group->clubs ) && is_array( $group->clubs ) ) {
					$clubs = array_merge( $clubs, $group->clubs );
				}
			}

			return $clubs;
		}

		// Get clubs for selected group_id
		foreach ( $groups as $group ) {
			if ( (int) $group->id === $group_arg && ! empty( $group->clubs ) && is_array( $group->clubs ) ) {
				return $group->clubs;
			}
		}

		return [];
	}

	/**
	 * Get list of all clubs for multistage competition.
	 *
	 * @param int   $competition_id
	 *
	 * @since 0.10.20
	 * @return array
	 */
	public function get_competition_multistage_clubs( $competition_id ) {

		$club_ids = $this->get_competition_clubs( $competition_id, 'all' );
		$stages   = $this->get_secondary_competitions_list( $competition_id );

		if ( empty( $stages ) || ! is_array( $stages ) ) {
			return $club_ids;
		}

		$stage_competition_ids = wp_list_pluck( $stages, 'id' );

		foreach ( $stage_competition_ids as $stage_competition_id ) {
			$stage_clubs = $this->get_competition_clubs( $stage_competition_id, 'all' );

			if ( ! empty( $stage_clubs ) && is_array( $stage_clubs ) ) {
				$club_ids = array_merge( $club_ids, $stage_clubs );
			}
		}

		$club_ids = array_unique( $club_ids );

		return $club_ids;
	}

	/**
	 * Get list of competitions.
	 * Used on admin Standing page.
	 * Used on admin Match page.
	 *
	 * @since 0.2.0 (2017-12-10)
	 * @deprecated Use lighter version get_competitions_data()
	 * @return array $output_data -
	 */
	public function get_competitions( $force_update = false ) {

		// @deprecated 0.18.0 use get_competitions_data() - runtime notice deferred until Phase 6 migrates internal callers.

		static $output_data = null;

		if ( null !== $output_data && ! $force_update ) {
			return $output_data;
		}

		$output_data = [];
		$data        = $this->get_competitions_data( $force_update );

		if ( empty( $data ) ) {
			return $output_data;
		}

		/*
		|--------------------------------------------------------------------
		| Bulk-fetch heavy groups/rounds columns in one query.
		|
		| get_competitions_data() ships a light SELECT (v0.18.1) so row_cache
		| is NOT primed with full rows. Fetching per-ID via get_row() would
		| fire N queries here; one WHERE IN (...) keeps it at one.
		|--------------------------------------------------------------------
		*/
		global $wpdb;

		$ids          = array_map( 'absint', wp_list_pluck( $data, 'id' ) );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$heavy_rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $placeholders contains only %d tokens fed to prepare().
				"SELECT competition_id, stage_groups, stage_rounds FROM {$wpdb->anwpfl_competitions} WHERE competition_id IN ($placeholders)",
				$ids
			),
			OBJECT_K
		) ?: [];

		foreach ( $data as $competition_data ) {
			$obj = (object) $competition_data;

			$heavy = $heavy_rows[ $competition_data['id'] ] ?? null;

			$obj->groups = $heavy ? json_decode( (string) ( $heavy->stage_groups ?? '' ) ) : null;
			$obj->rounds = $heavy ? json_decode( (string) ( $heavy->stage_rounds ?? '' ) ) : null;

			$output_data[] = $obj;
		}

		return $output_data;
	}

	/**
	 * Get list of competitions.
	 * Simplified version of get_competitions())
	 *
	 * @return array[
	 *     [id] => 52575
	 *     [title] => UEFA Nations League 2024-2025 - League B
	 *     [title_full] => UEFA Nations League 2024-2025 - League B
	 *     [stage_title] => League B
	 *     [stage_order] => 2
	 *     [type] => round-robin
	 *     [logo] =>
	 *     [league_id] => 44
	 *     [season_ids] => 40
	 *     [league_text] => UEFA Nations League
	 *     [season_text] => 2024-2025
	 *     [multistage] => secondary
	 *     [multistage_main] => 52572
	 *     [competition_order] => 0
	 *     [c_index] => 86
	 * ]
	 * @since 0.16.13
	 */
	public function get_competitions_data( $force_update = false ) {

		static $output_data = null;

		if ( null !== $output_data && ! $force_update ) {
			return $output_data;
		}

		global $wpdb;

		/*
		|--------------------------------------------------------------------
		| Fast path: single SELECT from custom table on migrated sites.
		|
		| Phase 1.4 (v0.18.1) - replaces the legacy wp_posts scan + fetch_rows
		| pair with one query. Saves -1 query on every page that hits
		| get_competitions_data() (competition sidebars, dropdowns, etc.).
		|
		| Trade-off: draft/trash/auto-draft rows now surface in the output
		| because the custom table write hook (sync_competition_to_table)
		| doesn't filter by post_status. Accepted as cosmetic (drafts in a
		| dropdown) in exchange for the query count win.
		|
		| Secondary-stage routing uses the `multistage` column value
		| ('secondary') as a proxy for the legacy post_status='stage_secondary'
		| check (values set at :3186 + :539).
		|--------------------------------------------------------------------
		*/
		if ( get_option( 'anwpfl_competitions_migrated' ) ) {

			/*
			|------------------------------------------------------------
			| Light SELECT: mirror warm_competitions() column list so
			| list_cache honors its documented light-row contract. Heavy
			| TEXT blobs (stage_groups, stage_rounds, bracket_options,
			| competition_roles, custom_content_below, tmpl_layout) are
			| fetched per-ID via get_row() on pages that need them.
			|------------------------------------------------------------
			*/
			$light_columns = apply_filters(
				'anwpfl/competition/warm_light_columns',
				[
					'title',
					'post_name',
					'league_id',
					'league_text',
					'season_ids',
					'season_text',
					'type',
					'is_friendly',
					'multistage',
					'multistage_main',
					'stage_title',
					'stage_order',
					'competition_order',
					'logo',
					'logo_id',
					'logo_big',
					'logo_big_id',
				]
			);

			$columns_sql = 'competition_id, ' . implode( ', ', array_map( 'sanitize_key', $light_columns ) );

			$table_rows = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $columns_sql is sanitize_key()'d.
				"SELECT $columns_sql FROM {$wpdb->anwpfl_competitions} ORDER BY title ASC",
				ARRAY_A
			) ?: [];

			if ( empty( $table_rows ) ) {
				$output_data = [];
				return $output_data;
			}

			$output_data      = [];
			$secondary_stages = [];

			foreach ( $table_rows as $row ) {
				$post_id = (int) $row['competition_id'];

				// Seed list_cache only. row_cache promises a full row (gotcha #2);
				// seeding it with a light row would break get_row() callers.
				$this->list_cache[ $post_id ] = $row;

				$entry = [
					'id'                => $post_id,
					'title'             => $row['title'] ?? '',
					'title_full'        => $row['title'] ?? '',
					'stage_title'       => $row['stage_title'] ?? '',
					'stage_order'       => absint( $row['stage_order'] ?? 0 ),
					'type'              => $row['type'] ?? '',
					'logo'              => $row['logo'] ?? '',
					'league_id'         => absint( $row['league_id'] ?? 0 ),
					'season_ids'        => trim( (string) ( $row['season_ids'] ?? '' ), ',' ),
					'league_text'       => (string) ( $row['league_text'] ?? '' ),
					'season_text'       => trim( (string) ( $row['season_text'] ?? '' ), ',' ),
					'multistage'        => $row['multistage'] ?? '',
					'multistage_main'   => absint( $row['multistage_main'] ?? 0 ),
					'competition_order' => absint( $row['competition_order'] ?? 0 ),
				];

				if ( '' !== $entry['multistage'] && $entry['stage_title'] && ! str_contains( $entry['title_full'], $entry['stage_title'] ) ) {
					$entry['title_full'] .= ' - ' . $entry['stage_title'];
				}

				// Route as secondary only when multistage_main points to a DIFFERENT
				// competition. Self-referencing rows (multistage_main == self) exist
				// in real data (e.g. Ligue 1 first-stage "main" carries multistage='secondary'
				// with multistage_main = own ID) and belong in the main output; legacy
				// path kept them visible via post_status='publish' routing.
				if ( 'secondary' === $entry['multistage'] && $entry['multistage_main'] > 0 && $entry['multistage_main'] !== $post_id ) {
					$secondary_stages[ $entry['multistage_main'] ][] = $entry;
				} else {
					$output_data[] = $entry;
				}
			}

			$output_data = $this->splice_secondary_stages( $output_data, $secondary_stages );
			$output_data = $this->index_competitions_output( $output_data );

			return $output_data;
		}

		/*
		|--------------------------------------------------------------------
		| Legacy path (mid-migration, anwpfl_competitions_migrated not set).
		|--------------------------------------------------------------------
		*/
		$post_rows = $wpdb->get_results(
			"
			SELECT ID, post_title, post_status
			FROM $wpdb->posts
			WHERE post_type = 'anwp_competition' AND post_status IN ( 'publish', 'stage_secondary' )
			ORDER BY post_title ASC
			"
		); // phpcs:ignore WordPress.DB.PreparedSQL

		if ( empty( $post_rows ) ) {
			$output_data = [];
			return $output_data;
		}

		$post_ids = array_map( 'absint', wp_list_pluck( $post_rows, 'ID' ) );

		/*
		|--------------------------------------------------------------------
		| Bulk-load all rows from the custom table.
		|--------------------------------------------------------------------
		*/
		$table_rows = $this->fetch_rows( $post_ids );

		// Populate per-request caches so subsequent get_row() / get_competition_list_row()
		// calls for the same IDs don't re-query the DB.
		foreach ( $table_rows as $cached_id => $cached_row ) {
			$this->cache_row( (int) $cached_id, $cached_row );
			$this->list_cache[ (int) $cached_id ] = $cached_row;
		}

		// Prime caches for postmeta fallback (mid-migration only).
		$missing = array_diff( $post_ids, array_keys( $table_rows ) );

		if ( ! empty( $missing ) ) {
			update_meta_cache( 'post', $missing );
			update_object_term_cache( $missing, 'anwp_competition' );
		}

		/*
		|--------------------------------------------------------------------
		| Build output entries (preserving legacy shape).
		|--------------------------------------------------------------------
		*/
		$output_data      = [];
		$secondary_stages = [];

		foreach ( $post_rows as $post ) {
			$post_id = (int) $post->ID;

			if ( isset( $table_rows[ $post_id ] ) ) {
				$row = $table_rows[ $post_id ];
			} else {
				$row = $this->get_competition_row_from_postmeta( $post_id );

				if ( ! $row ) {
					continue;
				}

				// Mid-migration fallback: secondary stages inherit parent's competition_order.
				// INTENTIONAL postmeta read - parent may also be on postmeta-only state.
				// Do NOT replace with get_row()/get_competition_list_row() as that would
				// recursively re-enter this fallback branch for the parent.
				if ( 'stage_secondary' === $post->post_status ) {
					$main_id = absint( $row['multistage_main'] );

					if ( $main_id ) {
						$row['competition_order'] = absint( get_post_meta( $main_id, '_anwpfl_competition_order', true ) );
					}
				}

				// Populate row cache from postmeta fallback too.
				$this->cache_row( $post_id, $row );
				$this->list_cache[ $post_id ] = $row;
			}

			$entry = [
				'id'                => $post_id,
				'title'             => $row['title'] ?? $post->post_title,
				'title_full'        => $row['title'] ?? $post->post_title,
				'stage_title'       => $row['stage_title'] ?? '',
				'stage_order'       => absint( $row['stage_order'] ?? 0 ),
				'type'              => $row['type'] ?? '',
				'logo'              => $row['logo'] ?? '',
				'league_id'         => absint( $row['league_id'] ?? 0 ),
				'season_ids'        => trim( (string) ( $row['season_ids'] ?? '' ), ',' ),
				'league_text'       => (string) ( $row['league_text'] ?? '' ),
				'season_text'       => trim( (string) ( $row['season_text'] ?? '' ), ',' ),
				'multistage'        => $row['multistage'] ?? '',
				'multistage_main'   => absint( $row['multistage_main'] ?? 0 ),
				'competition_order' => absint( $row['competition_order'] ?? 0 ),
			];

			// Set full title in multistage competitions.
			if ( '' !== $entry['multistage'] && $entry['stage_title'] && ! str_contains( $entry['title_full'], $entry['stage_title'] ) ) {
				$entry['title_full'] .= ' - ' . $entry['stage_title'];
			}

			if ( 'stage_secondary' === $post->post_status ) {
				$secondary_stages[ $entry['multistage_main'] ][] = $entry;
			} else {
				$output_data[] = $entry;
			}
		}

		$output_data = $this->splice_secondary_stages( $output_data, $secondary_stages );
		$output_data = $this->index_competitions_output( $output_data );

		return $output_data;
	}

	/**
	 * Splice secondary-stage entries directly after their main parent.
	 *
	 * Shared helper for get_competitions_data() fast + legacy paths.
	 *
	 * @since 0.18.1
	 *
	 * @param array $main_stages     Main-stage entries (flat list).
	 * @param array $secondary_stages Secondary entries grouped by main-stage ID.
	 * @return array Flat list with secondaries inserted after their parent.
	 */
	private function splice_secondary_stages( array $main_stages, array $secondary_stages ): array {
		if ( empty( $secondary_stages ) ) {
			return $main_stages;
		}

		$spliced_main_ids = [];
		$cloned_data      = $main_stages;

		foreach ( $cloned_data as $main_stage_competition ) {
			$main_id = $main_stage_competition['id'];

			if ( ! empty( $secondary_stages[ $main_id ] ) ) {
				$stages = wp_list_sort( $secondary_stages[ $main_id ], 'stage_order' );
				$index  = array_search( $main_id, wp_list_pluck( $main_stages, 'id' ), true );

				array_splice( $main_stages, $index + 1, 0, $stages );
				$spliced_main_ids[ $main_id ] = true;
			}
		}

		// Append orphaned secondaries (parent main missing from output) so they
		// stay visible. Mirrors legacy tolerance for broken multistage data.
		foreach ( $secondary_stages as $main_id => $stages ) {
			if ( isset( $spliced_main_ids[ $main_id ] ) ) {
				continue;
			}

			$stages = wp_list_sort( $stages, 'stage_order' );

			foreach ( $stages as $orphan ) {
				$main_stages[] = $orphan;
			}
		}

		return $main_stages;
	}

	/**
	 * Key the competitions list by ID and attach c_index order.
	 *
	 * @since 0.18.1
	 *
	 * @param array $output_data Flat ordered list.
	 * @return array Keyed by competition_id with c_index merged in.
	 */
	private function index_competitions_output( array $output_data ): array {
		$indexed = [];

		foreach ( $output_data as $c_key => $c_data ) {
			$indexed[ $c_data['id'] ] = array_merge( $c_data, [ 'c_index' => $c_key ] );
		}

		return $indexed;
	}

	/**
	 * Get list of competition groups without assigned standings.
	 *
	 * @return array $output_data
	 * @since 0.11.1
	 */
	public function get_competition_groups_without_standing(): array {

		$competitions = $this->get_competitions();

		if ( empty( $competitions ) || ! is_array( $competitions ) ) {
			return [];
		}

		$output_data = [];

		// Filter only round robin competitions
		$competitions = wp_list_filter( $competitions, [ 'type' => 'round-robin' ] );

		if ( empty( $competitions ) || ! is_array( $competitions ) ) {
			return [];
		}

		/*
		|--------------------------------------------------------------------
		| Get all saved Standing Tables from custom table
		|--------------------------------------------------------------------
		*/
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$standings = $wpdb->get_results(
			"SELECT s.standing_id, s.competition_id, s.group_id
			FROM {$wpdb->prefix}anwpfl_standings s
			INNER JOIN {$wpdb->posts} p ON s.standing_id = p.ID
			WHERE p.post_status = 'publish'
				AND s.competition_id > 0"
		);

		$standings_map = [];

		if ( ! empty( $standings ) && is_array( $standings ) ) {
			foreach ( $standings as $standing ) {
				$standings_map[ $standing->competition_id ][ $standing->group_id ] = $standing->standing_id;
			}
		}

		foreach ( $competitions as $competition ) {
			$c_groups      = [];
			$c_competition = clone $competition;

			if ( ! empty( $c_competition->groups ) && is_array( $c_competition->groups ) ) {
				foreach ( $c_competition->groups as $group ) {

					if ( ! empty( $standings_map[ $c_competition->id ] ) && ! empty( $standings_map[ $c_competition->id ][ $group->id ] ) ) {
						continue;
					}

					$c_groups[] = $group;
				}
			}

			if ( ! empty( $c_groups ) ) {
				$c_competition->groups = $c_groups;
				$output_data[]         = $c_competition;
			}
		}

		return $output_data;
	}

	/**
	 * Get list of standings grouped by competition.
	 *
	 * @return array $output_data
	 * @since 0.11.1
	 */
	public function get_competition_standings_map() {

		static $output_data = null;

		if ( null === $output_data ) {

			global $wpdb;

			$output_data = [];

			/*
			|--------------------------------------------------------------------
			| Get all saved Standing Tables from custom table
			|--------------------------------------------------------------------
			*/
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$standings = $wpdb->get_results(
				"SELECT s.standing_id, s.competition_id, p.post_title
				FROM {$wpdb->prefix}anwpfl_standings s
				INNER JOIN {$wpdb->posts} p ON s.standing_id = p.ID
				WHERE p.post_status = 'publish'
					AND s.competition_id > 0
				ORDER BY s.standing_id"
			);

			if ( ! empty( $standings ) && is_array( $standings ) ) {
				foreach ( $standings as $standing ) {
					$output_data[ $standing->competition_id ][ $standing->standing_id ] = $standing->post_title;
				}
			}
		}

		return $output_data;
	}

	/**
	 * Method returns array of all competition (+ stages names for multistage)
	 *
	 * @return array
	 * @since 0.4.3 (2018-02-22)
	 */
	public function get_competition_options( $include_secondary = true ) {

		// Use get_competitions_data() so the postmeta fallback covers pre-migration state.
		// This is a BULK caller (admin dropdowns) - per-request static cache makes repeat calls free.
		$competitions = $this->get_competitions_data();

		if ( empty( $competitions ) ) {
			return [];
		}

		$options = [];

		foreach ( $competitions as $stage ) {
			if ( ! $include_secondary ) {
				if ( 'secondary' === $stage['multistage'] ) {
					continue;
				}

				$options[ $stage['id'] ] = $stage['title'];
				continue;
			}

			$options[ $stage['id'] ] = $stage['title_full'];
		}

		return $options;
	}

	/**
	 * Registers admin columns to display. Hooked in via hooks().
	 *
	 * @param array $columns Array of registered column names/labels.
	 *
	 * @return array          Modified array.
	 */
	public function sortable_columns( $sortable_columns ) {

		return array_merge( $sortable_columns, [ 'competition_id' => 'ID' ] );
	}

	/**
	 * Registers admin columns to display. Hooked in via hooks().
	 *
	 * @param  array $columns Array of registered column names/labels.
	 *
	 * @return array          Modified array.
	 * @since  0.1.0
	 */
	public function columns( $columns ) {

		// Add new columns
		$new_columns = [
			'anwpfl_league_logo' => esc_html__( 'Logo', 'anwp-football-leagues' ),
			'anwpfl_multistage'  => esc_html__( 'Stages', 'anwp-football-leagues' ),
			'competition_id'     => esc_html__( 'ID', 'anwp-football-leagues' ),
			'anwpfl_matches_qty' => esc_html__( 'Matches', 'anwp-football-leagues' ),
			'anwpfl_standings'   => esc_html__( 'Standings', 'anwp-football-leagues' ),
		];

		// Merge old and new columns
		$columns = array_merge( $new_columns, $columns );

		// Change columns order
		$new_columns_order = [
			'cb',
			'title',
			'anwpfl_league_logo',
			'taxonomy-anwp_league',
			'taxonomy-anwp_season',
			'anwpfl_multistage',
			'anwpfl_standings',
			'anwpfl_matches_qty',
			'date',
			'competition_id',
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
	 * @since  0.1.0
	 *
	 * @param array   $column  Column currently being rendered.
	 * @param integer $post_id ID of post to display column for.
	 */
	public function columns_display( $column, $post_id ) {

		// Warm full rows for the entire admin list query once per request.
		// Full warm (not light) because stage_groups is needed for the group count display.
		static $warmed = false;

		if ( ! $warmed ) {
			if ( isset( $GLOBALS['wp_query'] ) && ! empty( $GLOBALS['wp_query']->posts ) ) {
				$this->warm_competitions_full( wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
			}

			$warmed = true;
		}

		$row = $this->get_row( (int) $post_id ) ?: [];

		switch ( $column ) {

			case 'anwpfl_league_logo':
				$logo = (string) ( $row['logo'] ?? '' );

				if ( $logo ) {
					printf( '<img src="%s" class="anwp-admin-table-league-logo" alt="club logo" style="width: 30px; height: 30px; object-fit: contain;">', esc_url( $logo ) );
				}
				break;

			case 'anwpfl_multistage':
				$multistage  = (string) ( $row['multistage'] ?? '' );
				$type        = (string) ( $row['type'] ?? '' );
				$group_title = 'round-robin' === $type ? __( 'Groups', 'anwp-football-leagues' ) : __( 'Ties', 'anwp-football-leagues' );

				if ( '' === $multistage ) {
					echo sprintf( '<span class="anwp-g-label-like">%s</span>', esc_html__( 'Single', 'anwp-football-leagues' ) );
					echo '<br>> ' . esc_html( empty( $this->type_map[ $type ] ) ? '' : $this->type_map[ $type ] );

					if ( 'round-robin' === $type ) {
						$subtype = (string) ( $row['format_robin'] ?? '' );
						echo ' | ' . esc_html( empty( $this->format_robin_map[ $subtype ] ) ? '' : $this->format_robin_map[ $subtype ] );
					} elseif ( 'knockout' === $type ) {
						$subtype = (string) ( $row['format_knockout'] ?? '' );
						echo ' | ' . esc_html( empty( $this->format_knockout_map[ $subtype ] ) ? '' : $this->format_knockout_map[ $subtype ] );
					}

					// Get number of groups
					$groups = json_decode( (string) ( $row['stage_groups'] ?? '' ) );

					if ( is_array( $groups ) ) {
						echo ' | ' . esc_html( $group_title ) . ':&nbsp;' . (int) count( $groups );
					}

					echo ' | ' . esc_html__( 'ID', 'anwp-football-leagues' ) . ':&nbsp;' . (int) $post_id;
				} else {

					echo sprintf( '<span class="anwp-g-label-like">%s</span>', esc_html__( 'Multiple', 'anwp-football-leagues' ) );

					// Render main stage
					echo '<br><div class="anwp-g-stage-wrap">> <b>' . esc_html( (string) ( $row['stage_title'] ?? '' ) ) . '</b> | ';
					echo esc_html( empty( $this->type_map[ $type ] ) ? '' : $this->type_map[ $type ] );

					if ( 'round-robin' === $type ) {
						$subtype = (string) ( $row['format_robin'] ?? '' );
						echo ' | ' . esc_html( empty( $this->format_robin_map[ $subtype ] ) ? '' : $this->format_robin_map[ $subtype ] );
					} elseif ( 'knockout' === $type ) {
						$subtype = (string) ( $row['format_knockout'] ?? '' );
						echo ' | ' . esc_html( empty( $this->format_knockout_map[ $subtype ] ) ? '' : $this->format_knockout_map[ $subtype ] );
					}

					// Get number of groups
					$groups = json_decode( (string) ( $row['stage_groups'] ?? '' ) );

					if ( is_array( $groups ) ) {
						echo ' | ' . esc_html( $group_title ) . ':&nbsp;' . count( $groups ) . ' | ';
					}

					echo esc_html__( 'ID', 'anwp-football-leagues' ) . ':&nbsp;' . (int) $post_id;

					echo '</div>';
					// --- end of rendering main stage ---

					if ( 'main' === $multistage ) {
						// Get all secondary stages
						$stages = $this->get_secondary_competitions_list( $post_id );

						foreach ( $stages as $stage ) {
							$stage_group_title = 'round-robin' === $stage['type'] ? __( 'Groups', 'anwp-football-leagues' ) : __( 'Ties', 'anwp-football-leagues' );

							echo '<div class="anwp-g-stage-wrap">>> <b>' . esc_html( $stage['stage_title'] ) . '</b> | ';
							echo esc_html( empty( $this->type_map[ $stage['type'] ] ) ? '' : $this->type_map[ $stage['type'] ] );

							if ( 'round-robin' === $stage['type'] ) {
								$subtype = $stage['format_robin'];
								echo ' | ' . esc_html( empty( $this->format_robin_map[ $subtype ] ) ? '' : $this->format_robin_map[ $subtype ] );
							} elseif ( 'knockout' === $stage['type'] ) {
								$subtype = $stage['format_knockout'];
								echo ' | ' . esc_html( empty( $this->format_knockout_map[ $subtype ] ) ? '' : $this->format_knockout_map[ $subtype ] );
							}

							echo ' | ' . esc_html( $stage_group_title ) . ':&nbsp;' . (int) $stage['groups'] . ' | ';
							echo esc_html__( 'ID', 'anwp-football-leagues' ) . ':&nbsp;' . (int) $stage['id'];
							echo '</div>';
						}
					} elseif ( ! absint( $row['multistage_main'] ?? 0 ) ) {
						echo esc_html__( '!!! Error: Main Stage in Multistage competition is not set.', 'anwp-football-leagues' );
					}
				}

				break;

			case 'anwpfl_standings':
				$multistage   = (string) ( $row['multistage'] ?? '' );
				$type         = (string) ( $row['type'] ?? '' );
				$standing_map = $this->get_competition_standings_map();

				if ( empty( $standing_map ) ) {
					break;
				}

				if ( '' === $multistage ) {
					if ( 'round-robin' !== $type ) {
						break;
					}

					if ( ! empty( $standing_map[ $post_id ] ) ) {
						foreach ( $standing_map[ $post_id ] as $standing_id => $standing_title ) {
							$edit_link = admin_url( 'post.php?post=' . intval( $standing_id ) . '&action=edit' );

							echo '<svg class="anwp-icon anwp-icon--octi" style="margin-bottom: -2px;"><use href="#icon-link-external"></use></svg>&nbsp;&nbsp;';
							echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $standing_title ) . '</a><br>';
						}
					}
				} elseif ( 'main' === $multistage ) {

					if ( 'round-robin' === $type ) {
						if ( ! empty( $standing_map[ $post_id ] ) ) {
							foreach ( $standing_map[ $post_id ] as $standing_id => $standing_title ) {
								$edit_link = admin_url( 'post.php?post=' . intval( $standing_id ) . '&action=edit' );

								echo '<svg class="anwp-icon anwp-icon--octi" style="margin-bottom: -2px;"><use href="#icon-link-external"></use></svg>&nbsp;&nbsp;';
								echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $standing_title ) . '</a><br>';
							}
						}
					}

					// Get all secondary stages
					$stages = $this->get_secondary_competitions_list( $post_id );

					foreach ( $stages as $stage ) {
						if ( 'round-robin' === $stage['type'] && ! empty( $standing_map[ $stage['id'] ] ) ) {
							foreach ( $standing_map[ $stage['id'] ] as $standing_id => $standing_title ) {
								$edit_link = admin_url( 'post.php?post=' . intval( $standing_id ) . '&action=edit' );

								echo '<svg class="anwp-icon anwp-icon--octi" style="margin-bottom: -2px;"><use href="#icon-link-external"></use></svg>&nbsp;&nbsp;';
								echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $standing_title ) . '</a><br>';
							}
						}
					}
				}

				break;

			case 'anwpfl_matches_qty':
				echo absint( $this->get_column_matches_qty()[ $post_id ]['finished'] ?? 0 ) . '/' . absint( $this->get_column_matches_qty()[ $post_id ]['qty'] ?? 0 );

				break;

			case 'competition_id':
				echo (int) $post_id;
				break;
		}
	}

	/**
	 * Get Main Competition ID.
	 *
	 * @param int $id
	 *
	 * @since 0.7.4
	 * @return int
	 */
	public function get_main_competition_id( $id ) {
		$row = $this->get_competition_list_row( (int) $id );

		if ( ! $row ) {
			return $id;
		}

		if ( 'secondary' === ( $row['multistage'] ?? '' ) ) {
			return absint( $row['multistage_main'] ?? 0 );
		}

		return $id;
	}

	/**
	 * Get season selector data for a specific league.
	 * Single query on the custom table, no wp_posts JOIN.
	 *
	 * @param int $league_id League term ID.
	 *
	 * @since 0.18.0
	 * @return array [ [ 'id' => int, 'season_ids' => string, 'permalink' => string ], ... ]
	 */
	public function get_league_season_selector( int $league_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT competition_id, season_ids, post_name
				FROM {$wpdb->anwpfl_competitions}
				WHERE league_id = %d
					AND multistage != 'secondary'",
				$league_id
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return [];
		}

		if ( $this->plugin->cache->simple_link_building ) {
			$slug = $this->plugin->options->get_permalink_structure()['competition'];
			$base = home_url( '/' . $slug . '/' );

			foreach ( $rows as &$row ) {
				$row['permalink']  = $base . $row['post_name'] . '/';
				$row['season_ids'] = trim( (string) ( $row['season_ids'] ?? '' ), ',' );
			}
		} else {
			$ids   = wp_list_pluck( $rows, 'competition_id' );
			$links = $this->plugin->helper->get_permalinks_by_ids( array_map( 'absint', $ids ), 'anwp_competition' );

			foreach ( $rows as &$row ) {
				$row['permalink']  = $links[ (int) $row['competition_id'] ] ?? '';
				$row['season_ids'] = trim( (string) ( $row['season_ids'] ?? '' ), ',' );
			}
		}

		return $rows;
	}

	/**
	 * Returns competition round title.
	 *
	 * @param $competition_id
	 * @param $round_id
	 *
	 * @return string Competition round title.
	 * @since 0.10.0
	 */
	public function get_round_title( $competition_id, $round_id ): string {

		$title = '';

		static $competition_rounds = [];

		if ( ! isset( $competition_rounds[ $competition_id ] ) ) {
			$row                                   = $this->get_row( (int) $competition_id );
			$competition_rounds[ $competition_id ] = $row ? ( json_decode( (string) ( $row['stage_rounds'] ?? '' ) ) ?: [] ) : [];
		}

		if ( ! empty( $competition_rounds[ $competition_id ] ) && is_array( $competition_rounds[ $competition_id ] ) ) {
			foreach ( $competition_rounds[ $competition_id ] as $round ) {
				if ( intval( $round_id ) === intval( $round->id ) && ! empty( $round->title ) ) {
					$title = trim( $round->title );
					break;
				}
			}
		}

		return $title;
	}

	/**
	 * Returns MatchWeek or round title.
	 *
	 * @param int    $match_week
	 * @param int    $competition_id
	 * @param string $before
	 *
	 * @return string
	 * @since 0.10.0
	 */
	public function tmpl_get_matchweek_round_text( $match_week, $competition_id, $before = '' ) {
		$output = '';

		if ( empty( $competition_id ) ) {
			return $output;
		}

		$competition_type = anwp_fl()->competition->get_competition_data( $competition_id )['type'] ?? '';

		if ( 'round-robin' === $competition_type ) {
			$output = anwp_fl()->options->get_text_matchweek( $match_week );
		} elseif ( 'knockout' === $competition_type ) {

			// Backward compatibility: when round is not set use first
			$match_week = $match_week < 2 ? 1 : $match_week;

			$output = $this->get_round_title( $competition_id, $match_week );
		}

		if ( $output && $before ) {
			$output = $before . $output;
		}

		return $output;
	}

	/**
	 * Get number of matches for competitions in query
	 *
	 * @since 0.16.6
	 * @return array
	 */
	public function get_column_matches_qty(): array {

		static $output = null;

		if ( null === $output ) {
			global $wpdb, $wp_query;

			$include_ids          = wp_list_pluck( $wp_query->posts, 'ID' );
			$include_placeholders = array_fill( 0, count( $include_ids ), '%s' );
			$include_format       = implode( ', ', $include_placeholders );

			$output = $wpdb->get_results(
				$wpdb->prepare( "SELECT main_stage_id, COUNT(*) as qty, SUM( finished ) as finished FROM $wpdb->anwpfl_matches WHERE main_stage_id IN ({$include_format}) GROUP BY main_stage_id", $include_ids ), // phpcs:ignore
				OBJECT_K
			) ?: [];

			$output = array_map(
				function ( $obj ) {
					return get_object_vars( $obj );
				},
				$output
			);
		}

		return $output;
	}

	/**
	 * Get competition title.
	 *
	 * @param int $post_id
	 *
	 * @since 0.12.3
	 * @return string
	 */
	public function get_competition_title( int $post_id ): string {
		$competition_data = anwp_fl()->competition->get_competition_data( $post_id );

		if ( empty( $competition_data['id'] ) ) {
			return '';
		}

		if ( 'secondary' !== $competition_data['multistage'] ) {
			return ltrim( anwp_fl()->competition->get_competition_data( $post_id )['title_full'] ?? '', ' -' );
		}

		$root_competition_data = anwp_fl()->competition->get_competition_data( $competition_data['multistage_main'] );

		$root_title = $root_competition_data['title'];

		if ( str_contains( $root_title, $root_competition_data['stage_title'] ) ) {
			$root_title = ltrim( str_replace( $root_competition_data['stage_title'], '', $root_title ), ' -' );
		}

		return $root_title . ' - ' . $competition_data['stage_title'];
	}

	/**
	 * Get list of competitions.
	 *
	 * @return array
	 * @since 0.12.3
	 */
	public function get_competition_list( $args = [] ) {

		$args = (object) wp_parse_args(
			$args,
			[
				'league_ids'  => '',
				'season_ids'  => '',
				'include_ids' => '',
				'exclude_ids' => '',
				'group_by'    => '',
				'display'     => '',
				'show_logo'   => 'yes',
				'show_flag'   => 'big',
			]
		);

		$filtered_competitions = [];

		$include_ids = $args->include_ids ? wp_parse_id_list( $args->include_ids ) : [];
		$exclude_ids = $args->exclude_ids ? wp_parse_id_list( $args->exclude_ids ) : [];
		$league_ids  = $args->league_ids ? wp_parse_id_list( $args->league_ids ) : [];
		$season_ids  = $args->season_ids ? wp_parse_id_list( $args->season_ids ) : [];

		$show_logo = AnWP_Football_Leagues::string_to_bool( $args->show_logo );

		foreach ( anwp_fl()->competition->get_competitions_data() as $competition_data ) {

			if ( 'secondary' === $competition_data['multistage'] ) {
				continue;
			}

			if ( ! empty( $include_ids ) && ! in_array( absint( $competition_data['id'] ), $include_ids, true ) ) {
				continue;
			}

			if ( ! empty( $exclude_ids ) && in_array( absint( $competition_data['id'] ), $exclude_ids, true ) ) {
				continue;
			}

			if ( ! empty( $league_ids ) && ! in_array( absint( $competition_data['league_id'] ), $league_ids, true ) ) {
				continue;
			}

			if ( ! empty( $season_ids ) ) {
				$competition_season_ids = wp_parse_id_list( $competition_data['season_ids'] );
				if ( empty( array_intersect( $season_ids, $competition_season_ids ) ) ) {
					continue;
				}
			}

			$filtered_competitions[] = [
				'id'      => $competition_data['id'],
				'link'    => '',
				'title'   => $competition_data['title'],
				'country' => anwp_fl()->league->get_league_country_code( $competition_data['league_id'] ),
				'league'  => $competition_data['league_text'],
				'logo'    => $show_logo ? $competition_data['logo'] : '',
				'season'  => $competition_data['season_text'],
			];
		}

		/*
		|--------------------------------------------------------------------
		| Sorting
		|--------------------------------------------------------------------
		*/
		$competitions = wp_list_sort( $filtered_competitions, 'title' );

		if ( in_array( $args->display, [ 'league', 'league_season' ], true ) ) {
			$competitions = wp_list_sort( $competitions, 'league' );
		}

		/*
		|--------------------------------------------------------------------
		| Get Links
		|--------------------------------------------------------------------
		*/
		$competition_ids = wp_list_pluck( $competitions, 'id' );
		$links           = $this->plugin->helper->get_permalinks_by_ids( $competition_ids, 'anwp_competition' );

		foreach ( $competitions as $competition_index => $competition_post_obj ) {
			$competitions[ $competition_index ]['link'] = $links[ $competition_post_obj['id'] ] ?? '';
		}

		/*
		|--------------------------------------------------------------------
		| Grouping
		|--------------------------------------------------------------------
		*/
		if ( $args->group_by ) {

			$countries = [];
			foreach ( array_unique( wp_list_pluck( $competitions, 'country' ) ) as $country_item ) {
				$countries[ anwp_football_leagues()->data->get_value_by_key( $country_item, 'country' ) ? : $country_item ] = [
					'country_code' => $country_item,
					'country_name' => anwp_football_leagues()->data->get_value_by_key( $country_item, 'country' ),
				];
			}

			ksort( $countries );

			foreach ( $countries as $country ) {

				$country_items = [];

				foreach ( $competitions as $competition ) {
					if ( $competition['country'] !== $country['country_code'] ) {
						continue;
					}

					$country_items[] = $competition;
				}

				$output[] = [
					'country_code' => $country['country_code'],
					'country_name' => $country['country_name'],
					'items'        => $country_items,
				];
			}
		} else {
			$output = [
				[
					'country_code' => '',
					'country_name' => '',
					'items'        => $competitions,
				],
			];
		}

		return $output ?? [];
	}

	/**
	 * Get competition data.
	 *
	 * @param $id
	 *
	 * @since 0.13.0
	 * @return object|bool
	 */
	public function get_competition( $id ) {
		$competition_obj = array_values( wp_list_filter( $this->get_competitions(), [ 'id' => absint( $id ) ] ) );

		return empty( $competition_obj ) ? false : $competition_obj[0];
	}

	/**
	 * Get competition data (single row, per-request cached).
	 *
	 * Decoupled in 0.18.0: reads via get_competition_list_row() instead of
	 * loading all competitions. The c_index field (previously included) is
	 * no longer returned - it's only meaningful for the bulk-ordered list
	 * and no callers consumed it from this single-row method.
	 *
	 * @param int $competition_id
	 *
	 * @since 0.16.7
	 * @return array{
	 *     id: int,
	 *     title: string,
	 *     title_full: string,
	 *     type: string,
	 *     season_ids: string, // "14,13"
	 *     league_id: int,
	 *     league_text: string,
	 *     season_text: string,
	 *     multistage: string, // (empty)|main|secondary
	 *     multistage_main: int,
	 *     competition_order: int,
	 *     stage_title: string,
	 *     stage_order: int,
	 *     logo: string,
	 * }
	 */
	public function get_competition_data( int $competition_id ): array {

		if ( empty( $competition_id ) ) {
			return [];
		}

		$row = $this->get_competition_list_row( $competition_id );

		if ( ! $row ) {
			return [];
		}

		return $this->format_competition_data( $row );
	}

	/**
	 * Map a raw competitions-table row to the legacy get_competition_data() shape.
	 *
	 * Strips comma padding from season_ids/season_text, builds title_full,
	 * and casts integer columns. Logo inheritance for secondary stages is
	 * handled upstream by get_competition_list_row().
	 *
	 * @since 0.18.0
	 *
	 * @param array $row Raw row from anwpfl_competitions (or postmeta fallback shape).
	 *
	 * @return array
	 */
	private function format_competition_data( array $row ): array {
		$post_id = absint( $row['competition_id'] ?? 0 );
		$title   = (string) ( $row['title'] ?? '' );

		$entry = [
			'id'                => $post_id,
			'title'             => $title,
			'title_full'        => $title,
			'stage_title'       => (string) ( $row['stage_title'] ?? '' ),
			'stage_order'       => absint( $row['stage_order'] ?? 0 ),
			'type'              => (string) ( $row['type'] ?? '' ),
			'logo'              => (string) ( $row['logo'] ?? '' ),
			'league_id'         => absint( $row['league_id'] ?? 0 ),
			'season_ids'        => trim( (string) ( $row['season_ids'] ?? '' ), ',' ),
			'league_text'       => (string) ( $row['league_text'] ?? '' ),
			'season_text'       => trim( (string) ( $row['season_text'] ?? '' ), ',' ),
			'multistage'        => (string) ( $row['multistage'] ?? '' ),
			'multistage_main'   => absint( $row['multistage_main'] ?? 0 ),
			'competition_order' => absint( $row['competition_order'] ?? 0 ),
		];

		if ( '' !== $entry['multistage'] && '' !== $entry['stage_title'] && ! str_contains( $entry['title_full'], $entry['stage_title'] ) ) {
			$entry['title_full'] .= ' - ' . $entry['stage_title'];
		}

		/**
		 * Filter the formatted competition data array.
		 *
		 * Premium hooks this to merge its row fields (is_lazy, bracket, matchweek_current,
		 * bracket_options, bracket_layout_active, matchweeks_as_slides, competition_roles)
		 * into the output shape, so Phase 7.3 callers of get_competition_data() gain
		 * premium field access without per-file changes.
		 *
		 * @since 0.18.0
		 *
		 * @param array $entry Formatted entry array.
		 * @param array $row   Raw row from anwpfl_competitions (or postmeta fallback shape).
		 */
		return apply_filters( 'anwpfl/competition/format_data', $entry, $row );
	}

	/**
	 * Get competition data (extended with groups and rounds).
	 *
	 * @param int|null $competition_id
	 *
	 * @since 0.16.16
	 *
	 * @return array{
	 *     id: int,
	 *     title: string,
	 *     title_full: string,
	 *     type: string,
	 *     season_ids: string, // "14,13"
	 *     league_id: int,
	 *     league_text: string,
	 *     season_text: string,
	 *     multistage: string, // (empty)|main|secondary
	 *     multistage_main: int,
	 *     competition_order: int,
	 *     stage_title: string,
	 *     stage_order: int,
	 *     logo: string,
	 *     groups: array,
	 *     rounds: array,
	 * }
	 */
	public function get_competition_data_full( ?int $competition_id ): array {
		if ( empty( $competition_id ) ) {
			return [];
		}

		$row = $this->get_row( $competition_id );

		if ( ! $row ) {
			return [];
		}

		// Apply logo inheritance for secondary stages (get_row() does not, get_competition_list_row() does).
		$row = $this->maybe_inherit_logo( $row );

		$competition_data = $this->format_competition_data( $row );

		$competition_data['groups'] = json_decode( (string) ( $row['stage_groups'] ?? '' ) );
		$competition_data['rounds'] = json_decode( (string) ( $row['stage_rounds'] ?? '' ) );

		return $competition_data;
	}

	/*
	|--------------------------------------------------------------------------
	| Custom Table: Read Accessors
	|--------------------------------------------------------------------------
	| Per-request cache infrastructure for anwpfl_competitions table.
	| Two cache levels: $list_cache (light, 18 cols) and base $row_cache (full).
	| Postmeta fallback for mid-migration reads.
	|
	| @since 0.18.0
	*/

	/**
	 * Get a full competition row by ID.
	 *
	 * Overrides base to add postmeta fallback during migration.
	 * No negative caching - null stays uncached so next call retries.
	 *
	 * @since 0.18.0
	 *
	 * @param int $id Competition post ID.
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

		// POSTMETA FALLBACK: fires when anwpfl_competitions has no row for this ID.
		// After Phase 9 (0.18.0) this path exists for:
		//   - Site migration imports (until Phase 9.4 deferred work ships — see
		//     class-anwpfl-premium-site-migration.php api_import_batch TODO)
		//   - Rollback scenarios (customer manually truncates anwpfl_competitions)
		//   - Very old unmigrated installs (shouldn't exist post-0.17.2)
		//
		// Normal save paths (admin edit, clone, API import, update_current_matchweek)
		// always populate the row, so fallback does NOT fire for actively-saved
		// competitions. invalidate_cache() only flushes per-request cache — it
		// does NOT delete the row, so Phase 9's dual-write removal is safe.
		$row = $this->get_competition_row_from_postmeta( $id );

		// Cache null result too — prevents re-querying for non-existent IDs
		// within the same request (admin hooks speculatively call get_row()
		// for every post ID in the list).
		$this->cache_row( $id, $row );

		return $row;
	}

	/**
	 * Build a competition row from post + postmeta + taxonomy terms.
	 *
	 * Migration safety net: returns an array with the same keys
	 * as a real anwpfl_competitions row. Used by get_row() and warm methods
	 * when a competition hasn't been migrated to the custom table yet.
	 *
	 * Premium columns (is_lazy, bracket, etc.) are NOT included here -
	 * they are added via the anwpfl/competition/postmeta_row filter in Phase 4.
	 *
	 * @since 0.18.0
	 *
	 * @param int $competition_id Competition post ID.
	 *
	 * @return array|null Row-shaped array or null if post doesn't exist.
	 */
	public function get_competition_row_from_postmeta( int $competition_id ): ?array {
		$post = get_post( $competition_id );

		if ( ! $post || 'anwp_competition' !== $post->post_type ) {
			return null;
		}

		$meta = get_post_meta( $competition_id );

		$m = function ( $key, $default = '' ) use ( $meta ) {
			return isset( $meta[ $key ][0] ) ? $meta[ $key ][0] : $default;
		};

		// Resolve league from anwp_league taxonomy.
		$league_id   = 0;
		$league_text = '';
		$league_terms = get_the_terms( $competition_id, 'anwp_league' );

		if ( ! empty( $league_terms ) && ! is_wp_error( $league_terms ) ) {
			$league_id   = (int) $league_terms[0]->term_id;
			$league_text = $league_terms[0]->name;
		}

		// Resolve seasons from anwp_season taxonomy (comma-padded format).
		$season_ids  = '';
		$season_text = '';
		$season_terms = get_the_terms( $competition_id, 'anwp_season' );

		if ( ! empty( $season_terms ) && ! is_wp_error( $season_terms ) ) {
			$ids   = wp_list_pluck( $season_terms, 'term_id' );
			$names = wp_list_pluck( $season_terms, 'name' );

			$season_ids  = ',' . implode( ',', array_map( 'absint', $ids ) ) . ',';
			$season_text = ',' . implode( ',', $names ) . ',';
		}

		$row = [
			'competition_id'     => $competition_id,
			'title'              => $post->post_title,
			'post_name'          => $post->post_name,
			'league_id'          => $league_id,
			'league_text'        => $league_text,
			'season_ids'         => $season_ids,
			'season_text'        => $season_text,
			'type'               => $m( '_anwpfl_type' ),
			'format_robin'       => $m( '_anwpfl_format_robin' ),
			'format_knockout'    => $m( '_anwpfl_format_knockout' ),
			'is_friendly'        => 'friendly' === $m( '_anwpfl_competition_status' ) ? 1 : 0,
			'multistage'         => $m( '_anwpfl_multistage' ),
			'multistage_main'    => absint( $m( '_anwpfl_multistage_main' ) ),
			'stage_title'        => $m( '_anwpfl_stage_title' ),
			'stage_order'        => absint( $m( '_anwpfl_stage_order' ) ),
			'competition_order'  => absint( $m( '_anwpfl_competition_order' ) ),
			'logo'               => $m( '_anwpfl_logo' ),
			'logo_id'            => absint( $m( '_anwpfl_logo_id' ) ),
			'logo_big'           => $m( '_anwpfl_logo_big' ),
			'logo_big_id'        => absint( $m( '_anwpfl_logo_big_id' ) ),
			'tmpl_layout'        => $m( '_anwpfl_tmpl_layout' ),
			'group_next_id'      => absint( $m( '_anwpfl_group_next_id' ) ),
			'round_next_id'      => absint( $m( '_anwpfl_round_next_id' ) ),
			'stage_groups'       => $m( '_anwpfl_groups' ),
			'stage_rounds'       => $m( '_anwpfl_rounds' ),
			'custom_content_below' => $m( '_anwpfl_custom_content_below' ),
		];

		/**
		 * Filter the postmeta-built competition row.
		 *
		 * Used by Premium to add its columns (is_lazy, bracket, etc.)
		 * during the migration period.
		 *
		 * @since 0.18.0
		 *
		 * @param array $row             Row-shaped array.
		 * @param int   $competition_id  Competition post ID.
		 * @param array $meta            Raw postmeta array.
		 */
		$row = apply_filters( 'anwpfl/competition/postmeta_row', $row, $competition_id, $meta );

		return $row;
	}

	/**
	 * Sync a competition's postmeta to the custom table.
	 *
	 * Generic utility: given a competition post ID whose `_anwpfl_*` postmeta
	 * values are the authoritative source, build a table row from that postmeta
	 * (via `get_competition_row_from_postmeta()` so the premium filter populates
	 * premium columns too) and upsert into `anwpfl_competitions`.
	 *
	 * Accepts an exclusion list so callers can drop columns that don't make
	 * sense in their context.
	 *
	 * Intentionally SKIPS the `anwpfl_competitions_migrated` gate so callers on
	 * a fresh install (where the Toolbox Updater hasn't run yet) can still
	 * populate the row directly.
	 *
	 * @internal No in-tree callers as of 0.18.0. Site migration switched to a
	 *   direct table-to-table import in the same release, so this method is
	 *   retained only as a reusable batch-repair / WP-CLI utility. If it stays
	 *   uncalled after a release cycle, consider removing.
	 *
	 * @since 0.18.0
	 *
	 * @param int      $post_id         Competition post ID.
	 * @param string[] $exclude_columns Column names to drop before upsert.
	 *
	 * @return bool True on success, false if post doesn't exist or upsert failed.
	 */
	public function sync_from_postmeta( int $post_id, array $exclude_columns = [] ): bool {
		$row = $this->get_competition_row_from_postmeta( $post_id );

		if ( ! $row ) {
			return false;
		}

		foreach ( $exclude_columns as $col ) {
			unset( $row[ $col ] );
		}

		$update_columns = array_diff( array_keys( $row ), [ 'competition_id' ] );
		$result         = $this->upsert( $post_id, $row, $update_columns );

		$this->invalidate_cache( $post_id );
		unset( $this->list_cache[ $post_id ] );

		return false !== $result;
	}

	/**
	 * Bulk warm light competition data for list views.
	 *
	 * Fetches 17 core display columns (extensible via filter) into $list_cache.
	 * Falls back to postmeta for IDs not yet in the custom table (mid-migration).
	 *
	 * Premium extends the column list via the `anwpfl/competition/warm_light_columns`
	 * filter - keep only small scalar columns here. Heavy TEXT blobs (stage_groups,
	 * stage_rounds, bracket_options, competition_roles) must be fetched via get_row().
	 *
	 * @since 0.18.0
	 *
	 * @param int[] $ids Competition post IDs.
	 */
	public function warm_competitions( array $ids ): void {
		$ids = array_unique( array_filter( array_map( 'absint', $ids ) ) );

		// Filter out IDs already in list_cache OR row_cache. A full row in row_cache
		// is a superset of light columns; mirror it into list_cache to avoid a
		// redundant SELECT when a caller already did get_row() / get_competition_data_full().
		$missing = [];

		foreach ( $ids as $id ) {
			if ( isset( $this->list_cache[ $id ] ) ) {
				continue;
			}

			$cached_row = $this->get_cached_row( $id );

			if ( null !== $cached_row ) {
				$this->list_cache[ $id ] = $cached_row;
				continue;
			}

			$missing[] = $id;
		}

		if ( empty( $missing ) ) {
			return;
		}

		/**
		 * Filter the columns fetched by warm_competitions() for list views.
		 *
		 * Premium adds its small scalar columns (matchweek_current, is_lazy, bracket,
		 * bracket_layout_active, matchweeks_as_slides) here. Heavy TEXT blobs
		 * (bracket_options, competition_roles) must NOT be added - they are read via
		 * get_row() on single-competition pages.
		 *
		 * @since 0.18.0
		 *
		 * @param string[] $columns List of column names to SELECT.
		 */
		$light_columns = apply_filters(
			'anwpfl/competition/warm_light_columns',
			[
				'title',
				'post_name',
				'league_id',
				'league_text',
				'season_ids',
				'season_text',
				'type',
				'is_friendly',
				'multistage',
				'multistage_main',
				'stage_title',
				'stage_order',
				'competition_order',
				'logo',
				'logo_id',
				'logo_big',
				'logo_big_id',
			]
		);

		$rows = $this->fetch_rows(
			$missing,
			implode( ', ', array_map( 'sanitize_key', $light_columns ) )
		);

		foreach ( $rows as $row ) {
			$this->list_cache[ (int) $row['competition_id'] ] = $row;
		}

		// Fallback: postmeta for IDs not in the table (mid-migration).
		$still_missing = array_diff( $missing, array_keys( $rows ) );

		if ( ! empty( $still_missing ) ) {
			update_meta_cache( 'post', $still_missing );

			foreach ( $still_missing as $competition_id ) {
				$row = $this->get_competition_row_from_postmeta( $competition_id );

				if ( $row ) {
					$this->list_cache[ $competition_id ] = $row;
				}
			}
		}
	}

	/**
	 * Bulk warm full competition data (all columns including JSON blobs).
	 *
	 * Populates both base $row_cache and $list_cache.
	 *
	 * @since 0.18.0
	 *
	 * @param int[] $ids Competition post IDs.
	 */
	public function warm_competitions_full( array $ids ): void {
		$ids = array_unique( array_filter( array_map( 'absint', $ids ) ) );

		if ( empty( $ids ) ) {
			return;
		}

		$rows = $this->fetch_rows( $ids );

		foreach ( $rows as $row ) {
			$competition_id = (int) $row['competition_id'];
			$this->cache_row( $competition_id, $row );
			$this->list_cache[ $competition_id ] = $row;
		}

		// Fallback: postmeta for IDs not in the table (mid-migration).
		$still_missing = array_diff( $ids, array_keys( $rows ) );

		if ( ! empty( $still_missing ) ) {
			update_meta_cache( 'post', $still_missing );

			foreach ( $still_missing as $competition_id ) {
				$row = $this->get_competition_row_from_postmeta( $competition_id );

				if ( $row ) {
					$this->cache_row( $competition_id, $row );
					$this->list_cache[ $competition_id ] = $row;
				}
			}
		}
	}

	/**
	 * Get light competition row from list cache or base row cache.
	 *
	 * Cascade: list_cache -> row_cache -> auto-fetch via get_row().
	 * Logo inheritance: if multistage=secondary and logo is empty,
	 * fetches parent logo from the main stage.
	 *
	 * @since 0.18.0
	 *
	 * @param int $id Competition post ID.
	 *
	 * @return array|null
	 */
	public function get_competition_list_row( int $id ): ?array {
		$row = $this->list_cache[ $id ] ?? $this->get_cached_row( $id );

		if ( null !== $row ) {
			return $this->maybe_inherit_logo( $row );
		}

		// Auto-fetch on cache miss (single competition access without prior warm).
		$row = $this->get_row( $id );

		if ( $row ) {
			$this->list_cache[ $id ] = $row;
		}

		return $row ? $this->maybe_inherit_logo( $row ) : null;
	}

	/**
	 * Inherit logo from main stage for secondary stages with no logo.
	 *
	 * @since 0.18.0
	 *
	 * @param array $row Competition row.
	 *
	 * @return array Row with inherited logo if applicable.
	 */
	private function maybe_inherit_logo( array $row ): array {
		if ( 'secondary' !== ( $row['multistage'] ?? '' ) || ! empty( $row['logo'] ) ) {
			return $row;
		}

		$main_id = (int) ( $row['multistage_main'] ?? 0 );

		if ( ! $main_id ) {
			return $row;
		}

		// Try list_cache first, then full fetch.
		$parent = $this->list_cache[ $main_id ] ?? null;

		if ( null === $parent ) {
			$parent = $this->get_row( $main_id );
		}

		if ( $parent && ! empty( $parent['logo'] ) ) {
			$row['logo']    = $parent['logo'];
			$row['logo_id'] = $parent['logo_id'] ?? 0;
		}

		return $row;
	}

	/*
	|--------------------------------------------------------------------------
	| Custom Table: Write Hooks
	|--------------------------------------------------------------------------
	| Sync from wp_posts/postmeta to anwpfl_competitions table.
	|
	| @since 0.18.0
	*/

	/**
	 * Sync competition title and post_name to custom table after post save.
	 *
	 * Hooked on save_post_anwp_competition at priority 999 (after CMB2 saves).
	 * Fallback for non-admin save paths (REST, programmatic wp_insert_post)
	 * that don't fire anwpfl/competition-stage/after_save.
	 * Ensures the row exists with at least title/post_name.
	 *
	 * @since 0.18.0
	 *
	 * @param int $post_id Competition post ID.
	 */
	public function sync_competition_to_table( int $post_id ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post || 'anwp_competition' !== $post->post_type ) {
			return;
		}

		$this->upsert(
			$post_id,
			[
				'competition_id' => $post_id,
				'title'          => $post->post_title,
				'post_name'      => $post->post_name,
			],
			[ 'title', 'post_name' ]
		);

		unset( $this->list_cache[ $post_id ] );
	}

	/**
	 * Full sync of one competition stage to the custom table.
	 *
	 * Hooked on anwpfl/competition-stage/after_save.
	 *
	 * Phase 9 rewrite: sources Vue-managed fields from $stage_data and
	 * $_POST-managed fields (logo) from $post_data. The 5 CMB2-managed
	 * fields (competition_order, tmpl_layout, custom_content_below,
	 * logo_big, logo_big_id) continue reading from postmeta because
	 * CMB2 writes them on save_post priority 10 BEFORE this action
	 * fires (save_post_anwp_competition is the typed variant, which
	 * WordPress dispatches after the generic save_post).
	 *
	 * Premium columns are written by Premium's own hook on the same action
	 * at a later priority.
	 *
	 * @since 0.18.0
	 *
	 * @param int          $stage_id        Stage (competition) post ID.
	 * @param object|array $stage_data      Vue form data for this stage.
	 * @param array        $post_data       Raw $_POST data.
	 * @param int          $parent_post_id  Parent (root) post ID. Needed to
	 *                                      populate multistage_main for
	 *                                      secondary stages. Defaults to 0
	 *                                      for backward compatibility with
	 *                                      any third-party callers.
	 */
	public function sync_stage_to_table( int $stage_id, $stage_data, array $post_data, int $parent_post_id = 0 ): void {
		// Migration gate: block writes until the custom table is populated
		// (matches the gate at the top of save_metabox()).
		if ( ! get_option( 'anwpfl_competitions_migrated' ) ) {
			return;
		}

		$post = get_post( $stage_id );

		if ( ! $post || 'anwp_competition' !== $post->post_type ) {
			return;
		}

		// Normalize $stage_data to array access. save_metabox passes an array
		// (via wp_parse_args); third-party callers might pass an object.
		$stage_data = is_object( $stage_data ) ? (array) $stage_data : $stage_data;
		$is_root    = ! empty( $stage_data['root'] );

		// Resolve league from taxonomy.
		$league_id    = 0;
		$league_text  = '';
		$league_terms = get_the_terms( $stage_id, 'anwp_league' );

		if ( ! empty( $league_terms ) && ! is_wp_error( $league_terms ) ) {
			$league_id   = (int) $league_terms[0]->term_id;
			$league_text = $league_terms[0]->name;
		}

		// Resolve seasons from taxonomy (comma-padded format).
		$season_ids   = '';
		$season_text  = '';
		$season_terms = get_the_terms( $stage_id, 'anwp_season' );

		if ( ! empty( $season_terms ) && ! is_wp_error( $season_terms ) ) {
			$ids   = wp_list_pluck( $season_terms, 'term_id' );
			$names = wp_list_pluck( $season_terms, 'name' );

			$season_ids  = ',' . implode( ',', array_map( 'absint', $ids ) ) . ',';
			$season_text = ',' . implode( ',', $names ) . ',';
		}

		// Rebuild groups/rounds JSON from $stage_data->rounds. Same logic
		// as the pre-Phase-9 save_metabox L764-774 block.
		$rounds_out = [];
		$groups_out = [];

		if ( ! empty( $stage_data['rounds'] ) && is_array( $stage_data['rounds'] ) ) {
			foreach ( $stage_data['rounds'] as $round_data ) {
				$rounds_out[] = [
					'id'    => $round_data->id ?? 0,
					'title' => $round_data->title ?? '',
				];

				if ( ! empty( $round_data->groups ) ) {
					$groups_out = array_merge( $groups_out, $round_data->groups );
				}
			}
		}

		// Derive multistage from $stage_data context. Previously at
		// save_metabox L790 via update_post_meta('_anwpfl_multistage', ...).
		// Needs the full $stages_data count to disambiguate 'main' vs '' when
		// root with single stage. Passed via $post_data['_fl_stages_data'] JSON.
		$stages_count = 1;

		if ( isset( $post_data['_fl_stages_data'] ) ) {
			$decoded      = json_decode( (string) $post_data['_fl_stages_data'] );
			$stages_count = is_array( $decoded ) ? count( $decoded ) : 1;
		}

		$multistage      = $is_root ? ( $stages_count > 1 ? 'main' : '' ) : 'secondary';
		$multistage_main = $is_root ? 0 : absint( $parent_post_id );

		$competition_order = absint( $post_data['_anwpfl_competition_order'] ?? 0 );

		$data = [
			'competition_id'       => $stage_id,
			'title'                => $post->post_title,
			'post_name'            => $post->post_name,
			'league_id'            => $league_id,
			'league_text'          => $league_text,
			'season_ids'           => $season_ids,
			'season_text'          => $season_text,

			// Vue-sourced (Phase 9):
			'type'                 => sanitize_key( $stage_data['type'] ?? '' ),
			'format_robin'         => sanitize_key( $stage_data['formatRobin'] ?? '' ),
			'format_knockout'      => sanitize_key( $stage_data['formatKnockout'] ?? '' ),
			'is_friendly'          => ! empty( $stage_data['isFriendly'] ) ? 1 : 0,
			'stage_title'          => sanitize_text_field( $stage_data['stageTitle'] ?? '' ),
			'stage_order'          => absint( $stage_data['order'] ?? 0 ),
			'group_next_id'        => absint( $stage_data['nextIdGroup'] ?? 0 ),
			'round_next_id'        => absint( $stage_data['nextIdRound'] ?? 0 ),
			'multistage'           => $multistage,
			'multistage_main'      => $multistage_main,
			'stage_groups'         => wp_json_encode( $groups_out ),
			'stage_rounds'         => wp_json_encode( $rounds_out ),

			// $_POST-sourced (Phase 9 + Phase 12):
			'logo'                 => sanitize_text_field( $post_data['_anwpfl_logo'] ?? '' ),
			'logo_id'              => absint( $post_data['_anwpfl_logo_id'] ?? 0 ),
			'competition_order'    => $competition_order,
			'logo_big'             => sanitize_text_field( $post_data['_anwpfl_logo_big'] ?? '' ),
			'logo_big_id'          => absint( $post_data['_anwpfl_logo_big_id'] ?? 0 ),
			'tmpl_layout'          => sanitize_key( $post_data['_anwpfl_tmpl_layout'] ?? '' ),
			'custom_content_below' => wp_kses_post( $post_data['anwp_custom_content_below'] ?? '' ),
		];

		$update_columns = array_diff( array_keys( $data ), [ 'competition_id' ] );

		// Root-only fields: exclude from secondary stage updates to prevent
		// root values bleeding into secondaries via shared $post_data (Phase 12).
		// competition_order is cascaded separately via raw SQL below.
		if ( ! $is_root ) {
			$update_columns = array_diff( $update_columns, [
				'competition_order',
				'tmpl_layout',
				'logo_big',
				'logo_big_id',
				'custom_content_below',
			] );
		}

		$this->upsert( $stage_id, $data, $update_columns );
		$this->invalidate_cache( $stage_id );
		unset( $this->list_cache[ $stage_id ] );

		// Cascade competition_order to all secondary stages (including 0 to handle clearing).
		if ( $is_root ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$this->table_name} SET competition_order = %d WHERE multistage_main = %d",
					$competition_order,
					$stage_id
				)
			);
		}
	}

	/**
	 * Remove competition row from custom table on permanent delete.
	 *
	 * Hooked on delete_post (fires before post is removed from DB).
	 * Trashed/drafted competitions keep their row - only permanent deletes remove it.
	 * Also cascades: deletes rows for secondary stages of a main competition.
	 *
	 * @since 0.18.0
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_competition_delete( int $post_id ): void {
		if ( 'anwp_competition' !== get_post_type( $post_id ) ) {
			return;
		}

		// Delete secondary stage rows if this is a main stage.
		global $wpdb;

		$secondary_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT competition_id FROM {$wpdb->anwpfl_competitions} WHERE multistage_main = %d",
				$post_id
			)
		);

		foreach ( $secondary_ids as $secondary_id ) {
			// wp_delete_post triggers this same hook recursively for each
			// secondary, which handles $this->delete() + list_cache cleanup.
			wp_delete_post( (int) $secondary_id, true );
		}

		$this->delete( $post_id );
		unset( $this->list_cache[ $post_id ] );
	}

	/**
	 * Sync league/season taxonomy changes into the custom table.
	 *
	 * Hooked on `set_object_terms`. Fires when any taxonomy is assigned to any
	 * object, so filters aggressively: skip unless this is an anwp_competition
	 * post AND the taxonomy is anwp_league / anwp_season, AND we're NOT already
	 * inside a save_metabox() run (ANWPFL_SAVING_COMPETITION is defined there
	 * before wp_set_object_terms() fires; the authoritative sync_stage_to_table
	 * write later in the same request would just overwrite our work).
	 *
	 * @since 0.18.0
	 *
	 * @param int    $object_id Object (post) ID.
	 * @param array  $terms     Term IDs/slugs passed to wp_set_object_terms().
	 * @param array  $tt_ids    Term taxonomy IDs that were set.
	 * @param string $taxonomy  Taxonomy name.
	 */
	public function sync_taxonomy_to_table( int $object_id, array $terms, array $tt_ids, string $taxonomy ): void {

		if ( 'anwp_league' !== $taxonomy && 'anwp_season' !== $taxonomy ) {
			return;
		}

		if ( defined( 'ANWPFL_SAVING_COMPETITION' ) && ANWPFL_SAVING_COMPETITION ) {
			return;
		}

		if ( 'anwp_competition' !== get_post_type( $object_id ) ) {
			return;
		}

		// Migration gate: block writes until the custom table is populated.
		if ( ! get_option( 'anwpfl_competitions_migrated' ) ) {
			return;
		}

		if ( 'anwp_league' === $taxonomy ) {
			$league_id    = 0;
			$league_text  = '';
			$league_terms = get_the_terms( $object_id, 'anwp_league' );

			if ( ! empty( $league_terms ) && ! is_wp_error( $league_terms ) ) {
				$league_id   = (int) $league_terms[0]->term_id;
				$league_text = $league_terms[0]->name;
			}

			$this->upsert(
				$object_id,
				[
					'competition_id' => $object_id,
					'league_id'      => $league_id,
					'league_text'    => $league_text,
				],
				[ 'league_id', 'league_text' ]
			);
		} else {
			$season_ids   = '';
			$season_text  = '';
			$season_terms = get_the_terms( $object_id, 'anwp_season' );

			if ( ! empty( $season_terms ) && ! is_wp_error( $season_terms ) ) {
				$ids   = wp_list_pluck( $season_terms, 'term_id' );
				$names = wp_list_pluck( $season_terms, 'name' );

				$season_ids  = ',' . implode( ',', array_map( 'absint', $ids ) ) . ',';
				$season_text = ',' . implode( ',', $names ) . ',';
			}

			$this->upsert(
				$object_id,
				[
					'competition_id' => $object_id,
					'season_ids'     => $season_ids,
					'season_text'    => $season_text,
				],
				[ 'season_ids', 'season_text' ]
			);
		}

		$this->invalidate_cache( $object_id );
		unset( $this->list_cache[ $object_id ] );
	}

	/**
	 * Sync league/season term renames to the custom table's text columns.
	 *
	 * Hooked on `edited_term`. Fires for every taxonomy edit site-wide, so we
	 * filter to anwp_league / anwp_season up front.
	 *
	 * For `anwp_league` the update is a single UPDATE keyed on `league_id` (one
	 * league per competition). For `anwp_season` we rebuild the comma-padded
	 * `season_text` column per affected row via `get_the_terms()` so we don't
	 * have to track the pre-rename name in a stash. The volume here is tiny
	 * (admin renaming a term is rare), so N+1 is acceptable.
	 *
	 * Follows the Catalog #12 pattern: collect affected IDs first, run the
	 * raw UPDATE, then invalidate per-request caches so downstream reads in
	 * the same request see the new value.
	 *
	 * @since 0.18.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy name.
	 */
	public function sync_term_name_to_table( int $term_id, int $tt_id, string $taxonomy ): void {

		if ( 'anwp_league' !== $taxonomy && 'anwp_season' !== $taxonomy ) {
			return;
		}

		if ( ! get_option( 'anwpfl_competitions_migrated' ) ) {
			return;
		}

		$term = get_term( $term_id, $taxonomy );

		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		global $wpdb;

		if ( 'anwp_league' === $taxonomy ) {
			$affected_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT competition_id FROM {$wpdb->anwpfl_competitions} WHERE league_id = %d",
					$term_id
				)
			);

			if ( empty( $affected_ids ) ) {
				return;
			}

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->anwpfl_competitions} SET league_text = %s WHERE league_id = %d",
					$term->name,
					$term_id
				)
			);
		} else {
			$affected_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT competition_id FROM {$wpdb->anwpfl_competitions} WHERE season_ids LIKE %s",
					'%,' . absint( $term_id ) . ',%'
				)
			);

			if ( empty( $affected_ids ) ) {
				return;
			}

			foreach ( $affected_ids as $comp_id ) {
				$season_terms = get_the_terms( (int) $comp_id, 'anwp_season' );

				if ( empty( $season_terms ) || is_wp_error( $season_terms ) ) {
					continue;
				}

				$names       = wp_list_pluck( $season_terms, 'name' );
				$season_text = ',' . implode( ',', $names ) . ',';

				$wpdb->update(
					$wpdb->anwpfl_competitions,
					[ 'season_text' => $season_text ],
					[ 'competition_id' => (int) $comp_id ],
					[ '%s' ],
					[ '%d' ]
				);
			}
		}

		foreach ( $affected_ids as $id ) {
			$this->invalidate_cache( (int) $id );
			unset( $this->list_cache[ (int) $id ] );
		}
		$this->reset_list_cache();
	}

	/**
	 * Clear all per-request competition caches.
	 *
	 * Resets $list_cache (instance) and $row_cache (base, this table only).
	 * Called by AnWPFL_Cache when flushing competition caches.
	 *
	 * @since 0.18.0
	 */
	public function reset_list_cache(): void {
		$this->list_cache        = [];
		$this->secondaries_cache = [];
		$this->reset_cache();
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get list of competition groups without assigned standings.
	 *
	 * Zero internal callers since 0.18.0 - preserved for theme override compat.
	 *
	 * @deprecated 0.18.0
	 *
	 * @param int $competition_id
	 * @param int $group_id
	 *
	 * @return array
	 * @since 0.11.1
	 */
	public function get_competition_group_standing( $competition_id, $group_id ) {

		_deprecated_function( __METHOD__, '0.18.0' );

		$row = $this->get_row( (int) $competition_id );

		if ( ! $row ) {
			return [];
		}

		$groups = json_decode( (string) ( $row['stage_groups'] ?? '' ) );

		$c_groups = [];

		if ( ! empty( $groups ) && is_array( $groups ) ) {
			foreach ( $groups as $group ) {
				if ( absint( $group->id ?? 0 ) === absint( $group_id ) ) {
					$c_groups[] = $group;
					break;
				}
			}
		}

		$competition         = (object) $this->format_competition_data( $row );
		$competition->groups = $c_groups;

		return [ $competition ];
	}
}
