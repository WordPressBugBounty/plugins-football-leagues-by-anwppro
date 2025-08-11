<?php
/**
 * Plugin Name: AnWP Football Leagues
 * Plugin URI:  https://anwppro.userecho.com/communities/1-football-leagues
 * Description: Create and manage your own football club, competition, league or soccer news website. Knockout and round-robin stages, player profiles, standing tables and much more.
 * Version:     0.16.18
 * Author:      Andrei Strekozov <anwppro>
 * Author URI:  https://anwp.pro
 * License:     GPLv3
 * Requires PHP: 7.4
 * Text Domain: anwp-football-leagues
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2017-2025 Andrei Strekozov <anwppro> (email: anwp.pro@gmail.com)
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Built using generator-plugin-wp (https://github.com/WebDevStudios/generator-plugin-wp)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'ANWP_FL_VERSION', '0.16.18' );
define( 'ANWP_FL_MIN_PHP_VERSION', '7.4' );

// Check for required PHP version
if ( version_compare( PHP_VERSION, ANWP_FL_MIN_PHP_VERSION, '<' ) ) {
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

	if ( version_compare( PHP_VERSION, ANWP_FL_MIN_PHP_VERSION, '<' ) ) {
		/* translators: %s minimum PHP version */
		$details .= '<small>' . sprintf( esc_html__( 'Football Leagues by AnWPPro cannot run on PHP versions older than %s. Please contact your hosting provider to update your site.', 'anwp-football-leagues' ), ANWP_FL_MIN_PHP_VERSION ) . '</small><br />';
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
 * @since  0.16.0
 * @return AnWP_Football_Leagues  Singleton instance of plugin class.
 */
function anwp_fl(): AnWP_Football_Leagues {
	return AnWP_Football_Leagues::get_instance();
}

/**
 * Grab the AnWP_Football_Leagues object and return it.
 * Wrapper for AnWP_Football_Leagues::get_instance().
 *
 * @since  0.1.0
 * @return AnWP_Football_Leagues  Singleton instance of plugin class.
 */
function anwp_football_leagues(): AnWP_Football_Leagues {
	return anwp_fl();
}
