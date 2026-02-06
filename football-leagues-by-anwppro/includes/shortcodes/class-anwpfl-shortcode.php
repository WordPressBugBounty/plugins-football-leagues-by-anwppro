<?php
/**
 * Add Shortcodes Button in TinyMCE.
 *
 * @since   0.5.4
 * @package AnWP_Football_Leagues
 */

/**
 * AnWP Football Leagues :: Shortcode
 */
class AnWPFL_Shortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 */
	public function hooks() {
		add_action( 'admin_init', [ $this, 'mce_button' ] );

		add_action( 'after_wp_tiny_mce', [ $this, 'tinymce_l10n_vars' ] );
		add_action( 'enqueue_block_assets', [ $this, 'add_scripts_to_gutenberg' ] );

		// REST API route for shortcode form.
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 0.17.0
	 */
	public function register_rest_routes() {
		register_rest_route(
			'anwpfl',
			'/shortcode-form/(?P<shortcode>[a-z0-9_-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_get_shortcode_form' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' );
				},
				'args'                => [
					'shortcode' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);
	}

	/**
	 * REST callback: Get shortcode form HTML.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_REST_Response
	 * @since 0.17.0
	 */
	public function rest_get_shortcode_form( WP_REST_Request $request ) {
		$shortcode = $request->get_param( 'shortcode' );

		if ( empty( $shortcode ) ) {
			return new WP_REST_Response(
				[ 'message' => 'Shortcode parameter required' ],
				400
			);
		}

		ob_start();

		/**
		 * Render form with shortcode options (core shortcodes).
		 *
		 * @since 0.12.7
		 */
		do_action( 'anwpfl/shortcode/get_shortcode_form_' . $shortcode );

		/**
		 * Hook: anwpfl/shortcodes/modal_form_shortcode
		 *
		 * Legacy hook that triggers premium shortcode forms via
		 * AnWPFL_Premium_Shortcode::get_modal_form() which fires
		 * 'anwpfl/shortcode-pro/get_shortcode_form_' . $shortcode.
		 *
		 * @since 0.10.8
		 */
		do_action( 'anwpfl/shortcodes/modal_form_shortcode', $shortcode );

		$html_output = ob_get_clean();

		return new WP_REST_Response(
			[
				'success' => true,
				'html'    => $html_output,
			],
			200
		);
	}

	/**
	 * Load TinyMCE localized vars
	 *
	 * @since 0.5.5
	 * @since 0.17.0 Added shortcode options and additional l10n strings for Alpine component
	 */
	public function tinymce_l10n_vars() {

		// Get core shortcode options (sorted alphabetically)
		$core_options = apply_filters( 'anwpfl/shortcode/get_shortcode_options', [] );

		if ( ! empty( $core_options ) && is_array( $core_options ) ) {
			asort( $core_options );
		}

		// Get premium shortcode options (sorted alphabetically)
		$premium_options = apply_filters( 'anwpfl/shortcode-pro/get_shortcode_options', [] );

		if ( ! empty( $premium_options ) && is_array( $premium_options ) ) {
			asort( $premium_options );
		}

		$vars = [
			'football_leagues'          => esc_html__( 'Football Leagues', 'anwp-football-leagues' ),
			'nonce'                     => wp_create_nonce( 'fl_shortcodes_nonce' ),
			'shortcode_options'         => $core_options,
			'shortcode_options_premium' => $premium_options,
			'shortcode'                 => esc_html__( 'Shortcode', 'anwp-football-leagues' ),
			'select'                    => esc_html__( 'select', 'anwp-football-leagues' ),
			'insert'                    => esc_html__( 'Insert Shortcode', 'anwp-football-leagues' ),
			'copy'                      => esc_html__( 'Copy', 'anwp-football-leagues' ),
			'cancel'                    => esc_html__( 'Close', 'anwp-football-leagues' ),
			'copied_to_clipboard'       => esc_html__( 'Copied to Clipboard', 'anwp-football-leagues' ),
		];

		?>
		<script type="text/javascript">
			var _fl_shortcodes_l10n = <?php echo wp_json_encode( $vars ); ?>;
		</script>
		<?php
	}

	/**
	 * Filter Functions with Hooks
	 *
	 * @since 0.5.4
	 */
	public function mce_button() {

		// Check if user have permission
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		// Disable loading UI helper programmatically
		if ( ! apply_filters( 'anwpfl/config/load_shortcodes_ui_helper', true ) ) {
			return;
		}

		// Check if WYSIWYG is enabled
		if ( 'true' === get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', [ $this, 'add_tinymce_plugin' ] );
			add_filter( 'mce_buttons', [ $this, 'register_tinymce_button' ] );
		}
	}

	/**
	 * @param $plugin_array
	 *
	 * @return mixed
	 * @since 0.5.4
	 */
	public function add_tinymce_plugin( $plugin_array ) {
		$plugin_array['football_leagues_button'] = AnWP_Football_Leagues::url( 'admin/js/tinymce-plugin.js?ver=' . anwp_football_leagues()->version );

		return $plugin_array;
	}

	/**
	 * @param $buttons
	 *
	 * @return mixed
	 * @since 0.5.4
	 */
	public function register_tinymce_button( $buttons ) {

		array_push( $buttons, 'football_leagues' );

		return $buttons;
	}

	/**
	 * Added TinyMCE scripts to the Gutenberg Classic editor Block
	 *
	 * @since 0.8.3
	 */
	public function add_scripts_to_gutenberg() {
		global $current_screen;

		$is_gutenberg_old = function_exists( 'is_gutenberg_page' ) && is_gutenberg_page();
		$is_gutenberg_new = $current_screen instanceof WP_Screen && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor();

		if ( is_admin() && ( $is_gutenberg_new || $is_gutenberg_old ) ) {
			$this->tinymce_l10n_vars();
		}
	}

}
