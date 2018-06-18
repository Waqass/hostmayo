
var chat = chat || {};

$(document).ready(function(){

    if (roomid > 0) {

        var e = {};
        livevisitor = livevisitor || {};
        chat = chat || {};
        chat.user.fullname = clientexec.admin_fullname;
        chat.roomispublic = 0;
        chat.groupid = 1;
        chat.path = "../plugins/dashboard/livevisitor/chat/";
        chat.track = null; //var storing track information
        chat.activeroomchatterid = 0; //will be set when we get room info after page load
        chat.roomid = roomid;
        chat.user.email = clientexec.admin_email;
        chat.user.gravatar = ce.getAvatarUrl(chat.user.email, 80, chat.user.fullname);
        chat.user.chatterid = $.cookie('achatid');
        chat.set_defaults_for_new_room();
        chat.send_login_info();
        chat.eventserviceid = chat.getuniqueid();

        //chat.roomname = e.roomname;
        livevisitor.loadchatwindow(chat.roomid);

        chat.setup_admin_room();

        //let's show right panel if admin
        $('.maincontainer').removeClass('withoutrightpanel');
        $('.chat-request-info').show();

        setTimeout(function(){
          chat.canscroll = true;
          chat.scrollintoview();
        },1200);
    }

    // $(document).bind('onlineusers-updated', function(event, v1, v2) {
    //   console.debug(clientexec.whoisonline.onlineusers);
    // });

});



chat.setup_admin_room = function() {


    chat.showconfirmonpaste = false;
    //these are the things we only want to do once

    //binding for close room button
    var roomclosebutton_clickhandler = function() {
        RichHTML.alert('Are you sure you want to close this room?',{},function(ret){
            if (ret.btn == lang("Yes")) {
                chat.close_room();
            }
        });
    };

    var roomleavebutton_clickhandler = function() {
        $.ajax({
            url: "index.php?fuse=admin&controller=plugin&action=doplugin&pluginaction=leaveroom&plugin=livevisitor",
            type:'POST',
            data:{
                chatterid: chat.user.chatterid,
                roomid: chat.roomid,
                groupid: chat.groupid
            }
        });
        RichHTML.mask();

        chat.closeactiveroom();

        //allows cache to be updated timely
        setTimeout(function(){location.href="index.php?fuse=admin&controller=plugin&view=doplugin&pluginaction=showadminpanel&plugin=livevisitor";},1500);
    };

    var roombutton_soundhandler = function()
    {
        if (chat.playsound) {
            chat.playsound = false;
            $('.options-room-sound .icon-volume-up').addClass('icon-volume-off').removeClass('icon-volume-up');
        } else {
            chat.playsound = true;
            $('.options-room-sound .icon-volume-off').addClass('icon-volume-up').removeClass('icon-volume-off');
        }

        $.cookie('a_new_message_playsound', chat.playsound);
    };

    $(document).on("click",".options-room-sound button",roombutton_soundhandler);
    $(document).on("click",".close-room button", roomclosebutton_clickhandler);
    $(document).on("click",".leave-room button", roomleavebutton_clickhandler);

    if (($.cookie('a_new_message_playsound') == null) || ($.cookie('a_new_message_playsound') == "true") ) {
        chat.playsound = true;
        $('.options-room-sound i').addClass('icon-volume-up');
    } else {
        chat.playsound = false;
        $('.options-room-sound i').addClass('icon-volume-off');
    }
    $.cookie('a_new_message_playsound', chat.playsound);

    if (window.webkitNotifications) {
        if (window.webkitNotifications.checkPermission() == 0) {
            $('#request_permission').hide();
        } else {
            $('#request_permission').show();
        }
    }

    document.querySelector('#request_permission').addEventListener('click',function(){
        window.webkitNotifications.requestPermission();},false
    );


    $('.maincontainer').scroll(function(e){
        //if (chat.initialload) return;
        //console.debug('scrolling1',chat.initialload);
        //gives an offset of 100 pixels of bottom to screen to determine if I can scroll
        var elem = $(e.currentTarget);
        if ( chat.initialload || (elem[0].scrollHeight - elem.scrollTop() - 180) < elem.outerHeight())
        {
            //scrolled to bottom
            chat.canscroll = true;
        } else {
            chat.canscroll = false;
        }

        //console.debug('scrolling2',chat.canscroll);

    });

    chat.register_keypress($('#msgreply'));

    $.post("index.php?fuse=admin&controller=plugin&action=doplugin&pluginaction=getuserinroominfo&plugin=livevisitor",
        {chatterid:chat.user.chatterid,roomid:chat.roomid},function(data){

        data = jQuery.parseJSON(data);
        chat.add_user_banner_information(data);

        //let's add room title
        $('.room-title-banner').html("<span class='label-title-banner'><a class='titlelink'>"+data.description+"</a></span>");

        //chat.set_all_user_names(data.users);
        chat.add_admin_users_to_attendees(data.admins, data.users);

        chat.startMyEventService(1);

        /* file uploaded */
        $('.msgreply-upload-btn input').bind('fileuploadpaste', function (e, data) {

            if (data.files.length === 0) return true;
            if (!data.files[0].type.match('image.*')) return false;
            chat.showconfirmonpaste = true;
        });

        $('.msgreply-upload-btn input').bind('fileuploaddrop', function (e, data) {
           if ( (data.files.length === 0) || (!data.files[0].type.match('image.*')) ) return false;
           chat.showconfirmonpaste = true;
        });

        $('.msgreply-upload-btn #fileupload').fileupload({
            dataType: 'json',
            done: function (e, data) {
                if (chat.showconfirmonpaste) {
                    RichHTML.alert("Are you sure you want to upload that image",{},function(o){
                        if (o.btn == lang("Yes")){
                            chat.sendimages(data);
                        }
                    });
                } else {
                    chat.sendimages(data);
                }
                chat.showconfirmonpaste = false;
            }
        });

    });


};

