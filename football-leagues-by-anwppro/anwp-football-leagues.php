<?php
/**
 * Plugin Name: AnWP Football Leagues
 * Plugin URI:  https://anwppro.userecho.com/communities/1-football-leagues
 * Description: Create and manage your own football club, competition, league or soccer news website. Knockout and round-robin stages, player profiles, standing tables and much more.
 * Version:     0.16.15
 * Author:      Andrei Strekozov <anwppro>
 * Author URI:  https://anwp.pro
 * License:     GPLv2+
 * Requires PHP: 7.0
 * Text Domain: anwp-football-leagues
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2017-2025 Andrei Strekozov <anwppro> (email: anwp.pro@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using generator-plugin-wp (https://github.com/WebDevStudios/generator-plugin-wp)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'ANWP_FL_VERSION', '0.16.15' );

// Check for required PHP version
if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
	add_action( 'admin_notices', 'anwpfl_requirements_not_met_notice' );
} else {

	// Require the main plugin class
	require_once plugin_dir_path( __FILE__ ) . 'class-anwp-football-leagues.php';

	// Kick it off
	add_action( 'plugins_loaded', array( anwp_fl(), 'hooks' ) );

	// Activation and deactivation
	register_activation_hook( __FILE__, array( anwp_fl(), 'activate' ) );
}

/**
 * Adds a notice to the dashboard if the plugin requirements are not met.
 *
 * @since  0.2.0
 * @return void
 */
function anwpfl_requirements_not_met_notice() {

	// Compile default message.
	$default_message = esc_html__( 'Football Leagues by AnWPPro is missing requirements and currently NOT ACTIVE. Please make sure all requirements are available.', 'anwp-football-leagues' );

	// Default details.
	$details = '';

	if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
		/* translators: %s minimum PHP version */
		$details .= '<small>' . sprintf( esc_html__( 'Football Leagues by AnWPPro cannot run on PHP versions older than %s. Please contact your hosting provider to update your site.', 'anwp-football-leagues' ), '7.0' ) . '</small><br />';
	}

	// Output errors.
	?>
	<div id="message" class="error">
		<p><?php echo wp_kses_post( $default_message ); ?></p>
		<?php echo wp_kses_post( $details ); ?>
	</div>
	<?php
}

/**
 * Grab the AnWP_Football_Leagues object and return it.
 * Wrapper for AnWP_Football_Leagues::get_instance().
 *
 * @since  0.1.0
 * @return AnWP_Football_Leagues  Singleton instance of plugin class.
 * @deprecated Recommended to use short version anwp_fl()
 */
function anwp_football_leagues(): AnWP_Football_Leagues {
	return AnWP_Football_Leagues::get_instance();
}

/**
 * Grab the AnWP_Football_Leagues object and return it.
 * Wrapper for AnWP_Football_Leagues::get_instance().
 *
 * @since  0.16.0
 * @return AnWP_Football_Leagues  Singleton instance of plugin class.
 */
function anwp_fl(): AnWP_Football_Leagues {
	return AnWP_Football_Leagues::get_instance();
}
