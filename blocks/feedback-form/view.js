/**
 * Frontend script for the Feedback Form block (rockaden/feedback-form).
 *
 * No-build / vanilla JS (mirrors blocks/section-nav/view.js). Intercepts the
 * form submit and posts to the REST endpoint with the standard REST nonce.
 * The status messages are read from data-* attributes (translated server-side).
 */
( function () {
	document
		.querySelectorAll( '.rockaden-feedback' )
		.forEach( function ( form ) {
			var restUrl = form.getAttribute( 'data-rest-url' );
			var nonce = form.getAttribute( 'data-nonce' );
			var status = form.querySelector( '.rockaden-feedback__status' );
			var submit = form.querySelector( '.rockaden-feedback__submit' );
			if ( ! restUrl || ! nonce ) {
				return;
			}

			function setStatus( msg, state ) {
				if ( ! status ) {
					return;
				}
				status.textContent = msg;
				status.className =
					'rockaden-feedback__status' +
					( state ? ' is-' + state : '' );
			}

			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				if ( submit ) {
					submit.disabled = true;
				}
				setStatus( form.getAttribute( 'data-sending' ) || '', 'sending' );

				var payload = {
					name: ( form.elements.name && form.elements.name.value ) || '',
					email:
						( form.elements.email && form.elements.email.value ) ||
						'',
					message:
						( form.elements.message &&
							form.elements.message.value ) ||
						'',
					website:
						( form.elements.website &&
							form.elements.website.value ) ||
						'',
				};

				fetch( restUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify( payload ),
				} )
					.then( function ( res ) {
						if ( ! res.ok ) {
							throw new Error( 'request failed' );
						}
						return res.json();
					} )
					.then( function () {
						form.reset();
						setStatus(
							form.getAttribute( 'data-success' ) || '',
							'success'
						);
					} )
					.catch( function () {
						setStatus(
							form.getAttribute( 'data-error' ) || '',
							'error'
						);
						if ( submit ) {
							submit.disabled = false;
						}
					} );
			} );
		} );
} )();
