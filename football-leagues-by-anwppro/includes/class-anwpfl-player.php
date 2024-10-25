<?php
/**
 * AnWP Football Leagues :: Player.
 *
 * @since   0.1.0
 * @package AnWP_Football_Leagues
 */

/**
 * AnWP Football Leagues :: Player post type class.
 *
 * @since 0.1.0
 */
class AnWPFL_Player extends AnWPFL_DB {

	/**
	 * @var AnWP_Football_Leagues
	 */
	protected $plugin = null;

	/**
	 * @var bool
	 */
	public $use_short_name = true;

	/**
	 * Constructor.
	 *
	 * @since  0.1.0
	 * @param AnWP_Football_Leagues $plugin Main plugin object.
	 */
	public function __construct( AnWP_Football_Leagues $plugin ) {
		global $wpdb;

		$this->plugin = $plugin;

		$this->use_short_name = apply_filters( 'anwpfl/player/use_short_name', true );

		$this->primary_key = 'player_id';
		$this->table_name  = $wpdb->anwpfl_player_data;

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
		$permalink_slug      = empty( $permalink_structure['player'] ) ? 'player' : $permalink_structure['player'];

		// Register this CPT.
		$labels = [
			'name'               => _x( 'Players', 'Post type general name', 'anwp-football-leagues' ),
			'singular_name'      => _x( 'Player', 'Post type singular name', 'anwp-football-leagues' ),
			'menu_name'          => _x( 'Players', 'Admin Menu text', 'anwp-football-leagues' ),
			'name_admin_bar'     => _x( 'Player', 'Add New on Toolbar', 'anwp-football-leagues' ),
			'add_new'            => __( 'Add New Player', 'anwp-football-leagues' ),
			'add_new_item'       => __( 'Add New Player', 'anwp-football-leagues' ),
			'new_item'           => __( 'New Player', 'anwp-football-leagues' ),
			'edit_item'          => __( 'Edit Player', 'anwp-football-leagues' ),
			'view_item'          => __( 'View Player', 'anwp-football-leagues' ),
			'all_items'          => __( 'All Players', 'anwp-football-leagues' ),
			'search_items'       => __( 'Search Players', 'anwp-football-leagues' ),
			'not_found'          => __( 'No players found.', 'anwp-football-leagues' ),
			'not_found_in_trash' => __( 'No players found in Trash.', 'anwp-football-leagues' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 34,
			'menu_icon'           => 'dashicons-groups',
			'query_var'           => true,
			'rewrite'             => [ 'slug' => $permalink_slug ],
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'exclude_from_search' => 'hide' === AnWPFL_Options::get_value( 'display_front_end_search_player' ),
			'supports'            => [ 'title', 'comments' ],
		];

		if ( apply_filters( 'anwp-football-leagues/config/cpt_only_admin_access', true ) ) {
			$args['capabilities'] = [
				'edit_post'         => 'manage_options',
				'read_post'         => 'manage_options',
				'delete_post'       => 'manage_options',
				'edit_posts'        => 'manage_options',
				'edit_others_posts' => 'manage_options',
				'delete_posts'      => 'manage_options',
				'publish_posts'     => 'manage_options',
			];
		}

		register_post_type( 'anwp_player', $args );
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {

		// admin UI improvements
		add_filter( 'enter_title_here', [ $this, 'title' ], 10, 2 );
		add_filter( 'manage_edit-anwp_player_sortable_columns', [ $this, 'sortable_columns' ] );
		add_filter( 'manage_edit-anwp_player_columns', [ $this, 'columns' ] );
		add_action( 'manage_anwp_player_posts_custom_column', [ $this, 'columns_display' ], 10, 2 );

		// Create CMB2 metabox
		add_action( 'cmb2_admin_init', [ $this, 'init_cmb2_metaboxes' ] );
		add_action( 'cmb2_before_post_form_anwp_player_metabox', [ $this, 'cmb2_before_metabox' ] );
		add_action( 'cmb2_after_post_form_anwp_player_metabox', [ $this, 'cmb2_after_metabox' ] );

		// Add custom filter
		add_action( 'restrict_manage_posts', [ $this, 'custom_admin_filters' ] );
		add_filter( 'posts_clauses', [ $this, 'modify_query_clauses' ], 20, 2 );

		// Render Custom Content below
		add_action(
			'anwpfl/tmpl-player/after_wrapper',
			function ( $player_id ) {

				$content_below = get_post_meta( $player_id, '_anwpfl_custom_content_below', true );

				if ( trim( $content_below ) ) {
					echo '<div class="anwp-b-wrap mt-4">' . do_shortcode( $content_below ) . '</div>';
				}
			}
		);

		add_action( 'delete_post', [ $this, 'on_player_delete' ] );

		add_action( 'rest_api_init', [ $this, 'add_rest_routes' ] );

		add_action( 'load-post.php', [ $this, 'init_metaboxes' ] );
		add_action( 'load-post-new.php', [ $this, 'init_metaboxes' ] );
		add_action( 'save_post', [ $this, 'save_metabox' ], 10, 2 );

		add_filter( 'cmb2_override_meta_value', [ $this, 'get_cmb2_player_data' ], 10, 4 );

		add_action( 'save_post_anwp_player', [ $this, 'change_player_title_on_inline_save' ], 10, 3 );
	}

	/**
	 * Sync player title on inline save.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 * @param bool    $update
	 */
	public function change_player_title_on_inline_save( int $post_id, WP_Post $post, bool $update ) {
		if ( $update && 'inline-save' === ( $_POST['action'] ?? '' ) && ! empty( $post->post_title ) ) { // phpcs:ignore
			anwp_fl()->player->update( $post_id, [ 'name' => sanitize_text_field( $post->post_title ) ] );
		}
	}

	/**
	 * Fires before removing a post.
	 *
	 * @param int $post_ID Post ID.
	 */
	public function on_player_delete( int $post_ID ) {
		if ( 'anwp_player' === get_post_type( $post_ID ) ) {
			$this->delete( $post_ID );
		}
	}

	/**
	 * Filter CPT title entry placeholder text
	 *
	 * @param  string $title Original placeholder text
	 *
	 * @return string        Modified placeholder text
	 */
	public function title( $title, $post ) {
		if ( isset( $post->post_type ) && 'anwp_player' === $post->post_type ) {
			return esc_html__( 'Player Name', 'anwp-football-leagues' );
		}

		return $title;
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @param array $sortable_columns Array of registered column names/labels.
	 *
	 * @return array          Modified array.
	 */
	public function sortable_columns( $sortable_columns ) {

		return array_merge( $sortable_columns, [ '_fl_player_id' => 'ID' ] );
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @param  array $columns Array of registered column names/labels.
	 *
	 * @return array          Modified array.
	 */
	public function columns( $columns ) {

		// Add new columns
		$new_columns = [
			'_fl_player_position'  => esc_html__( 'Position', 'anwp-football-leagues' ),
			'_fl_player_team_id'   => esc_html__( 'Current Club', 'anwp-football-leagues' ),
			'_fl_player_birthdate' => esc_html__( 'Date of Birth', 'anwp-football-leagues' ),
			'_fl_player_id'        => esc_html__( 'ID', 'anwp-football-leagues' ),
		];

		// Merge old and new columns
		$columns = array_merge( $new_columns, $columns );

		// Change columns order
		$new_columns_order = [
			'cb',
			'title',
			'_fl_player_position',
			'_fl_player_team_id',
			'_fl_player_birthdate',
			'comments',
			'date',
			'_fl_player_id',
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
		global $post;

		switch ( $column ) {
			case '_fl_player_position':
				$position_options = $this->plugin->data->get_positions();

				if ( ! empty( $position_options[ $post->_fl_player_position ] ) ) {
					echo esc_html( $position_options[ $post->_fl_player_position ] );
				}

				break;

			case '_fl_player_team_id':
				$clubs_options = $this->plugin->club->get_clubs_options();

				if ( ! empty( $clubs_options[ $post->_fl_player_team_id ] ) ) {
					echo esc_html( $clubs_options[ $post->_fl_player_team_id ] );
				}

				break;

			case '_fl_player_birthdate':
				echo $post->_fl_player_birthdate && '0000-00-00' !== $post->_fl_player_birthdate ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $post->_fl_player_birthdate ) ) ) : '';
				break;

			case '_fl_player_id':
				echo absint( $post_id );
				break;
		}
	}

	/**
	 * Fires before the Filter button on the Posts and Pages list tables.
	 *
	 * The Filter button allows sorting by date and/or category on the
	 * Posts list table, and sorting by date on the Pages list table.
	 *
	 * @param string $post_type The post type slug.
	 */
	public function custom_admin_filters( $post_type ) {
		if ( 'anwp_player' === $post_type ) {
			$clubs = $this->plugin->club->get_clubs_options();

			// phpcs:ignore WordPress.Security.NonceVerification
			$filter_fl_team_id = $_GET['_fl_team_id'] ?? '';
			ob_start();
			?>

			<select name='_fl_team_id' id='fl-team-filter' class='postform'>
				<option value=''>All Clubs</option>
				<?php foreach ( $clubs as $club_id => $club_title ) : ?>
					<option value="<?php echo esc_attr( $club_id ); ?>" <?php selected( $club_id, absint( $filter_fl_team_id ) ); ?>>
						<?php echo esc_html( $club_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
			echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Handle custom filter.
	 *
	 * Covers the WHERE, GROUP BY, JOIN, ORDER BY, DISTINCT,
	 * fields (SELECT), and LIMIT clauses.
	 *
	 * @param string[] $clauses {
	 *     Associative array of the clauses for the query.
	 *
	 *     @type string $where    The WHERE clause of the query.
	 *     @type string $groupby  The GROUP BY clause of the query.
	 *     @type string $join     The JOIN clause of the query.
	 *     @type string $orderby  The ORDER BY clause of the query.
	 *     @type string $distinct The DISTINCT clause of the query.
	 *     @type string $fields   The SELECT clause of the query.
	 *     @type string $limits   The LIMIT clause of the query.
	 * }
	 *
	 * @param WP_Query $query   The WP_Query instance (passed by reference).
	 */
	public function modify_query_clauses( $clauses, $query ) {
		global $post_type, $pagenow, $wpdb;

		// Check main query in admin
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $clauses;
		}

		if ( 'edit.php' === $pagenow && 'anwp_player' === $post_type ) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->prefix}anwpfl_player_data fl_player ON fl_player.player_id = {$wpdb->posts}.ID";

			$player_fields = [
				'fl_player.position        _fl_player_position',
				'fl_player.team_id         _fl_player_team_id',
				'fl_player.date_of_birth   _fl_player_birthdate',
			];

			$clauses['fields'] .= ',' . implode( ',', $player_fields );

			$get_data = wp_parse_args(
				$_GET, // phpcs:ignore WordPress.Security.NonceVerification
				[
					'_fl_team_id' => '',
				]
			);

			if ( absint( $get_data['_fl_team_id'] ) ) {
				$clauses['where'] .= $wpdb->prepare( ' AND fl_player.team_id = %d ', absint( $get_data['_fl_team_id'] ) );
			}
		}

		return $clauses;
	}

	/**
	 * Get player fields not saved in postmeta
	 *
	 * @since 0.16.0
	 */
	public function get_cmb2_player_data( $initial_value, $post_id, $args, CMB2_Field $cmb_field ) {
		if ( ! empty( $cmb_field->cmb_id ) || 'anwp_player_metabox' !== $cmb_field->cmb_id ) {
			return $initial_value;
		}

		global $wpdb;

		$field_name = str_replace( '_anwpfl_', '', $args['field_id'] );

		$available_fields = [
			'country_of_birth',
			'date_of_birth',
			'date_of_death',
			'full_name',
			'height',
			'national_team',
			'nationality',
			'photo',
			'photo_id',
			'place_of_birth',
			'player_external_id',
			'position',
			'short_name',
			'team_id',
			'weight',
		];

		if ( ! in_array( $field_name, $available_fields, true ) ) {
			return $initial_value;
		}

		static $player_data = null;

		if ( null === $player_data ) {
			$player_data = $wpdb->get_row(
				$wpdb->prepare(
					"
					SELECT *
					FROM {$wpdb->prefix}anwpfl_player_data
					WHERE player_id = %d
					",
					$post_id
				),
				ARRAY_A
			);

			if ( empty( $player_data ) ) {
				return $initial_value;
			}

			$player_data['nationalities'] = empty( $player_data['nationality'] ) ? [] : [ $player_data['nationality'] ];

			if ( ! empty( $player_data['nationality_extra'] ) ) {
				$player_data['nationalities'] = array_merge( $player_data['nationalities'], explode( '%', trim( $player_data['nationality_extra'], '%' ) ) );
			}
		}

		if ( 'photo_id' === $field_name && ! empty( $player_data['photo'] ) ) {
			return attachment_url_to_postid( wp_upload_dir()['baseurl'] . $player_data['photo'] ) ?: $initial_value;
		} elseif ( 'photo' === $field_name && ! empty( $player_data['photo'] ) ) {
			return wp_upload_dir()['baseurl'] . $player_data['photo'];
		} elseif ( 'date_of_death' === $field_name ) {
			return '0000-00-00' === $player_data['date_of_death'] ? '' : $player_data['date_of_death'];
		} elseif ( 'date_of_birth' === $field_name ) {
			return '0000-00-00' === $player_data['date_of_birth'] ? '' : $player_data['date_of_birth'];
		} elseif ( 'nationality' === $field_name ) {
			return empty( $player_data['nationalities'] ) ? [] : $player_data['nationalities'];
		}

		return $player_data[ $field_name ] ?? $initial_value;
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

				if ( 'anwp_player' === $post_type ) {
					add_meta_box(
						'anwpfl_player_manual_stats',
						esc_html__( 'Manual Statistics', 'anwp-football-leagues' ),
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
	 */
	public function render_metabox( WP_Post $post ) {

		// Add nonce for security and authentication.
		wp_nonce_field( 'anwp_save_metabox_' . $post->ID, 'anwp_metabox_nonce' );

		$l10n = [
			'add_new_record'       => esc_html__( 'Add New Record', 'anwp-football-leagues' ),
			'notice_max'           => esc_html__( 'the maximum value is 65535', 'anwp-football-leagues' ),
			'notice_1'             => esc_html__( 'Season and Competition are required fields. The data will not be saved if you do not fill them.', 'anwp-football-leagues' ),
			'notice_2'             => esc_html__( 'You can select an existing Competition or add a text title of a new one without creation.', 'anwp-football-leagues' ),
			'notice_3'             => __( '"Goals Conceded" and "Clean Sheets" - for goalkeepers only', 'anwp-football-leagues' ),
			'competition'          => esc_html__( 'Competition', 'anwp-football-leagues' ),
			'season'               => esc_html__( 'Season', 'anwp-football-leagues' ),
			'played_matches'       => esc_html__( 'Played Matches', 'anwp-football-leagues' ),
			'started'              => esc_html__( 'Started', 'anwp-football-leagues' ),
			'substituted_in'       => esc_html__( 'Substituted In', 'anwp-football-leagues' ),
			'minutes'              => esc_html__( 'Minutes', 'anwp-football-leagues' ),
			'card_y'               => esc_html__( 'Yellow Cards', 'anwp-football-leagues' ),
			'card_yr'              => __( '2d Yellow > Red Cards', 'anwp-football-leagues' ),
			'card_r'               => esc_html__( 'Red Cards', 'anwp-football-leagues' ),
			'goals'                => esc_html__( 'Goals', 'anwp-football-leagues' ),
			'goals_penalty'        => esc_html__( 'Goals from penalty', 'anwp-football-leagues' ),
			'assists'              => esc_html__( 'Assists', 'anwp-football-leagues' ),
			'own_goals'            => esc_html__( 'Own Goals', 'anwp-football-leagues' ),
			'goals_conceded'       => esc_html__( 'Goals Conceded', 'anwp-football-leagues' ),
			'clean_sheets'         => esc_html__( 'Clean Sheets', 'anwp-football-leagues' ),
			'select_season'        => esc_html__( 'select season', 'anwp-football-leagues' ),
			'new_competition'      => esc_html__( 'New Competition', 'anwp-football-leagues' ),
			'existing_competition' => esc_html__( 'Existing Competition', 'anwp-football-leagues' ),
		];

		$app_data = [
			'l10n'              => anwp_fl()->helper->recursive_entity_decode( $l10n ),
			'statsData'         => $this->get_manual_stats( get_the_ID() ),
			'seasons_list'      => $this->plugin->season->get_seasons_list(),
			'competitions_list' => anwp_fl()->competition->get_competitions(),
		];

		/**
		 * Modify App data
		 *
		 * @since 0.13.7
		 */
		$app_data = apply_filters( 'anwpfl/player/manual_stats_app_data', $app_data );
		?>
		<script type="text/javascript">
			window.anwpPlayerManualData = <?php echo wp_json_encode( $app_data ); ?>;
		</script>
		<div class="anwp-b-wrap">
			<div class="p-3">
				<div id="fl-app-player-manual-stats"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int     $post_id The ID of the post being saved.
	 * @param WP_Post $post_obj
	 *
	 * @since  0.13.7
	 * @return int
	 */
	public function save_metabox( int $post_id, WP_Post $post_obj ): int {

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
		if ( 'anwp_player' !== $_POST['post_type'] ) {
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

		$post_data = wp_unslash( $_POST );

		/* OK, it's safe for us to save the data now. */
		global $wpdb;

		/*
		|--------------------------------------------------------------------
		| Create player record if not exists
		|--------------------------------------------------------------------
		*/
		if ( empty( $this->get_player_data( $post_id ) ) || absint( $post_id ) !== absint( $this->get_player_data( $post_id )['player_id'] ) ) {
			$wpdb->insert( $wpdb->anwpfl_player_data, [ 'player_id' => $post_id ] );
		}

		/*
		|--------------------------------------------------------------------
		| Prepare non-standard data
		|--------------------------------------------------------------------
		*/
		$nationality     = '';
		$nationality_ext = '';
		$nationality_raw = $post_data['_anwpfl_nationality'] ?? [];

		if ( is_array( $nationality_raw ) && count( $nationality_raw ) ) {
			$nationality = array_shift( $nationality_raw );

			if ( count( $nationality_raw ) ) {
				$nationality_ext = '%' . implode( '%', $nationality_raw ) . '%';
			}
		}

		$photo = $post_data['_anwpfl_photo'] ?? '';

		if ( ! empty( $photo ) ) {
			$photo = str_ireplace( wp_make_link_relative( wp_upload_dir()['baseurl'] ), '', wp_make_link_relative( $photo ) );
		}

		/*
		|--------------------------------------------------------------------
		| Insert Data
		|--------------------------------------------------------------------
		*/
		$update_data = [
			'name'               => $post_data['post_title'] ?? '',
			'short_name'         => $post_data['_anwpfl_short_name'] ?? '',
			'full_name'          => $post_data['_anwpfl_full_name'] ?? '',
			'weight'             => $post_data['_anwpfl_weight'] ?? '',
			'height'             => $post_data['_anwpfl_height'] ?? '',
			'position'           => $post_data['_anwpfl_position'] ?? '',
			'team_id'            => $post_data['_anwpfl_team_id'] ?? 0,
			'national_team'      => $post_data['_anwpfl_national_team'] ?? 0,
			'nationality'        => $nationality,
			'nationality_extra'  => $nationality_ext,
			'place_of_birth'     => $post_data['_anwpfl_place_of_birth'] ?? '',
			'country_of_birth'   => $post_data['_anwpfl_country_of_birth'] ?? '',
			'date_of_birth'      => $post_data['_anwpfl_date_of_birth'] ?? '0000-00-00',
			'date_of_death'      => $post_data['_anwpfl_date_of_death'] ?? '0000-00-00',
			'player_external_id' => $post_data['_anwpfl_player_external_id'] ?? '',
			'photo'              => $photo,
		];

		$this->update( $post_id, $update_data );

		/*
		|--------------------------------------------------------------------
		| Save Player's Manual Stats
		|--------------------------------------------------------------------
		*/
		if ( ! empty( $_POST['_anwpfl_player_manual_data'] ) ) {
			$this->update_manual_stats( $post_id, json_decode( wp_unslash( $_POST['_anwpfl_player_manual_data'] ) ) );
		}

		/**
		 * Trigger on save player's data.
		 *
		 * @param array $post_id
		 * @param array $_POST
		 *
		 * @since 0.13.7
		 */
		do_action( 'anwpfl/player/on_save', $post_id, $_POST );

		return $post_id;
	}

	/**
	 * Update Player's manual stats
	 *
	 * @since 0.13.7
	 */
	public function update_manual_stats( $player_id, $manual_data ) {

		global $wpdb;

		$stats_table = $wpdb->prefix . 'anwpfl_players_manual_stats';

		// Remove old stats
		$wpdb->delete( $stats_table, [ 'player_id' => $player_id ] );

		$table_fields = [
			'played',
			'started',
			'sub_in',
			'minutes',
			'card_y',
			'card_yr',
			'card_r',
			'goals',
			'goals_penalty',
			'assists',
			'own_goals',
			'goals_conceded',
			'clean_sheets',
		];

		foreach ( $manual_data as $data_row ) {

			if ( empty( $data_row->competition_type ) || ! in_array( $data_row->competition_type, [ 'new', 'id' ], true ) ) {
				continue;
			}

			if ( empty( $data_row->season_id ) || ! absint( $data_row->season_id ) ) {
				continue;
			}

			$data_to_insert = [
				'player_id'        => $player_id,
				'season_id'        => absint( $data_row->season_id ),
				'competition_id'   => '',
				'competition_text' => '',
				'competition_type' => $data_row->competition_type,
			];

			if ( 'new' === $data_row->competition_type ) {
				if ( empty( $data_row->competition_text ) ) {
					continue;
				}

				$data_to_insert['competition_text'] = sanitize_text_field( $data_row->competition_text );
			} else {
				if ( empty( $data_row->competition_id ) || ! absint( $data_row->competition_id ) ) {
					continue;
				}

				$data_to_insert['competition_id'] = absint( $data_row->competition_id );
			}

			foreach ( $table_fields as $table_field ) {
				$data_to_insert[ $table_field ] = isset( $data_row->{$table_field} ) ? absint( $data_row->{$table_field} ) : 0;
			}

			$wpdb->insert( $stats_table, $data_to_insert );
		}
	}

	/**
	 * Update Player's manual stats
	 *
	 * @param int $player_id
	 * @param int $season_id
	 *
	 * @since 0.13.7
	 *@return array
	 */
	public function get_manual_stats( $player_id, $season_id = 0 ) {

		global $wpdb;

		if ( ! absint( $player_id ) ) {
			return [];
		}

		$query  = 'SELECT `season_id`, `competition_id`, `competition_text`, `competition_type`, `played`, `started`, `sub_in`, `minutes`, `card_y`, `card_yr`, `card_r`, `goals`, `goals_penalty`, `assists`, `own_goals`, `goals_conceded`, `clean_sheets` ';
		$query .= "FROM {$wpdb->prefix}anwpfl_players_manual_stats ";

		$query .= $wpdb->prepare( 'WHERE player_id = %d ', $player_id );

		if ( absint( $season_id ) ) {
			$query .= $wpdb->prepare( ' AND season_id = %d ', $season_id );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $query );

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return [];
		}

		$table_fields = [
			'played',
			'started',
			'sub_in',
			'minutes',
			'card_y',
			'card_yr',
			'card_r',
			'goals',
			'goals_penalty',
			'assists',
			'own_goals',
			'goals_conceded',
			'clean_sheets',
		];

		foreach ( $rows as $row_index => $row ) {

			$row->id             = $row_index + 1;
			$row->season_id      = absint( $row->season_id );
			$row->competition_id = absint( $row->competition_id ) ?: '';

			foreach ( $table_fields as $table_field ) {
				$row->{$table_field} = absint( $row->{$table_field} ) ?: '';
			}
		}

		return $rows;
	}

	/**
	 * Register REST routes.
	 *
	 * @since 0.12.3
	 */
	public function add_rest_routes() {

		register_rest_route(
			'anwpfl/player',
			'/update-player-current-club/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'update_player_current_club' ],
				'permission_callback' => [ anwp_football_leagues()->helper, 'update_permissions_check' ],
			]
		);

		register_rest_route(
			'anwpfl/player',
			'/add-player-to-squad/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'add_player_to_squad' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' ); // ToDo check with update_permissions_check
				},
			]
		);

		register_rest_route(
			'anwpfl/player',
			'/get-player-data/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_player_actions_data' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' ); // ToDo check with update_permissions_check
				},
			]
		);
	}

