<?php
/**
 * Shortcode form field renderer.
 *
 * Renders form fields for shortcode builder based on declarative field definitions.
 * Reduces HTML duplication across shortcode classes.
 *
 * @since   0.17.0
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Field_Renderer' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode Field Renderer.
 *
 * @since 0.17.0
 */
class AnWPFL_Shortcode_Field_Renderer {

	/**
	 * Track if a section card is currently open.
	 *
	 * @since 0.17.0
	 * @var bool
	 */
	protected $section_open = false;

	/**
	 * Collect field descriptions for section footer.
	 *
	 * @since 0.17.0
	 * @var array
	 */
	protected $section_descriptions = [];

	/**
	 * Render a single field.
	 *
	 * @param array $field Field definition.
	 *
	 * @since 0.17.0
	 *
	 */
	public function render_field( array $field ): void {
		$method = 'render_' . $field['type'];

		if ( ! method_exists( $this, $method ) ) {
			return;
		}

		// Section headers have their own wrapper.
		if ( 'section_header' === $field['type'] ) {
			$this->$method( $field );

			return;
		}

		// Vue match edit pattern: vertical stack (label + input) that flows inline with siblings
		echo '<div class="fl-field anwp-d-flex anwp-flex-col anwp-mb-4 anwp-m-2">';
		$this->render_label( $field );
		$this->$method( $field );
		echo '</div>';

		// Collect description for section footer instead of inline.
		if ( ! empty( $field['description'] ) ) {
			$this->section_descriptions[] = [
				'label'       => $field['label'],
				'description' => $field['description'],
			];
		}
	}

	/**
	 * Render multiple fields with modern flex layout.
	 *
	 * All fields are rendered inside section cards. If no section_header is defined
	 * or fields appear before the first section_header, they are wrapped in an
	 * implicit "General" section.
	 *
	 * @param array $fields Array of field definitions.
	 *
	 * @since 0.17.0
	 *
	 */
	public function render_fields( array $fields ): void {
		$this->section_open = false;

		echo '<div class="fl-shortcode-fields">';

		// Check if first field is section_header.
		$first_is_section = isset( $fields[0]['type'] ) && 'section_header' === $fields[0]['type'];

		// Create implicit "General" section if fields exist before first section_header.
		if ( ! $first_is_section && ! empty( $fields ) ) {
			$this->render_section_header( [
				'type'  => 'section_header',
				'label' => __( 'General', 'anwp-football-leagues' ),
			] );
		}

		foreach ( $fields as $field ) {
			$this->render_field( $field );
		}

		// Close final section if opened.
		if ( $this->section_open ) {
			$this->render_section_descriptions();
			echo '</div></div>'; // content + section card
		}

		echo '</div>';
	}

	/**
	 * Render field label.
	 *
	 * @param array $field Field definition.
	 *
	 * @since 0.17.0
	 *
	 */
	protected function render_label( array $field ): void {
		printf(
			'<label for="fl-form-shortcode-%s">%s</label>',
			esc_attr( $field['name'] ),
			esc_html( $field['label'] )
		);
	}

	/**
	 * Render collected descriptions at section footer.
	 *
	 * @since 0.17.0
	 */
	protected function render_section_descriptions(): void {
		if ( empty( $this->section_descriptions ) ) {
			return;
		}

		echo '<div class="fl-section-descriptions w-100 anwp-mt-3 anwp-pt-3 px-2 anwp-border-top anwp-border-gray-400">';

		foreach ( $this->section_descriptions as $item ) {
			printf(
				'<div class="fl-section-description"><strong>%s:</strong> %s</div>',
				esc_html( $item['label'] ),
				esc_html( $item['description'] )
			);
		}

		echo '</div>';
	}

