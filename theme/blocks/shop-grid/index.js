/**
 * Editor script for the Shop Items block (rockaden/shop-grid).
 *
 * No-build / vanilla JS: uses the global `wp` packages and wp.element.createElement
 * instead of JSX, so the theme needs no compilation step (mirrors how
 * theme/blocks/section-nav ships hand-written view.js). Dependencies are declared
 * in the sibling index.asset.php.
 *
 * The block is server-rendered (render.php); the editor only provides the
 * settings panel plus a static placeholder preview.
 */
( function ( blocks, blockEditor, components, element ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var SelectControl = components.SelectControl;

	blocks.registerBlockType( 'rockaden/shop-grid', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Settings' },
						el( TextControl, {
							label: 'Count',
							value: String( attributes.count || '' ),
							onChange: function ( val ) {
								setAttributes( {
									count: Math.max( 1, Number( val ) || 1 ),
								} );
							},
						} ),
						el( SelectControl, {
							label: 'Layout',
							value: attributes.layout,
							options: [
								{ label: 'Grid (multi-column)', value: 'grid' },
								{ label: 'Column (vertical)', value: 'column' },
							],
							onChange: function ( val ) {
								setAttributes( { layout: val } );
							},
						} ),
						el( TextControl, {
							label: 'Archive URL',
							value: attributes.moreUrl,
							onChange: function ( val ) {
								setAttributes( { moreUrl: val } );
							},
						} ),
						el( TextControl, {
							label: 'More link label',
							value: attributes.moreLabel,
							onChange: function ( val ) {
								setAttributes( { moreLabel: val } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el(
						'p',
						{
							style: {
								padding: '2rem',
								background: '#f0f0f0',
								textAlign: 'center',
								borderRadius: '0.25rem',
								color: '#6b7280',
							},
						},
						'[ Shop Items — ' +
							( attributes.count || 4 ) +
							' items, ' +
							( attributes.layout || 'grid' ) +
							' layout ]'
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element );