chat.closeactiveroom = function()
{
    parent = $('.visitor-active').parent();
    if (parent.find('.item').length == 1) {
        $('.recentlistchats .no-data').show();
    }
    $('.visitor-active').remove();

    if ($('.recentlistchats-content .item').length == 0) {
      $('.recentlistchats-content').append('<div class="no-data">'+lang("No Active Chats")+'</div>');
    }
    livevisitor.plugin.setContent();

    chat.close_event_source();
}

function ce_highlightWords( line, word )
{
     var regex = new RegExp( '(' + word + ')', 'gi' );
     return line.replace( regex, "<b style='color:rgb(204, 65, 0);font-weight:900;'>$1</b>" );
}

chat.addGenericMessage = function(msg)
{

    timestamp = Math.round(new Date().getTime() / 1000);

    messageDate = new Date(timestamp*1000);
    min = (messageDate.getMinutes() < 10) ? "0"+messageDate.getMinutes() : messageDate.getMinutes();
    newtime = messageDate.getHours()+":"+min;

    loggedon = $('<dl><dt><div class="msgtime">'+newtime+'</div></dt><dd><div class="label leftroomlabel">&nbsp;&nbsp;'+msg+'</a>&nbsp;&nbsp;</div></dd></dl>');
    $('#log dl:last').after(loggedon);
    chat.scrollintoview();
};


chat.change_order_of_userlist = function(data)
{
    var online = $('.hello-users i.icon-chatuser-status-online, .hello-users .label-chatuser[data-userid="'+chat.user.chatterid+'"] i').parent().clone();
    var away = $('.hello-users .label-chatuser:not([data-userid="'+chat.user.chatterid+'"]) i.icon-chatuser-status-away').parent().clone();
    var offline = $('.hello-users .label-chatuser:not([data-userid="'+chat.user.chatterid+'"]) i.icon-chatuser-status-offline').parent().clone();

    $('.hello-users .label-chatuser').remove();
    $('.hello-users').append(online);
    $('.hello-users').append(away);
    $('.hello-users').append(offline);

    //move myself to top
    //TODO

};

