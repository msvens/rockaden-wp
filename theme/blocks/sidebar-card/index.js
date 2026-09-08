/**
 * Editor script for one Sidebar Card (rockaden/sidebar-card).
 *
 * No-build / vanilla JS: uses the global `wp` packages and wp.element.createElement
 * instead of JSX, so the theme needs no compilation step (mirrors
 * theme/blocks/shop-grid). Dependencies are declared in the sibling
 * index.asset.php.
 *
 * The card is FIELDS, not free-form inner blocks, and that is deliberate. The
 * settings screen at Appearance -> Rockaden won't let anyone put a table or a
 * video in a sidebar, and moving the cards into the editor should not quietly
 * hand that back. So the same fields appear here, and the text case uses a
 * RichText limited to bold, italic and links — the equivalent of the small
 * visual editor the settings form provides.
 *
 * The image is chosen from the media library rather than pasted as a URL, so an
 * attachment ID is stored: that is what gives Gutenberg its crop and rotate
 * tools and the front end a responsive srcset.
 */
( function ( blocks, blockEditor, components, element ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var MediaPlaceholder = blockEditor.MediaPlaceholder;
	var MediaReplaceFlow = blockEditor.MediaReplaceFlow;
	var BlockControls = blockEditor.BlockControls;
	var RichText = blockEditor.RichText;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var SelectControl = components.SelectControl;

	// Bold, italic and link only. Anything richer belongs in the page body, not
	// in a sidebar panel.
	var ALLOWED_FORMATS = [ 'core/bold', 'core/italic', 'core/link' ];

	blocks.registerBlockType( 'rockaden/sidebar-card', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var isImage = attributes.cardType === 'image';

			var className =
				'rc-sidebar-card' +
				( isImage && attributes.fullBleed
					? ' rc-sidebar-card--full-bleed'
					: '' );
			var blockProps = useBlockProps( { className } );

			function onSelectMedia( media ) {
				if ( ! media || ! media.url ) {
					return;
				}
				setAttributes( {
					mediaId: media.id || 0,
					mediaUrl: media.url,
					alt: media.alt || attributes.alt || '',
				} );
			}

			var settings = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: 'Card' },
					el( SelectControl, {
						label: 'Type',
						value: attributes.cardType,
						options: [
							{ label: 'Text', value: 'text' },
							{ label: 'Image', value: 'image' },
						],
						onChange: function ( val ) {
							setAttributes( { cardType: val } );
						},
					} ),
					el( TextControl, {
						label: 'Title',
						value: attributes.title,
						onChange: function ( val ) {
							setAttributes( { title: val } );
						},
					} ),
					el( ToggleControl, {
						label: 'Show title',
						checked: !! attributes.showTitle,
						onChange: function ( val ) {
							setAttributes( { showTitle: !! val } );
						},
					} ),
					isImage &&
						el( ToggleControl, {
							label: 'Full bleed',
							help: 'Drop the card background and padding so the image fills the width.',
							checked: !! attributes.fullBleed,
							onChange: function ( val ) {
								setAttributes( { fullBleed: !! val } );
							},
						} ),
					isImage &&
						el( TextControl, {
							label: 'Alt text',
							help: 'Describes the image for screen readers.',
							value: attributes.alt,
							onChange: function ( val ) {
								setAttributes( { alt: val } );
							},
						} ),
					el( TextControl, {
						label: 'Link',
						help: isImage
							? 'Where the image links to. Leave empty for no link.'
							: 'Shown as a button under the text. Needs a label too.',
						value: attributes.linkUrl,
						onChange: function ( val ) {
							setAttributes( { linkUrl: val } );
						},
					} ),
					! isImage &&
						el( TextControl, {
							label: 'Button label',
							value: attributes.linkLabel,
							onChange: function ( val ) {
								setAttributes( { linkLabel: val } );
							},
						} )
				)
			);

			var heading =
				attributes.showTitle && attributes.title
					? el( 'h3', null, attributes.title )
					: null;

			if ( isImage ) {
				return el(
					'div',
					blockProps,
					settings,
					heading,
					attributes.mediaUrl
						? el(
								Fragment,
								null,
								el(
									BlockControls,
									null,
									el( MediaReplaceFlow, {
										mediaId: attributes.mediaId,
										mediaURL: attributes.mediaUrl,
										accept: 'image/*',
										allowedTypes: [ 'image' ],
										onSelect: onSelectMedia,
									} )
								),
								el( 'img', {
									src: attributes.mediaUrl,
									alt: attributes.alt || '',
								} )
						  )
						: el( MediaPlaceholder, {
								icon: 'format-image',
								labels: {
									title: 'Sidebar image',
									instructions:
										'Upload an image or pick one from the media library.',
								},
								accept: 'image/*',
								allowedTypes: [ 'image' ],
								onSelect: onSelectMedia,
						  } )
				);
			}

			return el(
				'div',
				blockProps,
				settings,
				heading,
				el( RichText, {
					tagName: 'div',
					value: attributes.content,
					allowedFormats: ALLOWED_FORMATS,
					placeholder: 'Sidebar text…',
					onChange: function ( val ) {
						setAttributes( { content: val } );
					},
				} )
			);
		},
		// Server-rendered: the front-end markup comes from render.php, so nothing
		// is written into post content but the attributes themselves.
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
