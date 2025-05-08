window.AnWPFootballLeaguesCustomizer = window.AnWPFootballLeaguesCustomizer || {};

( function( window, document, $, plugin ) {

	'use strict';

	const $c = {};

	plugin.init = function() {
		plugin.cache();
		plugin.bindEvents();
	};

	plugin.cache = function() {
		$c.window   = $( window );
		$c.body     = $( document.body );
		$c.document = $( document );
	};

	plugin.bindEvents = function() {

		if ( document.readyState !== 'loading' ) {
			plugin.onPageReady();
		} else {
			document.addEventListener( 'DOMContentLoaded', plugin.onPageReady );
		}
	};

	const handleDependentFields = ( el ) => {
		const dependentFields = el.dataset.anwpDependentControls.split( ',' ) || [];

		dependentFields.forEach( field => {
			if ( document.getElementById( field ) ) {
				if ( el.checked ) {
					document.getElementById( field ).classList.remove( 'd-none' );
				} else {
					document.getElementById( field ).classList.add( 'd-none' );
				}
			}
		});
	};

	// Get the values from the checkboxes and add to our hidden field
	function anwpGetAllPillCheckboxes( $element ) {
		const inputValues = $element.find( '.sortable-pill-checkbox' ).map( function() {
			if ( $( this ).is( ':checked' ) ) {
				return $( this ).val();
			}
		} ).toArray();
		$element.find( '.customize-control-sortable-pill-checkbox' ).val( inputValues ).trigger( 'change' );
	}

	plugin.onPageReady = function() {
		document.getElementById( 'customize-controls' ).addEventListener( 'change', event => {
			if ( ! event.target.matches( '.anwp-toggle-switch-checkbox' ) ) {
				return;
			}

			handleDependentFields( event.target );
		} );

		document.querySelectorAll( '.anwp-toggle-switch-checkbox' ).forEach( el => {
			handleDependentFields( el );
		} );

		$( '.anwp-pill_checkbox_control .sortable' ).sortable( {
			placeholder: 'pill-ui-state-highlight',
			update: function( event, ui ) {
				anwpGetAllPillCheckboxes( $( this ).parent() );
			}
		} );

		$( '.anwp-pill_checkbox_control .sortable-pill-checkbox' ).on( 'change', function() {
			anwpGetAllPillCheckboxes( $( this ).parent().parent().parent() );
		} );
	};

	$( plugin.init );
}( window, document, jQuery, window.AnWPFootballLeaguesCustomizer ) );


