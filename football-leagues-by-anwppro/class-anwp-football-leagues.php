<?php
/**
 * AnWP Football Leagues :: Main Class
 *
 * @since   0.1.0
 * @package AnWP_Football_Leagues
 */

/**
 * Autoload files with classes when needed.
 *
 * @since  0.1.0
 *
 * @param string $class_name Name of the class being requested.
 */
function anwp_football_leagues_autoload_classes( $class_name ) {

	// If our class doesn't have our prefix, don't load it.
	if ( 0 !== strpos( $class_name, 'AnWPFL_' ) ) {
		return;
	}

	// Set up our filename.
	$filename = strtolower( str_replace( '_', '-', substr( $class_name, strlen( 'AnWPFL_' ) ) ) );

	// Shortcode classes are in the shortcodes subdirectory.
	if ( 0 === strpos( $filename, 'shortcode' ) ) {
		AnWP_Football_Leagues::include_file( 'includes/shortcodes/class-anwpfl-' . $filename );
	} else {
		AnWP_Football_Leagues::include_file( 'includes/class-anwpfl-' . $filename );
	}
}

spl_autoload_register( 'anwp_football_leagues_autoload_classes' );

/**
 * Main initiation class.
 *
 * @since  0.1.0
 * @property-read AnWPFL_Assets            $assets
 * @property-read AnWPFL_Blocks            $blocks
 * @property-read AnWPFL_Club              $club
 * @property-read AnWPFL_Competition       $competition
 * @property-read AnWPFL_Competition_Admin $competition_admin
 * @property-read AnWPFL_Data              $data
 * @property-read AnWPFL_Data_Port         $data_port
 * @property-read AnWPFL_Health            $health
 * @property-read AnWPFL_Cache             $cache
 * @property-read AnWPFL_Customizer        $customizer
 * @property-read AnWPFL_Helper            $helper
 * @property-read AnWPFL_League            $league
 * @property-read AnWPFL_Match             $match
 * @property-read AnWPFL_Match_Admin       $match_admin
 * @property-read AnWPFL_Options           $options
 * @property-read AnWPFL_Player            $player
 * @property-read AnWPFL_Season            $season
 * @property-read AnWPFL_Staff             $staff
 * @property-read AnWPFL_Referee           $referee
 * @property-read AnWPFL_Standing          $standing
 * @property-read AnWPFL_Stadium           $stadium
 * @property-read AnWPFL_Template          $template
 * @property-read AnWPFL_Template_Status   $template_status
 * @property-read AnWPFL_Text              $text
 * @property-read AnWPFL_Text_Countries    $text_countries
 * @property-read AnWPFL_Toolbox           $toolbox
 * @property-read AnWPFL_Upgrade           $upgrade
 * @property-read string                   $path     Path of plugin directory
 *
 */
final class AnWP_Football_Leagues { //phpcs:ignore

	/**
	 * Current version.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	const VERSION = '0.18.0';

	/**
	 * Current DB structure version.
	 *
	 * @var    int
	 * @since  0.3.0
	 */
	const DB_VERSION = 49;

	/**
	 * Menu Icon.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	const SVG_BALL = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCI+PHBhdGggZmlsbD0iI2E3YWFhZCIgZD0iTTAsMCBIMjAgVjIwIEgwIFogTTEsMSBWMTkgSDE5IFYxIFoiLz48cGF0aCBmaWxsPSIjYTdhYWFkIiBkPSJNMy4wMDQyNyAxNS4wMDAxVjQuODE4MjRIOS41MjdWNi4zNjQ0SDQuODQ4NzNWOS4xMjg2MUg5LjA3OTU2VjEwLjY3NDhINC44NDg3M1YxNS4wMDAxSDMuMDA0MjdaIi8+PHBhdGggZmlsbD0iI2E3YWFhZCIgZD0iTTExLjIwNzQgMTUuMDAwMVY0LjgxODI0SDEzLjA1MTlWMTMuNDUzOUgxNy41MzYyVjE1LjAwMDFIMTEuMjA3NFoiLz48L3N2Zz4=';
	const SVG_VS   = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCI+PHBhdGggZmlsbD0iI2E3YWFhZCIgZD0iTTYuNjY3IDFzMi44NTcgMi4xMjUgNi42NjYgMi4xMjVDMTMuMzMzIDE0LjgxMiA2LjY2NyAxOCA2LjY2NyAxOFMwIDE0LjgxMiAwIDMuMTI1QzMuODEgMy4xMjUgNi42NjcgMSA2LjY2NyAxeiIvPjxwYXRoIGZpbGw9IiNhN2FhYWQiIGZpbGwtb3BhY2l0eT0iLjQiIGQ9Ik0xMy4zMzMgMVMxNi4xOSAzLjEyNSAyMCAzLjEyNUMyMCAxNC44MTIgMTMuMzMzIDE4IDEzLjMzMyAxOFM2LjY2NyAxNC44MTIgNi42NjcgMy4xMjVDMTAuNDc2IDMuMTI1IDEzLjMzMyAxIDEzLjMzMyAxeiIvPjwvc3ZnPg==';
	const SVG_CUP  = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE0LjU2NDIgMTguMTI1SDUuMTAzN1YyMEgxNC41NjQyVjE4LjEyNVoiIGZpbGw9IiNBN0FBQUQiLz4KPHBhdGggZD0iTTE1LjcyNjEgMC42NDM5MDZDMTUuNzI2MSAwLjI0NjcxOSAxNS43MjYxIDAgMTUuNzI2MSAwSDkuNzUxMDNIMy43NzU5MUMzLjc3NTkxIDAgMy43NzU5MSAwLjI0NjcxOSAzLjc3NTkxIDAuNjQzOTA2SDAuMDE3OTcySDAuMDEwMjY5N0gwVjMuNzg3ODFDMCA0LjkwODQ0IDAuNDM5MDI5IDUuOTQyOTcgMS4yMzYyNiA2LjcwMTAxQzIuMDQ3NDMgNy40NzIzNCAzLjE3NzE0IDcuOTM2ODMgNC42MDI0IDguMDg4NTFDNS45NjY2OSAxMC4wMzcyIDguNTQxMTMgMTEuMTA5MyA4LjU0MTEzIDEyLjAxMTRDOC41NDExMyAxMy4yMzg3IDcuMTk2NjUgMTUuMzI1IDcuMTk2NjUgMTUuMzI1VjE2LjY2MjlWMTYuNjY1NlYxNi42NzUxSDEyLjMwNTRWMTUuMzI1QzEyLjMwNTQgMTUuMzI1IDEwLjk2MTEgMTMuMjM4NyAxMC45NjExIDEyLjAxMTRDMTAuOTYxMSAxMS4xMTU0IDEzLjQ5OTYgMTAuMDUxOSAxNC44NzA5IDguMTI4NjdDMTYuNTQ2NSA4LjA0MzI0IDE3Ljg1NTIgNy41NjUgMTguNzYzNyA2LjcwMTA1QzE5LjU2MSA1Ljk0MzAxIDIwIDQuOTA4NDcgMjAgMy43ODc4NVYwLjY0MzkwNkgxNS43MjYxWk0xLjU3Njc4IDMuNzg3ODVWMi4wODMyOEgzLjc3NTkxQzMuNzc1OTEgMy4yNjUyMyAzLjc3NTkxIDQuNjg3MTkgMy43NzU5MSA1LjYwNjA5QzMuNzc1OTEgNS44Njg4MyAzLjgwNjE2IDYuMTE4MDQgMy44NTI1NCA2LjM1OTU3QzMuODU5NDggNi40MDQ3MiAzLjg2MzU4IDYuNDQ5NzIgMy44NzE2MyA2LjQ5NTA4QzEuODM2OSA1Ljk1MjYxIDEuNTc2NzggNC40OTM3MSAxLjU3Njc4IDMuNzg3ODVaTTE4LjQyMzIgMy43ODc4NUMxOC40MjMyIDQuNDA3MDcgMTguMjIzMSA1LjYwNTQ3IDE2LjgwMDkgNi4yNTY3MkMxNi40NzA4IDYuNDA2ODcgMTYuMDc0NyA2LjUyNzExIDE1LjYwMDQgNi42MDM1NUMxNS42ODEgNi4yODk1MyAxNS43MjYxIDUuOTU3ODEgMTUuNzI2MSA1LjYwNjA5QzE1LjcyNjEgNC42ODcxOSAxNS43MjYxIDMuMjY1MjMgMTUuNzI2MSAyLjA4MzI4SDE4LjQyMzJMMTguNDIzMiAzLjc4Nzg1WiIgZmlsbD0iI0E3QUFBRCIvPgo8L3N2Zz4K';

	/**
	 * URL of plugin directory.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $basename = '';

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    AnWP_Football_Leagues
	 * @since  0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * @var AnWPFL_Options
	 */
	protected $options;

	/**
	 * @var AnWPFL_Upgrade
	 */
	protected $upgrade;

	/**
	 * @var AnWPFL_League
	 */
	protected $league;

	/**
	 * @var AnWPFL_Season
	 */
	protected $season;

	/**
	 * @var AnWPFL_Match
	 */
	protected $match;

	/**
	 * @var AnWPFL_Match_Admin
	 */
	protected $match_admin;

	/**
	 * @var AnWPFL_Competition_Admin
	 */
	protected $competition_admin;

	/**
	 * @var AnWPFL_Data_Port
	 */
	protected $data_port;

	/**
	 * @var AnWPFL_Competition
	 */
	protected $competition;

	/**
	 * @var AnWPFL_Blocks
	 */
	protected $blocks;

	/**
	 * @var AnWPFL_Assets
	 */
	protected $assets;

	/**
	 * @var AnWPFL_Club
	 */
	protected $club;

	/**
	 * @var AnWPFL_Stadium
	 */
	protected $stadium;

	/**
	 * @var AnWPFL_Health
	 */
	protected $health;

	/**
	 * @var AnWPFL_Customizer
	 */
	protected $customizer;

	/**
	 * @var AnWPFL_Helper
	 */
	protected $helper;

	/**
	 * @var AnWPFL_Player
	 */
	protected $player;

	/**
	 * @var AnWPFL_Staff
	 */
	protected $staff;

	/**
	 * @var AnWPFL_Referee
	 */
	protected $referee;

	/**
	 * @var AnWPFL_Cache
	 */
	protected $cache;

	/**
	 * @var AnWPFL_Standing
	 */
	protected $standing;

	/**
	 * @var AnWPFL_Text
	 */
	protected $text;

	/**
	 * @var AnWPFL_Toolbox
	 */
	protected $toolbox;

	/**
	 * @var AnWPFL_Text_Countries
	 */
	protected $text_countries;

	/**
	 * @var AnWPFL_Data
	 */
	protected $data;

	/**
	 * @var AnWPFL_Template
	 */
	protected $template;

	/**
	 * @var AnWPFL_Template_Status
	 */
	protected $template_status;

	/**
	 * @var AnWPFL_Sitemap
	 */
	protected $sitemap;

	/**
	 * Plugin Post Types
	 *
	 * @since 0.5.5
	 * @var array
	 */
	protected $plugin_post_types = [];

	/**
	 * @var string
	 */
	public $upload_dir;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   0.1.0
	 * @return  AnWP_Football_Leagues A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since  0.1.0
	 */
	protected function __construct() {

		// initial vars
		$this->basename   = plugin_basename( self::dir( 'anwp-football-leagues.php' ) );
		$this->url        = plugin_dir_url( __FILE__ );
		$this->path       = plugin_dir_path( __FILE__ );
		$this->upload_dir = wp_upload_dir()['baseurl'];

		$this->define_tables();

		$this->plugin_post_types = apply_filters(
			'anwpfl/config/plugin_post_types',
			[
				'anwp_match',
				'anwp_competition',
				'anwp_club',
				'anwp_stadium',
				'anwp_standing',
				'anwp_player',
				'anwp_staff',
				'anwp_referee',
			]
		);
	}

