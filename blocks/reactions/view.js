/**
 * Frontend script for the reactions bar (rockaden/reactions, also rendered
 * inside latest-news cards).
 *
 * No-build / vanilla JS (mirrors blocks/feedback-form/view.js). On load it
 * fetches live counts plus a REST nonce (the page may come from a cache, so
 * neither is trusted from the HTML), marks the emoji this browser chose
 * earlier, and enables the heart. The heart opens a picker; picking an emoji
 * sends the +1/-1 deltas, updating optimistically and reverting on failure.
 * The pills are passive badges (as in Signal, you change or remove your
 * reaction through the picker) and a count of 1 is not shown.
 *
 * "One reaction per reader" is remembered in localStorage only — nothing
 * about the reader reaches the server. Requests deliberately omit cookies so
 * a signed-in editor reacts as an anonymous visitor (and so the anonymous
 * nonce from the GET verifies for them too).
 */
( function () {
	var STORAGE_KEY = 'rcReactions';

	function readChoices() {
		try {
			var raw = window.localStorage.getItem( STORAGE_KEY );
			var parsed = raw ? JSON.parse( raw ) : {};
			return parsed && typeof parsed === 'object' ? parsed : {};
		} catch ( e ) {
			return {};
		}
	}

	function writeChoice( postId, emoji ) {
		try {
			var choices = readChoices();
			if ( emoji ) {
				choices[ postId ] = emoji;
			} else {
				delete choices[ postId ];
			}
			window.localStorage.setItem(
				STORAGE_KEY,
				JSON.stringify( choices )
			);
		} catch ( e ) {
			// Storage unavailable (private mode, quota): the click still
			// counts, it just isn't remembered.
		}
	}

	function parseJson( response ) {
		if ( ! response.ok ) {
			return Promise.reject( new Error( 'HTTP ' + response.status ) );
		}
		return response.json();
	}

	function init( root ) {
		var postId = root.getAttribute( 'data-post-id' );
		var restUrl = root.getAttribute( 'data-rest-url' );
		var given = root.querySelector( '.rockaden-reactions__given' );
		var addBtn = root.querySelector( '.rockaden-reactions__add' );
		var picker = root.querySelector( '.rockaden-reactions__picker' );
		var status = root.querySelector( '.rockaden-reactions__status' );
		if ( ! postId || ! restUrl || ! given || ! addBtn || ! picker ) {
			return;
		}

		var options = Array.prototype.slice.call(
			picker.querySelectorAll( '.rockaden-reactions__option' )
		);
		// Picker order decides tie-breaks when sorting pills.
		var order = {};
		options.forEach( function ( option, index ) {
			order[ option.getAttribute( 'data-emoji' ) ] = index;
		} );

		var nonce = '';
		var busy = false;
		var counts = {};
		var current = readChoices()[ postId ] || null;

		// Start from the server-rendered numbers so the bar never flashes empty.
		Array.prototype.slice
			.call( given.querySelectorAll( '.rockaden-reactions__pill' ) )
			.forEach( function ( pill ) {
				var countEl = pill.querySelector( '.rockaden-reactions__count' );
				counts[ pill.getAttribute( 'data-emoji' ) ] =
					parseInt( countEl ? countEl.textContent : '0', 10 ) || 0;
			} );

		function sortedEmojis() {
			return Object.keys( counts )
				.filter( function ( emoji ) {
					return counts[ emoji ] > 0;
				} )
				.sort( function ( a, b ) {
					if ( counts[ a ] !== counts[ b ] ) {
						return counts[ b ] - counts[ a ];
					}
					var ia = order[ a ] === undefined ? Infinity : order[ a ];
					var ib = order[ b ] === undefined ? Infinity : order[ b ];
					return ia - ib;
				} );
		}

		function makePill( emoji ) {
			var pill = document.createElement( 'span' );
			pill.className = 'rockaden-reactions__pill';
			pill.setAttribute( 'data-emoji', emoji );
			var icon = document.createElement( 'span' );
			icon.className = 'rockaden-reactions__emoji';
			icon.textContent = emoji;
			var count = document.createElement( 'span' );
			count.className = 'rockaden-reactions__count';
			pill.appendChild( icon );
			pill.appendChild( count );
			return pill;
		}

		function render() {
			var emojis = sortedEmojis();
			var existing = {};
			Array.prototype.slice
				.call( given.querySelectorAll( '.rockaden-reactions__pill' ) )
				.forEach( function ( pill ) {
					existing[ pill.getAttribute( 'data-emoji' ) ] = pill;
				} );

			// Rebuild in sorted order, reusing the existing badges.
			emojis.forEach( function ( emoji ) {
				var pill = existing[ emoji ] || makePill( emoji );
				delete existing[ emoji ];
				pill.classList.toggle( 'is-active', emoji === current );
				pill.querySelector( '.rockaden-reactions__count' ).textContent =
					counts[ emoji ] > 1 ? String( counts[ emoji ] ) : '';
				given.appendChild( pill );
			} );
			Object.keys( existing ).forEach( function ( emoji ) {
				given.removeChild( existing[ emoji ] );
			} );

			options.forEach( function ( option ) {
				var active = option.getAttribute( 'data-emoji' ) === current;
				option.classList.toggle( 'is-active', active );
				option.setAttribute( 'aria-pressed', String( active ) );
			} );
			addBtn.disabled = nonce === '';
		}

		function setOpen( open ) {
			picker.hidden = ! open;
			addBtn.setAttribute( 'aria-expanded', String( open ) );
		}

		function react( emoji ) {
			if ( busy || nonce === '' ) {
				return;
			}
			var previous = current;
			var payload = {};

			if ( previous === emoji ) {
				payload.remove = emoji;
				current = null;
			} else {
				if ( previous ) {
					payload.remove = previous;
				}
				payload.add = emoji;
				current = emoji;
			}

			var snapshot = {};
			Object.keys( counts ).forEach( function ( key ) {
				snapshot[ key ] = counts[ key ];
			} );
			if ( payload.remove ) {
				counts[ payload.remove ] = Math.max(
					0,
					( counts[ payload.remove ] || 0 ) - 1
				);
			}
			if ( payload.add ) {
				counts[ payload.add ] = ( counts[ payload.add ] || 0 ) + 1;
			}
			setOpen( false );
			render();
			if ( status ) {
				status.textContent = '';
			}
			busy = true;

			fetch( restUrl, {
				method: 'POST',
				credentials: 'omit',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
				body: JSON.stringify( payload ),
			} )
				.then( parseJson )
				.then( function ( data ) {
					counts = data.counts || counts;
					writeChoice( postId, current );
					render();
				} )
				.catch( function () {
					counts = snapshot;
					current = previous;
					render();
					if ( status ) {
						status.textContent =
							root.getAttribute( 'data-error' ) || '';
					}
				} )
				.then( function () {
					busy = false;
				} );
		}

		options.forEach( function ( option ) {
			option.addEventListener( 'click', function () {
				react( option.getAttribute( 'data-emoji' ) );
			} );
		} );

		// No stopPropagation: the click must reach the document so every other
		// bar's outside-click handler closes its own picker.
		addBtn.addEventListener( 'click', function () {
			setOpen( picker.hidden );
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( ! picker.hidden && ! root.contains( e.target ) ) {
				setOpen( false );
			}
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! picker.hidden ) {
				setOpen( false );
				addBtn.focus();
			}
		} );

		render();

		fetch( restUrl, { credentials: 'omit' } )
			.then( parseJson )
			.then( function ( data ) {
				counts = data.counts || {};
				nonce = data.nonce || '';
				render();
			} )
			.catch( function () {
				// Leave the static, disabled bar in place.
			} );
	}

	function boot() {
		document
			.querySelectorAll( '.rockaden-reactions' )
			.forEach( function ( root ) {
				if ( ! root.hasAttribute( 'data-rc-ready' ) ) {
					root.setAttribute( 'data-rc-ready', '1' );
					init( root );
				}
			} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
