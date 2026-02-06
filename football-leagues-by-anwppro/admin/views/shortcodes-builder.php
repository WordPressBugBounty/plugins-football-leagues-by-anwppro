<?php
/**
 * Shortcodes Builder page for AnWP Football Leagues
 *
 * Uses Alpine.js shortcodeBuilder component for reactive form handling.
 *
 * @link       https://anwp.pro
 * @since      0.10.8
 * @since      0.17.0 Refactored to use Alpine.js
 *
 * @package    AnWP_Football_Leagues
 * @subpackage AnWP_Football_Leagues/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get core shortcode options.
 *
 * Direct method call - no filter timing issues.
 *
 * @since 0.17.0
 */
$available_core_shortcodes = anwp_football_leagues()->get_shortcode_options();

/**
 * Get premium shortcode options.
 *
 * Direct method call if premium plugin is active.
 *
 * @since 0.17.0
 */
$available_premium_shortcodes = [];

if ( function_exists( 'anwp_football_leagues_premium' ) && method_exists( anwp_football_leagues_premium(), 'get_shortcode_options' ) ) {
	$available_premium_shortcodes = anwp_football_leagues_premium()->get_shortcode_options();
}

// Output localized shortcode options for Import feature
?>
<script type="text/javascript">
	window._fl_shortcodes_l10n = window._fl_shortcodes_l10n || {};
	window._fl_shortcodes_l10n.shortcode_options = <?php echo wp_json_encode( $available_core_shortcodes ); ?>;
	window._fl_shortcodes_l10n.shortcode_options_premium = <?php echo wp_json_encode( $available_premium_shortcodes ); ?>;
</script>

<div class="anwp-mb-2 anwp-pb-1">
	<h1 class="anwp-mb-0 anwp-d-flex anwp-items-center">
	<?php echo esc_html__( 'Shortcode Builder', 'anwp-football-leagues' ); ?>
		<a
			href="https://anwp.pro/docs/football-leagues/display/shortcode-builder/"
			target="_blank"
			title="Click '?' to view documentation."
			class="anwp-fl-help-header-link anwp-ml-2">?</a>
	</h1>
</div>
<div class="anwp-mb-3 anwp-d-flex anwp-items-center">
	<span class="anwp-text-gray-600"><?php echo esc_html__( 'Shortcode Builder', 'anwp-football-leagues' ); ?></span>
	<small class="anwp-text-gray-600 anwp-mx-2 anwp-d-inline-block">|</small>
	<a class="anwp-link-without-effects" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-shortcodes&tab=howto' ) ); ?>">
		<?php echo esc_html__( 'How To\'s', 'anwp-football-leagues' ); ?>
	</a>
</div>

<hr class="anwp-mb-3">

