<?php
/**
 * Shortcodes page for AnWP Football Leagues
 *
 * @link       https://anwp.pro
 * @since      0.10.7
 *
 * @package    AnWP_Football_Leagues
 * @subpackage AnWP_Football_Leagues/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification
$shortcode_tab = sanitize_key( $_GET['tab'] ?? '' ) ?: 'builder';
?>

<div class="wrap anwp-b-wrap">

	<?php if ( 'howto' === $shortcode_tab ) : ?>
		<?php AnWP_Football_Leagues::include_file( 'admin/views/shortcodes-howto' ); ?>
	<?php else : ?>
		<?php AnWP_Football_Leagues::include_file( 'admin/views/shortcodes-builder' ); ?>
	<?php endif; ?>

</div>
