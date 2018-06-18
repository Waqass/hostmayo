$(document).ready(function(){
	$('.preference-switch').on('switch-change', function (e, data) {
    	var pref_name = $(this).closest('tr').attr('data-preference-name');
    	clientexec.updateCustomField(pref_name,(data.value) ? 1: 0,adminid, function(response){
    		ce.parseResponse(response);
    	});
    });

	//let's only enable live chat settings if it is enabled
    if ($.inArray("livevisitor", clientexec.sidebarplugins.names) > -1) {
        $('.preference-switches-chat').show();
    }

});
