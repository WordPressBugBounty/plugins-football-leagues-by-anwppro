<?php
/**
 * Cache page for AnWP Football Leagues
 *
 * @link       https://anwp.pro
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
?>
<div class="mb-2 pb-1">
	<h1 class="anwp-font-normal mb-0">FL Cache Config</h1>
</div>
<div class="mb-3 d-flex align-items-center">
	<a class="text-decoration-none" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox' ) ); ?>">Database Updater</a>
	<small class="text-muted mx-2 d-inline-block">|</small>
	<a class="text-decoration-none" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox&tab=toolkit' ) ); ?>">Toolkit</a>
	<small class="text-muted mx-2 d-inline-block">|</small>
	<span class="text-muted">Caching</span>
</div>

<hr class="mb-3">
<h3 class="mt-3 mb-2">FL Cache Version - <?php echo esc_html( anwp_fl()->cache->version ? : '-none-' ); ?></h3>
<p class="font-italic anwp-text-xs mt-1">
	none - plugin caching is not activated<br>
	v1 - old cache version based on Transients and options table<br>
	v2 - new cache version based on Object Cache and improved logic
</p>

<?php if ( 'v2' === anwp_fl()->cache->version ) : ?>
	<hr class="mb-3">
	<h3 class="mt-3 mb-2">wp_cache_supports</h3>
	<table class="wp-list-table widefat striped table-view-list w-auto">
		<tr>
			<td class="px-3 anwp-font-semibold">flush_group</td>
			<td class="px-2">
				<span class="anwp-leading-1 text-white px-2 py-0 rounded anwp-bg-<?php echo wp_cache_supports( 'flush_group' ) ? 'green' : 'orange'; ?>-600"><?php echo wp_cache_supports( 'flush_group' ) ? 'YES' : 'NO'; ?></span>
			</td>
		</tr>
	</table>
	<?php
	global $wp_object_cache;

	$cached_vars = [];
	$root_vars   = [
		'FL-REFEREES-LIST-SIMPLE',
		'FL-REFEREES-LIST',
		'FL-STADIUMS-LIST',
		'FL-CLUBS-LIST',
		'FL-STANDINGS-LIST',
		'FL-COMPETITIONS-DATA',
		'FL-PRO-REFEREES-NAME-LIST',
		'FL-PRO-REFEREES-NAMES',
		'FL-STAFF-PHOTO-MAP',
		'FL-LEAGUE-OPTIONS',
		'FL-MATCHWEEK-OPTIONS',
	];

	foreach ( $root_vars as $fl_key ) {
		$cached_vars[] = [
			'size'  => number_format( round( strlen( maybe_serialize( anwp_fl()->cache->get( $fl_key ) ) ) / 1024 ) ) . ' KB',
			'count' => number_format( count( anwp_fl()->cache->get( $fl_key ) ) ),
			'key'   => preg_replace( '/^.*?(FL-)/', '$1', $fl_key ),
		];
	}

	if ( method_exists( $wp_object_cache, 'stats' ) ) :
		?>
		<hr class="mb-3">
		<h3 class="mt-3 mb-2">Object Cache Statistics</h3>
		<p><?php echo $wp_object_cache->stats(); //phpcs:ignore ?></p>
		<?php
	endif;

	if ( ! empty( $cached_vars ) ) :
		?>
		<hr class="mb-3">
		<h3 class="mt-3 mb-2">FL Cached Objects</h3>
		<table class="wp-list-table widefat striped table-view-list w-auto">
			<thead>
			<tr>
				<th>Caches Key</th>
				<th>Count</th>
				<th>Size</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $cached_vars as $cached_var ) : ?>
				<tr>
					<td class="px-3 anwp-font-semibold"><?php echo esc_html( $cached_var['key'] ); ?></td>
					<td class="px-3 anwp-text-right"><?php echo esc_html( $cached_var['count'] ); ?></td>
					<td class="px-3 anwp-text-right"><?php echo esc_html( $cached_var['size'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	endif;
endif;
?>

<hr class="mb-3">
<h3 class="mt-3 mb-2">DB Stats</h3>

<?php
global $wpdb;

$plugin_qty = [
	'games'        => number_format( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}anwpfl_matches" ) ?: 0 ),
	'players'      => number_format( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}anwpfl_player_data" ) ?: 0 ),
	'competitions' => number_format( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'anwp_competition'" ) ?: 0 ),
	'staff'        => number_format( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'anwp_staff'" ) ?: 0 ),
	'clubs'        => number_format( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'anwp_club'" ) ?: 0 ),
	'stadiums'     => number_format( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'anwp_stadium'" ) ?: 0 ),
	'referees'     => number_format( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'anwp_referee'" ) ?: 0 ),
	'standings'    => number_format( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'anwp_standing'" ) ?: 0 ),
	'leagues'      => number_format( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE taxonomy = 'anwp_league'" ) ?: 0 ),
	'seasons'      => number_format( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE taxonomy = 'anwp_season'" ) ?: 0 ),
];
?>
<table class="wp-list-table widefat striped table-view-list w-auto">
	<tr>
		<td class="px-3 anwp-font-semibold">Players</td>
		<td class="px-2 anwp-text-right"><?php echo esc_html( $plugin_qty['players'] ); ?></td>
	</tr>
	<tr>
		<td class="px-3 anwp-font-semibold">Games</td>
		<td class="px-2 anwp-text-right"><?php echo esc_html( $plugin_qty['games'] ); ?></td>
	</tr>
	<tr>
		<td class="px-3 anwp-font-semibold">Competitions</td>
		<td class="px-2 anwp-text-right"><?php echo esc_html( $plugin_qty['competitions'] ); ?></td>
	</tr>
	<tr>
		<td class="px-3 anwp-font-semibold">Clubs</td>
		<td class="px-2 anwp-text-right"><?php echo esc_html( $plugin_qty['clubs'] ); ?></td>
	</tr>
	<tr>
		<td class="px-3 anwp-font-semibold">Staff</td>
		<td class="px-2 anwp-text-right"><?php echo esc_html( $plugin_qty['staff'] ); ?></td>
	</tr>
	<tr>
		<td class="px-3 anwp-font-semibold">Stadiums</td>
		<td class="px-2 anwp-text-right"><?php echo esc_html( $plugin_qty['stadiums'] ); ?></td>
	</tr>
	<tr>
		<td class="px-3 anwp-font-semibold">Standings</td>
		<td class="px-2 anwp-text-right"><?php echo esc_html( $plugin_qty['standings'] ); ?></td>
	</tr>
	<tr>
		<td class="px-3 anwp-font-semibold">Seasons</td>
		<td class="px-2 anwp-text-right"><?php echo esc_html( $plugin_qty['seasons'] ); ?></td>
	</tr>
	<tr>
		<td class="px-3 anwp-font-semibold">Leagues</td>
		<td class="px-2 anwp-text-right"><?php echo esc_html( $plugin_qty['leagues'] ); ?></td>
	</tr>
</table>

<hr class="mb-3">
<h3 class="mt-3 mb-2">Simple Permalink Slug Building (BETA)</h3>

<?php
$slug_activated = 'yes' === AnWPFL_Options::get_value( 'simple_permalink_slug_building' );
$slug_working   = in_array( get_option( 'permalink_structure' ), [ '/%postname%/', '/%category%/%postname%/' ], true ) && $slug_activated;
?>
<table class="wp-list-table widefat striped table-view-list w-auto">
	<tr>
		<td class="px-3 anwp-font-semibold">Is Activated</td>
		<td class="px-2">
			<span class="anwp-leading-1 text-white px-2 py-0 rounded anwp-bg-<?php echo $slug_activated ? 'green' : 'orange'; ?>-600"><?php echo $slug_activated ? 'YES' : 'NO'; ?></span>
		</td>
	</tr>
	<tr>
		<td class="px-3 anwp-font-semibold">Is Working</td>
		<td class="px-2">
			<span class="anwp-leading-1 text-white px-2 py-0 rounded anwp-bg-<?php echo $slug_working ? 'green' : 'orange'; ?>-600"><?php echo $slug_working ? 'YES' : 'NO'; ?></span>
		</td>
	</tr>
	<tr>
		<td class="px-3 anwp-font-semibold">Permalink Structure</td>
		<td class="px-2">
			<?php echo esc_html( get_option( 'permalink_structure' ) ); ?>
		</td>
	</tr>
</table>
