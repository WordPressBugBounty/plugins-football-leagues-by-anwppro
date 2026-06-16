<?php
/**
 * Template Status Class
 * AnWP Football Leagues :: Template Status.
 *
 * Scans registered template paths for overrides (themes + third-party plugins),
 * compares @version tags, and checks for migration-incompatible patterns.
 *
 * @since   0.18.0
 * @package AnWP_Football_Leagues
 */

class AnWPFL_Template_Status {

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
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Scan all registered template paths for overrides.
	 *
	 * For each plugin template, check whether any registered override path
	 * (child theme, parent theme, third-party plugin) contains a matching file.
	 * When found, compare @version tags and run migration-pattern checks.
	 *
	 * @since 0.18.0
	 *
	 * @return array Map of template relative path => override metadata. See PLAN.md "Data Structure".
	 */
	public function get_template_overrides(): array {
		$templates      = $this->get_plugin_templates();
		$override_paths = $this->get_override_paths();
		$result         = [];

		foreach ( $templates as $relative => $plugin_data ) {
			$override = $this->find_override( $relative, $override_paths );

			if ( null === $override ) {
				$result[ $relative ] = [
					'plugin_version'   => $plugin_data['version'],
					'override_version' => '',
					'override_path'    => '',
					'source'           => '',
					'source_priority'  => 0,
					'outdated'         => false,
					'version_missing'  => false,
					'warnings'         => [],
				];
				continue;
			}

			$override_version = $this->get_template_version( $override['path'] );
			$version_missing  = '' === $override_version || '' === $plugin_data['version'];
			$outdated         = ! $version_missing && version_compare( $override_version, $plugin_data['version'], '<' );

			$result[ $relative ] = [
				'plugin_version'   => $plugin_data['version'],
				'override_version' => $override_version,
				'override_path'    => $override['path'],
				'source'           => $override['source'],
				'source_priority'  => $override['priority'],
				'outdated'         => $outdated,
				'version_missing'  => $version_missing,
				'warnings'         => $this->check_migration_patterns( $override['path'], $relative ),
			];
		}

		return $result;
	}

	/**
	 * Build the map of plugin templates (core + premium).
	 *
	 * Premium templates overwrite core entries when the relative path matches —
	 * the runtime loader behaves the same way (premium has priority 50 vs core 100).
	 *
	 * @return array Relative path => [ source, path, version ].
	 */
	private function get_plugin_templates(): array {
		$templates = [];

		$core_dir = trailingslashit( $this->plugin->path ) . 'templates/';

		foreach ( $this->scan_directory( $core_dir ) as $file ) {
			$relative               = substr( $file, strlen( $core_dir ) );
			$templates[ $relative ] = [
				'source'  => 'core',
				'path'    => $file,
				'version' => $this->get_template_version( $file ),
			];
		}

		if ( function_exists( 'anwp_fl_pro' ) ) {
			$pro_dir = trailingslashit( AnWP_Football_Leagues_Premium::dir( 'templates' ) );

			foreach ( $this->scan_directory( $pro_dir ) as $file ) {
				$relative               = substr( $file, strlen( $pro_dir ) );
				$templates[ $relative ] = [
					'source'  => 'premium',
					'path'    => $file,
					'version' => $this->get_template_version( $file ),
				];
			}
		}

		return $templates;
	}

	/**
	 * Build the list of override directories — same paths the runtime loader checks.
	 *
	 * Runs the `anwpfl_template_paths` filter so third-party plugins are included.
	 * Normalizes trailing slashes (the loader does this too) and removes the plugin's
	 * own template dirs by path comparison (priority numbers aren't reliable — a
	 * third-party plugin at priority 50 replacing premium is a legitimate override).
	 *
	 * @return array Priority => directory (trailingslashit).
	 */
	private function get_override_paths(): array {
		$file_paths = [];

		if ( get_stylesheet_directory() !== get_template_directory() ) {
			$file_paths[1] = get_stylesheet_directory() . '/anwp-football-leagues/';
		}

		$file_paths[10] = get_template_directory() . '/anwp-football-leagues/';

		$file_paths = apply_filters( 'anwpfl_template_paths', $file_paths );

		ksort( $file_paths );

		$file_paths = array_map( 'trailingslashit', $file_paths );

		$plugin_dirs = [
			trailingslashit( $this->plugin->path ) . 'templates/',
		];

		if ( function_exists( 'anwp_fl_pro' ) ) {
			$plugin_dirs[] = trailingslashit( AnWP_Football_Leagues_Premium::dir( 'templates' ) );
		}

		foreach ( $file_paths as $priority => $dir ) {
			if ( in_array( $dir, $plugin_dirs, true ) ) {
				unset( $file_paths[ $priority ] );
			}
		}

		return $file_paths;
	}