	/**
	 * Render section header as card (Vue match edit pattern).
	 *
	 * Creates bordered card with header bar containing icon and collapse toggle.
	 * Content area uses flex-wrap for fluid field layout.
	 *
	 * @param array $field Field definition.
	 *
	 * @since 0.17.0
	 *
	 */
	protected function render_section_header( array $field ): void {
		// Close previous section if open.
		if ( $this->section_open ) {
			$this->render_section_descriptions();
			echo '</div></div>'; // content + section card
		}

		// Reset descriptions for new section.
		$this->section_descriptions = [];

		$label = str_replace( '>> ', '', $field['label'] ?? '' );
		$icon  = $this->get_section_icon( $label );

		// Section card wrapper with border (border color in global.css for TinyMCE compatibility).
		echo '<div class="fl-section-card" fl-x-data="{ collapsed: false }">';

		// Header bar (clickable) - border/background in CSS for TinyMCE compatibility.
		printf(
			'<button type="button" class="fl-section-card__header d-flex align-items-center px-3 py-2 anwp-cursor-pointer" fl-x-on:click="collapsed = !collapsed">
				<span class="dashicons %s anwp-dashicons-16 mr-2"></span>
				<span>%s</span>
				<span class="dashicons anwp-dashicons-14 ml-auto" fl-x-bind:class="collapsed ? \'dashicons-arrow-down-alt2\' : \'dashicons-arrow-up-alt2\'"></span>
			</button>',
			esc_attr( $icon ),
			esc_html( $label )
		);

		// Content area with flex-wrap for fluid field layout.
		echo '<div class="fl-section-card__content anwp-d-flex--noimp anwp-flex-wrap--noimp anwp-items-start--noimp" fl-x-show="!collapsed">';

		$this->section_open = true;
	}

	/**
	 * Get dashicon class for section based on label keywords.
	 *
	 * @param string $label Section label.
	 *
	 * @return string Dashicon class.
	 * @since 0.17.0
	 *
	 */
	protected function get_section_icon( string $label ): string {
		$label_lower = strtolower( $label );

		// Match common section names.
		if ( false !== strpos( $label_lower, 'general' ) || false !== strpos( $label_lower, 'basic' ) ) {
			return 'dashicons-admin-settings';
		}

		if ( false !== strpos( $label_lower, 'query' ) || false !== strpos( $label_lower, 'data' ) || false !== strpos( $label_lower, 'filter' ) ) {
			return 'dashicons-filter';
		}

		if ( false !== strpos( $label_lower, 'display' ) || false !== strpos( $label_lower, 'layout' ) ) {
			return 'dashicons-layout';
		}

		if ( false !== strpos( $label_lower, 'style' ) || false !== strpos( $label_lower, 'styling' ) ) {
			return 'dashicons-art';
		}

		if ( false !== strpos( $label_lower, 'advanced' ) || false !== strpos( $label_lower, 'option' ) ) {
			return 'dashicons-admin-generic';
		}

		return 'dashicons-menu-alt'; // Default
	}

	/**
	 * Get width class for field input.
	 *
	 * Supports explicit 'width' property in field definition to override defaults.
	 * Valid values: 'narrow', 'medium', 'wide', 'multiselect', 'selector'.
	 *
	 * @param array  $field         Field definition.
	 * @param string $default_width Default width key (e.g., 'medium', 'narrow').
	 *
	 * @return string CSS class name.
	 * @since 0.17.0
	 *
	 */
	protected function get_width_class( array $field, string $default_width = 'medium' ): string {
		$width = $field['width'] ?? $default_width;

		return 'fl-shortcode-attr--' . $width;
	}

