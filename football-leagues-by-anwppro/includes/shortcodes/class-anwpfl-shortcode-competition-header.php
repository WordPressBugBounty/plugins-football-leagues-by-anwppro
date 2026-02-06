<?php
/**
 * AnWP Football Leagues :: Shortcode > Competition Header.
 *
 * @since   0.5.1
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Competition_Header' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode > Competition Header.
 *
 * @since 0.4.3
 */
class AnWPFL_Shortcode_Competition_Header extends AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_tag(): string {
		return 'anwpfl-competition-header';
	}

	/**
	 * Get the shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_key(): string {
		return 'competition-header';
	}

	/**
	 * Get the shortcode label.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_shortcode_label(): string {
		return __( 'Competition Header', 'anwp-football-leagues' );
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
		return 'competition_header';
	}

	/**
	 * Get default attribute values.
	 *
	 * @return array
	 * @since 0.17.0
	 */
	protected function get_defaults(): array {
		return [
			'id'              => '',
			'title'           => '',
			'title_field'     => '',
			'title_as_link'   => 0,
			'season_selector' => 0,
			'transparent_bg'  => 0,
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
				'name'     => 'id',
				'type'     => 'selector',
				'entity'   => 'main_stage',
				'multiple' => false,
				'label'    => __( 'Competition ID', 'anwp-football-leagues' ),
			],
			[
				'name'    => 'title_as_link',
				'type'    => 'yes_no',
				'label'   => __( 'Title as a link', 'anwp-football-leagues' ),
				'default' => '0',
			],
			[
				'name'    => 'title_field',
				'type'    => 'select',
				'label'   => __( 'Competition Title in Competition Header', 'anwp-football-leagues' ),
				'default' => '',
				'options' => [
					''            => __( 'League Name', 'anwp-football-leagues' ),
					'competition' => __( 'Competition Title', 'anwp-football-leagues' ),
				],
			],
			[
				'name'  => 'title',
				'type'  => 'text',
				'label' => __( 'Custom Title', 'anwp-football-leagues' ),
			],
		];
	}
}

// Bump
new AnWPFL_Shortcode_Competition_Header();
