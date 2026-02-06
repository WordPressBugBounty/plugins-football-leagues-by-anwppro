<?php
/**
 * AnWP Football Leagues :: Cache.
 *
 * @since   0.13.3
 * @package AnWP_Football_Leagues
 *
 */

/**
 * AnWP Football Leagues :: Cache.
 */
class AnWPFL_Cache {

	/**
	 * Parent plugin class.
	 */
	protected ?AnWP_Football_Leagues $plugin = null;

	/**
	 * Is cache active.
	 */
	public $active = true;

	/**
	 * Allow Simple Link Building
	 */
	public bool $simple_link_building = false;

	/**
	 * Cache version
	 * v1 - based on transients and options table
	 * v2 - based on external object cache
	 */
	public string $version = '';

	/**
	 * Cache group map
	 * Used in v1 (transients)
	 */
	protected array $groups_map = [
		'anwp_match'         => 'anwpfl_cached_keys__game',
		'anwp_player'        => 'anwpfl_cached_keys__player',
		'anwp_transfer'      => 'anwpfl_cached_keys__transfer',
		'anwp_fl_suspension' => 'anwpfl_cached_keys__suspension',
	];

	/**
	 * Constructor.
	 *
	 * @param AnWP_Football_Leagues $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		/**
		 * Disable/Enable cache
		 */
		$this->active = apply_filters( 'anwpfl/cache/is_active', 'no' !== AnWPFL_Options::get_value( 'cache_active', $this->active ) );

		$this->simple_link_building = in_array( get_option( 'permalink_structure' ), [ '/%postname%/', '/%category%/%postname%/' ], true ) && 'yes' === AnWPFL_Options::get_value( 'simple_permalink_slug_building' );

		if ( ! wp_next_scheduled( 'anwp_fl_cache_maybe_cleanup' ) && $this->active ) {
			wp_schedule_event( time() + 86400, 'weekly', 'anwp_fl_cache_maybe_cleanup' );
		}

		if ( $this->active ) {
			$this->version = wp_using_ext_object_cache() ? 'v2' : 'v1';
		}

		// Run Hooks
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since   0.13.3
	 */
	public function hooks() {

		// Run cache invalidating on changing plugin instances
		add_action( 'before_delete_post', [ $this, 'on_modify_post' ], 10, 2 );
		add_action( 'edit_post', [ $this, 'on_modify_post' ], 10, 2 );
		add_action( 'wp_insert_post', [ $this, 'on_modify_post' ], 10, 2 );
		add_action( 'anwp_fl_edit_post', [ $this, 'on_modify_post' ], 10, 2 );

		add_action( 'delete_anwp_league', [ $this, 'on_modify_league' ] );
		add_action( 'edit_term_anwp_league', [ $this, 'on_modify_league' ] );
		add_action( 'created_anwp_league', [ $this, 'on_modify_league' ] );

		// Cron action
		add_action( 'anwp_fl_cache_maybe_cleanup', [ $this, 'cleanup_generated_keys' ] );
		add_action( 'anwp_fl_cache_maybe_reflush', [ $this, 'flush_cache_by_post_type' ] );

		add_action( 'permalink_structure_changed', [ $this, 'permalink_structure_changed' ] );
	}

	/**
	 * Get cached value
	 *
	 * @param string $cache_key
	 * @param string $dependent_group
	 * @param array|string  $default_value
	 *
	 * @since   0.13.3
	 * @return array|mixed
	 */
	public function get( string $cache_key, string $dependent_group = '', $default_value = [] ) {
		if ( ! $this->active || in_array( $cache_key, apply_filters( 'anwpfl/cache/excluded_keys', [] ), true ) ) {
			return false;
		}

		if ( 'v1' === $this->version ) {
			if ( $dependent_group && ! in_array( $cache_key, $this->get_saved_keys( $dependent_group ), true ) ) {
				return false;
			}

			$response = get_transient( $cache_key );

			return false !== $response ? $response : $default_value;
		} elseif ( 'v2' === $this->version ) {
			$cached_value = wp_cache_get( $cache_key, 'anwp-fl-' . $dependent_group );

			return false !== $cached_value ? $cached_value : $default_value;
		}

		return $default_value;
	}

