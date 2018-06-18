chat = chat || {};
chat.path = "plugins/dashboard/livevisitor/chat/";
chat.groupid = 0;
chat.roomispublic = 1;

$(document).ready(function(){
	chat.plugin = new ceSidebarPlugin({pluginname:"livevisitor"});	
	if (typeof(public_room_id) != "undefined") chat.start_public_room();
});

chat.start_public_room = function()
{
	chat.registerwithserver('',{
		chatid: clientexec.customerId,
		chatfullname: clientexec.customerFullName,
		chatemail: clientexec.customerEmail,
		chatroomid: public_room_id
	});
	$('.msgs-wrapper').show();
	$('.login-wrapper').hide();

	chat.publicroom_reset_chatroom_cookie();

    //let's show last message
    setTimeout(function(){
    	chat.canscroll = true;
		chat.scrollintoview();
    },700);

    //let's get public rooms
    chat.plugin.addHeartBeat({
        name: 'getlistofpublicrooms',
        delay:1,
        pulse:45,
        args: {
            ispublic: 1
        },
        callback: function(response) {
        	chat.publicroom_check_if_user_should_be_logged_off();
            if (response) {
            	$.each(response,function(i,o) {
            		if (o.room.id == public_room_id) {
            			$('.room-description').text(o.room.description);
            			$('.list-user').remove();
            			user_elements = "";
            			$.each(o.users,function(i2,o2) {

            				if (o2.usertype == 1) {							
            					user_elements += "<li data-chatterid='"+o2.chatterid+"' class='list-user'><a href='#'><i class='icon-certificate'></i>&nbsp;&nbsp;"+o2.fullname+"</li>";							
            				} else {
            					user_elements += "<li data-chatterid='"+o2.chatterid+"' class='list-user'><a href='#'>"+o2.fullname+"</li>";
            				}

            			});
            			$('.room-user-list').append(user_elements);

            		}
            	});              
            }
        }
    });
}


$(document).on('click','.btn-log-out-room',function(){
	chat.publicroom_logout();
});

chat.publicroom_handleroomstatusupdate = function(e) {
	//not really sure when I get here
	alert('chat.publicroom_handleroomstatusupdate!!!!');
}

chat.publicroom_check_if_user_should_be_logged_off = function(e)
{

	var cookieexists = true;

	//is cookie expired
	if ($.cookie('public_chatroomid') == null) 
	{
		cookieexists = false;
	}

	if (!cookieexists) {
		chat.publicroom_logout("timeout");
	} else {
		chat.close_event_source();
		chat.startMyEventService(0);
	}
}

chat.publicroom_logout = function(reason)
{
	if (typeof(reason) == "undefined") reason = "logout";

	RichHTML.mask();
	$.ajax({
        url: "index.php?fuse=admin&controller=plugin&action=doplugin&pluginaction=leaveroom&plugin=livevisitor",
        type:'POST',
        data:{
            chatterid: clientexec.customerId,
            roomid: public_room_id,
            groupid: 0
        },
        success: function(response) {        
        	window.location.href = "index.php?fuse=admin&controller=plugin&view=doplugin&pluginaction=showpublicroom&plugin=livevisitor&msgreason="+reason;
        }
    });
}

chat.publicroom_handle_typing_event = function(data) {
	//console.debug(data);
}

chat.publicroom_reset_chatroom_cookie = function() {
	var date = new Date();
	var minutes = 6;
	date.setTime(date.getTime() + (minutes * 60 * 1000));
	$.removeCookie("public_chatroomid");
	$.cookie('public_chatroomid',public_room_id,{expires: date}); 
}
