( function ( blocks, element, i18n, serverSideRender ) {
	var createElement = element.createElement;
	var ServerSideRender = serverSideRender.default || serverSideRender;

	blocks.registerBlockType( 'trinity-preschool/weekly-events', {
		edit: function () {
			return createElement(
				'div',
				{ className: 'tp-week-events-editor-preview' },
				createElement(
					'p',
					{ className: 'tp-week-events-editor-label' },
					i18n.__( 'Live weekly event preview', 'trinity-site-functionality' )
				),
				createElement( ServerSideRender, { block: 'trinity-preschool/weekly-events' } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
