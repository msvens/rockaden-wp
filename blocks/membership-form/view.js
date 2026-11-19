/**
 * Frontend script for the Membership Form block (rockaden/membership-form).
 *
 * No-build / vanilla ES5. Three jobs:
 *
 * 1. Fetch a fresh REST nonce on load (the page may come from a cache, so a
 *    nonce printed into it could be stale). Submitting stays disabled until
 *    it arrives. Both requests omit cookies: a submission is anonymous, and a
 *    signed-in editor would otherwise fail WordPress's cookie check with the
 *    anonymous nonce. The page's locale travels as a hidden field instead of
 *    the rc_locale cookie, so replies come back in the page's language.
 * 2. Show the field groups for the chosen category. Each group carries
 *    data-for="<category slugs>"; groups that do not apply are hidden AND
 *    disabled, so the browser neither requires nor submits their inputs.
 * 3. Submit as JSON. The server is the one that validates: when it rejects a
 *    field it names it, and that field is marked and focused here.
 *
 * All text comes from data-* attributes or the server, translated in PHP.
 */
( function () {
	var forms = document.querySelectorAll( '.rockaden-membership' );

	Array.prototype.forEach.call( forms, function ( form ) {
		var contextUrl = form.getAttribute( 'data-context-url' );
		var submitUrl = form.getAttribute( 'data-submit-url' );
		var status = form.querySelector( '.rockaden-membership__status' );
		var submit = form.querySelector( '.rockaden-membership__submit' );
		var category = form.querySelector( 'select[name="category"]' );
		var groups = form.querySelectorAll( '[data-for]' );
		var nonce = '';
		var busy = false;

		if ( ! contextUrl || ! submitUrl || ! status || ! submit ) {
			return;
		}

		function setStatus( msg, state ) {
			status.textContent = msg;
			status.className =
				'rockaden-membership__status' + ( state ? ' is-' + state : '' );
		}

		function applyCategory( value ) {
			Array.prototype.forEach.call( groups, function ( group ) {
				var on =
					value !== '' &&
					( ' ' + group.getAttribute( 'data-for' ) + ' ' ).indexOf(
						' ' + value + ' '
					) !== -1;
				group.hidden = ! on;
				group.disabled = ! on;
			} );
		}

		function clearInvalid() {
			Array.prototype.forEach.call(
				form.querySelectorAll( '.is-invalid' ),
				function ( input ) {
					input.classList.remove( 'is-invalid' );
					input.removeAttribute( 'aria-invalid' );
				}
			);
		}

		function markInvalid( name ) {
			if ( ! name ) {
				return;
			}
			// Only an enabled input: the same name can exist in a hidden group.
			var inputs = form.querySelectorAll( '[name="' + name + '"]' );
			for ( var i = 0; i < inputs.length; i++ ) {
				if ( ! inputs[ i ].disabled && inputs[ i ].type !== 'hidden' ) {
					inputs[ i ].classList.add( 'is-invalid' );
					inputs[ i ].setAttribute( 'aria-invalid', 'true' );
					inputs[ i ].focus();
					return;
				}
			}
		}

		function payload() {
			// FormData skips disabled controls, so hidden groups drop out.
			var data = {};
			new FormData( form ).forEach( function ( value, key ) {
				data[ key ] = typeof value === 'string' ? value : '';
			} );
			return data;
		}

		if ( category ) {
			category.addEventListener( 'change', function () {
				applyCategory( category.value );
			} );
			// Browsers restore form values on back/forward navigation.
			applyCategory( category.value );
		}

		form.addEventListener( 'input', function ( e ) {
			if ( e.target && e.target.classList ) {
				e.target.classList.remove( 'is-invalid' );
				e.target.removeAttribute( 'aria-invalid' );
			}
		} );

		// Leave requests confirm first. The browser has already checked the
		// required fields by the time 'submit' fires, so the dialog only
		// appears for a form that is ready to send.
		var dialog = form.parentNode.querySelector( '.rockaden-membership__confirm' );
		var useDialog = !! ( dialog && dialog.showModal );

		// Thank-you dialog after a successful submission; without <dialog>
		// support the status line under the button says it instead.
		var done = form.parentNode.querySelector( '.rockaden-membership__done' );
		if ( done && done.showModal ) {
			done.addEventListener( 'click', function ( e ) {
				if ( e.target && e.target.getAttribute( 'data-done' ) ) {
					done.close();
				}
			} );
		} else {
			done = null;
		}

		if ( useDialog ) {
			dialog.addEventListener( 'click', function ( e ) {
				var choice = e.target && e.target.getAttribute( 'data-confirm' );
				if ( choice ) {
					dialog.close();
					if ( choice === 'ok' ) {
						send();
					}
				}
			} );
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			if ( busy || nonce === '' ) {
				return;
			}
			var question = form.getAttribute( 'data-confirm-fallback' );
			if ( useDialog ) {
				dialog.showModal();
				return;
			}
			// No <dialog> support: the browser's own confirm instead.
			if ( question && ! window.confirm( question ) ) {
				return;
			}
			send();
		} );

		function send() {
			if ( busy ) {
				return;
			}
			busy = true;
			submit.disabled = true;
			clearInvalid();
			setStatus( form.getAttribute( 'data-sending' ) || '', 'sending' );

			fetch( submitUrl, {
				method: 'POST',
				credentials: 'omit',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
				body: JSON.stringify( payload() ),
			} )
				.then( function ( res ) {
					return res
						.json()
						.catch( function () {
							return {};
						} )
						.then( function ( body ) {
							return { ok: res.ok, body: body || {} };
						} );
				} )
				.then( function ( result ) {
					busy = false;
					if ( result.ok ) {
						form.reset();
						// A fixed category has no picker and its groups stay as rendered.
						if ( category ) {
							applyCategory( '' );
						}
						setStatus( form.getAttribute( 'data-success' ) || '', 'success' );
						submit.disabled = false;
						if ( done ) {
							done.showModal();
						}
						return;
					}
					var body = result.body;
					var field = body.data && body.data.field;
					// A message is only shown when the server named a field:
					// anything else (an expired nonce, a crash) gets the
					// generic text rather than a technical one.
					setStatus(
						field && body.message
							? body.message
							: form.getAttribute( 'data-error' ) || '',
						'error'
					);
					markInvalid( field );
					submit.disabled = false;
				} )
				.catch( function () {
					busy = false;
					setStatus( form.getAttribute( 'data-error' ) || '', 'error' );
					submit.disabled = false;
				} );
		}

		fetch( contextUrl, { credentials: 'omit' } )
			.then( function ( res ) {
				return res.ok ? res.json() : {};
			} )
			.then( function ( data ) {
				nonce = ( data && data.nonce ) || '';
				submit.disabled = nonce === '';
			} )
			.catch( function () {
				setStatus( form.getAttribute( 'data-error' ) || '', 'error' );
			} );
	} );
} )();
