<?php
/**
 * AnWP Football Leagues :: Shortcode > Standing.
 *
 * @since   0.3.0
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Standing' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Standing.
 *
 * @since 0.3.0
 */
class AnWPFL_Shortcode_Standing extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-standing';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'standing';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Standing Table', 'anwp-football-leagues' );
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
			'title'         => '',
			'id'            => '',
			'exclude_ids'   => '',
			'layout'        => '',
			'partial'       => '',
			'bottom_link'   => '',
			'link_text'     => '',
			'wrapper_class' => '',
			'show_notes'    => 1,
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
				'name'  => 'title',
				'type'  => 'text',
				'label' => __( 'Title', 'anwp-football-leagues' ),
			],
			[
				'name'             => 'id',
				'type'             => 'tom_select',
				'label'            => __( 'Standing Table', 'anwp-football-leagues' ),
				'options_callback' => 'get_standings',
				'width'            => 'wide',
			],
			[
				'name'        => 'exclude_ids',
				'type'        => 'selector',
				'entity'      => 'club',
				'multiple'    => true,
				'label'       => __( 'Exclude Clubs', 'anwp-football-leagues' ),
				'description' => __( 'comma-separated list of IDs', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'partial',
				'type'        => 'text',
				'label'       => __( 'Show Partial Data', 'anwp-football-leagues' ),
				'description' => __( 'Eg.: "1-5" (show teams from 1 to 5 place), "45" - show table slice with specified team ID in the middle', 'anwp-football-leagues' ),
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
					''     => __( 'default', 'anwp-football-leagues' ),
					'mini' => __( 'mini', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'show_notes',
				'type'    => 'yes_no',
				'label'   => __( 'Show Notes', 'anwp-football-leagues' ),
				'default' => '1',
			],

			// == Links Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Links', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'bottom_link',
				'type'    => 'select',
				'label'   => __( 'Show link to', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''            => __( 'none', 'anwp-football-leagues' ),
					'competition' => __( 'competition', 'anwp-football-leagues' ),
					'standing'    => __( 'standing', 'anwp-football-leagues' ),
				],
			],
			[
				'name'  => 'link_text',
				'type'  => 'text',
				'label' => __( 'Alternative bottom link text', 'anwp-football-leagues' ),
			],
		];
	}
}

// Bump
new AnWPFL_Shortcode_Standing();
