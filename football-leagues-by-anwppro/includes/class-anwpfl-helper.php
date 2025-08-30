<?php
/**
 * AnWP Football Leagues :: Helper.
 *
 * @since   0.2.0
 * @package AnWP_Football_Leagues
 */

/**
 * AnWP Football Leagues :: Helper class.
 *
 * @since 0.1.0
 */
class AnWPFL_Helper {

	/**
	 * Parent plugin class.
	 *
	 * @var AnWP_Football_Leagues
	 * @since  0.1.0
	 */
	protected ?AnWP_Football_Leagues $plugin = null;

	private static array $selector_context = [ 'player', 'staff', 'referee', 'club', 'match', 'competition', 'main_stage', 'stage', 'season', 'league' ];

	/**
	 * Constructor.
	 *
	 * @param AnWP_Football_Leagues $plugin Main plugin object.
	 */
	public function __construct( AnWP_Football_Leagues $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 0.3.0
	 */
	public function hooks() {

		add_action( 'rest_api_init', [ $this, 'add_rest_routes' ] );

		// Modify CMB2 metabox form
		add_filter( 'cmb2_get_metabox_form_format', [ $this, 'modify_cmb2_metabox_form_format' ], 10, 3 );
	}

	/**
	 * Modify CMB2 Default Form Output
	 * Remove form tag and submit button
	 *
	 * @param  string  $form_format Form output format
	 * @param  string  $object_id   In the case of an options page, this will be the option key
	 * @param  object  $cmb         CMB2 object. Can use $cmb->cmb_id to retrieve the metabox ID
	 *
	 * @return string               Possibly modified form output
	 * @since 0.12.6
	 */
	public function modify_cmb2_metabox_form_format( $form_format, $object_id, $cmb ) {
		if ( in_array( $cmb->cmb_id, [ 'anwp_club_info_metabox', 'anwp_match_metabox' ], true ) ) {
			$form_format = '<input type="hidden" name="object_id" value="%2$s">';
		}

		return $form_format;
	}

	/**
	 * Create metabox navigation items
	 *
	 * @param array $nav_items
	 *
	 * @return string
	 * @since 0.12.6
	 */
	public function create_metabox_navigation( $nav_items ) {

		ob_start();

		foreach ( $nav_items as $nav_item_index => $nav_item ) :

			$nav_item = wp_parse_args(
				$nav_item,
				[
					'icon'         => '',
					'icon_classes' => 'anwp-icon--octi',
					'classes'      => '',
					'label'        => '',
					'slug'         => '',
				]
			);

			?>
			<li class="anwp-fl-metabox-page-nav__item d-block m-0 anwp-border anwp-border-gray-500 <?php echo $nav_item_index ? 'anwp-border-top-0' : ''; ?>">
				<a class="anwp-fl-smooth-scroll d-flex align-items-center text-decoration-none anwp-link-without-effects anwp-text-gray-800 py-2 px-1 <?php echo esc_attr( $nav_item['classes'] ); ?>" href="#<?php echo esc_attr( $nav_item['slug'] ); ?>">
					<svg class="anwp-icon anwp-icon--s16 d-inline-block mx-2 anwp-flex-none anwp-fill-current <?php echo esc_attr( $nav_item['icon_classes'] ); ?>">
						<use xlink:href="#icon-<?php echo esc_attr( $nav_item['icon'] ); ?>"></use>
					</svg>
					<span class="ml-1"><?php echo esc_html( $nav_item['label'] ); ?></span>
				</a>
			</li>
			<?php
		endforeach;

		?>
		<li class="anwp-fl-metabox-page-nav__item d-block m-0 anwp-border anwp-border-gray-500 anwp-border-top-0">
			<a class="d-flex align-items-center text-decoration-none anwp-link-without-effects anwp-text-gray-800 py-2 px-1 anwp-fl-collapse-menu" href="#">
				<svg class="anwp-icon anwp-icon--s16 anwp-icon--feather d-inline-block mx-2 anwp-flex-none">
					<use xlink:href="#icon-arrow-left-circle"></use>
				</svg>
				<span class="ml-1"><?php echo esc_html__( 'Collapse menu' ); ?></span>
			</a>
		</li>
		<?php

		return ob_get_clean();
	}

	/**
	 * Register REST routes.
	 *
	 * @since 0.9.2
	 */
	public function add_rest_routes() {

		register_rest_route(
			'anwpfl',
			'/helper/create_league',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_new_league' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl',
			'/helper/create_season',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_new_season' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl',
			'/helper/flush-plugin-cache',
			[
				'methods'             => 'GET',
				'callback'            => [ $this->plugin->cache, 'flush_all_cache' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl',
			'/helper/load-standing-data',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'load_standing_data' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl',
			'/helper/get-selector-data/(?P<args>[a-zA-Z0-9-_~%:,]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_selector_data' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl',
			'/helper/get-selector-initial/(?P<args>[a-zA-Z0-9-_~%:,]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_selector_initial' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl',
			'/helper/get-selector-global-options/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_selector_global_options' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/**
	 * Magic getter for our object.
	 *
	 * @param string $field Field to get.
	 *
	 * @since  0.2.0 (2018-01-05)
	 *
	 * @return mixed         Value of the field.
	 * @throws Exception     Throws an exception if the field is invalid.
	 */
	public function __get( string $field ) {

		if ( property_exists( $this, $field ) ) {
			return $this->$field;
		}

		throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
	}

	/**
	 * Validate datetime.
	 * From - https://secure.php.net/manual/en/function.checkdate.php#113205
	 *
	 * @param        $date
	 * @param string $format
	 *
	 * @since 2018-01-22
	 * @return bool
	 */
	public function validate_date( $date, $format = 'Y-m-d H:i:s' ) {
		$d = DateTime::createFromFormat( $format, $date );

		return $d && $d->format( $format ) === $date;
	}

	/**
	 * Get valid date
	 *
	 * @param string $date
	 *
	 * @return string
	 */
	public function get_valid_date( $date ) {
		return $this->validate_date( $date ) ? $date : '0000-00-00 00:00:00';
	}

	/**
	 * Prepare events for rendering in match.
	 * Method sorts and groups events by type.
	 *
	 * @param $events
	 *
	 * @since 0.3.0 (2018-02-08)
	 * @since 0.8.2 (2018-11-13) Fixed error in sorting with penalty shootout.
	 * @return array
	 */
	public function parse_match_events( $events ) {

		$output = [
			'goals'            => [],
			'cards'            => [],
			'subs'             => [],
			'players'          => [],
			'players_minutes'  => [],
			'missed_penalty'   => [],
			'penalty_shootout' => [],
		];

		// Prepare Penalty Shootout first
		foreach ( $events as $e ) {
			if ( ! empty( $e->player ) && 'penalty_shootout' === $e->type ) {
				$output['penalty_shootout'][] = $e;
			}
		}

		// Sort events
		usort(
			$events,
			function ( $a, $b ) {
				return intval( $a->minute ) - intval( $b->minute );
			}
		);

		// phpcs:disable WordPress.NamingConventions
		foreach ( $events as $e ) {

			if ( isset( $e->club ) ) {
				$e->club = intval( $e->club );
			}

			if ( ! empty( $e->player ) ) {
				switch ( $e->type ) {
					case 'goal':
						$output['goals'][] = $e;

						// add data to players array
						$goal_type = 'goal';

						if ( 'yes' === $e->ownGoal ) {
							$goal_type = 'goal_own';
						} elseif ( 'yes' === $e->fromPenalty ) {
							$goal_type = 'goal_penalty';
						}

						$output['players'][ $e->player ][] = $goal_type;

						if ( absint( $e->minute ) ) {
							$output['players_minutes'][ $e->player ][] =
								[
									'type'      => $goal_type,
									'minute'    => $e->minute,
									'minuteAdd' => isset( $e->minuteAdd ) ? $e->minuteAdd : '',
								];
						}

						break;

					case 'card':
						$output['cards'][] = $e;

						// add data to players array
						$output['players'][ $e->player ][] = 'card_' . $e->card;

						if ( absint( $e->minute ) ) {
							$output['players_minutes'][ $e->player ][] =
								[
									'type'      => 'card_' . $e->card,
									'minute'    => $e->minute,
									'minuteAdd' => isset( $e->minuteAdd ) ? $e->minuteAdd : '',
								];
						}
						break;

					case 'substitute':
						$output['subs'][] = $e;

						// add data to players array
						$output['players'][ $e->player ][]    = 'subs_in';
						$output['players'][ $e->playerOut ][] = 'subs_out';

						if ( absint( $e->minute ) ) {
							$output['players_minutes'][ $e->player ][] =
								[
									'type'      => 'subs_in',
									'minute'    => $e->minute,
									'minuteAdd' => isset( $e->minuteAdd ) ? $e->minuteAdd : '',
								];
						}

						if ( absint( $e->minute ) ) {
							$output['players_minutes'][ $e->playerOut ][] =
								[
									'type'      => 'subs_out',
									'minute'    => $e->minute,
									'minuteAdd' => isset( $e->minuteAdd ) ? $e->minuteAdd : '',
								];
						}
						break;

					case 'missed_penalty':
						$output['missed_penalty'][] = $e;
						break;
				}
			}
		}

		// phpcs:enable WordPress.NamingConventions
		return $output;
	}

	/**
	 * Prepare events for rendering in match (withh or without minute).
	 *
	 * @param $events
	 *
	 * @since 0.14.14
	 * @return array
	 */
	public function parse_match_events_lineups( $events ) {

		$output = [];

		// phpcs:disable WordPress.NamingConventions
		foreach ( $events as $e ) {

			if ( isset( $e->club ) ) {
				$e->club = intval( $e->club );
			}

			if ( ! empty( $e->player ) ) {
				switch ( $e->type ) {

					case 'goal':
						$goal_type = 'goal';

						if ( 'yes' === $e->ownGoal ) {
							$goal_type = 'goal_own';
						} elseif ( 'yes' === $e->fromPenalty ) {
							$goal_type = 'goal_penalty';
						}

						$output[ $e->player ][] =
							[
								'type'      => $goal_type,
								'minute'    => absint( $e->minute ) ?: '',
								'minuteAdd' => isset( $e->minuteAdd ) ? $e->minuteAdd : '',
							];

						break;

					case 'card':
						$output[ $e->player ][] =
							[
								'type'      => 'card_' . $e->card,
								'minute'    => absint( $e->minute ) ?: '',
								'minuteAdd' => isset( $e->minuteAdd ) ? $e->minuteAdd : '',
							];
						break;

					case 'substitute':
						$output[ $e->player ][] =
							[
								'type'      => 'subs_in',
								'minute'    => absint( $e->minute ) ?: '',
								'minuteAdd' => isset( $e->minuteAdd ) ? $e->minuteAdd : '',
							];

						$output[ $e->playerOut ][] =
							[
								'type'      => 'subs_out',
								'minute'    => absint( $e->minute ) ?: '',
								'minuteAdd' => isset( $e->minuteAdd ) ? $e->minuteAdd : '',
							];

						break;
				}
			}
		}

		// phpcs:enable WordPress.NamingConventions
		return $output;
	}

	/**
	 * Rendering season dropdown.
	 *
	 * @param int    $season_id
	 * @param bool   $echo
	 * @param string $class
	 * @param array  $filter (player|club)
	 *
	 * @return string
	 * @since 0.5.0 (2018-03-13)
	 */
	public function season_dropdown( $season_id, $echo = true, $class = '', $filter = [] ) {

		// Get all season options
		$season_options = anwp_football_leagues()->season->get_season_slug_options();

		if ( count( $season_options ) < 2 ) {
			return '';
		}

		// Filter season options
		$season_options = $this->filter_season_list( $season_options, $filter );

		if ( empty( $season_options ) ) {
			return '';
		}

		if ( ! empty( $filter['context'] ) && 'player' === $filter['context'] ) {
			if ( apply_filters( 'anwpfl/layout/show_all_seasons', true ) ) {
				$season_options = array_merge(
					[
						[
							'slug' => 'all',
							'name' => AnWPFL_Text::get_value( 'general__all_seasons', __( 'All Seasons', 'anwp-football-leagues' ) ),
						],
					],
					$season_options
				);
			}
		}

		if ( ! empty( $filter['context'] ) && 'player' === $filter['context'] && 'yes' === AnWPFL_Options::get_value( 'all_season_default' ) ) {
			$active_season_id = 'all';

			$selected_season = $active_season_id === $season_id
				? 'all'
				: anwp_football_leagues()->season->get_season_slug_by_id( $season_id );

			ob_start();
			?>
			<select class="anwp-fl-season-dropdown anwp-text-sm <?php echo esc_attr( $class ); ?>">
				<?php
				foreach ( $season_options as $s ) :
					$data_url = 'all' === $s['slug'] ? remove_query_arg( 'season' ) : add_query_arg( 'season', $s['slug'] );
					?>
					<option <?php selected( $s['slug'], $selected_season ); ?> data-href="<?php echo esc_url( $data_url ); ?>" value="<?php echo esc_attr( $s['slug'] ); ?>"><?php echo esc_attr( $s['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		} else {
			if ( 'yes' === AnWPFL_Options::get_value( 'hide_not_used_seasons' ) ) {
				if ( ! empty( $filter ) && ! empty( $filter['context'] ) && absint( $filter['id'] ) ) {
					if ( 'player' === $filter['context'] ) {
						$active_season_id = anwp_football_leagues()->get_active_player_season( $filter['id'] );
					} elseif ( 'club' === $filter['context'] ) {
						$active_season_id = anwp_football_leagues()->get_active_club_season( $filter['id'] );
					} elseif ( 'stadium' === $filter['context'] ) {
						$active_season_id = anwp_football_leagues()->get_active_stadium_season( $filter['id'] );
					} elseif ( 'referee' === $filter['context'] ) {
						$active_season_id = anwp_football_leagues()->get_active_referee_season( $filter['id'] );
					}
				}
			}

			if ( empty( $active_season_id ) ) {
				$active_season_id = anwp_football_leagues()->get_active_season();
			}

			$active_season = anwp_football_leagues()->season->get_season_slug_by_id( $active_season_id );

			if ( 'all' !== $season_id ) {
				$selected_season = intval( $season_id ) === $active_season_id
					? $active_season
					: anwp_football_leagues()->season->get_season_slug_by_id( $season_id );
			} else {
				$selected_season = 'all';
			}

			ob_start();
			?>
			<select class="anwp-fl-season-dropdown anwp-text-sm <?php echo esc_attr( $class ); ?>">
				<?php
				foreach ( $season_options as $s ) :
					$data_url = $s['slug'] === $active_season ? remove_query_arg( 'season' ) : add_query_arg( 'season', $s['slug'] );
					?>
					<option <?php selected( $s['slug'], $selected_season ); ?> data-href="<?php echo esc_url( $data_url ); ?>" value="<?php echo esc_attr( $s['slug'] ); ?>"><?php echo esc_attr( $s['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		}

		$output = ob_get_clean();

		/**
		 * Filter season dropdown output.
		 *
		 * @param string $output
		 * @param int    $season_id
		 *
		 * @since 0.10.8
		 */
		$output = apply_filters( 'anwpfl/layout/season_dropdown', $output, $season_id );

		if ( $echo ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $output;
		}

		return $output;
	}

	/**
	 * Get filtered list of seasons
	 *
	 * @param array $options Available season options
	 * @param array $filter  Filter Options (context and ID)
	 *
	 * @return array
	 * @since 0.11.6
	 */
	public function filter_season_list( $options, $filter ) {

		// Check season filter is set in options
		if ( 'yes' !== AnWPFL_Options::get_value( 'hide_not_used_seasons' ) ) {
			return $options;
		}

		$filter = wp_parse_args(
			$filter,
			[
				'context' => '',
				'id'      => 0,
			]
		);

		// Validate filter data
		if ( empty( $filter['context'] ) || ! in_array( $filter['context'], [ 'player', 'club', 'stadium', 'referee' ], true ) || ! absint( $filter['id'] ) ) {
			return $options;
		}

		$season_slugs     = $this->get_filtered_seasons( $filter['context'], absint( $filter['id'] ) );
		$filtered_options = [];

		foreach ( $options as $option ) {
			if ( in_array( $option['slug'], $season_slugs, true ) ) {
				$filtered_options[] = $option;
			}
		}

		return $filtered_options;
	}

	/**
	 * Get number of matches for selected competition.
	 *
	 * @param string $type
	 * @param int    $id
	 *
	 * @return array
	 * @since 0.10.0
	 */
	public function get_filtered_seasons( $type, $id ) {

		static $options = [
			'player'  => [],
			'club'    => [],
			'stadium' => [],
			'referee' => [],
		];

		// Validate data
		if ( ! in_array( $type, [ 'club', 'player', 'stadium', 'referee' ], true ) ) {
			return [];
		}

		// Return cached
		if ( ! empty( $options[ $type ][ absint( $id ) ] ) ) {
			return $options[ $type ][ absint( $id ) ];
		}

		global $wpdb;

		if ( 'player' === $type ) {
			$options[ $type ][ absint( $id ) ] = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT DISTINCT t.slug
					FROM {$wpdb->prefix}anwpfl_players a
					LEFT JOIN {$wpdb->prefix}anwpfl_matches m ON m.match_id = a.match_id
					LEFT JOIN {$wpdb->terms} t ON t.term_id = m.season_id
					WHERE a.player_id = %d
					",
					$id
				)
			);

			/*
			|--------------------------------------------------------------------
			| Manual Stats
			|--------------------------------------------------------------------
			*/
			$manual_stats = $this->plugin->player->get_manual_stats( $id );

			if ( ! empty( $manual_stats ) ) {
				foreach ( $manual_stats as $stat_row ) {
					$season_slug = anwp_football_leagues()->season->get_season_slug_by_id( $stat_row->season_id );

					if ( ! in_array( $season_slug, $options[ $type ][ absint( $id ) ], true ) ) {
						$options[ $type ][ absint( $id ) ][] = $season_slug;
					}
				}
			}
		} elseif ( 'club' === $type ) {
			$options[ $type ][ absint( $id ) ] = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT DISTINCT t.slug
					FROM {$wpdb->prefix}anwpfl_matches a
					LEFT JOIN {$wpdb->terms} t ON t.term_id = a.season_id
					WHERE a.home_club = %d OR a.away_club = %d
					",
					$id,
					$id
				)
			);

			/*
			|--------------------------------------------------------------------
			| Get club squad slugs
			|--------------------------------------------------------------------
			*/
			$squad_season_ids = anwp_football_leagues()->club->get_club_squad_season_ids( $id );

			if ( ! empty( $squad_season_ids ) ) {
				foreach ( $squad_season_ids as $squad_season_id ) {
					$squad_season_slug = anwp_football_leagues()->season->get_season_slug_by_id( $squad_season_id );

					if ( $squad_season_slug && ! in_array( $squad_season_slug, $options[ $type ][ absint( $id ) ], true ) ) {
						$options[ $type ][ absint( $id ) ][] = $squad_season_slug;
					}
				}
			}
		} elseif ( 'stadium' === $type ) {
			$options[ $type ][ absint( $id ) ] = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT DISTINCT t.slug
					FROM {$wpdb->prefix}anwpfl_matches a
					LEFT JOIN {$wpdb->terms} t ON t.term_id = a.season_id
					WHERE a.stadium_id = %d
					",
					$id
				)
			);
		} elseif ( 'referee' === $type ) {
			$options[ $type ][ absint( $id ) ] = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT DISTINCT t.slug
					FROM {$wpdb->prefix}anwpfl_matches a
					LEFT JOIN $wpdb->postmeta pm ON ( pm.post_id = a.match_id AND pm.meta_key IN ( '_anwpfl_assistant_1', '_anwpfl_assistant_2', '_anwpfl_referee_fourth' ) )
					LEFT JOIN {$wpdb->terms} t ON t.term_id = a.season_id
					WHERE pm.meta_value = %d OR a.referee = %d
					",
					$id,
					$id
				)
			);

			if ( anwp_football_leagues()->referee->site_has_additional_referees() ) {
				$additional_referees = $wpdb->get_col(
					$wpdb->prepare( // phpcs:disable
						"
						SELECT DISTINCT t.slug
						FROM {$wpdb->prefix}anwpfl_matches a
						LEFT JOIN $wpdb->postmeta pm ON ( pm.post_id = a.match_id AND pm.meta_key = '_anwpfl_additional_referees' )
						LEFT JOIN {$wpdb->terms} t ON t.term_id = a.season_id
						WHERE pm.meta_value LIKE '%\"%d\"%'
						",
						$id
					)
				); // phpcs:enable

				$options[ $type ][ absint( $id ) ] = array_merge( $options[ $type ][ absint( $id ) ], $additional_referees );
			}
		}

		return empty( $options[ $type ][ absint( $id ) ] ) ? [] : $options[ $type ][ absint( $id ) ];
	}

	/**
	 * Rendering club form.
	 *
	 * @param int $club_id
	 * @param bool $echo
	 *
	 * @return string
	 * @since 0.5.0 (2018-03-14)
	 */
	public function club_form( $club_id, $echo = true ) {

		global $wpdb;
		$series_map = anwp_football_leagues()->data->get_series();

		// Get latest matches
		$matches = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT *
				FROM {$wpdb->prefix}anwpfl_matches
				WHERE finished = 1 AND ( home_club = %d OR away_club = %d )
				ORDER BY kickoff DESC
				LIMIT 5
				",
				$club_id,
				$club_id
			)
		);

		$matches = array_reverse( $matches );

		ob_start();
		?>
		<div class="club-form">
			<?php
			foreach ( $matches as $match ) :

				$match_result = 'w';
				$result_class = 'anwp-bg-success';

				if ( $match->home_goals === $match->away_goals ) {
					$match_result = 'd';
					$result_class = 'anwp-bg-warning';
				} elseif ( ( (int) $club_id === (int) $match->home_club && $match->home_goals < $match->away_goals ) || ( (int) $club_id === (int) $match->away_club && $match->home_goals > $match->away_goals ) ) {
					$match_result = 'l';
					$result_class = 'anwp-bg-danger';
				}
				?>
				<span class="my-1 d-inline-block club-form__item px-1 anwp-text-white <?php echo esc_attr( $result_class ); ?>">
					<?php echo esc_html( mb_strtoupper( $series_map[ $match_result ] ) ); ?>
				</span>
			<?php endforeach; ?>
		</div>
		<?php
		$output = ob_get_clean();

		if ( $echo ) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return $output;
	}

