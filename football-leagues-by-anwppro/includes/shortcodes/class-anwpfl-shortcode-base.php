<?php
/**
 * Abstract base class for all shortcodes.
 *
 * Provides common functionality for shortcode registration, rendering,
 * and form generation to reduce code duplication across shortcode classes.
 *
 * @since   0.17.0
 * @package AnWP_Football_Leagues
 */

if ( class_exists( 'AnWPFL_Shortcode_Base' ) ) {
	return;
}

/**
 * AnWP Football Leagues :: Shortcode Base.
 *
 * @since 0.17.0
 */
abstract class AnWPFL_Shortcode_Base {

	/**
	 * Get the shortcode tag (e.g., 'anwpfl-matches').
	 *
	 * @return string
	 * @since 0.17.0
	 */
	abstract protected function get_shortcode_tag(): string;

	/**
	 * Get the shortcode key for options/hooks (e.g., 'matches').
	 *
	 * @return string
	 * @since 0.17.0
	 */
	abstract protected function get_shortcode_key(): string;

	/**
	 * Get the shortcode label for UI (e.g., 'Matches').
	 *
	 * @return string
	 * @since 0.17.0
	 */
	abstract protected function get_shortcode_label(): string;

	/**
	 * Get default attribute values.
	 *
	 * @return array
	 * @since 0.17.0
	 */
	abstract protected function get_defaults(): array;

	/**
	 * Get documentation URL for this shortcode.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	abstract protected function get_docs_url(): string;

	/**
	 * Get form field definitions.
	 * Override in child class to use declarative field system.
	 *
	 * @return array
	 * @since 0.17.0
	 */
	protected function get_form_fields(): array {
		return [];
	}

	/**
	 * Whether this shortcode supports live preview.
	 *
	 * Override in child class to disable preview for shortcodes
	 * that don't render correctly in iframe (e.g., swiper-based).
	 *
	 * @return bool
	 * @since 0.17.0
	 */
	protected function supports_preview(): bool {
		return true;
	}

	/**
	 * Get default preview width for this shortcode.
	 *
	 * Override in child class to set narrower width (400) for single-item
	 * displays (player, club, match). Default is 700 for lists/tables.
	 *
	 * @return int Width in pixels (400 or 700).
	 * @since 0.17.0
	 */
	protected function get_default_preview_width(): int {
		return 700;
	}

	/**
	 * Validate and sanitize attributes.
	 * Override in child class for custom validation.
	 *
	 * @param array $atts Raw attributes.
	 *
	 * @return array Validated attributes.
	 * @since 0.17.0
	 *
	 */
	protected function validate_atts( array $atts ): array {
		return $atts;
	}

	/**
	 * Get template name for rendering.
	 * Defaults to shortcode key.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_template_name(): string {
		return $this->get_shortcode_key();
	}

	/**
	 * Get the hook namespace for this shortcode type.
	 * Override in premium for 'anwpfl/shortcode-pro'.
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_hook_namespace(): string {
		return 'anwpfl/shortcode';
	}

	/**
	 * Get prefix for option labels (e.g., emoji for premium).
	 *
	 * @return string
	 * @since 0.17.0
	 */
	protected function get_option_prefix(): string {
		return '';
	}

	/**
	 * Constructor - registers hooks using namespace.
	 *
	 * @since 0.17.0
	 */
	public function __construct() {
		$namespace = $this->get_hook_namespace();
		$key       = $this->get_shortcode_key();

		add_action( 'init', [ $this, 'register_shortcode' ] );
		add_action( $namespace . '/get_shortcode_form_' . $key, [ $this, 'load_shortcode_form' ] );
		add_filter( $namespace . '/get_shortcode_options', [ $this, 'add_shortcode_option' ] );
	}

	/**
	 * Register the shortcode.
	 *
	 * @since 0.17.0
	 */
	public function register_shortcode(): void {
		add_shortcode( $this->get_shortcode_tag(), [ $this, 'render_shortcode' ] );
	}

