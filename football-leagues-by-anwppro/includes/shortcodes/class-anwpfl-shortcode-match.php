<?php
/**
 * AnWP Football Leagues :: Shortcode > Match.
 *
 * @since   0.6.1
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Match' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Match.
 */
class AnWPFL_Shortcode_Match extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-match';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'match';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Match', 'anwp-football-leagues' );
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
			'layout'         => '',
			'match_id'       => '',
			'club_last'      => '',
			'club_next'      => '',
			'sections'       => '',
			'show_header'    => 1,
			'section_header' => 1,
			'class'          => 'mt-4',
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
				'name'     => 'match_id',
				'type'     => 'selector',
				'entity'   => 'match',
				'multiple' => false,
				'label'    => __( 'Match ID', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'club_last',
				'type'     => 'selector',
				'entity'   => 'club',
				'multiple' => false,
				'label'    => __( 'Last finished match of the club', 'anwp-football-leagues' ),
			],
			[
				'name'     => 'club_next',
				'type'     => 'selector',
				'entity'   => 'club',
				'multiple' => false,
				'label'    => __( 'Next match of the club', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'layout',
				'type'    => 'select',
				'label'   => __( 'Layout', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''     => __( 'Default', 'anwp-football-leagues' ),
					'slim' => __( 'Slim', 'anwp-football-leagues' ),
				],
			],
			[
				'name'         => 'sections',
				'type'         => 'tom_select',
				'multiple'     => true,
				'sortable'     => true,
				'label'        => __( 'Sections', 'anwp-football-leagues' ),
				'options'      => [
					'goals'            => __( 'Goals', 'anwp-football-leagues' ),
					'cards'            => __( 'Cards', 'anwp-football-leagues' ),
					'line_ups'         => __( 'Line Ups', 'anwp-football-leagues' ),
					'substitutes'      => __( 'Substitutes', 'anwp-football-leagues' ),
					'stats'            => __( 'Stats', 'anwp-football-leagues' ),
					'referees'         => __( 'Referees', 'anwp-football-leagues' ),
					'missed_penalties' => __( 'Missed Penalties', 'anwp-football-leagues' ),
					'summary'          => __( 'Summary', 'anwp-football-leagues' ),
					'penalty_shootout' => __( 'Penalty Shootout', 'anwp-football-leagues' ),
					'video'            => __( 'Video', 'anwp-football-leagues' ),
					'missing'          => __( 'Missing Players', 'anwp-football-leagues' ),
				],
				'options_hook' => 'anwpfl/shortcodes/match_shortcode_options',
			],
			[
				'name'    => 'show_header',
				'type'    => 'yes_no',
				'label'   => __( 'Show Match Header', 'anwp-football-leagues' ),
				'default' => '1',
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

		// Validate shortcode attr.
		$atts['match_id'] = (int) $atts['match_id'];
		$atts['layout']   = in_array( $atts['layout'], [ 'full', 'slim' ], true ) ? $atts['layout'] : '';

		return anwp_fl()->template->shortcode_loader( 'match', $atts );
	}
}

new AnWPFL_Shortcode_Match();
