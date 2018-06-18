permissions = {};

$(document).ready(function(){

	$('.acoordion-permissions .accordion-toggle').bind('click',function(e){

		if ($(this).parent().hasClass('active-header')) {
			$('.accordion-heading.active-header').removeClass('active-header');
		} else {			
			$('.accordion-heading.active-header').removeClass('active-header');
			$(this).parent().addClass('active-header');	
		}		

		permissions.striprows();

	});
	
    $('.permission-switch').on('switch-change', function (e, data) {
    	var my_id = $(this).closest('tr').attr('data-permission-id');
    	//$('tr[data-child-of-id="'+my_id+'"]').togggle();
    	permissions.toggle_permissions(data.value, my_id);

    });

    $('.update-permission-btn').on('click',function(e){
    	$.post('index.php?fuse=admin&controller=roles&action=savepermissions', $('#groupForm').serialize(), function(response){
    		ce.parseResponse(response);
    	})
    });

});

permissions.toggle_permissions = function(row_state,my_id)
{

	if (row_state) { 

    	 $('tr[data-child-of-id="'+my_id+'"]')
    	 .show()
		 .find('td') 
		 .wrapInner('<div style="display: none;" />')
		 .parent()
		 .find('td > div')
		 .slideDown(300,function(){
		 	var $set = $(this);
  			$set.replaceWith($set.contents());
  			//$('tr[data-child-of-id="'+my_id+'"] .permission-switch').bootstrapSwitch('setState', true);  			
		 });
	 } else {
	 	$('tr[data-child-of-id="'+my_id+'"]')
		 .find('td')
		 .wrapInner('<div style="display: block;" />')
		 .parent()
		 .find('td > div')
		 .slideUp(300, function(){

		 	var $set = $(this);
  			$set.replaceWith($set.contents());	 	
		  	$('tr[data-child-of-id="'+my_id+'"]').hide();

  			$('tr[data-child-of-id="'+my_id+'"] .permission-switch').bootstrapSwitch('setState', false);  			

		 });
	 }

}

permissions.striprows = function()
{
	$('.table tbody tr:visible:even').addClass('table-striped-visible');
}