	/**
	 * Get Player Data
	 *
	 * @param WP_REST_Request $request
	 *
	 * @since 0.12.6
	 * @return WP_REST_Response | WP_Error
	 */
	public function get_player_actions_data( WP_REST_Request $request ) {

		$params    = $request->get_params();
		$player_id = isset( $params['player_id'] ) ? absint( $params['player_id'] ) : '';

		if ( empty( $player_id ) ) {
			return new WP_Error( 'rest_anwp_fl_error', 'Invalid Player ID', [ 'status' => 400 ] );
		}

		$player = $this->get_player_data( $player_id );

		$player_data = [
			'current_club' => absint( $player['team_id'] ) ? ( anwp_fl()->club->get_club_title_by_id( $player['team_id'] ) . ' (ID: ' . $player['team_id'] . ')' ) : ' - ',
		];

		$player_data = apply_filters( 'anwpfl/player/player_actions_data', $player_data, $params );

		return rest_ensure_response(
			[
				'result'      => true,
				'player_data' => $player_data,
			]
		);
	}

	/**
	 * Update Player Current Club
	 *
	 * @param WP_REST_Request $request
	 *
	 * @since 0.12.3
	 * @return WP_REST_Response | WP_Error
	 */
	public function update_player_current_club( WP_REST_Request $request ) {
		global $wpdb;

		$params = $request->get_params();

		$player_id = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : '';
		$club_id   = isset( $params['club_id'] ) ? absint( $params['club_id'] ) : '';

		if ( empty( $player_id ) || empty( $club_id ) ) {
			return new WP_Error( 'rest_anwp_fl_error', 'Invalid Data', [ 'status' => 400 ] );
		}

		$saved_current_club = $this->get_player_data( $player_id )['team_id'] ?? '';

		if ( (int) $saved_current_club === $club_id ) {
			return rest_ensure_response( [ 'result' => true ] );
		}

		if ( ! $wpdb->update( $wpdb->prefix . 'anwpfl_player_data', [ 'team_id' => $club_id ], [ 'player_id' => $player_id ] ) ) {
			return new WP_Error( 'rest_anwp_fl_error', 'Save error', [ 'status' => 400 ] );
		}

		return rest_ensure_response( [ 'result' => true ] );
	}

