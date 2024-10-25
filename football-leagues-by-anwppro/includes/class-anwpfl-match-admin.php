<?php
/**
 * AnWP Football Leagues :: Match Admin.
 *
 * @package AnWP_Football_Leagues
 */

class AnWPFL_Match_Admin {

	/**
	 * @var AnWP_Football_Leagues
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.1.0
	 * @param  AnWP_Football_Leagues $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {
		add_filter( 'manage_edit-anwp_match_columns', [ $this, 'columns' ] );
		add_action( 'manage_anwp_match_posts_custom_column', [ $this, 'columns_display' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ $this, 'custom_admin_filters' ] );
		add_filter( 'disable_months_dropdown', [ $this, 'disable_months_dropdown' ], 10, 2 );
		add_filter( 'pre_get_posts', [ $this, 'handle_custom_filter' ] );
		add_filter( 'posts_clauses', [ $this, 'modify_query_clauses' ], 20, 2 );
		add_filter( 'post_row_actions', [ $this, 'modify_quick_actions' ], 10, 2 );
		add_action( 'add_meta_boxes_anwp_match', [ $this, 'remove_unused_metaboxes' ] );
		add_filter( 'manage_edit-anwp_match_sortable_columns', [ $this, 'sortable_columns' ] );

		add_action( 'cmb2_admin_init', [ $this, 'init_cmb2_metaboxes' ] );
	}

	/**
	 * Remove metaboxes.
	 */
	public function remove_unused_metaboxes( $post ) {
		remove_meta_box( 'submitdiv', 'anwp_match', 'side' );
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @param $sortable_columns
	 *
	 * @return array
	 */
	public function sortable_columns( $sortable_columns ) {

		return array_merge( $sortable_columns, [ '_fl_game_id' => 'ID' ] );
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @since  0.1.0
	 *
	 * @param  array $columns Array of registered column names/labels.
	 * @return array          Modified array.
	 */
	public function columns( $columns ) {
		// Add new columns
		$new_columns = [
			'_fl_match_competition' => esc_html__( 'Competition', 'anwp-football-leagues' ),
			'_fl_match_scores'      => esc_html__( 'Match', 'anwp-football-leagues' ),
			'_fl_match_datetime'    => esc_html__( 'Kick Off Time', 'anwp-football-leagues' ),
			'_fl_game_id'           => esc_html__( 'ID', 'anwp-football-leagues' ),
		];

		// Merge old and new columns
		$columns = array_merge( $new_columns, $columns );

		// Change columns order
		$new_columns_order = [
			'cb',
			'title',
			'_fl_match_competition',
			'_fl_match_datetime',
			'_fl_match_scores',
			'comments',
			'_fl_game_id',
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
	 * @param string  $column  Column currently being rendered.
	 * @param integer $post_id ID of post to display column for.
	 */
	public function columns_display( string $column, int $post_id ) {
		global $post;

		switch ( $column ) {

			case '_fl_match_competition':
				// Get competition title
				$competition = anwp_fl()->competition->get_competition( $post->_fl_competition_id );

				if ( ! $competition ) {
					return;
				}

				echo '<span class="anwp-admin-competition-icon"></span> <strong>' . esc_html( $competition->title ) . '</strong><br>';

				// Stage title
				if ( '' !== $competition->multistage && $competition->stage_title ) {
					echo '<strong>' . esc_html__( 'Stage', 'anwp-football-leagues' ) . ':</strong> ' . esc_html( $competition->stage_title ) . '<br>';
				}

				// Season
				if ( ! empty( $competition->season_text ) ) {
					echo '<strong>' . esc_html__( 'Season', 'anwp-football-leagues' ) . ':</strong> ' . esc_html( $competition->season_text ) . '<br>';
				}

				if ( 'knockout' === $competition->type ) {
					$round_id = $post->_fl_match_week ?: 1;

					if ( $round_id ) {
						$round_title = '';

						if ( ! empty( $competition->rounds ) && is_array( $competition->rounds ) ) {
							foreach ( $competition->rounds as $round ) {
								if ( intval( $round_id ) === intval( $round->id ) && ! empty( $round->title ) ) {
									$round_title = trim( $round->title );
									break;
								}
							}
						}

						echo '<strong>' . esc_html__( 'Round', 'anwp-football-leagues' ) . ' #' . intval( $round_id ) . ':</strong> ' . esc_html( $round_title );
					}
				} else {
					echo '<strong>' . esc_html__( 'MatchWeek', 'anwp-football-leagues' ) . ':</strong> ' . esc_html( $post->_fl_match_week ) . '<br>';
				}
				break;

			case '_fl_match_scores':
				// HOME
				echo '<span class="anwp-text-nowrap"><span class="anwp-admin-table-scores">' . ( absint( $post->_fl_finished ) ? absint( $post->_fl_home_goals ) : '-' ) . '</span>';
				$clubs_options = $this->plugin->club->get_clubs_options();

				if ( ! empty( $clubs_options[ $post->_fl_home_club ] ) ) {
					echo esc_html( $clubs_options[ $post->_fl_home_club ] ) . '</span><br>';
				}

				// AWAY
				echo '<span class="anwp-text-nowrap"><span class="anwp-admin-table-scores">' . ( absint( $post->_fl_finished ) ? absint( $post->_fl_away_goals ) : '-' ) . '</span>';
				$clubs_options = $this->plugin->club->get_clubs_options();

				if ( ! empty( $clubs_options[ $post->_fl_away_club ] ) ) {
					echo esc_html( $clubs_options[ $post->_fl_away_club ] );
				}

				echo '</span>';

				break;

			case '_fl_match_datetime':
				if ( ! empty( $post->_fl_kickoff ) && '0000-00-00 00:00:00' !== $post->_fl_kickoff ) {
					echo esc_html( date_i18n( 'M j, Y', strtotime( $post->_fl_kickoff ) ) ) . '<br>' . esc_html( date( 'H:i', strtotime( $post->_fl_kickoff ) ) );
				}

				break;

			case '_fl_game_id':
				echo absint( $post_id );
				break;
		}
	}

	/**
	 * Handle custom filter.
	 *
	 * Covers the WHERE, GROUP BY, JOIN, ORDER BY, DISTINCT,
	 * fields (SELECT), and LIMIT clauses.
	 *
	 * @param string[] $clauses   {
	 *     Associative array of the clauses for the query.
	 *
	 *     @type string $where The WHERE clause of the query.
	 *     @type string $groupby The GROUP BY clause of the query.
	 *     @type string $join The JOIN clause of the query.
	 *     @type string $orderby The ORDER BY clause of the query.
	 *     @type string $distinct The DISTINCT clause of the query.
	 *     @type string $fields The SELECT clause of the query.
	 *     @type string $limits The LIMIT clause of the query.
	 * }
	 *
	 * @param WP_Query  $query The WP_Query instance (passed by reference).
	 *
	 */
	public function modify_query_clauses( array $clauses, WP_Query $query ) {
		global $post_type, $pagenow, $wpdb;

		// Check main query in admin
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $clauses;
		}

		if ( 'edit.php' === $pagenow && 'anwp_match' === $post_type ) {
			$clauses['join'] .= " LEFT JOIN $wpdb->anwpfl_matches fl_match ON fl_match.match_id = {$wpdb->posts}.ID";

			$game_fields = [
				'fl_match.competition_id _fl_competition_id',
				'fl_match.match_week     _fl_match_week',
				'fl_match.finished       _fl_finished',
				'fl_match.home_goals     _fl_home_goals',
				'fl_match.away_goals     _fl_away_goals',
				'fl_match.home_club      _fl_home_club',
				'fl_match.away_club      _fl_away_club',
				'fl_match.kickoff        _fl_kickoff',
				'fl_match.priority       _fl_priority',
			];

			$clauses['fields'] .= ',' . implode( ',', $game_fields );

			$get_data = wp_parse_args(
				$_GET, // phpcs:ignore WordPress.Security.NonceVerification
				[
					'_fl_team_id'        => '',
					'_fl_finished'       => '',
					'_fl_league'         => '',
					'_fl_season'         => '',
					'_fl_competition_id' => '',
					'_fl_date_from'      => '',
					'_fl_date_to'        => '',
					'_fl_matchweek'      => '',
				]
			);

			$get_data = array_map( 'sanitize_text_field', $get_data );

			if ( absint( $get_data['_fl_team_id'] ) ) {
				$clauses['where'] .= $wpdb->prepare( ' AND ( fl_match.home_club = %d OR fl_match.away_club = %d ) ', absint( $get_data['_fl_team_id'] ), absint( $get_data['_fl_team_id'] ) );
			}

			if ( '' !== $get_data['_fl_finished'] ) {
				$clauses['where'] .= $wpdb->prepare( ' AND fl_match.finished = %d ', absint( $get_data['_fl_finished'] ) );
			}

			if ( '' !== $get_data['_fl_league'] ) {
				$clauses['where'] .= $wpdb->prepare( ' AND fl_match.league_id = %d ', absint( $get_data['_fl_league'] ) );
			}

			if ( '' !== $get_data['_fl_season'] ) {
				$clauses['where'] .= $wpdb->prepare( ' AND fl_match.season_id = %d ', absint( $get_data['_fl_season'] ) );
			}

			if ( '' !== $get_data['_fl_competition_id'] ) {
				$clauses['where'] .= $wpdb->prepare( ' AND fl_match.competition_id = %d ', absint( $get_data['_fl_competition_id'] ) );
			}

			if ( '' !== $get_data['_fl_date_from'] ) {
				$clauses['where'] .= $wpdb->prepare( ' AND fl_match.kickoff >= %s ', $get_data['_fl_date_from'] . ' 00:00:00' );
			}

			if ( '' !== $get_data['_fl_date_to'] ) {
				$clauses['where'] .= $wpdb->prepare( ' AND fl_match.kickoff <= %s ', $get_data['_fl_date_to'] . ' 23:59:59' );
			}

			if ( '' !== $get_data['_fl_matchweek'] ) {
				$clauses['where'] .= $wpdb->prepare( ' AND fl_match.match_week = %d ', absint( $get_data['_fl_matchweek'] ) );
			}
		}

		return $clauses;
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
		if ( 'anwp_match' === $post_type ) {

			ob_start();

			/*
			|--------------------------------------------------------------------
			| Filter By Game State
			|--------------------------------------------------------------------
			*/
			// phpcs:ignore WordPress.Security.NonceVerification
			$current_status = isset( $_GET['_fl_finished'] ) ? sanitize_text_field( $_GET['_fl_finished'] ) : '';
			?>
			<select name='_fl_finished' id='anwp_status_filter' class='postform'>
				<option value=''><?php echo esc_html__( 'Status', 'anwp-football-leagues' ); ?></option>
				<option value="1" <?php selected( '1', $current_status ); ?>>- <?php echo esc_html__( 'Result', 'anwp-football-leagues' ); ?></option>
				<option value="0" <?php selected( '0', $current_status ); ?>>- <?php echo esc_html__( 'Fixture', 'anwp-football-leagues' ); ?></option>
			</select>
			<?php
			/*
			|--------------------------------------------------------------------
			| Filter By Competition Id
			|--------------------------------------------------------------------
			*/
			// phpcs:ignore WordPress.Security.NonceVerification
			$competition_filter = empty( $_GET['_fl_competition_id'] ) ? '' : (int) $_GET['_fl_competition_id'];
			?>
			<input class="postform anwp-g-float-left anwp-g-admin-list-input anwp-w-120" name="_fl_competition_id" type="text" value="<?php echo esc_attr( $competition_filter ); ?>"
					placeholder="<?php echo esc_attr__( 'Competition ID', 'anwp-football-leagues' ); ?>"/>

			<button type="button" class="button anwp-fl-selector anwp-fl-selector--visible anwp-mr-2 postform anwp-g-float-left anwp-d-flex anwp-align-items-center"
					style="display: none;" data-context="competition" data-single="yes">
				<span class="dashicons dashicons-search"></span>
			</button>
			<?php
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
				$league_filter = empty( $_GET['_fl_league'] ) ? '' : (int) $_GET['_fl_league'];
				?>

				<select name='_fl_league' id='anwp_league_filter' class='postform'>
					<option value=''><?php echo esc_html__( 'All Leagues', 'anwp-football-leagues' ); ?></option>
					<?php foreach ( $leagues as $league ) : ?>
						<option value="<?php echo esc_attr( $league->term_id ); ?>" <?php selected( $league->term_id, $league_filter ); ?>>
							<?php echo esc_html( $league->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php
			}

			// Seasons dropdown
			$seasons = get_terms(
				[
					'taxonomy'   => 'anwp_season',
					'hide_empty' => false,
				]
			);

			if ( ! is_wp_error( $seasons ) && ! empty( $seasons ) ) {
				$seasons = wp_list_sort( $seasons, 'name', 'DESC' );

				// phpcs:ignore WordPress.Security.NonceVerification
				$season_filter = empty( $_GET['_fl_season'] ) ? '' : (int) $_GET['_fl_season'];
				?>
				<select name='_fl_season' id='anwp_season_filter' class='postform'>
					<option value=''><?php echo esc_html__( 'All Seasons', 'anwp-football-leagues' ); ?></option>
					<?php foreach ( $seasons as $season ) : ?>
						<option value="<?php echo esc_attr( $season->term_id ); ?>" <?php selected( $season->term_id, $season_filter ); ?>>
							<?php echo esc_html( $season->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php
			}

			// phpcs:ignore WordPress.Security.NonceVerification
			$current_club_filter = empty( $_GET['_fl_team_id'] ) ? '' : (int) $_GET['_fl_team_id'];
			?>
			<input class="postform anwp-g-float-left anwp-g-admin-list-input anwp-w-120" name="_fl_team_id"
					type="text" value="<?php echo esc_attr( $current_club_filter ); ?>"
					placeholder="<?php echo esc_attr__( 'Club ID', 'anwp-football-leagues' ); ?>"/>

			<button type='button' class='button anwp-fl-selector anwp-fl-selector--visible anwp-mr-2 postform anwp-g-float-left anwp-d-flex anwp-align-items-center'
					style='display: none;' data-context='club' data-single='yes'>
				<span class='dashicons dashicons-search'></span>
			</button>
			<?php

			/*
			|--------------------------------------------------------------------
			| Date From/To
			|--------------------------------------------------------------------
			*/
			// phpcs:ignore WordPress.Security.NonceVerification
			$date_from = empty( $_GET['_fl_date_from'] ) ? '' : sanitize_text_field( $_GET['_fl_date_from'] );

			// phpcs:ignore WordPress.Security.NonceVerification
			$date_to = empty( $_GET['_fl_date_to'] ) ? '' : sanitize_text_field( $_GET['_fl_date_to'] );
			?>
			<input type="text" class="postform anwp-g-float-left anwp-g-admin-list-input" name="_fl_date_from"
					placeholder="<?php echo esc_attr__( 'Date From', 'anwp-football-leagues' ); ?>" value="<?php echo esc_attr( $date_from ); ?>"/>
			<input type="text" class="postform anwp-g-float-left anwp-g-admin-list-input" name="_fl_date_to"
					placeholder="<?php echo esc_attr__( 'Date To', 'anwp-football-leagues' ); ?>" value="<?php echo esc_attr( $date_to ); ?>"/>
			<?php
			// MatchWeek Options
			$matchweeks = $this->get_matchweek_options();

			// phpcs:ignore WordPress.Security.NonceVerification
			$current_matchweek = empty( $_GET['_fl_matchweek'] ) ? '' : (int) $_GET['_fl_matchweek'];
			if ( ! empty( $matchweeks ) ) {
				?>
				<select name='_fl_matchweek' id='anwp_matchweek_filter' class='postform'>
					<option value=''><?php echo esc_html__( 'All Matchweeks', 'anwp-football-leagues' ); ?></option>
					<?php foreach ( $matchweeks as $matchweek ) : ?>
						<option value="<?php echo esc_attr( $matchweek ); ?>" <?php selected( $matchweek, $current_matchweek ); ?>>
							<?php echo esc_html( $matchweek ); ?>
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
	public function handle_custom_filter( $query ) {
		global $post_type, $pagenow;

		// Check main query in admin
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'edit.php' !== $pagenow || 'anwp_match' !== $post_type ) {
			return;
		}

		$sub_query = [];

		/*
		|--------------------------------------------------------------------
		| Find Duplicates
		|--------------------------------------------------------------------
		*/
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['_anwpfl_match_duplicates'] ) ) {
			$sub_query[] =
				[
					'key'   => '_anwpfl_match_duplicates',
					'value' => 'yes',
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
					array_merge( [ 'relation' => 'AND' ], $sub_query ),
				]
			);
		}
	}

	/**
	 * Filters the array of row action links on the Pages list table.
	 *
	 * @param array $actions
	 * @param WP_Post $post
	 *
	 * @return array
	 * @since 0.10.0
	 */
	public function modify_quick_actions( $actions, $post ) {

		if ( 'anwp_match' === $post->post_type && current_user_can( 'edit_post', $post->ID ) ) {

			// Create edit link
			$edit_link = admin_url( 'post.php?post=' . $post->ID . '&action=edit&setup-match-header=yes' );

			$actions['edit-match-header'] = '<a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit structure', 'anwp-football-leagues' ) . '</a>';
		}

		return $actions;
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

		return 'anwp_match' === $post_type ? true : $disable;
	}

	/**
	 * Create CMB2 metaboxes
	 *
	 * @since 0.2.0 (2018-01-17)
	 */
	public function init_cmb2_metaboxes() {

		// Start with an underscore to hide fields from custom fields list
		$prefix = '_anwpfl_';

		$cmb = new_cmb2_box(
			[
				'id'              => 'anwp_match_metabox',
				'object_types'    => [ 'anwp_match' ],
				'context'         => 'advanced',
				'priority'        => 'high',
				'save_button'     => '',
				'show_names'      => true,
				'remove_box_wrap' => true,
				'classes'         => 'anwp-b-wrap',
			]
		);

		/*
		|--------------------------------------------------------------------------
		| Match Summary
		|--------------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'            => esc_html__( 'Text 1', 'anwp-football-leagues' ) . '<br>' . esc_html__( 'Match Preview or Summary', 'anwp-football-leagues' ),
				'id'              => $prefix . 'summary',
				'type'            => 'wysiwyg',
				'sanitization_cb' => [ anwp_fl()->helper, 'sanitize_cmb2_fl_text' ],
				'options'         => [
					'wpautop'       => true,
					'media_buttons' => true, // show insert/upload button(s)
					'textarea_name' => 'anwp_match_summary_input',
					'textarea_rows' => 5,
					'teeny'         => false, // output the minimal editor config used in Press This
					'dfw'           => false, // replace the default fullscreen with DFW (needs specific css)
					'tinymce'       => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
					'quicktags'     => true, // load Quicktags, can be used to pass settings directly to Quicktags using an array()
				],
				'before_row'      => anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'note',
						'label' => __( 'Text Content', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-summary-metabox',
					]
				),
			]
		);

		$cmb->add_field(
			[
				'name'      => esc_html__( 'Text 2', 'anwp-football-leagues' ) . '<br>' . esc_html__( 'Custom Content', 'anwp-football-leagues' ),
				'id'        => $prefix . 'custom_content_below',
				'type'      => 'wysiwyg',
				'options'   => [
					'wpautop'       => true,
					'media_buttons' => true, // show insert/upload button(s)
					'textarea_name' => 'anwp_custom_content_below',
					'textarea_rows' => 5,
					'teeny'         => false, // output the minimal editor config used in Press This
					'dfw'           => false, // replace the default fullscreen with DFW (needs specific css)
					'tinymce'       => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
					'quicktags'     => true, // load Quicktags, can be used to pass settings directly to Quicktags using an array()
				],
				'after_row' => '</div></div>',
			]
		);

		/*
		|--------------------------------------------------------------------------
		| Media
		|--------------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Video Source', 'anwp-football-leagues' ),
				'id'         => $prefix . 'video_source',
				'type'       => 'select',
				'before_row' => anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'device-camera',
						'label' => __( 'Media', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-media-match-metabox',
					]
				),
				'default'    => '',
				'options'    => [
					''        => esc_html__( '- select source -', 'anwp-football-leagues' ),
					'site'    => esc_html__( 'Media Library', 'anwp-football-leagues' ),
					'youtube' => esc_html__( 'Youtube', 'anwp-football-leagues' ),
					'vimeo'   => esc_html__( 'Vimeo', 'anwp-football-leagues' ),
				],
			]
		);

		$cmb->add_field(
			[
				'name'    => esc_html__( 'YouTube Image Format', 'anwp-football-leagues' ),
				'id'      => $prefix . 'yt_image_format',
				'type'    => 'select',
				'default' => '',
				'options' => [
					''     => esc_html__( '- default -', 'anwp-football-leagues' ),
					'webp' => 'webp',
					'jpg'  => 'jpg',
				],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Video ID (or URL)', 'anwp-football-leagues' ),
				'id'         => $prefix . 'video_id',
				'type'       => 'text_small',
				'label_cb'   => [ $this->plugin, 'cmb2_field_label' ],
				'label_help' => __( 'for Youtube or Vimeo', 'anwp-football-leagues' ),
			]
		);

		$cmb->add_field(
			[
				'name'         => esc_html__( 'Video File', 'anwp-football-leagues' ),
				'id'           => $prefix . 'video_media_url',
				'type'         => 'file',
				'label_cb'     => [ $this->plugin, 'cmb2_field_label' ],
				'label_help'   => __( 'for Media Library', 'anwp-football-leagues' ),
				'options'      => [
					'url' => false,
				],
				'text'         => [
					'add_upload_file_text' => esc_html__( 'Open Media Library', 'anwp-football-leagues' ),
				],
				'query_args'   => [
					'type' => 'video/mp4',
				],
				'preview_size' => 'large',
			]
		);

		$cmb->add_field(
			[
				'name' => esc_html__( 'Video Description', 'anwp-football-leagues' ),
				'id'   => $prefix . 'video_info',
				'type' => 'text',
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
				'name' => esc_html__( 'Text below gallery', 'anwp-football-leagues' ),
				'id'   => $prefix . 'gallery_notes',
				'type' => 'textarea_small',
			]
		);

		/*
		|--------------------------------------------------------------------
		| Additional Video
		|--------------------------------------------------------------------
		*/
		$group_field_id = $cmb->add_field(
			[
				'id'               => $prefix . 'additional_videos',
				'type'             => 'group',
				'after_group'      => '</div></div>',
				'classes'          => 'mt-0 pt-0',
				'before_group_row' => '<h4>' . esc_html__( 'Additional videos', 'anwp-football-leagues' ) . '</h4>',
				'options'          => [
					'group_title'    => __( 'Additional Video', 'anwp-football-leagues' ),
					'add_button'     => __( 'Add Another Video', 'anwp-football-leagues' ),
					'remove_button'  => __( 'Remove Video', 'anwp-football-leagues' ),
					'sortable'       => true,
					'remove_confirm' => esc_html__( 'Are you sure you want to remove?', 'anwp-football-leagues' ),
				],
			]
		);

		$cmb->add_group_field(
			$group_field_id,
			[
				'name'    => esc_html__( 'Video Source', 'anwp-football-leagues' ),
				'id'      => 'video_source',
				'type'    => 'select',
				'default' => '',
				'options' => [
					''        => esc_html__( '- select source -', 'anwp-football-leagues' ),
					'site'    => esc_html__( 'Media Library', 'anwp-football-leagues' ),
					'youtube' => esc_html__( 'Youtube', 'anwp-football-leagues' ),
					'vimeo'   => esc_html__( 'Vimeo', 'anwp-football-leagues' ),
				],
			]
		);

		$cmb->add_group_field(
			$group_field_id,
			[
				'name'       => esc_html__( 'Video ID (or URL)', 'anwp-football-leagues' ),
				'id'         => 'video_id',
				'type'       => 'text_small',
				'label_cb'   => [ $this->plugin, 'cmb2_field_label' ],
				'label_help' => __( 'for Youtube or Vimeo', 'anwp-football-leagues' ),
			]
		);

		$cmb->add_group_field(
			$group_field_id,
			[
				'name'         => esc_html__( 'Video File', 'anwp-football-leagues' ),
				'id'           => 'video_media_url',
				'type'         => 'file',
				'label_cb'     => [ $this->plugin, 'cmb2_field_label' ],
				'label_help'   => __( 'for Media Library', 'anwp-football-leagues' ),
				'options'      => [
					'url' => false,
				],
				'text'         => [
					'add_upload_file_text' => esc_html__( 'Open Media Library', 'anwp-football-leagues' ),
				],
				'query_args'   => [
					'type' => 'video/mp4',
				],
				'preview_size' => 'large',
			]
		);

		$cmb->add_group_field(
			$group_field_id,
			[
				'name' => esc_html__( 'Video Description', 'anwp-football-leagues' ),
				'id'   => 'video_info',
				'type' => 'text',
			]
		);

		/*
		|--------------------------------------------------------------------------
		| Match Timing
		|--------------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Full Time', 'anwp-football-leagues' ),
				'id'         => $prefix . 'duration_full',
				'type'       => 'text_small',
				'default'    => '90',
				'before_row' => anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'watch',
						'label' => __( 'Match Duration', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-timing-match-metabox',
					]
				),
			]
		);

		$cmb->add_field(
			[
				'name'      => esc_html__( 'Extra Time', 'anwp-football-leagues' ),
				'id'        => $prefix . 'duration_extra',
				'type'      => 'text_small',
				'default'   => '30',
				'after_row' => '</div></div>',
			]
		);

		/*
		|--------------------------------------------------------------------------
		| Custom Outcome
		|--------------------------------------------------------------------------
		*/
		$cmb->add_field(
			[
				'name'       => esc_html__( 'Custom Outcome', 'anwp-football-leagues' ),
				'id'         => $prefix . 'custom_outcome',
				'type'       => 'select',
				'options'    => [
					''    => __( 'No', 'anwp-football-leagues' ),
					'yes' => __( 'Yes', 'anwp-football-leagues' ),
				],
				'attributes' => [
					'class'     => 'cmb2_select anwp-fl-parent-of-dependent',
					'data-name' => $prefix . 'custom_outcome',
				],
				'default'    => '',
				'before_row' => anwp_football_leagues()->helper->create_metabox_header(
					[
						'icon'  => 'law',
						'label' => __( 'Custom Outcome', 'anwp-football-leagues' ),
						'slug'  => 'anwp-fl-outcome-match-metabox',
					]
				),
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Outcome for home team', 'anwp-football-leagues' ),
				'id'         => $prefix . 'outcome_home',
				'type'       => 'select',
				'options'    => [
					''      => __( '- not selected -', 'anwp-football-leagues' ),
					'won'   => __( 'Won', 'anwp-football-leagues' ),
					'drawn' => __( 'Drawn', 'anwp-football-leagues' ),
					'lost'  => __( 'Lost', 'anwp-football-leagues' ),
				],
				'attributes' => [
					'class'       => 'regular-text anwp-fl-dependent-field',
					'data-parent' => $prefix . 'custom_outcome',
					'data-action' => 'show',
					'data-value'  => 'yes',
				],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Outcome for away team', 'anwp-football-leagues' ),
				'id'         => $prefix . 'outcome_away',
				'type'       => 'select',
				'options'    => [
					''      => __( '- not selected -', 'anwp-football-leagues' ),
					'won'   => __( 'Won', 'anwp-football-leagues' ),
					'drawn' => __( 'Drawn', 'anwp-football-leagues' ),
					'lost'  => __( 'Lost', 'anwp-football-leagues' ),
				],
				'attributes' => [
					'class'       => 'regular-text anwp-fl-dependent-field',
					'data-parent' => $prefix . 'custom_outcome',
					'data-action' => 'show',
					'data-value'  => 'yes',
				],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Points for home team', 'anwp-football-leagues' ),
				'id'         => $prefix . 'outcome_points_home',
				'type'       => 'text',
				'attributes' => [
					'class'       => 'regular-text anwp-input-number-small anwp-fl-dependent-field',
					'data-parent' => $prefix . 'custom_outcome',
					'data-action' => 'show',
					'data-value'  => 'yes',
					'type'        => 'number',
				],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Points for away team', 'anwp-football-leagues' ),
				'id'         => $prefix . 'outcome_points_away',
				'type'       => 'text',
				'attributes' => [
					'type'        => 'number',
					'class'       => 'regular-text anwp-input-number-small anwp-fl-dependent-field',
					'data-parent' => $prefix . 'custom_outcome',
					'data-action' => 'show',
					'data-value'  => 'yes',
				],
			]
		);

		$cmb->add_field(
			[
				'name'       => esc_html__( 'Outcome Text', 'anwp-football-leagues' ),
				'id'         => $prefix . 'outcome_text',
				'type'       => 'text',
				'attributes' => [
					'class'       => 'regular-text anwp-fl-dependent-field',
					'data-parent' => $prefix . 'custom_outcome',
					'data-action' => 'show',
					'data-value'  => 'yes',
				],
				'after_row'  => '</div></div>',
			]
		);

		/**
		 * Adds extra fields to the metabox.
		 *
		 * @since 0.9.0
		 */
		$extra_fields = apply_filters( 'anwpfl/cmb2_tabs_content/match', [] );

		if ( ! empty( $extra_fields ) && is_array( $extra_fields ) ) {
			foreach ( $extra_fields as $field ) {
				$cmb->add_field( $field );
			}
		}
	}

	/**
	 * Returns all available matchweek values.
	 *
	 * @return array
	 */
	public function get_matchweek_options(): array {

		global $wpdb;

		// Get finished matches
		return $wpdb->get_col(
			"
			SELECT DISTINCT match_week
			FROM $wpdb->anwpfl_matches
			WHERE match_week != 0
			ORDER BY match_week ASC
			"
		) ?: [];
	}
}