	/**
	 * Set/update cached value
	 *
	 * @param string       $cache_key
	 * @param $value
	 * @param string|array $dependent_group
	 */
	public function set( string $cache_key, $value, $dependent_group = '' ) {
		if ( ! $this->active || in_array( $cache_key, apply_filters( 'anwpfl/cache/excluded_keys', [] ), true ) ) {
			return;
		}

		$expiration = $this->get_key_expiration( $cache_key );

		if ( 'v1' === $this->version ) {
			if ( $expiration ) {
				set_transient( $cache_key, $value, $expiration );

				// Add generated keys to the options
				if ( $dependent_group ) {
					if ( is_array( $dependent_group ) ) {
						foreach ( $dependent_group as $group ) {
							$this->maybe_add_cached_key( $cache_key, $group );
						}
					} else {
						$this->maybe_add_cached_key( $cache_key, $dependent_group );
					}
				}
			}
		} elseif ( 'v2' === $this->version ) {
			wp_cache_set( $cache_key, $value, 'anwp-fl-' . $dependent_group, $expiration );
		}
	}

	/**
	 * Get key expiration.
	 *
	 * @param string $cache_key
	 *
	 * @return string
	 */
	protected function get_key_expiration( string $cache_key ): string {

		$expiration_map = [
			'FL-REFEREES-LIST-SIMPLE'            => WEEK_IN_SECONDS,
			'FL-REFEREES-LIST'                   => WEEK_IN_SECONDS,
			'FL-STADIUMS-LIST'                   => WEEK_IN_SECONDS,
			'FL-CLUBS-LIST'                      => WEEK_IN_SECONDS,
			'FL-STANDINGS-LIST'                  => MONTH_IN_SECONDS,
			'FL-COMPETITIONS-LIST'               => WEEK_IN_SECONDS,
			'FL-COMPETITIONS-DATA'               => WEEK_IN_SECONDS,
			'FL-LEAGUE-OPTIONS'                  => WEEK_IN_SECONDS,
			'FL-PLAYER_tmpl_get_latest_matches'  => WEEK_IN_SECONDS,
			'FL-PLAYER_tmpl_get_players_by_type' => WEEK_IN_SECONDS,
			'FL-SHORTCODE_players'               => WEEK_IN_SECONDS,
			'FL-SHORTCODE_cards'                 => WEEK_IN_SECONDS,
			'FL-PLAYER_get_birthdays'            => DAY_IN_SECONDS,
			'FL-PRO-REFEREES-NAME-LIST'          => WEEK_IN_SECONDS,
			'FL-PRO-REFEREES-NAMES'              => WEEK_IN_SECONDS,
			'FL-STAFF-PHOTO-MAP'                 => WEEK_IN_SECONDS,
			'FL-MATCHWEEK-OPTIONS'               => WEEK_IN_SECONDS,
		];

		$cache_group = explode( '__', $cache_key )[0];

		/**
		 * Add/Modify expiration keys (group) map
		 *
		 * @param array  $expiration_map
		 * @param string $cache_key
		 */
		$expiration_map = apply_filters( 'anwpfl/cache/expiration_map', $expiration_map, $cache_group, $cache_key );

		return $expiration_map[ $cache_group ] ?? HOUR_IN_SECONDS;
	}

	/**
	 * Delete cache
	 *
	 * @param        $cache_key
	 * @param string $cached_group
	 */
	public function delete( $cache_key, string $cached_group = '' ) {
		if ( 'v1' === $this->version ) {
			delete_transient( $cache_key );
		} elseif ( 'v2' === $this->version ) {
			wp_cache_delete( $cache_key, 'anwp-fl-' . $cached_group );
		}
	}

	/**
	 * Run on post delete/update to invalidate some cache.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post_obj
	 */
	public function on_modify_post( int $post_id, WP_Post $post_obj ) {

		if ( ! $this->active ) {
			return;
		}

		if ( isset( $post_obj->post_type ) ) {
			$this->flush_cache_by_post_type( $post_obj->post_type );

			// On modify Plugin Instances
			if ( 'v1' === $this->version && ! empty( $this->groups_map[ $post_obj->post_type ] ) ) {
				$this->schedule_cache_reflush( $post_obj->post_type );
			}
		}
	}

	/**
	 * Run on term delete/update to invalidate some cache.
	 */
	public function on_modify_league() {
		if ( ! $this->active ) {
			return;
		}

		$this->delete( 'FL-LEAGUE-OPTIONS' );
	}

