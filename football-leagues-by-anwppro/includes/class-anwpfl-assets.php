<?php
/**
 * AnWP Football Leagues :: Assets.
 *
 * @since   0.15.3
 * @package AnWP_Football_Leagues
 *
 */

/**
 * AnWP Football Leagues :: Assets.
 */
class AnWPFL_Assets {

	/**
	 * Parent plugin class.
	 *
	 * @var AnWP_Football_Leagues
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @param AnWP_Football_Leagues $plugin Main plugin object.
	 */
	public function __construct( AnWP_Football_Leagues $plugin ) {

		$this->plugin = $plugin;

		// Run Hooks
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 */
	public function hooks() {

		/**
		 * Enqueue public scripts & styles
		 *
		 * @since 0.3.0 (2018-01-29)
		 */
		add_action( 'wp_enqueue_scripts', [ $this, 'public_enqueue_scripts' ] );

		/**
		 * Enqueue admin scripts
		 *
		 * @since 0.2.0 (2017-10-28)
		 */
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		/**
		 * Add svg icons to the footer
		 *
		 * @since 0.2.0 (2017-10-28)
		 */
		add_action( 'admin_footer', [ $this, 'include_admin_svg_icons' ], 99 );

		/**
		 * Add svg icons to the public side
		 *
		 * @since 0.3.0 (2018-02-08)
		 */
		add_action( 'wp_footer', [ $this, 'include_public_svg_icons' ], 99 );
	}

	/**
	 * Load admin scripts and styles
	 *
	 * @since 0.3.0 (2018-01-29)
	 */
	public function public_enqueue_scripts() {

		if ( is_rtl() ) {
			wp_enqueue_style( 'anwpfl_styles_rtl', AnWP_Football_Leagues::url( 'public/css/styles-rtl.css' ), [], AnWP_Football_Leagues::VERSION );
		} else {
			wp_enqueue_style( 'anwpfl_styles', AnWP_Football_Leagues::url( 'public/css/styles.min.css' ), [], AnWP_Football_Leagues::VERSION );
		}

		/*
		|--------------------------------------------------------------------------
		| Flags
		|--------------------------------------------------------------------------
		*/
		if ( 'legacy' === anwp_football_leagues()->customizer->get_value( 'general', 'flags' ) ) {
			wp_enqueue_style( 'anwpfl_flags', AnWP_Football_Leagues::url( 'vendor/world-flags-sprite/stylesheets/flags32.css' ), [], AnWP_Football_Leagues::VERSION );
			wp_enqueue_style( 'anwpfl_flags_16', AnWP_Football_Leagues::url( 'vendor/world-flags-sprite/stylesheets/flags16.css' ), [], AnWP_Football_Leagues::VERSION );
		}

		/*
		|--------------------------------------------------------------------------
		| Main JS
		|--------------------------------------------------------------------------
		*/
		wp_enqueue_script( 'anwp-fl-public', AnWP_Football_Leagues::url( 'public/js/anwp-fl-public.min.js' ), [], AnWP_Football_Leagues::VERSION, true );

		wp_add_inline_script(
			'anwp-fl-public',
			'window.AnWPFL = ' . wp_json_encode(
				[
					'native_yt' => in_array( AnWPFL_Options::get_value( 'preferred_video_player' ), [ 'youtube', 'mixed' ], true ) ? 'yes' : '',
					'rest_root' => esc_url_raw( rest_url() ),
				]
			),
			'before'
		);

		/*
		|--------------------------------------------------------------------------
		| Justified Gallery
		|--------------------------------------------------------------------------
		*/
		wp_register_script( 'anwp-fl-justified-gallery', AnWP_Football_Leagues::url( 'vendor/flickr-justified-gallery/fjGallery.min.js' ), [], AnWP_Football_Leagues::VERSION, true );
		wp_register_script( 'anwp-fl-justified-gallery-modal', AnWP_Football_Leagues::url( 'vendor/baguette-box/baguetteBox.min.js' ), [], AnWP_Football_Leagues::VERSION, true );

		/*
		|--------------------------------------------------------------------------
		| Micromodal
		|
		| @license  MIT
		| @link     https://github.com/ghosh/Micromodal
		|--------------------------------------------------------------------------
		*/
		wp_enqueue_script( 'micromodal', AnWP_Football_Leagues::url( 'vendor/micromodal/micromodal.min.js' ), [], '0.4.10', false );

		/*
		|--------------------------------------------------------------------------
		| Plyr
		| @licence - MIT
		| @url - https://plyr.io/
		|--------------------------------------------------------------------------
		*/
		if ( apply_filters( 'anwpfl/config/load_plyr', true ) && 'youtube' !== AnWPFL_Options::get_value( 'preferred_video_player' ) ) {
			wp_register_script( 'plyr', AnWP_Football_Leagues::url( 'vendor/plyr/plyr.polyfilled.min.js' ), [], '3.7.8', false );
			wp_register_style( 'plyr', AnWP_Football_Leagues::url( 'vendor/plyr/plyr.css' ), [], '3.7.8' );
		}

		/*
		|--------------------------------------------------------------------------
		| Load Customizer Classes
		|--------------------------------------------------------------------------
		*/
		$customizer_css = anwp_football_leagues()->customizer->get_customizer_css();

		if ( $customizer_css ) {
			if ( is_rtl() ) {
				wp_add_inline_style( 'anwpfl_styles_rtl', $customizer_css );
			} else {
				wp_add_inline_style( 'anwpfl_styles', $customizer_css );
			}
		}

		/*
		|--------------------------------------------------------------------------
		| Additional Inline CSS
		|--------------------------------------------------------------------------
		*/
		$inline_css = [
			'[fl-x-cloak] { display: none !important; }', // AlpineJS support
			'.anwpfl-not-ready {opacity: 0; transition: opacity 0.5s ease; visibility: hidden;}', // Hide some content before DOM loaded
			'.anwpfl-ready .anwpfl-not-ready {opacity: 1; visibility: visible;}',
			'body:not(.anwpfl-ready) .anwpfl-not-ready-0 {display: none !important;}',
		];

		wp_add_inline_style( 'anwpfl_styles', implode( ' ', $inline_css ) );
	}

	/**
	 * Load admin scripts and styles
	 *
	 * @since 0.2.0 (2017-10-28)
	 */
	public function admin_enqueue_scripts() {

		// Load global styles
		if ( is_rtl() ) {
			wp_enqueue_style( 'anwpfl_styles_global_rtl', AnWP_Football_Leagues::url( 'admin/css/global-rtl.css' ), [], AnWP_Football_Leagues::VERSION );
			wp_enqueue_style( 'anwpfl_styles_global_rtl_extra', AnWP_Football_Leagues::url( 'admin/css/global-rtl-extra.css' ), [], AnWP_Football_Leagues::VERSION );
		} else {
			wp_enqueue_style( 'anwpfl_styles_global', AnWP_Football_Leagues::url( 'admin/css/global.css' ), [], AnWP_Football_Leagues::VERSION );
		}

		/*
		|--------------------------------------------------------------------------
		| Modaal
		|
		| @license  MIT
		| @link     https://github.com/humaan/Modaal
		|--------------------------------------------------------------------------
		*/
		wp_enqueue_script( 'modaal', AnWP_Football_Leagues::url( 'vendor/modaal/modaal.min.js' ), [ 'jquery', 'underscore' ], AnWP_Football_Leagues::VERSION, false );

		/*
		|--------------------------------------------------------------------------
		| Global JS
		|--------------------------------------------------------------------------
		*/
		wp_enqueue_script( 'anwp-fl-js-global', AnWP_Football_Leagues::url( 'admin/js/anwp-fl-global.min.js' ), [], AnWP_Football_Leagues::VERSION, false );

		wp_localize_script(
			'anwp-fl-js-global',
			'anwpflGlobals',
			[
				'ajaxNonce'     => wp_create_nonce( 'ajax_anwpfl_nonce' ),
				'selectorHtml'  => anwp_fl()->include_selector_modaal(),
				'rest_root'     => esc_url_raw( rest_url() ),
				'rest_nonce'    => wp_create_nonce( 'wp_rest' ),
				'countries'     => [],
				'clubs'         => [],
				'seasons'       => [],
				'leagues'       => [],
				'context_l10n'  => [
					'club'        => esc_html__( 'Club', 'anwp-football-leagues' ),
					'competition' => esc_html__( 'Competition', 'anwp-football-leagues' ),
					'main_stage'  => esc_html__( 'Main Stage', 'anwp-football-leagues' ),
					'match'       => esc_html__( 'Match', 'anwp-football-leagues' ),
					'player'      => esc_html__( 'Player', 'anwp-football-leagues' ),
					'referee'     => esc_html__( 'Referee', 'anwp-football-leagues' ),
					'staff'       => esc_html__( 'Staff', 'anwp-football-leagues' ),
					'stage'       => esc_html__( 'Stage', 'anwp-football-leagues' ),
				],
				'optionsLoaded' => false,
			]
		);

		/*
		|--------------------------------------------------------------------------
		| Select2 - 4.0.13
		|
		| @license  MIT
		| @link     https://select2.github.io
		|--------------------------------------------------------------------------
		*/
		wp_enqueue_script( 'anwp-select2', AnWP_Football_Leagues::url( 'vendor/select2/select2.full.min.js' ), [ 'jquery' ], '4.0.13', false );
		wp_enqueue_style( 'anwp-select2', AnWP_Football_Leagues::url( 'vendor/select2/select2.min.css' ), [], '4.0.13' );

		// Load styles and scripts (limit to plugin pages)
		$current_screen = get_current_screen();

		$page_prefix          = sanitize_title( _x( 'Football Leagues', 'admin menu title', 'anwp-football-leagues' ) );
		$page_settings_prefix = sanitize_title( _x( 'Settings & Tools', 'admin menu title', 'anwp-football-leagues' ) );

		$plugin_pages = [

			// Top level
			'toplevel_page_anwp-football-leagues',
			'toplevel_page_anwp-settings-tools',

			// CPTs
			'anwp_competition',
			'anwp_standing',
			'anwp_club',
			'anwp_staff',
			'anwp_referee',
			'anwp_player',
			'anwp_stadium',
			'anwp_match',

			// Options page
			'settings-tools_page_anwp_football_leagues_options',
			$page_settings_prefix . '_page_anwp_football_leagues_options',

			// toolbox
			'football-leagues_page_anwpfl-toolbox',
			$page_prefix . '_page_anwpfl-toolbox',

			// Text page
			'settings-tools_page_anwp_fl_text',
			$page_settings_prefix . '_page_anwp_fl_text',

			// Countries page
			'settings-tools_page_anwp_fl_text_countries',
			$page_settings_prefix . '_page_anwp_fl_text_countries',

			// Tools page
			'settings-tools_page_anwp-settings-tools',
			$page_settings_prefix . '_page_anwp-settings-tools',

			// Premium page
			'football-leagues_page_anwpfl_premium',
			$page_prefix . '_page_anwpfl_premium',

			// Support page
			'football-leagues_page_support',
			$page_prefix . '_page_support',

			// Shortcodes page
			'football-leagues_page_anwpfl-shortcodes',
			$page_prefix . '_page_anwpfl-shortcodes',

			// Plugin Health
			'football-leagues_page_anwpfl-plugin-health',
			$page_prefix . '_page_anwpfl-plugin-health',

			// Plugin Health
			'football-leagues_page_anwpfl-plugin-customize',
			$page_prefix . '_page_anwpfl-plugin-customize',

			// Match Admin List
			'edit-anwp_match',

			// Competition Admin List
			'edit-anwp_competition',
		];

		/**
		 * Filters plugin pages.
		 *
		 * @param array $plugin_pages List of plugin pages to load styles.
		 *
		 * @since 0.5.5
		 */
		$plugin_pages = array_unique( apply_filters( 'anwpfl/admin/plugin_pages', $plugin_pages ) );

		// Load Common files
		if ( in_array( $current_screen->id, $plugin_pages, true ) ) {

			wp_enqueue_media();

			/*
			|--------------------------------------------------------------------------
			| CSS Styles
			|--------------------------------------------------------------------------
			*/
			if ( is_rtl() ) {
				wp_enqueue_style( 'anwpfl_styles_rtl', AnWP_Football_Leagues::url( 'admin/css/styles-rtl.css' ), [ 'wp-color-picker' ], AnWP_Football_Leagues::VERSION );
			} else {
				wp_enqueue_style( 'anwpfl_styles', AnWP_Football_Leagues::url( 'admin/css/styles.css' ), [ 'wp-color-picker' ], AnWP_Football_Leagues::VERSION );
			}

			/*
			|--------------------------------------------------------------------------
			| World Flags Sprite
			|--------------------------------------------------------------------------
			*/
			wp_enqueue_style( 'anwpfl_flags', AnWP_Football_Leagues::url( 'vendor/world-flags-sprite/stylesheets/flags32.css' ), [], AnWP_Football_Leagues::VERSION );
			wp_enqueue_style( 'anwpfl_flags_16', AnWP_Football_Leagues::url( 'vendor/world-flags-sprite/stylesheets/flags16.css' ), [], AnWP_Football_Leagues::VERSION );

			/*
			|--------------------------------------------------------------------------
			| FlatPickrStyles
			|--------------------------------------------------------------------------
			*/
			wp_enqueue_style( 'anwpfl_flatpickr', AnWP_Football_Leagues::url( 'admin/css/flatpickr.min.css' ), [], AnWP_Football_Leagues::VERSION );
			wp_enqueue_style( 'anwpfl_flatpickr_theme', AnWP_Football_Leagues::url( 'admin/css/flatpickr_airbnb.css' ), [], AnWP_Football_Leagues::VERSION );

			/*
			|--------------------------------------------------------------------------
			| Toastr
			|
			| @license  MIT
			| @link     https://github.com/CodeSeven/toastr
			|--------------------------------------------------------------------------
			*/
			wp_enqueue_script( 'toastr', AnWP_Football_Leagues::url( 'vendor/toastr/toastr.min.js' ), [], '2.1.4', false );

			/*
			|--------------------------------------------------------------------------
			| notyf
			|
			| @license  MIT
			| @link     https://github.com/caroso1222/notyf
			|--------------------------------------------------------------------------
			*/
			wp_enqueue_script( 'notyf', AnWP_Football_Leagues::url( 'vendor/notyf/notyf.min.js' ), [], '3.10.0', false );

			/*
			|--------------------------------------------------------------------------
			| Tippy.js
			| * (c) 2017-2019 atomiks
			| * MIT
			|--------------------------------------------------------------------------
			*/
			wp_enqueue_script( 'tippy', AnWP_Football_Leagues::url( 'vendor/tippy/tippy-bundle.umd.min.js' ), [ 'popperjs' ], '6.1.1', true );

			/*
			|--------------------------------------------------------------------------
			| Popper.js (UMD)
			| * (c) Federico Zivolo
			| * MIT
			|--------------------------------------------------------------------------
			*/
			wp_enqueue_script( 'popperjs', AnWP_Football_Leagues::url( 'vendor/popperjs/popper.min.js' ), [], '2.2.3', true );

			/*
			|--------------------------------------------------------------------------
			| jQuery Autocomplete
			|
			| @license  MIT
			| @link     https://github.com/devbridge/jQuery-Autocomplete
			|--------------------------------------------------------------------------
			*/
			wp_enqueue_script( 'anwp-jquery-autocomplete', AnWP_Football_Leagues::url( 'vendor/jquery-autocomplete/jquery.autocomplete.min.js' ), [], '1.4.11', false );

			/*
			|--------------------------------------------------------------------------
			| Main admin JS
			|--------------------------------------------------------------------------
			*/
			wp_enqueue_script( 'anwpfl_admin', AnWP_Football_Leagues::url( 'admin/js/anwpfl-admin.min.js' ), [ 'jquery', 'underscore', 'jquery-ui-sortable' ], AnWP_Football_Leagues::VERSION, true );

			wp_localize_script(
				'anwpfl_admin',
				'anwp',
				[
					'rest_root'    => esc_url_raw( rest_url() ),
					'rest_nonce'   => wp_create_nonce( 'wp_rest' ),
					'spinner_url'  => admin_url( 'images/spinner.gif' ),
					'admin_url'    => admin_url(),
					'seasons_list' => anwp_football_leagues()->season->get_seasons_list(),
					'positions'    => anwp_football_leagues()->data->get_positions(),
					'activeSeason' => anwp_football_leagues()->get_active_season(),
				]
			);

			/*
			|--------------------------------------------------------------------------
			| Admin App
			|--------------------------------------------------------------------------
			*/
			$admin_app_pages = [
				'anwp_competition',
				'anwp_standing',
				'anwp_club',
				'anwp_player',
				'anwp_match',
				'football-leagues_page_anwpfl-plugin-health',
				$page_prefix . '_page_anwpfl-plugin-health',
				'football-leagues_page_anwpfl-toolbox',
				$page_prefix . '_page_anwpfl-toolbox',
				'toplevel_page_anwp-settings-tools',
			];

			if ( in_array( $current_screen->id, $admin_app_pages, true ) ) {
				wp_enqueue_script( 'fl-admin-core-app', AnWP_Football_Leagues::url( 'admin/js/admin-core.min.js' ), [ 'wp-color-picker', 'jquery' ], AnWP_Football_Leagues::VERSION, true );
			}
		}

		// Load Google Maps only for stadium page
		if ( 'anwp_stadium' === $current_screen->id ) {

			if ( AnWPFL_Options::get_value( 'google_maps_api' ) ) {
				$google_maps_api_key = '?key=' . AnWPFL_Options::get_value( 'google_maps_api' );

				wp_enqueue_script( 'google-maps-api-3', '//maps.googleapis.com/maps/api/js' . $google_maps_api_key . '&libraries=places', [], 3, false );
			}
		}

		/*
		|--------------------------------------------------------------------------
		| Jspreadsheet CE (jExcel)
		| * Author: Paul Hodel <paul.hodel@gmail.com>
		| * Website: https://github.com/jspreadsheet/ce
		| * MIT License
		|--------------------------------------------------------------------------
		*/
		wp_register_style( 'jexcel-v4', AnWP_Football_Leagues::url( 'vendor/jexcel/jexcel.min.css' ), [], '4.13.4' );
		wp_register_script( 'jexcel-v4', AnWP_Football_Leagues::url( 'vendor/jexcel/jexcel.min.js' ), [ 'jexcel-suites-v4' ], '4.13.4', true );
		wp_register_style( 'jexcel-suites-v4', AnWP_Football_Leagues::url( 'vendor/jexcel/jsuites.css' ), [], '4.17.5' );
		wp_register_script( 'jexcel-suites-v4', AnWP_Football_Leagues::url( 'vendor/jexcel/jsuites.js' ), [], '4.17.5', true );

		if ( false !== mb_strpos( $current_screen->id, '_page_anwp-settings-tools' ) ) {
			wp_localize_script(
				'anwpfl_admin',
				'anwpImportOptions',
				anwp_football_leagues()->data->get_import_options()
			);
		}

		if ( 'edit-anwp_match' === $current_screen->id ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
		}
	}

	/**
	 * Add SVG definitions to the admin footer.
	 *
	 * @since 0.2.0 (2018-01-03)
	 */
	public function include_admin_svg_icons() {

		// Define SVG sprite file.
		$svg_icons = AnWP_Football_Leagues::dir( 'admin/img/svg-icons.svg' );

		// If it exists, include it.
		if ( file_exists( $svg_icons ) ) {
			require_once $svg_icons;
		}
	}

	/**
	 * Add SVG definitions to the public footer.
	 *
	 * @since 0.3.0 (2018-02-08)
	 */
	public function include_public_svg_icons() {

		// Define SVG sprite file.
		$svg_icons = AnWP_Football_Leagues::dir( 'public/img/svg-icons.svg' );

		// If it exists, include it.
		if ( file_exists( $svg_icons ) ) {
			require_once $svg_icons;
		}
	}
}