	/**
	 * Add this shortcode to the selector dropdown.
	 *
	 * @param array $data Existing options.
	 *
	 * @return array Modified options.
	 * @since 0.17.0
	 *
	 */
	public function add_shortcode_option( array $data ): array {
		$data[ $this->get_shortcode_key() ] = $this->get_option_prefix() . $this->get_shortcode_label();

		return $data;
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array|string $atts Shortcode attributes.
	 *
	 * @return string Rendered output.
	 * @since 0.17.0
	 *
	 */
	public function render_shortcode( $atts ): string {
		$atts = shortcode_atts( $this->get_defaults(), (array) $atts, $this->get_shortcode_tag() );
		$atts = $this->validate_atts( $atts );

		return anwp_football_leagues()->template->shortcode_loader( $this->get_template_name(), $atts );
	}

	/**
	 * Render the shortcode form header with docs link.
	 *
	 * @since 0.17.0
	 */
	protected function render_form_header(): void {
		$shortcode_title = esc_html__( 'Shortcodes', 'anwp-football-leagues' ) . ' :: ' . $this->get_shortcode_label();
		anwp_football_leagues()->helper->render_docs_template( $this->get_docs_url(), $shortcode_title );
	}

	/**
	 * Render the shortcode form footer with hidden shortcode name.
	 *
	 * @since 0.17.0
	 */
	protected function render_form_footer(): void {
		printf(
			'<input type="hidden" class="fl-shortcode-name" name="fl-slug" value="%s" data-supports-preview="%s" data-preview-width="%d">',
			esc_attr( $this->get_shortcode_tag() ),
			$this->supports_preview() ? 'true' : 'false',
			(int) $this->get_default_preview_width()
		);
	}

	/**
	 * Field renderer instance.
	 *
	 * @since 0.17.0
	 * @var AnWPFL_Shortcode_Field_Renderer|null
	 */
	protected static ?AnWPFL_Shortcode_Field_Renderer $field_renderer = null;

	/**
	 * Get field renderer instance.
	 *
	 * @return AnWPFL_Shortcode_Field_Renderer
	 * @since 0.17.0
	 */
	protected function get_field_renderer(): AnWPFL_Shortcode_Field_Renderer {
		if ( null === self::$field_renderer ) {
			self::$field_renderer = new AnWPFL_Shortcode_Field_Renderer();
		}

		return self::$field_renderer;
	}

	/**
	 * Load shortcode form using field definitions.
	 * Override in child class to customize form rendering.
	 *
	 * @since 0.17.0
	 */
	public function load_shortcode_form(): void {
		$fields = $this->get_form_fields();

		$this->render_form_header();

		if ( ! empty( $fields ) ) {
			$fields = $this->resolve_field_options( $fields );
			$this->get_field_renderer()->render_fields( $fields );
		}

		$this->render_form_footer();
	}

	/**
	 * Resolve options_hook and options_callback to options arrays.
	 *
	 * Calls the method specified in options_hook/options_callback on this class to get options.
	 * If method exists on shortcode class, it's called directly.
	 * Otherwise, options_callback is passed to field renderer for global mapping resolution.
	 *
	 * @param array $fields Field definitions.
	 *
	 * @return array Fields with resolved options.
	 * @since 0.17.0
	 *
	 */
	protected function resolve_field_options( array $fields ): array {
		foreach ( $fields as &$field ) {
			// Handle options_hook (preferred pattern for shortcode methods).
			if ( ! empty( $field['options_hook'] ) && method_exists( $this, $field['options_hook'] ) ) {
				$field['options'] = $this->{$field['options_hook']}();
				unset( $field['options_hook'] );
			}

			// Handle options_callback if it's a method on this class.
			// Otherwise, leave it for field renderer's global mapping.
			if ( ! empty( $field['options_callback'] ) && method_exists( $this, $field['options_callback'] ) ) {
				$field['options'] = $this->{$field['options_callback']}();
				unset( $field['options_callback'] );
			}
		}

		return $fields;
	}
}
