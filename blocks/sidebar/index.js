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
( function ( blocks, blockEditor, components, element ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var useInnerBlocksProps = blockEditor.useInnerBlocksProps;
	var InnerBlocks = blockEditor.InnerBlocks;
	var InspectorControls = blockEditor.InspectorControls;
	var Fragment = element.Fragment;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;

	// Only a plain CSS length. The value reaches a style attribute, so anything
	// else is dropped rather than trusted — server-side too, in render.php.
	var LENGTH = /^\d+(\.\d+)?(px|rem|em|%|ch|vw)$/;

	var ALLOWED = [ 'rockaden/sidebar-card' ];

	blocks.registerBlockType( 'rockaden/sidebar', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var maxWidth = attributes.maxWidth || '';

			// The same class the front end and the settings-driven panel use, so
			// the editor shows the real spacing rather than an approximation —
			// including the width cap, so its effect is visible while editing.
			var blockProps = useBlockProps( {
				className: 'rc-sidebar',
				style: LENGTH.test( maxWidth ) ? { maxWidth } : undefined,
			} );

			var innerBlocksProps = useInnerBlocksProps( blockProps, {
				allowedBlocks: ALLOWED,
				orientation: 'vertical',
				// One empty card to start, so the block is never a blank box.
				template: [ [ 'rockaden/sidebar-card' ] ],
			} );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Sidebar' },
						el( TextControl, {
							label: 'Maximum width',
							help: 'A CSS length such as 320px or 24rem. Empty fills the column.',
							value: maxWidth,
							onChange: function ( val ) {
								setAttributes( { maxWidth: val } );
							},
						} )
					)
				),
				el( 'div', innerBlocksProps )
			);
		},
		// Server-rendered wrapper, so save() only preserves the child markup for
		// render.php to receive as $content.
		save: function () {
			return el( InnerBlocks.Content );
		},
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element
);
