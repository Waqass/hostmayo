var webhooks = webhooks || {};
webhooks.radiocounter = 1;

$(document).ready(function(){
	$('.hook-group').each(function(index,obj){
		//let's go mark each hook with the active event
		$(obj).find('.active-event-type').text($(obj).find('.event-type.active a').text());
		$(obj).find('.active-event-type').attr('data-event-type',$(obj).find('.event-type.active a').attr('data-event-type'));
	});

	$('body').on('click','.remove-event',webhooks.remove_event);
	$('body').on('click','.add-additional-hook a',webhooks.clone_new_group);
	$('body').on('click','.btn-save-changes',webhooks.save_hooks);
	$('body').on('click','.btn-test-hooks',webhooks.test_hooks);
	$('body').on('click','.dropdown-event-type',webhooks.update_event_type);
	$('body').on('click','.hook-radio-group input',webhooks.update_hook_type);


	//let's always start with one empty
	webhooks.clone_new_group();

});

webhooks.update_hook_type = function(e)
{
	if($(this).val() == 1) {
		$(this).closest('.hook-group').find('.webhook-url').attr('placeholder',lang('Your secret key'));
	} else {
		$(this).closest('.hook-group').find('.webhook-url').attr('placeholder',"https://webhookurlhere.com");
	}

}

webhooks.update_event_type = function(e) {

	e.preventDefault();

	$(this).closest('.hook-group').find('li.active').removeClass('active');
	$(this).closest('li').addClass('active');

	var eventName = $(this).text()
	var eventType = $(this).attr('data-event-type')

	$(this).closest('.hook-group').find('.active-event-type').text(eventName);
	$(this).closest('.hook-group').find('.active-event-type').attr('data-event-type',eventType);
}

webhooks.test_hooks = function(e) {
	$.post('index.php?fuse=admin&controller=hook&action=testwebhooks',{},function(response) {
		json = ce.parseResponse(response);
	});
}

webhooks.remove_event = function(e){
	e.preventDefault();

	var self = this;
	var event_id = $(this).attr('data-event-id');

	//see if we are removing an event that hasn't even been saved
	if (event_id == 0) {
		$(self).closest('.hook-group').remove();
		if ($('.hook-group').length == 0) webhooks.clone_new_group(e);
		return true;
	}

	//remove from db
	RichHTML.mask();
	$.post('index.php?fuse=admin&controller=hook&action=deletewebhook',{id:event_id},function(response) {
		json = ce.parseResponse(response);
		if (!json.error) {
			$(self).closest('.hook-group').remove();
			//if we remove last one add a blank
			if ($('.hook-group').length == 0) webhooks.clone_new_group(e);
		}
		RichHTML.unMask();
	});
}

webhooks.save_hooks = function(e)
{
	var event_url = "";
	var event_type;
	var postdata = [];
	$('.hook-group').each(function(index,obj){

		hook_type = $(obj).find('.hook-radio-group input:checked').val();
		event_type = $(obj).find('.event-type.active a').attr('data-event-type');
		webhook_url = $(obj).find('.webhook-url').val();
		if (typeof(event_type) != "undefined" && ($.trim(webhook_url) != ""))
		{
			postdata.push({url:webhook_url, type:event_type, hooktype: hook_type});
		}
	});

	RichHTML.mask();
	$.post('index.php?fuse=admin&controller=hook&action=savewebhooks',{hooks:postdata},function(response) {
		json = ce.parseResponse(response);
		if (!json.error) {
			window.location.href = "index.php?fuse=admin&view=webhooks&controller=hook";
		}
	});
	RichHTML.unMask();

}

/**
 * clone the mock div when we need to add more inputs for webhooks
 * @param  event e
 * @return void
 */
webhooks.clone_new_group = function(e)
{
	var radioname = "hooks_radio_"+webhooks.radiocounter++;

	if (typeof(e) != "undefined") e.preventDefault();

	el = $('.hook-group-base').clone();

	//let's rename the radio button
	el.find('.hook-radio-group input[name="webhook_type"]').attr('name',radioname);

	$(el).addClass('hook-group').removeClass('hook-group-base').show();
	$('.webhooks-content').append(el);

}