	/**
	 * Add Player to Squad
	 *
	 * @param WP_REST_Request $request
	 *
	 * @since 0.12.3
	 *@return WP_REST_Response | WP_Error
	 */
	public function add_player_to_squad( WP_REST_Request $request ) {

		$params = $request->get_params();

		$player_id = absint( $params['player_id'] ?? 0 );
		$club_id   = absint( $params['club_id'] ?? 0 );
		$season_id = absint( $params['season_id'] ?? 0 );

		if ( ! $club_id || ! $player_id || ! $season_id ) {
			return rest_ensure_response( [ 'result' => false ] );
		}

		$season_slug = 's:' . $season_id;
		$club_squad  = json_decode( get_post_meta( $club_id, '_anwpfl_squad', true ) );

		if ( ! $club_squad ) {
			$club_squad = (object) [];
		}

		$squad_players = $club_squad->{$season_slug} ?? [];

		if ( ! empty( wp_list_filter( $squad_players, [ 'id' => $player_id ] ) ) ) {
			return rest_ensure_response( [ 'result' => true ] );
		}

		$squad_players[] = (object) [
			'id'       => $player_id,
			'position' => $this->get_player_data( $player_id )['position'] ?? '',
			'number'   => '',
			'status'   => '',
		];

		/*
		|--------------------------------------------------------------------
		| Save Club Squad
		|--------------------------------------------------------------------
		*/
		// Update club slug with new data
		$club_squad->{$season_slug} = $squad_players;

		// Save squad
		if ( ! update_post_meta( $club_id, '_anwpfl_squad', wp_slash( wp_json_encode( $club_squad ) ) ) ) {
			return new WP_Error( 'rest_anwp_fl_error', 'Save error', [ 'status' => 400 ] );
		}

		return rest_ensure_response( [ 'result' => true ] );
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
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-general-player_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-gear"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'General', 'anwp-football-leagues' ); ?></span>
					</div>
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-media-player_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-device-camera"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Media', 'anwp-football-leagues' ); ?></span>
					</div>
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-desc-player_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-note"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Bio', 'anwp-football-leagues' ); ?></span>
					</div>
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-social-club_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-repo-forked"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Social', 'anwp-football-leagues' ); ?></span>
					</div>
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-custom_fields-player_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-server"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Custom Fields', 'anwp-football-leagues' ); ?></span>
					</div>
					<div class="p-3 anwp-metabox-tabs__control-item" data-target="#anwp-tabs-bottom_content-player_metabox">
						<svg class="anwp-icon anwp-icon--octi d-inline-block"><use xlink:href="#icon-repo-push"></use></svg>
						<span class="d-block"><?php echo esc_html__( 'Bottom Content', 'anwp-football-leagues' ); ?></span>
					</div>
					<?php
					/**
					 * Fires in the bottom of player tabs.
					 * Add new tabs here.
					 *
					 * @since 0.9.0
					 */
					do_action( 'anwpfl/cmb2_tabs_control/player' );
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
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		// @formatter:on
	}

