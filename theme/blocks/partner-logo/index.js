/**
 * Editor script for a single Partner Logo (rockaden/partner-logo).
 *
 * No-build / vanilla JS: uses the global `wp` packages and wp.element.createElement
 * instead of JSX, so the theme needs no compilation step (mirrors
 * theme/blocks/shop-grid). Dependencies are declared in the sibling
 * index.asset.php.
 *
 * The image is chosen through the media library rather than by pasting a URL, so
 * an attachment ID is stored. That ID is what gives Gutenberg its crop and
 * rotate tools, and what lets the front end emit a responsive srcset — the
 * landing-why pattern's URL-only <img> has neither.
 */
( function ( blocks, blockEditor, components, element ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var BlockControls = blockEditor.BlockControls;
	var MediaPlaceholder = blockEditor.MediaPlaceholder;
	var MediaReplaceFlow = blockEditor.MediaReplaceFlow;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;

	blocks.registerBlockType( 'rockaden/partner-logo', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'rockaden-partner-logo',
			} );

			function onSelect( media ) {
				if ( ! media || ! media.url ) {
					return;
				}
				setAttributes( {
					mediaId: media.id || 0,
					mediaUrl: media.url,
					// Prefer the alt already set in the media library; the
					// editor can still override it below.
					alt: media.alt || attributes.alt || '',
				} );
			}

			var settings = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: 'Settings' },
					el( TextControl, {
						label: 'Link',
						help: 'Where the logo links to. Leave empty for no link.',
						value: attributes.url || '',
						onChange: function ( val ) {
							setAttributes( { url: val } );
						},
					} ),
					el( TextControl, {
						label: 'Alt text',
						help: 'Describes the logo for screen readers, e.g. the partner name.',
						value: attributes.alt || '',
						onChange: function ( val ) {
							setAttributes( { alt: val } );
						},
					} )
				)
			);

			if ( ! attributes.mediaUrl ) {
				return el(
					'div',
					blockProps,
					settings,
					el( MediaPlaceholder, {
						icon: 'format-image',
						labels: {
							title: 'Partner logo',
							instructions:
								'Upload a logo or pick one from the media library.',
						},
						accept: 'image/*',
						allowedTypes: [ 'image' ],
						onSelect,
					} )
				);
			}

			return el(
				'div',
				blockProps,
				settings,
				el(
					BlockControls,
					null,
					el( MediaReplaceFlow, {
						mediaId: attributes.mediaId,
						mediaURL: attributes.mediaUrl,
						accept: 'image/*',
						allowedTypes: [ 'image' ],
						onSelect,
					} )
				),
				el( 'img', {
					src: attributes.mediaUrl,
					alt: attributes.alt || '',
				} )
			);
		},
		// Server-rendered: the front-end markup comes from render.php, so
		// nothing is written into post content but the attributes themselves.
		save: function () {
			return null;
		},
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element
);
