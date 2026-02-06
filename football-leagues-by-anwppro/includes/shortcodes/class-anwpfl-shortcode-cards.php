<?php
/**
 * AnWP Football Leagues :: Shortcode > Cards.
 *
 * @since   0.7.4
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Cards' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Cards.
 */
class AnWPFL_Shortcode_Cards extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-cards';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'cards';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Cards', 'anwp-football-leagues' );
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
			'competition_id' => '',
			'join_secondary' => 1,
			'season_id'      => '',
			'league_id'      => '',
			'club_id'        => '',
			'type'           => 'players',
			'limit'          => 0,
			'soft_limit'     => 'yes',
			'show_photo'     => 'yes',
			'points_r'       => '5',
			'points_yr'      => '2',
			'hide_zero'      => 0,
			'sort_by_point'  => '',
			'layout'         => '',
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
				'default' => 'players',
				'options' => [
					'players' => __( 'Players', 'anwp-football-leagues' ),
					'clubs'   => __( 'Clubs', 'anwp-football-leagues' ),
				],
			],
			[
				'name'     => 'competition_id',
				'type'     => 'selector',
				'entity'   => 'main_stage',
				'multiple' => false,
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
				'label'    => __( 'Club ID', 'anwp-football-leagues' ),
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
					''     => __( 'Default', 'anwp-football-leagues' ),
					'mini' => __( 'Mini', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'show_photo',
				'type'    => 'yes_no',
				'label'   => __( 'Show Photo/Logo', 'anwp-football-leagues' ),
				'default' => '1',
			],
			[
				'name'    => 'hide_zero',
				'type'    => 'yes_no',
				'label'   => __( 'Hide with zero points', 'anwp-football-leagues' ),
				'default' => '1',
			],
			[
				'name'    => 'sort_by_point',
				'type'    => 'select',
				'label'   => __( 'Sort By Points', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''    => __( 'Descending', 'anwp-football-leagues' ),
					'asc' => __( 'Ascending', 'anwp-football-leagues' ),
				],
			],

			// == Points Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Points', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'points_r',
				'type'    => 'text',
				'label'   => __( 'Points for Red card', 'anwp-football-leagues' ),
				'default' => '5',
			],
			[
				'name'    => 'points_yr',
				'type'    => 'text',
				'label'   => __( 'Points for Yellow/Red card', 'anwp-football-leagues' ),
				'default' => '2',
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

		return anwp_football_leagues()->template->shortcode_loader( 'cards', $atts );
	}
}

new AnWPFL_Shortcode_Cards();
