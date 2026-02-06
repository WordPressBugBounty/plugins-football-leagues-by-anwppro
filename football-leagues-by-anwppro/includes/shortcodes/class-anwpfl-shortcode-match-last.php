<?php
/**
 * AnWP Football Leagues :: Shortcode > Match Last.
 *
 * @since   0.12.7
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Match_Last' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Match Last.
 *
 * @since 0.12.7
 */
class AnWPFL_Shortcode_Match_Last extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-match-last';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'match-last';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Last Match', 'anwp-football-leagues' );
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
			'club_id'         => '',
			'competition_id'  => '',
			'season_id'       => '',
			'match_link_text' => '',
			'show_club_name'  => 1,
			'exclude_ids'     => '',
			'include_ids'     => '',
			'max_size'        => '',
			'offset'          => '',
			'transparent_bg'  => '',
		];
	}

	/**
	 * Get default preview width (narrow for single-item display).
	 *
	 * @return int
	 * @since 0.17.0
	 */
	protected function get_default_preview_width(): int {
		return 400;
	}

	/**
	 * Get form field definitions.
	 *
	 * @return array
	 * @since 0.17.0
	 */
	protected function get_form_fields(): array {
		return [
			// == Query Section ==
			[
				'name'     => 'club_id',
				'type'     => 'selector',
				'entity'   => 'club',
				'multiple' => false,
				'label'    => __( 'Club ID', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'competition_id',
				'type'     => 'selector',
				'entity'   => 'competition',
				'multiple' => true,
				'label'    => __( 'Competition ID', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'season_id',
				'type'     => 'selector',
				'entity'   => 'season',
				'multiple' => false,
				'label'    => __( 'Season ID', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'exclude_ids',
				'type'        => 'selector',
				'entity'      => 'match',
				'multiple'    => true,
				'label'       => __( 'Exclude IDs', 'anwp-football-leagues' ),
				'description' => __( 'comma-separated list of IDs', 'anwp-football-leagues' ),
			],
			[
				'name'  => 'offset',
				'type'  => 'text',
				'label' => __( 'Game Offset', 'anwp-football-leagues' ),
			],

			// == Display Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Display', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'show_club_name',
				'type'    => 'yes_no',
				'label'   => __( 'Show Club Name', 'anwp-football-leagues' ),
				'default' => '1',
			],
			[
				'name'    => 'match_link_text',
				'type'    => 'text',
				'label'   => __( 'Match link text', 'anwp-football-leagues' ),
				'default' => __( '- match preview -', 'anwp-football-leagues' ),
			],
		];
	}
}

// Bump
new AnWPFL_Shortcode_Match_Last();
