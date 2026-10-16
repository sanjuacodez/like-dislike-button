/**
 * Likenook Buttons block (no build step).
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var c = wp.components;
	var ServerSideRender = wp.serverSideRender;
	var opts = window.likenookBlockOptions || {};

	function choices( list ) {
		return [ { label: __( 'Use plugin setting', 'likenook' ), value: '' } ].concat( list || [] );
	}

	function select( props, key, label, list ) {
		return el( c.SelectControl, {
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
			label: label,
			value: props.attributes[ key ],
			options: choices( list ),
			onChange: function ( value ) {
				var change = {};
				change[ key ] = value;
				props.setAttributes( change );
			},
		} );
	}

	wp.blocks.registerBlockType( 'likenook/buttons', {
		edit: function ( props ) {
			var a = props.attributes;
			var postId = props.context.postId;
			var blockProps = be.useBlockProps();
			var yesNo = [
				{ label: __( 'Show', 'likenook' ), value: 'yes' },
				{ label: __( 'Hide', 'likenook' ), value: 'no' },
			];

			return el(
				'div',
				blockProps,
				el(
					be.BlockControls,
					{ group: 'block' },
					el( be.AlignmentControl, {
						value: a.alignment,
						onChange: function ( value ) {
							props.setAttributes( { alignment: value || '' } );
						},
					} )
				),
				el(
					be.InspectorControls,
					null,
					el(
						c.PanelBody,
						{ title: __( 'Look', 'likenook' ) },
						el(
							c.__experimentalVStack || 'div',
							{ spacing: 4 },
							select( props, 'iconSet', __( 'Icons', 'likenook' ), opts.iconSets ),
							select( props, 'buttonStyle', __( 'Button style', 'likenook' ), opts.styles ),
							select( props, 'size', __( 'Size', 'likenook' ), opts.sizes ),
							select( props, 'layout', __( 'Layout', 'likenook' ), opts.layouts ),
							select( props, 'counts', __( 'Counts', 'likenook' ), opts.counts ),
							select( props, 'showLabels', __( 'Labels', 'likenook' ), yesNo ),
							select( props, 'ratioBar', __( 'Like ratio bar', 'likenook' ), yesNo )
						)
					),
					el(
						c.PanelBody,
						{ title: __( 'Text', 'likenook' ), initialOpen: false },
						el( c.TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: __( 'Question above the buttons', 'likenook' ),
							help: __( 'Leave empty to use the plugin setting.', 'likenook' ),
							value: a.question || '',
							onChange: function ( value ) {
								props.setAttributes( { question: value || undefined } );
							},
						} ),
						el( 'div', { style: { height: 16 } } ),
						el( c.TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: __( 'Like label', 'likenook' ),
							value: a.likeLabel || '',
							onChange: function ( value ) {
								props.setAttributes( { likeLabel: value || undefined } );
							},
						} ),
						el( 'div', { style: { height: 16 } } ),
						el( c.TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: __( 'Dislike label', 'likenook' ),
							value: a.dislikeLabel || '',
							onChange: function ( value ) {
								props.setAttributes( { dislikeLabel: value || undefined } );
							},
						} ),
						el( 'div', { style: { height: 16 } } ),
						el( c.TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: __( 'Thank-you message', 'likenook' ),
							value: a.thanks || '',
							onChange: function ( value ) {
								props.setAttributes( { thanks: value || undefined } );
							},
						} )
					)
				),
				el(
					c.Disabled,
					null,
					el( ServerSideRender, {
						block: 'likenook/buttons',
						attributes: a,
						urlQueryArgs: postId ? { post_id: postId } : {},
						skipBlockSupportAttributes: true,
						EmptyResponsePlaceholder: function () {
							return el( c.Placeholder, {
								icon: 'thumbs-up',
								label: __( 'Likenook Buttons', 'likenook' ),
								instructions: __( 'Shows the buttons for the post being viewed. In a sidebar they appear on single posts and pages, not on blog or archive pages.', 'likenook' ),
							} );
						},
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
