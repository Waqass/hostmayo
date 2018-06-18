chat = chat || {};
chat.user = chat.user || {};
chat.groupid = 0;

chat.path = "plugins/dashboard/livevisitor/chat/";

chat.registerwithserver = function(firstmessage, user_data)
{
  var chatid,chatfullname,chatemail,chatroomid;

  if (typeof(user_data) != "undefined") {
    //used when coming from public chat
    chat.user.chatterid = user_data.chatid;
    chat.user.fullname = user_data.chatfullname;
    chat.user.email = user_data.chatemail;
    chat.user.gravatar = ce.getAvatarUrl(message.email, 80, chat.user.fullname);
    chat.roomid = user_data.chatroomid;
  } else {
    chat.user.chatterid = $.cookie('chatid');
    chat.user.fullname = $.cookie('chatfullname');
    chat.user.email = $.cookie('chatemail');
    chat.user.gravatar = ce.getAvatarUrl(chat.user.email, 80, chat.user.fullname);
    chat.roomid = $.cookie('chatroomid');
  }

  chat.playsound = true;
  chat.eventserviceid = chat.getuniqueid();

  chat.set_defaults_for_new_room();
  chat.startMyEventService(0);
  //send my information to the room

  chat.send_login_info();

  chat.register_keypress($('#msg'));

  if (typeof(firstmessage) !== "undefined") {
    chat.sendtypedmessage($('input').val(firstmessage),0);
  }
};

chat.check_if_user_should_be_logged_off = function(e)
{

  var cookieexists = true;

  //is cookie expired
  if ($.cookie('chatroomid') == null)
  {
    cookieexists = false;
  }

  if (!cookieexists) {
      chat.handleroomstatusupdate();
  } else {
      chat.close_event_source();
      chat.startMyEventService(0);
  }

};

/** User logged off */
chat.handleroomstatusupdate = function()
{
  $.removeCookie("chatroomid");

  chat.close_event_source();

  //remove the typing image
  $('span.typing_wrapper img').remove();
  $('.operator-status').text("Conversation Closed");
  chat.hellobar.open();
  $('#messageform fieldset').hide();
  $('.restartchatdiv').show();

  chat.close_room();

};


/** let's update cookie so that after customer types
they are allowed to be around a little longer
Note: update the cookie and add 8 minutes **/
chat.reset_chatroom_cookie = function()
{
  var date = new Date();
  var minutes = 6;
  var roomid = $.cookie('chatroomid');
  date.setTime(date.getTime() + (minutes * 60 * 1000));
  $.removeCookie("chatroomid");
  $.cookie('chatroomid',roomid,{expires: date});
};


chat.post_sent_message = function()
{
  if (chat.roomispublic) {
    chat.publicroom_reset_chatroom_cookie();
  } else {
    chat.reset_chatroom_cookie();
  }

};


chat.new_message_notification = function(data)
{
  document.getElementById('soundreceived').play();
};

chat.handle_typing_event = function(e)
{

  var data = $.parseJSON(e.data);
  if (chat.roomispublic) {
    chat.publicroom_handle_typing_event(data);
  } else {
    //console.debug("Event:typing");
    $('span.typing_wrapper img').remove();
    $.each(data.users,function(index,obj){
      if (obj.subtype === "1") {
          $('<img>').attr('src',chat.path+'../assets/typing.png').appendTo('span.typing_wrapper');
      }
    });
  }

};

chat.remove_typing_image = function(chatterid)
{
  $('span.typing_wrapper img').remove();
};

/**
 * doing something when admin is signed on
 * @param {[type]} user [description]
 */
chat.set_other_users_name = function(allusers)
{

  //console.debug("chat.set_other_users_name");
  //console.debug(allusers.users);

  $.each(allusers.users,function(index,data) {

    //Waiting for an operator to respond.
    if (chat.roomispublic) {

      //Nothing really necessary here
      //public rooms user list are updated another way

    } else {

      //let's not do anything if it is me
      if (data.chatterid === chat.user.chatterid)  return;
      if (data.user === null) {
        $('.operator-status').text("Waiting for an operator to respond.");
      } else {
        $('.operator-status').text("Talking to "+data.user);
      }
    }

  });

};
