( function () {
	document
		.querySelectorAll( '.rc-section-nav' )
		.forEach( function ( nav ) {
			var btn = nav.querySelector( '.rc-section-nav__mobile-btn' );
			var menu = nav.querySelector( '.rc-section-nav__mobile-menu' );
			if ( ! btn || ! menu ) {
				return;
			}

			btn.addEventListener( 'click', function () {
				var open = menu.classList.toggle( 'is-open' );
				btn.classList.toggle( 'is-open', open );
				btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );

			document.addEventListener( 'click', function ( e ) {
				if (
					! btn.contains( e.target ) &&
					! menu.contains( e.target )
				) {
					menu.classList.remove( 'is-open' );
					btn.classList.remove( 'is-open' );
					btn.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		} );
} )();
