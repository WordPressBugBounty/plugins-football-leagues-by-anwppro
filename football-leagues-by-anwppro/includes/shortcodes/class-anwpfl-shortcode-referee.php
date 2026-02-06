<?php
/**
 * AnWP Football Leagues :: Shortcode > Referee.
 *
 * @since   0.12.4
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Referee' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Referee.
 *
 * @since 0.12.4
 */
class AnWPFL_Shortcode_Referee extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-referee';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'referee';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Referee', 'anwp-football-leagues' );
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
			'referee_id'        => '',
			'options_text'      => '',
			'profile_link'      => '',
			'profile_link_text' => '',
			'show_job'          => '',
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
			[
				'name'     => 'referee_id',
				'type'     => 'selector',
				'entity'   => 'referee',
				'multiple' => false,
				'label'    => __( 'Referee ID', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'show_job',
				'type'    => 'yes_no',
				'label'   => __( 'Show Job Title', 'anwp-football-leagues' ),
				'default' => '0',
			],
			[
				'name'    => 'profile_link',
				'type'    => 'yes_no',
				'label'   => __( 'Show Link to Profile', 'anwp-football-leagues' ),
				'default' => '1',
			],
			[
				'name'    => 'profile_link_text',
				'type'    => 'text',
				'label'   => __( 'Profile link text', 'anwp-football-leagues' ),
				'default' => 'profile',
			],
			[
				'name'        => 'options_text',
				'type'        => 'text',
				'label'       => __( 'Options Text', 'anwp-football-leagues' ),
				'description' => __( 'Separate line by "|", number and label - with ":". E.q.: "Goals: 8 | Assists: 5"', 'anwp-football-leagues' ),
			],
		];
	}
}

// Bump
new AnWPFL_Shortcode_Referee();