	/**
	 * Render text input.
	 *
	 * @param array $field Field definition.
	 *
	 * @since 0.17.0
	 *
	 */
	protected function render_text( array $field ): void {
		$readonly    = ! empty( $field['readonly'] ) ? ' readonly disabled' : '';
		$width_class = $this->get_width_class( $field, 'medium' );

		printf(
			'<input name="%s" data-fl-type="text" type="text" id="fl-form-shortcode-%s" value="%s" class="fl-shortcode-attr %s code"%s>',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $field['default'] ?? '' ),
			esc_attr( $width_class ),
			$readonly
		);
	}

	/**
	 * Render number input.
	 *
	 * @param array $field Field definition.
	 *
	 * @since 0.17.0
	 *
	 */
	protected function render_number( array $field ): void {
		printf(
			'<input name="%s" data-fl-type="text" type="number" id="fl-form-shortcode-%s" value="%s" class="fl-shortcode-attr fl-shortcode-attr--narrow code">',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $field['default'] ?? '0' )
		);
	}

	/**
	 * Render select dropdown.
	 *
	 * @param array $field Field definition.
	 *
	 * @since 0.17.0
	 *
	 */
	protected function render_select( array $field ): void {
		$width_class = $this->get_width_class( $field, 'medium' );

		printf(
			'<select name="%s" data-fl-type="select" id="fl-form-shortcode-%s" class="postform fl-shortcode-attr %s">',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $width_class )
		);

		$default = $field['default'] ?? '';
		$options = $field['options'] ?? [];

		foreach ( $options as $value => $label ) {
			$selected = ( (string) $default === (string) $value ) ? ' selected' : '';
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				$selected,
				esc_html( $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Render yes/no dropdown.
	 *
	 * @param array $field Field definition.
	 *
	 * @since 0.17.0
	 *
	 */
	protected function render_yes_no( array $field ): void {
		$default  = $field['default'] ?? '0';
		$yes_text = esc_html__( 'Yes', 'anwp-football-leagues' );
		$no_text  = esc_html__( 'No', 'anwp-football-leagues' );

		printf(
			'<select name="%s" data-fl-type="select" id="fl-form-shortcode-%s" class="postform fl-shortcode-attr fl-shortcode-attr--narrow">',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] )
		);

		printf( '<option value="1"%s>%s</option>', '1' === (string) $default ? ' selected' : '', $yes_text );
		printf( '<option value="0"%s>%s</option>', '0' === (string) $default ? ' selected' : '', $no_text );

		echo '</select>';
	}

	/**
	 * Render Tom Select dropdown.
	 *
	 * Supports 'sortable' => true for drag-drop reordering (sections, columns).
	 *
	 * @param array $field Field definition.
	 *
	 * @since 0.17.0
	 *
	 */
	protected function render_tom_select( array $field ): void {
		$options = [];

		if ( ! empty( $field['options_callback'] ) ) {
			$options = $this->get_dynamic_options( $field['options_callback'] );
		} elseif ( ! empty( $field['options'] ) ) {
			$options = $field['options'];
		}

		$is_multiple   = ! empty( $field['multiple'] );
		$multiple      = $is_multiple ? ' multiple' : '';
		$sortable      = ! empty( $field['sortable'] ) ? ' data-fl-sortable="true"' : '';
		$default_width = $is_multiple ? 'multiselect' : 'medium';
		$width_class   = $this->get_width_class( $field, $default_width );

		printf(
			'<select name="%s" data-fl-type="tom_select" id="fl-form-shortcode-%s" class="postform fl-shortcode-attr %s fl-shortcode-tom-select"%s%s>',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $width_class ),
			$multiple,
			$sortable
		);

		// Placeholder is handled by shortcode option methods (include '' => '- select -').
		// Field renderer doesn't add placeholder - let shortcode control it.

		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%s">%s</option>',
				esc_attr( $value ),
				esc_html( $label )
			);
		}

		// Allow premium to add additional options.
		if ( ! empty( $field['options_hook'] ) ) {
			do_action( $field['options_hook'] );
		}

		echo '</select>';
	}

	/**
	 * Render entity selector.
	 *
	 * @param array $field Field definition.
	 *
	 * @since 0.17.0
	 *
	 */
	protected function render_selector( array $field ): void {
		$entity   = $field['entity'] ?? 'club';
		$multiple = empty( $field['multiple'] ) ? 'true' : 'false';

		printf(
			'<div class="anwp-x-selector" fl-x-data="selectorItem(\'%s\',%s)">',
			esc_attr( $entity ),
			$multiple
		);

		printf(
			'<input name="%s" id="fl-form-shortcode-%s" data-fl-type="text" fl-x-model="selected" type="text" class="fl-shortcode-attr fl-shortcode-attr--selector code" value="" />',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] )
		);

		echo '<button fl-x-on:click="openModal()" type="button" class="button anwp-ml-1 postform">';
		echo '<span class="dashicons dashicons-search"></span>';
		echo '</button></div>';
	}

	/**
	 * Get dynamic options from callback.
	 *
	 * @param string $callback Callback key.
	 *
	 * @return array
	 * @since 0.17.0
	 *
	 */
	protected function get_dynamic_options( string $callback ): array {
		$mapping = [
			'get_stadiums'     => [ anwp_football_leagues()->stadium, 'get_stadiums_options' ],
			'get_standings'    => [ anwp_football_leagues()->standing, 'get_standing_options' ],
			'get_competitions' => [ anwp_football_leagues()->competition, 'get_competition_options' ],
			'get_seasons'      => [ anwp_football_leagues()->season, 'get_seasons_options' ],
			'get_leagues'      => [ anwp_football_leagues()->league, 'get_leagues_options' ],
			'get_clubs'        => [ anwp_football_leagues()->club, 'get_clubs_options' ],
		];

		/**
		 * Filter dynamic options callbacks.
		 * Allows premium to add custom callbacks.
		 *
		 * @param array $mapping Callback mapping.
		 *
		 * @since 0.17.0
		 *
		 */
		$mapping = apply_filters( 'anwpfl/shortcode/field_options_callbacks', $mapping );

		if ( isset( $mapping[ $callback ] ) && is_callable( $mapping[ $callback ] ) ) {
			return call_user_func( $mapping[ $callback ] );
		}

		return [];
	}
}
