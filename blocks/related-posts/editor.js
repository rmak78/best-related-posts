/* global wp */
( function () {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'best-related-posts/related-posts', {
		edit: function ( props ) {
			var max = props.attributes.max || 0;
			var blockProps = useBlockProps();

			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Related posts', 'best-related-posts' ) },
						el( RangeControl, {
							label: __( 'Number of posts (0 = use plugin setting)', 'best-related-posts' ),
							value: max,
							min: 0,
							max: 20,
							onChange: function ( value ) {
								props.setAttributes( { max: parseInt( value, 10 ) || 0 } );
							}
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'best-related-posts/related-posts',
						attributes: { max: max }
					} )
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )();
