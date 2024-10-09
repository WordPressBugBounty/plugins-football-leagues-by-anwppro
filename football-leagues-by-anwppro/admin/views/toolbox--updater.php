<?php
/**
 * Import API subpage for AnWP Football Leagues
 *
 * @link       https://anwp.pro
 * @since      0.16.0
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
	<h1 class="anwp-font-normal mb-0">Database Schema Updater</h1>
</div>
<div class="mb-3 d-flex align-items-center">
	<span class="text-muted">Database Updater</span>
<!--	<small class="text-muted mx-2 d-inline-block">|</small>-->
<!--	<a class="text-decoration-none" href="#">Optimizer</a>-->
</div>

<hr class="mb-3">
<div class="mt-2 mt-n3 anwp-text-right">
	Change request qty:
	<a class="mx-1 <?php echo esc_attr( 1 === absint( $_GET['fl_page_num'] ?? 0 ) ? 'anwp-font-bold' : '' ); ?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox&fl_page_num=1' ) ); ?>">1</a>|
	<a class="mx-1 <?php echo esc_attr( 5 === absint( $_GET['fl_page_num'] ?? 0 ) ? 'anwp-font-bold' : '' ); ?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox&fl_page_num=5' ) ); ?>">5</a>|
	<a class="mx-1 <?php echo esc_attr( 10 === absint( $_GET['fl_page_num'] ?? 0 ) ? 'anwp-font-bold' : '' ); ?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox&fl_page_num=10' ) ); ?>">10</a>|
	<a class="mx-1 <?php echo esc_attr( 20 === absint( $_GET['fl_page_num'] ?? 0 ) ? 'anwp-font-bold' : '' ); ?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox&fl_page_num=20' ) ); ?>">20</a>|
	<a class="mx-1 <?php echo esc_attr( 50 === absint( $_GET['fl_page_num'] ?? 0 ) ? 'anwp-font-bold' : '' ); ?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox&fl_page_num=50' ) ); ?>">50</a>
</div>

<div id="fl-app-toolbox--updater"></div>
