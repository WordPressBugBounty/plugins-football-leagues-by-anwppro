/* eslint-disable camelcase */

/**
 * TinyMCE Shortcode Plugin
 *
 * Uses shared Alpine.js shortcodeBuilder component with native <dialog>.
 *
 * @since 0.10.8
 * @since 0.17.0 Refactored to use Alpine.js shortcodeBuilder component
 * @since 0.17.3 Replaced modaal with native <dialog>, dropped jQuery dependency
 */

( function( window, document ) {
	'use strict';

	var dialog = null;
	var backdrop = null;

	function getShortcodeOptionsHtml() {
		var html = '';
		var l10n = window._fl_shortcodes_l10n || {};

		// Core options (sorted alphabetically)
		var coreOptions = l10n.shortcode_options || {};
		var coreKeys = Object.keys( coreOptions ).sort( function( a, b ) {
			return coreOptions[ a ].localeCompare( coreOptions[ b ] );
		} );

		coreKeys.forEach( function( key ) {
			html += '<option value="' + key + '">' + coreOptions[ key ] + '</option>';
		} );

		// Premium options (sorted alphabetically)
		var premiumOptions = l10n.shortcode_options_premium || {};
		var premiumKeys = Object.keys( premiumOptions ).sort( function( a, b ) {
			return premiumOptions[ a ].localeCompare( premiumOptions[ b ] );
		} );

		premiumKeys.forEach( function( key ) {
			html += '<option value="' + key + '">' + premiumOptions[ key ] + '</option>';
		} );

		return html;
	}

	function createDialog() {
		if ( dialog ) {
			return;
		}

		var l10n = window._fl_shortcodes_l10n || {};

		dialog = document.createElement( 'dialog' );
		dialog.id = 'anwpfl-shortcode-dialog';

		dialog.innerHTML =
			'<div' +
			' class="anwpfl-shortcode-modal-content"' +
			' fl-x-data="shortcodeBuilder({ isModal: true })"' +
			' fl-x-on:shortcode-inserted.window="closeShortcodeDialog()"' +
			'>' +
				'<div class="anwpfl-shortcode-dialog__header">' +
					'<label for="anwpfl-shortcode-dialog__selector">' + ( l10n.shortcode || 'Shortcode' ) + '</label>' +
					'<select' +
					' id="anwpfl-shortcode-dialog__selector"' +
					' fl-x-model="selectedShortcode"' +
					' fl-x-on:change="loadForm()"' +
					'>' +
						'<option value="">- ' + ( l10n.select || 'select' ) + ' -</option>' +
						getShortcodeOptionsHtml() +
					'</select>' +
					'<span class="spinner" fl-x-bind:class="{ \'is-active\': loading }"></span>' +
				'</div>' +
				'<div' +
				' class="anwpfl-shortcode-dialog__content"' +
				' fl-x-ref="formWrap"' +
				' fl-x-html="formHtml"' +
				' fl-x-on:input.debounce.150ms="buildShortcode()"' +
				' fl-x-on:change="buildShortcode()"' +
				' fl-x-on:update-x-fl-outer-wrapper.window="buildShortcode()"' +
				'></div>' +
				'<div class="anwpfl-shortcode-dialog__footer">' +
					'<button type="button" class="button" onclick="window.closeShortcodeDialog()">' + ( l10n.cancel || 'Close' ) + '</button>' +
					'<button' +
					' type="button"' +
					' class="button button-primary"' +
					' fl-x-on:click="insertToEditor()"' +
					' fl-x-bind:disabled="!shortcodeString"' +
					'>' + ( l10n.insert || 'Insert Shortcode' ) + '</button>' +
				'</div>' +
			'</div>';

		// Manual backdrop (dialog.show() doesn't create one)
		backdrop = document.createElement( 'div' );
		backdrop.id = 'anwpfl-shortcode-dialog-backdrop';
		backdrop.addEventListener( 'click', function() {
			window.closeShortcodeDialog();
		} );

		document.body.appendChild( backdrop );
		document.body.appendChild( dialog );

		// Handle Esc key (dialog.show() doesn't fire cancel event)
		dialog.addEventListener( 'keydown', function( e ) {
			if ( e.key === 'Escape' ) {
				e.preventDefault();
				window.closeShortcodeDialog();
			}
		} );

		// Store localization data for Alpine component
		window._fl_shortcode_builder_l10n = window._fl_shortcode_builder_l10n || {
			nonce: l10n.nonce,
			copied_to_clipboard: l10n.copied_to_clipboard || 'Copied to Clipboard'
		};
	}

	/**
	 * Reset Alpine component state.
	 *
	 * Alpine's mutation observer (from the admin-global bundle) auto-initializes
	 * fl-x-data elements when appended to the DOM - no manual initTree needed.
	 * Using window.Alpine would hit the wrong instance (public bundle).
	 */
	function cleanupAlpineComponent() {
		if ( ! dialog ) {
			return;
		}

		var el = dialog.querySelector( '.anwpfl-shortcode-modal-content' );

		if ( el && el._x_dataStack ) {
			var data = el._x_dataStack[0];

			if ( data ) {
				data.destroy();
				data.selectedShortcode = '';
				data.formHtml = '';
				data.shortcodeString = '';
			}
		}
	}

	// Global close function
	window.closeShortcodeDialog = function() {
		if ( dialog && dialog.open ) {
			dialog.close();
			backdrop.hidden = true;
			cleanupAlpineComponent();

			if ( typeof tinymce !== 'undefined' && tinymce.activeEditor ) {
				tinymce.activeEditor.focus();
			}
		}
	};

	// Global open function (used by TinyMCE onclick)
	window.openShortcodeDialog = function() {
		createDialog();

		if ( dialog && ! dialog.open ) {
			backdrop.hidden = false;
			dialog.show();
		}
	};

	// Register TinyMCE plugin
	if ( typeof tinymce !== 'undefined' ) {
		tinymce.create( 'tinymce.plugins.football_leagues_button', {
			init: function( editor ) {
				editor.addButton( 'football_leagues', {
					title: ( window._fl_shortcodes_l10n || {} ).football_leagues || 'Football Leagues',
					icon: 'icon anwpfl-button-icon',
					onclick: function() {
						window.openShortcodeDialog();
					}
				} );
			},
			createControl: function() {
				return null;
			}
		} );

		tinymce.PluginManager.add( 'football_leagues_button', tinymce.plugins.football_leagues_button );
	}
}( window, document ) );
