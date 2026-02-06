<?php
/**
 * AnWP Football Leagues :: Shortcode > Club.
 *
 * @since   0.11.8
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Club' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Club.
 */
class AnWPFL_Shortcode_Club extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-club';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'club';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Club', 'anwp-football-leagues' );
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
			'club_id'   => '',
			'season_id' => '',
			'sections'  => '',
		];
	}

	/**
	 * Get default preview width (narrow for single-item display).
	 *
	 * @return int
	 * @since 0.17.0
	 */
	protected function get_default_preview_width(): int {
		return 700;
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
				'name'     => 'club_id',
				'type'     => 'selector',
				'entity'   => 'club',
				'multiple' => false,
				'label'    => __( 'Club ID', 'anwp-football-leagues' ),
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
				'sortable'     => true,
				'label'        => __( 'Sections', 'anwp-football-leagues' ),
				'options'      => [
					'header'      => __( 'Header', 'anwp-football-leagues' ),
					'description' => __( 'Description', 'anwp-football-leagues' ),
					'gallery'     => __( 'Gallery', 'anwp-football-leagues' ),
					'fixtures'    => __( 'Fixtures', 'anwp-football-leagues' ),
					'latest'      => __( 'Latest', 'anwp-football-leagues' ),
					'squad'       => __( 'Squad', 'anwp-football-leagues' ),
				],
				'options_hook' => 'anwpfl/shortcodes/club_shortcode_options',
			],
		];
	}
}

// Bump
new AnWPFL_Shortcode_Club();
