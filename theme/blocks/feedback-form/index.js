/**
 * Editor script for the Feedback Form block (rockaden/feedback-form).
 *
 * No-build / vanilla JS (mirrors blocks/shop-grid). The block is server-rendered
 * (render.php); the editor renders the SAME markup + classes (read-only) so the
 * preview matches the front end — the theme stylesheet is loaded as an editor
 * style, so width, alignment and field styling all render identically. Heading
 * and intro are edited in the block sidebar.
 */
( function ( blocks, blockEditor, components, element ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var TextareaControl = components.TextareaControl;
	var SelectControl = components.SelectControl;

	function field( labelText, inputEl, hintText ) {
		var children = [ el( 'label', { key: 'l' }, labelText ), inputEl ];
		if ( hintText ) {
			children.push(
				el(
					'span',
					{ key: 'h', className: 'rockaden-feedback__hint' },
					hintText
				)
			);
		}
		return el(
			'p',
			{ className: 'rockaden-feedback__field' },
			children
		);
	}

	blocks.registerBlockType( 'rockaden/feedback-form', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'rockaden-feedback-block',
			} );
			var innerClass =
				'rockaden-feedback__inner' +
				( 'center' === ( attributes.formAlign || 'left' )
					? ' rockaden-feedback__inner--center'
					: '' );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Content' },
						el( TextControl, {
							label: 'Heading',
							value: attributes.heading || '',
							onChange: function ( val ) {
								setAttributes( { heading: val } );
							},
						} ),
						el( TextareaControl, {
							label: 'Intro text',
							value: attributes.intro || '',
							onChange: function ( val ) {
								setAttributes( { intro: val } );
							},
						} ),
						el( SelectControl, {
							label: 'Form alignment',
							value: attributes.formAlign || 'left',
							options: [
								{ label: 'Left', value: 'left' },
								{ label: 'Center', value: 'center' },
							],
							onChange: function ( val ) {
								setAttributes( { formAlign: val } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el(
						'div',
						{ className: innerClass },
						attributes.heading
							? el(
									'h2',
									{ className: 'rockaden-feedback__heading' },
									attributes.heading
							  )
							: null,
						attributes.intro
							? el(
									'p',
									{ className: 'rockaden-feedback__intro' },
									attributes.intro
							  )
							: null,
						el(
							'form',
							{
								className: 'rockaden-feedback',
								onSubmit: function ( e ) {
									e.preventDefault();
								},
							},
							field(
								'Namn',
								el( 'input', {
									key: 'i',
									type: 'text',
									readOnly: true,
								} )
							),
							field(
								'E-post',
								el( 'input', {
									key: 'i',
									type: 'email',
									readOnly: true,
								} ),
								'Valfritt – fyll i om du vill att vi ska kunna kontakta dig.'
							),
							field(
								'Meddelande',
								el( 'textarea', {
									key: 'i',
									rows: 5,
									readOnly: true,
								} )
							),
							el(
								'button',
								{
									type: 'button',
									className: 'rockaden-feedback__submit',
								},
								'Skicka'
							)
						)
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element );
