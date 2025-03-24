<?php
/**
 * Toolbox subpage for AnWP Football Leagues
 *
 * @link       https://anwp.pro
 * @since      0.16.14
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
	<h1 class="anwp-font-normal mb-0">Toolkit</h1>
</div>
<div class="mb-3 d-flex align-items-center">
	<a class="text-decoration-none" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox' ) ); ?>">Database Updater</a>
	<small class="text-muted mx-2 d-inline-block">|</small>
	<span class="text-muted">Toolkit</span>
</div>

<hr class="mb-3">

<div id="fl-app-toolbox--toolkit"></div>