	/**
	 * Register custom tables within $wpdb object.
	 */
	private function define_tables() {
		global $wpdb;

		$tables = [
			'anwpfl_matches',
			'anwpfl_players',
			'anwpfl_player_data',
			'anwpfl_missing_players',
			'anwpfl_players_manual_stats',
			'anwpfl_lineups',
			'anwpfl_clubs',
			'anwpfl_competitions',
			'anwpfl_standings',
		];

		foreach ( $tables as $table ) {
			$wpdb->$table   = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.1.0
	 */
	public function plugin_classes() {

		// Options
		$this->options = new AnWPFL_Options( $this );

		$this->assets = new AnWPFL_Assets( $this );
		$this->cache  = new AnWPFL_Cache( $this );

		// Taxonomies
		$this->league = new AnWPFL_League( $this );
		$this->season = new AnWPFL_Season( $this );

		// CPT
		$this->match       = new AnWPFL_Match( $this );
		$this->competition = new AnWPFL_Competition( $this );
		$this->club        = new AnWPFL_Club( $this );
		$this->stadium     = new AnWPFL_Stadium( $this );
		$this->player      = new AnWPFL_Player( $this );
		$this->staff       = new AnWPFL_Staff( $this );
		$this->referee     = new AnWPFL_Referee( $this );
		$this->standing    = new AnWPFL_Standing( $this );

		// Others
		$this->match_admin       = new AnWPFL_Match_Admin( $this );
		$this->competition_admin = new AnWPFL_Competition_Admin( $this );
		$this->data              = new AnWPFL_Data( $this );
		$this->data_port         = new AnWPFL_Data_Port( $this );
		$this->helper            = new AnWPFL_Helper( $this );
		$this->health            = new AnWPFL_Health( $this );
		$this->template          = new AnWPFL_Template( $this );
		$this->template_status   = new AnWPFL_Template_Status( $this );
		$this->text              = new AnWPFL_Text( $this );
		$this->text_countries    = new AnWPFL_Text_Countries( $this );
		$this->customizer        = new AnWPFL_Customizer( $this );
		$this->blocks            = new AnWPFL_Blocks( $this );
		$this->toolbox           = new AnWPFL_Toolbox( $this );

		$this->upgrade = new AnWPFL_Upgrade( $this );
		$this->sitemap = new AnWPFL_Sitemap( $this );

		// Shortcodes
		$this->load_shortcodes();
	}

	/**
	 * Get shortcode definitions array.
	 *
	 * Single source of truth for shortcode file paths, class names, and labels.
	 * Used by both load_shortcodes() and get_shortcode_options().
	 *
	 * @since 0.17.0
	 * @return array
	 */
	protected function get_shortcode_definitions(): array {
		return [
			'standing'          => [
				'file'  => 'class-anwpfl-shortcode-standing.php',
				'class' => 'AnWPFL_Shortcode_Standing',
				'label' => __( 'Standing Table', 'anwp-football-leagues' ),
			],
			'club'              => [
				'file'  => 'class-anwpfl-shortcode-club.php',
				'class' => 'AnWPFL_Shortcode_Club',
				'label' => __( 'Club', 'anwp-football-leagues' ),
			],
			'clubs'             => [
				'file'  => 'class-anwpfl-shortcode-clubs.php',
				'class' => 'AnWPFL_Shortcode_Clubs',
				'label' => __( 'Clubs', 'anwp-football-leagues' ),
			],
			'matches'           => [
				'file'  => 'class-anwpfl-shortcode-matches.php',
				'class' => 'AnWPFL_Shortcode_Matches',
				'label' => __( 'Matches', 'anwp-football-leagues' ),
			],
			'match'             => [
				'file'  => 'class-anwpfl-shortcode-match.php',
				'class' => 'AnWPFL_Shortcode_Match',
				'label' => __( 'Match', 'anwp-football-leagues' ),
			],
			'squad'             => [
				'file'  => 'class-anwpfl-shortcode-squad.php',
				'class' => 'AnWPFL_Shortcode_Squad',
				'label' => __( 'Squad', 'anwp-football-leagues' ),
			],
			'competition-header' => [
				'file'  => 'class-anwpfl-shortcode-competition-header.php',
				'class' => 'AnWPFL_Shortcode_Competition_Header',
				'label' => __( 'Competition Header', 'anwp-football-leagues' ),
			],
			'competition-list'  => [
				'file'  => 'class-anwpfl-shortcode-competition-list.php',
				'class' => 'AnWPFL_Shortcode_Competition_List',
				'label' => __( 'Competition List', 'anwp-football-leagues' ),
			],
			'players'           => [
				'file'  => 'class-anwpfl-shortcode-players.php',
				'class' => 'AnWPFL_Shortcode_Players',
				'label' => __( 'Players', 'anwp-football-leagues' ),
			],
			'cards'             => [
				'file'  => 'class-anwpfl-shortcode-cards.php',
				'class' => 'AnWPFL_Shortcode_Cards',
				'label' => __( 'Cards', 'anwp-football-leagues' ),
			],
			'player'            => [
				'file'  => 'class-anwpfl-shortcode-player.php',
				'class' => 'AnWPFL_Shortcode_Player',
				'label' => __( 'Player Card', 'anwp-football-leagues' ),
			],
			'staff'             => [
				'file'  => 'class-anwpfl-shortcode-staff.php',
				'class' => 'AnWPFL_Shortcode_Staff',
				'label' => __( 'Staff', 'anwp-football-leagues' ),
			],
			'referee'           => [
				'file'  => 'class-anwpfl-shortcode-referee.php',
				'class' => 'AnWPFL_Shortcode_Referee',
				'label' => __( 'Referee', 'anwp-football-leagues' ),
			],
			'player-data'       => [
				'file'  => 'class-anwpfl-shortcode-player-data.php',
				'class' => 'AnWPFL_Shortcode_Player_Data',
				'label' => __( 'Player Data', 'anwp-football-leagues' ),
			],
			'match-next'        => [
				'file'  => 'class-anwpfl-shortcode-match-next.php',
				'class' => 'AnWPFL_Shortcode_Match_Next',
				'label' => __( 'Next Match', 'anwp-football-leagues' ),
			],
			'match-last'        => [
				'file'  => 'class-anwpfl-shortcode-match-last.php',
				'class' => 'AnWPFL_Shortcode_Match_Last',
				'label' => __( 'Last Match', 'anwp-football-leagues' ),
			],
		];
	}

	/**
	 * Get shortcode options for UI (dropdown labels).
	 *
	 * Direct method call - no filter timing issues.
	 *
	 * @since 0.17.0
	 * @return array Key => label pairs for shortcode dropdown.
	 */
	public function get_shortcode_options(): array {
		$options = [];

		foreach ( $this->get_shortcode_definitions() as $key => $def ) {
			if ( ! empty( $def['label'] ) ) {
				$options[ $key ] = $def['label'];
			}
		}

		asort( $options );

		return $options;
	}

	/**
	 * Load all core shortcodes.
	 *
	 * @since 0.17.0
	 */
	protected function load_shortcodes(): void {
		$shortcodes_dir = self::dir( 'includes/shortcodes/' );

		// Load base classes first (required for inheritance).
		require_once $shortcodes_dir . 'class-anwpfl-shortcode-base.php';
		require_once $shortcodes_dir . 'class-anwpfl-shortcode-field-renderer.php';
		require_once $shortcodes_dir . 'class-anwpfl-shortcode.php';

		// Instantiate main shortcode controller (TinyMCE, REST API).
		new AnWPFL_Shortcode();

		// Load shortcode preview REST endpoint.
		require_once $shortcodes_dir . 'class-anwpfl-shortcode-preview.php';
		new AnWPFL_Shortcode_Preview();

		// Load and instantiate shortcodes from definitions.
		foreach ( $this->get_shortcode_definitions() as $def ) {
			$file_path = $shortcodes_dir . $def['file'];

			if ( file_exists( $file_path ) ) {
				require_once $file_path;

				if ( class_exists( $def['class'], false ) ) {
					new $def['class']();
				}
			}
		}
	}

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {

		add_action( 'init', [ $this, 'init' ], 0 );

		/**
		 * Initialize widgets
		 *
		 * @since 0.4.3 (2018-02-18)
		 */
		add_action( 'widgets_init', [ $this, 'register_widgets' ], 0 );

		/**
		 * Register menu pages.
		 *
		 * @since  0.1.0 (2017-10-17)
		 * @since  0.17.0 Added register_entity_menus for reorganized menu structure
		 */
		add_action( 'admin_menu', [ $this, 'register_menus' ], 5 );
		add_action( 'admin_menu', [ $this, 'register_alt_menus' ], 5 );
		add_action( 'admin_menu', [ $this, 'register_entity_menus' ], 5 );
		add_filter( 'parent_file', [ $this, 'fix_parent_highlight' ] );
		add_filter( 'submenu_file', [ $this, 'fix_submenu_highlight' ] );

		add_action( 'wp_footer', [ $this, 'render_modal_wrappers' ], 99 );

		/**
		 * Maybe flush rewrite rules.
		 *
		 * @since 0.3.0 (2018-02-05)
		 */
		add_action( 'init', [ $this, 'flush_rewrite_rules_maybe' ], 20 );

		/**
		 * Register custom status for secondary stage (in multistage competition)
		 *
		 * @since 0.4.2 (2018-02-16)
		 */
		add_action( 'init', [ $this, 'register_secondary_post_status' ], 0 );

		/**
		 * Add theme name to body classes
		 *
		 * @since 0.5.1 (2018-03-22)
		 */
		add_filter( 'body_class', [ $this, 'add_body_classes' ] );

		/**
		 * Filters the retrieved excerpt.
		 *
		 * @since 0.5.5
		 */
		add_filter( 'get_the_excerpt', [ $this, 'get_the_excerpt' ], 5, 2 );

		/**
		 * Add plugin meta links.
		 *
		 * @since 0.8.1
		 */
		add_filter( 'plugin_row_meta', [ $this, 'add_plugin_meta_links' ], 10, 2 );

		/**
		 * Renders notice if CMB2 not installed.
		 *
		 * @since 0.9.0
		 */
		add_action( 'admin_notices', [ $this, 'notice_cmb_not_installed' ] );

		/**
		 * Filters the post title.
		 *
		 * @since 0.10.6
		 */
		add_filter( 'the_title', [ $this, 'filter_post_title' ], 10, 2 );

		/**
		 * Filters the post title.
		 *
		 * @since 0.10.17
		 */
		add_filter( 'nav_menu_item_title', [ $this, 'fix_filter_post_menu_title' ], 9, 2 );

		/**
		 * Add redirect to premium page.
		 *
		 * @since 0.10.7
		 */
		add_action( 'admin_init', [ $this, 'page_redirect' ] );

		/**
		 * Inject media image instead of thumbnail id to use on the archive page.
		 */
		add_filter( 'get_post_metadata', [ $this, 'insert_thumbnail_id' ], 20, 3 );
		add_action( 'kadence_before_main_content', [ $this, 'add_kadence_thumbnail_support' ] );

		/**
		 * Prime entity null-caches on plugin CPT admin list screens.
		 *
		 * @since 0.18.2
		 */
		add_filter( 'the_posts', [ $this, 'prime_admin_list_null_cache' ], 10, 2 );

		/**
		 * Fix Divi content duplication
		 *
		 * @since 0.10.12
		 */
		add_filter( 'et_first_image_use_custom_content', [ $this, 'fix_divi_duplicate_content' ], 20, 3 );

		add_filter(
			'wpsh_date_skip_formats',
			function ( $skip_formats ) {
				return array_merge( $skip_formats, [ 'Z' ] );
			}
		);

		// Notices
		add_action( 'admin_notices', [ $this, 'notice_data_migration_required' ] );
		add_action( 'admin_notices', [ $this, 'display_admin_pre_remove_notice' ] );
		add_action( 'admin_notices', [ $this, 'notice_premium_too_old' ] );

		add_action( 'pre_delete_term', [ $this, 'maybe_prevent_delete_term' ], 10, 2 );
		add_filter( 'pre_delete_post', [ $this, 'maybe_prevent_delete_competition' ], 10, 2 );
		add_filter( 'pre_trash_post', [ $this, 'maybe_prevent_delete_competition' ], 10, 2 );
	}

	/**
	 * Fix duplicate content on Divi sometimes.
	 *
	 * @param         $bool
	 * @param         $content
	 * @param WP_Post $post
	 *
	 * @return bool|string
	 * @since 0.10.12
	 */
	public function fix_divi_duplicate_content( $bool, $content, $post ) {

		if ( in_array( $post->post_type, $this->plugin_post_types, true ) ) {
			return '';
		}

		return $bool;
	}

	/**
	 * Prime entity null-caches for visible post IDs on plugin admin list screens.
	 *
	 * WP core hooks (meta lookups, thumbnail filter, etc.) speculatively probe
	 * anwp_fl()->{match,club,competition,standing,player}->get_row( $id ) for
	 * every post row. Post IDs are globally unique across post_types, so IDs
	 * from one CPT can never legitimately exist as a row in a different
	 * entity's table. Priming the negative cache with visible post IDs up
	 * front collapses 150+ wasted SELECTs into zero.
	 *
	 * The only entity whose table we skip is the one whose primary key would
	 * legitimately equal a visible post ID (e.g., skip `anwpfl_matches` on the
	 * match admin list, since match post IDs ARE match_id values).
	 *
	 * @since 0.18.2
	 *
	 * @param WP_Post[] $posts Posts returned by the query.
	 * @param WP_Query  $query Current query.
	 *
	 * @return WP_Post[] Unchanged.
	 */
	public function prime_admin_list_null_cache( $posts, $query ) {
		if ( ! is_admin() || empty( $posts ) || ! $query instanceof WP_Query ) {
			return $posts;
		}

		if ( ! $query->is_main_query() ) {
			return $posts;
		}

		$post_type = $query->get( 'post_type' );

		if ( ! is_string( $post_type ) || 0 !== strpos( $post_type, 'anwp_' ) ) {
			return $posts;
		}

		$ids = wp_list_pluck( $posts, 'ID' );

		if ( empty( $ids ) ) {
			return $posts;
		}

		// Map each CPT to its own entity table (if any). The listed entity is
		// SKIPPED during priming because post IDs for that CPT legitimately
		// appear as rows in its own table.
		$own_entity = [
			'anwp_match'       => 'match',
			'anwp_club'        => 'club',
			'anwp_competition' => 'competition',
			'anwp_standing'    => 'standing',
			'anwp_player'      => 'player',
		];

		$skip = $own_entity[ $post_type ] ?? '';

		foreach ( [ 'match', 'club', 'competition', 'standing', 'player' ] as $entity ) {
			if ( $entity === $skip ) {
				continue;
			}

			$this->$entity->prime_missing_ids( $ids );
		}

		// Per-CPT bulk warmers for admin-list columns that would otherwise N+1.
		if ( 'anwp_club' === $post_type ) {
			$this->club->warm_clubs_full( $ids );
			$this->club->warm_admin_list_game_counts( $ids );
		} elseif ( 'anwp_competition' === $post_type ) {
			$this->competition->warm_competitions_full( $ids );
		} elseif ( 'anwp_match' === $post_type ) {
			$this->match->warm_admin_list_game_data( $ids );
		} elseif ( 'anwp_player' === $post_type ) {
			$this->player->warm_admin_list_player_data( $ids );

			// Current-club column reads each player's team_id. Warm those club
			// rows in one bulk fetch.
			$club_ids = [];

			foreach ( $ids as $player_id ) {
				$team_id = (int) ( $this->player->get_player_data( (int) $player_id )['team_id'] ?? 0 );

				if ( $team_id ) {
					$club_ids[] = $team_id;
				}
			}

			if ( ! empty( $club_ids ) ) {
				$this->club->warm_clubs_full( array_unique( $club_ids ) );
			}
		} elseif ( 'anwp_standing' === $post_type ) {
			$this->standing->warm_standings_full( $ids );

			// Competition column reads each standing's competition_id. Warm
			// those competition rows in one bulk fetch.
			$competition_ids = [];

			foreach ( $ids as $standing_id ) {
				$competition_id = (int) ( $this->standing->get_row( (int) $standing_id )['competition_id'] ?? 0 );

				if ( $competition_id ) {
					$competition_ids[] = $competition_id;
				}
			}

			if ( ! empty( $competition_ids ) ) {
				$this->competition->warm_competitions_full( array_unique( $competition_ids ) );
			}
		}

		return $posts;
	}

	/**
	 * Insert media images as thumbnails to use at archive page.
	 *
	 * @param null   $check
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 *
	 * @return mixed
	 * @since 0.10.9
	 */
	public function insert_thumbnail_id( $check, $object_id, $meta_key ) {

		$is_plugin_instance_archive = is_post_type_archive( [ 'anwp_player', 'anwp_competition', 'anwp_referee', 'anwp_staff', 'anwp_stadium', 'anwp_club' ] );
		$allow_look_for_everywhere  = apply_filters( 'anwpfl/thumbnail/look_for_everywhere', false );

		if ( ( ! is_search() && ! $is_plugin_instance_archive && ! $allow_look_for_everywhere ) || empty( $object_id ) ) {
			return $check;
		}

		if ( '_thumbnail_id' !== $meta_key ) {
			return $check;
		}

		$thumbnail_id = 0;

		switch ( get_post_type( $object_id ) ) {
			case 'anwp_stadium':
			case 'anwp_referee':
			case 'anwp_staff':
				$thumbnail_id = get_post_meta( $object_id, '_anwpfl_photo_id', true );
				break;

			case 'anwp_club':
				$thumbnail_id = anwp_fl()->club->get_club_list_row( (int) $object_id )['logo_id'] ?? 0;
				break;

			case 'anwp_competition':
				$row          = anwp_fl()->competition->get_competition_list_row( (int) $object_id );
				$thumbnail_id = ( $row['logo_big_id'] ?? 0 ) ?: ( $row['logo_id'] ?? 0 );
				break;

			case 'anwp_player':
				$thumbnail_url = anwp_fl()->player->get_player_data( $object_id )['photo'] ?? '';

				if ( $thumbnail_url ) {
					$thumbnail_id = AnWPFL_Helper::get_image_id_by_url( anwp_fl()->upload_dir . $thumbnail_url );
				}
				break;
		}

		if ( empty( $thumbnail_id ) ) {
			return $check;
		}

		return $thumbnail_id;
	}

	/**
	 * Get thumbnail ID for the archive page
	 *
	 * @since 0.16.0
	 */
	public function add_kadence_thumbnail_support() {

		if ( ( ! is_search() && ! ( is_post_type_archive( [ 'anwp_player', 'anwp_referee', 'anwp_competition', 'anwp_staff', 'anwp_stadium', 'anwp_club' ] ) ) ) ) {
			return;
		}

		add_post_type_support( 'anwp_player', 'thumbnail' );
		add_post_type_support( 'anwp_competition', 'thumbnail' );
		add_post_type_support( 'anwp_club', 'thumbnail' );
		add_post_type_support( 'anwp_staff', 'thumbnail' );
		add_post_type_support( 'anwp_referee', 'thumbnail' );
		add_post_type_support( 'anwp_stadium', 'thumbnail' );
	}

	/**
	 * Filter Post title.
	 *
	 * @param string $title   The post title.
	 * @param int    $post_id The post ID.
	 *
	 * @since 0.10.6
	 * @return string
	 */
	public function filter_post_title( $title, $post_id = null ) {

		if ( 'no' === AnWPFL_Customizer::get_static_value( 'general', 'hide_post_titles' ) ) {
			return $title;
		}

		if ( is_singular( [ 'anwp_match' ] ) && 'anwp_match' === get_post_type( $post_id ) && is_main_query() && get_the_ID() === $post_id ) {
			return '';
		}

		if ( is_singular( [ 'anwp_competition' ] ) && 'anwp_competition' === get_post_type( $post_id ) && is_main_query() && get_the_ID() === $post_id ) {
			return '';
		}

		return $title;
	}

	/**
	 * Fix Post title filtering in Menu.
	 *
	 * @param string  $title   The post title.
	 * @param WP_Post $post The post ID.
	 *
	 * @return string
	 * @since 0.10.17
	 */
	public function fix_filter_post_menu_title( $title, $post ) {

		if ( '' === $post->post_title && ! empty( $post->object ) && in_array( $post->object, [ 'anwp_match', 'anwp_competition' ], true ) && 'no' !== anwp_fl()->customizer->get_value( 'general', 'hide_post_titles' ) ) {
			if ( ! empty( $post->object_id ) && absint( $post->object_id ) ) {
				$menu_object_post = get_post( $post->object_id );

				return $menu_object_post->post_title;
			}
		}

		return $title;
	}

	/**
	 * Add body classes.
	 *
	 * @param array $classes
	 *
	 * @return array
	 * @since 0.10.2
	 */
	public function add_body_classes( $classes ) {
		global $is_IE;

		// If it's IE, add a class.
		if ( $is_IE ) {
			$classes[] = 'ie';
		}

		if ( 'no' !== anwp_fl()->customizer->get_value( 'general', 'hide_post_titles' ) ) {
			$classes[] = 'anwp-hide-titles';
		}

		$classes[] = 'theme--' . wp_get_theme()->get_template();

		// ANWP-CSS v2.0.0 scope class - boosts specificity of spacing/display/text-align utilities (0,2,0) to beat theme selectors like .entry-content p (0,1,1).
		if ( ! in_array( 'anwp-css-body', $classes, true ) ) {
			$classes[] = 'anwp-css-body';
		}

		return $classes;
	}

	/**
	 * Filters the retrieved excerpt.
	 *
	 * @param string  $post_excerpt The post excerpt.
	 * @param WP_Post $post         Post object.
	 *
	 * @since 0.5.5
	 * @return string Modified post excerpt
	 */
	public function get_the_excerpt( $post_excerpt, $post = null ): string {

		if ( ! $post ) {
			return $post_excerpt;
		}

		if ( in_array( $post->post_type, $this->plugin_post_types, true ) && empty( $post_excerpt ) ) {
			$post_excerpt = $post->post_title;
		}

		return $post_excerpt;
	}

	/**
	 * Add plugin meta links.
	 *
	 * @param array  $links       An array of the plugin's metadata,
	 *                            including the version, author,
	 *                            author URI, and plugin URI.
	 * @param string $file        Path to the plugin file, relative to the plugins directory.
	 *
	 * @since 0.8.1
	 * @return array
	 */
	public function add_plugin_meta_links( $links, $file ) {

		if ( false !== strpos( $file, $this->basename ) ) {
			$new_links = [
				'doc'       => '<a href="https://anwppro.userecho.com/communities/1-football-leagues" target="_blank">' . esc_html__( 'Documentation', 'anwp-football-leagues' ) . '</a>',
				'changelog' => '<a href="https://anwppro.userecho.com/knowledge-bases/11-fl-changelog/categories/28-basic-version/articles" target="_blank">' . esc_html__( 'Changelog', 'anwp-football-leagues' ) . '</a>',
				'premium'   => '<a href="https://anwp.pro/football-leagues-premium/" target="_blank">' . esc_html__( 'Go Premium', 'anwp-football-leagues' ) . '</a>',
			];

			$links = array_merge( $links, $new_links );
		}

		return $links;
	}

	/**
	 * Register custom status for secondary stage (in multistage competition)
	 *
	 * @since 0.4.2 (2018-02-16)
	 */
	public function register_secondary_post_status() {
		register_post_status(
			'stage_secondary',
			[
				'label'                     => esc_html_x( 'Stage Secondary', 'post status', 'anwp-football-leagues' ),
				'public'                    => false,
				'internal'                  => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
			]
		);
	}

	/**
	 * Flush rewrite rules if the previously added flag exists,
	 * and then remove the flag.
	 *
	 * from - https://andrezrv.com/2014/08/12/efficiently-flush-rewrite-rules-plugin-activation/
	 *
	 * @since 0.3.0 (2018-02-05)
	 */
	public function flush_rewrite_rules_maybe() {

		// Check flag exists
		if ( get_option( 'anwpfl_flush_rewrite_rules_flag' ) ) {

			// Flush and delete flag
			flush_rewrite_rules();
			delete_option( 'anwpfl_flush_rewrite_rules_flag' );
		}
	}

	/**
	 * Manually render a CMB2 field.
	 *
	 * @deprecated Will be removed soon.
	 *
	 * @param  array      $field_args Array of field arguments.
	 * @param  CMB2_Field $field      The field object
	 */
	public function cmb_render_row_cb( /** @noinspection PhpUnusedParameterInspection */ $field_args, $field ) {

		$id    = $field->args( 'id' );
		$label = $field->args( 'name' );
		$name  = $field->args( '_name' );
		$value = $field->escaped_value();

		$field->peform_param_callback( 'before_row' );
		?>
		<div class="anwp-mb-3 <?php echo esc_attr( $field->row_classes() ); ?>" data-fieldtype="<?php echo esc_attr( $field->type() ); ?>">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>

			<?php $field->peform_param_callback( 'before' ); ?>

			<?php if ( 'text' === $field->type() ) : ?>

				<input
					id="<?php echo esc_attr( $id ); ?>"
					class="form-control"
					type="text"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"/>

			<?php else : ?>

				<?php
				$types = new CMB2_Types( $field );
				$types->render();
				?>

			<?php endif; ?>

			<?php $field->peform_param_callback( 'after' ); ?>
		</div>
		<?php

		$field->peform_param_callback( 'after_row' );
	}

	/**
	 * Register menu pages.
	 *
	 * @since 0.1.0 (2017-10-17)
	 * @since 0.17.0 Changed menu position from 32 to 26
	 */
	public function register_menus() {

		add_menu_page(
			esc_html_x( 'Football Leagues', 'admin page title', 'anwp-football-leagues' ),
			esc_html_x( 'Football Leagues', 'admin menu title', 'anwp-football-leagues' ),
			'manage_options',
			'anwp-football-leagues',
			[ $this, 'render_tutorials_page' ],
			self::SVG_BALL,
			26
		);

		/*
		|--------------------------------------------------------------------------
		| Prepare submenu pages
		|--------------------------------------------------------------------------
		*/
		$submenu_pages = [
			'tutorials'     => [
				'parent_slug' => 'anwp-football-leagues',
				'page_title'  => esc_html__( 'Dashboard', 'anwp-football-leagues' ),
				'menu_title'  => esc_html__( 'Dashboard', 'anwp-football-leagues' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'anwp-football-leagues',
				'output_func' => '',
			],
			'shortcodes'    => [
				'parent_slug' => 'anwp-football-leagues',
				'page_title'  => esc_html__( 'Shortcodes', 'anwp-football-leagues' ),
				'menu_title'  => esc_html__( 'Shortcodes', 'anwp-football-leagues' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'anwpfl-shortcodes',
				'output_func' => [ $this, 'render_shortcode_page' ],
			],
			'support'       => [
				'parent_slug' => 'anwp-football-leagues',
				'page_title'  => esc_html__( 'Support', 'anwp-football-leagues' ),
				'menu_title'  => esc_html__( 'Support', 'anwp-football-leagues' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'anwpfl-support',
				'output_func' => [ $this, 'render_support_page' ],
			],
			'plugin-health' => [
				'parent_slug' => 'anwp-football-leagues',
				'page_title'  => esc_html__( 'Plugin Health', 'anwp-football-leagues' ),
				'menu_title'  => esc_html__( 'Plugin Health', 'anwp-football-leagues' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'anwpfl-plugin-health',
				'output_func' => [ $this, 'render_health_page' ],
			],
			'toolbox'       => [
				'parent_slug' => 'anwp-football-leagues',
				'page_title'  => esc_html__( 'Toolbox', 'anwp-football-leagues' ),
				'menu_title'  => esc_html__( 'Toolbox', 'anwp-football-leagues' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'anwpfl-toolbox',
				'output_func' => [ $this, 'render_toolbox_page' ],
			],
			'customize'     => [
				'parent_slug' => 'anwp-football-leagues',
				'page_title'  => esc_html__( 'Customize', 'anwp-football-leagues' ),
				'menu_title'  => esc_html__( 'Customize', 'anwp-football-leagues' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'anwpfl-plugin-customize',
				'output_func' => [ $this, 'render_customize_page' ],
			],
			'premium'       => [
				'parent_slug' => 'anwp-football-leagues',
				'page_title'  => '',
				'menu_title'  => '<span style="color: #fd7e14">' . esc_html__( 'Go Premium', 'anwp-football-leagues' ) . '</span>',
				'capability'  => 'manage_options',
				'menu_slug'   => 'redirect_anwpfl_premium',
				'output_func' => [ $this, 'page_redirect' ],
			],
		];

		/**
		 * Filters loaded submenu pages.
		 *
		 * @since 0.5.5
		 *
		 * @param array Array of submenus
		 */
		$submenu_pages = apply_filters( 'anwpfl/admin/submenu_pages', $submenu_pages );

		foreach ( $submenu_pages as $m ) {
			add_submenu_page( $m['parent_slug'], $m['page_title'], $m['menu_title'], $m['capability'], $m['menu_slug'], $m['output_func'] );
		}
	}

	/**
	 * Register settings menu pages.
	 *
	 * @since 0.10.14
	 * @since 0.17.0 Changed menu position from 32 to 27, added Translations menu
	 */
	public function register_alt_menus() {

		// Settings & Tools (position 27)
		add_menu_page(
			esc_html_x( 'Settings & Tools', 'admin page title', 'anwp-football-leagues' ),
			esc_html_x( 'Settings & Tools', 'admin menu title', 'anwp-football-leagues' ),
			'manage_options',
			'anwp-settings-tools',
			'',
			self::SVG_BALL,
			27
		);

		// Translations (position 28)
		// Uses CMB2 page URL as slug - clicking menu goes directly to Text Options
		add_menu_page(
			esc_html_x( 'Translations', 'admin page title', 'anwp-football-leagues' ),
			esc_html_x( 'Translations', 'admin page title', 'anwp-football-leagues' ),
			'manage_options',
			'admin.php?page=anwp_fl_text',
			'',
			'dashicons-translation',
			28
		);

		/*
		|--------------------------------------------------------------------------
		| Settings Menu
		|--------------------------------------------------------------------------
		*/
		$submenu_settings_pages = [
			'tools' => [
				'parent_slug' => 'anwp-settings-tools',
				'page_title'  => esc_html__( 'Tools', 'anwp-football-leagues' ),
				'menu_title'  => esc_html__( 'Data Import', 'anwp-football-leagues' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'anwp-settings-tools',
				'output_func' => [ $this, 'render_tools_page' ],
			],
		];

		/**
		 * Filters loaded submenu pages.
		 *
		 * @since 0.10.14
		 *
		 * @param array Array of submenus
		 */
		$submenu_settings_pages = apply_filters( 'anwpfl/admin/submenu_settings_pages', $submenu_settings_pages );

		foreach ( $submenu_settings_pages as $m ) {
			add_submenu_page( $m['parent_slug'], $m['page_title'], $m['menu_title'], $m['capability'], $m['menu_slug'], $m['output_func'] );
		}
	}

	/**
	 * Register entity group menus.
	 *
	 * Creates parent menus for grouping CPTs:
	 * - Teams & People: Clubs, Players, Staff, Referees, Stadiums
	 * - Competitions: Competitions, Matches
	 *
	 * @since 0.17.0
	 */
	public function register_entity_menus() {

		// Clubs & People (position 30)
		// Uses CPT edit URL as slug - clicking menu goes directly to Players list
		add_menu_page(
			esc_html__( 'Clubs & People', 'anwp-football-leagues' ),
			esc_html__( 'Clubs & People', 'anwp-football-leagues' ),
			'edit_posts',
			'edit.php?post_type=anwp_player',
			'',
			self::SVG_VS,
			30
		);

		// Competitions (position 31)
		// Uses CPT edit URL as slug - clicking menu goes directly to Competitions list
		add_menu_page(
			esc_html__( 'Competitions', 'anwp-football-leagues' ),
			esc_html__( 'Competitions', 'anwp-football-leagues' ),
			'edit_posts',
			'edit.php?post_type=anwp_competition',
			'',
			self::SVG_CUP,
			31
		);

		// Add Leagues taxonomy to Competitions menu
		add_submenu_page(
			'edit.php?post_type=anwp_competition',
			esc_html__( 'Leagues', 'anwp-football-leagues' ),
			esc_html__( 'Leagues', 'anwp-football-leagues' ),
			'manage_categories',
			'edit-tags.php?taxonomy=anwp_league&post_type=anwp_competition'
		);

		// Add Seasons taxonomy to Competitions menu
		add_submenu_page(
			'edit.php?post_type=anwp_competition',
			esc_html__( 'Seasons', 'anwp-football-leagues' ),
			esc_html__( 'Seasons', 'anwp-football-leagues' ),
			'manage_categories',
			'edit-tags.php?taxonomy=anwp_season&post_type=anwp_competition'
		);

		// Remove duplicate first submenu items (WordPress auto-creates them)
		add_action( 'admin_menu', [ $this, 'remove_duplicate_submenus' ], 999 );
	}

	/**
	 * Remove duplicate submenu items and reorder Competitions menu.
	 *
	 * When using page URLs as menu slugs, WordPress creates an auto-generated
	 * first submenu item. This removes duplicates for Translations, Clubs & People,
	 * and Competitions menus, and ensures proper ordering (Competitions first, then Matches, etc.).
	 *
	 * @since 0.17.0
	 */
	public function remove_duplicate_submenus() {
		global $submenu;

		// Translations menu: remove auto-generated "Translations" duplicate
		$trans_slug  = 'admin.php?page=anwp_fl_text';
		$trans_title = esc_html_x( 'Translations', 'admin page title', 'anwp-football-leagues' );

		if ( isset( $submenu[ $trans_slug ] ) ) {
			foreach ( $submenu[ $trans_slug ] as $key => $item ) {
				if ( $item[0] === $trans_title ) {
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					unset( $submenu[ $trans_slug ][ $key ] );
					break;
				}
			}
		}

		// Clubs & People menu: remove auto-generated "Clubs & People" duplicate
		$clubs_slug  = 'edit.php?post_type=anwp_player';
		$clubs_title = esc_html__( 'Clubs & People', 'anwp-football-leagues' );

		if ( isset( $submenu[ $clubs_slug ] ) ) {
			foreach ( $submenu[ $clubs_slug ] as $key => $item ) {
				if ( $item[0] === $clubs_title ) {
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					unset( $submenu[ $clubs_slug ][ $key ] );
					break;
				}
			}
		}

		// Competitions menu: deduplicate and put Competitions first
		$comp_slug = 'edit.php?post_type=anwp_competition';

		if ( ! isset( $submenu[ $comp_slug ] ) ) {
			return;
		}

		$comp_title = esc_html__( 'Competitions', 'anwp-football-leagues' );
		$comp_item  = null;
		$other      = [];

		foreach ( $submenu[ $comp_slug ] as $item ) {
			if ( $item[0] === $comp_title ) {
				$comp_item = $item; // Keep last match (deduplicates)
			} else {
				$other[] = $item;
			}
		}

		// Rebuild: Competitions first, then others
		$new_order = $comp_item ? array_merge( [ $comp_item ], $other ) : $other;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu[ $comp_slug ] = [];
		$pos                   = 5;

		foreach ( $new_order as $item ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$submenu[ $comp_slug ][ $pos ] = $item;
			$pos                          += 5;
		}
	}

	/**
	 * Fix parent menu highlighting for taxonomy pages.
	 *
	 * @param string $parent_file The parent file.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	public function fix_parent_highlight( string $parent_file ): string {
		global $pagenow;

		// phpcs:ignore WordPress.Security.NonceVerification
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';

		// Fix Competitions menu parent highlighting for League/Season taxonomy pages
		if ( 'edit-tags.php' === $pagenow && in_array( $taxonomy, [ 'anwp_league', 'anwp_season' ], true ) ) {
			$parent_file = 'edit.php?post_type=anwp_competition';
		}

		return $parent_file;
	}

	/**
	 * Fix submenu highlighting for menus using page URLs as slugs.
	 *
	 * @param string|null $submenu_file The submenu file.
	 *
	 * @return string|null
	 * @since 0.17.0
	 */
	public function fix_submenu_highlight( ?string $submenu_file ): ?string {
		global $pagenow;

		// phpcs:ignore WordPress.Security.NonceVerification
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';

		// Fix Translations menu highlighting
		if ( 'admin.php' === $pagenow && in_array( $page, [ 'anwp_fl_text', 'anwp_fl_text_countries' ], true ) ) {
			$submenu_file = $page;
		}

		// Fix Competitions submenu highlighting for League/Season taxonomy pages
		if ( 'edit-tags.php' === $pagenow && in_array( $taxonomy, [ 'anwp_league', 'anwp_season' ], true ) ) {
			$submenu_file = 'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=anwp_competition';
		}

		return $submenu_file;
	}

	/**
	 * Rendering Tutorials page
	 *
	 * @since 0.1.0
	 */
	public function render_tutorials_page() {

		//must check that the user has the required capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'anwp-football-leagues' ) );
		}

		self::include_file( 'admin/views/tutorials' );
	}

	/**
	 * Get dashboard data for admin dashboard page.
	 *
	 * @since 0.17.0
	 * @return array Dashboard data including counts and dependency checks.
	 */
	public function get_dashboard_data(): array {
		global $wpdb;

		// Entity counts (WordPress caches these internally)
		$counts = [
			'leagues'      => absint( wp_count_terms( [ 'taxonomy' => 'anwp_league', 'hide_empty' => false ] ) ),
			'seasons'      => absint( wp_count_terms( [ 'taxonomy' => 'anwp_season', 'hide_empty' => false ] ) ),
			'clubs'        => absint( wp_count_posts( 'anwp_club' )->publish ?? 0 ),
			'players'      => absint( wp_count_posts( 'anwp_player' )->publish ?? 0 ),
			'competitions' => absint( wp_count_posts( 'anwp_competition' )->publish ?? 0 ),
			'stadiums'     => absint( wp_count_posts( 'anwp_stadium' )->publish ?? 0 ),
			'staff'        => absint( wp_count_posts( 'anwp_staff' )->publish ?? 0 ),
			'referees'     => absint( wp_count_posts( 'anwp_referee' )->publish ?? 0 ),
			'matches'      => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->anwpfl_matches}" ) ?? 0 ),
		];

		// Dependency checks for conditional Add buttons
		$can_add = [
			'competitions' => ( $counts['leagues'] > 0 && $counts['seasons'] > 0 ),
			'matches'      => ( $counts['competitions'] > 0 && $counts['clubs'] >= 2 ),
		];

		// New user detection (no matches = new user)
		$is_new_user = ( 0 === $counts['matches'] );

		// Premium API Import check
		$is_premium_new = false;

		if ( function_exists( 'anwp_fl_pro' ) ) {
			$api_config     = get_option( 'anwpfl_api_import_config', [] );
			$is_premium_new = empty( $api_config['key'] ?? '' );
		}

		return [
			'counts'         => $counts,
			'can_add'        => $can_add,
			'is_new_user'    => $is_new_user,
			'is_premium_new' => $is_premium_new,
		];
	}

	/**
	 * Parse changelog file and return structured version data with color emojis.
	 *
	 * @since 0.17.0
	 *
	 * @param string $file_path Path to changelog.txt file.
	 * @param int    $limit     Number of versions to return. Default 3.
	 *
	 * @return array Array of version data with 'version', 'date', and 'changes' keys.
	 */
	public function parse_changelog( string $file_path, int $limit = 3 ): array {
		if ( ! file_exists( $file_path ) ) {
			return [];
		}

		// Emoji map for change type prefixes (supports both Title Case and lowercase)
		$emoji_map = [
			'add'         => '🟢',
			'fix'         => '🟠',
			'fixed'       => '🟠',
			'performance' => '🟡',
			'update'      => '🔵',
			'improve'     => '🔵',
			'improved'    => '🔵',
			'tweak'       => '⚪',
			'changed'     => '🔵',
			'new feature' => '🚀',
			'security'    => '🔒',
			'note'        => '⚠️',
		];

		$content  = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$versions = [];

		// Match version blocks: = X.Y.Z - DATE = or = X.Y.Z - WIP =
		preg_match_all( '/^= ([0-9.]+) - ([^=]+) =\s*\n((?:\* .+\n?)+)/m', $content, $matches, PREG_SET_ORDER );

		foreach ( array_slice( $matches, 0, $limit ) as $match ) {
			$version = $match[1];
			$date    = trim( $match[2] );
			$changes = array_filter( array_map( 'trim', explode( "\n", trim( $match[3] ) ) ) );

			// Process each change line: remove "* ", add emoji based on prefix
			$changes = array_map(
				function ( $line ) use ( $emoji_map ) {
					$line = preg_replace( '/^\*\s*/', '', $line );

					// Extract prefix (first word or two words before " - " or ": ")
					if ( preg_match( '/^([\w ]+?)\s*[-:]\s*/', $line, $prefix_match ) ) {
						$prefix = strtolower( $prefix_match[1] );
						$emoji  = $emoji_map[ $prefix ] ?? '';

						if ( $emoji ) {
							$line = $emoji . ' ' . $line;
						}
					}

					return $line;
				},
				$changes
			);

			$versions[] = [
				'version' => $version,
				'date'    => $date,
				'changes' => array_values( $changes ),
			];
		}

		return $versions;
	}

	/**
	 * Rendering Tutorials page
	 *
	 * @since 0.5.5
	 */
	public function render_support_page() {

		//must check that the user has the required capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'anwp-football-leagues' ) );
		}

		self::include_file( 'admin/views/support' );
	}

	/**
	 * Rendering Plugin Health Page
	 *
	 * @since 0.13.2
	 */
	public function render_health_page() {

		//must check that the user has the required capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'anwp-football-leagues' ) );
		}

		self::include_file( 'admin/views/plugin-health' );
	}

	/**
	 * Rendering Optimizer Page
	 */
	public function render_toolbox_page() {

		//must check that the user has the required capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'anwp-football-leagues' ) );
		}

		self::include_file( 'admin/views/toolbox' );
	}

	/**
	 * Rendering Customize Page
	 *
	 * @since 0.14,0
	 */
	public function render_customize_page() {

		//must check that the user has the required capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'anwp-football-leagues' ) );
		}

		self::include_file( 'admin/views/customize' );
	}

	/**
	 * Rendering Shortcodes page
	 *
	 * @since 0.10.7
	 */
	public function render_shortcode_page() {

		// Check that the user has the required capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'anwp-football-leagues' ) );
		}

		self::include_file( 'admin/views/shortcodes' );
	}

	/**
	 * Rendering Tools page
	 *
	 * @since 0.8.2
	 */
	public function render_tools_page() {

		//must check that the user has the required capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'anwp-football-leagues' ) );
		}

		self::include_file( 'admin/views/tools' );
	}

	/**
	 * Rendering Premium page
	 *
	 * @since 0.8.0
	 */
	public function page_redirect() {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['page'] ) && 'redirect_anwpfl_premium' === $_GET['page'] ) {
			// phpcs:ignore WordPress.Security.SafeRedirect
			wp_redirect( 'https://anwp.pro/football-leagues-premium/' );
			die;
		}
	}

	/**
	 * Activate the plugin.
	 *
	 * @since  0.1.0
	 */
	public function activate() {

		// Add rewrite flag - from - https://andrezrv.com/2014/08/12/efficiently-flush-rewrite-rules-plugin-activation/
		if ( ! get_option( 'anwpfl_flush_rewrite_rules_flag' ) ) {
			add_option( 'anwpfl_flush_rewrite_rules_flag', true );
		}

		// Pre-set migration flags on fresh installs (zero FL CPT data).
		// Catches the fresh-install scenario at activation time, before any admin
		// page renders, so the migration notices can never fire when there's
		// nothing to migrate. Reactivation on existing data leaves flags alone.
		$club_counts = wp_count_posts( 'anwp_club' );
		$total_clubs = (int) ( $club_counts->publish ?? 0 ) + (int) ( $club_counts->draft ?? 0 ) + (int) ( $club_counts->pending ?? 0 ) + (int) ( $club_counts->private ?? 0 );

		if ( 0 === $total_clubs ) {
			update_option( 'anwpfl_clubs_migrated', 1, true );
			update_option( 'anwpfl_squad_migrated', 1, true );
			update_option( 'anwpfl_competitions_migrated', 1, true );
			update_option( 'anwpfl_standings_migrated', 1, true );

			// Bypass the pre-0.16 data-schema evaluation entirely. Premium can
			// register its toolbox-updater filter before Core's version_upgrade()
			// runs (when premium loads first in active_plugins), and that filter
			// can flag non-empty tasks against tables init priority 1 has not yet
			// created - which sets data_schema=15 and triggers the "Data Migration
			// Required" notice on a fresh install with nothing to migrate.
			// Pre-seeding anwpfl_version makes version_upgrade() return early at
			// the saved===current gate.
			update_option( 'anwpfl_data_schema', 16, true );
			update_option( 'anwpfl_version', self::VERSION, true );

			// Premium update_db() queues an FL+ competition backfill task whenever
			// anwpfl_competitions_migrated=1 and prev_db_version<21. Layer 1 trips
			// the first condition on every fresh install, so the toolbox shows a
			// no-op backfill task (wizard already writes directly to
			// anwpfl_competitions). Pre-set the "done" flag so it never queues.
			update_option( 'anwpfl_competitions_premium_backfilled', 1, true );
		}
	}

	/**
	 * Init hooks
	 *
	 * @since  0.1.0
	 */
	public function init() {

		// Load translated strings for plugin.
		load_plugin_textdomain( 'anwp-football-leagues', false, dirname( $this->basename ) . '/languages/' );

		// Include Gamajo_Template_Loader - http://github.com/GaryJones/Gamajo-Template-Loader
		require_once self::dir( 'vendor/class-gamajo-template-loader.php' );

		// Initialize plugin classes.
		$this->plugin_classes();

		// Include CMB2 fields.
		if ( is_admin() ) {
			require_once self::dir( 'includes/cmb2-fields/cmb-field-map.php' );
			require_once self::dir( 'includes/cmb2-fields/cmb-field-simple-trigger.php' );
			require_once self::dir( 'includes/cmb2-fields/cmb-field-ordering-list.php' );
			require_once self::dir( 'includes/cmb2-fields/cmb-anwp-fl-custom-fields.php' );
			require_once self::dir( 'includes/cmb2-fields/class-anwp-cmb2-field-ajax-search.php' );
			require_once self::dir( 'includes/cmb2-fields/class-anwp-fl-cmb2-field-select2.php' );
			require_once self::dir( 'includes/cmb2-fields/cmb-field-translated-text.php' );
			require_once self::dir( 'includes/cmb2-fields/cmb-field-anwp-fl-selector.php' );
		}
	}

	/**
	 * Load selector modal
	 *
	 * @return string
	 * @since 0.11.7
	 */
	public function include_selector_modaal() {
		ob_start();
		?>
		<dialog fl-x-data
			fl-x-effect="(()=>{ try { $store.selectorModal.isOpen ? (!$el.open && $el.showModal()) : ($el.open && $el.close()); } catch(e) {} })()"
			fl-x-on:close="$store.selectorModal.isOpen && $store.selectorModal.closeModal()"
			class="anwp-x-modal">
			<div fl-x-show="$store.selectorModal.isOpen" class="anwp-d-flex--noimp anwp-x-modal__wrapper">
				<div class="anwp-x-modal__header">
					<h3 style="margin: 0">FL Selector: <span fl-x-text="$store.selectorModal.contextHeader"></span></h3>
				</div>

				<div fl-x-on:click="$store.selectorModal.closeModal()" class="anwp-x-modal__close-button">
					<span>X</span>
				</div>

				<div class="anwp-x-modal__section anwp-x-modal__search-bar">
					<div class="anwp-x-modal__bar-group anwp-mr-2 anwp-mt-2">
						<label for="anwp-x-modal__field__search"><?php echo esc_html__( 'start typing name or title ...', 'anwp-football-leagues' ); ?></label>
						<input fl-x-on:input.debounce="$store.selectorModal.sendSearchRequest()" fl-x-model="$store.selectorModal.s" name="s" type="text" id="anwp-x-modal__field__search" value="" class="fl-shortcode-attr code">
					</div>
					<div fl-x-show="['player','staff'].includes( $store.selectorModal.context ) && ! $store.selectorModal.isLoadingGlobals" class="anwp-x-modal__bar-group anwp-mr-2 anwp-mt-2">
						<div class="anwp-d-flex--noimp">
							<label for="anwp-x-modal__field__club"><?php echo esc_html__( 'Club', 'anwp-football-leagues' ); ?></label>
							<span class="anwp-d-flex--noimp anwp-x-modal__clear-filter"
								fl-x-on:click="$store.selectorModal.clearFilter('clubs')"
								fl-x-show="$store.selectorModal.filterValues['clubs']">X</span>
						</div>
						<select name="clubs" id="anwp-x-modal__field__club" class="anwp-x-modal__select">
							<option value="">- select -</option>
						</select>
					</div>
					<div fl-x-show="['match'].includes( $store.selectorModal.context ) && ! $store.selectorModal.isLoadingGlobals" class="anwp-x-modal__bar-group anwp-mr-2 anwp-mt-2">
						<div class="anwp-d-flex--noimp">
							<label for="anwp-x-modal__field__club-home"><?php echo esc_html__( 'Home Club', 'anwp-football-leagues' ); ?></label>
							<span class="anwp-d-flex--noimp anwp-x-modal__clear-filter"
								fl-x-on:click="$store.selectorModal.clearFilter('club_home')"
								fl-x-show="$store.selectorModal.filterValues['club_home']">X</span>
						</div>
						<select name="club_home" id="anwp-x-modal__field__club-home" class="anwp-x-modal__select">
							<option value="">- select -</option>
						</select>
					</div>
					<div fl-x-show="['match'].includes( $store.selectorModal.context ) && ! $store.selectorModal.isLoadingGlobals" class="anwp-x-modal__bar-group anwp-mr-2 anwp-mt-2">
						<div class="anwp-d-flex--noimp">
							<label for="anwp-x-modal__field__club-away"><?php echo esc_html__( 'Away Club', 'anwp-football-leagues' ); ?></label>
							<span class="anwp-d-flex--noimp anwp-x-modal__clear-filter"
								fl-x-on:click="$store.selectorModal.clearFilter('club_away')"
								fl-x-show="$store.selectorModal.filterValues['club_away']">X</span>
						</div>
						<select name="club_away" id="anwp-x-modal__field__club-away" class="anwp-x-modal__select">
							<option value="">- select -</option>
						</select>
					</div>
					<div fl-x-show="['match','competition','stage','main_stage'].includes( $store.selectorModal.context ) && ! $store.selectorModal.isLoadingGlobals" class="anwp-x-modal__bar-group anwp-mr-2 anwp-mt-2">
						<div class="anwp-d-flex--noimp">
							<label for="anwp-x-modal__field__season">
								<?php echo esc_html__( 'Season', 'anwp-football-leagues' ); ?>
							</label>
							<span class="anwp-d-flex--noimp anwp-x-modal__clear-filter"
								fl-x-on:click="$store.selectorModal.clearFilter('seasons')"
								fl-x-show="$store.selectorModal.filterValues['seasons']">X</span>
						</div>

						<select name="seasons" id="anwp-x-modal__field__season" class="anwp-x-modal__select"></select>
					</div>
					<div fl-x-show="['match','competition','stage','main_stage'].includes( $store.selectorModal.context ) && ! $store.selectorModal.isLoadingGlobals" class="anwp-x-modal__bar-group anwp-mr-2 anwp-mt-2">
						<div class="anwp-d-flex--noimp">
							<label for="anwp-x-modal__field__league"><?php echo esc_html__( 'League', 'anwp-football-leagues' ); ?></label>
							<span class="anwp-d-flex--noimp anwp-x-modal__clear-filter"
								fl-x-on:click="$store.selectorModal.clearFilter('leagues')"
								fl-x-show="$store.selectorModal.filterValues['leagues']">X</span>
						</div>
						<select name="leagues" id="anwp-x-modal__field__league" class="anwp-x-modal__select"></select>
					</div>
					<div fl-x-show="['match'].includes( $store.selectorModal.context )" class="anwp-x-modal__bar-group anwp-mr-2 anwp-mt-2">
						<div class="anwp-d-flex--noimp">
							<label for="anwp-x-modal__field__date"><?php echo esc_html__( 'Date', 'anwp-football-leagues' ); ?></label>
						</div>
						<input fl-x-on:change="$store.selectorModal.sendSearchRequest()" fl-x-model="$store.selectorModal.date" name="date" type="date" id="anwp-x-modal__field__date" value="" class="fl-shortcode-attr code">
					</div>
					<div fl-x-show="['player','referee','club'].includes( $store.selectorModal.context ) && ! $store.selectorModal.isLoadingGlobals" class="anwp-x-modal__bar-group anwp-mr-2 anwp-mt-2">
						<div class="anwp-d-flex--noimp">
							<label for="anwp-x-modal__field__country"><?php echo esc_html__( 'Country/Nationality', 'anwp-football-leagues' ); ?></label>
							<span class="anwp-d-flex--noimp anwp-x-modal__clear-filter"
								fl-x-on:click="$store.selectorModal.clearFilter('countries')"
								fl-x-show="$store.selectorModal.filterValues['countries']">X</span>
						</div>
						<select name="countries" id="anwp-x-modal__field__country" class="anwp-x-modal__select"></select>
					</div>
					<div fl-x-show="$store.selectorModal.isLoadingGlobals" class="anwp-mt-2 anwp-d-flex--noimp anwp-align-items-center">
						<span class="spinner is-active" style="float: none; margin-top: 0;"></span>
					</div>
				</div>

				<div class="anwp-x-modal__section anwp-x-modal__section--secondary">
					<h4 style="margin: 0"><?php echo esc_html__( 'Selected Values', 'anwp-football-leagues' ); ?><span fl-x-show="$store.selectorModal.single"> (max - 1)</span>:
						<span fl-x-bind:class="$store.selectorModal.isLoadingInitial? 'is-active' : ''" class="spinner" style="float: none; margin-top: 0;"></span>
					</h4>
					<div fl-x-show="$store.selectorModal.selectedItems.length" fl-x-transition class="anwp-x-modal__selected-wrapper">
						<template fl-x-for="selectedItem in $store.selectorModal.selectedItems">
							<div class="anwp-x-modal__selected-item">
								<img fl-x-show="selectedItem.img" fl-x-bind:src="selectedItem.img" class="anwp-mr-2" alt="logo" style="width: 25px; height: 25px; object-fit: contain;" />
								<span fl-x-text="selectedItem.title"></span>
								<button fl-x-on:click="$store.selectorModal.removeSelected( selectedItem.id )"
									type="button" class="button button-small">
									X
								</button>
							</div>
						</template>
					</div>
					<div fl-x-show="!$store.selectorModal.selectedItems.length">
						- <?php echo esc_html__( 'none', 'anwp-football-leagues' ); ?> -
					</div>
				</div>

				<div class="anwp-x-modal__section anwp-x-modal__section-main">
					<table fl-x-show="$store.selectorModal.rows.length && !$store.selectorModal.isLoadingContent" class="wp-list-table widefat striped table-view-list">
						<thead>
						<tr fl-x-show="$store.selectorModal.columns.length">
							<td class="manage-column check-column"></td>
							<template fl-x-for="column in $store.selectorModal.columns">
								<td class="manage-column" fl-x-text="column.title"></td>
							</template>
						</tr>
						</thead>

						<tbody>
						<template fl-x-for="row in $store.selectorModal.rows">
							<tr>
								<td>
									<button
										fl-x-on:click="$store.selectorModal.addToSelected( row.id, row.title, row.img || '' )"
										fl-x-bind:disabled="$store.selectorModal.selectedItemIds.includes( row.id )"
										type="button" class="button button-small anwp-x-modal__section-action">
										+
									</button>
								</td>
								<template fl-x-for="column in $store.selectorModal.columns">
									<td fl-x-bind:class="'img' === column.slug ? 'anwp-w-10' : ''">
										<template fl-x-if="'img' === column.slug && row.img">
											<img fl-x-bind:src="row.img" class="anwp-admin-table-league-logo" alt="logo" style="width: 30px; height: 30px; object-fit: contain;" />
										</template>
										<template fl-x-if="'flag' === column.slug && row.flag">
											<svg class="fl-flag--rounded " width="25" height="25"><use fl-x-bind:href="row.flag"></use></svg>
										</template>
										<template fl-x-if="'img' !== column.slug && 'flag' !== column.slug">
											<span fl-x-text="row[column.slug]"></span>
										</template>
									</td>
								</template>
							</tr>
						</template>
						</tbody>

						<tfoot>
						<tr>
							<td class="manage-column check-column"></td>
							<template fl-x-for="column in $store.selectorModal.columns">
								<td class="manage-column" fl-x-text="column.title"></td>
							</template>
						</tr>
						</tfoot>
					</table>
					<div fl-x-show="!$store.selectorModal.rows.length && !$store.selectorModal.isLoadingContent"
							class="anwp-alert-warning">- <?php echo esc_html__( 'nothing found', 'anwp-football-leagues' ); ?> -</div>
				</div>
				<span fl-x-show="$store.selectorModal.isLoadingContent"
						fl-x-bind:class="$store.selectorModal.isLoadingContent? 'is-active' : ''"
						class="spinner anwp-x-modal__spinner"></span>

				<div class="anwp-x-modal__footer">
					<button fl-x-on:click="$store.selectorModal.closeModal()" class="button">
						<?php echo esc_html__( 'Cancel', 'anwp-football-leagues' ); ?>
					</button>
					<button fl-x-on:click="$store.selectorModal.insertSelected()" class="button button-primary anwp-ml-2">
						<?php echo esc_html__( 'Insert', 'anwp-football-leagues' ); ?>
					</button>
				</div>
			</div>
		</dialog>
		<?php
		return ob_get_clean();
	}

	/**
	 * Register widgets.
	 *
	 * @since 0.4.3 (2018-02-18)
	 */
	public function register_widgets() {

		// include classes
		self::include_file( 'includes/widgets/class-anwpfl-widget' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-standing' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-clubs' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-matches' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-players' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-cards' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-player' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-birthday' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-next-match' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-last-match' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-video' );
		self::include_file( 'includes/widgets/class-anwpfl-widget-competition-list' );

		// register widgets
		register_widget( 'AnWPFL_Widget_Standing' );
		register_widget( 'AnWPFL_Widget_Clubs' );
		register_widget( 'AnWPFL_Widget_Matches' );
		register_widget( 'AnWPFL_Widget_Players' );
		register_widget( 'AnWPFL_Widget_Cards' );
		register_widget( 'AnWPFL_Widget_Player' );
		register_widget( 'AnWPFL_Widget_Birthday' );
		register_widget( 'AnWPFL_Widget_Next_Match' );
		register_widget( 'AnWPFL_Widget_Last_Match' );
		register_widget( 'AnWPFL_Widget_Video' );
		register_widget( 'AnWPFL_Widget_Competition_List' );
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.1.0
	 *
	 * @param  string $field Field to get.
	 *
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
			case 'options':
			case 'league':
			case 'match':
			case 'match_admin':
			case 'season':
			case 'competition':
			case 'competition_admin':
			case 'club':
			case 'stadium':
			case 'health':
			case 'helper':
			case 'player':
			case 'staff':
			case 'referee':
			case 'cache':
			case 'standing':
			case 'template':
			case 'template_status':
			case 'text':
			case 'text_countries':
			case 'data':
			case 'data_port':
			case 'customizer':
			case 'blocks':
			case 'assets':
			case 'upgrade':
			case 'toolbox':
			case 'sitemap':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . esc_html( $field ) );
		}
	}

	/**
	 * Include a file from the includes directory.
	 *
	 * @param string $filename Name of the file to be included.
	 *
	 * @since  0.1.0
	 * @return mixed Result of include call.
	 */
	public static function include_file( string $filename ) {
		$file = self::dir( $filename . '.php' );
		if ( file_exists( $file ) ) {
			return include_once $file;
		}

		return false;
	}

	/**
	 * This plugin's directory.
	 *
	 * @param  string $path (optional) appended path.
	 *
	 * @since  0.1.0
	 * @return string       Directory and path.
	 */
	public static function dir( string $path = '' ): string {
		static $dir;
		$dir = $dir ?: trailingslashit( __DIR__ );

		return $dir . $path;
	}

	/**
	 * This plugin's url.
	 *
	 * @param  string $path (optional) appended path.
	 *
	 * @since  0.1.0
	 * @return string       URL and path.
	 */
	public static function url( string $path = '' ): string {
		static $url;
		$url = $url ? : trailingslashit( plugin_dir_url( __FILE__ ) );

		return $url . $path;
	}

	/**
	 * Load template partial.
	 * Proxy for template rendering class method.
	 *
	 * @param array|object $atts
	 * @param string       $slug
	 * @param string       $layout
	 *
	 * @since 0.6.1
	 * @return string
	 */
	public function load_partial( $atts, string $slug, string $layout = '' ) {

		$layout = empty( $layout ) ? '' : ( '-' . sanitize_key( $layout ) );
		return $this->template->set_template_data( $atts )->get_template_part( $slug, $layout );
	}

	/**
	 * Get list of plugin post types.
	 *
	 * @since 0.7.2 (2018-09-17)
	 * @return array
	 */
	public function get_post_types() {
		return $this->plugin_post_types;
	}

	/**
	 * Get Options value helper.
	 *
	 * @param  string $value
	 *
	 * @return string
	 * @since  0.7.5
	 */
	public function get_option_value( $value ) {
		return AnWPFL_Options::get_value( $value );
	}

	/**
	 * Get active season.
	 *
	 * @return int
	 * @since 0.8.4
	 */
	public function get_active_season() {

		// Get season ID from plugin options.
		$season_id = $this->get_option_value( 'active_season' );

		if ( ! $season_id ) {
			$season_options = anwp_fl()->season->get_seasons_options();

			if ( ! empty( $season_options ) && is_array( $season_options ) ) {
				$season_id = max( array_keys( $season_options ) );
			}
		}

		return (int) $season_id;
	}

	/**
	 * Get active player season.
	 *
	 * @param int $player_id
	 *
	 * @return int
	 * @since 0.11.6
	 */
	public function get_active_player_season( $player_id ) {

		// Get season ID from plugin options.
		$season_id = $this->get_option_value( 'active_season' );

		if ( 'yes' !== AnWPFL_Options::get_value( 'hide_not_used_seasons' ) ) {
			if ( ! $season_id ) {
				$season_options = anwp_fl()->season->get_seasons_options();

				if ( ! empty( $season_options ) && is_array( $season_options ) ) {
					$season_id = max( array_keys( $season_options ) );
				}
			}
		} elseif ( absint( $player_id ) ) {

			$filtered_season_slugs = anwp_fl()->helper->get_filtered_seasons( 'player', $player_id );

			// Check if active system season exists in player seasons
			if ( $season_id ) {
				$season_slug = anwp_fl()->season->get_season_slug_by_id( $season_id );

				if ( in_array( $season_slug, $filtered_season_slugs, true ) ) {
					return (int) $season_id;
				}
			}

			if ( ! empty( $filtered_season_slugs ) ) {
				rsort( $filtered_season_slugs, SORT_NUMERIC );
				$season_id = anwp_fl()->season->get_season_id_by_slug( $filtered_season_slugs[0] );
			}
		}

		return (int) $season_id;
	}

	/**
	 * Get active club season.
	 *
	 * @param int $club_id
	 *
	 * @return int
	 * @since 0.11.6
	 */
	public function get_active_club_season( $club_id ) {

		// Get season ID from plugin options.
		$season_id = $this->get_option_value( 'active_season' );

		if ( 'yes' !== AnWPFL_Options::get_value( 'hide_not_used_seasons' ) ) {
			if ( ! $season_id ) {
				$season_options = anwp_fl()->season->get_seasons_options();

				if ( ! empty( $season_options ) && is_array( $season_options ) ) {
					$season_id = max( array_keys( $season_options ) );
				}
			}
		} elseif ( absint( $club_id ) ) {

			$filtered_season_slugs = anwp_fl()->helper->get_filtered_seasons( 'club', $club_id );

			// Check if active system season exists in player seasons
			if ( $season_id ) {
				$season_slug = anwp_fl()->season->get_season_slug_by_id( $season_id );

				if ( in_array( $season_slug, $filtered_season_slugs, true ) ) {
					return (int) $season_id;
				}
			}

			if ( ! empty( $filtered_season_slugs ) ) {
				rsort( $filtered_season_slugs, SORT_NUMERIC );
				$season_id = anwp_fl()->season->get_season_id_by_slug( $filtered_season_slugs[0] );
			}
		}

		return (int) $season_id;
	}

	/**
	 * Get active club season.
	 *
	 * @param int $stadium_id
	 *
	 * @return int
	 * @since 0.11.6
	 */
	public function get_active_stadium_season( $stadium_id ) {

		// Get season ID from plugin options.
		$season_id = $this->get_option_value( 'active_season' );

		if ( 'yes' !== AnWPFL_Options::get_value( 'hide_not_used_seasons' ) ) {
			if ( ! $season_id ) {
				$season_options = anwp_fl()->season->get_seasons_options();

				if ( ! empty( $season_options ) && is_array( $season_options ) ) {
					$season_id = max( array_keys( $season_options ) );
				}
			}
		} elseif ( absint( $stadium_id ) ) {

			$filtered_season_slugs = anwp_fl()->helper->get_filtered_seasons( 'stadium', $stadium_id );

			// Check if active system season exists in player seasons
			if ( $season_id ) {
				$season_slug = anwp_fl()->season->get_season_slug_by_id( $season_id );

				if ( in_array( $season_slug, $filtered_season_slugs, true ) ) {
					return (int) $season_id;
				}
			}

			if ( ! empty( $filtered_season_slugs ) ) {
				rsort( $filtered_season_slugs, SORT_NUMERIC );
				$season_id = anwp_fl()->season->get_season_id_by_slug( $filtered_season_slugs[0] );
			}
		}

		return (int) $season_id;
	}

	/**
	 * Get active referee's season.
	 *
	 * @param int $referee_id
	 *
	 * @return int
	 * @since 0.11.17
	 */
	public function get_active_referee_season( $referee_id ) {

		// Get season ID from plugin options.
		$season_id = $this->get_option_value( 'active_season' );

		if ( 'yes' !== AnWPFL_Options::get_value( 'hide_not_used_seasons' ) ) {
			if ( ! $season_id ) {
				$season_options = anwp_fl()->season->get_seasons_options();

				if ( ! empty( $season_options ) && is_array( $season_options ) ) {
					$season_id = max( array_keys( $season_options ) );
				}
			}
		} elseif ( absint( $referee_id ) ) {

			$filtered_season_slugs = anwp_fl()->helper->get_filtered_seasons( 'referee', $referee_id );

			// Check if active system season exists in player seasons
			if ( $season_id ) {
				$season_slug = anwp_fl()->season->get_season_slug_by_id( $season_id );

				if ( in_array( $season_slug, $filtered_season_slugs, true ) ) {
					return (int) $season_id;
				}
			}

			if ( ! empty( $filtered_season_slugs ) ) {
				rsort( $filtered_season_slugs, SORT_NUMERIC );
				$season_id = anwp_fl()->season->get_season_id_by_slug( $filtered_season_slugs[0] );
			}
		}

		return (int) $season_id;
	}

	/**
	 * Overrides CMB2 label layout.
	 *
	 * @param            $field_args
	 * @param CMB2_Field $field
	 *
	 * @return string Label html markup.
	 * @since  0.9.0
	 */
	public function cmb2_field_label( $field_args, $field ) {

		if ( ! $field->args( 'name' ) ) {
			return '';
		}

		$output = sprintf( "\n" . '<label class="anwp-cmb2-label" for="%1$s">%2$s', $field->id(), $field->args( 'name' ) );

		// Check tooltip
		if ( ! empty( $field->args( 'label_tooltip' ) ) ) {
			$output .= '<span data-anwp-tooltip="' . esc_attr( $field->args( 'label_tooltip' ) ) . '"><svg class="anwp-icon anwp-icon--octi"><use href="#icon-info"></use></svg></span>';
		}

		$output .= '</label>' . "\n";

		// Check helper text
		if ( ! empty( $field->args( 'label_help' ) ) ) {
			$output .= "\n" . '<span class="anwp-cmb2-label__helper">' . $field->args( 'label_help' ) . '</span>';
		}

		return $output;
	}

	/**
	 * Metabox ShowOn filter for Non Fixed Data
	 *
	 * @param bool  $display
	 * @param array $meta_box
	 *
	 * @return bool display metabox
	 * @since 0.10.0
	 */
	public function show_on_fixed_metabox( $display, $meta_box ) {
		_deprecated_function( __METHOD__, '0.18.0' );

		if ( ! isset( $meta_box['show_on']['key'] ) ) {
			return $display;
		}

		if ( 'fixed' !== $meta_box['show_on']['key'] ) {
			return $display;
		}

		return $display;
	}

	/**
	 * Renders notice if CMB2 not installed.
	 *
	 * @since 0.9.0
	 */
	public function notice_cmb_not_installed() {

		if ( defined( 'CMB2_LOADED' ) ) {
			return;
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['action'] ) && 'install-plugin' === $_GET['action'] ) {
			return;
		}

		// Check CMB2 installed
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins      = get_plugins();
		$plugin_installed = isset( $all_plugins['cmb2/init.php'] );
		?>
		<div class="notice anwp-fl-cmb2-notice">
			<img src="<?php echo esc_url( self::url( 'admin/img/anwp-fl-icon.png' ) ); ?>" alt="fl icon">
			<img src="<?php echo esc_url( self::url( 'admin/img/cmb2-icon.png' ) ); ?>" alt="cmb icon">
			<h3>Please install and activate CMB2 plugin</h3>
			<p>CMB2 is required for proper work of AnWP Football Leagues, and is used for building metaboxes and custom fields.</p>
			<p>

				<?php if ( $plugin_installed && current_user_can( 'activate_plugins' ) ) : ?>
					<a href="<?php echo esc_url( wp_nonce_url( 'plugins.php?action=activate&plugin=' . rawurlencode( 'cmb2/init.php' ), 'activate-plugin_cmb2/init.php' ) ); ?>" class="button button-primary"><?php echo esc_html__( 'Activate plugin', 'anwp-football-leagues' ); ?></a>
				<?php elseif ( current_user_can( 'install_plugins' ) ) : ?>
					<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=cmb2' ), 'install-plugin_cmb2' ) ); ?>" class="button button-primary"><?php echo esc_html__( 'Install plugin', 'anwp-football-leagues' ); ?></a>
				<?php endif; ?>

				<a class="button" href="https://wordpress.org/plugins/cmb2/" target="_blank"><?php echo esc_html__( 'Plugin page at wp.org', 'anwp-football-leagues' ); ?></a>
			</p>
			<p class="anwp-notice-clear-both"></p>
		</div>
		<?php
	}

	/**
	 * Renders notice if Data Migration is required
	 *
	 * @since 0.16.0
	 */
	public function notice_data_migration_required() {

		$active_page     = $_GET['page'] ?? ''; // phpcs:ignore
		$premium_too_old = self::is_premium_too_old();

		/*
		|--------------------------------------------------------------------
		| v0.16.0
		|--------------------------------------------------------------------
		*/
		if ( absint( get_option( 'anwpfl_data_schema' ) ) < 16 && 'anwpfl-toolbox' !== $active_page ) {
			?>
			<div class="notice anwp-fl-cmb2-notice">
				<img src="<?php echo esc_url( self::url( 'admin/img/anwp-fl-icon.png' ) ); ?>" alt="fl icon">
				<h3>Important Notice: Data Migration Required</h3>
				<p>v0.16.0 introduces a new data structure that enhances performance and reduces database space. Open the Database Updater tool to migrate your data to the new format.</p>
				<p>
					<a href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox' ) ); ?>" class="button button-primary"><?php echo esc_html__( 'Database Updater', 'anwp-football-leagues' ); ?></a>
				</p>
				<p class="anwp-notice-clear-both"></p>
			</div>
			<?php
		}

		/*
		|--------------------------------------------------------------------
		| Competitions migration required
		|--------------------------------------------------------------------
		*/
		if ( ! get_option( 'anwpfl_competitions_migrated' ) && 'anwpfl-toolbox' !== $active_page ) {

			$competition_counts = wp_count_posts( 'anwp_competition' );
			$total_competitions = (int) ( $competition_counts->publish ?? 0 ) + (int) ( $competition_counts->draft ?? 0 ) + (int) ( $competition_counts->pending ?? 0 ) + (int) ( $competition_counts->private ?? 0 );

			if ( 0 === $total_competitions ) {
				update_option( 'anwpfl_competitions_migrated', 1, true );
			} else {
				?>
				<div class="notice anwp-fl-cmb2-notice">
					<img src="<?php echo esc_url( self::url( 'admin/img/anwp-fl-icon.png' ) ); ?>" alt="fl icon">
					<h3>Competitions Data Migration Required</h3>
					<p>Competition editing is disabled until the competitions data migration is complete. Open the Database Updater tool to run the migration.</p>
					<p>
						<?php if ( $premium_too_old ) : ?>
							<button type="button" class="button" disabled><?php echo esc_html__( 'Database Updater', 'anwp-football-leagues' ); ?></button>
						<?php else : ?>
							<a href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox' ) ); ?>" class="button button-primary"><?php echo esc_html__( 'Database Updater', 'anwp-football-leagues' ); ?></a>
						<?php endif; ?>
					</p>
					<?php if ( $premium_too_old ) : ?>
						<p><em style="color: #d6601d;"><?php
							printf(
								/* translators: %s: required Premium version */
								esc_html__( 'Update AnWP Football Leagues Premium to %s or higher first.', 'anwp-football-leagues' ),
								'0.18.0'
							);
						?></em></p>
					<?php endif; ?>
					<p class="anwp-notice-clear-both"></p>
				</div>
				<?php
			}
		}

		/*
		|--------------------------------------------------------------------
		| Standings migration required
		|--------------------------------------------------------------------
		*/
		if ( ! get_option( 'anwpfl_standings_migrated' ) && 'anwpfl-toolbox' !== $active_page ) {

			$standing_counts = wp_count_posts( 'anwp_standing' );
			$total_standings = (int) ( $standing_counts->publish ?? 0 ) + (int) ( $standing_counts->draft ?? 0 ) + (int) ( $standing_counts->pending ?? 0 ) + (int) ( $standing_counts->private ?? 0 );

			if ( 0 === $total_standings ) {
				update_option( 'anwpfl_standings_migrated', 1, true );
			} else {
				?>
				<div class="notice anwp-fl-cmb2-notice">
					<img src="<?php echo esc_url( self::url( 'admin/img/anwp-fl-icon.png' ) ); ?>" alt="fl icon">
					<h3>Standings Data Migration Required</h3>
					<p>Standing editing is disabled until the standings data migration is complete. Open the Database Updater tool to run the migration.</p>
					<p>
						<?php if ( $premium_too_old ) : ?>
							<button type="button" class="button" disabled><?php echo esc_html__( 'Database Updater', 'anwp-football-leagues' ); ?></button>
						<?php else : ?>
							<a href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox' ) ); ?>" class="button button-primary"><?php echo esc_html__( 'Database Updater', 'anwp-football-leagues' ); ?></a>
						<?php endif; ?>
					</p>
					<?php if ( $premium_too_old ) : ?>
						<p><em style="color: #d6601d;"><?php
							printf(
								/* translators: %s: required Premium version */
								esc_html__( 'Update AnWP Football Leagues Premium to %s or higher first.', 'anwp-football-leagues' ),
								'0.18.0'
							);
						?></em></p>
					<?php endif; ?>
					<p class="anwp-notice-clear-both"></p>
				</div>
				<?php
			}
		}

		/*
		|--------------------------------------------------------------------
		| Clubs migration required
		|--------------------------------------------------------------------
		*/
		if ( ! get_option( 'anwpfl_clubs_migrated' ) && 'anwpfl-toolbox' !== $active_page ) {

			// Auto-mark flag and skip notice when there are no clubs to migrate.
			// Covers fresh installs (no CPT posts at all) and environments where
			// the Toolbox updater's table-existence check fails due to MySQL LIKE
			// underscore handling.
			$club_counts = wp_count_posts( 'anwp_club' );
			$total_clubs = (int) ( $club_counts->publish ?? 0 ) + (int) ( $club_counts->draft ?? 0 ) + (int) ( $club_counts->pending ?? 0 ) + (int) ( $club_counts->private ?? 0 );

			if ( 0 === $total_clubs ) {
				update_option( 'anwpfl_clubs_migrated', 1, true );

				if ( ! get_option( 'anwpfl_squad_migrated' ) ) {
					update_option( 'anwpfl_squad_migrated', 1, true );
				}
			} else {
				?>
				<div class="notice anwp-fl-cmb2-notice">
					<img src="<?php echo esc_url( self::url( 'admin/img/anwp-fl-icon.png' ) ); ?>" alt="fl icon">
					<h3>Clubs Data Migration Required</h3>
					<p>Club editing is disabled until the clubs data migration is complete. Open the Database Updater tool to run the migration.</p>
					<p>
						<?php if ( $premium_too_old ) : ?>
							<button type="button" class="button" disabled><?php echo esc_html__( 'Database Updater', 'anwp-football-leagues' ); ?></button>
						<?php else : ?>
							<a href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox' ) ); ?>" class="button button-primary"><?php echo esc_html__( 'Database Updater', 'anwp-football-leagues' ); ?></a>
						<?php endif; ?>
					</p>
					<?php if ( $premium_too_old ) : ?>
						<p><em style="color: #d6601d;"><?php
							printf(
								/* translators: %s: required Premium version */
								esc_html__( 'Update AnWP Football Leagues Premium to %s or higher first.', 'anwp-football-leagues' ),
								'0.18.0'
							);
						?></em></p>
					<?php endif; ?>
					<p class="anwp-notice-clear-both"></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Whether the active Premium plugin is below the schema-compatible threshold for Core 0.18.0+.
	 *
	 * Returns false when Premium is not installed (or its main file hasn't loaded), so core-only
	 * sites and core+inactive-premium installs are not gated.
	 *
	 * @return bool
	 */
	public static function is_premium_too_old() {

		if ( ! defined( 'ANWP_FL_PREMIUM_VERSION' ) ) {
			return false;
		}

		return version_compare( ANWP_FL_PREMIUM_VERSION, '0.17.99', '<' );
	}

	/**
	 * Renders admin notice when Premium plugin is older than the schema-compatible threshold.
	 *
	 * Trips on the 0.18.0 schema cutover when Core is updated before Premium. Notice only - Premium
	 * still loads (its own guard may bail with its own notice on a future release). The Core-side
	 * notice is the early signal in the common Core-first update order.
	 */
	public function notice_premium_too_old() {

		if ( ! self::is_premium_too_old() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'AnWP Football Leagues', 'anwp-football-leagues' ); ?>:</strong>
				<?php
				printf(
					/* translators: 1: required Premium version, 2: currently installed Premium version */
					esc_html__( 'recommends updating AnWP Football Leagues Premium to %1$s or higher (currently %2$s) to match the Core schema. Premium features may behave unexpectedly until updated.', 'anwp-football-leagues' ),
					'<code>0.18.0</code>',
					'<code>' . esc_html( ANWP_FL_PREMIUM_VERSION ) . '</code>'
				);
				?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( self_admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Open Plugins page', 'anwp-football-leagues' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render an inline "data migration required" panel for entity edit screens
	 * where the FL metabox UI must be hidden until the data migration completes.
	 * Replaces the editor render so users don't see an editable form whose saves silently no-op.
	 *
	 * @param string $heading Translated heading text (e.g. "Clubs Data Migration Required").
	 */
	public static function print_migration_required_panel( $heading ) {

		$premium_too_old = self::is_premium_too_old();
		?>
		<div class="notice notice-warning inline" style="margin: 12px 0; padding: 12px;">
			<h3 style="margin-top: 0;"><?php echo esc_html( $heading ); ?></h3>
			<p><?php esc_html_e( 'Editing is disabled until the data migration is complete. Open the Database Updater tool to run the migration.', 'anwp-football-leagues' ); ?></p>
			<p>
				<?php if ( $premium_too_old ) : ?>
					<button type="button" class="button" disabled><?php esc_html_e( 'Database Updater', 'anwp-football-leagues' ); ?></button>
				<?php else : ?>
					<a class="button button-primary" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox' ) ); ?>">
						<?php esc_html_e( 'Database Updater', 'anwp-football-leagues' ); ?>
					</a>
				<?php endif; ?>
			</p>
			<?php if ( $premium_too_old ) : ?>
				<p><em style="color: #d6601d;"><?php
					printf(
						/* translators: %s: required Premium version */
						esc_html__( 'Update AnWP Football Leagues Premium to %s or higher first.', 'anwp-football-leagues' ),
						'0.18.0'
					);
				?></em></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Return localized menu prefix.
	 *
	 * @return string
	 * @since 0.1.0
	 */
	public function get_l10n_menu_prefix() {
		return sanitize_title( _x( 'Football Leagues', 'admin menu title', 'anwp-football-leagues' ) );
	}

	/**
	 * Return localized settings menu prefix.
	 *
	 * @return string
	 * @since 0.10.14
	 */
	public function get_l10n_menu_settings_prefix() {
		return sanitize_title( _x( 'Settings & Tools', 'admin menu title', 'anwp-football-leagues' ) );
	}

	/**
	 * Get POST season.
	 *
	 * @return int
	 * @since 0.10.14
	 */
	public function get_post_season() {

		static $season_id = null;

		if ( null === $season_id ) {

			// Get Season ID
			$season_id = anwp_fl()->get_active_season();

			// phpcs:ignore WordPress.Security.NonceVerification
			if ( ! empty( $_GET['season'] ) ) {

				// phpcs:ignore WordPress.Security.NonceVerification
				$maybe_season_id = anwp_fl()->season->get_season_id_by_slug( sanitize_key( $_GET['season'] ) );

				if ( absint( $maybe_season_id ) ) {
					return absint( $maybe_season_id );
				}
			}
		}

		return absint( $season_id );
	}

	/**
	 * Converts a string to a bool.
	 * From WOO
	 *
	 * @param mixed $string_value String to convert.
	 *
	 * @since 0.7.4
	 * @return bool
	 */
	public static function string_to_bool( $string_value ): bool {
		return is_bool( $string_value ) ? $string_value : ( 1 === $string_value || 'yes' === $string_value || 'true' === $string_value || '1' === $string_value );
	}

	/**
	 * Render modal wrappers.
	 *
	 * @return void
	 * @since  0.15.0
	 */
	public function render_modal_wrappers() {
		if ( in_array( AnWPFL_Options::get_value( 'preferred_video_player' ), [ 'youtube', 'mixed' ], true ) ) {
			ob_start();
			?>
			<div id="anwp-fl-v-modal" class="anwp-fl-v-modal" aria-hidden="true">
				<div class="anwp-fl-v-modal__overlay" tabindex="-1" data-micromodal-close>
					<div class="anwp-fl-v-modal__container" role="dialog" aria-modal="true">
						<button class="anwp-fl-v-modal__close" aria-label="Close modal" type="button" data-micromodal-close></button>
						<div id="anwp-fl-v-spinner"></div>
						<figure id="anwp-fl-v-modal__container"></figure>
					</div>
				</div>
			</div>
			<?php
			echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Check the possibility to delete Competition
	 *
	 * @param WP_Post|false|null $delete Whether to go forward with deletion.
	 * @param WP_Post            $post   Post object.
	 *
	 * @since 0.16.4
	 */
	public function maybe_prevent_delete_competition( $delete, $post ) {

		if ( ! empty( $post->post_type ) && 'anwp_competition' === $post->post_type ) {

			$games = anwp_fl()->competition->tmpl_get_competition_matches_extended(
				[
					'show_secondary' => 1,
					'competition_id' => $post->ID,
				],
				'ids'
			);

			if ( count( $games ) ) {
				set_transient( 'anwp-admin-pre-remove-warning', esc_html__( 'It is prohibited to delete a Competition with Games. First, remove the attached Games.', 'anwp-football-leagues' ), 10 );
				return $post->ID;
			}
		}

		return $delete;
	}

	/**
	 * Check the possibility to delete Season or League
	 *
	 * @param int    $term_id     Term ID.
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @since 0.16.4
	 */
	public function maybe_prevent_delete_term( int $term_id, string $taxonomy ) {

		if ( in_array( $taxonomy, [ 'anwp_season', 'anwp_league' ], true ) ) {

			$posts = get_posts(
				[
					'post_type'      => 'anwp_competition',
					'posts_per_page' => - 1,
					'tax_query'      => [
						[
							'taxonomy' => $taxonomy,
							'field'    => 'id',
							'terms'    => $term_id,
						],
					],
				]
			);

			if ( count( $posts ) ) {
				set_transient( 'anwp-admin-pre-remove-warning', esc_html__( 'It is prohibited to delete a League or a Season that has Competitions linked to it.', 'anwp-football-leagues' ), 10 );
				wp_die();
			}
		}
	}

	/**
	 * Display pre-remove warning message
	 *
	 * @since 0.16.4
	 */
	public function display_admin_pre_remove_notice() {

		if ( get_transient( 'anwp-admin-pre-remove-warning' ) ) :
			?>
			<div class="notice notice-error is-dismissible notice-alt anwp-visible-after-header">
				<p><?php echo esc_html( get_transient( 'anwp-admin-pre-remove-warning' ) ); ?></p>
			</div>
			<?php
			delete_transient( 'anwp-admin-pre-remove-warning' );
		endif;
	}

	public static function is_editing_block_on_backend(): bool {
		return defined( 'REST_REQUEST' ) && true === REST_REQUEST && 'edit' === filter_input( INPUT_GET, 'context', FILTER_SANITIZE_SPECIAL_CHARS );
	}
}
