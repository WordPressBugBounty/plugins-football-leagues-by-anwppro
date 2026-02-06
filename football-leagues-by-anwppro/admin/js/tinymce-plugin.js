/* eslint-disable camelcase */

/**
 * TinyMCE Shortcode Plugin
 *
 * Uses shared Alpine.js shortcodeBuilder component.
 *
 * @since 0.10.8
 * @since 0.17.0 Refactored to use Alpine.js shortcodeBuilder component
 */

// Initialize plugin object
window.FootballLeaguesShortcodeButton = window.FootballLeaguesShortcodeButton || {};

// Localization vars
window._fl_shortcodes_l10n = window._fl_shortcodes_l10n || {};

( function( window, document, $, plugin ) {
	'use strict';

	var $c = {};

	plugin.init = function() {
		plugin.cache();
		plugin.bindEvents();

		tinymce.create( 'tinymce.plugins.football_leagues_button', {
			init: function( editor ) {
				editor.addButton( 'football_leagues', {
					title: _fl_shortcodes_l10n.football_leagues,
					icon: 'icon anwpfl-button-icon',
					classes: 'anwpfl-shortcode-modal-bump',
					onclick: function() {
						if ( $.fn.modaal && $c.body.hasClass( 'block-editor-page' ) ) {
							$c.modal.modaal( 'open' );
						}
					}
				} );
			},
			createControl: function() {
				return null;
			}
		} );

		tinymce.PluginManager.add( 'football_leagues_button', tinymce.plugins.football_leagues_button );
	};

	plugin.cache = function() {
		$c.window = $( window );
		$c.body = $( document.body );
		$c.xhr = null;
	};

	plugin.bindEvents = function() {
		if ( document.readyState === 'complete' ) {
			plugin.onPageReady();
		} else {
			window.onload = plugin.onPageReady;
		}
	};

	plugin.onPageReady = function() {
		if ( ! $.fn.modaal ) {
			return;
		}

		var modalOptions = {};

		if ( $c.body.hasClass( 'block-editor-page' ) ) {
			$c.body.append( '<a href="#anwpfl-shortcode-modal" id="anwpfl-shortcode-modal-bump"></a><div id="anwpfl-shortcode-modal"></div>' );
			$c.modal = $( '#anwpfl-shortcode-modal-bump' );
			$c.modalWrapper = $( '#anwpfl-shortcode-modal' );

			modalOptions = {
				custom_class: 'anwpfl-shortcode-modal',
				hide_close: true,
				animation: 'none',
				after_open: function() {
					plugin.initAlpineComponent();
				},
				after_close: function() {
					tinymce.activeEditor.focus();
					plugin.cleanupAlpineComponent();
				}
			};
		} else {
			$c.body.append( '<div id="anwpfl-shortcode-modal"></div>' );
			$c.modal = $( '.mce-anwpfl-shortcode-modal-bump' );
			$c.modalWrapper = $( '#anwpfl-shortcode-modal' );

			modalOptions = {
				content_source: '#anwpfl-shortcode-modal',
				custom_class: 'anwpfl-shortcode-modal',
				hide_close: true,
				animation: 'none',
				after_open: function() {
					plugin.initAlpineComponent();
				},
				after_close: function() {
					tinymce.activeEditor.focus();
					plugin.cleanupAlpineComponent();
				}
			};
		}

		// Init modal
		$c.modal.modaal( modalOptions );

		// Create modal structure with Alpine component
		plugin.createModalContent();
	};

	plugin.createModalContent = function() {
		var shortcodeOptions = plugin.getShortcodeOptionsHtml();

		var modalHtml = `
			<div
				class="anwpfl-shortcode-modal-content"
				fl-x-data="shortcodeBuilder({ isModal: true })"
				fl-x-on:shortcode-inserted.window="closeShortcodeModal()"
			>
				<div class="anwpfl-shortcode-modal__header d-flex align-items-center p-3 border-bottom">
					<label for="anwpfl-shortcode-modal__selector" class="mr-2">${ _fl_shortcodes_l10n.shortcode || 'Shortcode' }</label>
					<select
						id="anwpfl-shortcode-modal__selector"
						class="flex-grow-1"
						fl-x-model="selectedShortcode"
						fl-x-on:change="loadForm()"
					>
						<option value="">- ${ _fl_shortcodes_l10n.select || 'select' } -</option>
						${ shortcodeOptions }
					</select>
					<span class="spinner ml-2" fl-x-bind:class="{ 'is-active': loading }"></span>
				</div>

				<div
					class="anwpfl-shortcode-modal__content p-3"
					style="max-height: 400px; overflow-y: auto;"
					fl-x-ref="formWrap"
					fl-x-html="formHtml"
					fl-x-on:input.debounce.150ms="buildShortcode()"
					fl-x-on:change="buildShortcode()"
					fl-x-on:update-x-fl-outer-wrapper.window="buildShortcode()"
				></div>

				<div class="anwpfl-shortcode-modal__footer p-3 border-top">
					<button
						type="button"
						class="button mr-2"
						onclick="window.closeShortcodeModal()"
					>${ _fl_shortcodes_l10n.cancel || 'Close' }</button>
					<button
						type="button"
						class="button button-primary"
						fl-x-on:click="insertToEditor()"
						fl-x-bind:disabled="!shortcodeString"
					>${ _fl_shortcodes_l10n.insert || 'Insert Shortcode' }</button>
				</div>
			</div>
		`;

		$c.modalWrapper.html( modalHtml );

		// Store localization data for Alpine component
		window._fl_shortcode_builder_l10n = window._fl_shortcode_builder_l10n || {
			nonce: _fl_shortcodes_l10n.nonce,
			copied_to_clipboard: _fl_shortcodes_l10n.copied_to_clipboard || 'Copied to Clipboard'
		};
	};

	plugin.getShortcodeOptionsHtml = function() {
		var html = '';

		// Core options first (already sorted alphabetically from PHP)
		var coreOptions = _fl_shortcodes_l10n.shortcode_options || {};
		var coreKeys = Object.keys( coreOptions ).sort( function( a, b ) {
			return coreOptions[ a ].localeCompare( coreOptions[ b ] );
		} );

		coreKeys.forEach( function( key ) {
			html += '<option value="' + key + '">' + coreOptions[ key ] + '</option>';
		} );

		// Premium options after core (already sorted alphabetically from PHP)
		var premiumOptions = _fl_shortcodes_l10n.shortcode_options_premium || {};
		var premiumKeys = Object.keys( premiumOptions ).sort( function( a, b ) {
			return premiumOptions[ a ].localeCompare( premiumOptions[ b ] );
		} );

		premiumKeys.forEach( function( key ) {
			html += '<option value="' + key + '">' + premiumOptions[ key ] + '</option>';
		} );

		return html;
	};

	plugin.initAlpineComponent = function() {
		// Initialize Alpine on modal content if Alpine is available
		if ( typeof Alpine !== 'undefined' && $c.modalWrapper.length ) {
			var el = $c.modalWrapper.find( '.anwpfl-shortcode-modal-content' )[ 0 ];
			if ( el && ! el._x_dataStack ) {
				Alpine.initTree( el );
			}
		}
	};

	plugin.cleanupAlpineComponent = function() {
		// Reset the component state
		if ( typeof Alpine !== 'undefined' && $c.modalWrapper.length ) {
			var el = $c.modalWrapper.find( '.anwpfl-shortcode-modal-content' )[ 0 ];
			if ( el && el._x_dataStack ) {
				// Get the component data and reset it
				var data = Alpine.$data( el );
				if ( data ) {
					data.destroy();
					data.selectedShortcode = '';
					data.formHtml = '';
					data.shortcodeString = '';
				}
			}
		}
	};

	// Global close function
	window.closeShortcodeModal = function() {
		if ( $c.modal && $.fn.modaal ) {
			$c.modal.modaal( 'close' );
		}
	};

	plugin.init();
}( window, document, jQuery, window.FootballLeaguesShortcodeButton ) );
