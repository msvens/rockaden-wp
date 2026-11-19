/**
 * Editor script for the Membership Form block (rockaden/membership-form).
 *
 * No-build / vanilla JS. The block is server-rendered; the editor shows the
 * real render.php output through ServerSideRender, so the preview follows the
 * settings (birth date or personnummer, the school list) without a copy of
 * the markup here. The form is inert in the editor: its view script only runs
 * on the front end.
 */
( function ( blocks, blockEditor, components, element, serverSideRender ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var Disabled = components.Disabled;

	blocks.registerBlockType( 'rockaden/membership-form', {
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
						{ title: 'Form' },
						el( SelectControl, {
							label: 'Request',
							value: attributes.variant || 'join',
							options: [
								{ label: 'Join (Anmälan)', value: 'join' },
								{ label: 'Leave (Utträde)', value: 'leave' },
							],
							onChange: function ( val ) {
								setAttributes( { variant: val } );
							},
						} ),
						// Only a join form has a category (see render.php).
						'leave' === attributes.variant ? null : el( SelectControl, {
							label: 'Category',
							help: 'Fix the category to hide the picker, e.g. on the Skolschack page.',
							value: attributes.category || '',
							options: [
								{ label: 'Visitor chooses', value: '' },
								{ label: 'Skolschack', value: 'skolschack' },
								{ label: 'Klubbschack', value: 'klubbschack' },
								{ label: 'Vuxen medlem', value: 'vuxen' },
								{ label: 'Pensionär', value: 'pensionar' },
							],
							onChange: function ( val ) {
								setAttributes( { category: val } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el(
						Disabled,
						null,
						el( serverSideRender, {
							block: 'rockaden/membership-form',
							attributes: attributes,
						} )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element,
	window.wp.serverSideRender
);
