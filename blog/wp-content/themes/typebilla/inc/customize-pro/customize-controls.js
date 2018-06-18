( function( api ) {

	// Extends our custom "type-pro" section.
	api.sectionConstructor['type-pro'] = api.Section.extend( {

		// No events for this type of section.
		attachEvents: function () {},

		// Always make the section active.
		isContextuallyActive: function () {
			return true;
		}
	} );

} )( wp.customize );
