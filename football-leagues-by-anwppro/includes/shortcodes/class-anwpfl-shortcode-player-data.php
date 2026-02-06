<?php
/**
 * AnWP Football Leagues :: Shortcode > Player Data.
 *
 * @since   0.11.7
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Player_Data' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Player Data.
 */
class AnWPFL_Shortcode_Player_Data extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-player-data';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'player-data';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Player Data', 'anwp-football-leagues' );
	}

	/**
	 * Get documentation URL.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_docs_url(): string {
		return '';
	}

	/**
	 * Get default attribute values.
	 *
	 * @return array
	 * @since 0.17.0
	 */
	protected function get_defaults(): array {
		return [
			'player_id' => '',
			'season_id' => '',
			'sections'  => '',
		];
	}

	/**
	 * Get form field definitions.
	 *
	 * @return array
	 * @since 0.17.0
	 */
	protected function get_form_fields(): array {
		return [
			[
				'name'     => 'player_id',
				'type'     => 'selector',
				'entity'   => 'player',
				'multiple' => false,
				'label'    => __( 'Player ID', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'season_id',
				'type'     => 'selector',
				'entity'   => 'season',
				'multiple' => false,
				'label'    => __( 'Season ID', 'anwp-football-leagues' ),
			],
			[
				'name'         => 'sections',
				'type'         => 'tom_select',
				'multiple'     => true,
				'label'        => __( 'Sections', 'anwp-football-leagues' ),
				'options'      => [
					'header'      => __( 'Header', 'anwp-football-leagues' ),
					'description' => __( 'Description', 'anwp-football-leagues' ),
					'gallery'     => __( 'Gallery', 'anwp-football-leagues' ),
					'matches'     => __( 'Matches', 'anwp-football-leagues' ),
					'missed'      => __( 'Missed', 'anwp-football-leagues' ),
					'stats'       => __( 'Stats', 'anwp-football-leagues' ),
				],
				'options_hook' => 'anwpfl/shortcodes/player_shortcode_options',
			],
		];
	}
}

// Bump
new AnWPFL_Shortcode_Player_Data();
