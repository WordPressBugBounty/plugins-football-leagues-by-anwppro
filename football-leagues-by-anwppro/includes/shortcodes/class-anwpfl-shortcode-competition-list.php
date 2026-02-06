<?php
/**
 * AnWP Football Leagues :: Shortcode > Competition List.
 *
 * @since   0.12.3
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Competition_List' ) ) {
	return;
}

class AnWPFL_Shortcode_Competition_List extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-competition-list';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'competition-list';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Competition List', 'anwp-football-leagues' );
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
	 * Get template name.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_template_name(): string {
		return 'competition_list';
	}

	/**
	 * Get default attribute values.
	 *
	 * @return array
	 * @since 0.17.0
	 */
	protected function get_defaults(): array {
		return [
			'league_ids'  => '',
			'season_ids'  => '',
			'include_ids' => '',
			'exclude_ids' => '',
			'group_by'    => '',
			'display'     => '',
			'show_logo'   => 'yes',
			'show_flag'   => 'big',
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
			// == Selection Section ==
			[
				'name'        => 'league_ids',
				'type'        => 'selector',
				'entity'      => 'league',
				'multiple'    => true,
				'label'       => __( 'League IDs', 'anwp-football-leagues' ),
				'description' => __( 'Optional. Empty - for all.', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'season_ids',
				'type'        => 'selector',
				'entity'      => 'season',
				'multiple'    => true,
				'label'       => __( 'Season IDs', 'anwp-football-leagues' ),
				'description' => __( 'Optional. Empty - for all.', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'include_ids',
				'type'        => 'selector',
				'entity'      => 'competition',
				'multiple'    => true,
				'label'       => __( 'Include Competitions', 'anwp-football-leagues' ),
				'description' => __( 'comma-separated list of IDs', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'exclude_ids',
				'type'        => 'selector',
				'entity'      => 'competition',
				'multiple'    => true,
				'label'       => __( 'Exclude Competitions', 'anwp-football-leagues' ),
				'description' => __( 'comma-separated list of IDs', 'anwp-football-leagues' ),
			],

			// == Display Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Display', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'group_by',
				'type'    => 'select',
				'label'   => __( 'Group By', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''                  => __( 'none', 'anwp-football-leagues' ),
					'country'           => __( 'Country', 'anwp-football-leagues' ),
					'country_collapsed' => __( 'Country - collapsed', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'display',
				'type'    => 'select',
				'label'   => __( 'Display Competition as', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''              => __( 'Competition', 'anwp-football-leagues' ),
					'league'        => __( 'League', 'anwp-football-leagues' ),
					'league_season' => __( 'League', 'anwp-football-leagues' ) . ' - ' . __( 'Season', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'show_logo',
				'type'    => 'yes_no',
				'label'   => __( 'Show Competition Logo', 'anwp-football-leagues' ),
				'default' => '1',
			],
			[
				'name'    => 'show_flag',
				'type'    => 'select',
				'label'   => __( 'Show Country Flag', 'anwp-football-leagues' ),
				'default' => 'big',
				'options' => [
					'big'   => __( 'Yes', 'anwp-football-leagues' ) . ' - ' . __( 'Big', 'anwp-football-leagues' ),
					'small' => __( 'Yes', 'anwp-football-leagues' ) . ' - ' . __( 'Small', 'anwp-football-leagues' ),
					''      => __( 'No', 'anwp-football-leagues' ),
				],
			],
		];
	}
}

// Bump
new AnWPFL_Shortcode_Competition_List();
