/**
 * Editor script for the Sidebar container (rockaden/sidebar).
 *
 * No-build / vanilla JS: uses the global `wp` packages and wp.element.createElement
 * instead of JSX, so the theme needs no compilation step (mirrors
 * theme/blocks/shop-grid). Dependencies are declared in the sibling
 * index.asset.php.
 *
 * The cards render live rather than as a placeholder, because arranging them is
 * the whole point of the block.
 *
 * allowedBlocks keeps anything but a Sidebar Card out, but the block is
 * deliberately NOT locked with templateLock "contentOnly": that mode forbids
 * inserting inner blocks, which would remove the ability to add a card — the
 * reason this is a container at all. The constraint that matters lives inside
 * each card, which is fields rather than free-form blocks.
 */
( function ( blocks, blockEditor, element ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var useInnerBlocksProps = blockEditor.useInnerBlocksProps;
	var InnerBlocks = blockEditor.InnerBlocks;

	var ALLOWED = [ 'rockaden/sidebar-card' ];

	blocks.registerBlockType( 'rockaden/sidebar', {
		edit: function () {
			// The same class the front end and the settings-driven panel use, so
			// the editor shows the real spacing rather than an approximation.
			var blockProps = useBlockProps( { className: 'rc-sidebar' } );

			var innerBlocksProps = useInnerBlocksProps( blockProps, {
				allowedBlocks: ALLOWED,
				orientation: 'vertical',
				// One empty card to start, so the block is never a blank box.
				template: [ [ 'rockaden/sidebar-card' ] ],
			} );

			return el( 'div', innerBlocksProps );
		},
		// Server-rendered wrapper, so save() only preserves the child markup for
		// render.php to receive as $content.
		save: function () {
			return el( InnerBlocks.Content );
		},
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element );
