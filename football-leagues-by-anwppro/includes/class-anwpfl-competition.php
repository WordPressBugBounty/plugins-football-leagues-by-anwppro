<?php
/**
 * AnWP Football Leagues :: Competition.
 *
 * @since   0.1.0
 * @package AnWP_Football_Leagues
 */

require_once __DIR__ . '/../vendor/cpt-core/CPT_Core.php';

/**
 * AnWP Football Leagues :: Competition post type class.
 *
 * @since 0.1.0
 *
 * @see   https://github.com/WebDevStudios/CPT_Core
 */
class AnWPFL_Competition extends CPT_Core {

	/**
	 * Parent plugin class.
	 *
	 * @var AnWP_Football_Leagues
	 * @since  0.1.0
	 */
	protected $plugin = null;

	/**
	 * Mapped Data.
	 *
	 * @var AnWP_Football_Leagues
	 * @since  0.2.0
	 */
	protected $type_map;
	protected $format_robin_map;
	protected $format_knockout_map;

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
		$permalink_slug      = empty( $permalink_structure['competition'] ) ? 'competition' : $permalink_structure['competition'];

		// Register this cpt.
		parent::__construct(
			[ // array with Singular, Plural, and Registered name
				esc_html__( 'Competition', 'anwp-football-leagues' ),
				esc_html__( 'Competitions', 'anwp-football-leagues' ),
				'anwp_competition',
			],
			[
				'supports'            => [
					'title',
					'comments',
				],
				'show_in_menu'        => true,
				'menu_position'       => 33,
				'menu_icon'           => $plugin::SVG_CUP,
				'show_in_rest'        => true,
				'rest_base'           => 'anwp_competitions',
				'exclude_from_search' => 'hide' === AnWPFL_Options::get_value( 'display_front_end_search_competition' ),
				'rewrite'             => [ 'slug' => $permalink_slug ],
				'public'              => true,
				'labels'              => [
					'all_items'    => esc_html__( 'All Competitions', 'anwp-football-leagues' ),
					'add_new'      => esc_html__( 'Add New Competition', 'anwp-football-leagues' ),
					'add_new_item' => esc_html__( 'Add New Competition', 'anwp-football-leagues' ),
					'edit_item'    => esc_html__( 'Edit Competition', 'anwp-football-leagues' ),
					'new_item'     => esc_html__( 'New Competition', 'anwp-football-leagues' ),
					'view_item'    => esc_html__( 'View Competition', 'anwp-football-leagues' ),
				],
				'taxonomies'          => [
					'anwp_league',
					'anwp_season',
				],
			]
		);

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
	 * Filter CPT title entry placeholder text
	 *
	 * @param  string $title Original placeholder text
	 *
	 * @return string        Modified placeholder text
	 */
	public function title( $title ) {

		$screen = get_current_screen();
		if ( isset( $screen->post_type ) && $screen->post_type === $this->post_type ) {
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

		// Init CMB2 metaboxes
		add_action( 'cmb2_admin_init', [ $this, 'init_cmb2_metaboxes' ] );
		add_action( 'cmb2_before_post_form_anwp_competition_cmb2_metabox', [ $this, 'cmb2_before_metabox' ] );
		add_action( 'cmb2_after_post_form_anwp_competition_cmb2_metabox', [ $this, 'cmb2_after_metabox' ] );

		// Render Custom Content below
		add_action(
			'anwpfl/tmpl-competition/after_wrapper',
			function ( $competition_post_id ) {

				$content_below = get_post_meta( $competition_post_id, '_anwpfl_custom_content_below', true );

				if ( trim( $content_below ) ) {
					echo '<div class="anwp-b-wrap mt-4">' . do_shortcode( $content_below ) . '</div>';
				}
			}
		);

		// Clone Competition
		add_filter( 'post_row_actions', [ $this, 'modify_quick_actions' ], 10, 2 );
		add_action( 'wp_ajax_fl_clone_competition', [ $this, 'process_clone_competition' ] );
		add_action( 'admin_footer-edit.php', [ $this, 'include_admin_clone_competition_modaal' ], 99 );
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

			$meta_fields_to_clone = [
				'_anwpfl_custom_content_below',
				'_anwpfl_competition_order',
				'_anwpfl_tmpl_layout',
				'_anwpfl_logo',
				'_anwpfl_logo_id',
				'_anwpfl_logo_big',
				'_anwpfl_logo_big_id',
				'_anwpfl_groups',
				'_anwpfl_rounds',
				'_anwpfl_type',
				'_anwpfl_format_robin',
				'_anwpfl_format_knockout',
				'_anwpfl_competition_status',
				'_anwpfl_multistage',
				'_anwpfl_multistage_main',
				'_anwpfl_stage_title',
				'_anwpfl_stage_order',
				'_anwpfl_group_next_id',
				'_anwpfl_round_next_id',
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
			| Clone Secondary Stages
			|--------------------------------------------------------------------
			*/

			if ( 'main' === get_post_meta( $competition_id, '_anwpfl_multistage', true ) ) {

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

						$meta_fields_to_clone = [
							'_anwpfl_custom_content_below',
							'_anwpfl_competition_order',
							'_anwpfl_tmpl_layout',
							'_anwpfl_logo',
							'_anwpfl_logo_id',
							'_anwpfl_logo_big',
							'_anwpfl_logo_big_id',
							'_anwpfl_groups',
							'_anwpfl_rounds',
							'_anwpfl_type',
							'_anwpfl_format_robin',
							'_anwpfl_format_knockout',
							'_anwpfl_competition_status',
							'_anwpfl_multistage',
							'_anwpfl_stage_title',
							'_anwpfl_stage_order',
							'_anwpfl_group_next_id',
							'_anwpfl_round_next_id',
						];

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
	public function include_admin_clone_competition_modaal() {

		// Load styles and scripts (limit to competition page)
		$current_screen = get_current_screen();

		if ( ! empty( $current_screen->id ) && 'edit-anwp_competition' === $current_screen->id ) {
			?>
			<div id="anwp-fl-competition-clone-modaal" style="display: none;">
				<div class="anwpfl-shortcode-modal__header">
					<h3 style="margin: 0"><?php echo esc_html__( 'clone Competition', 'anwp-football-leagues' ); ?></h3>
				</div>
				<div class="anwpfl-shortcode-modal__content">
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
				<div class="anwpfl-shortcode-modal__footer">
					<button id="anwp-fl-competition-clone-modaal__cancel" class="button"><?php echo esc_html__( 'Close', 'anwp-football-leagues' ); ?></button>
					<button id="anwp-fl-competition-clone-modaal__clone" class="button button-primary"><?php echo esc_html__( 'Clone', 'anwp-football-leagues' ); ?></button>
					<span class="spinner"></span>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Renders tabs for metabox. Helper HTML before.
	 *
	 * @since 0.10.0
	 */
	public function cmb2_before_metabox() {
		// @formatter:off
		ob_start();
		?>
		<div class="anwp-b-wrap">
			<div class="anwp-metabox-tabs d-sm-flex">
				<div class="anwp-metabox-tabs__controls d-flex flex-sm-column flex-wrap">
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-display-competition_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-eye"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Display', 'anwp-football-leagues' ); ?></span>
					</div>
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-bottom_content-competition_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-repo-push"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Bottom Content', 'anwp-football-leagues' ); ?></span>
					</div>
					<?php
					/**
					 * Fires in the bottom of match tabs.
					 *
					 * @since 0.9.0
					 */
					do_action( 'anwpfl/cmb2_tabs_control/competition' );
					?>
				</div>
				<div class="anwp-metabox-tabs__content pl-4 pb-4">
		<?php
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		// @formatter:on
	}

	/**
	 * Renders tabs for metabox. Helper HTML after.
	 *
	 * @since 0.10.0
	 */
	public function cmb2_after_metabox() {
		// @formatter:off
		ob_start();
		?>
				</div>
			</div>
		</div>
		<?php
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		// @formatter:on
	}

	/**
	 * Define the metabox and field configurations.
	 *
	 * @since 0.3.0 (2018-02-04)
	 * @since 0.4.5 added extra seasons metabox
	 */
	public function init_cmb2_metaboxes() {

		// Start with an underscore to hide fields from custom fields list
		$prefix = '_anwpfl_';

		$cmb_side = new_cmb2_box(
			[
				'id'              => 'anwp_competition_side',
				'title'           => esc_html__( 'Big Logo', 'anwp-football-leagues' ),
				'object_types'    => [ 'anwp_competition' ],
				'context'         => 'side',
				'priority'        => 'low',
				'classes'         => 'anwp-b-wrap',
				'show_names'      => false,
				'remove_box_wrap' => true,
			]
		);

		$cmb_side->add_field(
			[
				'name'         => esc_html__( 'Logo Big', 'anwp-football-leagues' ),
				'id'           => $prefix . 'logo_big',
				'type'         => 'file',
				'query_args'   => [
					'type' => 'image',
				],
				'options'      => [
					'url' => false,
				],
				'preview_size' => 'large',
			]
		);

		/* ****
		|--------------------------------------------------------------------------
		| General Metabox
		|--------------------------------------------------------------------------
		* *** */
		$cmb = new_cmb2_box(
			[
				'id'           => 'anwp_competition_cmb2_metabox',
				'title'        => esc_html__( 'Display Options', 'anwp-football-leagues' ),
				'object_types' => [ 'anwp_competition' ],
				'priority'     => 'low',
				'classes'      => 'anwp-b-wrap',
				'show_names'   => true,
			]
		);

		/*
		|--------------------------------------------------------------------------
		| Display
		|--------------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'             => esc_html__( 'Layout', 'anwp-football-leagues' ),
				'id'               => $prefix . 'tmpl_layout',
				'type'             => 'select',
				'default'          => '',
				'description'      => '*' . esc_html__( 'Tabs - only for Multiple Stages or Rounds ', 'anwp-football-leagues' ),
				'show_option_none' => false,
				'before_row'       => '<div id="anwp-tabs-display-competition_metabox" class="anwp-metabox-tabs__content-item">',
				'options'          => [
					''     => esc_html__( 'Default', 'anwp-football-leagues' ),
					'tabs' => esc_html__( 'Tabs', 'anwp-football-leagues' ),
				],
			]
		);

		$cmb->add_field(
			[
				'name'      => esc_html__( 'Competition Order', 'anwp-football-leagues' ),
				'id'        => $prefix . 'competition_order',
				'label_cb'  => [ $this->plugin, 'cmb2_field_label' ],
				'type'      => 'text_small',
				'default'   => 0,
				'after_row' => '</div>',
			]
		);

		/*
		|--------------------------------------------------------------------------
		| Bottom Content
		|--------------------------------------------------------------------------
		*/
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
					'teeny'         => false,
					'dfw'           => false,
					'tinymce'       => true,
					'quicktags'     => true,
				],
				'show_names' => false,
				'before_row' => '<div id="anwp-tabs-bottom_content-competition_metabox" class="anwp-metabox-tabs__content-item d-none">',
				'after_row'  => '</div>',
			]
		);


		/**
		 * Adds extra fields to the metabox.
		 *
		 * @since 0.10.1
		 */
		$extra_fields = apply_filters( 'anwpfl/cmb2_tabs_content/competition', [] );

		if ( ! empty( $extra_fields ) && is_array( $extra_fields ) ) {
			foreach ( $extra_fields as $field ) {
				$cmb->add_field( $field );
			}
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

		if ( null === $output_data ) {

			/*
			|--------------------------------------------------------------------
			| Prepare Terms Data Map
			|--------------------------------------------------------------------
			*/
			global $wpdb;

			$term_data = $wpdb->get_results(
				"
					SELECT t.term_id, t.name, tr.object_id, tt.taxonomy
					FROM $wpdb->terms AS t
					INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
					INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE tt.taxonomy IN ('anwp_league', 'anwp_season')
				"
			); // phpcs:ignore WordPress.DB.PreparedSQL

			$term_data_map = [];

			if ( ! empty( $term_data ) && is_array( $term_data ) ) {
				foreach ( $term_data as $term_object_data ) {
					if ( ! isset( $term_data_map[ $term_object_data->object_id ] ) ) {
						$term_data_map[ $term_object_data->object_id ] = [];
					}

					$term_data_map[ $term_object_data->object_id ][] = $term_object_data;
				}
			}

			/*
			|--------------------------------------------------------------------
			| Competitions
			|--------------------------------------------------------------------
			*/
			$output_data      = [];
			$secondary_stages = [];

			$all_competitions = get_posts(
				[
					'numberposts'      => - 1,
					'post_type'        => 'anwp_competition',
					'suppress_filters' => false,
					'post_status'      => [ 'publish', 'stage_secondary' ],
					'orderby'          => 'title',
					'order'            => 'ASC',
					'meta_query'       => [
						[
							'key'     => '_anwpfl_multistage',
							'value'   => [ 'main', 'secondary' ],
							'compare' => 'IN',
						],
					],
				]
			);

			/** @var WP_Post $competition */
			foreach ( $all_competitions as $competition ) {

				$obj              = (object) [];
				$obj->id          = $competition->ID;
				$obj->title       = $competition->post_title;
				$obj->stage_title = get_post_meta( $competition->ID, '_anwpfl_stage_title', true );
				$obj->stage_order = get_post_meta( $competition->ID, '_anwpfl_stage_order', true );
				$obj->type        = get_post_meta( $competition->ID, '_anwpfl_type', true );
				$obj->season_ids  = [];
				$obj->league_id   = 0;
				$obj->multistage  = get_post_meta( $competition->ID, '_anwpfl_multistage', true );
				$obj->edit_link   = get_edit_post_link( $competition, '' );

				/*
				|--------------------------------------------------------------------
				| Get Season and League data
				|--------------------------------------------------------------------
				*/
				if ( ! empty( $term_data_map[ $obj->id ] ) && is_array( $term_data_map[ $obj->id ] ) ) {
					foreach ( $term_data_map[ $obj->id ] as $obj_term ) {
						if ( 'anwp_league' === $obj_term->taxonomy ) {
							$obj->league_id = (int) $obj_term->term_id;
						} elseif ( 'anwp_season' === $obj_term->taxonomy ) {
							$obj->season_ids[] = (int) $obj_term->term_id;
						}
					}
				}

				$obj->season_ids = implode( ',', $obj->season_ids );

				/*
				|--------------------------------------------------------------------
				| Prepare output
				|--------------------------------------------------------------------
				*/
				if ( 'stage_secondary' === $competition->post_status ) {
					$secondary_stages[ get_post_meta( $competition->ID, '_anwpfl_multistage_main', true ) ][] = $obj;
				} else {
					$obj->stages   = [];
					$output_data[] = $obj;
				}
			}

			foreach ( $output_data as $main_stage ) {
				if ( ! empty( $secondary_stages[ $main_stage->id ] ) ) {
					$main_stage->stages = wp_list_sort( $secondary_stages[ $main_stage->id ], 'stage_order' );
				}
			}
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

		$args = [
			'post_type'        => 'anwp_competition',
			'posts_per_page'   => - 1,
			'suppress_filters' => false,
			'post_status'      => [ 'publish', 'stage_secondary' ],
			'meta_key'         => '_anwpfl_multistage_main',
			'meta_value'       => (int) $main_id,
		];

		$query  = new WP_Query( $args );
		$stages = [];

		if ( $query->have_posts() ) {

			/** @var  $p WP_Post */
			foreach ( $query->get_posts() as $p ) {

				$groups        = json_decode( get_post_meta( $p->ID, '_anwpfl_groups', true ) );
				$groups_number = is_array( $groups ) ? count( $groups ) : 0;

				$stages[ $p->ID ] = [
					'title'           => $p->post_title,
					'id'              => $p->ID,
					'order'           => get_post_meta( $p->ID, '_anwpfl_stage_order', true ),
					'stage_title'     => get_post_meta( $p->ID, '_anwpfl_stage_title', true ),
					'type'            => get_post_meta( $p->ID, '_anwpfl_type', true ),
					'format_robin'    => get_post_meta( $p->ID, '_anwpfl_format_robin', true ),
					'format_knockout' => get_post_meta( $p->ID, '_anwpfl_format_knockout', true ),
					'groups'          => $groups_number,
				];
			}
		}

		// Sort stages
		usort(
			$stages,
			function ( $a, $b ) {
				return strcmp( $a['order'], $b['order'] );
			}
		);

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

		// Populate Object Cache
		$ids = wp_list_pluck( $matches, 'match_id' );

		// Get match links
		$matches_posts = [];

		$args = [
			'include'                => $ids,
			'post_type'              => 'anwp_match',
			'update_post_meta_cache' => false,
		];

		/** @var WP_Post $match_post */
		foreach ( get_posts( $args ) as $match_post ) {
			$matches_posts[ $match_post->ID ] = $match_post;
		}

		// Add extra data to match
		foreach ( $matches as $match_index => $match ) {
			$match->permalink = get_permalink( $matches_posts[ $match->match_id ] ?? $match->match_id );
		}

		return $matches;
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
			if ( false !== mb_stripos( $options->filter_values, '-', true ) ) {
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
			if ( false !== mb_stripos( $options->filter_by_matchweeks, '-', true ) ) {
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
		$options->sort_by_matchweek = $options->sort_by_matchweek && in_array( mb_strtolower( $options->sort_by_matchweek ), [ 'asc', 'desc' ], true ) ? $options->sort_by_matchweek : '';

		if ( $options->sort_by_matchweek ) {

			$matchweek_order = mb_strtoupper( sanitize_key( $options->sort_by_matchweek ) );

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
			return $matches;
		}

		// Populate Object Cache
		$ids = wp_list_pluck( $matches, 'match_id' );

		if ( 'ids' === $result ) {
			return $ids;
		}

		$permalinks = $this->plugin->helper->get_permalinks_by_ids( $ids, 'anwp_match' );

		// Add extra data to match
		foreach ( $matches as $match ) {
			$match->permalink = $permalinks[ $match->match_id ] ?? '';
		}

		return $matches;
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

		$stage_order = [];
		foreach ( $competitions as $c ) {
			$stage_order[ $c->ID ] = (int) get_post_meta( $c->ID, '_anwpfl_stage_order', true );
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
		$standings = get_posts(
			[
				'ignore_sticky_posts' => true,
				'numberposts'         => 1,
				'post_type'           => 'anwp_standing',
				'meta_query'          => [
					[
						'key'   => '_anwpfl_competition',
						'value' => $competition_id,
					],
					[
						'key'   => '_anwpfl_competition_group',
						'value' => $group_id,
					],
				],
			]
		);

		return $standings;
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

		$groups = get_post_meta( (int) $competition_id, '_anwpfl_groups', true );

		if ( empty( $groups ) ) {
			return [];
		}

		$groups = json_decode( $groups );

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

		static $output_data = null;

		if ( null === $output_data || $force_update ) {

			/*
			|--------------------------------------------------------------------
			| Prepare Terms Data Map
			|--------------------------------------------------------------------
			*/
			global $wpdb;

			$term_data = $wpdb->get_results(
				"
					SELECT t.term_id, t.name, tr.object_id, tt.taxonomy
					FROM $wpdb->terms AS t
					INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
					INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE tt.taxonomy IN ('anwp_league', 'anwp_season')
				"
			); // phpcs:ignore WordPress.DB.PreparedSQL

			$term_data_map = [];

			if ( ! empty( $term_data ) && is_array( $term_data ) ) {
				foreach ( $term_data as $term_object_data ) {
					if ( ! isset( $term_data_map[ $term_object_data->object_id ] ) ) {
						$term_data_map[ $term_object_data->object_id ] = [];
					}

					$term_data_map[ $term_object_data->object_id ][] = $term_object_data;
				}
			}

			/*
			|--------------------------------------------------------------------
			| Competitions
			|--------------------------------------------------------------------
			*/
			$output_data = [];

			$meta_keys = [
				'_anwpfl_groups',
				'_anwpfl_rounds',
				'_anwpfl_type',
				'_anwpfl_multistage',
				'_anwpfl_multistage_main',
				'_anwpfl_competition_order',
				'_anwpfl_stage_title',
				'_anwpfl_stage_order',
				'_anwpfl_logo',
			];

			$all_meta_data    = $this->plugin->helper->get_metadata_grouped( $meta_keys );
			$all_competitions = $wpdb->get_results(
				"
				SELECT p.*
				FROM $wpdb->posts p
				WHERE ( p.post_status = 'publish' OR p.post_status = 'stage_secondary' ) AND p.post_type = 'anwp_competition'
				ORDER BY p.post_title ASC
				"
			) ?: [];

			/** @var WP_Post $competition */
			foreach ( $all_competitions as $competition ) {

				$obj                    = (object) [];
				$obj->id                = $competition->ID;
				$obj->title             = $competition->post_title;
				$obj->groups            = json_decode( $all_meta_data['_anwpfl_groups'][ $competition->ID ] ?? '' );
				$obj->rounds            = json_decode( $all_meta_data['_anwpfl_rounds'][ $competition->ID ] ?? '' );
				$obj->type              = $all_meta_data['_anwpfl_type'][ $competition->ID ] ?? '';
				$obj->league_id         = 0;
				$obj->season_ids        = [];
				$obj->league_text       = '';
				$obj->season_text       = [];
				$obj->multistage        = $all_meta_data['_anwpfl_multistage'][ $competition->ID ] ?? '';
				$obj->multistage_main   = $all_meta_data['_anwpfl_multistage_main'][ $competition->ID ] ?? '';
				$obj->competition_order = $all_meta_data['_anwpfl_competition_order'][ $competition->ID ] ?? '';
				$obj->title_full        = $obj->title;
				$obj->stage_title       = $all_meta_data['_anwpfl_stage_title'][ $competition->ID ] ?? '';
				$obj->stage_order       = absint( $all_meta_data['_anwpfl_stage_order'][ $competition->ID ] ?? 0 );
				$obj->logo              = '';

				// Set full title in multistage competitions
				if ( '' !== $obj->multistage && $obj->stage_title && ! str_contains( $obj->title_full, $obj->stage_title ) ) {
					$obj->title_full .= ' - ' . $obj->stage_title;
				}

				/*
				|--------------------------------------------------------------------
				| Get Season and League data
				|--------------------------------------------------------------------
				*/
				if ( ! empty( $term_data_map[ $obj->id ] ) && is_array( $term_data_map[ $obj->id ] ) ) {
					foreach ( $term_data_map[ $obj->id ] as $obj_term ) {
						if ( 'anwp_league' === $obj_term->taxonomy ) {
							$obj->league_id   = (int) $obj_term->term_id;
							$obj->league_text = $obj_term->name;
						} elseif ( 'anwp_season' === $obj_term->taxonomy ) {
							$obj->season_ids[]  = (int) $obj_term->term_id;
							$obj->season_text[] = $obj_term->name;
						}
					}
				}

				$obj->season_ids  = implode( ',', $obj->season_ids );
				$obj->season_text = implode( ',', $obj->season_text );

				/*
				|--------------------------------------------------------------------
				| Prepare output
				|--------------------------------------------------------------------
				*/
				if ( 'stage_secondary' === $competition->post_status ) {
					$obj->title_full        = '- ' . $obj->title_full;
					$obj->logo              = $all_meta_data['_anwpfl_logo'][ $obj->multistage_main ] ?? '';
					$obj->competition_order = $all_meta_data['_anwpfl_competition_order'][ $obj->multistage_main ] ?? '';

					$secondary_stages[ $obj->multistage_main ][] = $obj;
				} else {
					$obj->logo     = $all_meta_data['_anwpfl_logo'][ $competition->ID ] ?? '';
					$output_data[] = $obj;
				}
			}

			/*
			|--------------------------------------------------------------------
			| Reorder
			|--------------------------------------------------------------------
			*/
			$clone_data = $output_data;

			foreach ( $clone_data as $main_stage_competition ) {
				if ( ! empty( $secondary_stages[ $main_stage_competition->id ] ) ) {
					$stages = $secondary_stages[ $main_stage_competition->id ];
					$stages = wp_list_sort( $stages, 'stage_order' );
					$index  = array_search( $main_stage_competition->id, wp_list_pluck( $output_data, 'id' ) );

					array_splice( $output_data, $index + 1, 0, $stages );
				}
			}
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

		if ( null === $output_data || $force_update ) {

			$cache_key = 'FL-COMPETITIONS-DATA';

			if ( anwp_fl()->cache->get( $cache_key ) ) {
				$output_data = anwp_fl()->cache->get( $cache_key );

				return $output_data;
			}

			/*
			|--------------------------------------------------------------------
			| Prepare Terms Data Map
			|--------------------------------------------------------------------
			*/
			global $wpdb;

			$term_data = $wpdb->get_results(
				"
					SELECT t.term_id, t.name, tr.object_id, tt.taxonomy
					FROM $wpdb->terms AS t
					INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
					INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE tt.taxonomy IN ('anwp_league', 'anwp_season')
				"
			); // phpcs:ignore WordPress.DB.PreparedSQL

			$term_data_map = [];

			if ( ! empty( $term_data ) && is_array( $term_data ) ) {
				foreach ( $term_data as $term_object_data ) {
					if ( ! isset( $term_data_map[ $term_object_data->object_id ] ) ) {
						$term_data_map[ $term_object_data->object_id ] = [];
					}

					$term_data_map[ $term_object_data->object_id ][] = $term_object_data;
				}
			}

			/*
			|--------------------------------------------------------------------
			| Competitions
			|--------------------------------------------------------------------
			*/
			$output_data = [];

			$meta_keys = [
				'_anwpfl_type',
				'_anwpfl_multistage',
				'_anwpfl_multistage_main',
				'_anwpfl_competition_order',
				'_anwpfl_stage_title',
				'_anwpfl_stage_order',
				'_anwpfl_logo',
			];

			$all_meta_data    = $this->plugin->helper->get_metadata_grouped( $meta_keys );
			$all_competitions = $wpdb->get_results(
				"
				SELECT p.*
				FROM $wpdb->posts p
				WHERE ( p.post_status = 'publish' OR p.post_status = 'stage_secondary' ) AND p.post_type = 'anwp_competition'
				ORDER BY p.post_title ASC
				"
			) ?: [];

			/** @var WP_Post $competition */
			foreach ( $all_competitions as $competition ) {

				$current_competition = [
					'id'                => absint( $competition->ID ),
					'title'             => $competition->post_title,
					'title_full'        => $competition->post_title,
					'stage_title'       => $all_meta_data['_anwpfl_stage_title'][ $competition->ID ] ?? '',
					'stage_order'       => absint( $all_meta_data['_anwpfl_stage_order'][ $competition->ID ] ?? 0 ),
					'type'              => $all_meta_data['_anwpfl_type'][ $competition->ID ] ?? '',
					'logo'              => $all_meta_data['_anwpfl_logo'][ $competition->ID ] ?? '',
					'league_id'         => 0,
					'season_ids'        => [],
					'league_text'       => '',
					'season_text'       => [],
					'multistage'        => $all_meta_data['_anwpfl_multistage'][ $competition->ID ] ?? '', // -none-/main/secondary
					'multistage_main'   => absint( $all_meta_data['_anwpfl_multistage_main'][ $competition->ID ] ?? 0 ),
					'competition_order' => absint( $all_meta_data['_anwpfl_competition_order'][ $competition->ID ] ?? 0 ),
				];

				// Set full title in multistage competitions
				if ( '' !== $current_competition['multistage'] && $current_competition['stage_title'] && ! str_contains( $current_competition['title_full'], $current_competition['stage_title'] ) ) {
					$current_competition['title_full'] .= ' - ' . $current_competition['stage_title'];
				}

				/*
				|--------------------------------------------------------------------
				| Get Season and League data
				|--------------------------------------------------------------------
				*/
				if ( ! empty( $term_data_map[ $current_competition['id'] ] ) && is_array( $term_data_map[ $current_competition['id'] ] ) ) {
					foreach ( $term_data_map[ $current_competition['id'] ] as $obj_term ) {
						if ( 'anwp_league' === $obj_term->taxonomy ) {
							$current_competition['league_id']   = (int) $obj_term->term_id;
							$current_competition['league_text'] = $obj_term->name;
						} elseif ( 'anwp_season' === $obj_term->taxonomy ) {
							$current_competition['season_ids'][]  = (int) $obj_term->term_id;
							$current_competition['season_text'][] = $obj_term->name;
						}
					}
				}

				$current_competition['season_ids']  = implode( ',', $current_competition['season_ids'] );
				$current_competition['season_text'] = implode( ',', $current_competition['season_text'] );

				/*
				|--------------------------------------------------------------------
				| Prepare output
				|--------------------------------------------------------------------
				*/
				if ( 'stage_secondary' === $competition->post_status ) {
					$current_competition['competition_order'] = $all_meta_data['_anwpfl_competition_order'][ $current_competition['multistage_main'] ] ?? '';

					$secondary_stages[ $current_competition['multistage_main'] ][] = $current_competition;
				} else {
					$output_data[] = $current_competition;
				}
			}

			/*
			|--------------------------------------------------------------------
			| Reorder
			|--------------------------------------------------------------------
			*/
			$cloned_data = $output_data;

			foreach ( $cloned_data as $main_stage_competition ) {

				if ( ! empty( $secondary_stages[ $main_stage_competition['id'] ] ) ) {

					$stages = $secondary_stages[ $main_stage_competition['id'] ];
					$stages = wp_list_sort( $stages, 'stage_order' );
					$index  = array_search( $main_stage_competition['id'], wp_list_pluck( $output_data, 'id' ), true );

					array_splice( $output_data, $index + 1, 0, $stages );
				}
			}

			unset( $cloned_data );

			/*
			|--------------------------------------------------------------------
			| Add keys and indexes
			|--------------------------------------------------------------------
			*/
			$updated_data = [];

			foreach ( $output_data as $c_key => $c_data ) {
				$updated_data[ $c_data['id'] ] = array_merge( $c_data, [ 'c_index' => $c_key ] );
			}

			$output_data = $updated_data;

			/*
			|--------------------------------------------------------------------
			| Save transient
			|--------------------------------------------------------------------
			*/
			if ( ! empty( $output_data ) ) {
				anwp_fl()->cache->set( $cache_key, $output_data );
			}
		}

		return $output_data;
	}

	/**
	 * Get list of competition groups without assigned standings.
	 *
	 * @param int $competition_id
	 * @param int $group_id
	 *
	 * @return array $output_data
	 * @since 0.11.1
	 */
	public function get_competition_group_standing( $competition_id, $group_id ) {

		$competitions = $this->get_competitions();
		$output_data  = [];

		if ( empty( $competitions ) || ! is_array( $competitions ) ) {
			return [];
		}

		foreach ( $competitions as $competition ) {
			if ( absint( $competition->id ) === absint( $competition_id ) ) {
				$c_groups = [];

				if ( ! empty( $competition->groups ) && is_array( $competition->groups ) ) {
					foreach ( $competition->groups as $group ) {
						if ( absint( $group->id ) === absint( $group_id ) ) {
							$c_groups[] = $group;
							break;
						}
					}
				}

				$competition->groups = $c_groups;
				$output_data[]       = $competition;

				return $output_data;
			}
		}

		return $output_data;
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
		| Get all saved Standing Tables
		|--------------------------------------------------------------------
		*/
		global $wpdb;

		$query = "
		SELECT p.ID, pm2.meta_value group_id, pm1.meta_value competition_id
		FROM $wpdb->posts p
		LEFT JOIN $wpdb->postmeta pm1 ON ( pm1.post_id = p.ID AND pm1.meta_key = '_anwpfl_competition' )
		LEFT JOIN $wpdb->postmeta pm2 ON ( pm2.post_id = p.ID AND pm2.meta_key = '_anwpfl_competition_group' )
		LEFT JOIN $wpdb->postmeta pm3 ON ( pm3.post_id = p.ID AND pm3.meta_key = '_anwpfl_fixed' )
		WHERE p.post_type = 'anwp_standing' AND p.post_status = 'publish' AND pm3.meta_value = 'true' AND pm1.meta_value IS NOT NULL AND pm1.meta_value != '' AND pm2.meta_value IS NOT NULL AND pm2.meta_value != ''
		";

		$query .= ' GROUP BY p.ID';

		/*
		|--------------------------------------------------------------------
		| Bump Query
		|--------------------------------------------------------------------
		*/
		$standings = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL

		$standings_map = [];

		if ( ! empty( $standings ) && is_array( $standings ) ) {
			foreach ( $standings as $standing ) {
				$standings_map[ $standing->competition_id ][ $standing->group_id ] = $standing->ID;
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
			| Get all saved Standing Tables
			|--------------------------------------------------------------------
			*/
			$query = "
				SELECT p.ID, p.post_title, pm1.meta_value competition_id
				FROM $wpdb->posts p
				LEFT JOIN $wpdb->postmeta pm1 ON ( pm1.post_id = p.ID AND pm1.meta_key = '_anwpfl_competition' )
				LEFT JOIN $wpdb->postmeta pm2 ON ( pm2.post_id = p.ID AND pm2.meta_key = '_anwpfl_competition_group' )
				LEFT JOIN $wpdb->postmeta pm3 ON ( pm3.post_id = p.ID AND pm3.meta_key = '_anwpfl_fixed' )
				WHERE p.post_type = 'anwp_standing' AND p.post_status = 'publish' AND pm3.meta_value = 'true' AND pm1.meta_value IS NOT NULL AND pm1.meta_value != '' AND pm2.meta_value IS NOT NULL AND pm2.meta_value != ''
			";

			$query .= ' GROUP BY p.ID';

			/*
			|--------------------------------------------------------------------
			| Bump Query
			|--------------------------------------------------------------------
			*/
			$standings = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL

			if ( ! empty( $standings ) && is_array( $standings ) ) {
				foreach ( $standings as $standing ) {
					$output_data[ $standing->competition_id ][ $standing->ID ] = $standing->post_title;
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

		$options      = [];
		$competitions = $this->get_competitions();

		if ( empty( $competitions ) || ! is_array( $competitions ) ) {
			return $options;
		}

		foreach ( $competitions as $stage ) {
			if ( ! $include_secondary ) {
				if ( 'secondary' === $stage->multistage ) {
					continue;
				}

				$options[ $stage->id ] = $stage->title;

			} else {
				$options[ $stage->id ] = $stage->title_full;
			}
		}

		return $options;
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @param array $columns Array of registered column names/labels.
	 *
	 * @return array          Modified array.
	 */
	public function sortable_columns( $sortable_columns ) {

		return array_merge( $sortable_columns, [ 'competition_id' => 'ID' ] );
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
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
	 * Handles admin column display. Hooked in via CPT_Core.
	 *
	 * @since  0.1.0
	 *
	 * @param array   $column  Column currently being rendered.
	 * @param integer $post_id ID of post to display column for.
	 */
	public function columns_display( $column, $post_id ) {

		switch ( $column ) {

			case 'anwpfl_league_logo':
				$logo = get_post_meta( $post_id, '_anwpfl_logo', true );

				if ( $logo ) {
					printf( '<img src="%s" class="anwp-admin-table-league-logo" alt="club logo" style="width: 30px; height: 30px; object-fit: contain;">', esc_url( $logo ) );
				}
				break;

			case 'anwpfl_multistage':
				$multistage  = get_post_meta( $post_id, '_anwpfl_multistage', true );
				$type        = get_post_meta( $post_id, '_anwpfl_type', true );
				$group_title = 'round-robin' === $type ? __( 'Groups', 'anwp-football-leagues' ) : __( 'Ties', 'anwp-football-leagues' );

				if ( '' === $multistage ) {
					echo sprintf( '<span class="anwp-g-label-like">%s</span>', esc_html__( 'Single', 'anwp-football-leagues' ) );
					echo '<br>> ' . esc_html( empty( $this->type_map[ $type ] ) ? '' : $this->type_map[ $type ] );

					if ( 'round-robin' === $type ) {
						$subtype = get_post_meta( $post_id, '_anwpfl_format_robin', true );
						echo ' | ' . esc_html( empty( $this->format_robin_map[ $subtype ] ) ? '' : $this->format_robin_map[ $subtype ] );
					} elseif ( 'knockout' === $type ) {
						$subtype = get_post_meta( $post_id, '_anwpfl_format_knockout', true );
						echo ' | ' . esc_html( empty( $this->format_knockout_map[ $subtype ] ) ? '' : $this->format_knockout_map[ $subtype ] );
					}

					// Get number of groups
					$groups = json_decode( get_post_meta( $post_id, '_anwpfl_groups', true ) );

					if ( is_array( $groups ) ) {
						echo ' | ' . esc_html( $group_title ) . ':&nbsp;' . (int) count( $groups );
					}

					echo ' | ' . esc_html__( 'ID', 'anwp-football-leagues' ) . ':&nbsp;' . (int) $post_id;
				} else {

					echo sprintf( '<span class="anwp-g-label-like">%s</span>', esc_html__( 'Multiple', 'anwp-football-leagues' ) );

					// Render main stage
					echo '<br><div class="anwp-g-stage-wrap">> <b>' . esc_html( get_post_meta( $post_id, '_anwpfl_stage_title', true ) ) . '</b> | ';
					echo esc_html( empty( $this->type_map[ $type ] ) ? '' : $this->type_map[ $type ] );

					if ( 'round-robin' === $type ) {
						$subtype = get_post_meta( $post_id, '_anwpfl_format_robin', true );
						echo ' | ' . esc_html( empty( $this->format_robin_map[ $subtype ] ) ? '' : $this->format_robin_map[ $subtype ] );
					} elseif ( 'knockout' === $type ) {
						$subtype = get_post_meta( $post_id, '_anwpfl_format_knockout', true );
						echo ' | ' . esc_html( empty( $this->format_knockout_map[ $subtype ] ) ? '' : $this->format_knockout_map[ $subtype ] );
					}

					// Get number of groups
					$groups = json_decode( get_post_meta( $post_id, '_anwpfl_groups', true ) );

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
					} elseif ( ! (int) get_post_meta( $post_id, '_anwpfl_multistage_main', true ) ) {
						echo esc_html__( '!!! Error: Main Stage in Multistage competition is not set.', 'anwp-football-leagues' );
					}
				}

				break;

			case 'anwpfl_standings':
				$multistage   = get_post_meta( $post_id, '_anwpfl_multistage', true );
				$type         = get_post_meta( $post_id, '_anwpfl_type', true );
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

							echo '<svg class="anwp-icon anwp-icon--octi" style="margin-bottom: -2px;"><use xlink:href="#icon-link-external"></use></svg>&nbsp;&nbsp;';
							echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $standing_title ) . '</a><br>';
						}
					}
				} elseif ( 'main' === $multistage ) {

					if ( 'round-robin' === $type ) {
						if ( ! empty( $standing_map[ $post_id ] ) ) {
							foreach ( $standing_map[ $post_id ] as $standing_id => $standing_title ) {
								$edit_link = admin_url( 'post.php?post=' . intval( $standing_id ) . '&action=edit' );

								echo '<svg class="anwp-icon anwp-icon--octi" style="margin-bottom: -2px;"><use xlink:href="#icon-link-external"></use></svg>&nbsp;&nbsp;';
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

								echo '<svg class="anwp-icon anwp-icon--octi" style="margin-bottom: -2px;"><use xlink:href="#icon-link-external"></use></svg>&nbsp;&nbsp;';
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
		if ( 'secondary' === get_post_meta( $id, '_anwpfl_multistage', true ) ) {
			return get_post_meta( $id, '_anwpfl_multistage_main', true );
		}

		return $id;
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
			$competition_rounds[ $competition_id ] = json_decode( get_post_meta( $competition_id, '_anwpfl_rounds', true ) ) ?: [];
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
		// Populate Object Cache
		$competition_ids = wp_list_pluck( $competitions, 'id' );

		// Get match links
		$competition_posts = [];

		$query_args = [
			'include'       => $competition_ids,
			'post_type'     => 'anwp_competition',
			'cache_results' => false,
		];

		/** @var WP_Post $competition_post */
		foreach ( get_posts( $query_args ) as $competition_post ) {
			$competition_posts[ $competition_post->ID ] = $competition_post;
		}

		foreach ( $competitions as $competition_index => $competition_post_obj ) {
			$competitions[ $competition_index ]['link'] = isset( $competition_posts[ $competition_post_obj['id'] ] ) ? get_permalink( $competition_posts[ $competition_post_obj['id'] ] ) : '';
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
	 * Get competition data.
	 *
	 * @param int $competition_id
	 *
	 * @since 0.16.7
	 * @return array{
	 *     id: int,
	 *     title: string,
	 *     title_full: int,
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
	 *     c_index: int,
	 * }
	 */
	public function get_competition_data( int $competition_id ): array {

		$competition_data = $this->get_competitions_data()[ $competition_id ] ?? [];

		if ( ! $competition_data ) {
			return [];
		}

		if ( 'secondary' === $competition_data['multistage'] ) {
			$competition_data['logo'] = $this->get_competitions_data()[ $competition_data['multistage_main'] ]['logo'] ?? '';
		}

		return $competition_data;
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
	 *     title_full: int,
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
	 *     c_index: int,
	 *     groups: array,
	 *     rounds: array,
	 * }
	 */
	public function get_competition_data_full( ?int $competition_id ): array {
		if ( empty( $competition_id ) ) {
			return [];
		}

		$competition_data = $this->get_competitions_data()[ $competition_id ] ?? [];

		if ( ! $competition_data ) {
			return [];
		}

		if ( 'secondary' === $competition_data['multistage'] ) {
			$competition_data['logo'] = $this->get_competitions_data()[ $competition_data['multistage_main'] ]['logo'] ?? '';
		}

		$competition_data['groups'] = json_decode( get_post_meta( $competition_id, '_anwpfl_groups', true ) ?? '' );
		$competition_data['rounds'] = json_decode( get_post_meta( $competition_id, '_anwpfl_rounds', true ) ?? '' );

		return $competition_data;
	}
}