	/**
	 * Converts a string to a bool.
	 * From WOO
	 *
	 * @param string|int|bool $string_value String to convert.
	 *
	 * @return bool
	 * @deprecated - use AnWP_Football_Leagues::string_to_bool
	 *
	 * @since      0.7.4
	 */
	public function string_to_bool( $string_value ): bool {
		return is_bool( $string_value ) ? $string_value : ( 1 === $string_value || 'yes' === $string_value || 'true' === $string_value || '1' === $string_value );
	}

	/**
	 * Get default player photo.
	 *
	 * @since 0.8.3
	 * @return string
	 */
	public function get_default_player_photo() {

		static $photo = null;

		if ( null === $photo ) {

			// Get photo from plugin options
			$photo = anwp_football_leagues()->customizer->get_value( 'player', 'default_player_photo' );

			if ( ! $photo ) {
				$photo = AnWP_Football_Leagues::url( 'public/img/empty_player.png' );
			}
		}

		return $photo;
	}

	/**
	 * Get default club logo.
	 *
	 * @return string
	 * @since 0.10.23
	 */
	public function get_default_club_logo() {

		if ( 'no' === anwp_football_leagues()->customizer->get_value( 'club', 'show_default_club_logo' ) ) {
			return '';
		}

		// Get photo from plugin options
		$logo = anwp_football_leagues()->customizer->get_value( 'club', 'default_club_logo' );

		if ( ! $logo ) {
			$logo = AnWP_Football_Leagues::url( 'public/img/empty_logo.png' );
		}

		return $logo;
	}

