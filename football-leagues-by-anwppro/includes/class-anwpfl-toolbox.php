<?php
/**
 * Toolbox Class
 * AnWP Football Leagues :: Toolbox.
 *
 * @since   0.16.14
 * @package AnWP_Football_Leagues
 */

class AnWPFL_Toolbox {

	/**
	 * Parent plugin class.
	 *
	 * @var    AnWP_Football_Leagues
	 */
	protected $plugin = null;

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
	 */
	public function hooks() {
		add_action( 'rest_api_init', [ $this, 'add_rest_routes' ] );
	}

	/**
	 * Register REST routes.
	 */
	public function add_rest_routes() {

		register_rest_route(
			'anwpfl/toolkit',
			'/(?P<toolkit_method>[a-z_]+)/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'process_toolkit_get_requests' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'anwpfl/toolkit',
			'/(?P<toolkit_method>[a-z_]+)/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'process_toolkit_post_requests' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/**
	 * Handle Toolkit Get Requests
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function process_toolkit_get_requests( WP_REST_Request $request ) {

		@set_time_limit( 300 ); // phpcs:ignore
		do_action( 'qm/cease' );

		// Get Request params
		$params         = $request->get_params();
		$toolkit_method = sanitize_key( $params['toolkit_method'] );

		if ( empty( $toolkit_method ) ) {
			return new WP_Error( 'rest_invalid', 'Incorrect Toolkit Method', [ 'status' => 400 ] );
		}

		if ( ! method_exists( $this, $toolkit_method ) ) {
			return new WP_Error( 'rest_invalid', 'Method not allowed', [ 'status' => 400 ] );
		}

		return rest_ensure_response( $this->{$toolkit_method}( $params ) );
	}

	/**
	 * Handle Toolkit POST Requests
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function process_toolkit_post_requests( WP_REST_Request $request ) {

		@set_time_limit( 300 ); // phpcs:ignore
		do_action( 'qm/cease' );

		// Get Request params
		$params         = $request->get_params();
		$toolkit_method = sanitize_key( $params['toolkit_method'] );

		if ( empty( $toolkit_method ) ) {
			return new WP_Error( 'rest_invalid', 'Incorrect Toolkit Method', [ 'status' => 400 ] );
		}

		if ( ! method_exists( $this, $toolkit_method ) ) {
			return new WP_Error( 'rest_invalid', 'Method not allowed', [ 'status' => 400 ] );
		}

		return rest_ensure_response( $this->{$toolkit_method}( $params ) );
	}

	/**
	 * @return array[]
	 */
	public function get_all_player_ids(): array {

		global $wpdb;

		return [ 'toolOptions' => [ 'player_ids' => $wpdb->get_col( "SELECT player_id FROM $wpdb->anwpfl_player_data" ) ?: [] ] ];
	}

	/**
	 * @param $params
	 *
	 * @return array|WP_Error
	 */
	public function update_player_current_team( $params ) {

		$player_id = absint( $params['playerId'] );

		if ( empty( $player_id ) ) {
			return new WP_Error( 'anwp_rest_error', 'Invalid Player ID', [ 'status' => 400 ] );
		}

		$player_team_id = absint( anwp_fl()->player->get_player_data( $player_id )['team_id'] ?? 0 );
		$last_team_id   = absint( anwp_fl()->player->get_player_last_team( $player_id ) );
		$update_result  = false;

		if ( $last_team_id && $last_team_id !== $player_team_id ) {
			$update_result = anwp_fl()->player->update( $player_id, [ 'team_id' => absint( $last_team_id ) ] );
		}

		return [
			'result' => true,
			'saved'  => $update_result,
		];
	}
}
