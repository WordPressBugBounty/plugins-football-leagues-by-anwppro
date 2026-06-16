<?php
/**
 * Template Loader
 * AnWP Football Leagues :: Template.
 *
 * @since   0.3.0
 * @package AnWP_Football_Leagues
 */

/**
 * AnWP Football Leagues :: Template class.
 *
 * @since 0.3.0
 */
class AnWPFL_Template extends Gamajo_Template_Loader {

	/**
	 * Parent plugin class.
	 *
	 * @var AnWP_Football_Leagues
	 * @since  0.3.0
	 */
	protected $plugin = null;

	/**
	 * Reference to the root directory path of this plugin.
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	protected $plugin_directory = null;

	/**
	 * Prefix for filter names.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	protected $filter_prefix = 'anwpfl';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	protected $theme_template_directory = 'anwp-football-leagues';


	/**
	 * Constructor.
	 *
	 * @since  0.3.0
	 *
	 * @param  AnWP_Football_Leagues $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {

		$this->plugin           = $plugin;
		$this->plugin_directory = $this->plugin->path;

		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.3.0
	 */
	public function hooks() {

		/**
		 * Template loader
		 *
		 * @since 0.3.0 (2018-01-28)
		 */
		add_filter( 'the_content', [ $this, 'content_loader' ] );

		/**
		 * Override single layout file.
		 * more info - /wp-includes/template.php:62
		 *
		 * @since 0.7.2 (2018-09-17)
		 */
		add_filter( 'single_template', [ $this, 'template_loader' ] );
	}

