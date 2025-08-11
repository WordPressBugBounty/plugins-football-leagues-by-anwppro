<?php
/*
CMB2 Field Type: AnWP FL Selector
Version: 1.0.0
License: GPLv2+
*/

add_action( 'cmb2_render_anwp_fl_selector', 'cmb2_render_anwp_fl_selector', 10, 2 );
add_filter( 'cmb2_sanitize_anwp_fl_selector', 'cmb2_sanitize_anwp_fl_selector', 10, 2 );

if ( ! function_exists( 'cmb2_render_anwp_fl_selector' ) ) {

	/**
	 * Render Simple Trigger field
	 *
	 * @param $field
	 * @param $value
	 */
	function cmb2_render_anwp_fl_selector( $field, $value ) {
		?>
		<div class="anwp-x-selector" fl-x-data="selectorItem('<?php echo sanitize_key( $field->args['fl_context'] ); ?>',<?php echo sanitize_key( $field->args['fl_single'] ); ?>)" fl-x-cloak>
			<input fl-x-model.fill="selected" class="w-100" name="<?php echo esc_attr( $field->args['id'] ); ?>" type="text" value="<?php echo esc_html( $value ); ?>">

			<button fl-x-on:click="openModal()" type="button" class="button"><span class="dashicons dashicons-search"></span></button>
		</div>
		<?php
	}
}

if ( ! function_exists( 'cmb2_sanitize_anwp_fl_selector' ) ) {
	/**
	 * Sanitize Text field
	 *
	 * @param $deprecated
	 * @param $field_value
	 *
	 * @return string
	 */
	function cmb2_sanitize_anwp_fl_selector( $deprecated, $field_value ): string {

		return sanitize_text_field( $field_value );
	}
}
