<?php
/**
 * AnWP Football Leagues :: Shortcode > Matches.
 *
 * @since   0.4.3
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Matches' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Matches.
 */
class AnWPFL_Shortcode_Matches extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-matches';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'matches';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Matches', 'anwp-football-leagues' );
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
			'competition_id'        => '',
			'stage_id'              => '',
			'show_secondary'        => 1,
			'season_id'             => '',
			'league_id'             => '',
			'group_id'              => '',
			'type'                  => '',
			'limit'                 => 0,
			'date_from'             => '',
			'date_to'               => '',
			'stadium_id'            => '',
			'filter_by'             => '',
			'filter_values'         => '',
			'filter_by_clubs'       => '',
			'filter_by_matchweeks'  => '',
			'days_offset'           => '',
			'days_offset_to'        => '',
			'sort_by_date'          => '',
			'sort_by_matchweek'     => '',
			'club_links'            => 1,
			'priority'              => '',
			'class'                 => '',
			'group_by'              => '',
			'group_by_header_style' => '',
			'show_club_logos'       => 1,
			'show_match_datetime'   => 1,
			'competition_logo'      => 1,
			'exclude_ids'           => '',
			'include_ids'           => '',
			'outcome_id'            => '',
			'no_data_text'          => '',
			'home_club'             => '',
			'away_club'             => '',
			'layout'                => '',
			'header_class'          => '',
			'show_load_more'        => false,
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
				'name'     => 'competition_id',
				'type'     => 'selector',
				'entity'   => 'main_stage',
				'multiple' => true,
				'label'    => __( 'Competition ID', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'stage_id',
				'type'     => 'selector',
				'entity'   => 'stage',
				'multiple' => true,
				'label'    => __( 'Competition Stage ID', 'anwp-football-leagues' ),
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
				'name'    => 'group_id',
				'type'    => 'text',
				'label'   => __( 'Group ID', 'anwp-football-leagues' ),
				'default' => '',
			],
			[
				'name'    => 'type',
				'type'    => 'select',
				'label'   => __( 'Match Type', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''        => __( 'All', 'anwp-football-leagues' ),
					'result'  => __( 'Result', 'anwp-football-leagues' ),
					'fixture' => __( 'Fixture', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'limit',
				'type'    => 'text',
				'label'   => __( 'Matches Limit (0 - for all)', 'anwp-football-leagues' ),
				'default' => '0',
			],

			// == Filters Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Filters', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'filter_by_clubs',
				'type'        => 'selector',
				'entity'      => 'club',
				'multiple'    => true,
				'label'       => __( 'Filter by Clubs', 'anwp-football-leagues' ),
				'description' => __( 'comma-separated list of IDs', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'home_club',
				'type'     => 'selector',
				'entity'   => 'club',
				'multiple' => false,
				'label'    => __( 'Filter by Home Club', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'away_club',
				'type'     => 'selector',
				'entity'   => 'club',
				'multiple' => false,
				'label'    => __( 'Filter by Away Club', 'anwp-football-leagues' ),
			],
			[
				'name'             => 'stadium_id',
				'type'             => 'tom_select',
				'label'            => __( 'Filter by Stadium', 'anwp-football-leagues' ),
				'default'          => '',
				'multiple'         => false,
				'options_callback' => 'get_stadiums_options',
			],
			[
				'name'        => 'filter_by_matchweeks',
				'type'        => 'text',
				'label'       => __( 'Filter by Matchweeks or Round IDs', 'anwp-football-leagues' ),
				'default'     => '',
				'description' => __( 'comma-separated list of matchweeks or rounds to filter', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'include_ids',
				'type'        => 'selector',
				'entity'      => 'match',
				'multiple'    => true,
				'label'       => __( 'Include IDs', 'anwp-football-leagues' ),
				'description' => __( 'comma-separated list of IDs', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'exclude_ids',
				'type'        => 'selector',
				'entity'      => 'match',
				'multiple'    => true,
				'label'       => __( 'Exclude IDs', 'anwp-football-leagues' ),
				'description' => __( 'comma-separated list of IDs', 'anwp-football-leagues' ),
			],

			// == Date Filters Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Date Filters', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'date_from',
				'type'        => 'text',
				'label'       => __( 'Date From', 'anwp-football-leagues' ),
				'default'     => '',
				'description' => __( 'Format: YYYY-MM-DD. E.g.: 2019-04-21', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'date_to',
				'type'        => 'text',
				'label'       => __( 'Date To', 'anwp-football-leagues' ),
				'default'     => '',
				'description' => __( 'Format: YYYY-MM-DD. E.g.: 2019-04-21', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'days_offset',
				'type'        => 'text',
				'label'       => __( 'Dynamic days filter', 'anwp-football-leagues' ),
				'default'     => '',
				'description' => __( 'For example: "-2" from 2 days ago and newer; "2" from the day after tomorrow and newer', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'days_offset_to',
				'type'        => 'text',
				'label'       => __( 'Dynamic days filter to', 'anwp-football-leagues' ),
				'default'     => '',
				'description' => __( 'For example: "1" - till tomorrow (tomorrow not included)', 'anwp-football-leagues' ),
			],

			// == Sorting & Grouping Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Sorting & Grouping', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'sort_by_date',
				'type'    => 'select',
				'label'   => __( 'Sort By Date', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''     => __( 'none', 'anwp-football-leagues' ),
					'asc'  => __( 'Oldest', 'anwp-football-leagues' ),
					'desc' => __( 'Latest', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'sort_by_matchweek',
				'type'    => 'select',
				'label'   => __( 'Sort By MatchWeek', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''     => __( 'none', 'anwp-football-leagues' ),
					'asc'  => __( 'Ascending', 'anwp-football-leagues' ),
					'desc' => __( 'Descending', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'group_by',
				'type'    => 'select',
				'label'   => __( 'Group By', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''            => __( 'none', 'anwp-football-leagues' ),
					'day'         => __( 'Day', 'anwp-football-leagues' ),
					'month'       => __( 'Month', 'anwp-football-leagues' ),
					'matchweek'   => __( 'Matchweek', 'anwp-football-leagues' ),
					'stage'       => __( 'Stage', 'anwp-football-leagues' ),
					'competition' => __( 'Competition', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'group_by_header_style',
				'type'    => 'select',
				'label'   => __( 'Group By Header Style', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''          => __( 'Default', 'anwp-football-leagues' ),
					'secondary' => __( 'Secondary', 'anwp-football-leagues' ),
				],
			],

			// == Display Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Display', 'anwp-football-leagues' ),
			],
			[
				'name'             => 'layout',
				'type'             => 'select',
				'label'            => __( 'Available Layouts', 'anwp-football-leagues' ),
				'default'          => '',
				'options_callback' => 'get_matches_layouts',
			],
			[
				'name'    => 'show_club_logos',
				'type'    => 'yes_no',
				'label'   => __( 'Show club logos', 'anwp-football-leagues' ),
				'default' => '1',
			],
			[
				'name'    => 'show_match_datetime',
				'type'    => 'yes_no',
				'label'   => __( 'Show match datetime', 'anwp-football-leagues' ),
				'default' => '1',
			],
			[
				'name'    => 'competition_logo',
				'type'    => 'yes_no',
				'label'   => __( 'Show Competition Logo', 'anwp-football-leagues' ),
				'default' => '1',
			],
			[
				'name'        => 'outcome_id',
				'type'        => 'selector',
				'entity'      => 'club',
				'multiple'    => false,
				'label'       => __( 'Show Outcome for club ID', 'anwp-football-leagues' ),
				'description' => __( 'works only in "slim" layout', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'no_data_text',
				'type'    => 'text',
				'label'   => __( 'No data text', 'anwp-football-leagues' ),
				'default' => '',
			],
			[
				'name'    => 'show_load_more',
				'type'    => 'yes_no',
				'label'   => __( 'Show Load More', 'anwp-football-leagues' ),
				'default' => '0',
			],
		];
	}

	/**
	 * Get stadium options for Tom Select.
	 *
	 * @return array
	 * @since 0.17.0
	 */
	public function get_stadiums_options(): array {
		$options = [ '' => '- ' . __( 'select', 'anwp-football-leagues' ) . ' -' ];

		foreach ( anwp_football_leagues()->stadium->get_stadiums_options() as $stadium_id => $stadium_title ) {
			$options[ $stadium_id ] = $stadium_title;
		}

		return $options;
	}

	/**
	 * Get available match layouts.
	 *
	 * @return array
	 * @since 0.17.0
	 */
	public function get_matches_layouts(): array {
		$available_layouts = [ 'slim', 'modern', 'simple' ];
		$available_layouts = apply_filters( 'anwpfl/shortcodes/matches_available_layouts', $available_layouts );

		$options = [];

		foreach ( $available_layouts as $layout ) {
			$value             = 'slim' === $layout ? '' : $layout;
			$options[ $value ] = $layout;
		}

		return $options;
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

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( '%competition_id%' === $atts['competition_id'] && ! empty( $_GET['competition_id'] ) && absint( $_GET['competition_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$atts['competition_id'] = absint( $_GET['competition_id'] );
		}

		// Validate shortcode attr.
		$atts['show_secondary']      = (int) $atts['show_secondary'];
		$atts['limit']               = (int) $atts['limit'];
		$atts['competition_id']      = (int) $atts['competition_id'] ? sanitize_text_field( $atts['competition_id'] ) : '';
		$atts['season_id']           = (int) $atts['season_id'] ?: '';
		$atts['stadium_id']          = (int) $atts['stadium_id'] ?: '';
		$atts['show_club_logos']     = (int) $atts['show_club_logos'];
		$atts['show_match_datetime'] = (int) $atts['show_match_datetime'];
		$atts['club_links']          = (int) $atts['club_links'];

		$atts['type']                  = in_array( $atts['type'], [ 'result', 'fixture' ], true ) ? $atts['type'] : '';
		$atts['filter_by']             = in_array( $atts['filter_by'], [ 'club', 'matchweek' ], true ) ? $atts['filter_by'] : '';
		$atts['group_by']              = in_array( $atts['group_by'], [
			'day',
			'month',
			'matchweek',
			'stage',
			'competition',
		], true ) ? $atts['group_by'] : '';
		$atts['group_by_header_style'] = esc_attr( $atts['group_by_header_style'] );
		$atts['sort_by_date']          = in_array( strtolower( $atts['sort_by_date'] ), [
			'asc',
			'desc',
		], true ) ? strtolower( $atts['sort_by_date'] ) : '';

		return anwp_football_leagues()->template->shortcode_loader( 'matches', $atts );
	}
}

new AnWPFL_Shortcode_Matches();