	/**
	 * Get array of dates.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_selector_data( WP_REST_Request $request ) {

		$params = $request->get_params();
		$args   = self::parse_rest_url_params( $params['args'] );

		$args = wp_parse_args(
			$args,
			[
				'context'   => '',
				's'         => '',
				'clubs'     => '',
				'countries' => '',
				'club_away' => '',
				'club_home' => '',
				'seasons'   => '',
				'leagues'   => '',
				'stages'    => '',
			]
		);

		if ( ! in_array( $args['context'], [ 'player', 'staff', 'referee', 'club', 'match', 'competition', 'main_stage', 'stage', 'season', 'league' ], true ) ) {
			return rest_ensure_response( [] );
		}

		$items = [];

		switch ( $args['context'] ) {
			case 'competition':
				$items = $this->get_selector_competition_data( $args );
				break;

			case 'main_stage':
			case 'stage':
				$items = $this->get_selector_stage_data( $args );
				break;

			case 'club':
				$items = $this->get_selector_club_data( $args );
				break;

			case 'player':
				$items = $this->get_selector_player_data( $args );
				break;

			case 'staff':
				$items = $this->get_selector_staff_data( $args );
				break;

			case 'referee':
				$items = $this->get_selector_referee_data( $args );
				break;

			case 'season':
				$items = $this->get_selector_season_data( $args );
				break;

			case 'league':
				$items = $this->get_selector_league_data( $args );
				break;

			case 'match':
				$items = $this->get_selector_game_data( $args );
				break;
		}

		return rest_ensure_response( $items );
	}

	/**
	 * Get selector competition data.
	 *
	 * @param array $search_data
	 *
	 * @since 0.11.15
	 * @return array
	 */
	private function get_selector_competition_data( array $search_data ): array {

		$query_args = [
			'post_type'   => [ 'anwp_competition' ],
			'numberposts' => 30,
			's'           => $search_data['s'],
			'orderby'     => 'title',
			'order'       => 'ASC',
		];

		if ( 'yes' === $search_data['stages'] ) {
			$query_args['post_status'] = [ 'publish', 'stage_secondary' ];
		}

		$tax_query = [];

		if ( absint( $search_data['seasons'] ?? 0 ) ) {
			$tax_query[] =
				[
					'taxonomy' => 'anwp_season',
					'terms'    => absint( $search_data['seasons'] ),
				];
		}

		if ( absint( $search_data['leagues'] ?? 0 ) ) {
			$tax_query[] =
				[
					'taxonomy' => 'anwp_league',
					'terms'    => absint( $search_data['leagues'] ),
				];
		}

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query;
		}

		$all_competitions = get_posts( $query_args );
		$output_data      = [];

