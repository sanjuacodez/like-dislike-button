/**
 * Most Liked Posts block (no build step).
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var c = wp.components;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'like-dislike-button/most-liked', {
		edit: function ( props ) {
			var a = props.attributes;
			var blockProps = be.useBlockProps();
			var postTypes = wp.data.useSelect( function ( select ) {
				return select( 'core' ).getPostTypes( { per_page: -1 } );
			}, [] );
			var typeOptions = ( postTypes || [] )
				.filter( function ( type ) {
					return type.viewable && 'attachment' !== type.slug;
				} )
				.map( function ( type ) {
					return { label: type.labels.singular_name || type.name, value: type.slug };
				} );
			if ( ! typeOptions.length ) {
				typeOptions = [ { label: __( 'Posts', 'like-dislike-button' ), value: 'post' } ];
			}

			return el(
				'div',
				blockProps,
				el(
					be.InspectorControls,
					null,
					el(
						c.PanelBody,
						{ title: __( 'Settings', 'like-dislike-button' ) },
						el(
							c.__experimentalVStack || 'div',
							{ spacing: 4 },
							el( c.RangeControl, {
								__nextHasNoMarginBottom: true,
								__next40pxDefaultSize: true,
								label: __( 'Number of posts', 'like-dislike-button' ),
								value: a.number,
								min: 1,
								max: 20,
								onChange: function ( value ) {
									props.setAttributes( { number: value || 5 } );
								},
							} ),
							el( c.SelectControl, {
								__nextHasNoMarginBottom: true,
								__next40pxDefaultSize: true,
								label: __( 'Post type', 'like-dislike-button' ),
								value: a.postType,
								options: typeOptions,
								onChange: function ( value ) {
									props.setAttributes( { postType: value } );
								},
							} ),
							el( c.SelectControl, {
								__nextHasNoMarginBottom: true,
								__next40pxDefaultSize: true,
								label: __( 'Period', 'like-dislike-button' ),
								value: a.period,
								options: [
									{ label: __( 'All time', 'like-dislike-button' ), value: 'all' },
									{ label: __( 'Last 7 days', 'like-dislike-button' ), value: 'week' },
									{ label: __( 'Last 30 days', 'like-dislike-button' ), value: 'month' },
									{ label: __( 'Last 12 months', 'like-dislike-button' ), value: 'year' },
								],
								onChange: function ( value ) {
									props.setAttributes( { period: value } );
								},
							} ),
							el( c.ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: __( 'Show like count', 'like-dislike-button' ),
								checked: a.showCount,
								onChange: function ( value ) {
									props.setAttributes( { showCount: value } );
								},
							} ),
							el( c.ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: __( 'Show featured image', 'like-dislike-button' ),
								checked: a.showThumbnail,
								onChange: function ( value ) {
									props.setAttributes( { showThumbnail: value } );
								},
							} )
						)
					)
				),
				el(
					c.Disabled,
					null,
					el( ServerSideRender, {
						block: 'like-dislike-button/most-liked',
						attributes: a,
						skipBlockSupportAttributes: true,
						EmptyResponsePlaceholder: function () {
							return el( c.Placeholder, {
								icon: 'heart',
								label: __( 'Most Liked Posts', 'like-dislike-button' ),
								instructions: __( 'No liked posts yet for this post type and period. The list fills in as visitors vote.', 'like-dislike-button' ),
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
