/**
 * Likenook — admin screens.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.likenookAdmin || {};
	var i18n = cfg.i18n || {};
	var $form = $( '#likenook-settings-form' );

	function post( action, data ) {
		return $.post( cfg.ajax, $.extend( { action: action, nonce: cfg.nonce }, data ) );
	}

	/* Tabs ------------------------------------------------------------ */

	function showTab( id, focus ) {
		var $tab = $( '.likenook-tab[aria-controls="likenook-tab-' + id + '"]' );
		if ( ! $tab.length ) {
			return;
		}
		$( '.likenook-tab' ).attr( { 'aria-selected': 'false', tabindex: '-1' } );
		$tab.attr( { 'aria-selected': 'true', tabindex: '0' } );
		$( '.likenook-panel' ).prop( 'hidden', true );
		$( '#likenook-tab-' + id ).prop( 'hidden', false );
		if ( focus ) {
			$tab.trigger( 'focus' );
		}
		// Keep the tab after saving.
		var ref = $form.find( 'input[name="_wp_http_referer"]' );
		if ( ref.length ) {
			ref.val( ref.val().replace( /#.*$/, '' ) + '#' + id );
		}
		if ( window.history.replaceState ) {
			window.history.replaceState( null, '', '#' + id );
		}
	}

	$( '.likenook-tab' ).on( 'click', function () {
		showTab( $( this ).attr( 'aria-controls' ).replace( 'likenook-tab-', '' ) );
	} );

	$( '.likenook-tabs' ).on( 'keydown', '.likenook-tab', function ( e ) {
		var $tabs = $( '.likenook-tab' );
		var index = $tabs.index( this );
		var next = null;
		if ( 'ArrowRight' === e.key ) {
			next = ( index + 1 ) % $tabs.length;
		} else if ( 'ArrowLeft' === e.key ) {
			next = ( index - 1 + $tabs.length ) % $tabs.length;
		}
		if ( null !== next ) {
			e.preventDefault();
			showTab( $tabs.eq( next ).attr( 'aria-controls' ).replace( 'likenook-tab-', '' ), true );
		}
	} );

	if ( $( '.likenook-tabs' ).length ) {
		showTab( ( window.location.hash || '#appearance' ).slice( 1 ) );
	}

	/* Conditional fields --------------------------------------------- */

	function value( key ) {
		var $input = $form.find( '[name="likenook_settings[' + key + ']"]' );
		if ( $input.is( ':radio' ) ) {
			return $input.filter( ':checked' ).val();
		}
		if ( $input.is( ':checkbox' ) ) {
			return $input.is( ':checked' ) ? '1' : '';
		}
		return $input.val();
	}

	function conditions() {
		$( '[data-show-when]' ).each( function () {
			var rule = $( this ).attr( 'data-show-when' ).split( '=' );
			$( this ).prop( 'hidden', value( rule[ 0 ] ) !== rule[ 1 ] );
		} );
	}

	/* Live preview --------------------------------------------------- */

	var timer = null;
	var request = null;

	function refreshPreview() {
		clearTimeout( timer );
		timer = setTimeout( function () {
			if ( request ) {
				request.abort();
			}
			request = post( 'likenook_preview', { settings: $form.serialize() } ).done( function ( res ) {
				if ( res && res.success ) {
					$( '#likenook-preview' ).html( res.data.html );
				}
			} );
		}, 120 );
	}

	$form.on( 'change input', ':input', function () {
		conditions();
		refreshPreview();
	} );

	$( '.likenook-preview-bg' ).on( 'click', 'button', function () {
		$( '.likenook-preview-bg button' ).attr( 'aria-checked', 'false' );
		$( this ).attr( 'aria-checked', 'true' );
		$( '.likenook-preview-stage' ).attr( 'data-bg', $( this ).data( 'bg' ) );
	} );

	/* Presets -------------------------------------------------------- */

	function setValue( key, val ) {
		var $input = $form.find( '[name="likenook_settings[' + key + ']"]' );
		if ( $input.is( ':radio' ) ) {
			$input.prop( 'checked', false ).filter( '[value="' + val + '"]' ).prop( 'checked', true );
		} else if ( $input.is( ':checkbox' ) ) {
			$input.prop( 'checked', !! val );
		} else {
			$input.val( val );
		}
	}

	$( '.likenook-preset' ).on( 'click', function () {
		var preset = ( cfg.presets || {} )[ $( this ).data( 'preset' ) ];
		if ( ! preset ) {
			return;
		}
		$.each( preset.values, setValue );
		$( '.likenook-preset' ).removeClass( 'is-applied' );
		$( this ).addClass( 'is-applied' );
		conditions();
		refreshPreview();
	} );

	/* Colours -------------------------------------------------------- */

	if ( $.fn.wpColorPicker ) {
		$( '.likenook-color' ).wpColorPicker( {
			change: refreshPreview,
			clear: refreshPreview,
		} );
	}

	/* Custom icon images --------------------------------------------- */

	$( '.likenook-media' ).each( function () {
		var $box = $( this );
		var frame = null;

		$box.find( '.likenook-media__pick' ).on( 'click', function () {
			if ( ! frame ) {
				frame = wp.media( {
					title: i18n.choose,
					button: { text: i18n.use },
					library: { type: 'image' },
					multiple: false,
				} );
				frame.on( 'select', function () {
					var file = frame.state().get( 'selection' ).first().toJSON();
					var url = file.sizes && file.sizes.thumbnail ? file.sizes.thumbnail.url : file.url;
					$box.find( 'input' ).val( file.id ).trigger( 'change' );
					$box.find( '.likenook-media__thumb' ).html( $( '<img alt="">' ).attr( 'src', url ) );
				} );
			}
			frame.open();
		} );

		$box.find( '.likenook-media__clear' ).on( 'click', function () {
			$box.find( 'input' ).val( '0' ).trigger( 'change' );
			$box.find( '.likenook-media__thumb' ).empty();
		} );
	} );

	conditions();

	/* Chart tooltip -------------------------------------------------- */

	$( '.likenook-chart' ).each( function () {
		var $chart = $( this );
		var $tip = $chart.find( '.likenook-chart__tip' );

		function show( col ) {
			var $col = $( col );
			var box = col.getBoundingClientRect();
			var outer = $chart[ 0 ].getBoundingClientRect();
			$tip
				.empty()
				.append( $( '<strong>' ).text( $col.data( 'label' ) ) )
				.append( $( '<span class="likenook-chart__tip-row">' ).append( '<i class="likenook-key likenook-key--like"></i>', document.createTextNode( i18n.likes + ' ' ), $( '<b>' ).text( $col.data( 'likes' ) ) ) )
				.append( $( '<span class="likenook-chart__tip-row">' ).append( '<i class="likenook-key likenook-key--dislike"></i>', document.createTextNode( i18n.dislikes + ' ' ), $( '<b>' ).text( $col.data( 'dislikes' ) ) ) )
				.prop( 'hidden', false );

			var left = box.left - outer.left + box.width / 2;
			var tipWidth = $tip.outerWidth();
			left = Math.max( tipWidth / 2, Math.min( outer.width - tipWidth / 2, left ) );
			$tip.css( { left: left + 'px' } );
			$chart.find( '.likenook-chart__col' ).removeClass( 'is-hover' );
			$col.addClass( 'is-hover' );
		}

		function hide() {
			$tip.prop( 'hidden', true );
			$chart.find( '.likenook-chart__col' ).removeClass( 'is-hover' );
		}

		$chart.on( 'mouseenter focus', '.likenook-chart__col', function () {
			show( this );
		} );
		$chart.on( 'mouseleave', hide );
		$chart.on( 'blur', '.likenook-chart__col', hide );
	} );

	/* Tools ---------------------------------------------------------- */

	$( '.likenook-import' ).on( 'click', function () {
		var $btn = $( this );
		var $status = $btn.siblings( '.likenook-progress' );
		var source = $btn.data( 'source' );
		var total = 0;

		$btn.prop( 'disabled', true );

		function step( cursor ) {
			post( 'likenook_import', $.extend( { source: source }, cursor ) )
				.done( function ( res ) {
					if ( ! res || ! res.success ) {
						$status.text( ( res && res.data && res.data.message ) || i18n.error );
						$btn.prop( 'disabled', false );
						return;
					}
					total += res.data.processed;
					if ( res.data.done ) {
						$status.text( i18n.imported.replace( '%d', total.toLocaleString() ) );
						$btn.prop( 'disabled', false );
					} else {
						$status.text( i18n.importing.replace( '%d', total.toLocaleString() ) );
						step( res.data.cursor );
					}
				} )
				.fail( function () {
					$status.text( i18n.error );
					$btn.prop( 'disabled', false );
				} );
		}

		$status.text( i18n.importing.replace( '%d', '0' ) );
		step( {} );
	} );

	$( '#likenook-recount' ).on( 'click', function () {
		var $btn = $( this );
		var $status = $btn.siblings( '.likenook-progress' );
		$btn.prop( 'disabled', true );
		$status.text( i18n.recounting );

		function step( data ) {
			post( 'likenook_recount', data )
				.done( function ( res ) {
					if ( ! res || ! res.success ) {
						$status.text( i18n.error );
						$btn.prop( 'disabled', false );
					} else if ( res.data.done ) {
						$status.text( i18n.recounted );
						$btn.prop( 'disabled', false );
					} else {
						step( { step: res.data.step, after: res.data.after } );
					}
				} )
				.fail( function () {
					$status.text( i18n.error );
					$btn.prop( 'disabled', false );
				} );
		}
		step( { step: 0, after: 0 } );
	} );

	$( '.likenook-copy__btn' ).on( 'click', function () {
		var $btn = $( this );
		var original = $btn.text();
		var done = function () {
			$btn.text( i18n.copied );
			setTimeout( function () {
				$btn.text( original );
			}, 1500 );
		};
		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( $btn.data( 'copy' ) ).then( done );
		} else {
			var $tmp = $( '<textarea>' ).val( $btn.data( 'copy' ) ).appendTo( 'body' ).trigger( 'select' );
			document.execCommand( 'copy' );
			$tmp.remove();
			done();
		}
	} );

	$( '.likenook-reset-form' ).on( 'submit', function ( e ) {
		if ( ! window.confirm( i18n.confirmReset ) ) {
			e.preventDefault();
		}
	} );
} )( jQuery );