/**
 * used to update all users
 * @param array data list of users
 */
chat.add_admin_users_to_attendees = function(admins, usersinroom)
{

    //let's create attendees
    //add myself of list of users since I'm not doing that in the service
    // users.unshift({chatterid:chat.user.chatterid,user:chat.user.fullname,email:chat.user.email,usertype:1,movetotop:true});
    $('.hello-users .label-chatuser').remove();
    $.each(usersinroom,function(index,obj) {
        chat.build_attendees_list(obj);
    });

    chat.change_order_of_userlist();

};

/**
 * Adds one name to the attendees list
 * @param  {[type]} obj [description]
 * @return {[type]}     [description]
 */
chat.build_attendees_list = function(obj)
{
    //let's see if admin is in room
    if (obj.loggedin == 0) {
        myclass = "system-offline";
        classNow = "icon-chatuser-status-offline";
    }else if (( (obj.timediff/60) < 10)) { //10 minutes
        myclass = "system-online";
        classNow = "icon-chatuser-status-online";
    } else {
        myclass = "system-away";
        classNow = "icon-chatuser-status-away";
    }

    if (obj.usertype == 0) {
        label_class_name = "customer-name";
        label_name = "<i>"+ce.htmlspecialchars(obj.user)+"</i>";
    } else {
        label_class_name = "staff-name";
        label_name = ce.htmlspecialchars(obj.user);
    }

    $('.hello-users').append('<div class="label-chatuser" data-userid="'+obj.id+'" data-email="'+obj.email+'" style="display:none;"><i class="'+myclass+' '+classNow+' icon-circle"></i>&nbsp;&nbsp;<span class="'+label_class_name+'">'+label_name+'</span></div>');
    $('.label-chatuser[data-userid="'+obj.id+'"]').show();

}


/**
 * used to only update changes to users
 * @param data list of "new" users (or after they have expired sse)
 */
chat.set_other_users_name = function(data)
{
    // console.debug("chat.set_other_users_name");
    chat.add_admin_users_to_attendees(data.admins,data.users);
};

chat.add_footprint_dom = function(data)
{
    if ($('#log dl.'+chat.activeroomchatterid+':last').length > 0) {
        $('<dd><div class="label footprintlabel"><img style="width:12px;" src="../plugins/dashboard/livevisitor/assets/footprints.png"/>&nbsp;&nbsp;&nbsp;<a target="_blank" href="'+data.url+'">Viewing '+data.title+'</a>&nbsp;&nbsp;</div></dd>').appendTo($('#log dl.'+chat.activeroomchatterid+':last'));
        chat.scrollintoview();
    }
};

/**
 * method to handle typing event responses
 * @param  {[type]} e [description]
 * @return {[type]}   [description]
 */
chat.handle_typing_event = function(e)
{
    var data = $.parseJSON(e.data);

    //console.debug("Event:typing",data);
    $.each(data.users,function(index,obj){

        user = $('.hello-users [data-email="'+obj.email+'"]');
        chat.remove_typing_image(null,obj.email);

        if (obj.subtype === "1") {
            user.addClass('typing').append('<span class="user-typing-icon"><img src="'+chat.path+'../assets/typing.png" /></span>');
        }
    });

};

chat.remove_typing_image = function(chatterid,email)
{
    $('.hello-users [data-email="'+email+'"]').removeClass('typing');
    $('.hello-users [data-email="'+email+'"] span.user-typing-icon').remove();
};

chat.new_message_notification = function(data,playsound)
{
    if (playsound) document.getElementById('soundreceived').play();

    clientexec.raise_badge_count();

    //data.msg is already in htmlentities
    msg = data.msg;
    msg = msg.replace(/&lt;/g,"<");
    msg = msg.replace(/&gt;/g,">");
    clientexec.notifybrowser(data.fullname + " says...", msg);
};