	/**
	 * Flush cache by post type
	 *
	 * @param string $post_type
	 */
	public function flush_cache_by_post_type( string $post_type ) {
		if ( ! $this->active || empty( $post_type ) ) {
			return;
		}

		/*
		|--------------------------------------------------------------------
		| Remove Static Keys
		|--------------------------------------------------------------------
		*/

		// On modify PLAYER
		if ( 'anwp_player' === $post_type ) {
			// player cache live here
		}

		// On modify REFEREE
		if ( 'anwp_referee' === $post_type ) {
			$this->delete( 'FL-REFEREES-LIST-SIMPLE' );
			$this->delete( 'FL-REFEREES-LIST' );
			$this->delete( 'FL-PRO-REFEREES-NAME-LIST' );
			$this->delete( 'FL-PRO-REFEREES-NAMES' );
		}

		// On modify STAFF
		if ( 'anwp_staff' === $post_type ) {
			$this->delete( 'FL-STAFF-PHOTO-MAP' );
		}

		// On modify STADIUM
		if ( 'anwp_stadium' === $post_type ) {
			$this->delete( 'FL-STADIUMS-LIST' );
		}

		// On modify CLUB
		if ( 'anwp_club' === $post_type ) {
			$this->delete( 'FL-CLUBS-LIST' );
		}

		// On modify COMPETITION
		if ( 'anwp_competition' === $post_type ) {
			$this->delete( 'FL-COMPETITIONS-LIST' );
			$this->delete( 'FL-COMPETITIONS-DATA' );
		}

		// On modify STANDING
		if ( 'anwp_standing' === $post_type ) {
			$this->delete( 'FL-STANDINGS-LIST' );
		}

		// On modify GAME
		if ( 'anwp_match' === $post_type ) {
			$this->delete( 'FL-MATCHWEEK-OPTIONS' );
		}

		/*
		|--------------------------------------------------------------------
		| Remove Dynamic Keys
		|--------------------------------------------------------------------
		*/

		// On modify Plugin Instances
		if ( ! empty( $this->groups_map[ $post_type ] ) ) {
			$this->remove_cached_group_keys( $post_type );
		}
	}

