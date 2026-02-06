<?php
/**
 * AnWP Football Leagues :: Shortcode > Clubs.
 *
 * @since   0.4.3
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Clubs' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Clubs.
 *
 * @since 0.4.3
 */
class AnWPFL_Shortcode_Clubs extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-clubs';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'clubs';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Clubs', 'anwp-football-leagues' );
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
			'logo_size'      => 'big',
			'layout'         => '',
			'logo_height'    => '50px',
			'logo_width'     => '50px',
			'exclude_ids'    => '',
			'include_ids'    => '',
			'show_club_name' => false,
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
				'name'     => 'competition_id',
				'type'     => 'selector',
				'entity'   => 'competition',
				'multiple' => false,
				'label'    => __( 'Competition ID', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'include_ids',
				'type'        => 'selector',
				'entity'      => 'club',
				'multiple'    => true,
				'label'       => __( 'Include Clubs', 'anwp-football-leagues' ),
				'description' => __( 'comma-separated list of IDs', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'exclude_ids',
				'type'        => 'selector',
				'entity'      => 'club',
				'multiple'    => true,
				'label'       => __( 'Exclude Clubs', 'anwp-football-leagues' ),
				'description' => __( 'comma-separated list of IDs', 'anwp-football-leagues' ),
			],

			// == Logo Settings Section ==
			[
				'type'  => 'section_header',
				'label' => __( 'Logo Settings', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'layout',
				'type'    => 'select',
				'label'   => __( 'Layout', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''     => __( 'Custom Height and Width', 'anwp-football-leagues' ),
					'2col' => __( '2 Columns', 'anwp-football-leagues' ),
					'3col' => __( '3 Columns', 'anwp-football-leagues' ),
					'4col' => __( '4 Columns', 'anwp-football-leagues' ),
					'6col' => __( '6 Columns', 'anwp-football-leagues' ),
				],
			],
			[
				'name'    => 'logo_size',
				'type'    => 'select',
				'label'   => __( 'Logo Size', 'anwp-football-leagues' ),
				'default' => 'big',
				'options' => [
					'small' => __( 'Small', 'anwp-football-leagues' ),
					'big'   => __( 'Big', 'anwp-football-leagues' ),
				],
			],
			[
				'name'        => 'logo_height',
				'type'        => 'text',
				'label'       => __( 'Logo Height', 'anwp-football-leagues' ),
				'default'     => '50px',
				'description' => __( 'Height value with units. Example: "50px" or "3rem".', 'anwp-football-leagues' ),
			],
			[
				'name'        => 'logo_width',
				'type'        => 'text',
				'label'       => __( 'Logo Width', 'anwp-football-leagues' ),
				'default'     => '50px',
				'description' => __( 'Width value with units. Example: "50px" or "3rem".', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'show_club_name',
				'type'    => 'yes_no',
				'label'   => __( 'Show club name', 'anwp-football-leagues' ),
				'default' => '0',
			],
		];
	}
}

// Bump
new AnWPFL_Shortcode_Clubs();
