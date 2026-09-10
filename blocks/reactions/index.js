/**
 * Editor script for the Reactions block (rockaden/reactions).
 *
 * No-build / vanilla JS (mirrors blocks/latest-news). The block is
 * server-rendered and has no settings of its own — the emoji list lives under
 * Appearance → Rockaden — so the editor shows a static preview using the same
 * classes as the front end (custom.css is loaded as an editor style).
 */
( function ( blocks, blockEditor, element ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;

	function pill( emoji, count ) {
		return el(
			'span',
			{ key: emoji, className: 'rockaden-reactions__pill' },
			el( 'span', { className: 'rockaden-reactions__emoji' }, emoji ),
			el( 'span', { className: 'rockaden-reactions__count' }, count )
		);
	}

	blocks.registerBlockType( 'rockaden/reactions', {
		edit: function () {
			var blockProps = useBlockProps( { className: 'rockaden-reactions' } );

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ className: 'rockaden-reactions__given' },
					pill( '👍', '3' ),
					pill( '❤️', '' )
				),
				el(
					'span',
					{ className: 'rockaden-reactions__add', 'aria-hidden': 'true' },
					el(
						'svg',
						{
							viewBox: '0 0 24 24',
							fill: 'none',
							stroke: 'currentColor',
							strokeWidth: '1.75',
							strokeLinecap: 'round',
							strokeLinejoin: 'round',
						},
						el( 'path', {
							d: 'M18.3 5.2a4.8 4.8 0 0 0-6.8 0L10.6 6.1l-.9-.9a4.8 4.8 0 0 0-6.8 6.8l.9.9 6.8 6.8 6.8-6.8.9-.9a4.8 4.8 0 0 0 0-6.8z',
						} ),
						el( 'circle', {
							cx: '19',
							cy: '5',
							r: '4.75',
							fill: 'var(--wp--preset--color--surface, #ffffff)',
							stroke: 'none',
						} ),
						el( 'path', { d: 'M19 2.75v4.5M16.75 5h4.5', strokeWidth: '2' } )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element );
