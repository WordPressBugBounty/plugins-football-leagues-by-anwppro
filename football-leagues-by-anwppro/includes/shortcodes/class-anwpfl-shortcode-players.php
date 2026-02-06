<?php
/**
 * AnWP Football Leagues :: Shortcode > Players.
 *
 * @since   0.5.1
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Players' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Players.
 */
class AnWPFL_Shortcode_Players extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-players';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'players';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Players', 'anwp-football-leagues' );
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
			'competition_id'    => '',
			'join_secondary'    => 0,
			'season_id'         => '',
			'league_id'         => '',
			'club_id'           => '',
			'type'              => 'scorers',
			'limit'             => 0,
			'soft_limit'        => 'yes',
			'layout'            => '',
			'hide_zero'         => 0,
			'show_photo'        => 'yes',
			'penalty_goals'     => 0,
			'games_played'      => 0,
			'games_played_text' => '',
			'secondary_sorting' => '',
			'group_by_place'    => 0,
			'compact'           => 0,
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
			// == Query Section ==
			[
				'name'    => 'type',
				'type'    => 'select',
				'label'   => __( 'Type', 'anwp-football-leagues' ),
				'default' => 'scorers',
				'options' => [
					'scorers' => __( 'Scorers', 'anwp-football-leagues' ),
					'assists' => __( 'Assists', 'anwp-football-leagues' ),
				],
			],
			[
				'name'     => 'competition_id',
				'type'     => 'selector',
				'entity'   => 'main_stage',
				'multiple' => true,
				'label'    => __( 'Competition IDs', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'season_id',
				'type'     => 'selector',
				'entity'   => 'season',
				'multiple' => false,
				'label'    => __( 'Season ID', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'league_id',
				'type'     => 'selector',
				'entity'   => 'league',
				'multiple' => false,
				'label'    => __( 'League ID', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'club_id',
				'type'     => 'selector',
				'entity'   => 'club',
				'multiple' => true,
				'label'    => __( 'Club IDs', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'limit',
				'type'    => 'text',
				'label'   => __( 'Players Limit (0 - for all)', 'anwp-football-leagues' ),
				'default' => '0',
			],
			[
				'name'        => 'soft_limit',
				'type'        => 'yes_no',
				'label'       => __( 'Soft Limit', 'anwp-football-leagues' ),
				'default'     => '1',
				'description' => __( 'Increase number of players to the end of players with equal stats value.', 'anwp-football-leagues' ),
			],

			// == Display Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Display', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'layout',
				'type'    => 'select',
				'label'   => __( 'Layout', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''      => __( 'Default', 'anwp-football-leagues' ),
					'small' => __( 'Small', 'anwp-football-leagues' ),
					'mini'  => __( 'Mini', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'show_photo',
				'type'    => 'yes_no',
				'label'   => __( 'Show Photo', 'anwp-football-leagues' ),
				'default' => '1',
			],
			[
				'name'    => 'compact',
				'type'    => 'yes_no',
				'label'   => __( 'Compact', 'anwp-football-leagues' ),
				'default' => '0',
			],
			[
				'name'    => 'hide_zero',
				'type'    => 'yes_no',
				'label'   => __( 'Hide Zeros', 'anwp-football-leagues' ),
				'default' => '0',
			],
			[
				'name'    => 'penalty_goals',
				'type'    => 'select',
				'label'   => __( 'Goals (from penalty)', 'anwp-football-leagues' ),
				'default' => '0',
				'options' => [
					'0' => __( 'Hide', 'anwp-football-leagues' ),
					'1' => __( 'Show', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'games_played',
				'type'    => 'select',
				'label'   => __( 'Matches played', 'anwp-football-leagues' ),
				'default' => '0',
				'options' => [
					'0' => __( 'Hide', 'anwp-football-leagues' ),
					'1' => __( 'Show', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'games_played_text',
				'type'    => 'text',
				'label'   => __( 'Text for "Matches played" column', 'anwp-football-leagues' ),
				'default' => __( 'Played', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'group_by_place',
				'type'    => 'yes_no',
				'label'   => __( 'Group By Place', 'anwp-football-leagues' ),
				'default' => '0',
			],
			[
				'name'    => 'secondary_sorting',
				'type'    => 'select',
				'label'   => __( 'Secondary Sorting', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''             => __( 'Default', 'anwp-football-leagues' ),
					'less_games'   => __( 'Less Games', 'anwp-football-leagues' ),
					'less_penalty' => __( 'Less Penalty', 'anwp-football-leagues' ),
				],
			],
		];
	}

	/**
	 * Rendering shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function render_shortcode( $atts ): string {

		// Parse defaults and create a shortcode.
		$atts = shortcode_atts( $this->get_defaults(), (array) $atts, $this->get_shortcode_tag() );

		return anwp_football_leagues()->template->shortcode_loader( 'players', $atts );
	}
}

new AnWPFL_Shortcode_Players();
