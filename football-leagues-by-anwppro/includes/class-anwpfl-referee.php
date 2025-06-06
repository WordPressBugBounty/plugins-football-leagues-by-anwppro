<?php
/**
 * AnWP Football Leagues :: Referee.
 *
 * @since   0.7.3
 * @package AnWP_Football_Leagues
 */

require_once dirname( __FILE__ ) . '/../vendor/cpt-core/CPT_Core.php';

/**
 * AnWP Football Leagues :: Referee post type class.
 *
 * @since 0.1.0
 *
 * @see   https://github.com/WebDevStudios/CPT_Core
 */
class AnWPFL_Referee extends CPT_Core {

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
	 * @since  0.1.0
	 * @param  AnWP_Football_Leagues $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		$permalink_structure = $plugin->options->get_permalink_structure();
		$permalink_slug      = empty( $permalink_structure['referee'] ) ? 'referee' : $permalink_structure['referee'];

		// Register this cpt.
		parent::__construct(
			[ // array with Singular, Plural, and Registered name
				esc_html__( 'Referee', 'anwp-football-leagues' ),
				esc_html__( 'Referee', 'anwp-football-leagues' ),
				'anwp_referee',
			],
			[
				'supports'            => [
					'title',
					'comments',
				],
				'rewrite'             => [ 'slug' => $permalink_slug ],
				'show_in_menu'        => true,
				'menu_position'       => 34,
				'exclude_from_search' => 'hide' === AnWPFL_Options::get_value( 'display_front_end_search_referee' ),
				'menu_icon'           => 'dashicons-groups',
				'public'              => true,
				'labels'              => [
					'all_items'    => esc_html__( 'Referee', 'anwp-football-leagues' ),
					'add_new'      => esc_html__( 'Add Referee', 'anwp-football-leagues' ),
					'add_new_item' => esc_html__( 'Add Referee', 'anwp-football-leagues' ),
					'edit_item'    => esc_html__( 'Edit Referee', 'anwp-football-leagues' ),
					'new_item'     => esc_html__( 'New Referee', 'anwp-football-leagues' ),
					'view_item'    => esc_html__( 'View Referee', 'anwp-football-leagues' ),
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
			return esc_html__( 'Name', 'anwp-football-leagues' );
		}

		return $title;
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {

		// Create CMB2 metabox
		add_action( 'cmb2_admin_init', [ $this, 'init_cmb2_metaboxes' ] );
		add_action( 'cmb2_before_post_form_anwp_referee_metabox', [ $this, 'cmb2_before_metabox' ] );
		add_action( 'cmb2_after_post_form_anwp_referee_metabox', [ $this, 'cmb2_after_metabox' ] );

		// Render Custom Content below
		add_action(
			'anwpfl/tmpl-referee/after_wrapper',
			function ( $staff_id ) {

				$content_below = get_post_meta( $staff_id, '_anwpfl_custom_content_below', true );

				if ( trim( $content_below ) ) {
					echo '<div class="anwp-b-wrap mt-4">' . do_shortcode( $content_below ) . '</div>';
				}
			}
		);
	}

	/**
	 * Renders tabs for metabox. Helper HTML before.
	 *
	 * @since 0.9.0
	 */
	public function cmb2_before_metabox() {
		// @formatter:off
		ob_start();
		?>
		<div class="anwp-b-wrap">
			<div class="anwp-metabox-tabs d-sm-flex">
				<div class="anwp-metabox-tabs__controls d-flex flex-sm-column flex-wrap">
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-general-referee_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-gear"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'General', 'anwp-football-leagues' ); ?></span>
					</div>
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-media-referee_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-device-camera"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Media', 'anwp-football-leagues' ); ?></span>
					</div>
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-desc-referee_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-note"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Bio', 'anwp-football-leagues' ); ?></span>
					</div>
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-custom_fields-referee_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-server"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Custom Fields', 'anwp-football-leagues' ); ?></span>
					</div>
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-bottom_content-referee_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-repo-push"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Bottom Content', 'anwp-football-leagues' ); ?></span>
					</div>
					<?php
					/**
					 * Fires in the bottom of referee tabs.
					 * Add new tabs here.
					 *
					 * @since 0.9.0
					 */
					do_action( 'anwpfl/cmb2_tabs_control/referee' );
					?>
				</div>
				<div class="anwp-metabox-tabs__content pl-4 pb-4">
		<?php
		echo ob_get_clean(); // WPCS: XSS ok.
		// @formatter:on
	}

	/**
	 * Renders tabs for metabox. Helper HTML after.
	 *
	 * @since 0.9.0
	 */
	public function cmb2_after_metabox() {
		// @formatter:off
		ob_start();
		?>
				</div>
			</div>
		</div>
		<?php
		echo ob_get_clean(); // WPCS: XSS ok.
		// @formatter:on
	}

	/**
	 * Create CMB2 metaboxes
	 */
	public function init_cmb2_metaboxes() {

		// Start with an underscore to hide fields from custom fields list
		$prefix = '_anwpfl_';

		/**
		 * Initiate the metabox
		 */
		$cmb = new_cmb2_box(
			[
				'id'           => 'anwp_referee_metabox',
				'title'        => esc_html__( 'Referee Info', 'anwp-football-leagues' ),
				'object_types' => [ 'anwp_referee' ],
				'context'      => 'normal',
				'priority'     => 'high',
				'classes'      => 'anwp-b-wrap',
				'show_names'   => true,
			]
		);

		// Short Name
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Short Name', 'anwp-football-leagues' ),
				'id'         => $prefix . 'short_name',
				'type'       => 'text',
				'before_row' => '<div id="anwp-tabs-general-referee_metabox" class="anwp-metabox-tabs__content-item">',
			]
		);

		// Full Name
		$cmb->add_field(
			[
				'name' => esc_html__( 'Full Name', 'anwp-football-leagues' ),
				'id'   => $prefix . 'full_name',
				'type' => 'text',
			]
		);

		// Place of Birth
		$cmb->add_field(
			[
				'name' => esc_html__( 'Place of Birth', 'anwp-football-leagues' ),
				'id'   => $prefix . 'place_of_birth',
				'type' => 'text',
			]
		);

		// Date of Birth
		$cmb->add_field(
			[
				'name'        => esc_html__( 'Date of Birth', 'anwp-football-leagues' ),
				'id'          => $prefix . 'date_of_birth',
				'type'        => 'text_date',
				'date_format' => 'Y-m-d',
			]
		);

		// Date of death
		$cmb->add_field(
			[
				'name'        => esc_html__( 'Date of death', 'anwp-football-leagues' ),
				'id'          => $prefix . 'date_of_death',
				'type'        => 'text_date',
				'date_format' => 'Y-m-d',
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Nationality', 'anwp-football-leagues' ),
				'id'         => $prefix . 'nationality',
				'type'       => 'anwp_fl_multiselect',
				'options_cb' => [ $this->plugin->data, 'cb_get_countries' ],
			]
		);

		// Job Title
		$cmb->add_field(
			[
				'name' => esc_html__( 'Job Title', 'anwp-football-leagues' ),
				'id'   => $prefix . 'job_title',
				'type' => 'text',
			]
		);

		$cmb->add_field(
			[
				'name'        => esc_html__( 'External ID', 'anwp-football-leagues' ),
				'id'          => $prefix . 'referee_external_id',
				'type'        => 'text',
				'description' => esc_html__( 'Used on Data Import', 'anwp-football-leagues' ),
				'after_row'   => '</div>',
			]
		);

		/*
		|--------------------------------------------------------------------------
		| Media
		|--------------------------------------------------------------------------
		*/

		// Photo
		$cmb->add_field(
			[
				'name'         => esc_html__( 'Photo', 'anwp-football-leagues' ),
				'id'           => $prefix . 'photo',
				'after_row'    => '</div>',
				'before_row'   => '<div id="anwp-tabs-media-referee_metabox" class="anwp-metabox-tabs__content-item d-none">',
				'type'         => 'file',
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

		/*
		|--------------------------------------------------------------------------
		| Description
		|--------------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'            => esc_html__( 'Description', 'anwp-football-leagues' ),
				'id'              => $prefix . 'description',
				'type'            => 'wysiwyg',
				'options'         => [
					'wpautop'       => true,
					'media_buttons' => true,
					'textarea_name' => 'anwp_referee_description_input',
					'textarea_rows' => 10,
					'teeny'         => false, // output the minimal editor config used in Press This
					'dfw'           => false, // replace the default fullscreen with DFW (needs specific css)
					'tinymce'       => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
					'quicktags'     => true, // load Quicktags, can be used to pass settings directly to Quicktags using an array()
				],
				'show_names'      => false,
				'sanitization_cb' => [ anwp_fl()->helper, 'sanitize_cmb2_fl_text' ],
				'before_row'      => '<div id="anwp-tabs-desc-referee_metabox" class="anwp-metabox-tabs__content-item d-none">',
				'after_row'       => '</div>',
			]
		);

		/*
		|--------------------------------------------------------------------------
		| Custom Fields Metabox
		|--------------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Title', 'anwp-football-leagues' ) . ' #1',
				'id'         => $prefix . 'custom_title_1',
				'before_row' => '<div id="anwp-tabs-custom_fields-referee_metabox" class="anwp-metabox-tabs__content-item d-none">',
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
				'option_slug' => 'referee_custom_fields',
				'after_row'   => '</div>',
				'before_row'  => '<hr>',
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
					'teeny'         => false, // output the minimal editor config used in Press This
					'dfw'           => false, // replace the default fullscreen with DFW (needs specific css)
					'tinymce'       => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
					'quicktags'     => true, // load Quicktags, can be used to pass settings directly to Quicktags using an array()
				],
				'show_names' => false,
				'before_row' => '<div id="anwp-tabs-bottom_content-referee_metabox" class="anwp-metabox-tabs__content-item d-none">',
				'after_row'  => '</div>',
			]
		);

		/**
		 * Adds extra fields to the metabox.
		 *
		 * @since 0.9.0
		 */
		$extra_fields = apply_filters( 'anwpfl/cmb2_tabs_content/referee', [] );

		if ( ! empty( $extra_fields ) && is_array( $extra_fields ) ) {
			foreach ( $extra_fields as $field ) {
				$cmb->add_field( $field );
			}
		}
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @param array $columns Array of registered column names/labels.
	 *
	 * @return array          Modified array.
	 */
	public function sortable_columns( $sortable_columns ) {

		return array_merge( $sortable_columns, [ 'referee_id' => 'ID' ] );
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @param  array $columns Array of registered column names/labels.
	 * @return array          Modified array.
	 */
	public function columns( $columns ) {

		// Add new columns
		$new_columns = [
			'referee_id' => esc_html__( 'ID', 'anwp-football-leagues' ),
		];

		// Merge old and new columns
		$columns = array_merge( $new_columns, $columns );

		// Change columns order
		$new_columns_order = [
			'cb',
			'title',
			'comments',
			'date',
			'referee_id',
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
	 */
	public function columns_display( $column, $post_id ) {
		switch ( $column ) {

			case 'referee_id':
				echo (int) $post_id;
				break;
		}
	}

	/**
	 * Get list of all referees with some extra data.
	 *
	 * @since 0.14.5
	 * @return array $output_data
	 */
	public function get_referee_list() {

		$cache_key = 'FL-REFEREES-LIST';

		if ( anwp_fl()->cache->get( $cache_key ) ) {
			return anwp_fl()->cache->get( $cache_key );
		}

		global $wpdb;

		$all_officials = $wpdb->get_results(
			"
			SELECT p.ID id, p.post_title name, p.post_name,
				MAX( CASE WHEN pm.meta_key = '_anwpfl_job_title' THEN pm.meta_value ELSE '' END) as job,
				MAX( CASE WHEN pm.meta_key = '_anwpfl_nationality' THEN pm.meta_value ELSE '' END) as nationality,
				MAX( CASE WHEN pm.meta_key = '_anwpfl_date_of_birth' THEN pm.meta_value ELSE '' END) as birthdate,
				MAX( CASE WHEN pm.meta_key = '_anwpfl_photo' THEN pm.meta_value ELSE '' END) as photo
			FROM $wpdb->posts p
			LEFT JOIN $wpdb->postmeta pm ON ( pm.post_id = p.ID )
			WHERE p.post_status = 'publish' AND p.post_type = 'anwp_referee'
			GROUP BY p.ID
			ORDER BY p.post_title
			"
		);

		/*
		|--------------------------------------------------------------------
		| Get Referee links
		|--------------------------------------------------------------------
		*/
		$referee_links = [];

		if ( ! $this->plugin->cache->simple_link_building ) {
			$all_referee_objects = get_posts(
				[
					'numberposts'      => - 1,
					'post_type'        => 'anwp_referee',
					'suppress_filters' => false,
					'post_status'      => 'publish',
					'cache_results'    => false,
				]
			);

			foreach ( $all_referee_objects as $referee_obj ) {
				$referee_links[ $referee_obj->ID ] = get_permalink( $referee_obj );
			}
		}

		$permalink_slug = $this->plugin->options->get_permalink_structure()['referee'] ?? 'referee';
		$base_url       = get_site_url( null, '/' . $permalink_slug . '/' );

		/*
		|--------------------------------------------------------------------
		| Prepare referee Data
		|--------------------------------------------------------------------
		*/
		if ( empty( $all_officials ) ) {
			return [];
		}

		foreach ( $all_officials as $official ) {

			$official->id      = absint( $official->id );
			$official->country = '';
			$official->link    = $referee_links[ $official->id ] ?? '';

			if ( $this->plugin->cache->simple_link_building && empty( $official->link ) ) {
				$official->link = $base_url . $official->post_name . '/';
			}

			if ( $official->birthdate ) {
				$official->birthdate = date_i18n( get_option( 'date_format' ), strtotime( $official->birthdate ) );
			}

			if ( $official->nationality ) {
				$countries = maybe_unserialize( $official->nationality );

				if ( ! empty( $countries ) && is_array( $countries ) && ! empty( $countries[0] ) ) {
					$official->country = mb_strtolower( $countries[0] );
				}

				if ( ! empty( $countries ) && is_array( $countries ) && ! empty( $countries[1] ) ) {
					$official->country_2 = mb_strtolower( $countries[1] );
				}
			}

			unset( $official->nationality );
		}

		anwp_fl()->cache->set( $cache_key, $all_officials );

		return $all_officials;
	}

	/**
	 * Get Referee data
	 *
	 * @param int $referee_id Referee ID
	 *
	 * @return object|bool
	 * @since 0.14.5
	 */
	public function get_referee( $referee_id ) {

		static $referees_cache = null;

		if ( null === $referees_cache ) {
			$referees_cache = [];

			foreach ( $this->get_referee_list() as $referee_saved ) {
				$referees_cache[ $referee_saved->id ] = $referee_saved;
			}
		}

		if ( empty( $referees_cache ) || ! absint( $referee_id ) ) {
			return false;
		}

		return $referees_cache[ $referee_id ] ?? false;
	}

	/**
	 * Get array of matches for widget and shortcode.
	 *
	 * @param object|array $options
	 * @param string       $result
	 * @param string       $type
	 *
	 * @since 0.11.14
	 * @return array|null|object
	 */
	public function get_referee_games( $options, string $result = '', string $type = 'referee' ) {

		global $wpdb;

		$options = (object) wp_parse_args(
			$options,
			[
				'referee_id'      => '',
				'competition_id'  => '',
				'season_id'       => '',
				'show_secondary'  => '',
				'type'            => '',
				'filter_by_clubs' => '',
				'sort_by_date'    => '',
				'limit'           => '',
				'date_from'       => '',
				'date_to'         => '',
				'exclude_ids'     => '',
				'include_ids'     => '',
			]
		);

		if ( ! absint( $options->referee_id ) ) {
			return [];
		}

		if ( 'additional' === $type && ! $this->site_has_additional_referees() ) {
			return [];
		}

		$query = "
		SELECT g.*
		FROM $wpdb->anwpfl_matches g
		";

		if ( 'assistant' === $type ) {
			$query .= "LEFT JOIN $wpdb->postmeta pm ON ( pm.post_id = g.match_id AND pm.meta_key IN ('_anwpfl_assistant_1', '_anwpfl_assistant_2') )";
		} elseif ( 'referee_fourth' === $type ) {
			$query .= "LEFT JOIN $wpdb->postmeta pm ON ( pm.post_id = g.match_id AND pm.meta_key = '_anwpfl_referee_fourth' )";
		} elseif ( 'additional' === $type ) {
			$query .= "LEFT JOIN $wpdb->postmeta pm ON ( pm.post_id = g.match_id AND pm.meta_key = '_anwpfl_additional_referees' )";
		}

		if ( 'additional' === $type ) {
			$query .= ' WHERE pm.meta_value LIKE \'%"' . absint( $options->referee_id ) . '"%\' ';
		} elseif ( 'referee' === $type ) {
			$query .= $wpdb->prepare( ' WHERE g.referee = %d ', $options->referee_id );
		} else {
			$query .= $wpdb->prepare( ' WHERE pm.meta_value = %d ', $options->referee_id );
		}

		/**==================
		 * WHERE filter by competition
		 *================ */
		if ( ! empty( $options->competition_id ) ) {
			if ( AnWP_Football_Leagues::string_to_bool( $options->show_secondary ) ) {
				$competition_ids = wp_parse_id_list( $options->competition_id );
				$format          = implode( ', ', array_fill( 0, count( $competition_ids ), '%d' ) );

				$query .= $wpdb->prepare( " AND ( g.competition_id IN ({$format}) OR g.main_stage_id IN ({$format}) ) ", array_merge( $competition_ids, $competition_ids ) ); // phpcs:ignore
			} else {

				$competition_ids = wp_parse_id_list( $options->competition_id );
				$format          = implode( ', ', array_fill( 0, count( $competition_ids ), '%d' ) );

				$query .= $wpdb->prepare( " AND g.competition_id IN ({$format}) ", $competition_ids ); // phpcs:ignore
			}
		}

		/**==================
		 * WHERE filter by season
		 *================ */
		if ( ! empty( $options->season_id ) ) {
			$query .= $wpdb->prepare( ' AND g.season_id = %d ', $options->season_id );
		}

		/**==================
		 * WHERE filter by type
		 *================ */
		if ( '' !== $options->type ) {
			$query .= $wpdb->prepare( ' AND g.finished = %d ', 'result' === $options->type ? 1 : 0 );
		}

		/**==================
		 * WHERE filter by club
		 *================ */
		if ( ! empty( $options->filter_by_clubs ) ) {

			$clubs  = wp_parse_id_list( $options->filter_by_clubs );
			$format = implode( ', ', array_fill( 0, count( $clubs ), '%d' ) );

			$query .= $wpdb->prepare( " AND ( g.home_club IN ({$format}) OR g.away_club IN ({$format}) ) ", array_merge( $clubs, $clubs ) ); // phpcs:ignore
		}

		/**==================
		 * WHERE filter by date_to
		 *
		 * @since 0.10.3
		 *================ */
		if ( trim( $options->date_to ) ) {
			$date_to = explode( ' ', $options->date_to )[0];

			if ( anwp_football_leagues()->helper->validate_date( $date_to, 'Y-m-d' ) ) {
				$query .= $wpdb->prepare( ' AND g.kickoff <= %s ', $date_to . ' 23:59:59' );
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
				$query .= $wpdb->prepare( ' AND g.kickoff >= %s ', $date_from . ' 00:00:00' );
			}
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

				$query .= $wpdb->prepare( " AND g.match_id NOT IN ({$exclude_format})", $exclude_ids ); // phpcs:ignore
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

				$query .= $wpdb->prepare( " AND g.match_id IN ({$include_format})", $include_ids ); // phpcs:ignore
			}
		}

		/**==================
		 * ORDER BY
		 *================ */
		if ( 'asc' === mb_strtolower( $options->sort_by_date ) ) {
			$query .= 'ORDER BY g.kickoff ASC';
		} elseif ( 'desc' === mb_strtolower( $options->sort_by_date ) ) {
			$query .= 'ORDER BY g.kickoff DESC';
		}

		/**==================
		 * LIMIT clause
		 *================ */
		if ( isset( $options->limit ) && 0 < $options->limit ) {
			$query .= $wpdb->prepare( ' LIMIT %d', $options->limit );
		}

		$matches = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL

		if ( 'stats' === $result ) {
			return $matches;
		}

		// Populate Object Cache
		$ids = wp_list_pluck( $matches, 'match_id' );

		if ( 'ids' === $result ) {
			return $ids;
		}

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
			$matches[ $match_index ]->permalink = get_permalink( isset( $matches_posts[ $match->match_id ] ) ? $matches_posts[ $match->match_id ] : $match->match_id );
		}

		return $matches;
	}

	/**
	 * Get array of matches for widget and shortcode.
	 *
	 * @param object/bool $game
	 * @param array $player_cards
	 *
	 * @return string
	 * @since 0.11.14
	 */
	public function get_cards_game_html( $game, $player_cards = [] ) {

		$y_cards = 0;
		$r_cards = 0;

		$yr_count = AnWPFL_Options::get_value( 'yr_card_count', 'r' );

		if ( ! empty( $game ) ) {

			/*
			|--------------------------------------------------------------------
			| Get number of cards by team stats
			|--------------------------------------------------------------------
			*/
			$yr_cards = (int) $game->home_cards_yr + (int) $game->away_cards_yr;
			$y_cards  = (int) $game->home_cards_y + (int) $game->away_cards_y + ( in_array( $yr_count, [ 'y', 'yr' ], true ) ? $yr_cards : 0 );
			$r_cards  = (int) $game->home_cards_r + (int) $game->away_cards_r + ( in_array( $yr_count, [ 'r', 'yr' ], true ) ? $yr_cards : 0 );

			/*
			|--------------------------------------------------------------------
			| Get number of cards by players stats
			|--------------------------------------------------------------------
			*/
			$player_cards = (object) wp_parse_args(
				$player_cards,
				[
					'y'  => 0,
					'yr' => 0,
					'r'  => 0,
				]
			);

			// Compare team vs players stats
			$players_y_cards = (int) $player_cards->y + ( in_array( $yr_count, [ 'y', 'yr' ], true ) ? (int) $player_cards->yr : 0 );
			$players_r_cards = (int) $player_cards->r + ( in_array( $yr_count, [ 'r', 'yr' ], true ) ? (int) $player_cards->yr : 0 );

			if ( $y_cards < $players_y_cards ) {
				$y_cards = $players_y_cards;
			}

			if ( $r_cards < $players_r_cards ) {
				$r_cards = $players_r_cards;
			}
		}

		ob_start();
		?>
		<span class="ml-1 mt-1 anwp-w-20 anwp-leading-1-5 anwp-text-nowrap anwp-text-center anwp-text-white anwp-font-semibold <?php echo $y_cards ? 'anwp-bg-warning' : 'anwp-bg-gray'; ?>"><?php echo $y_cards ? absint( $y_cards ) : '&nbsp;'; ?></span>
		<span class="ml-1 mt-1 anwp-w-20 anwp-leading-1-5 anwp-text-nowrap anwp-text-center anwp-text-white anwp-font-semibold <?php echo $r_cards ? 'anwp-bg-danger' : 'anwp-bg-gray'; ?>"><?php echo $r_cards ? absint( $r_cards ) : '&nbsp;'; ?></span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get game cards by players
	 *
	 * @param array $games
	 *
	 * @return array
	 * @since 0.11.15
	 */
	public function get_cards_game_by_players( $games ) {

		$game_ids = wp_list_pluck( $games, 'match_id' );

		if ( empty( $game_ids ) ) {
			return [];
		}

		/*
		|--------------------------------------------------------------------
		| Get data from DB
		|--------------------------------------------------------------------
		*/
		global $wpdb;

		$query = "
		SELECT match_id, SUM(card_y) as y, SUM(card_yr) as yr, SUM(card_r) as r
		FROM {$wpdb->prefix}anwpfl_players
		";

		$game_ids = wp_parse_id_list( $game_ids );
		$format   = implode( ', ', array_fill( 0, count( $game_ids ), '%d' ) );

		$query .= $wpdb->prepare( " WHERE match_id IN ({$format}) ", $game_ids ); // phpcs:ignore
		$query .= ' GROUP BY match_id';

		return $wpdb->get_results( $query, OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get Post ID by External id
	 *
	 * @param $external_id
	 *
	 * @return string|null
	 * @since 0.14.0
	 */
	public function get_referee_id_by_external_id( $external_id ) {

		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = '_anwpfl_referee_external_id' AND meta_value = %s
				",
				$external_id
			)
		);
	}

	/**
	 * Check if site has additional referees
	 *
	 * @return int
	 * @since 0.14.4
	 */
	public function site_has_additional_referees() {

		static $output_result = null;

		if ( null === $output_result ) {
			global $wpdb;

			$output_result = $wpdb->get_var(
				"
				SELECT COUNT(*)
				FROM $wpdb->postmeta
				WHERE meta_key = '_anwpfl_additional_referees' AND meta_value != '' AND meta_value != 'a:0:{}'
				"
			);
		}

		return absint( $output_result );
	}

	/**
	 * Get additional referee grouped by job
	 *
	 * @return array
	 * @since 0.14.4
	 */
	public function get_additional_referee_grouped( $games_additional, $referee_id ) {
		global $wpdb;

		$game_ids = wp_parse_id_list( wp_list_pluck( $games_additional, 'match_id' ) );
		$format   = implode( ', ', array_fill( 0, count( $game_ids ), '%d' ) );

		$game_data = $wpdb->get_results(
			$wpdb->prepare( // phpcs:disable
				"
				SELECT post_id, meta_value
				FROM $wpdb->postmeta
				WHERE meta_key = '_anwpfl_additional_referees' AND meta_value != '' AND post_id IN ({$format})
				",
				$game_ids
			),
			OBJECT_K
		); // phpcs:enable

		$output = [];

		foreach ( $games_additional as $game ) {
			if ( isset( $game_data[ $game->match_id ] ) ) {
				$additional_referees = maybe_unserialize( $game_data[ $game->match_id ]->meta_value );

				foreach ( $additional_referees as $additional_referee ) {
					if ( absint( $additional_referee['_anwpfl_referee'] ) === absint( $referee_id ) && trim( $additional_referee['role'] ) ) {

						if ( ! isset( $output[ trim( $additional_referee['role'] ) ] ) ) {
							$output[ trim( $additional_referee['role'] ) ] = [];
						}

						$output[ trim( $additional_referee['role'] ) ][] = $game;
					}
				}
			}
		}

		return $output;
	}

	public function get_referee_name_by_id( $referee_id ) {

		static $referee_list = null;

		if ( null === $referee_list ) {
			$cache_key = 'FL-REFEREES-NAMES';

			if ( anwp_football_leagues()->cache->get( $cache_key ) ) {
				$referee_list = anwp_football_leagues()->cache->get( $cache_key );

				return $referee_list[ $referee_id ]->name ?? '';
			}

			global $wpdb;

			$referee_list = $wpdb->get_results(
				"
				SELECT ID, post_title name
				FROM $wpdb->posts
				WHERE post_status = 'publish' AND post_type = 'anwp_referee'
				",
				OBJECT_K
			);

			/*
			|--------------------------------------------------------------------
			| Save transient
			|--------------------------------------------------------------------
			*/
			if ( ! empty( $referee_list ) ) {
				anwp_football_leagues()->cache->set( $cache_key, $referee_list );
			}
		}

		return $referee_list[ $referee_id ]->name ?? '';
	}
}