	/**
	 * Check a template against override paths in priority order.
	 *
	 * @param string $relative       Template relative path (e.g. "club/club-header.php").
	 * @param array  $override_paths Priority => directory map from get_override_paths().
	 *
	 * @return array|null Override metadata (path, priority, source) or null when not overridden.
	 */
	private function find_override( string $relative, array $override_paths ): ?array {
		foreach ( $override_paths as $priority => $dir ) {
			if ( file_exists( $dir . $relative ) ) {
				return [
					'path'     => $dir . $relative,
					'priority' => (int) $priority,
					'source'   => $this->identify_source( $dir ),
				];
			}
		}

		return null;
	}

	/**
	 * Label an override source by comparing the directory against known theme dirs.
	 *
	 * @param string $dir Override directory (trailingslashit).
	 *
	 * @return string 'child', 'parent', or the plugin directory name.
	 */
	private function identify_source( string $dir ): string {
		$dir = trailingslashit( $dir );

		$child_dir  = trailingslashit( get_stylesheet_directory() ) . 'anwp-football-leagues/';
		$parent_dir = trailingslashit( get_template_directory() ) . 'anwp-football-leagues/';

		if ( $dir === $child_dir && get_stylesheet_directory() !== get_template_directory() ) {
			return 'child';
		}

		if ( $dir === $parent_dir ) {
			return 'parent';
		}

		return basename( dirname( $dir ) );
	}

