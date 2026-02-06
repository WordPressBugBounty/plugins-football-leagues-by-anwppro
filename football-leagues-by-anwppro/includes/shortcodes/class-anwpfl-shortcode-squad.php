<?php
/**
 * AnWP Football Leagues :: Shortcode > Squad.
 *
 * @since   0.5.0
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Squad' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Squad.
 *
 * @since 0.5.0
 */
class AnWPFL_Shortcode_Squad extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-squad';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'squad';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Squad', 'anwp-football-leagues' );
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
			'season_id' => '',
			'club_id'   => '',
			'header'    => 1,
			'layout'    => '',
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
				'name'     => 'season_id',
				'type'     => 'selector',
				'entity'   => 'season',
				'multiple' => false,
				'label'    => __( 'Season ID', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'club_id',
				'type'     => 'selector',
				'entity'   => 'club',
				'multiple' => false,
				'label'    => __( 'Club ID', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'header',
				'type'    => 'yes_no',
				'label'   => __( 'Show Header', 'anwp-football-leagues' ),
				'default' => '1',
			],
			[
				'name'    => 'layout',
				'type'    => 'select',
				'label'   => __( 'Layout', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''       => __( 'Default', 'anwp-football-leagues' ),
					'blocks' => __( 'Blocks', 'anwp-football-leagues' ),
				],
			],
		];
	}
}

// Bump
new AnWPFL_Shortcode_Squad();
