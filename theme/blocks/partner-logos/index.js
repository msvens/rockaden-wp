/**
 * Editor script for the Partner Logos container (rockaden/partner-logos).
 *
 * No-build / vanilla JS: uses the global `wp` packages and wp.element.createElement
 * instead of JSX, so the theme needs no compilation step (mirrors
 * theme/blocks/shop-grid). Dependencies are declared in the sibling
 * index.asset.php.
 *
 * Unlike the theme's other blocks this one renders its children live rather than
 * a static placeholder, because managing the logos IS the point of the block —
 * a grey box saying "[ Partner Logos ]" would hide the only thing worth seeing.
 *
 * allowedBlocks restricts the children to Partner Logo, but the block is
 * deliberately NOT locked with templateLock "contentOnly": that mode forbids
 * inserting new inner blocks, which would take away the ability to add a
 * partner — the whole reason this is a block rather than a pattern.
 */
( function ( blocks, blockEditor, components, element ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useBlockProps = blockEditor.useBlockProps;
	var useInnerBlocksProps = blockEditor.useInnerBlocksProps;
	var InnerBlocks = blockEditor.InnerBlocks;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var RangeControl = components.RangeControl;

	var ALLOWED = [ 'rockaden/partner-logo' ];

	blocks.registerBlockType( 'rockaden/partner-logos', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var blockProps = useBlockProps( {
				className:
					'rockaden-partner-logos is-' +
					( attributes.orientation || 'vertical' ),
			} );

			var innerBlocksProps = useInnerBlocksProps( blockProps, {
				allowedBlocks: ALLOWED,
				orientation: attributes.orientation || 'vertical',
				// One empty logo to start, so the block is never a blank box.
				template: [ [ 'rockaden/partner-logo' ] ],
			} );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Settings' },
						el( SelectControl, {
							label: 'Direction',
							value: attributes.orientation || 'vertical',
							options: [
								{ label: 'Stacked', value: 'vertical' },
								{ label: 'Side by side', value: 'horizontal' },
							],
							onChange: function ( val ) {
								setAttributes( { orientation: val } );
							},
						} ),
						el( RangeControl, {
							label: 'Logo width',
							help: 'Maximum width of each logo, in pixels.',
							value: attributes.logoWidth || 160,
							min: 60,
							max: 400,
							step: 10,
							onChange: function ( val ) {
								setAttributes( {
									logoWidth: Number( val ) || 160,
								} );
							},
						} )
					)
				),
				el( 'div', innerBlocksProps )
			);
		},
		// Server-rendered wrapper, so save() only needs to preserve the child
		// block markup for render.php to receive as $content.
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
