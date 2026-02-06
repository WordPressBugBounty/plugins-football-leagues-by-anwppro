<?php
/**
 * AnWP Football Leagues :: Shortcode > Staff.
 *
 * @since   0.12.4
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Staff' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Staff.
 *
 * @since 0.12.4
 */
class AnWPFL_Shortcode_Staff extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-staff';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'staff';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Staff', 'anwp-football-leagues' );
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
			'staff_id'          => '',
			'options_text'      => '',
			'profile_link'      => '',
			'profile_link_text' => '',
			'show_club'         => '',
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
				'name'     => 'staff_id',
				'type'     => 'selector',
				'entity'   => 'staff',
				'multiple' => false,
				'label'    => __( 'Staff ID', 'anwp-football-leagues' ),
			],

			// == Display Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Display', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'show_club',
				'type'    => 'yes_no',
				'label'   => __( 'Show Club', 'anwp-football-leagues' ),
				'default' => '0',
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
new AnWPFL_Shortcode_Staff();
