<?php
/**
 * Shortcode Preview REST API handler.
 *
 * Provides REST endpoints for live shortcode preview in the builder.
 *
 * @since   0.17.0
 * @package AnWP_Football_Leagues
 */

/**
 * AnWP Football Leagues :: Shortcode Preview.
 *
 * @since 0.17.0
 */
class AnWPFL_Shortcode_Preview {

	/**
	 * Constructor.
	 *
	 * @since 0.17.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 *
	 * @since 0.17.0
	 */
	public function register_routes(): void {
		// POST endpoint for JSON response.
		register_rest_route(
			'anwpfl',
			'/shortcode-preview',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'render_preview' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'shortcode' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'atts'      => [
						'required' => false,
						'type'     => 'object',
						'default'  => [],
					],
				],
			]
		);

		// GET endpoint for iframe HTML page.
		register_rest_route(
			'anwpfl',
			'/shortcode-preview/iframe',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'render_iframe' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'shortcode' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'atts'      => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Check user permissions.
	 *
	 * @since 0.17.0
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' );
	}

	/**
	 * Render preview JSON response.
	 *
	 * @since 0.17.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function render_preview( WP_REST_Request $request ): WP_REST_Response {
		$shortcode = $request->get_param( 'shortcode' );
		$atts      = $request->get_param( 'atts' );

		$shortcode_string = $this->build_shortcode_string( $shortcode, $atts );
		$html             = do_shortcode( $shortcode_string );

		return new WP_REST_Response(
			[
				'success'   => true,
				'html'      => $html,
				'shortcode' => $shortcode_string,
			],
			200
		);
	}

	/**
	 * Render iframe HTML page.
	 *
	 * Outputs a complete HTML page with frontend styles for iframe preview.
	 *
	 * @since 0.17.0
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function render_iframe( WP_REST_Request $request ): void {
		$shortcode = $request->get_param( 'shortcode' );
		$atts      = json_decode( $request->get_param( 'atts' ) ?? '{}', true );

		if ( ! is_array( $atts ) ) {
			$atts = [];
		}

		$shortcode_string = $this->build_shortcode_string( $shortcode, $atts );

		// Send HTML headers to prevent REST API from treating as JSON.
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

		$this->output_iframe_html( $shortcode_string );
		exit;
	}

	/**
	 * Build shortcode string from name and attributes.
	 *
	 * @since 0.17.0
	 *
	 * @param string $name Shortcode name (without anwpfl- prefix).
	 * @param array  $atts Shortcode attributes.
	 *
	 * @return string
	 */
	protected function build_shortcode_string( string $name, array $atts ): string {
		/**
		 * Filter to resolve multi-form shortcode keys to actual tags.
		 *
		 * For shortcodes with multiple builder forms (e.g., charts-goals-15, charts-team-defaults)
		 * that use a single shortcode tag (e.g., charts), this filter maps the form key to the tag.
		 *
		 * @since 0.17.0
		 *
		 * @param string $name Shortcode key from builder (e.g., 'charts-goals-15').
		 * @param array  $atts Shortcode attributes.
		 *
		 * @return string Resolved shortcode key (e.g., 'charts').
		 */
		$shortcode_key = apply_filters( 'anwpfl/shortcode_preview/resolve_shortcode_key', $name, $atts );
		$shortcode_tag = 'anwpfl-' . sanitize_key( $shortcode_key );

		// Verify shortcode exists (security hardening).
		if ( ! shortcode_exists( $shortcode_tag ) ) {
			return '';
		}

		$attr_string = '';

		foreach ( $atts as $key => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}
			$attr_string .= sprintf( ' %s="%s"', sanitize_key( $key ), esc_attr( $value ) );
		}

		return sprintf( '[%s%s]', $shortcode_tag, $attr_string );
	}

	/**
	 * Output complete HTML page for iframe preview.
	 *
	 * @since 0.17.0
	 *
	 * @param string $shortcode_string Shortcode string to render.
	 */
	protected function output_iframe_html( string $shortcode_string ): void {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'Shortcode Preview', 'anwp-football-leagues' ); ?></title>
			<?php
			// Enqueue frontend styles.
			wp_enqueue_style( 'anwpfl_styles', AnWP_Football_Leagues::url( 'public/css/styles.min.css' ), [], AnWP_Football_Leagues::VERSION );

			// Load legacy Bootstrap if enabled.
			if ( 'no' !== anwp_fl()->customizer->get_value( 'advanced_css', 'load_legacy_bootstrap' ) ) {
				wp_enqueue_style( 'anwpfl_legacy_bootstrap', AnWP_Football_Leagues::url( 'public/css/styles-legacy-bootstrap.min.css' ), [ 'anwpfl_styles' ], AnWP_Football_Leagues::VERSION );
			}

			// Load RTL styles if needed.
			if ( is_rtl() ) {
				wp_enqueue_style( 'anwpfl_styles_rtl', AnWP_Football_Leagues::url( 'public/css/styles-rtl.min.css' ), [ 'anwpfl_styles' ], AnWP_Football_Leagues::VERSION );
			}

			// Load Customizer CSS.
			$customizer_css = anwp_fl()->customizer->get_customizer_css();
			if ( $customizer_css ) {
				wp_add_inline_style( 'anwpfl_styles', $customizer_css );
			}

			/**
			 * Hook: anwpfl/shortcode_preview/enqueue_styles
			 *
			 * Allows premium to enqueue its styles.
			 *
			 * @since 0.17.0
			 */
			do_action( 'anwpfl/shortcode_preview/enqueue_styles' );

			wp_print_styles();
			?>
			<style>
				body {
					margin: 0;
					padding: 16px;
					background: #fff;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				}
				.anwpfl-preview-empty {
					text-align: center;
					padding: 40px;
					color: #666;
				}
				.anwpfl-preview-loading {
					text-align: center;
					padding: 40px;
					color: #999;
				}
			</style>
		</head>
		<body>
			<?php
			/**
			 * Hook: anwpfl/shortcode_preview/register_scripts
			 *
			 * Allows plugins to register scripts before shortcode renders.
			 * Scripts enqueued by shortcode templates will only work if registered here.
			 *
			 * @since 0.17.0
			 */
			do_action( 'anwpfl/shortcode_preview/register_scripts' );
			?>
			<div class="anwpfl-preview-container">
				<?php
				$output = do_shortcode( $shortcode_string );

				if ( empty( trim( $output ) ) ) {
					echo '<div class="anwpfl-preview-empty">';
					esc_html_e( 'No data to display for current settings.', 'anwp-football-leagues' );
					echo '</div>';
				} else {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output.
					echo $output;
				}
				?>
			</div>
			<?php
			// Include SVG sprite.
			anwp_fl()->assets->include_public_svg_icons();

			// Print scripts.
			wp_enqueue_script( 'anwp-fl-public', AnWP_Football_Leagues::url( 'public/js/anwp-fl-public.min.js' ), [], AnWP_Football_Leagues::VERSION, true );
			wp_print_scripts();

			/**
			 * Hook: anwpfl/shortcode_preview/after_scripts
			 *
			 * Allows plugins to output inline scripts after main scripts.
			 * Used for triggering initialization hooks (e.g., Swiper).
			 *
			 * @since 0.17.0
			 */
			do_action( 'anwpfl/shortcode_preview/after_scripts' );
			?>
		</body>
		</html>
		<?php
	}
}

new AnWPFL_Shortcode_Preview();
