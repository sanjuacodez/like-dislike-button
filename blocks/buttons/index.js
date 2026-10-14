/**
 * Like Dislike Buttons block (no build step).
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var c = wp.components;
	var ServerSideRender = wp.serverSideRender;
	var opts = window.ldbtnBlockOptions || {};

	function choices( list ) {
		return [ { label: __( 'Use plugin setting', 'like-dislike-button' ), value: '' } ].concat( list || [] );
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

	wp.blocks.registerBlockType( 'like-dislike-button/buttons', {
		edit: function ( props ) {
			var a = props.attributes;
			var postId = props.context.postId;
			var blockProps = be.useBlockProps();
			var yesNo = [
				{ label: __( 'Show', 'like-dislike-button' ), value: 'yes' },
				{ label: __( 'Hide', 'like-dislike-button' ), value: 'no' },
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
						{ title: __( 'Look', 'like-dislike-button' ) },
						el(
							c.__experimentalVStack || 'div',
							{ spacing: 4 },
							select( props, 'iconSet', __( 'Icons', 'like-dislike-button' ), opts.iconSets ),
							select( props, 'buttonStyle', __( 'Button style', 'like-dislike-button' ), opts.styles ),
							select( props, 'size', __( 'Size', 'like-dislike-button' ), opts.sizes ),
							select( props, 'layout', __( 'Layout', 'like-dislike-button' ), opts.layouts ),
							select( props, 'counts', __( 'Counts', 'like-dislike-button' ), opts.counts ),
							select( props, 'showLabels', __( 'Labels', 'like-dislike-button' ), yesNo ),
							select( props, 'ratioBar', __( 'Like ratio bar', 'like-dislike-button' ), yesNo )
						)
					),
					el(
						c.PanelBody,
						{ title: __( 'Text', 'like-dislike-button' ), initialOpen: false },
						el( c.TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: __( 'Question above the buttons', 'like-dislike-button' ),
							help: __( 'Leave empty to use the plugin setting.', 'like-dislike-button' ),
							value: a.question || '',
							onChange: function ( value ) {
								props.setAttributes( { question: value || undefined } );
							},
						} ),
						el( 'div', { style: { height: 16 } } ),
						el( c.TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: __( 'Like label', 'like-dislike-button' ),
							value: a.likeLabel || '',
							onChange: function ( value ) {
								props.setAttributes( { likeLabel: value || undefined } );
							},
						} ),
						el( 'div', { style: { height: 16 } } ),
						el( c.TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: __( 'Dislike label', 'like-dislike-button' ),
							value: a.dislikeLabel || '',
							onChange: function ( value ) {
								props.setAttributes( { dislikeLabel: value || undefined } );
							},
						} ),
						el( 'div', { style: { height: 16 } } ),
						el( c.TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: __( 'Thank-you message', 'like-dislike-button' ),
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
						block: 'like-dislike-button/buttons',
						attributes: a,
						urlQueryArgs: postId ? { post_id: postId } : {},
						skipBlockSupportAttributes: true,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