chat.check_if_user_should_be_logged_off = function(e)
{
  //chat.close_event_source();
  chat.startMyEventService(1);
};

chat.handleroomstatusupdate = function(e)
{
    //console.debug("Event:statusupdate");
    chat.internal_close_room(e);
};

chat.add_file = function(e) {
    //console.debug(e);
}

chat.internal_close_room = function()
{
    chat.roomid = 0;
    chat.activeroomchatterid = 0;
    chat.track = null;
    chat.data.close();

    //let's remove the active room
    $('.visitor-active').remove();

    $('#livevisitorchat').hide();
    $('.livevisitorchat-closed').html("<em>This chat has closed</em>").show();
    $('.close-room').hide();
}
chat.add_user_banner_information = function(data)
{
    var name = data.fullname + " ( "+data.email+" )";
    var city = "";
    var region = "";
    var country = "";

    $('.chat-request-info').removeClass('chat-request-staff');

    // console.debug(data.session);
    if ( (typeof(data.session.location) != "undefined") && (typeof(data.session.location.address) != "undefined") ) {

        city = data.session.location.address.city;
        region = data.session.location.address.region;
        country = data.session.location.address.country;

        //let's add map
        $('.chat-request-info-map').show();
        $('.chat-request-map-image').attr('src','https://maps.googleapis.com/maps/api/staticmap?center='+city+'%2C'+region+'%2C'+country+'&size=120x150&zoom=4&sensor=false');
        $('.chat-request-info-map .browser').addClass(data.browser_name.toLowerCase()).attr('title',data.browser_name+' '+data.browser_ver);

        strlocation = city+', '+region+' '+country;
        $('.chat-visitor-location').text( strlocation.toLowerCase().capitalize() ).append('<img src="../images/blank.png" class="flag flag-'+data.country+'" alt="Country Flag" style="margin-left: 4px;margin-bottom: 3px;">');
        $('.chat-request-info-map .operating-system img').attr('title',data.session.os).attr('src','../templates/default/img/'+data.os.toLowerCase()+'.png');

    } else {
        if (typeof(data.session.locale) != "undefined") country = data.session.locale.country;
        else country = "US";

        $('.chat-request-info-map').show();
        $('.chat-request-map-image').attr('src','https://maps.googleapis.com/maps/api/staticmap?center='+country+'&size=120x150&zoom=1&sensor=false');
        $('.chat-visitor-location').text( lang("Location not detected") );
    }

    //previous chats
    $('.chat-request-fullname').text(name);
    $('#log').addClass('viewing-customer');

    if (data.isclosed) {
        $('.livevisitorchat-closed').show();
        $('.close-room').hide();
        chat.internal_close_room();
    } else {
        $('#msgreply').show();
        $('.msgreply-options').show();
        $('.close-room').show();
    }

    chat.activeroomchatterid = data.chatterid;
};

String.prototype.capitalize = function(){
   return this.replace( /(^|\s)([a-z])/g , function(m,p1,p2){ return p1+p2.toUpperCase(); } );
};

function doGetCaretPosition (ctrl) {

    var CaretPos = 0;
    // IE Support
    if (document.selection) {

        ctrl.focus ();
        var Sel = document.selection.createRange ();

        Sel.moveStart ('character', -ctrl.value.length);

        CaretPos = Sel.text.length;
    }
    // Firefox support
    else if (ctrl.selectionStart || ctrl.selectionStart == '0')
        CaretPos = ctrl.selectionStart;

    return (CaretPos);

}

function setCaretPosition(ctrl, pos)
{

    if(ctrl.setSelectionRange)
    {
        ctrl.focus();
        ctrl.setSelectionRange(pos,pos);
    }
    else if (ctrl.createTextRange) {
        var range = ctrl.createTextRange();
        range.collapse(true);
        range.moveEnd('character', pos);
        range.moveStart('character', pos);
        range.select();
    }
}