	/**
	 * Create CMB2 metaboxes
	 *
	 * @since 0.2.0 (2018-01-05)
	 */
	public function init_cmb2_metaboxes() {

		// Start with an underscore to hide fields from custom fields list
		$prefix = '_anwpfl_';

		/**
		 * Initiate the metabox
		 */
		$cmb = new_cmb2_box(
			[
				'id'           => 'anwp_player_metabox',
				'title'        => esc_html__( 'Player Info', 'anwp-football-leagues' ),
				'object_types' => [ 'anwp_player' ],
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
				'save_field' => false,
				'before_row' => '<div id="anwp-tabs-general-player_metabox" class="anwp-metabox-tabs__content-item">',
			]
		);

		// Full Name
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Full Name', 'anwp-football-leagues' ),
				'id'         => $prefix . 'full_name',
				'save_field' => false,
				'type'       => 'text',
			]
		);

		// Weight
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Weight (kg)', 'anwp-football-leagues' ),
				'id'         => $prefix . 'weight',
				'save_field' => false,
				'type'       => 'text',
			]
		);

		// Height
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Height (cm)', 'anwp-football-leagues' ),
				'id'         => $prefix . 'height',
				'save_field' => false,
				'type'       => 'text',
			]
		);

		$cmb->add_field(
			[
				'name'             => esc_html__( 'Position', 'anwp-football-leagues' ),
				'id'               => $prefix . 'position',
				'type'             => 'select',
				'save_field'       => false,
				'show_option_none' => esc_html__( '- not selected -', 'anwp-football-leagues' ),
				'options_cb'       => [ $this->plugin->data, 'get_positions' ],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Current Club', 'anwp-football-leagues' ),
				'id'         => $prefix . 'team_id',
				'save_field' => false,
				'options_cb' => [ $this->plugin->club, 'get_clubs_options' ],
				'type'       => 'anwp_fl_select',
				'attributes' => [
					'placeholder' => esc_html__( '- not selected -', 'anwp-football-leagues' ),
				],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'National Team', 'anwp-football-leagues' ),
				'id'         => $prefix . 'national_team',
				'save_field' => false,
				'options_cb' => [ $this->plugin->club, 'get_national_team_options' ],
				'type'       => 'anwp_fl_select',
				'attributes' => [
					'placeholder' => esc_html__( '- not selected -', 'anwp-football-leagues' ),
				],
			]
		);

		// Place of Birth
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Place of Birth', 'anwp-football-leagues' ),
				'id'         => $prefix . 'place_of_birth',
				'type'       => 'text',
				'save_field' => false,
			]
		);

		// Country of Birth
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Country of Birth', 'anwp-football-leagues' ),
				'id'         => $prefix . 'country_of_birth',
				'type'       => 'anwp_fl_select',
				'options_cb' => [ $this->plugin->data, 'cb_get_countries' ],
				'save_field' => false,
			]
		);

		// Date of Birth
		$cmb->add_field(
			[
				'name'        => esc_html__( 'Date of Birth', 'anwp-football-leagues' ),
				'id'          => $prefix . 'date_of_birth',
				'type'        => 'text_date',
				'date_format' => 'Y-m-d',
				'save_field'  => false,
			]
		);

		// Date of death
		$cmb->add_field(
			[
				'name'        => esc_html__( 'Date of death', 'anwp-football-leagues' ),
				'id'          => $prefix . 'date_of_death',
				'type'        => 'text_date',
				'date_format' => 'Y-m-d',
				'save_field'  => false,
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Nationality', 'anwp-football-leagues' ),
				'id'         => $prefix . 'nationality',
				'type'       => 'anwp_fl_multiselect',
				'options_cb' => [ $this->plugin->data, 'cb_get_countries' ],
				'save_field' => false,
			]
		);

		$cmb->add_field(
			[
				'name'        => esc_html__( 'External ID', 'anwp-football-leagues' ),
				'id'          => $prefix . 'player_external_id',
				'type'        => 'text',
				'description' => esc_html__( 'Used on Data Import', 'anwp-football-leagues' ),
				'after_row'   => '</div>',
				'save_field'  => false,
			]
		);

		$cmb->add_field(
			[
				'name'            => esc_html__( 'Description', 'anwp-football-leagues' ),
				'id'              => $prefix . 'description',
				'type'            => 'wysiwyg',
				'options'         => [
					'wpautop'       => true,
					'media_buttons' => true,
					'textarea_name' => 'anwp_player_description_input',
					'textarea_rows' => 10,
					'teeny'         => false, // output the minimal editor config used in Press This
					'dfw'           => false, // replace the default fullscreen with DFW (needs specific css)
					'tinymce'       => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
					'quicktags'     => true, // load Quicktags, can be used to pass settings directly to Quicktags using an array()
				],
				'show_names'      => false,
				'sanitization_cb' => [ anwp_fl()->helper, 'sanitize_cmb2_fl_text' ],
				'before_row'      => '<div id="anwp-tabs-desc-player_metabox" class="anwp-metabox-tabs__content-item d-none">',
				'after_row'       => '</div>',
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
				'before_row'   => '<div id="anwp-tabs-media-player_metabox" class="anwp-metabox-tabs__content-item d-none">',
				'type'         => 'file',
				'options'      => [
					'url' => false, // Hide the text input for the url
				],
				// query_args are passed to wp.media's library query.
				'query_args'   => [
					'type' => 'image',
				],
				'save_field'   => false,
				'preview_size' => 'medium', // Image size to use when previewing in the admin.
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
				'after_row' => '</div>',
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
				'before_row' => '<div id="anwp-tabs-social-club_metabox" class="anwp-metabox-tabs__content-item d-none">',
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
				'after_row' => '</div>',
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
				'before_row' => '<div id="anwp-tabs-custom_fields-player_metabox" class="anwp-metabox-tabs__content-item d-none">',
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
				'option_slug' => 'player_custom_fields',
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
				'before_row' => '<div id="anwp-tabs-bottom_content-player_metabox" class="anwp-metabox-tabs__content-item d-none">',
				'after_row'  => '</div>',
			]
		);

		/**
		 * Adds extra fields to the metabox.
		 *
		 * @since 0.9.0
		 */
		$extra_fields = apply_filters( 'anwpfl/cmb2_tabs_content/player', [] );

		if ( ! empty( $extra_fields ) && is_array( $extra_fields ) ) {
			foreach ( $extra_fields as $field ) {
				$cmb->add_field( $field );
			}
		}
	}


	/**
	 * Fetches players ids from match data and cache them.
	 *
	 * @param array $game_data
	 *
	 * @since 0.16.0
	 * @return array
	 */
	public function get_game_players( array $game_data ):array {
		$ids = [];

		// Parse events
		if ( ! empty( $game_data['parsed_events'] ) && is_array( $game_data['parsed_events'] ) ) {
			foreach ( $game_data['parsed_events'] as $event_group_slug => $event_group ) {

				if ( 'players' === $event_group_slug ) {
					continue;
				}

				if ( ! empty( $event_group ) && is_array( $event_group ) ) {
					foreach ( $event_group as $event ) {
						if ( ! empty( $event->player ) ) {
							$ids[] = $event->player;
						}

						if ( ! empty( $event->assistant ) ) {
							$ids[] = $event->assistant;
						}

						if ( ! empty( $event->playerOut ) ) { // phpcs:ignore
							$ids[] = $event->playerOut; // phpcs:ignore
						}
					}
				}
			}
		}

		// Parse lineups and substitutes
		$fields = [ 'home_line_up', 'away_line_up', 'home_subs', 'away_subs' ];

		foreach ( $fields as $field ) {
			if ( ! empty( $game_data[ $field ] ) ) {
				$ids = array_merge( $ids, wp_parse_id_list( $game_data[ $field ] ) );
			}
		}

		$ids = wp_parse_id_list( $ids );

		return empty( $ids ) ? [] : $this->get_players_by_ids( $ids );
	}

	/**
	 * Get players by IDs
	 *
	 * @param array $ids
	 * @param bool  $with_permalink
	 *
	 * @since 0.16.0
	 * @return array
	 */
	public function get_players_by_ids( array $ids, bool $with_permalink = true ):array {
		global $wpdb;

		if ( empty( $ids ) ) {
			return [];
		}

		$placeholders = array_fill( 0, count( $ids ), '%s' );
		$format       = implode( ', ', $placeholders );

		$players = $wpdb->get_results(
		// phpcs:disable
			$wpdb->prepare(
				"
					SELECT *
					FROM {$wpdb->prefix}anwpfl_player_data
					WHERE player_id IN ({$format})
					",
				$ids
			)
		// phpcs:enable
		);

		$output   = [];
		$link_map = $with_permalink ? $this->plugin->helper->get_permalinks_by_ids( $ids, 'anwp_player' ) : [];

		foreach ( $players as $player ) {
			$output[ $player->player_id ]                  = (array) $player;
			$output[ $player->player_id ]['link']          = $link_map[ $player->player_id ] ?? '';
			$output[ $player->player_id ]['date_of_birth'] = '0000-00-00' === $output[ $player->player_id ]['date_of_birth'] ? '' : $output[ $player->player_id ]['date_of_birth'];
			$output[ $player->player_id ]['date_of_death'] = '0000-00-00' === $output[ $player->player_id ]['date_of_death'] ? '' : $output[ $player->player_id ]['date_of_death'];
			$output[ $player->player_id ]['short_name']    = $this->use_short_name && $player->short_name ? $player->short_name : $player->name;
			$output[ $player->player_id ]['nationalities'] = empty( $player->nationality ) ? [] : [ $player->nationality ];

			if ( ! empty( $player->nationality_extra ) ) {
				$output[ $player->player_id ]['nationalities'] = array_merge( $output[ $player->player_id ]['nationalities'], explode( '%', trim( $player->nationality_extra, '%' ) ) );
			}
		}

		return $output;
	}

	/**
	 * Get player data.
	 *
	 * @param int  $player_id
	 * @param bool $with_permalink
	 *
	 * @since 0.16.0
	 * @return array = [
	 *       'country_of_birth' => 'tr',
	 *       'date_of_birth' => '2004-01-20',
	 *       'date_of_death' => '0000-00-00',
	 *       'full_name' => 'John Doe',
	 *       'height' => '181',
	 *       'link' => "http://fl-1.test/player/b-ince",
	 *       'name' => 'John Doe',
	 *       'national_team' => 0,
	 *       'nationalities' => [ 'de', 'nl' ],
	 *       'nationality' => 'tr', // main nationality (country code)
	 *       'nationality_extra' => '%de%nl%', // country codes separated by %
	 *       'photo' => '/2022/07/128903.jpg', // relative to uploads folder
	 *       'photo_sm' => '',
	 *       'place_of_birth' => 'Manisa',
	 *       'player_external_id' => '',
	 *       'player_id' => 0,
	 *       'position' => 'm',
	 *       'position' => 'm',
	 *       'short_name' => 'J. Doe',
	 *       'team_id' => 12211,
	 *       'weight' => '70',
	 *   ]
	 */
	public function get_player_data( int $player_id, bool $with_permalink = false ): array {
		global $wpdb;

		if ( ! absint( $player_id ) ) {
			return [];
		}

		static $players = [];

		if ( ! empty( $players[ $player_id ] ) ) {
			return $players[ $player_id ];
		}

		$player_data = $wpdb->get_row(
			$wpdb->prepare(
				"
					SELECT *
					FROM {$wpdb->prefix}anwpfl_player_data
					WHERE player_id = %d
					",
				$player_id
			),
			ARRAY_A
		);

		if ( empty( $player_data ) ) {
			return [];
		}

		$player_data['link']          = $with_permalink ? get_permalink( $player_id ) : '';
		$player_data['short_name']    = $this->use_short_name && $player_data['short_name'] ? $player_data['short_name'] : $player_data['name'];
		$player_data['nationalities'] = empty( $player_data['nationality'] ) ? [] : [ $player_data['nationality'] ];

		if ( ! empty( $player_data['nationality_extra'] ) ) {
			$player_data['nationalities'] = array_merge( $player_data['nationalities'], explode( '%', trim( $player_data['nationality_extra'], '%' ) ) );
		}

		$players[ $player_id ] = $player_data;

		return $players[ $player_id ];
	}

	/**
	 * Get position translation
	 *
	 * @param $position_code
	 *
	 * @since 0.10.19
	 * @return string
	 */
	public function get_position_l10n( $position_code ): string {
		if ( empty( $position_code ) ) {
			return '';
		}

		static $position_map = null;

		if ( null === $position_map ) {
			$translated_map = [
				'g' => anwp_fl()->get_option_value( 'text_single_goalkeeper' ),
				'd' => anwp_fl()->get_option_value( 'text_single_defender' ),
				'm' => anwp_fl()->get_option_value( 'text_single_midfielder' ),
				'f' => anwp_fl()->get_option_value( 'text_single_forward' ),
			];

			foreach ( anwp_fl()->data->get_positions() as $pos_code => $position_name ) {
				$position_map[ $pos_code ] = $translated_map[ $pos_code ] ?: $position_name;
			}
		}

		return $position_map[ $position_code ] ?? '';
	}

	/**
	 * Get players and staff with upcoming birthdays.
	 *
	 * @param array $options
	 *
	 * @since 0.10.19
	 * @return array
	 */
	public function get_birthdays( array $options ): array {

		$cur_date = apply_filters( 'anwpfl/config/localize_date_arg', true ) ? date_i18n( 'Y-m-d' ) : date( 'Y-m-d' );

		/*
		|--------------------------------------------------------------------
		| Try to get from cache
		|--------------------------------------------------------------------
		*/
		$cache_key = 'FL-PLAYER_get_birthdays__' . $cur_date . '-' . md5( maybe_serialize( $options ) );

		if ( false !== anwp_fl()->cache->get( $cache_key, 'anwp_player', false ) ) {
			return anwp_fl()->cache->get( $cache_key, 'anwp_player' );
		}

		// Load data in default way
		global $wpdb;

		$options = wp_parse_args(
			$options,
			[
				'club_id'     => '',
				'type'        => 'players',
				'days_before' => 5,
				'days_after'  => 3,
			]
		);

		$hide_dead_players = apply_filters( 'anwpfl/player/birthdays_hide_dead', true );

		/*
		|--------------------------------------------------------------------
		| Get Staff
		|--------------------------------------------------------------------
		*/
		$staff_list = [];

		if ( 'all' === $options['type'] || 'staff' === $options['type'] ) {
			$query = "
			SELECT p.ID, pm2.meta_value current_club, pm1.meta_value date_of_birth, p.post_title player_name, DATE_FORMAT( pm1.meta_value, '%m-%d' ) meta_date_short
			FROM $wpdb->posts p
			LEFT JOIN $wpdb->postmeta pm1 ON ( pm1.post_id = p.ID AND pm1.meta_key = '_anwpfl_date_of_birth' )
			LEFT JOIN $wpdb->postmeta pm2 ON ( pm2.post_id = p.ID AND pm2.meta_key = '_anwpfl_current_club' )
			LEFT JOIN $wpdb->postmeta pm3 ON ( pm3.post_id = p.ID AND pm3.meta_key = '_anwpfl_date_of_death' )
			WHERE p.post_status = 'publish' AND pm1.meta_value IS NOT NULL AND pm1.meta_value != '' AND p.post_type = 'anwp_staff' 
			";

			// show or hide dead
			if ( $hide_dead_players ) {
				$query .= ' AND pm3.meta_value IS NULL ';
			}

			if ( absint( $options['club_id'] ) ) {
				$clubs  = wp_parse_id_list( $options['club_id'] );
				$format = implode( ', ', array_fill( 0, count( $clubs ), '%d' ) );

				$query .= $wpdb->prepare( " AND pm2.meta_value IN ({$format}) ", $clubs ); // phpcs:ignore
			}

			// filter by date
			$query .= $wpdb->prepare( ' AND pm1.meta_value >= DATE_SUB( DATE_SUB( %s, INTERVAL YEAR( %s ) - YEAR( pm1.meta_value ) YEAR ), INTERVAL %d DAY )', $cur_date, $cur_date, $options['days_before'] );
			$query .= $wpdb->prepare( ' AND pm1.meta_value <= DATE_ADD( DATE_SUB( %s, INTERVAL YEAR( %s ) - YEAR( pm1.meta_value ) YEAR ), INTERVAL %d DAY )', $cur_date, $cur_date, $options['days_after'] );

			$query .= ' GROUP BY p.ID';
			$query .= ' ORDER BY meta_date_short';

			// bump query
			$staff_list = $wpdb->get_results( $query ) ? : []; // phpcs:ignore WordPress.DB.PreparedSQL

			foreach ( $staff_list as $staff_row ) {
				$staff_position_map = anwp_fl()->staff->get_positions_map();
				$staff_photo_map    = anwp_fl()->staff->get_staff_photo_map();

				$staff_row->position = $staff_position_map[ $staff_row->ID ] ?? '';
				$staff_row->photo    = $staff_photo_map[ $staff_row->ID ] ?? '';
			}
		}

		/*
		|--------------------------------------------------------------------
		| Get Players
		|--------------------------------------------------------------------
		*/
		$player_list = [];

		if ( 'all' === $options['type'] || 'players' === $options['type'] || 'player' === $options['type'] ) {
			$query = "
			SELECT player_id as ID, date_of_birth, `name` as player_name, DATE_FORMAT( date_of_birth, '%m-%d' ) meta_date_short, `photo`, `position`, team_id as current_club
			FROM $wpdb->anwpfl_player_data
			WHERE date_of_birth != '0000-00-00'
			";

			// show or hide dead
			if ( $hide_dead_players ) {
				$query .= ' AND date_of_death = "0000-00-00" ';
			}

			if ( absint( $options['club_id'] ) ) {
				$clubs  = wp_parse_id_list( $options['club_id'] );
				$format = implode( ', ', array_fill( 0, count( $clubs ), '%d' ) );

				$query .= $wpdb->prepare( " AND team_id IN ({$format}) ", $clubs ); // phpcs:ignore
			}

			// filter by date
			$query .= $wpdb->prepare( ' AND date_of_birth >= DATE_SUB( DATE_SUB( %s, INTERVAL YEAR( %s ) - YEAR( date_of_birth ) YEAR ), INTERVAL %d DAY )', $cur_date, $cur_date, $options['days_before'] );
			$query .= $wpdb->prepare( ' AND date_of_birth <= DATE_ADD( DATE_SUB( %s, INTERVAL YEAR( %s ) - YEAR( date_of_birth ) YEAR ), INTERVAL %d DAY )', $cur_date, $cur_date, $options['days_after'] );

			// bump query
			$query      .= ' ORDER BY meta_date_short';
			$player_list = $wpdb->get_results( $query ) ?: []; // phpcs:ignore WordPress.DB.PreparedSQL

			foreach ( $player_list as $player_row ) {
				$player_row->photo    = $player_row->photo ? anwp_fl()->upload_dir . $player_row->photo : '';
				$player_row->position = anwp_fl()->player->get_position_l10n( $player_row->position );
			}
		}

		/*
		|--------------------------------------------------------------------
		| Save transient
		|--------------------------------------------------------------------
		*/
		$output = wp_list_sort( array_merge( $player_list, $staff_list ), 'meta_date_short' );
		anwp_fl()->cache->set( $cache_key, $output, 'anwp_player' );

		return $output;
	}

	/**
	 * Method returns players with id and title.
	 * Used in admin Squad assigning.
	 *
	 * @param array $squad_position_map
	 *
	 * @since 0.16.0
	 * @return array
	 */
	public function get_player_obj_list( array $squad_position_map = [] ): array {
		global $wpdb;

		$all_players = $wpdb->get_results(
			"
			SELECT `player_id` as id, `name` as name, `short_name`, `team_id` as club_id, `position`, `nationality` as country, `date_of_birth` as birthdate, `photo`
			FROM $wpdb->anwpfl_player_data
			ORDER BY name
			"
		) ?: [];

		foreach ( $all_players as $player ) {
			$player->id        = absint( $player->id );
			$player->birthdate = '0000-00-00' !== $player->birthdate ? date_i18n( 'M j, Y', strtotime( $player->birthdate ) ) : '';
			$player->country2  = '';
			$player->photo     = $player->photo ? anwp_fl()->upload_dir . $player->photo : '';

			if ( ! empty( $squad_position_map[ $player->id ] ) && $squad_position_map[ $player->id ] !== $player->position ) {
				$player->position = $squad_position_map[ $player->id ];
			}
		}

		return $all_players;
	}

	/**
	 * Helper template function, returns the latest matches for selected player
	 *
	 * @param $player_id
	 * @param $season_id
	 *
	 * @since 0.5.0 (2018-03-10)
	 * @return array
	 */
	public function tmpl_get_latest_matches( $player_id, $season_id ) {

		/*
		|--------------------------------------------------------------------
		| Try to get from cache
		|--------------------------------------------------------------------
		*/
		$cache_key = 'FL-PLAYER_tmpl_get_latest_matches__' . md5( maybe_serialize( $player_id . '-' . $season_id ) );

		if ( anwp_fl()->cache->get( $cache_key, 'anwp_match' ) ) {
			return anwp_fl()->cache->get( $cache_key, 'anwp_match' );
		}

		global $wpdb;

		if ( ! absint( $season_id ) && 'all' !== $season_id ) {
			return [];
		}

		if ( 'all' === $season_id ) {
			$matches = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT p.*, m.competition_id, m.season_id, m.league_id, m.kickoff, m.main_stage_id, m.home_club, m.away_club, m.home_goals, m.away_goals
					FROM {$wpdb->prefix}anwpfl_players AS p
					INNER JOIN {$wpdb->prefix}anwpfl_matches AS m ON m.match_id = p.match_id
					WHERE p.player_id = %d
					ORDER BY m.kickoff DESC
					",
					$player_id
				)
			);
		} else {
			$matches = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT p.*, m.competition_id, m.season_id, m.league_id, m.kickoff, m.main_stage_id, m.home_club, m.away_club, m.home_goals, m.away_goals
					FROM {$wpdb->prefix}anwpfl_players AS p
					INNER JOIN {$wpdb->prefix}anwpfl_matches AS m ON m.match_id = p.match_id
					WHERE p.player_id = %d
						AND m.season_id = %d
					ORDER BY m.kickoff DESC
					",
					$player_id,
					$season_id
				)
			);
		}

		/*
		|--------------------------------------------------------------------
		| Save transient
		|--------------------------------------------------------------------
		*/
		if ( ! empty( $matches ) ) {
			anwp_fl()->cache->set( $cache_key, $matches, 'anwp_match' );
		}

		return $matches;
	}

	/**
	 * Helper template function. Returns prepared data.
	 *
	 * @param array $matches - Data array with latest matches
	 *
	 * @since 0.5.0 (2018-03-11)
	 * @return array
	 */
	public function tmpl_prepare_competition_matches( $matches ) {

		$data = [];

		if ( empty( $matches ) ) {
			return $data;
		}

		// Set game links
		$game_ids  = wp_list_pluck( $matches, 'match_id' );
		$links_map = $this->plugin->helper->get_permalinks_by_ids( $game_ids, 'anwp_match' );

		$player_yr_card_count = AnWPFL_Options::get_value( 'player_yr_card_count', 'yyr' );

		// Get competition ids from matches
		$competition_ids = [];
		foreach ( $matches as $match ) {
			$competition_ids[] = (int) $match->main_stage_id;
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
					'totals'  => array_fill_keys( [ 'started', 'sub_in', 'minutes', 'card_y', 'card_yr', 'card_r', 'goals', 'assist', 'goals_own', 'goals_penalty', 'goals_conceded', 'clean_sheets' ], 0 ),
					'logo'    => get_post_meta( $competition->ID, '_anwpfl_logo', true ),
					'order'   => (int) get_post_meta( $competition->ID, '_anwpfl_competition_order', true ),
				];
			}
		}

		// Add matches to competitions
		foreach ( $matches as $match ) {

			$competition_index = (int) $match->main_stage_id;

			if ( isset( $data[ $competition_index ] ) ) {

				// Add link
				$match->link = $links_map[ $match->match_id ] ?? '';

				// Output Game
				$data[ $competition_index ]['matches'][] = $match;

				// Calculate totals
				$data[ $competition_index ]['totals']['started']        += (int) in_array( $match->appearance, [ '1', '2' ], true );
				$data[ $competition_index ]['totals']['sub_in']         += (int) in_array( $match->appearance, [ '3', '4' ], true );
				$data[ $competition_index ]['totals']['minutes']        += (int) $match->time_out - (int) $match->time_in;
				$data[ $competition_index ]['totals']['card_y']         += ( 'yr' === $player_yr_card_count && $match->card_yr > 0 ? 0 : (int) $match->card_y );
				$data[ $competition_index ]['totals']['card_yr']        += (int) $match->card_yr;
				$data[ $competition_index ]['totals']['card_r']         += (int) $match->card_r;
				$data[ $competition_index ]['totals']['goals']          += (int) $match->goals;
				$data[ $competition_index ]['totals']['assist']         += (int) $match->assist;
				$data[ $competition_index ]['totals']['goals_own']      += (int) $match->goals_own;
				$data[ $competition_index ]['totals']['goals_penalty']  += (int) $match->goals_penalty;
				$data[ $competition_index ]['totals']['goals_conceded'] += (int) $match->goals_conceded;

				if ( ( 1 === absint( $match->appearance ) || ( 2 === absint( $match->appearance ) && absint( $match->time_out ) > 59 ) ) && 0 === (int) $match->goals_conceded ) {
					$data[ $competition_index ]['totals']['clean_sheets'] ++;
				}

				// Fix minutes after half time substitution (1 min correction)
				// @since v0.6.5 (2018-08-17)
				if ( 46 === intval( $match->time_out ) ) {
					$data[ $competition_index ]['totals']['minutes'] = $data[ $competition_index ]['totals']['minutes'] - 1;
				} elseif ( 46 === intval( $match->time_in ) ) {
					$data[ $competition_index ]['totals']['minutes'] = $data[ $competition_index ]['totals']['minutes'] + 1;
				}
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
	 * Get players and teams Cards.
	 *
	 * @param array $options
	 *
	 * @since 0.7.3 (2018-09-23)
	 * @return array|null|object
	 */
	public function tmpl_get_players_cards( $options ) {

		global $wpdb;

		$player_yr_card_count = AnWPFL_Options::get_value( 'player_yr_card_count', 'yyr' );

		$options = wp_parse_args(
			$options,
			[
				'competition_id' => '',
				'join_secondary' => 0,
				'season_id'      => '',
				'league_id'      => '',
				'club_id'        => '',
				'type'           => 'players',
				'limit'          => 0,
				'soft_limit'     => '',
				'soft_limit_qty' => '',
				'points_r'       => '5',
				'points_yr'      => '2',
				'sort_by_point'  => '',
				'hide_zero'      => 0,
			]
		);

		// Prepare countable field
		if ( 'yr' === $player_yr_card_count && 'clubs' !== $options['type'] ) {
			$countable = ' SUM(CASE WHEN p.card_yr > 0 THEN 0 ELSE p.card_y END) as cards_y, SUM( p.card_yr ) as cards_yr, SUM( p.card_r ) as cards_r, SUM( p.card_r * ' . (int) $options['points_r'] . ' + p.card_yr * ' . (int) $options['points_yr'] . ' + ( CASE WHEN p.card_yr > 0 THEN 0 ELSE p.card_y END ) ) as countable ';
		} else {
			$countable = ' SUM( p.card_y ) as cards_y, SUM( p.card_yr ) as cards_yr, SUM( p.card_r ) as cards_r, SUM( p.card_r * ' . (int) $options['points_r'] . ' + p.card_yr * ' . (int) $options['points_yr'] . ' + p.card_y * 1 ) as countable ';
		}

		if ( 'clubs' === $options['type'] ) {
			$query = "SELECT p.club_id, {$countable}";
		} else {
			$query = "SELECT p.player_id, {$countable}, GROUP_CONCAT(DISTINCT p.club_id) as clubs";
		}

		$query .= "
		FROM {$wpdb->prefix}anwpfl_players p
		INNER JOIN {$wpdb->prefix}anwpfl_matches AS m ON m.match_id = p.match_id
		WHERE m.game_status = 1 
		";

		// Get competition to filter
		if ( AnWP_Football_Leagues::string_to_bool( $options['join_secondary'] ) && '' !== $options['competition_id'] ) {
			$query .= $wpdb->prepare( ' AND (m.competition_id = %d OR m.main_stage_id = %d) ', $options['competition_id'], $options['competition_id'] );
		} elseif ( '' !== $options['competition_id'] ) {
			$query .= $wpdb->prepare( ' AND m.competition_id = %d ', $options['competition_id'] );
		}

		// filter by season
		if ( (int) $options['season_id'] && '' === $options['competition_id'] ) {
			$query .= $wpdb->prepare( ' AND m.season_id = %d ', $options['season_id'] );
		}

		// filter by league
		if ( (int) $options['league_id'] && '' === $options['competition_id'] ) {
			$query .= $wpdb->prepare( ' AND m.league_id = %d ', $options['league_id'] );
		}

		// filter by club
		if ( (int) $options['club_id'] ) {
			$query .= $wpdb->prepare( ' AND p.club_id = %d ', $options['club_id'] );
		}

		// hide with zero
		if ( AnWP_Football_Leagues::string_to_bool( $options['hide_zero'] ) ) {
			$query .= ' AND ( p.card_y > 0 OR p.card_yr > 0 OR p.card_r > 0 )';
		}

		// handle player/club type
		$query .= ( 'clubs' === $options['type'] ) ? ' GROUP BY p.club_id' : ' GROUP BY p.player_id';

		// soft limit
		if ( absint( $options['soft_limit_qty'] ) ) {
			$query .= $wpdb->prepare( ' HAVING countable >= %d ', $options['soft_limit_qty'] );
		}

		// order
		$query .= ' ORDER BY countable ' . ( $options['sort_by_point'] ? 'ASC' : 'DESC' );

		// limit
		if ( absint( $options['limit'] ) ) {
			$query .= $wpdb->prepare( ' LIMIT %d', $options['limit'] );

			if ( AnWP_Football_Leagues::string_to_bool( $options['soft_limit'] ) ) {
				$soft_limit_qty = $wpdb->get_row( $query, OBJECT, ( $options['limit'] - 1 ) ); // phpcs:ignore WordPress.DB.PreparedSQL

				if ( ! empty( $soft_limit_qty ) && isset( $soft_limit_qty->countable ) ) {
					$options['limit']          = 0;
					$options['soft_limit']     = 0;
					$options['soft_limit_qty'] = $soft_limit_qty->countable;

					return $this->tmpl_get_players_cards( $options );
				}
			}
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $query );
	}

	/**
	 * Get players.
	 *
	 * @param array $options
	 *
	 * @since 0.5.1 (2018-03-22)
	 * @return array
	 */
	public function tmpl_get_players_by_type( $options ) {

		global $wpdb;

		$options = wp_parse_args(
			$options,
			[
				'competition_id'    => '',
				'join_secondary'    => 0,
				'season_id'         => '',
				'league_id'         => '',
				'club_id'           => '',
				'type'              => 'scorers',
				'limit'             => 0,
				'soft_limit'        => '',
				'soft_limit_qty'    => '',
				'hide_zero'         => 0,
				'penalty_goals'     => 0,
				'games_played'      => 0,
				'secondary_sorting' => '',
			]
		);

		if ( 'assists' === $options['type'] ) {
			$select_extra = [ 'SUM( p.assist ) as countable' ];
		} else {

			// Prepare select by type (default for "scorers")
			$select_extra = [ 'SUM( p.goals ) as countable' ];

			if ( $options['penalty_goals'] || 'less_penalty' === $options['secondary_sorting'] ) {
				$select_extra[] = 'SUM( p.goals_penalty ) as penalty';
			}
		}

		if ( $options['games_played'] || 'less_games' === $options['secondary_sorting'] ) {
			$select_extra[] = 'SUM(CASE WHEN (p.appearance > 0) THEN 1 ELSE 0 END) as played';
		}

		$select_extra = implode( ', ', $select_extra );

		$query = "
		SELECT p.player_id, {$select_extra}, GROUP_CONCAT(DISTINCT p.club_id) as clubs
		FROM {$wpdb->prefix}anwpfl_players p
		INNER JOIN {$wpdb->prefix}anwpfl_matches AS m ON m.match_id = p.match_id
		WHERE m.game_status = 1
		";

		/*
		|--------------------------------------------------------------------
		| WHERE filter by competition
		|--------------------------------------------------------------------
		*/
		// Get competition to filter
		if ( absint( $options['join_secondary'] ) && ! empty( $options['competition_id'] ) ) {
			$competition_ids = wp_parse_id_list( $options['competition_id'] );
			$format          = implode( ', ', array_fill( 0, count( $competition_ids ), '%d' ) );

			$query .= $wpdb->prepare( " AND ( m.competition_id IN ({$format}) OR m.main_stage_id IN ({$format}) ) ", array_merge( $competition_ids, $competition_ids ) ); // phpcs:ignore
		} elseif ( ! empty( $options['competition_id'] ) ) {
			$competition_ids = wp_parse_id_list( $options['competition_id'] );
			$format          = implode( ', ', array_fill( 0, count( $competition_ids ), '%d' ) );

			$query .= $wpdb->prepare( " AND m.competition_id IN ({$format}) ", $competition_ids ); // phpcs:ignore
		}

		/*
		|--------------------------------------------------------------------
		| WHERE filter by season
		|--------------------------------------------------------------------
		*/
		if ( (int) $options['season_id'] && '' === $options['competition_id'] ) {
			$query .= $wpdb->prepare( ' AND m.season_id = %d ', $options['season_id'] );
		}

		/*
		|--------------------------------------------------------------------
		| WHERE filter by league
		|--------------------------------------------------------------------
		*/
		if ( (int) $options['league_id'] && '' === $options['competition_id'] ) {
			$query .= $wpdb->prepare( ' AND m.league_id = %d ', $options['league_id'] );
		}

		/*
		|--------------------------------------------------------------------
		| WHERE filter by club
		|--------------------------------------------------------------------
		*/
		if ( (int) $options['club_id'] ) {
			$clubs  = wp_parse_id_list( $options['club_id'] );
			$format = implode( ', ', array_fill( 0, count( $clubs ), '%d' ) );

			$query .= $wpdb->prepare( " AND p.club_id IN ({$format}) ", $clubs ); // phpcs:ignore
		}

		/*
		|--------------------------------------------------------------------
		| Handle players Type
		|--------------------------------------------------------------------
		*/
		$query .= ' GROUP BY p.player_id';

		/*
		|--------------------------------------------------------------------
		| Hide Zeroes
		|--------------------------------------------------------------------
		*/
		if ( absint( $options['soft_limit_qty'] ) && AnWP_Football_Leagues::string_to_bool( $options['hide_zero'] ) ) {
			$query .= $wpdb->prepare( ' HAVING countable >= %d AND countable != 0 ', $options['soft_limit_qty'] );
		} elseif ( absint( $options['soft_limit_qty'] ) ) {
			$query .= $wpdb->prepare( ' HAVING countable >= %d ', $options['soft_limit_qty'] );
		} elseif ( AnWP_Football_Leagues::string_to_bool( $options['hide_zero'] ) ) {
			$query .= ' HAVING countable > 0 ';
		}

		/*
		|--------------------------------------------------------------------
		| Order
		|--------------------------------------------------------------------
		*/
		if ( 'less_games' === $options['secondary_sorting'] ) {
			$query .= ' ORDER BY countable DESC, played ASC ';
		} elseif ( 'less_penalty' === $options['secondary_sorting'] && 'assists' !== $options['type'] ) {
			$query .= ' ORDER BY countable DESC, penalty ASC ';
		} else {
			$query .= ' ORDER BY countable DESC';
		}

		/*
		|--------------------------------------------------------------------
		| LIMIT clause
		|--------------------------------------------------------------------
		*/
		if ( absint( $options['limit'] ) ) {

			$query .= $wpdb->prepare( ' LIMIT %d', $options['limit'] );

			if ( AnWP_Football_Leagues::string_to_bool( $options['soft_limit'] ) ) {
				$soft_limit_qty = $wpdb->get_row( $query, OBJECT, ( $options['limit'] - 1 ) ); // phpcs:ignore WordPress.DB.PreparedSQL

				if ( ! empty( $soft_limit_qty ) && isset( $soft_limit_qty->countable ) ) {
					$options['limit']          = 0;
					$options['soft_limit']     = 0;
					$options['soft_limit_qty'] = $soft_limit_qty->countable;

					return $this->tmpl_get_players_by_type( $options );
				}
			}
		}

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Filter goalkeepers player_ids from squad match data.
	 *
	 * @param string $line_up Comma separated list of players
	 * @param string $subs    Comma separated list of players
	 *
	 * @since 0.8.1
	 * @return array
	 */
	public function filter_goalkeepers_from_squad( string $line_up, string $subs ): array {
		global $wpdb;

		$ids = [];

		if ( $line_up ) {
			$ids = array_merge( $ids, explode( ',', $line_up ) );
		}

		if ( $subs ) {
			$ids = array_merge( $ids, explode( ',', $subs ) );
		}

		$ids = array_values( array_map( 'intval', $ids ) );

		if ( empty( $ids ) ) {
			return [];
		}

		$placeholders = array_fill( 0, count( $ids ), '%s' );
		$format       = implode( ', ', $placeholders );

		return $wpdb->get_col(
		// phpcs:disable
			$wpdb->prepare(
				"
					SELECT *
					FROM $wpdb->anwpfl_player_data
					WHERE player_id IN ({$format}) AND `position` = 'g'
					",
				$ids
			)
		// phpcs:enable
		) ?: [];
	}

	/**
	 * Get Post ID by External id
	 *
	 * @param int $external_id
	 *
	 * @return string|null
	 */
	public function get_player_id_by_external_id( int $external_id ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"
					SELECT player_id
					FROM {$wpdb->prefix}anwpfl_player_data
					WHERE player_external_id = %d
					",
				$external_id
			)
		) ?: 0;
	}

	/**
	 * Get Player Last Team ID
	 *
	 * @param int $player_id
	 *
	 * @return int
	 */
	public function get_player_last_team( int $player_id ): int {
		global $wpdb;

		$team_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT a.club_id
				FROM $wpdb->anwpfl_players a
				LEFT JOIN $wpdb->anwpfl_matches m ON m.match_id = a.match_id
				WHERE a.player_id = %d AND m.game_status = 1
				ORDER BY m.kickoff DESC
				",
				$player_id
			)
		) ?: [];

		if ( empty( $team_ids ) ) {
			return 0;
		}

		$national_teams = array_map( 'absint', array_keys( anwp_fl()->club->get_national_team_options() ?? [] ) );

		foreach ( $team_ids as $team_id ) {
			if ( ! in_array( absint( $team_id ), $national_teams, true ) ) {
				return absint( $team_id );
			}
		}

		return 0;
	}
}