		/** @var WP_Post $competition */
		foreach ( $all_competitions as $competition ) {

			$obj             = (object) [];
			$obj->id         = $competition->ID;
			$obj->title      = $competition->post_title;
			$obj->season     = '';
			$obj->multistage = get_post_meta( $competition->ID, '_anwpfl_multistage', true );

			$obj->title_full = $obj->title;
			$obj->logo       = anwp_fl()->competition->get_competition_data( $competition->ID )['logo'] ?? '';

			// Check multistage
			if ( '' !== $obj->multistage ) {

				$stage_title = get_post_meta( $competition->ID, '_anwpfl_stage_title', true );

				if ( $stage_title ) {
					$obj->title_full .= ' - ' . $stage_title;
				}
			}

			// Get Season and League
			$terms = wp_get_post_terms( $competition->ID, [ 'anwp_season' ] );

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( 'anwp_season' === $term->taxonomy ) {
						$obj->season .= $term->name . ' ';
					}
				}
			}

			if ( 'stage_secondary' === $competition->post_status ) {
				$obj->title_full  = '- ' . $obj->title_full;
				$obj->stage_order = get_post_meta( $competition->ID, '_anwpfl_stage_order', true );

				$secondary_stages[ get_post_meta( $competition->ID, '_anwpfl_multistage_main', true ) ][] = $obj;
			} else {
				$output_data[] = $obj;
			}
		}

		$clone_data = $output_data;

		foreach ( $clone_data as $main_stage_competition ) {
			if ( ! empty( $secondary_stages[ $main_stage_competition->id ] ) ) {
				$stages = $secondary_stages[ $main_stage_competition->id ];
				$stages = wp_list_sort( $stages, 'stage_order' );
				$index  = array_search( $main_stage_competition->id, wp_list_pluck( $output_data, 'id' ) );

				array_splice( $output_data, $index + 1, 0, $stages );
			}
		}

		$output = [
			'columns' => [
				[
					'slug'  => 'img',
					'title' => '',
				],
				[
					'slug'  => 'title',
					'title' => esc_html__( 'Competition', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'season',
					'title' => esc_html__( 'Season', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'id',
					'title' => esc_html__( 'ID', 'anwp-football-leagues' ),
				],
			],
			'rows'    => [],
		];

		foreach ( $output_data as $competition ) {
			$output['rows'][] = [
				'title'  => $competition->title_full,
				'season' => $competition->season,
				'id'     => $competition->id,
				'img'    => $competition->logo,
			];
		}

		return $output;
	}

	/**
	 * Get Global Selector Data
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_selector_global_options() {

		$options = [];

		// Seasons
		$options['seasons'] = anwp_fl()->helper->get_select2_formatted_options( anwp_fl()->season->get_seasons_options() );

		// Leagues
		$options['leagues'] = [];
		foreach ( anwp_fl()->league->get_league_options() as $league_id => $league_title ) {
			$country = anwp_fl()->data->get_value_by_key( anwp_fl()->league->get_league_country_code( $league_id ), 'country' );

			$options['leagues'][] = [
				'id'   => $league_id,
				'text' => $league_title . ( $country ? " ($country)" : '' ),
			];
		}

		$options['leagues'] = wp_list_sort( $options['leagues'], 'text' );

		// Countries
		$options['countries'] = [];
		foreach ( anwp_fl()->data->cb_get_countries() as $option_key => $option_text ) {
			$code_parsed = mb_strtolower( str_replace( '_', '-', $option_key ) );

			if ( in_array( $option_key, [ '__Africa', '__Asia', '__NC_America', '__Oceania', '__South_America' ], true ) ) {
				$code_parsed = '--world';
			} elseif ( ! in_array( $option_key, anwp_fl()->data->get_available_circle_flags(), true ) ) {
				$code_parsed = 'xx';
			}

			$options['countries'][] = [
				'id'   => $option_key,
				'text' => $option_text,
				'flag' => AnWP_Football_Leagues::url( 'public/img/flags-v2.svg' ) . '#fl-flag--' . $code_parsed,
			];
		}

		$options['countries'] = wp_list_sort( $options['countries'], 'text' );

		// Clubs
		$options['clubs'] = [];
		foreach ( anwp_fl()->club->get_clubs_options() as $option_key => $option_text ) {
			$options['clubs'][] = [
				'id'   => $option_key,
				'text' => $option_text,
				'img'  => anwp_fl()->club->get_club_logo_by_id( $option_key ),
			];
		}

		$options['clubs'] = wp_list_sort( $options['clubs'], 'text' );

		return rest_ensure_response( [ 'options' => $options ] );
	}

	/**
	 * Get Instance Selector Data
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_selector_initial( WP_REST_Request $request ) {

		$params = $request->get_params();
		$args   = self::parse_rest_url_params( $params['args'] );

		$args = wp_parse_args(
			$args,
			[
				'context' => '',
				'initial' => '',
			]
		);

		if ( ! in_array( $args['context'], self::$selector_context, true ) ) {
			return rest_ensure_response( [] );
		}

		if ( empty( trim( $args['initial'] ) ) ) {
			return rest_ensure_response( [] );
		}

		$data_initial = wp_parse_id_list( $args['initial'] );
		$output       = '';

		switch ( $args['context'] ) {
			case 'player':
				$output = $this->get_selector_player_initial( $data_initial );
				break;

			case 'staff':
				$output = $this->get_selector_staff_initial( $data_initial );
				break;

			case 'referee':
				$output = $this->get_selector_referee_initial( $data_initial );
				break;

			case 'club':
				$output = $this->get_selector_club_initial( $data_initial );
				break;

			case 'match':
				$output = $this->get_selector_match_initial( $data_initial );
				break;

			case 'main_stage':
			case 'stage':
			case 'competition':
				$output = $this->get_selector_competition_initial( $data_initial );
				break;

			case 'season':
				$output = $this->get_selector_season_initial( $data_initial );
				break;

			case 'league':
				$output = $this->get_selector_league_initial( $data_initial );
				break;
		}

		return rest_ensure_response( [ 'items' => $output ] );
	}

	/**
	 * Get selector staff initial data.
	 *
	 * @param array $data_initial
	 *
	 * @return array
	 * @since 0.12.4
	 */
	private function get_selector_staff_initial( array $data_initial ): array {

		$query_args = [
			'post_type'               => [ 'anwp_staff' ],
			'posts_per_page'          => 30,
			'include'                 => $data_initial,
			'cache_results'           => false,
			'update_post_meta_cache'  => false,
			'update_post_term_cache ' => false,
		];

		$results = get_posts( $query_args );

		if ( empty( $results ) || ! is_array( $results ) ) {
			return [];
		}

		$output = [];

		foreach ( $results as $result_item ) {
			$output[] = [
				'id'    => $result_item->ID,
				'title' => $result_item->post_title,
			];
		}

		return $output;
	}

	/**
	 * Get selector referee initial data.
	 *
	 * @param array $data_initial
	 *
	 * @return array
	 * @since 0.12.4
	 */
	private function get_selector_referee_initial( array $data_initial ): array {

		$query_args = [
			'post_type'               => [ 'anwp_referee' ],
			'posts_per_page'          => 30,
			'include'                 => $data_initial,
			'cache_results'           => false,
			'update_post_meta_cache'  => false,
			'update_post_term_cache ' => false,
		];

		$results = get_posts( $query_args );

		if ( empty( $results ) || ! is_array( $results ) ) {
			return [];
		}

		$output = [];

		foreach ( $results as $result_item ) {
			$output[] = [
				'id'    => $result_item->ID,
				'title' => $result_item->post_title,
			];
		}

		return $output;
	}

	/**
	 * Get selector club initial data.
	 *
	 * @param array $data_initial
	 *
	 * @return array
	 * @since 0.11.8
	 */
	private function get_selector_club_initial( array $data_initial ): array {

		$query_args = [
			'post_type'               => [ 'anwp_club' ],
			'posts_per_page'          => 50,
			'include'                 => $data_initial,
			'cache_results'           => false,
			'update_post_meta_cache'  => false,
			'update_post_term_cache ' => false,
		];

		$results = get_posts( $query_args );

		if ( empty( $results ) || ! is_array( $results ) ) {
			return [];
		}

		$output = [];

		foreach ( $results as $result_item ) {
			$output[] = [
				'id'    => $result_item->ID,
				'title' => $result_item->post_title,
				'img'   => esc_html( get_post_meta( $result_item->ID, '_anwpfl_logo', true ) ) ?: '',
			];
		}

		return $output;
	}

	/**
	 * Get selector player initial data.
	 *
	 * @param array $data_initial
	 *
	 * @return array
	 * @since 0.11.7
	 */
	private function get_selector_player_initial( array $data_initial ): array {

		$results = anwp_fl()->player->get_players_by_ids( wp_parse_id_list( $data_initial ), false );

		if ( empty( $results ) ) {
			return [];
		}

		$output = [];

		foreach ( $results as $result_item ) {
			$output[] = [
				'id'    => $result_item['player_id'],
				'title' => $result_item['short_name'],
				'img'   => $result_item['photo'] ? anwp_fl()->upload_dir . $result_item['photo'] : '',
			];
		}

		return $output;
	}

	/**
	 * Get selector player data.
	 *
	 * @param array $search_data
	 *
	 * @return array
	 * @since 0.11.7
	 */
	private function get_selector_player_data( array $search_data ): array {
		global $wpdb;

		$query =
			$wpdb->prepare(
				"
				SELECT `player_id`, `short_name`, `name`, `date_of_birth`, `name`, `photo`, `position`, `team_id`, `nationality`
				FROM $wpdb->anwpfl_player_data
				WHERE `name` LIKE %s
				",
				'%' . $wpdb->esc_like( sanitize_text_field( $search_data['s'] ) ) . '%'
			);

		if ( ! empty( $search_data['countries'] ) ) {
			$query .= $wpdb->prepare( ' AND nationality = %s ', sanitize_text_field( $search_data['countries'] ) );
		}

		if ( ! empty( $search_data['clubs'] ) && absint( $search_data['clubs'] ) ) {
			$query .= $wpdb->prepare( ' AND team_id = %d ', absint( $search_data['clubs'] ) );
		}

		// bump query
		$query .= ' ORDER BY name';
		$query .= ' LIMIT 30';

		$player_list = $wpdb->get_results( $query ) ?: []; // phpcs:ignore WordPress.DB.PreparedSQL

		$output = [
			'columns' => [
				[
					'slug'  => 'img',
					'title' => '',
				],
				[
					'slug'  => 'title',
					'title' => esc_html__( 'Player Name', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'date_of_birth',
					'title' => esc_html__( 'Date of Birth', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'club',
					'title' => esc_html__( 'Club', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'flag',
					'title' => esc_html__( 'Country', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'id',
					'title' => esc_html__( 'ID', 'anwp-football-leagues' ),
				],
			],
			'rows'    => [],
		];

		foreach ( $player_list as $player ) {
			$country_code = $player->nationality;
			$code_parsed  = '';

			if ( $country_code ) {
				$code_parsed = mb_strtolower( str_replace( '_', '-', $country_code ) );

				if ( in_array( $country_code, [ '__Africa', '__Asia', '__NC_America', '__Oceania', '__South_America' ], true ) ) {
					$code_parsed = '--world';
				} elseif ( ! in_array( $country_code, anwp_fl()->data->get_available_circle_flags(), true ) ) {
					$code_parsed = 'xx';
				}
			}

			$output['rows'][] = [
				'title'         => $player->short_name ? : $player->name,
				'id'            => $player->player_id,
				'date_of_birth' => esc_html( '0000-00-00' !== $player->date_of_birth ? $player->date_of_birth : '' ),
				'flag'          => $country_code ? ( AnWP_Football_Leagues::url( 'public/img/flags-v2.svg' ) . '#fl-flag--' . $code_parsed ) : '',
				'country'       => $country_code ? ( anwp_fl()->data->get_value_by_key( $country_code, 'country' ) ) : '',
				'img'           => $player->photo ? anwp_fl()->upload_dir . $player->photo : '',
				'club'          => anwp_fl()->club->get_club_title_by_id( $player->team_id ),
			];
		}

		return $output;
	}

	/**
	 * Get selector staff data.
	 *
	 * @param array $search_data
	 *
	 * @return array
	 * @since 0.12.4
	 */
	private function get_selector_staff_data( array $search_data ): array {

		$query_args = [
			'post_type'      => [ 'anwp_staff' ],
			'posts_per_page' => 30,
			's'              => $search_data['s'],
		];

		$meta_query = [];

		if ( ! empty( $search_data['clubs'] ) && absint( $search_data['clubs'] ) ) {
			$meta_query[] = [
				'key'   => '_anwpfl_current_club',
				'value' => absint( $search_data['clubs'] ),
			];
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$results = get_posts( $query_args );

		$output = [
			'columns' => [
				[
					'slug'  => 'title',
					'title' => esc_html__( 'Staff Name', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'date_of_birth',
					'title' => esc_html__( 'Date of Birth', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'club',
					'title' => esc_html__( 'Club', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'flag',
					'title' => esc_html__( 'Country', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'id',
					'title' => esc_html__( 'ID', 'anwp-football-leagues' ),
				],
			],
			'rows'    => [],
		];

		/** @var @WP_Post $staff_post */
		foreach ( $results as $staff_post ) {
			$country_code = get_post_meta( $staff_post->ID, '_anwpfl_nationality', true )[0] ?? '';
			$code_parsed  = '';

			if ( $country_code ) {
				$code_parsed = mb_strtolower( str_replace( '_', '-', $country_code ) );

				if ( in_array( $country_code, [ '__Africa', '__Asia', '__NC_America', '__Oceania', '__South_America' ], true ) ) {
					$code_parsed = '--world';
				} elseif ( ! in_array( $country_code, anwp_fl()->data->get_available_circle_flags(), true ) ) {
					$code_parsed = 'xx';
				}
			}

			$output['rows'][] = [
				'title'         => $staff_post->post_title,
				'id'            => $staff_post->ID,
				'date_of_birth' => esc_html( get_post_meta( $staff_post->ID, '_anwpfl_date_of_birth', true ) ? : '' ),
				'flag'          => $country_code ? ( AnWP_Football_Leagues::url( 'public/img/flags-v2.svg' ) . '#fl-flag--' . $code_parsed ) : '',
				'country'       => $country_code ? ( anwp_fl()->data->get_value_by_key( $country_code, 'country' ) ) : '',
				'club'          => anwp_fl()->club->get_club_title_by_id( get_post_meta( $staff_post->ID, '_anwpfl_current_club', true ) ),
			];
		}

		return $output;
	}

	/**
	 * Get selector referee data.
	 *
	 * @param array $search_data
	 *
	 * @return array
	 * @since 0.12.4
	 */
	private function get_selector_referee_data( array $search_data ): array {

		$query_args = [
			'post_type'      => [ 'anwp_referee' ],
			'posts_per_page' => 30,
			's'              => $search_data['s'],
		];

		$meta_query = [];

		if ( ! empty( $search_data['countries'] ) ) {
			$meta_query[] = [
				'key'     => '_anwpfl_nationality',
				'value'   => '"' . sanitize_text_field( $search_data['countries'] ) . '"',
				'compare' => 'LIKE',
			];
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$results = get_posts( $query_args );

		$output = [
			'columns' => [
				[
					'slug'  => 'title',
					'title' => esc_html__( 'Referee Name', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'date_of_birth',
					'title' => esc_html__( 'Date of Birth', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'flag',
					'title' => esc_html__( 'Country', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'id',
					'title' => esc_html__( 'ID', 'anwp-football-leagues' ),
				],
			],
			'rows'    => [],
		];

		/** @var @WP_Post $referee_post */
		foreach ( $results as $referee_post ) {
			$country_code = get_post_meta( $referee_post->ID, '_anwpfl_nationality', true )[0] ?? '';
			$code_parsed  = '';

			if ( $country_code ) {
				$code_parsed = mb_strtolower( str_replace( '_', '-', $country_code ) );

				if ( in_array( $country_code, [ '__Africa', '__Asia', '__NC_America', '__Oceania', '__South_America' ], true ) ) {
					$code_parsed = '--world';
				} elseif ( ! in_array( $country_code, anwp_fl()->data->get_available_circle_flags(), true ) ) {
					$code_parsed = 'xx';
				}
			}

			$output['rows'][] = [
				'title'         => $referee_post->post_title,
				'id'            => $referee_post->ID,
				'date_of_birth' => esc_html( get_post_meta( $referee_post->ID, '_anwpfl_date_of_birth', true ) ? : '' ),
				'flag'          => $country_code ? ( AnWP_Football_Leagues::url( 'public/img/flags-v2.svg' ) . '#fl-flag--' . $code_parsed ) : '',
				'country'       => $country_code ? ( anwp_fl()->data->get_value_by_key( $country_code, 'country' ) ) : '',
			];
		}

		return $output;
	}

	/**
	 * Get selector club data.
	 *
	 * @param array $search_data
	 *
	 * @return array
	 */
	private function get_selector_club_data( array $search_data ): array {

		$query_args = [
			'post_type'      => [ 'anwp_club' ],
			'posts_per_page' => 30,
			's'              => $search_data['s'],
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		$meta_query = [];

		if ( ! empty( $search_data['countries'] ) ) {
			$meta_query[] = [
				'key'   => '_anwpfl_nationality',
				'value' => sanitize_text_field( $search_data['countries'] ),
			];
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$clubs = get_posts( $query_args );

		$output = [
			'columns' => [
				[
					'slug'  => 'img',
					'title' => '',
				],
				[
					'slug'  => 'title',
					'title' => esc_html__( 'Club Title', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'city',
					'title' => esc_html__( 'City', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'flag',
					'title' => esc_html__( 'Country', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'id',
					'title' => esc_html__( 'ID', 'anwp-football-leagues' ),
				],
			],
			'rows'    => [],
		];

		/** @var WP_Post $club */
		foreach ( $clubs as $club ) {
			$country_code = get_post_meta( $club->ID, '_anwpfl_nationality', true );
			$code_parsed  = '';

			if ( $country_code ) {
				$code_parsed = mb_strtolower( str_replace( '_', '-', $country_code ) );

				if ( in_array( $country_code, [ '__Africa', '__Asia', '__NC_America', '__Oceania', '__South_America' ], true ) ) {
					$code_parsed = '--world';
				} elseif ( ! in_array( $country_code, anwp_fl()->data->get_available_circle_flags(), true ) ) {
					$code_parsed = 'xx';
				}
			}

			$output['rows'][] = [
				'title'   => $club->post_title,
				'id'      => $club->ID,
				'city'    => esc_html( get_post_meta( $club->ID, '_anwpfl_city', true ) ),
				'flag'    => $country_code ? ( AnWP_Football_Leagues::url( 'public/img/flags-v2.svg' ) . '#fl-flag--' . $code_parsed ) : '',
				'country' => $country_code ? ( anwp_fl()->data->get_value_by_key( $country_code, 'country' ) ) : '',
				'img'     => esc_html( get_post_meta( $club->ID, '_anwpfl_logo', true ) ) ?: '',
			];
		}

		return $output;
	}

	/**
	 * Get selector games data.
	 *
	 * @param array $search_data
	 *
	 * @return array
	 * @since 0.11.13
	 */
	private function get_selector_game_data( array $search_data ): array {

		$args = [
			'season_id'    => absint( $search_data['seasons'] ) ?: '',
			'league_id'    => absint( $search_data['leagues'] ) ?: '',
			'home_club'    => absint( $search_data['club_home'] ),
			'away_club'    => absint( $search_data['club_away'] ),
			'sort_by_date' => 'asc',
			'limit'        => 40,
		];

		if ( ! empty( $search_data['date'] ) ) {
			$args['date_to']   = sanitize_text_field( $search_data['date'] );
			$args['date_from'] = sanitize_text_field( $search_data['date'] );
		}

		$games = anwp_fl()->competition->tmpl_get_competition_matches_extended( $args, 'stats' );

		$output = [
			'columns' => [
				[
					'slug'  => 'title',
					'title' => '',
				],
				[
					'slug'  => 'id',
					'title' => esc_html__( 'ID', 'anwp-football-leagues' ),
				],
			],
			'rows'    => [],
		];

		foreach ( $games as $game ) {
			$club_home_title = anwp_fl()->club->get_club_title_by_id( $game->home_club );
			$club_away_title = anwp_fl()->club->get_club_title_by_id( $game->away_club );
			$game_date       = '0000-00-00' === explode( ' ', $game->kickoff )[0] ? '' : explode( ' ', $game->kickoff )[0];
			$game_scores     = absint( $game->finished ) ? ( $game->home_goals . ':' . $game->away_goals ) : '?:?';

			$game_title = $club_home_title . ' - ' . $club_away_title . ' / ' . $game_date . ' / ' . $game_scores;

			$output['rows'][] = [
				'title' => $game_title,
				'id'    => $game->match_id,
			];
		}

		return $output;
	}

	/**
	 * Get selector match initial data.
	 *
	 * @param array $data_initial
	 *
	 * @return array
	 * @since 0.11.13
	 */
	private function get_selector_match_initial( array $data_initial ): array {
		$args = [
			'include_ids'  => implode( ',', $data_initial ),
			'sort_by_date' => 'asc',
		];

		$games = anwp_fl()->competition->tmpl_get_competition_matches_extended( $args, 'stats' );

		if ( empty( $games ) || ! is_array( $games ) ) {
			return [];
		}

		$output = [];

		foreach ( $games as $game ) {
			$club_home_title = anwp_fl()->club->get_club_title_by_id( $game->home_club );
			$club_away_title = anwp_fl()->club->get_club_title_by_id( $game->away_club );
			$game_date       = '0000-00-00' === explode( ' ', $game->kickoff )[0] ? '' : explode( ' ', $game->kickoff )[0];
			$game_scores     = absint( $game->finished ) ? ( $game->home_goals . ':' . $game->away_goals ) : '?:?';

			$output[] = [
				'id'    => $game->match_id,
				'title' => $club_home_title . ' - ' . $club_away_title . ' / ' . $game_date . ' / ' . $game_scores,
			];
		}

		return $output;
	}

	/**
	 * Get selector competition initial data.
	 *
	 * @param array $data_initial
	 *
	 * @since 0.11.15
	 * @return array
	 */
	private function get_selector_competition_initial( array $data_initial ): array {

		$output = [];
		foreach ( $data_initial as $id ) {
			$competition_data = anwp_fl()->competition->get_competition_data( $id );

			if ( $competition_data['id'] ?? 0 ) {
				$output[] = [
					'id'    => $competition_data['id'],
					'title' => $competition_data['title'],
					'img'   => $competition_data['logo'],
				];
			}
		}

		return $output;
	}

	/**
	 * Get selector Season initial data.
	 *
	 * @param array $data_initial
	 *
	 * @return array
	 * @since 0.12.3
	 */
	private function get_selector_season_initial( array $data_initial ): array {

		$query_args = [
			'number'     => 50,
			'include'    => $data_initial,
			'orderby'    => 'name',
			'taxonomy'   => 'anwp_season',
			'hide_empty' => false,
		];

		$results = get_terms( $query_args );

		if ( empty( $results ) || ! is_array( $results ) ) {
			return [];
		}

		$output = [];

		foreach ( $results as $season_obj ) {
			$output[] = [
				'id'    => $season_obj->term_id,
				'title' => $season_obj->name,
			];
		}

		return $output;
	}

	/**
	 * Get selector League initial data.
	 *
	 * @param array $data_initial
	 *
	 * @return array
	 * @since 0.12.3
	 */
	private function get_selector_league_initial( array $data_initial ): array {
		$query_args = [
			'number'     => 50,
			'include'    => $data_initial,
			'orderby'    => 'name',
			'taxonomy'   => 'anwp_league',
			'hide_empty' => false,
		];

		$results = get_terms( $query_args );

		if ( empty( $results ) || ! is_array( $results ) ) {
			return [];
		}

		$output = [];

		foreach ( $results as $league_obj ) {
			$output[] = [
				'id'    => $league_obj->term_id,
				'title' => $league_obj->name,
			];
		}

		return $output;
	}

	/**
	 * Get selector Season data.
	 *
	 * @param array $search_data
	 *
	 * @return array
	 * @since 0.12.3
	 */
	private function get_selector_season_data( array $search_data ): array {
		$all_seasons = get_terms(
			[
				'number'     => 50,
				'search'     => $search_data['s'],
				'orderby'    => 'name',
				'taxonomy'   => 'anwp_season',
				'hide_empty' => false,
			]
		);

		$output = [
			'columns' => [
				[
					'slug'  => 'title',
					'title' => esc_html__( 'Season', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'id',
					'title' => esc_html__( 'ID', 'anwp-football-leagues' ),
				],
			],
			'rows'    => [],
		];

		foreach ( $all_seasons as $season_obj ) {
			$output['rows'][] = [
				'id'    => $season_obj->term_id,
				'title' => $season_obj->name,
			];
		}

		return $output;
	}

	/**
	 * Get selector League data.
	 *
	 * @param array $search_data
	 *
	 * @return array
	 * @since 0.12.3
	 */
	private function get_selector_league_data( array $search_data ): array {
		$all_leagues = get_terms(
			[
				'number'     => 50,
				'search'     => $search_data['s'],
				'orderby'    => 'name',
				'taxonomy'   => 'anwp_league',
				'hide_empty' => false,
			]
		);

		$output = [
			'columns' => [
				[
					'slug'  => 'title',
					'title' => esc_html__( 'League', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'flag',
					'title' => esc_html__( 'Country', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'id',
					'title' => esc_html__( 'ID', 'anwp-football-leagues' ),
				],
			],
			'rows'    => [],
		];

		foreach ( $all_leagues as $league_obj ) {
			$country_code = anwp_fl()->league->get_league_country_code( $league_obj->term_id ) ?: '';
			$code_parsed  = '';

			if ( $country_code ) {
				$code_parsed = mb_strtolower( str_replace( '_', '-', $country_code ) );

				if ( in_array( $country_code, [ '__Africa', '__Asia', '__NC_America', '__Oceania', '__South_America' ], true ) ) {
					$code_parsed = '--world';
				} elseif ( ! in_array( $country_code, anwp_fl()->data->get_available_circle_flags(), true ) ) {
					$code_parsed = 'xx';
				}
			}

			$output['rows'][] = [
				'id'      => $league_obj->term_id,
				'title'   => $league_obj->name,
				'flag'    => $country_code ? ( AnWP_Football_Leagues::url( 'public/img/flags-v2.svg' ) . '#fl-flag--' . $code_parsed ) : '',
				'country' => $country_code ? ( anwp_fl()->data->get_value_by_key( $country_code, 'country' ) ) : '',
			];
		}

		return $output;
	}

	/**
	 * Get selector competition data.
	 *
	 * @param array $search_data
	 *
	 * @since 0.16.13
	 * @return array
	 */
	private function get_selector_stage_data( array $search_data ): array {

		$competitions = array_filter(
			anwp_fl()->competition->get_competitions_data(),
			function ( $competition ) use ( $search_data ) {

				if ( 'main_stage' === $search_data['context'] && 'secondary' === $competition['multistage'] ) {
					return false;
				}

				if ( trim( $search_data['s'] ) && ! str_contains( mb_strtolower( $competition['title_full'] ), mb_strtolower( $search_data['s'] ) ) ) {
					return false;
				}

				if ( absint( $search_data['leagues'] ) && absint( $search_data['leagues'] ) !== absint( $competition['league_id'] ) ) {
					return false;
				}

				if ( absint( $search_data['seasons'] ) && absint( $search_data['seasons'] ) !== absint( $competition['season_ids'] ) ) {
					return false;
				}

				return true;
			}
		);

		$competitions = array_slice( wp_list_sort( $competitions, 'c_index' ), 0, 30 );

		$competitions = array_map(
			function ( $competition ) use ( $search_data ) {

				if ( 'main_stage' === $search_data['context'] && 'secondary' !== $competition['multistage'] ) {
					$competition['title_full'] = $competition['title'];
				}

				return $competition;
			},
			$competitions
		);

		$output = [
			'columns' => [
				[
					'slug'  => 'img',
					'title' => '',
				],
				[
					'slug'  => 'title',
					'title' => esc_html__( 'Competition', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'season',
					'title' => esc_html__( 'Season', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'flag',
					'title' => esc_html__( 'Country', 'anwp-football-leagues' ),
				],
				[
					'slug'  => 'id',
					'title' => esc_html__( 'ID', 'anwp-football-leagues' ),
				],
			],
			'rows'    => [],
		];

		foreach ( $competitions as $competition ) {
			$country_code = anwp_fl()->league->get_league_country_code( $competition['league_id'] );
			$code_parsed  = '';

			if ( $country_code ) {
				$code_parsed = mb_strtolower( str_replace( '_', '-', $country_code ) );

				if ( in_array( $country_code, [ '__Africa', '__Asia', '__NC_America', '__Oceania', '__South_America' ], true ) ) {
					$code_parsed = '--world';
				} elseif ( ! in_array( $country_code, anwp_fl()->data->get_available_circle_flags(), true ) ) {
					$code_parsed = 'xx';
				}
			}

			$output['rows'][] = [
				'title'   => $competition['title_full'],
				'season'  => $competition['season_text'],
				'id'      => $competition['id'],
				'flag'    => $country_code ? ( AnWP_Football_Leagues::url( 'public/img/flags-v2.svg' ) . '#fl-flag--' . $code_parsed ) : '',
				'country' => $country_code ? ( anwp_fl()->data->get_value_by_key( $country_code, 'country' ) ) : '',
				'img'     => 'secondary' === $competition['multistage'] ? anwp_fl()->competition->get_competitions_data()[ $competition['multistage_main'] ]['logo'] ?? '' : $competition['logo'],
			];
		}

		return $output;
	}

	/**
	 * Recursive sanitization.
	 *
	 * @param string|array $value
	 *
	 * @return string|array
	 */
	public function recursive_sanitize( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'recursive_sanitize' ], $value );
		} else {
			return is_scalar( $value ) ? sanitize_text_field( $value ) : $value;
		}
	}

	/**
	 * Recursive entity_decode.
	 *
	 * @param string|array $value
	 *
	 * @return string|array
	 */
	public function recursive_entity_decode( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'recursive_entity_decode' ], $value );
		} else {
			return is_scalar( $value ) ? html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' ) : $value;
		}
	}

	/**
	 * Entity_decode.
	 *
	 * @param string|array $value
	 *
	 * @return string|array
	 */
	public function entity_decode( $l10n ) {
		if ( is_string( $l10n ) ) {
			$l10n = html_entity_decode( $l10n, ENT_QUOTES, 'UTF-8' );
		} elseif ( is_array( $l10n ) ) {
			foreach ( $l10n as $key => $value ) {
				if ( ! is_scalar( $value ) ) {
					continue;
				}

				$l10n[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
			}
		}

		return $l10n;
	}

	/**
	 * Get options in Select2 format
	 *
	 * @param array
	 *
	 * @return array
	 */
	public function get_select2_formatted_options( $options ) {
		$output = [];

		foreach ( $options as $option_key => $option_text ) {
			$output[] = [
				'id'   => $option_key,
				'text' => $option_text,
			];
		}

		return $output;
	}

	/**
	 * Get Youtube ID from url
	 *
	 * @param $url
	 *
	 * @return string Youtube ID or empty string
	 */
	public function get_youtube_id( $url ) {

		if ( mb_strlen( $url ) <= 11 ) {
			return $url;
		}

		preg_match( "/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $url, $matches );

		return $matches[1] ?? '';
	}

	/**
	 * Create New League
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * @since 0.12.0
	 */
	public function create_new_league( WP_REST_Request $request ) {

		$params      = $request->get_params();
		$league_name = isset( $params['league_name'] ) ? sanitize_text_field( $params['league_name'] ) : '';

		if ( empty( $league_name ) ) {
			return new WP_Error( 'rest_invalid', 'Invalid League Name', [ 'status' => 400 ] );
		}

		$base_slug = sanitize_title( $params['league_name'] );
		if ( ! empty( $params['country_code'] ) ) {
			$base_slug .= '-' . sanitize_title( $params['country_code'] );
		}

		$maybe_slug = anwp_fl()->league->generate_unique_league_slug( $base_slug );

		$insert_result = wp_insert_term(
			$league_name,
			'anwp_league',
			[
				'slug' => $maybe_slug,
			]
		);

		// Handle all possible error cases
		if ( is_wp_error( $insert_result ) ) {
			if ( $insert_result->get_error_code() === 'term_exists' ) {
				return new WP_Error( 'rest_duplicate', 'League already exists', [ 'status' => 409 ] );
			}
			return new WP_Error( 'rest_invalid', 'Failed to create league: ' . $insert_result->get_error_message(), [ 'status' => 500 ] );
		}

		if ( empty( $insert_result['term_id'] ) ) {
			return new WP_Error( 'rest_invalid', 'Failed to create league', [ 'status' => 500 ] );
		}

		if ( ! empty( $params['country_code'] ) ) {
			update_term_meta( $insert_result['term_id'], '_anwpfl_country', sanitize_text_field( $params['country_code'] ) );
		}

		return rest_ensure_response(
			[
				'leagues'           => anwp_football_leagues()->league->get_leagues_list(),
				'created_league_id' => absint( $insert_result['term_id'] ),
			]
		);
	}

	/**
	 * Create New Season
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 * @since 0.12.0
	 */
	public function create_new_season( WP_REST_Request $request ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Access Denied !!!' );
		}

		$params      = $request->get_params();
		$season_name = isset( $params['season_name'] ) ? sanitize_text_field( $params['season_name'] ) : '';

		if ( empty( $season_name ) ) {
			wp_send_json_error( 'Invalid Season Name' );
		}

		$insert_result = wp_insert_term(
			$season_name,
			'anwp_season'
		);

		if ( ! empty( $insert_result ) && ! is_wp_error( $insert_result ) && ! empty( $insert_result['term_id'] ) ) {
			return rest_ensure_response(
				[
					'seasons'   => anwp_football_leagues()->season->get_seasons_list(),
					'season_id' => absint( $insert_result['term_id'] ),
				]
			);
		}

		return rest_ensure_response( new WP_Error( 'rest_invalid', esc_html__( 'Saving Data Error', 'anwp-football-leagues' ), [ 'status' => 400 ] ) );
	}

	/**
	 * Create metabox header
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 0.12.6
	 */
	public function create_metabox_header( $data ) {

		$data = wp_parse_args(
			$data,
			[
				'icon'         => '',
				'classes'      => 'mb-4',
				'icon_classes' => 'anwp-icon--octi',
				'label'        => '',
				'slug'         => '',
			]
		);

		// put some code into echo() to fix formatting issue
		ob_start();
		echo '<div class="anwp-border anwp-border-gray-500 ' . esc_attr( $data['classes'] ) . '" id="' . esc_attr( $data['slug'] ) . '">';
		?>
		<div class="anwp-border-bottom anwp-border-gray-500 bg-white d-flex align-items-center px-1 py-2 anwp-text-gray-700 anwp-font-semibold">
			<svg class="anwp-icon anwp-icon--s16 mx-2 anwp-fill-current <?php echo esc_attr( $data['icon_classes'] ); ?>">
				<use xlink:href="#icon-<?php echo esc_attr( $data['icon'] ); ?>"></use>
			</svg>
			<span><?php echo esc_html( $data['label'] ); ?></span>
		</div>
		<?php
		echo '<div class="bg-white p-3">';
		return ob_get_clean();
	}

	/**
	 * Check update permission.
	 *
	 * @param  WP_REST_Request $request
	 * @return WP_Error|boolean
	 */
	public function update_permissions_check( $request ) {

		$params  = $request->get_params();
		$post_id = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;

		if ( empty( $post_id ) ) {
			return new WP_Error(
				'anwpfl_rest_error',
				__( 'Sorry, you have not permission to edit', 'anwp-football-leagues' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		$post_obj = get_post( $post_id );

		if ( empty( $post_obj->post_type ) || ! in_array( $post_obj->post_type, [ 'anwp_player' ], true ) ) {
			return new WP_Error(
				'anwpfl_rest_error',
				__( 'Sorry, you have not permission to edit', 'anwp-football-leagues' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		if ( ! $this->rest_check_permissions( $post_obj->post_type, 'edit', $post_obj->ID ) ) {
			return new WP_Error(
				'anwpfl_rest_error',
				__( 'Sorry, you have not permission to edit', 'anwp-football-leagues' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Check permissions on REST API.
	 *
	 * @param string $post_type
	 * @param string $context
	 * @param int    $post_id
	 *
	 * @return bool
	 * @since 0.12.6
	 */
	public function rest_check_permissions( $post_type, $context = 'read', $post_id = 0 ) {

		$contexts = [
			'read'   => 'read_private_posts',
			'create' => 'publish_posts',
			'edit'   => 'edit_post',
			'delete' => 'delete_post',
			'batch'  => 'edit_others_posts',
		];

		$cap        = $contexts[ $context ];
		$permission = current_user_can( get_post_type_object( $post_type )->cap->$cap, $post_id );

		return apply_filters( 'anwpfl/rest/check_permissions', $permission, $context, $post_id, $post_type );
	}

	/**
	 * Renders documentation template.
	 *
	 * @param string $shortcode_link
	 * @param string $shortcode_title
	 *
	 * @since 0.12.7
	 */
	public function render_docs_template( $shortcode_link, $shortcode_title ) {
		ob_start();
		?>
		<div class="anwp-shortcode-docs-link">
			<svg class="anwp-icon anwp-icon--octi anwp-icon--s16">
				<use xlink:href="#icon-book"></use>
			</svg>
			<b class="mx-2"><?php echo esc_html__( 'Documentation', 'anwp-football-leagues' ); ?>:</b>
			<a target="_blank" href="<?php echo esc_url( $shortcode_link ); ?>"><?php echo esc_html( $shortcode_title ); ?></a>
		</div>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo ob_get_clean();
	}

	/**
	 * Get links map
	 *
	 * @param array        $ids
	 * @param string $post_type
	 *
	 * @since 0.13.3
	 *@return array
	 */
	public function get_permalinks_by_ids( array $ids, string $post_type ): array {
		global $wpdb;

		if ( empty( $post_type ) || empty( $ids ) ) {
			return [];
		}

		$output = [];

		if ( $this->plugin->cache->simple_link_building ) {
			$post_type_short = sanitize_key( str_replace( 'anwp_', '', $post_type ) );

			$permalink_slug = $this->plugin->options->get_permalink_structure()[ $post_type_short ] ?? $post_type_short;
			$base_url       = get_site_url( null, '/' . $permalink_slug . '/' );

			$query = $wpdb->prepare( "SELECT post_name, ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = %s", $post_type );

			$include_placeholders = array_fill( 0, count( $ids ), '%s' );
			$include_format       = implode( ', ', $include_placeholders );

			$query .= $wpdb->prepare( " AND ID IN ({$include_format})", $ids ); // phpcs:ignore

			$all_posts = $wpdb->get_results( $query, ARRAY_A ) ?: []; // phpcs:ignore

			foreach ( $all_posts as $post_data ) {
				$output[ $post_data['ID'] ] = $base_url . $post_data['post_name'] . '/';
			}
		} else {
			$args = [
				'include'       => $ids,
				'cache_results' => false,
				'post_type'     => $post_type,
			];

			foreach ( get_posts( $args ) as $post_obj ) {
				$output[ $post_obj->ID ] = get_permalink( $post_obj );
			}
		}

		return $output;
	}

	/**
	 * Get season ID
	 *
	 * @param array $get_data
	 * @param int   $season_id
	 *
	 * @return int
	 * @since 0.14.0
	 */
	public function get_season_id_maybe( $get_data, $season_id ) {

		if ( empty( $get_data['season'] ) ) {
			return $season_id;
		}

		$maybe_season_id = anwp_football_leagues()->season->get_season_id_by_slug( sanitize_key( $get_data['season'] ) );

		if ( absint( $maybe_season_id ) ) {
			$season_id = absint( $maybe_season_id );
		}

		return $season_id;
	}

	/**
	 * Load Standing table initial data from another table
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.14.4
	 */
	public function load_standing_data( WP_REST_Request $request ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Access Denied !!!' );
		}

		$params = $request->get_params();

		$standing_from = isset( $params['from'] ) ? absint( $params['from'] ) : '';
		$standing_to   = isset( $params['to'] ) ? absint( $params['to'] ) : '';

		if ( empty( $standing_from ) || empty( $standing_to ) ) {
			wp_send_json_error( 'Invalid Data' );
		}

		$competition_id  = get_post_meta( $standing_to, '_anwpfl_competition', true );
		$group_id        = get_post_meta( $standing_to, '_anwpfl_competition_group', true );
		$competition_obj = anwp_football_leagues()->competition->get_competition( $competition_id );

		$group_clubs = [];

		if ( ! empty( $competition_obj->groups ) && is_array( $competition_obj->groups ) ) {
			foreach ( $competition_obj->groups as $group ) {
				if ( absint( $group->id ) === absint( $group_id ) ) {
					$group_clubs = $group->clubs;
					break;
				}
			}
		}

		if ( empty( $group_clubs ) ) {
			wp_send_json_error( 'Invalid Clubs' );
		}

		$standing_from_data = json_decode( get_post_meta( $standing_from, '_anwpfl_table_main', true ) );
		$output_data        = [];

		foreach ( $group_clubs as $group_club ) {

			$table_row = array_values( wp_list_filter( $standing_from_data, [ 'club_id' => absint( $group_club ) ] ) )[0];

			if ( empty( $table_row ) ) {
				$output_data[ $group_club ] = [
					'played' => 0,
					'won'    => 0,
					'drawn'  => 0,
					'lost'   => 0,
					'gf'     => 0,
					'ga'     => 0,
					'gd'     => 0,
					'points' => 0,
				];
			} else {
				$output_data[ $group_club ] = [
					'played' => $table_row->played,
					'won'    => $table_row->won,
					'drawn'  => $table_row->drawn,
					'lost'   => $table_row->lost,
					'gf'     => $table_row->gf,
					'ga'     => $table_row->ga,
					'gd'     => $table_row->gd,
					'points' => $table_row->points,
				];
			}
		}

		return rest_ensure_response(
			[
				'initialData' => $output_data,
			]
		);
	}

	/**
	 * @param $image_url
	 *
	 * @return mixed
	 */
	public static function get_image_id_by_url( $image_url ) {
		global $wpdb;

		static $image_ids = [];

		if ( ! empty( $image_ids[ $image_url ] ) ) {
			return $image_ids[ $image_url ];
		}

		$image_ids[ $image_url ] = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE guid = %s",
				$image_url
			)
		);

		return $image_ids[ $image_url ];
	}

	/**
	 * Handles sanitization for text field.
	 *
	 * @param mixed $value The unsanitized value from the form.
	 *
	 * @return mixed                  Sanitized value to be stored.
	 */
	public function sanitize_cmb2_fl_text( $value ) {

		if ( current_user_can( 'manage_options' ) || apply_filters( 'anwpfl/cmb2_text_content/allow_unsanitized', false ) ) {
			return $value;
		}

		return wp_kses_post( $value );
	}

	/**
	 * Get metadata grouped by meta value and post id
	 *
	 * @param array $meta_keys
	 *
	 * @return array
	 */
	public function get_metadata_grouped( array $meta_keys ): array {
		global $wpdb;

		$all_meta_data = array_fill_keys( $meta_keys, [] );

		$include_placeholders = array_fill( 0, count( $meta_keys ), '%s' );
		$include_format       = implode( ', ', $include_placeholders );

		$meta_data_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value, meta_key FROM $wpdb->postmeta WHERE meta_value != '' AND meta_key IN ({$include_format})", // phpcs:ignore
				$meta_keys
			),
			ARRAY_A
		);

		foreach ( $meta_data_rows as $meta_data_row ) {
			$all_meta_data[ $meta_data_row['meta_key'] ][ $meta_data_row['post_id'] ] = $meta_data_row['meta_value'];
		}

		return $all_meta_data;
	}

	/**
	 * Parse Url params from WP Json request and return array with params.
	 *
	 * @param string $params_string
	 *
	 * @return array
	 */
	public static function parse_rest_url_params( string $params_string ): array {

		$args = [];

		foreach ( explode( '~', $params_string ) as $arg_string ) {
			$arg_parsed = explode( ':', $arg_string, 2 );

			if ( isset( $arg_parsed[1] ) ) {
				$args[ sanitize_key( $arg_parsed[0] ) ] = sanitize_text_field( urldecode( $arg_parsed[1] ) );
			}
		}

		return $args;
	}
}