<div fl-x-data="shortcodeBuilder()">
	<!-- Shortcode Selector (full width) -->
	<div id="anwp-shortcode-builder__header" class="anwp-d-flex anwp-items-center anwp-mb-3">
		<label for="anwp-shortcode-builder__selector"><?php echo esc_html__( 'Shortcode', 'anwp-football-leagues' ); ?></label>
		<select
			id="anwp-shortcode-builder__selector"
			class="anwp-mx-2"
			fl-x-model="selectedShortcode"
			fl-x-on:change="loadForm()"
		>
			<option value="">- <?php echo esc_html__( 'select', 'anwp-football-leagues' ); ?> -</option>
			<?php foreach ( $available_core_shortcodes as $shortcode_slug => $shortcode_name ) : ?>
				<option value="<?php echo esc_attr( $shortcode_slug ); ?>"><?php echo esc_html( $shortcode_name ); ?></option>
			<?php endforeach; ?>
			<?php
			/**
			 * Hook: anwpfl/shortcodes/selector_bottom
			 *
			 * @since 0.10.8
			 */
			do_action( 'anwpfl/shortcodes/selector_bottom' );
			?>
		</select>
		<span class="spinner" fl-x-bind:class="{ 'is-active': loading }"></span>
	</div>

	<!-- Two-Column Layout -->
	<div class="fl-shortcode-builder">
		<!-- Form Column -->
		<div class="fl-shortcode-builder__form">

			<!-- Generated Shortcode Output -->
			<div id="anwp-shortcode-builder__composed" class="fl-section-card anwp-border anwp-border-gray-500 anwp-mb-4" fl-x-show="shortcodeString" fl-x-cloak>
				<div class="fl-section-card__header anwp-border-bottom anwp-border-gray-500 anwp-bg-white anwp-d-flex anwp-items-center anwp-px-3 anwp-py-2 anwp-text-gray-700">
					<span class="dashicons dashicons-shortcode anwp-dashicons-16 anwp-mr-2"></span>
					<span><?php echo esc_html__( 'Output', 'anwp-football-leagues' ); ?></span>
					<button
						type="button"
						class="button button-small button-link anwp-ml-auto"
						fl-x-on:click="resetForm()"
					><?php echo esc_html__( 'Reset', 'anwp-football-leagues' ); ?></button>
				</div>
				<div class="anwp-p-3">
					<pre
						class="anwp-shortcode-output anwp-bg-gray-100 anwp-border anwp-border-gray-400 anwp-rounded anwp-p-3 anwp-text-sm anwp-m-0"
						fl-x-text="shortcodeString"
					></pre>
				</div>
				<div class="anwp-border-top anwp-border-gray-500 anwp-px-3 anwp-py-2 anwp-d-flex anwp-align-items-center">
					<button
						type="button"
						class="button button-primary anwp-d-inline-flex anwp-items-center"
						fl-x-on:click="copyToClipboard()"
						fl-x-bind:disabled="copiedRecently"
						style="min-width: 120px; justify-content: center;"
					>
						<span class="dashicons dashicons-admin-page anwp-dashicons-16" fl-x-show="!copiedRecently"></span>
						<span class="anwp-ml-1" fl-x-show="!copiedRecently"><?php echo esc_html__( 'Copy Shortcode', 'anwp-football-leagues' ); ?></span>
						<span fl-x-show="copiedRecently" fl-x-cloak>✓ <?php echo esc_html__( 'Copied!', 'anwp-football-leagues' ); ?></span>
					</button>

					<!-- Parse Shortcode button -->
					<button
						type="button"
						class="button anwp-ml-auto anwp-d-inline-flex anwp-items-center"
						fl-x-on:click="showParseModal = true"
					>
						<span class="dashicons dashicons-upload anwp-dashicons-16"></span>
						<span class="anwp-ml-1"><?php echo esc_html__( 'Parse Shortcode', 'anwp-football-leagues' ); ?></span>
					</button>
				</div>
			</div>

			<!-- Form Container (AJAX loaded) -->
			<div
				id="anwp-shortcode-builder__content"
				class="anwp-py-3"
				fl-x-ref="formWrap"
				fl-x-html="formHtml"
				fl-x-on:input.debounce.150ms="buildShortcode()"
				fl-x-on:change="buildShortcode()"
				fl-x-on:update-x-fl-outer-wrapper.window="buildShortcode()"
			></div>
		</div>

		<!-- Preview Column -->
		<div class="fl-shortcode-builder__preview" fl-x-show="selectedShortcode" fl-x-cloak fl-x-bind:style="'width:' + previewWidth + 'px'">
			<div class="fl-preview-panel anwp-border anwp-border-gray-500 anwp-bg-white">
				<!-- Header -->
				<div class="anwp-border-bottom anwp-border-gray-500 anwp-bg-white anwp-d-flex anwp-items-center anwp-px-3 anwp-py-2 anwp-text-gray-700">
					<span class="dashicons dashicons-visibility anwp-dashicons-16 anwp-mr-2"></span>
					<span><?php echo esc_html__( 'Preview', 'anwp-football-leagues' ); ?></span>

					<!-- Width Toggle -->
					<div class="fl-preview-width-toggle anwp-ml-3" fl-x-show="previewSupported">
						<button
							type="button"
							class="button button-small"
							fl-x-bind:class="{ 'button-primary': previewWidth === 400 }"
							fl-x-on:click="setPreviewWidth(400)"
						>400</button>
						<button
							type="button"
							class="button button-small"
							fl-x-bind:class="{ 'button-primary': previewWidth === 700 }"
							fl-x-on:click="setPreviewWidth(700)"
						>700</button>
					</div>

					<button
						type="button"
						class="button button-small anwp-ml-auto"
						fl-x-on:click="refreshPreview()"
						fl-x-bind:disabled="previewLoading"
						fl-x-show="previewSupported"
					>
						<span class="dashicons dashicons-update anwp-dashicons-14" fl-x-bind:class="{ 'anwp-spin': previewLoading }"></span>
						<span class="anwp-ml-1"><?php echo esc_html__( 'Refresh', 'anwp-football-leagues' ); ?></span>
					</button>
				</div>

				<!-- Preview Accuracy Notice -->
				<div fl-x-show="previewSupported" class="anwp-px-3 anwp-py-2 anwp-bg-blue-50 anwp-border-bottom anwp-border-gray-300 anwp-text-xs anwp-text-gray-600">
					<span class="dashicons dashicons-info-outline anwp-dashicons-14 anwp-mr-1" style="vertical-align: middle;"></span>
					<?php echo esc_html__( 'Preview may differ from actual output. Some responsive styles and theme styles are not applied.', 'anwp-football-leagues' ); ?>
				</div>

				<!-- Preview Not Supported Message -->
				<div fl-x-show="!previewSupported" class="anwp-p-4 anwp-text-center anwp-text-gray-500">
					<span class="dashicons dashicons-info-outline anwp-text-gray-400" style="font-size: 32px; width: 32px; height: 32px;"></span>
					<p class="anwp-m-0 anwp-mt-2"><?php echo esc_html__( 'Preview is not available for this shortcode.', 'anwp-football-leagues' ); ?></p>
					<p class="anwp-m-0 anwp-mt-1 anwp-text-sm"><?php echo esc_html__( 'Copy the shortcode and paste it into a post or page to see the output.', 'anwp-football-leagues' ); ?></p>
				</div>

				<!-- Loading State (only when preview supported) -->
				<div fl-x-show="previewSupported && previewLoading && !previewUrl" class="anwp-p-4 anwp-text-center anwp-text-gray-500">
					<span class="spinner is-active" style="float: none;"></span>
				</div>

				<!-- Error State -->
				<div fl-x-show="previewSupported && previewError" class="anwp-p-3 anwp-bg-red-50 anwp-text-red-700 anwp-text-sm" fl-x-text="previewError"></div>

				<!-- Iframe Preview -->
				<div fl-x-show="previewSupported && previewUrl" class="fl-preview-iframe-wrap" style="position: relative;">
					<div fl-x-show="previewLoading" class="fl-preview-loading-overlay">
						<span class="spinner is-active" style="float: none;"></span>
					</div>
					<iframe
						fl-x-ref="previewFrame"
						fl-x-bind:src="previewUrl"
						fl-x-on:load="onPreviewLoad()"
						fl-x-on:error="onPreviewError()"
						style="width: 100%; min-height: 300px; border: none; display: block;"
						sandbox="allow-same-origin allow-scripts"
					></iframe>
				</div>

				<!-- Empty State (only when preview supported) -->
				<div fl-x-show="previewSupported && !previewUrl && !previewLoading && !previewError" class="anwp-p-3 anwp-text-gray-500 anwp-text-sm">
					<p class="anwp-m-0"><?php echo esc_html__( 'Fill in the form fields to see a live preview.', 'anwp-football-leagues' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Parse Shortcode Modal -->
	<template fl-x-teleport="body">
		<div
			fl-x-show="showParseModal"
			fl-x-cloak
			class="fl-import-modal-overlay"
			fl-x-on:click.self="showParseModal = false"
			fl-x-on:keydown.escape.window="showParseModal = false"
		>
			<div class="fl-import-modal" fl-x-on:click.stop>
				<div class="fl-import-modal__header">
					<h3 class="anwp-m-0"><?php echo esc_html__( 'Parse Shortcode', 'anwp-football-leagues' ); ?></h3>
					<button type="button" class="fl-import-modal__close" fl-x-on:click="showParseModal = false">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="fl-import-modal__body">
					<p class="description anwp-m-0 anwp-mb-2"><?php echo esc_html__( 'Paste an existing shortcode to load its attribute values into the form.', 'anwp-football-leagues' ); ?></p>
					<textarea
						fl-x-model="parseText"
						placeholder='[anwpfl-matches competition_id="5" limit="10"]'
						class="large-text code"
						rows="3"
						fl-x-on:keydown.ctrl.enter="parseShortcodeText(parseText)"
						fl-x-on:keydown.meta.enter="parseShortcodeText(parseText)"
					></textarea>
					<p fl-x-show="parseError" fl-x-cloak class="fl-import-error anwp-m-0 anwp-mt-2" fl-x-text="parseError"></p>
					<p fl-x-show="parseWarning" fl-x-cloak class="fl-import-warning anwp-m-0 anwp-mt-2" fl-x-text="parseWarning"></p>
				</div>
				<div class="fl-import-modal__footer">
					<button type="button" class="button" fl-x-on:click="showParseModal = false; parseWarning = ''; parsedAttrs = null;">
						<?php echo esc_html__( 'Cancel', 'anwp-football-leagues' ); ?>
					</button>

					<!-- Parse button (hidden when warning shown) -->
					<button
						fl-x-show="!parseWarning"
						type="button"
						class="button button-primary"
						fl-x-on:click="parseShortcodeText(parseText)"
						fl-x-bind:disabled="!parseText.trim()"
					>
						<?php echo esc_html__( 'Parse Shortcode', 'anwp-football-leagues' ); ?>
					</button>

					<!-- Continue Anyway button (shown when warning) -->
					<button
						fl-x-show="parseWarning"
						fl-x-cloak
						type="button"
						class="button button-primary"
						fl-x-on:click="confirmParse()"
					>
						<?php echo esc_html__( 'Continue Anyway', 'anwp-football-leagues' ); ?>
					</button>
				</div>
			</div>
		</div>
	</template>
</div>
