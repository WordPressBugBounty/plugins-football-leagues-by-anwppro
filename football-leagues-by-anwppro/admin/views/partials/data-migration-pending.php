<?php
/**
 * Data Migration Pending notice.
 *
 * Shown by import-related admin pages (Import Tool, API Import, Site Migration)
 * when any of the v0.18 data migrations (clubs / competitions / standings) has not
 * yet completed. Schema upgrade and data migration are separate steps - this notice
 * covers the second.
 *
 * Used by both Core and Premium admin views.
 *
 * @link       https://anwp.pro
 *
 * @package    AnWP_Football_Leagues
 * @subpackage AnWP_Football_Leagues/admin/views/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$clubs_migrated        = (bool) get_option( 'anwpfl_clubs_migrated' );
$competitions_migrated = (bool) get_option( 'anwpfl_competitions_migrated' );
$standings_migrated    = (bool) get_option( 'anwpfl_standings_migrated' );
$toolbox_url           = admin_url( 'admin.php?page=anwpfl-toolbox' );
?>
<div class="anwp-b-wrap wrap">

	<h1 class="mb-3"><?php echo esc_html__( 'Data Migration Pending', 'anwp-football-leagues' ); ?></h1>

	<div class="notice notice-warning inline">
		<p><strong><?php echo esc_html__( 'This page is unavailable until all Football Leagues data migrations complete.', 'anwp-football-leagues' ); ?></strong></p>
		<p><?php echo esc_html__( 'Importers write directly to custom tables and assume migrated data is in place. Running imports before the migration produces orphan rows. Open the Database Updater, run the pending migrations, then return here.', 'anwp-football-leagues' ); ?></p>
	</div>

	<table class="widefat striped" style="max-width: 520px; margin-top: 16px;">
		<thead>
			<tr>
				<th><?php echo esc_html__( 'Migration', 'anwp-football-leagues' ); ?></th>
				<th><?php echo esc_html__( 'Status', 'anwp-football-leagues' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php echo esc_html__( 'Clubs', 'anwp-football-leagues' ); ?></td>
				<td><?php echo $clubs_migrated ? '<span style="color:#46b450;">&#10003; ' . esc_html__( 'Done', 'anwp-football-leagues' ) . '</span>' : '<span style="color:#dc3232;">&#10007; ' . esc_html__( 'Pending', 'anwp-football-leagues' ) . '</span>'; ?></td>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'Competitions', 'anwp-football-leagues' ); ?></td>
				<td><?php echo $competitions_migrated ? '<span style="color:#46b450;">&#10003; ' . esc_html__( 'Done', 'anwp-football-leagues' ) . '</span>' : '<span style="color:#dc3232;">&#10007; ' . esc_html__( 'Pending', 'anwp-football-leagues' ) . '</span>'; ?></td>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'Standings', 'anwp-football-leagues' ); ?></td>
				<td><?php echo $standings_migrated ? '<span style="color:#46b450;">&#10003; ' . esc_html__( 'Done', 'anwp-football-leagues' ) . '</span>' : '<span style="color:#dc3232;">&#10007; ' . esc_html__( 'Pending', 'anwp-football-leagues' ) . '</span>'; ?></td>
			</tr>
		</tbody>
	</table>

	<p style="margin-top: 16px;">
		<a class="button button-primary" href="<?php echo esc_url( $toolbox_url ); ?>"><?php echo esc_html__( 'Database Updater', 'anwp-football-leagues' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url() ); ?>"><?php echo esc_html__( 'Go to Dashboard', 'anwp-football-leagues' ); ?></a>
	</p>

</div>
