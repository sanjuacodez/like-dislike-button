/**
 * Likenook — front end.
 *
 * Counts and the visitor's own votes load through one REST request after the
 * page loads, so buttons stay correct on cached pages.
 */
( function () {
	'use strict';

	var config = window.likenookConfig || {};
	var i18n = config.i18n || {};
	var state = {};
	var busy = {};
	var compact = null;

	try {
		compact = new Intl.NumberFormat( document.documentElement.lang || undefined, { notation: 'compact', maximumFractionDigits: 1 } );
	} catch ( e ) {}

	function format( n ) {
		if ( config.short && compact && Math.abs( n ) >= 1000 ) {
			return compact.format( n );
		}
		return n.toLocaleString( document.documentElement.lang || undefined );
	}

	function keyOf( el ) {
		return el.getAttribute( 'data-likenook-type' ) + ':' + el.getAttribute( 'data-likenook-id' );
	}

	function widgets( key ) {
		var all = document.querySelectorAll( '.likenook[data-likenook-id]' );
		return Array.prototype.filter.call( all, function ( el ) {
			return ! key || keyOf( el ) === key;
		} );
	}

	function readState( el ) {
		return {
			likes: parseInt( el.getAttribute( 'data-likenook-likes' ), 10 ) || 0,
			dislikes: parseInt( el.getAttribute( 'data-likenook-dislikes' ), 10 ) || 0,
			vote: el.getAttribute( 'data-likenook-vote' ) || '',
		};
	}

	function setText( node, text, animate ) {
		if ( ! node || node.textContent === text ) {
			return;
		}
		node.textContent = text;
		if ( animate ) {
			node.classList.remove( 'is-changed' );
			void node.offsetWidth;
			node.classList.add( 'is-changed' );
		}
	}

	/**
	 * Draws counts and the pressed state into one widget.
	 */
	function paint( el, s, animate ) {
		var mode = el.getAttribute( 'data-likenook-counts' );
		var total = s.likes + s.dislikes;

		el.setAttribute( 'data-likenook-likes', s.likes );
		el.setAttribute( 'data-likenook-dislikes', s.dislikes );
		el.setAttribute( 'data-likenook-vote', s.vote );
		el.classList.toggle( 'has-voted', !! s.vote );

		el.querySelectorAll( '.likenook-btn' ).forEach( function ( btn ) {
			var reaction = btn.getAttribute( 'data-likenook-reaction' );
			var n = 'like' === reaction ? s.likes : s.dislikes;
			var active = s.vote === reaction;
			var count = btn.querySelector( '.likenook-count' );

			btn.classList.toggle( 'is-active', active );
			btn.setAttribute( 'aria-pressed', active ? 'true' : 'false' );

			if ( ! count ) {
				return;
			}
			if ( 'percent' === mode ) {
				setText( count, total ? Math.round( ( n / total ) * 100 ) + '%' : '', animate );
			} else if ( 'after_vote' === mode ) {
				count.hidden = ! s.vote;
				setText( count, format( n ), animate );
			} else {
				setText( count, format( n ), animate );
			}
		} );

		setText( el.querySelector( '.likenook-score' ), format( s.likes - s.dislikes ), animate );

		var bar = el.querySelector( '.likenook-bar span' );
		if ( bar ) {
			bar.style.width = ( total ? ( s.likes / total ) * 100 : 0 ) + '%';
		}
	}

	function message( el, html ) {
		var node = el.querySelector( '.likenook-msg' );
		if ( node ) {
			node.innerHTML = html;
		}
	}

	function escapeHtml( text ) {
		var div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}

	function animate( el, btn ) {
		if ( el.classList.contains( 'likenook--anim-none' ) || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			return;
		}
		btn.classList.remove( 'is-animating' );
		void btn.offsetWidth;
		btn.classList.add( 'is-animating' );
		btn.addEventListener( 'animationend', function done() {
			btn.classList.remove( 'is-animating' );
			btn.removeEventListener( 'animationend', done );
		} );

		if ( el.classList.contains( 'likenook--anim-burst' ) ) {
			var icon = btn.querySelector( '.likenook-icon' );
			var x = icon ? icon.offsetLeft + icon.offsetWidth / 2 + 'px' : '50%';
			for ( var i = 0; i < 8; i++ ) {
				var p = document.createElement( 'span' );
				var angle = ( Math.PI * 2 * i ) / 8;
				p.className = 'likenook-particle';
				p.setAttribute( 'aria-hidden', 'true' );
				p.style.setProperty( '--likenook-x', x );
				p.style.setProperty( '--likenook-dx', Math.cos( angle ) * 1.6 + 'em' );
				p.style.setProperty( '--likenook-dy', Math.sin( angle ) * 1.6 + 'em' );
				btn.appendChild( p );
				setTimeout( p.remove.bind( p ), 600 );
			}
		}
	}

	/**
	 * Calls the REST API. When a cached page carries an expired nonce, gets a
	 * fresh one for logged-in users, or retries as a visitor.
	 */
	function api( path, options, retried ) {
		options = options || {};
		var headers = { Accept: 'application/json' };
		if ( options.body ) {
			headers[ 'Content-Type' ] = 'application/json';
		}
		if ( config.nonce ) {
			headers[ 'X-WP-Nonce' ] = config.nonce;
		}

		return fetch( config.rest + path, {
			method: options.method || 'GET',
			credentials: 'same-origin',
			headers: headers,
			body: options.body ? JSON.stringify( options.body ) : undefined,
		} ).then( function ( res ) {
			return res.json().catch( function () {
				return {};
			} ).then( function ( data ) {
				if ( res.ok ) {
					return data;
				}
				if ( 'rest_cookie_invalid_nonce' === data.code && ! retried ) {
					return refreshNonce().then( function () {
						return api( path, options, true );
					} );
				}
				var error = new Error( data.message || i18n.error );
				error.code = data.code;
				throw error;
			} );
		} );
	}

	function refreshNonce() {
		return fetch( config.ajax + '?action=rest-nonce', { credentials: 'same-origin' } )
			.then( function ( res ) {
				return res.ok ? res.text() : '';
			} )
			.then( function ( nonce ) {
				config.nonce = /^[a-f0-9]{10}$/.test( nonce ) ? nonce : '';
			} )
			.catch( function () {
				config.nonce = '';
			} );
	}

	/**
	 * Loads live counts and the visitor's votes for every widget on the page.
	 */
	function load( root ) {
		var keys = [];
		var list = widgets();
		if ( root && root.querySelectorAll ) {
			list = root.matches( '.likenook[data-likenook-id]' ) ? [ root ] : Array.prototype.slice.call( root.querySelectorAll( '.likenook[data-likenook-id]' ) );
		}
		list.forEach( function ( el ) {
			if ( el.hasAttribute( 'data-likenook-preview' ) ) {
				return;
			}
			var key = keyOf( el );
			if ( keys.indexOf( key ) < 0 ) {
				keys.push( key );
			}
		} );

		for ( var i = 0; i < keys.length; i += 100 ) {
			api( 'state?items=' + encodeURIComponent( keys.slice( i, i + 100 ).join( ',' ) ) )
				.then( function ( data ) {
					Object.keys( data.items || {} ).forEach( function ( key ) {
						state[ key ] = data.items[ key ];
						widgets( key ).forEach( function ( el ) {
							paint( el, state[ key ], false );
						} );
					} );
				} )
				.catch( function () {} );
		}
	}

	function onClick( event ) {
		var btn = event.target.closest( '.likenook .likenook-btn' );
		if ( ! btn ) {
			return;
		}
		var el = btn.closest( '.likenook' );
		var key = keyOf( el );
		var reaction = btn.getAttribute( 'data-likenook-reaction' );
		var login = el.getAttribute( 'data-likenook-login' );
		var preview = el.hasAttribute( 'data-likenook-preview' );

		event.preventDefault();

		if ( login ) {
			message( el, ( i18n.login || '%s' ).replace( '%s', '<a href="' + escapeHtml( login ) + '">' + escapeHtml( i18n.logIn || 'log in' ) + '</a>' ) );
			return;
		}
		if ( busy[ key ] ) {
			return;
		}

		var before = preview ? readState( el ) : state[ key ] || readState( el );
		if ( before.vote && ! config.allowChange && ! preview ) {
			message( el, escapeHtml( i18n.voted ) );
			return;
		}

		// Show the result straight away, then confirm with the server.
		var after = { likes: before.likes, dislikes: before.dislikes, vote: before.vote === reaction ? '' : reaction };
		if ( before.vote ) {
			after[ before.vote + 's' ]--;
		}
		if ( after.vote ) {
			after[ after.vote + 's' ]++;
		}

		var targets = preview ? [ el ] : widgets( key );
		targets.forEach( function ( w ) {
			paint( w, after, true );
		} );
		if ( after.vote ) {
			animate( el, btn );
		}

		var thanks = el.getAttribute( 'data-likenook-thanks' );
		var note = after.vote ? ( thanks ? escapeHtml( thanks ) : '' ) : escapeHtml( i18n.removed || '' );

		if ( preview ) {
			message( el, note );
			return;
		}

		busy[ key ] = true;
		btn.setAttribute( 'aria-busy', 'true' );

		api( 'vote', {
			method: 'POST',
			body: { type: el.getAttribute( 'data-likenook-type' ), id: parseInt( el.getAttribute( 'data-likenook-id' ), 10 ), reaction: reaction },
		} )
			.then( function ( data ) {
				state[ key ] = data;
				targets.forEach( function ( w ) {
					paint( w, data, false );
				} );
				message( el, note );
			} )
			.catch( function ( error ) {
				targets.forEach( function ( w ) {
					paint( w, before, false );
				} );
				message( el, escapeHtml( error.message || i18n.error ) );
			} )
			.then( function () {
				busy[ key ] = false;
				btn.removeAttribute( 'aria-busy' );
			} );
	}

	document.addEventListener( 'click', onClick );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			load();
		} );
	} else {
		load();
	}

	/**
	 * For themes that add posts with infinite scroll: `likenook.refresh( container )`.
	 */
	window.likenook = {
		refresh: load,
		paint: paint,
	};
} )();