	/**
	 * Extract the @version value from a template docblock.
	 *
	 * Reads only the first 30 lines — docblocks are always at the top.
	 *
	 * @param string $file_path Absolute file path.
	 *
	 * @return string Version string, or '' when no @version tag is found.
	 */
	private function get_template_version( string $file_path ): string {
		$lines = file( $file_path );

		if ( false === $lines ) {
			return '';
		}

		$content = implode( '', array_slice( $lines, 0, 30 ) );

		if ( preg_match( '/@version\s+([\d.]+)/', $content, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Run migration-pattern rules against an override file.
	 *
	 * Rule messages are built at runtime so they go through i18n at the right stage.
	 *
	 * @param string $file_path     Absolute override file path.
	 * @param string $template_name Relative template name.
	 *
	 * @return array List of warnings: [ pattern, severity, message, line ].
	 */
	private function check_migration_patterns( string $file_path, string $template_name ): array {
		$lines = file( $file_path );

		if ( false === $lines ) {
			return [];
		}

		$warnings = [];
		$rules    = $this->get_pattern_rules();

		foreach ( $rules as $rule ) {
			$match_line = $this->match_rule( $lines, $rule );

			if ( null === $match_line ) {
				continue;
			}

			$warnings[] = [
				'pattern'  => $rule['pattern'],
				'severity' => $rule['severity'],
				'message'  => $rule['message'],
				'line'     => $match_line,
			];
		}

		/**
		 * Filter warnings for a specific template override.
		 *
		 * @since 0.18.0
		 *
		 * @param array  $warnings      List of warning rows.
		 * @param string $template_name Template relative path.
		 * @param string $file_path     Absolute override file path.
		 */
		return apply_filters( 'anwpfl/template-status/warnings', $warnings, $template_name, $file_path );
	}

	/**
	 * Match a single rule against file lines and return the first matching line number.
	 *
	 * Two rule shapes:
	 * - `search`: single string that must appear on a line.
	 * - `search_all`: list of strings that must ALL appear on the same line (co-occurrence).
	 *
	 * @param array $lines File lines (1-indexed for human display).
	 * @param array $rule  Rule definition.
	 *
	 * @return int|null First matching line number, or null.
	 */
	private function match_rule( array $lines, array $rule ): ?int {
		foreach ( $lines as $index => $line ) {
			if ( ! empty( $rule['search_all'] ) ) {
				$all_match = true;

				foreach ( $rule['search_all'] as $needle ) {
					if ( false === strpos( $line, $needle ) ) {
						$all_match = false;
						break;
					}
				}

				if ( $all_match ) {
					return $index + 1;
				}

				continue;
			}

			if ( ! empty( $rule['search'] ) && false !== strpos( $line, $rule['search'] ) ) {
				return $index + 1;
			}
		}

		return null;
	}

	/**
	 * Pattern rules registry.
	 *
	 * Each migration adds its own rule set. Premium adds rules via the
	 * `anwpfl/template-status/pattern_rules` filter.
	 *
	 * @return array List of rule definitions.
	 */
	private function get_pattern_rules(): array {
		$rules = [
			// Club migration (v0.18.0).
			[
				'pattern'  => 'club_wp_post_magic_literal',
				'search'   => '$club->_anwpfl_',
				'severity' => 'warning',
				'message'  => __( 'Reads club data via WP_Post postmeta magic property. Update to anwp_fl()->club->get_row() array access.', 'anwp-football-leagues' ),
			],
			[
				'pattern'  => 'club_wp_post_magic_dynamic',
				'search'   => '$club->{\'_anwpfl_',
				'severity' => 'warning',
				'message'  => __( 'Reads club data via WP_Post postmeta magic property (dynamic key). Update to anwp_fl()->club->get_row() array access.', 'anwp-football-leagues' ),
			],
			// Standing migration (v0.18.0).
			[
				'pattern'  => 'standing_wp_post_magic_literal',
				'search'   => '$standing->_anwpfl_',
				'severity' => 'warning',
				'message'  => __( 'Reads standing data via WP_Post postmeta magic property. Update to anwp_fl()->standing->get_row() array access.', 'anwp-football-leagues' ),
			],
			[
				'pattern'  => 'standing_wp_post_magic_dynamic',
				'search'   => '$standing->{\'_anwpfl_',
				'severity' => 'warning',
				'message'  => __( 'Reads standing data via WP_Post postmeta magic property (dynamic key). Update to anwp_fl()->standing->get_row() array access.', 'anwp-football-leagues' ),
			],
			// Competition migration (v0.18.0).
			[
				'pattern'  => 'competition_wp_post_magic_literal',
				'search'   => '$competition->_anwpfl_',
				'severity' => 'warning',
				'message'  => __( 'Reads competition data via WP_Post postmeta magic property. Update to anwp_fl()->competition->get_competition_data_full() array access.', 'anwp-football-leagues' ),
			],
			[
				'pattern'  => 'competition_wp_post_magic_dynamic',
				'search'   => '$competition->{\'_anwpfl_',
				'severity' => 'warning',
				'message'  => __( 'Reads competition data via WP_Post postmeta magic property (dynamic key). Update to anwp_fl()->competition->get_competition_data_full() array access.', 'anwp-football-leagues' ),
			],
			// Universal get_post_meta rule — fires on any migrated entity's _anwpfl_ key.
			// Named generic on purpose: the line doesn't tell us which entity; the migration
			// guide enumerates which accessor to use per meta key.
			[
				'pattern'    => 'postmeta_anwpfl_migrated_key',
				'search_all' => [ 'get_post_meta', '_anwpfl_' ],
				'severity'   => 'warning',
				'message'    => __( 'Uses get_post_meta() for plugin data. Entity data (club, standing, competition) is no longer stored in postmeta after v0.18.0. See the migration guide for the correct anwp_fl()->{entity}->get_row() accessor per meta key.', 'anwp-football-leagues' ),
			],
		];

		/**
		 * Filter migration-pattern rules.
		 *
		 * @since 0.18.0
		 *
		 * @param array $rules List of rule definitions (pattern, search|search_all, severity, message).
		 */
		return apply_filters( 'anwpfl/template-status/pattern_rules', $rules );
	}

	/**
	 * Recursive scan for .php templates under a directory.
	 *
	 * Skips `index.php` placeholder guards — they're not real templates.
	 *
	 * @param string $dir Absolute directory path.
	 *
	 * @return array List of absolute file paths.
	 */
	private function scan_directory( string $dir ): array {
		if ( ! is_dir( $dir ) ) {
			return [];
		}

		$files    = [];
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ) );

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}

			if ( 'php' !== strtolower( $file->getExtension() ) ) {
				continue;
			}

			if ( 'index.php' === $file->getFilename() ) {
				continue;
			}

			$files[] = $file->getPathname();
		}

		return $files;
	}
}
