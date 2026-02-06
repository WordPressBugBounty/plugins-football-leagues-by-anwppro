<?php
/**
 * Support page for AnWP Football Leagues
 *
 * @link       https://anwp.pro
 * @since      0.5.5
 *
 * @package    AnWP_Football_Leagues
 * @subpackage AnWP_Football_Leagues/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'anwp-football-leagues' ) );
}

global $wp_version, $wpdb;

$database_tables = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT table_name AS 'name'
				FROM information_schema.TABLES
				WHERE table_schema = %s
				ORDER BY name ASC;",
		DB_NAME
	)
);

try {
	$matches = get_posts(
		[
			'numberposts' => - 1,
			'post_type'   => 'anwp_match',
			'post_status' => 'publish',
			'fields'      => 'ids',
		]
	);

	$matches_qty = is_array( $matches ) ? count( $matches ) : 0;

	$stats_qty = $wpdb->get_var(
		"
				SELECT COUNT(*)
				FROM {$wpdb->prefix}anwpfl_matches
				"
	);
} catch ( RuntimeException $e ) {
	$matches_qty = 0;
	$stats_qty   = 0;
}

$active_plugins      = get_option( 'active_plugins' );
$active_plugins_list = [];

if ( is_array( $active_plugins ) ) {
	foreach ( $active_plugins as $plugin_path ) {
		$parts                 = explode( '/', $plugin_path );
		$active_plugins_list[] = $parts[0];
	}
}

$db_tables_list = [];

if ( ! empty( $database_tables ) && is_array( $database_tables ) ) {
	$db_tables_list = wp_list_pluck( $database_tables, 'name' );
}
?>

<div class="wrap anwp-b-wrap anwp-w-max-1000">
	<!-- Header -->
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Plugin Support', 'anwp-football-leagues' ); ?></h1>
	<hr class="anwp-my-3">

	<!-- Support Info Box -->
	<div class="anwp-mb-4 anwp-p-4 anwp-bg-blue-100 anwp-border anwp-border-blue-600 anwp-rounded">
		<div class="anwp-d-flex anwp-items-start anwp-gap-3">
			<span class="dashicons dashicons-sos anwp-dashicons-24 anwp-text-blue-600 anwp-shrink-0" style="margin-top: 2px;"></span>
			<div>
				<h3 class="anwp-mt-0 anwp-mb-2 anwp-text-blue-800 anwp-font-semibold">
					<?php esc_html_e( 'Need Help?', 'anwp-football-leagues' ); ?>
				</h3>
				<p class="anwp-mt-0 anwp-mb-3 anwp-text-sm anwp-text-blue-700">
					<?php echo esc_html_x( 'If you find a bug, need help, or would like to request a feature, please visit our support forum.', 'support page', 'anwp-football-leagues' ); ?>
				</p>
				<a href="https://anwppro.userecho.com/communities/1-football-leagues"
				   target="_blank"
				   class="button button-primary anwp-d-inline-flex anwp-items-center anwp-justify-center">
					<span class="dashicons dashicons-external anwp-dashicons-18"></span>
					<span class="anwp-ml-2"><?php echo esc_html_x( 'Visit Support Forum', 'support page', 'anwp-football-leagues' ); ?></span>
				</a>
			</div>
		</div>
	</div>

	<!-- Stats Grid -->
	<div class="anwp-mb-4 anwp-p-4 anwp-bg-white anwp-border anwp-border-gray-400 anwp-rounded">
		<h2 class="anwp-mt-0 anwp-mb-3 anwp-text-lg anwp-font-semibold">
			<?php echo esc_html_x( 'Quick Overview', 'support page', 'anwp-football-leagues' ); ?>
		</h2>

		<div class="anwp-d-grid anwp-grid-cols-2 anwp-grid-cols-md-3 anwp-grid-cols-lg-6 anwp-gap-3">
			<!-- Plugin Version -->
			<div class="anwp-p-3 anwp-bg-gray-50 anwp-rounded anwp-text-center">
				<span class="dashicons dashicons-admin-plugins anwp-dashicons-24 anwp-text-gray-600 anwp-mb-1"></span>
				<div class="anwp-text-base anwp-font-bold anwp-text-gray-900"><?php echo esc_html( anwp_football_leagues()->version ); ?></div>
				<div class="anwp-text-xs anwp-text-gray-600"><?php esc_html_e( 'Plugin', 'anwp-football-leagues' ); ?></div>
			</div>

			<!-- WordPress Version -->
			<div class="anwp-p-3 anwp-bg-gray-50 anwp-rounded anwp-text-center">
				<span class="dashicons dashicons-wordpress anwp-dashicons-24 anwp-text-gray-600 anwp-mb-1"></span>
				<div class="anwp-text-base anwp-font-bold anwp-text-gray-900"><?php echo esc_html( $wp_version ); ?></div>
				<div class="anwp-text-xs anwp-text-gray-600"><?php esc_html_e( 'WordPress', 'anwp-football-leagues' ); ?></div>
			</div>

			<!-- PHP Version -->
			<div class="anwp-p-3 anwp-bg-gray-50 anwp-rounded anwp-text-center">
				<span class="dashicons dashicons-editor-code anwp-dashicons-24 anwp-text-gray-600 anwp-mb-1"></span>
				<div class="anwp-text-base anwp-font-bold anwp-text-gray-900"><?php echo esc_html( phpversion() ); ?></div>
				<div class="anwp-text-xs anwp-text-gray-600"><?php esc_html_e( 'PHP', 'anwp-football-leagues' ); ?></div>
			</div>

			<!-- DB Version -->
			<div class="anwp-p-3 anwp-bg-gray-50 anwp-rounded anwp-text-center">
				<span class="dashicons dashicons-database anwp-dashicons-24 anwp-text-gray-600 anwp-mb-1"></span>
				<div class="anwp-text-base anwp-font-bold anwp-text-gray-900"><?php echo esc_html( get_option( 'anwpfl_db_version' ) ); ?></div>
				<div class="anwp-text-xs anwp-text-gray-600"><?php esc_html_e( 'DB Version', 'anwp-football-leagues' ); ?></div>
			</div>

			<!-- Matches -->
			<div class="anwp-p-3 anwp-bg-gray-50 anwp-rounded anwp-text-center">
				<span class="dashicons dashicons-megaphone anwp-dashicons-24 anwp-text-gray-600 anwp-mb-1"></span>
				<div class="anwp-text-base anwp-font-bold anwp-text-gray-900"><?php echo (int) $matches_qty; ?></div>
				<div class="anwp-text-xs anwp-text-gray-600"><?php esc_html_e( 'Matches', 'anwp-football-leagues' ); ?></div>
			</div>

			<!-- Stats Records -->
			<div class="anwp-p-3 anwp-bg-gray-50 anwp-rounded anwp-text-center">
				<span class="dashicons dashicons-chart-bar anwp-dashicons-24 anwp-text-gray-600 anwp-mb-1"></span>
				<div class="anwp-text-base anwp-font-bold anwp-text-gray-900"><?php echo (int) $stats_qty; ?></div>
				<div class="anwp-text-xs anwp-text-gray-600"><?php esc_html_e( 'Stats Records', 'anwp-football-leagues' ); ?></div>
			</div>
		</div>
	</div>

	<!-- Environment Details -->
	<div class="anwp-mb-4 anwp-p-4 anwp-bg-white anwp-border anwp-border-gray-400 anwp-rounded">
		<h2 class="anwp-mt-0 anwp-mb-3 anwp-text-lg anwp-font-semibold">
			<?php echo esc_html_x( 'Environment Details', 'support page', 'anwp-football-leagues' ); ?>
		</h2>

		<table class="wp-list-table widefat striped">
			<tbody>
				<tr>
					<td class="anwp-px-3 anwp-font-semibold" style="width: 200px;">
						<?php esc_html_e( 'Server Timezone', 'anwp-football-leagues' ); ?>
					</td>
					<td class="anwp-px-3"><?php echo esc_html( date_default_timezone_get() ); ?></td>
				</tr>
				<tr>
					<td class="anwp-px-3 anwp-font-semibold">
						<?php esc_html_e( 'WordPress Timezone', 'anwp-football-leagues' ); ?>
					</td>
					<td class="anwp-px-3"><?php echo esc_html( get_option( 'timezone_string' ) ?: __( 'Not set', 'anwp-football-leagues' ) ); ?></td>
				</tr>
				<tr>
					<td class="anwp-px-3 anwp-font-semibold">
						<?php esc_html_e( 'Current Date', 'anwp-football-leagues' ); ?>
					</td>
					<td class="anwp-px-3"><?php echo esc_html( date_i18n( 'Y-m-d H:i:s' ) ); ?></td>
				</tr>
				<tr>
					<td class="anwp-px-3 anwp-font-semibold">
						<?php esc_html_e( 'Site Locale', 'anwp-football-leagues' ); ?>
					</td>
					<td class="anwp-px-3"><?php echo esc_html( get_locale() ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Active Plugins -->
	<div class="anwp-mb-4 anwp-p-4 anwp-bg-white anwp-border anwp-border-gray-400 anwp-rounded">
		<h2 class="anwp-mt-0 anwp-mb-3 anwp-text-lg anwp-font-semibold anwp-d-flex anwp-items-center">
			<span class="dashicons dashicons-admin-plugins anwp-dashicons-18 anwp-text-gray-600 anwp-mr-2"></span>
			<?php
			printf(
				/* translators: %d: number of active plugins */
				esc_html__( 'Active Plugins (%d)', 'anwp-football-leagues' ),
				count( $active_plugins_list )
			);
			?>
		</h2>
		<?php if ( ! empty( $active_plugins_list ) ) : ?>
			<div class="anwp-d-flex anwp-flex-wrap anwp-gap-2">
				<?php foreach ( $active_plugins_list as $plugin_name ) : ?>
					<span class="anwp-px-2 anwp-py-1 anwp-bg-gray-100 anwp-text-gray-700 anwp-text-sm anwp-rounded">
						<?php echo esc_html( $plugin_name ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="anwp-text-gray-500 anwp-text-sm anwp-m-0"><?php esc_html_e( 'No active plugins found.', 'anwp-football-leagues' ); ?></p>
		<?php endif; ?>
	</div>

	<!-- Database Tables -->
	<div class="anwp-mb-4 anwp-p-4 anwp-bg-white anwp-border anwp-border-gray-400 anwp-rounded">
		<h2 class="anwp-mt-0 anwp-mb-3 anwp-text-lg anwp-font-semibold anwp-d-flex anwp-items-center">
			<span class="dashicons dashicons-database anwp-dashicons-18 anwp-text-gray-600 anwp-mr-2"></span>
			<?php
			printf(
				/* translators: %d: number of database tables */
				esc_html__( 'Database Tables (%d)', 'anwp-football-leagues' ),
				count( $db_tables_list )
			);
			?>
		</h2>
		<?php if ( ! empty( $db_tables_list ) ) : ?>
			<div class="anwp-d-flex anwp-flex-wrap anwp-gap-2">
				<?php foreach ( $db_tables_list as $table_name ) : ?>
					<span class="anwp-px-2 anwp-py-1 anwp-text-sm anwp-rounded <?php echo strpos( $table_name, 'anwpfl' ) !== false ? 'anwp-bg-blue-100 anwp-text-blue-700' : 'anwp-bg-gray-100 anwp-text-gray-700'; ?>">
						<?php echo esc_html( $table_name ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="anwp-text-gray-500 anwp-text-sm anwp-m-0"><?php esc_html_e( 'No database tables found.', 'anwp-football-leagues' ); ?></p>
		<?php endif; ?>
	</div>

	<!-- Copy System Info -->
	<div class="anwp-p-4 anwp-bg-gray-50 anwp-border anwp-border-gray-400 anwp-rounded">
		<h3 class="anwp-mt-0 anwp-mb-2 anwp-text-base anwp-font-semibold anwp-d-flex anwp-items-center">
			<span class="dashicons dashicons-clipboard anwp-dashicons-18 anwp-text-gray-600 anwp-mr-2"></span>
			<?php esc_html_e( 'Copy System Info', 'anwp-football-leagues' ); ?>
		</h3>
		<p class="anwp-text-sm anwp-text-gray-600 anwp-mt-0 anwp-mb-3">
			<?php esc_html_e( 'Copy this information when reporting issues to help us diagnose problems faster.', 'anwp-football-leagues' ); ?>
		</p>
		<textarea id="anwp-system-info" readonly class="large-text code" rows="8" style="font-family: monospace; font-size: 12px;"><?php
			echo "=== AnWP Football Leagues System Info ===\n";
			echo "Plugin Version: " . esc_html( anwp_football_leagues()->version ) . "\n";
			echo "WordPress Version: " . esc_html( $wp_version ) . "\n";
			echo "PHP Version: " . esc_html( phpversion() ) . "\n";
			echo "DB Version: " . esc_html( get_option( 'anwpfl_db_version' ) ) . "\n";
			echo "Matches: " . (int) $matches_qty . "\n";
			echo "Stats Records: " . (int) $stats_qty . "\n";
			echo "Server Timezone: " . esc_html( date_default_timezone_get() ) . "\n";
			echo "WP Timezone: " . esc_html( get_option( 'timezone_string' ) ?: 'Not set' ) . "\n";
			echo "Site Locale: " . esc_html( get_locale() ) . "\n";
			echo "Active Plugins: " . esc_html( implode( ', ', $active_plugins_list ) ) . "\n";
			?></textarea>
		<button type="button" class="button anwp-mt-2 anwp-d-inline-flex anwp-items-center anwp-justify-center" onclick="document.getElementById('anwp-system-info').select(); document.execCommand('copy'); this.querySelector('.anwp-copy-text').textContent = '<?php esc_attr_e( 'Copied!', 'anwp-football-leagues' ); ?>'; setTimeout(() => this.querySelector('.anwp-copy-text').textContent = '<?php esc_attr_e( 'Copy to Clipboard', 'anwp-football-leagues' ); ?>', 2000);">
			<span class="dashicons dashicons-admin-page anwp-dashicons-18"></span>
			<span class="anwp-ml-2 anwp-copy-text"><?php esc_html_e( 'Copy to Clipboard', 'anwp-football-leagues' ); ?></span>
		</button>
	</div>
</div>