	/**
	 * Run on post update to invalidate some cache.
	 *
	 * @param string $dependent_group
	 *
	 * @since 0.13.3
	 */
	private function remove_cached_group_keys( string $dependent_group ) {
		global $wpdb;

		if ( empty( $dependent_group ) || empty( $this->groups_map[ $dependent_group ] ) ) {
			return;
		}

		if ( 'v1' === $this->version ) {
			$saved_keys = $this->get_saved_keys( $dependent_group, true );
			$wpdb->update( $wpdb->options, [ 'option_value' => '' ], [ 'option_name' => $this->groups_map[ $dependent_group ] ] );

			foreach ( $saved_keys as $saved_key ) {
				if ( empty( $saved_key ) ) {
					continue;
				}

				$this->delete( $saved_key );
			}
		} elseif ( 'v2' === $this->version && wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'anwp-fl-' . $dependent_group );
		} elseif ( 'v2' === $this->version ) {
			wp_cache_flush();
		}
	}

	/**
	 * Removes all cache items.
	 *
	 * @return bool
	 * @since  0.13.3
	 */
	public function flush_all_cache(): bool {

		/*
		|--------------------------------------------------------------------
		| Remove Static Keys
		|--------------------------------------------------------------------
		*/
		$this->delete( 'FL-REFEREES-LIST-SIMPLE' );
		$this->delete( 'FL-REFEREES-LIST' );
		$this->delete( 'FL-STADIUMS-LIST' );
		$this->delete( 'FL-CLUBS-LIST' );
		$this->delete( 'FL-COMPETITIONS-LIST' );
		$this->delete( 'FL-COMPETITIONS-DATA' );
		$this->delete( 'FL-STANDINGS-LIST' );
		$this->delete( 'FL-PRO-REFEREES-NAME-LIST' );
		$this->delete( 'FL-PRO-CLUB-HISTORY-ALL' );

		$this->delete( 'FL-LEAGUE-OPTIONS' );

		if ( 'v2' === $this->version && wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'anwp-fl-' );
		}

		/*
		|--------------------------------------------------------------------
		| Remove Generated Keys
		|--------------------------------------------------------------------
		*/
		foreach ( array_keys( $this->groups_map ) as $cached_group ) {
			$this->remove_cached_group_keys( $cached_group );
		}

		return true;
	}

	/**
	 * Removes cache items on permalinks structure changed.
	 *
	 * @since  0.13.3
	 */
	public function permalink_structure_changed() {
		$this->delete( 'FL-CLUBS-LIST' );
	}

	/*
	|--------------------------------------------------------------------
	| V1 only methods
	|--------------------------------------------------------------------
	*/

	/**
	 * Removes all cache v1
	 *
	 * @return bool
	 */
	public function flush_all_cache_v1(): bool {

		/*
		|--------------------------------------------------------------------
		| Remove Static Keys
		|--------------------------------------------------------------------
		*/
		delete_transient( 'FL-REFEREES-LIST-SIMPLE' );
		delete_transient( 'FL-REFEREES-LIST' );
		delete_transient( 'FL-STADIUMS-LIST' );
		delete_transient( 'FL-CLUBS-LIST' );
		delete_transient( 'FL-COMPETITIONS-LIST' );
		delete_transient( 'FL-COMPETITIONS-DATA' );
		delete_transient( 'FL-STANDINGS-LIST' );
		delete_transient( 'FL-PRO-REFEREES-NAME-LIST' );

		/*
		|--------------------------------------------------------------------
		| Remove Generated Keys
		|--------------------------------------------------------------------
		*/
		foreach ( array_keys( $this->groups_map ) as $cached_group ) {
			if ( empty( $cached_group ) || empty( $this->groups_map[ $cached_group ] ) ) {
				continue;
			}

			$saved_keys = $this->get_saved_keys( $cached_group, true );
			delete_option( $this->groups_map[ $cached_group ] );

			foreach ( $saved_keys as $saved_key ) {
				if ( empty( $saved_key ) ) {
					continue;
				}

				$this->delete( $saved_key );
			}
		}

		return true;
	}

	/**
	 * Get saved cached keys
	 *
	 * @param string $group
	 * @param bool   $force_get
	 *
	 * @return mixed|string
	 */
	private function get_saved_keys( string $group = 'anwp_match', bool $force_get = false ) {

		global $wpdb;
		static $keys = [];

		if ( empty( $this->groups_map[ $group ] ) ) {
			return [];
		}

		if ( ! isset( $keys[ $group ] ) || $force_get ) {

			$keys[ $group ] = [];

			$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $this->groups_map[ $group ] ) );

			if ( is_object( $row ) && ! empty( $row->option_value ) ) {
				foreach ( explode( '++', $row->option_value ) as $cached_key ) {
					if ( $cached_key ) {
						$keys[ $group ][] = trim( $cached_key, '+' );
					}
				}
			} elseif ( ! is_object( $row ) ) {
				update_option( $this->groups_map[ $group ], '', false );
			}
		}

		return $keys[ $group ];
	}

	/**
	 * Maybe add cached key to the saved option
	 * V1 - only
	 *
	 * @param string $cache_key
	 * @param string $dependent_group
	 */
	private function maybe_add_cached_key( string $cache_key, string $dependent_group ) {
		global $wpdb;

		if ( ! in_array( $cache_key, $this->get_saved_keys( $dependent_group ), true ) && ! empty( $this->groups_map[ $dependent_group ] ) ) {
			$wpdb->query(
				$wpdb->prepare(
					"
			        UPDATE $wpdb->options
			        SET option_value = CONCAT(option_value, %s)
			        WHERE option_name = %s
			        ",
					'+' . $cache_key . '+',
					$this->groups_map[ $dependent_group ]
				)
			);
		}
	}

	/**
	 * Clean Up generated expired keys (CRON)
	 * V1 - only
	 *
	 * @return bool
	 */
	public function cleanup_generated_keys(): bool {
		if ( 'v1' !== $this->version ) {
			return true;
		}

		foreach ( array_keys( $this->groups_map ) as $cached_group ) {
			foreach ( $this->get_saved_keys( $cached_group ) as $cached_key ) {
				if ( false === get_transient( $cached_key ) ) {
					$this->remove_cached_group_key( $cached_key, $cached_group );
				}
			}
		}

		return true;
	}

	/**
	 * Remove single cached key
	 * V1 - only
	 *
	 * @param string $cached_key
	 * @param string $dependent_group
	 *
	 * @since 0.13.3
	 */
	private function remove_cached_group_key( string $cached_key, string $dependent_group ) {
		if ( 'v1' !== $this->version ) {
			return;
		}

		global $wpdb;

		if ( empty( $cached_key ) || empty( $dependent_group ) || empty( $this->groups_map[ $dependent_group ] ) ) {
			return;
		}

		$wpdb->query(
			$wpdb->prepare(
				"
			        UPDATE $wpdb->options
			        SET option_value = REPLACE(option_value, %s, '')
			        WHERE option_name = %s
			        ",
				'+' . $cached_key . '+',
				$this->groups_map[ $dependent_group ]
			)
		);
	}

	/**
	 * Schedule Re-Flush cache to fix trailing queries
	 *
	 * @param string $post_type
	 */
	private function schedule_cache_reflush( string $post_type ) {
		if ( empty( $post_type ) ) {
			return;
		}

		$args = [ $post_type ];

		if ( wp_next_scheduled( 'anwp_fl_cache_maybe_reflush', $args ) ) {
			wp_clear_scheduled_hook( 'anwp_fl_cache_maybe_reflush', $args );
		}

		wp_schedule_single_event( time() + 180, 'anwp_fl_cache_maybe_reflush', $args );
	}
}
