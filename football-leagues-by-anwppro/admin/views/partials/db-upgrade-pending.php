<?php
/**
 * Database Upgrade Pending notice.
 *
 * Shown by import-related admin pages (Import Tool, API Import, Site Migration)
 * when the v0.18 schema upgrade has not yet completed on this site. Import paths
 * write directly to custom tables and require the new schema to be in place.
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

$current_core_version    = (int) get_option( 'anwpfl_db_version' );
$expected_core_version   = (int) AnWP_Football_Leagues::DB_VERSION;
$current_prem_version    = class_exists( 'AnWPFL_Premium_Upgrade' ) ? (int) get_option( 'anwpfl_premium_db_version' ) : null;
$expected_prem_version   = class_exists( 'AnWPFL_Premium_Upgrade' ) ? (int) AnWPFL_Premium_Upgrade::DB_VERSION : null;
$support_url             = admin_url( 'admin.php?page=anwpfl-support' );
?>
<div class="anwp-b-wrap wrap">

	<h1 class="mb-3"><?php echo esc_html__( 'Database Upgrade Pending', 'anwp-football-leagues' ); ?></h1>

	<div class="notice notice-warning inline">
		<p><strong><?php echo esc_html__( 'This page is unavailable until the Football Leagues database upgrade completes.', 'anwp-football-leagues' ); ?></strong></p>
		<p><?php echo esc_html__( 'Open any other WordPress admin page (for example: Dashboard, Posts, Pages) and the upgrade will run automatically. Then return here and reload.', 'anwp-football-leagues' ); ?></p>
	</div>

	<table class="widefat striped" style="max-width: 520px; margin-top: 16px;">
		<thead>
			<tr>
				<th><?php echo esc_html__( 'Component', 'anwp-football-leagues' ); ?></th>
				<th><?php echo esc_html__( 'Current', 'anwp-football-leagues' ); ?></th>
				<th><?php echo esc_html__( 'Expected', 'anwp-football-leagues' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php echo esc_html__( 'Core DB version', 'anwp-football-leagues' ); ?></td>
				<td><?php echo esc_html( $current_core_version ); ?></td>
				<td><?php echo esc_html( $expected_core_version ); ?></td>
			</tr>
			<?php if ( null !== $expected_prem_version ) : ?>
				<tr>
					<td><?php echo esc_html__( 'Premium DB version', 'anwp-football-leagues' ); ?></td>
					<td><?php echo esc_html( $current_prem_version ); ?></td>
					<td><?php echo esc_html( $expected_prem_version ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<p style="margin-top: 16px;">
		<a class="button button-primary" href=""><?php echo esc_html__( 'Reload this page', 'anwp-football-leagues' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url() ); ?>"><?php echo esc_html__( 'Go to Dashboard', 'anwp-football-leagues' ); ?></a>
	</p>

	<p class="description" style="margin-top: 24px;">
		<?php
		printf(
			/* translators: %s: Support page link */
			esc_html__( 'If this notice persists after reloading the dashboard, the upgrade may have failed. Check %s for diagnostic information.', 'anwp-football-leagues' ),
			'<a href="' . esc_url( $support_url ) . '">' . esc_html__( 'FL > Support', 'anwp-football-leagues' ) . '</a>'
		);
		?>
	</p>

</div>
