<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

// phpcs:disable Universal.Files.SeparateFunctionsFromOO

if ( class_exists( 'WP_Customize_Control' ) ) {

	class AnWPFL_Simple_HTML_Custom_Control extends WP_Customize_Control {
		public $type = 'anwp_fl_simple_html';

		public function render_content() {
			?>
			<div class="anwp-fl-simple-html-custom-control">
				<?php if ( ! empty( $this->label ) ) : ?>
					<div class="anwp-fl-simple-html-control-title"><?php echo esc_html( $this->label ); ?></div>
				<?php endif; ?>
				<?php if ( ! empty( $this->description ) ) : ?>
					<div class="anwp-fl-simple-html-control-description"><?php echo wp_kses_post( $this->description ); ?></div>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	class AnWPFL_Subheader_Control extends WP_Customize_Control { // phpcs:ignore
		public $type = 'anwp_fl_subheader';

		public function render_content() {
			?>
			<div class="anwp-fl-subheader-control">
				<?php if ( ! empty( $this->label ) ) : ?>
					<div class="py-2 px-3 anwp-bg-info-light anwp-text-base mt-3 mx-n3"><?php echo esc_html( $this->label ); ?></div>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	/**
	 * Pill Checkbox Custom Control
	 * Based on https://github.com/maddisondesigns/customizer-custom-controls/blob/master/inc/custom-controls.php
	 */
	class AnWPFL_Toggle_Switch_Custom_Control extends WP_Customize_Control { //phpcs:ignore
		public $type = 'anwp_fl_toggle_switch';

		/**
		 * Enqueue our scripts and styles
		 */
		public function enqueue() {
			wp_enqueue_style(
				'anwp-fl-custom-controls-css',
				AnWP_Football_Leagues::url( 'admin/css/customizer.css' ),
				[],
				ANWP_FL_VERSION
			);

			wp_enqueue_script(
				'anwp-fl-custom-controls-js',
				AnWP_Football_Leagues::url( 'admin/js/customizer.js' ),
				[
					'jquery',
					'jquery-ui-core',
				],
				ANWP_FL_VERSION,
				true
			);
		}

		/**
		 * Render the control in the customizer
		 */
		public function render_content() {
			?>
			<div class="anwp-toggle-switch-control">
				<div class="anwp-toggle-switch">
					<input
						type="checkbox"
						id="<?php echo esc_attr( $this->id ); ?>"
						name="<?php echo esc_attr( $this->id ); ?>" class="anwp-toggle-switch-checkbox"
						data-anwp-dependent-controls="<?php echo esc_attr( implode( ',', $this->input_attrs['dependent_controls'] ?? [] ) ); ?>"
						value="<?php echo esc_attr( $this->value() ); ?>"
						<?php $this->link(); ?> <?php checked( $this->value() ); ?>>
					<label class="anwp-toggle-switch-label" for="<?php echo esc_attr( $this->id ); ?>">
						<span class="anwp-toggle-switch-inner"></span>
						<span class="anwp-toggle-switch-switch"></span>
					</label>
				</div>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php if ( ! empty( $this->description ) ) : ?>
					<span class="customize-control-description"><?php echo esc_html( $this->description ); ?></span>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	/**
	 * Pill Checkbox Custom Control
	 * Based on https://github.com/maddisondesigns/customizer-custom-controls/blob/master/inc/custom-controls.php
	 */
	class AnWPFL_Pill_Checkbox_Custom_Control extends WP_Customize_Control { //phpcs:ignore
		public $type = 'anwp_fl_pill_checkbox';

		/**
		 * @var bool Whether the pill checkbox is sortable. Default = false
		 */
		private bool $sortable;

		/**
		 * @var bool Whether the pill checkbox is fullwidth. Default = false
		 */
		private bool $fullwidth;

		/**
		 * Constructor
		 */
		public function __construct( $manager, $id, $args = [], $options = [] ) {
			parent::__construct( $manager, $id, $args );
			$this->sortable  = boolval( $this->input_attrs['sortable'] ?? false );
			$this->fullwidth = boolval( $this->input_attrs['fullwidth'] ?? false );
		}

		/**
		 * Enqueue our scripts and styles
		 */
		public function enqueue() {
			wp_enqueue_style(
				'anwp-fl-custom-controls-css',
				AnWP_Football_Leagues::url( 'admin/css/customizer.css' ),
				[],
				ANWP_FL_VERSION
			);

			wp_enqueue_script(
				'anwp-fl-custom-controls-js',
				AnWP_Football_Leagues::url( 'admin/js/customizer.js' ),
				[
					'jquery',
					'jquery-ui-core',
				],
				ANWP_FL_VERSION,
				true
			);
		}

		/**
		 * Render the control in the customizer
		 */
		public function render_content() {
			$reordered_choices = [];
			$saved_choices     = explode( ',', esc_attr( $this->value() ) );

			// Order the checkbox choices based on the saved order
			if ( $this->sortable ) {
				foreach ( $saved_choices as $key => $value ) {
					if ( isset( $this->choices[ $value ] ) ) {
						$reordered_choices[ $value ] = $this->choices[ $value ];
					}
				}
				$reordered_choices = array_merge( $reordered_choices, array_diff_assoc( $this->choices, $reordered_choices ) );
			} else {
				$reordered_choices = $this->choices;
			}
			?>
			<div class="anwp-pill_checkbox_control">
				<?php if ( ! empty( $this->label ) ) { ?>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php } ?>
				<?php if ( ! empty( $this->description ) ) { ?>
					<span class="customize-control-description"><?php echo esc_html( $this->description ); ?></span>
				<?php } ?>
				<input type="hidden" id="<?php echo esc_attr( $this->id ); ?>" name="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $this->value() ); ?>" class="customize-control-sortable-pill-checkbox" <?php $this->link(); ?> />
				<div class="sortable_pills<?php echo ( $this->sortable ? ' sortable' : '' ) . ( $this->fullwidth ? ' fullwidth_pills' : '' ); ?>">
					<?php foreach ( $reordered_choices as $key => $value ) { ?>
						<label class="checkbox-label">
							<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( esc_attr( $key ), $saved_choices, true ), true ); ?>
									class="sortable-pill-checkbox" />
							<span class="sortable-pill-title"><?php echo esc_html( $value ); ?></span>
							<?php if ( $this->sortable && $this->fullwidth ) { ?>
								<span class="dashicons dashicons-sort"></span>
							<?php } ?>
						</label>
					<?php } ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Text sanitization
	 *
	 * @param string $input Input to be sanitized (either a string containing a single string or multiple, separated by commas)
	 *
	 * @return string    Sanitized input
	 */
	if ( ! function_exists( 'anwp_customizer_text_sanitization' ) ) {
		function anwp_customizer_text_sanitization( $input ): string {
			if ( strpos( $input, ',' ) !== false ) {
				$input = explode( ',', $input );
			}
			if ( is_array( $input ) ) {
				foreach ( $input as $key => $value ) {
					$input[ $key ] = sanitize_text_field( $value );
				}
				$input = implode( ',', $input );
			} else {
				$input = sanitize_text_field( $input );
			}

			return $input;
		}
	}
}