	/**
	 * Retrieve a template part.
	 *
	 * When WP_ANWPFL_TEMPLATE_DEBUG constant is true (or the
	 * `anwpfl/template/debug_enabled` filter returns true), each template
	 * render is wrapped with Query Monitor timer + query-delta instrumentation
	 * so you can see per-template memory and query cost in QM's Timings + Logs
	 * panels.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Template slug.
	 * @param string $name Optional. Template variation name. Default null.
	 * @param bool   $load Optional. Whether to load template. Default true.
	 * @return string
	 */
	public function get_template_part( $slug, $name = null, $load = true ) {

		$debug = $this->is_template_debug_enabled();
		$label = '';

		if ( $debug ) {
			$label = $this->build_debug_label( $slug, $name );
			$this->start_debug_frame( $label );
		}

		// Execute code for this part.
		do_action( 'get_template_part_' . $slug, $slug, $name );
		do_action( $this->filter_prefix . '_get_template_part_' . $slug, $slug, $name );

		// Get files names of templates, for given slug and name.
		$templates = $this->get_template_file_names( $slug, $name );

		$result = '';

		try {
			$result = $this->locate_template( $templates, $load, false );
		} catch ( Throwable $e ) {
			if ( apply_filters( 'anwpfl/config/log_errors', true ) ) {
				error_log( $e ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}

		if ( $debug ) {
			$this->stop_debug_frame( $label );
		}

		return $result;
	}

	/**
	 * Whether per-template debug instrumentation is active.
	 *
	 * @return bool
	 */
	private function is_template_debug_enabled(): bool {
		static $enabled = null;

		if ( null === $enabled ) {
			$enabled = ( defined( 'WP_ANWPFL_TEMPLATE_DEBUG' ) && WP_ANWPFL_TEMPLATE_DEBUG )
				|| apply_filters( 'anwpfl/template/debug_enabled', false );
		}

		return $enabled;
	}

	/**
	 * Build a stable, human-readable label for a template frame.
	 *
	 * @param string      $slug Template slug.
	 * @param string|null $name Optional variation.
	 *
	 * @return string
	 */
	private function build_debug_label( string $slug, $name ): string {
		return 'FL:' . $slug . ( $name ? '-' . $name : '' );
	}

	/**
	 * Per-request debug frame stack. Parallel arrays keyed by frame label —
	 * push/pop so nested templates each get their own measurement window.
	 *
	 * @var array<string,array<int,array{mem:int,queries:int}>>
	 */
	private $debug_frames = [];

	/**
	 * Begin a debug frame: snapshot memory + query count and fire qm/start.
	 *
	 * Nested calls with the same label push onto a stack; stop_debug_frame()
	 * pops so outer frames see their own deltas.
	 *
	 * @param string $label Frame label.
	 */
	private function start_debug_frame( string $label ): void {
		global $wpdb;

		$this->debug_frames[ $label ][] = [
			'mem'     => memory_get_usage(),
			'queries' => is_array( $wpdb->queries ?? null ) ? count( $wpdb->queries ) : 0,
		];

		do_action( 'qm/start', $label );
	}

	/**
	 * End a debug frame: emit qm/stop + qm/debug with memory / query deltas.
	 *
	 * Optionally suppresses `qm/debug` output for frames that ran zero
	 * queries (qm/start + qm/stop still fire so the Timings panel stays
	 * complete). Enable via:
	 *   - define( 'WP_ANWPFL_TEMPLATE_DEBUG_MIN_QUERIES', 1 )
	 *   - add_filter( 'anwpfl/template/debug_min_queries', fn() => 1 )
	 *
	 * @param string $label Frame label.
	 */
	private function stop_debug_frame( string $label ): void {
		global $wpdb;

		if ( empty( $this->debug_frames[ $label ] ) ) {
			return;
		}

		$frame = array_pop( $this->debug_frames[ $label ] );

		do_action( 'qm/stop', $label );

		$mem_delta  = memory_get_usage() - (int) $frame['mem'];
		$q_now      = is_array( $wpdb->queries ?? null ) ? count( $wpdb->queries ) : 0;
		$q_delta    = $q_now - (int) $frame['queries'];
		$mem_kb     = $mem_delta / 1024;

		if ( $q_delta < $this->debug_min_queries_threshold() ) {
			return;
		}

		do_action(
			'qm/debug',
			sprintf(
				'%s | %+.2f KB | %+d queries',
				$label,
				$mem_kb,
				$q_delta
			)
		);
	}

	/**
	 * Minimum query-delta required before a frame is reported to qm/debug.
	 * Default 0 (log every frame). Set to 1 to hide 0-query templates.
	 *
	 * @return int
	 */
	private function debug_min_queries_threshold(): int {
		static $threshold = null;

		if ( null === $threshold ) {
			$threshold = defined( 'WP_ANWPFL_TEMPLATE_DEBUG_MIN_QUERIES' )
				? (int) WP_ANWPFL_TEMPLATE_DEBUG_MIN_QUERIES
				: (int) apply_filters( 'anwpfl/template/debug_min_queries', 0 );
		}

		return $threshold;
	}

	/**
	 * Load CPT content
	 *
	 * @param string $content Content of the current post.
	 *
	 * @global       $post    WP_Post
	 *
	 * @since 0.3.0
	 * @since 0.4.4 Added extra dash for layout. Changes in competition layout hierarchy.
	 * @since 0.7.3 Changed Match template (one for every status).
	 * @return string
	 */
	public function content_loader( $content ) {

		global $post;

		if ( empty( $post->post_type) ) {
			return $content;
		}

		$template_loading_check = [
			(bool) $post,
			in_array( $post->post_type, $this->plugin->get_post_types(), true ),
			is_singular(),
			is_main_query(),
			! post_password_required(),
		];

		if ( 'no' !== anwp_fl()->customizer->get_value( 'general', 'template_loading_check' ) ) {
			$template_loading_check[] = in_the_loop();
		}

		$template_loading_check = apply_filters( 'anwpfl/template/loading_check', $template_loading_check, $content );

		if ( array_product( $template_loading_check ) ) {
			if ( apply_filters( 'anwpfl/template/load_default_template', true, $post->post_type, $post ) ) {
				// Prepare layout (tmpl_layout is competition-only; not in light warm cols, use get_row()).
				$layout = '';

				if ( 'anwp_competition' === $post->post_type ) {
					$layout = anwp_fl()->competition->get_row( $post->ID )['tmpl_layout'] ?? '';
				}

				$layout = empty( $layout ) ? '' : ( '-' . sanitize_key( $layout ) );

				$this->get_template_part( 'content-' . str_replace( 'anwp_', '', $post->post_type ), $layout );
			} else {
				do_action( 'anwpfl/template/load_alt_template', $post->post_type, $post );
			}

			// Disable default content
			return '';
		}

		return $content;
	}

	/**
	 * Load shortcodes
	 *
	 * @param string $shortcode
	 * @param mixed $atts
	 *
	 * @since 0.3.0
	 * @return string
	 */
	public function shortcode_loader( $shortcode, $atts ) {

		// Start an output buffer.
		ob_start();

		// Convert options to object
		$atts = (object) $atts;

		// Check layout (add extra dash)
		$layout = empty( $atts->layout ) ? '' : ( '-' . sanitize_key( $atts->layout ) );

		$this->set_template_data( $atts )->get_template_part( 'shortcode-' . sanitize_key( $shortcode ), $layout );

		// Return the output buffer.
		return ob_get_clean();
	}

	/**
	 * Load widget content
	 *
	 * @param string $widget
	 * @param array $atts
	 *
	 * @since 0.3.0
	 * @since 0.4.4 - Added extra dash for layout
	 * @return string
	 */
	public function widget_loader( $widget, $atts ) {

		$atts = wp_parse_args(
			$atts,
			[
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'title'         => '',
				'after_title'   => '',
			]
		);

		// Start an output buffer.
		ob_start();

		// Start widget markup.
		echo $atts['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Maybe display widget title.
		echo ( $atts['title'] ) ? $atts['before_title'] . esc_html( $atts['title'] ) . $atts['after_title'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Check widget layout (add extra dash for layout)
		$layout = empty( $atts['layout'] ) ? '' : ( '-' . sanitize_key( $atts['layout'] ) );

		$this->set_template_data( $atts )->get_template_part( 'widget-' . sanitize_key( $widget ), $layout );

		// End the widget markup.
		echo $atts['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Return the output buffer.
		return ob_get_clean();
	}

	/**
	 * Get template file to load.
	 *
	 * @param string $template The path of the template to include.
	 *
	 * @since 0.3.0
	 * @return string
	 */
	public function template_loader( $template ) {

		$current_theme = get_template();
		$new_template  = '';

		$available_post_types = anwp_fl()->get_post_types();

		if ( 'yes' === anwp_fl()->customizer->get_value( 'general', 'load_alternative_page_layout' ) ) {
			if ( in_array( get_post_type(), $available_post_types, true ) ) {
				$new_template = anwp_fl()->path . 'theme-support/twentysixteen/single.php';
			}
		}

		if ( 'twentysixteen' === $current_theme ) {
			if ( in_array( get_post_type(), $available_post_types, true ) ) {
				$new_template = anwp_fl()->path . 'theme-support/twentysixteen/single.php';
			}
		}

		return ( $new_template && file_exists( $new_template ) ) ? $new_template : $template;
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.3.0
	 *
	 * @param  string $field Field to get.
	 *
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __get( $field ) {

		if ( property_exists( $this, $field ) ) {
			return $this->$field;
		}

		throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
	}

}
