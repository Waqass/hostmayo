var chat = chat || {};
chat.user = chat.user || {};

/**
 * sets defaults for entering new room
 */
chat.set_defaults_for_new_room = function()
{
  chat.timer_typing = null;
  chat.initialload = true;
  chat.lastmessageid = 0;
  chat.previoustime = 0;
  chat.canscroll = true;
  chat.users = null;
};

/**
 * what is needed to start sessions event service
 * @return {[type]} [description]
 */
chat.startMyEventService = function(usertype)
{

  chat.close_event_source();

  /*
  printout = chat;
  printout.hellobar = null;
  //console.debug(JSON.stringify(printout));
  */
  chat.data = new EventSource(chat.path+'send.php?serviceid='+chat.eventserviceid+'&roomid='+chat.roomid+'&fromid='+chat.lastmessageid+'&chatterid='+chat.user.chatterid+"&usertype="+usertype);

  chat.data.onopen = function(e) {
    //console.debug("eventsource onopen",e);
  };
  chat.data.onmessage = function(e) {
    //console.debug('eventsource onmessage',e);
  };

  chat.data.addEventListener(chat.roomid+'user',chat.handlerforuserevent,false);
  if (chat.roomispublic) {
    chat.data.addEventListener(chat.roomid+'roomstatus',chat.publicroom_handleroomstatusupdate,false);
  } else {
    chat.data.addEventListener(chat.roomid+'roomstatus',chat.handleroomstatusupdate,false);
  }
  chat.data.addEventListener(chat.roomid+'typing',chat.handle_typing_event,false);
  chat.data.addEventListener(chat.roomid+'message',chat.add_message,false);
  /*
  if (chat.groupid == 1) {
    chat.data.addEventListener(chat.roomid+'files',chat.add_file,false);
  }*/

  chat.data.onerror = chat.onerrorhandler;

};

chat.close_event_source = function()
{

  if ( chat.data != null) {
    chat.data.close();
    chat.data.readyState = 2;
    chat.data = null;
  }

};

chat.onerrorhandler = function(e)
{

  if (chat.roomispublic) {
    chat.publicroom_check_if_user_should_be_logged_off(e);
  } else {
    chat.check_if_user_should_be_logged_off(e);
  }
};

chat.handlerforuserevent = function(e)
{
  chat.set_other_users_name($.parseJSON(e.data));
};

chat.sendimages = function(data) {
  var msg = "";
  var new_msg_ctrl = null;
  var hash;

  //we only really have one file
  $.each(data.result.files, function (index, file) {
      msg += "[ceimg]"+file.name+"[/ceimg]";
      hash = file.name;
  });

  var enoch = Math.round(new Date().getTime() / 1000);

  //clear typing information
  window.clearTimeout(chat.timer_typing);
  chat.timer_typing = null;

  //our own messages should not be added if first one due to how sse returns first post
  if ($('#log dd').length > 0) {
    new_msg_ctrl = chat.addmessagedom({msg:msg,chatterid:chat.user.chatterid,time:enoch,gravatar:chat.user.gravatar,fullname:chat.user.fullname},true);
    chat.canscroll = true;
    chat.scrollintoview();
  }

  //let's add
  $.ajax({
        url: "index.php?fuse=admin&controller=plugin&action=doplugin&pluginaction=savemsg&plugin=livevisitor",
        type:'POST',
        data:{
            id: chat.roomid,
            chatterid: chat.user.chatterid,
            message: msg,
            title: top.document.title,
            groupid: chat.groupid,
            hash: hash,
            userid: (typeof(clientexec) != "undefined" && typeof(clientexec.admin_id) != "undefined") ? clientexec.admin_id : 0,
            user: chat.user.fullname,
            email: chat.user.email
        },
        success: function(response) {
          if (new_msg_ctrl !=null) new_msg_ctrl.attr('data-msgid',response);
        }
    });

};

chat.sendtypedmessage = function(ctrl)
{
    var new_msg_ctrl = null;
    var enoch = Math.round(new Date().getTime() / 1000);

    //clear typing information
    window.clearTimeout(chat.timer_typing);
    chat.timer_typing = null;

    //let's return if we didn't type anything
    if ($.trim(ctrl.val()) === "") return;

    msg = ctrl.val();
    if ( $('#log dd').length > 0) {
      new_msg_ctrl = chat.addmessagedom({msg:msg,chatterid:chat.user.chatterid,time:enoch,gravatar:chat.user.gravatar,fullname:chat.user.fullname}, true);
      chat.canscroll = true;
      chat.scrollintoview();
    }

    //let's add it
    $.ajax({
        url: "index.php?fuse=admin&controller=plugin&action=doplugin&pluginaction=savemsg&plugin=livevisitor",
        type:'POST',
        data:{
            id: chat.roomid,
            chatterid: chat.user.chatterid,
            message: ctrl.val(),
            title: top.document.title,
            groupid: chat.groupid,
            userid: (typeof(clientexec) != "undefined" && typeof(clientexec.admin_id) != "undefined") ? clientexec.admin_id : 0,
            user: chat.user.fullname,
            email: chat.user.email
        },
        success: function(response) {
          if (new_msg_ctrl !=null) new_msg_ctrl.attr('data-msgid',response);
        }
    });

    ctrl.val('');


};

chat.close_room = function(roomid)
{
  if (!roomid) roomid = chat.roomid;

  $.ajax({
        url: "index.php?fuse=admin&controller=plugin&action=doplugin&pluginaction=closeroom&plugin=livevisitor",
        type:'POST',
        data:{
            roomid: roomid
        },
        success:$.noop
    });
};


/**
 * determine if we need to add a message
 * @param {[type]} amessages [description]
 */
chat.add_message = function(e)
{
    var amessages = $.parseJSON(e.data);
    //console.debug(amessages);

    amessages = amessages['data'];
    if (amessages.length === 0) return;

    chat.lastmessageid = amessages[amessages.length-1].id;

    var msgaddednotme = false;

    $.each(amessages,function(index,message){

        if ( (message.chatterid != chat.user.chatterid) || (chat.initialload) ) {
          msgaddednotme = true;
          new_msg_ctrl = chat.addmessagedom(message);
          if (new_msg_ctrl !=null) new_msg_ctrl.attr('data-msgid',message.id);
        } else if (message.chatterid == chat.user.chatterid) {
          $('.msg-not-validated[data-msgid="'+message.id+'"]').removeClass('msg-not-validated')
        }

    });

    if (msgaddednotme && !chat.initialload) {

        chat.new_message_notification(amessages[0],chat.playsound);
        //let's remove typing image from this user
        //different from client app and admin app so let's just
        //call it instead of assuming which is the app being used
        chat.remove_typing_image(amessages[0].chatterid,amessages[0].email);
    }

    //if (chat.initialload) { chat.canscroll = true; }
    chat.scrollintoview(true);

    //let's do some post processing ... specific to client or admin
    if (typeof (chat.post_sent_message) === "function") {
      chat.post_sent_message();
    }

    chat.initialload = false;

};

/**
 * handle msg tags
 * @param  {[type]} msg [description]
 * @return {[type]}     [description]
 */
chat.handlemsgtags = function(msg)
{

    //s = s.replace(/\(.*?\)/g, 'world');
    if (msg.indexOf("[ceimg]") === -1) return msg;

    msg = msg.replace("[ceimg]","<ceimg>");
    msg = msg.replace("[/ceimg]","</ceimg>");
    path = $(msg).filter('ceimg').html();

    if ( chat.groupid === 0) {
      newpath = location.href.substring(0,location.href.lastIndexOf('/')+1);
      newpath = newpath+chat.path+"../../../../uploads/files/"+ chat.roomid + "/" +path;
      //newpath  += "../../../../uploads/files/r"+ chat.roomid + "/" +path;
      return "<a href='"+newpath+"' target='_blank'><img class='chat-log-image' src='"+newpath+"' style='padding-top:10px;padding-bottom:10px;max-width:265px;margin-left:-5px;' /></a>";
    } else {
      return "<a href='../uploads/files/"+chat.roomid+"/"+path+"' target='_blank'><img class='chat-log-image' src='../uploads/files/"+chat.roomid+"/"+path+"' /></a>";
    }

};

/**
 * Replace http:// type of text to anchors
 * @param  string text
 * @return string
 */
chat.replaceURLWithHTMLLinks = function(text) {

    text = text.replace(/<a href='http/g,"<a href='ht-ttp");
    text = text.replace(/(\b(https?|ftp|file):\/\/[\-A-Z0-9+&@#\/%?=~_|!:,.;]*[\-A-Z0-9+&@#\/%=~_|])/img, '<a href="$1" target="_blank">$1</a>');
    text = text.replace(/<a href='ht-ttp/g,"<a target='_blank' href='http");

    return text;

};

/**
 * adding dom message
 * @param  {[type]} message [description]
 * @return {[type]}         [description]
 */
chat.addmessagedom = function(message, sentbyme)
{

    if (message.msg === null) return null;
    if (typeof(sentbyme) =="undefined") sentbyme = false;

    var messageDate;
    var timediff;
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    message.msg  = message.msg.replace(/\\'/g, "'");
    message.msg  = message.msg.replace(/\\"/g, '"');
    nameclass = "";

    message.msg = ce.strip_tags(message.msg, '<a>');
    message.msg = chat.replaceURLWithHTMLLinks(message.msg);

    message.msg = chat.handlemsgtags(message.msg);
    message.msg = message.msg.replace(/\n/g, '<br/>');
    //console.debug(message.msg);

    //calculate time difference
    timediff = (message.time - chat.previoustime);
    chat.previoustime = message.time;


    if ((message.chatterid == $('#log dl:last').attr('class')) && (timediff < 900) ){ //can't be older than 15 minutes or will be treated as new msg (user) with stamp
     new_ctrl = $('<dd>' + message.msg + '</dd>').appendTo($('#log dl:last'));
    } else {
        if (message.chatterid !== chat.user.chatterid) {
            nameclass = "nameofotheruser";
        }

        if ( $('#log .scrolltome').length > 0)
        {
          ctrl = $('#skeleton').clone().insertBefore($('#log .scrolltome')).addClass(message.chatterid).removeAttr('id').find('dd').html("<strong class='nameofuser "+nameclass+"'>"+ce.htmlspecialchars(message.fullname)+"</strong><br/>"+message.msg).end().fadeIn();
          new_ctrl = $(ctrl.find('dd'));

        } else {

          ctrl = $('#skeleton').clone().appendTo($('#log')).addClass(message.chatterid).removeAttr('id').find('dd').html("<strong class='nameofuser "+nameclass+"'>"+ce.htmlspecialchars(message.fullname)+"</strong><br/>"+message.msg).end().fadeIn();
          new_ctrl = $(ctrl.find('dd'));

          messageDate = new Date(message.time*1000);
          min = (messageDate.getMinutes() < 10) ? "0"+messageDate.getMinutes() : messageDate.getMinutes();
          ctrl.find('dt .msgtime').html(months[messageDate.getMonth()]+" "+messageDate.getDate()+"<br/>"+messageDate.getHours()+":"+min);

        }

        $('dl:not(#skeleton):last img.usergravatar').attr('src', message.gravatar);

    }

    //this was sometimes not getting cleared so I'm removing it for now
    //if (sentbyme) new_ctrl.addClass('msg-not-validated')

    return new_ctrl;

};

/**
 * let's make sure that we properly register keypress to textarea
 * @return {[type]} [description]
 */
chat.register_keypress = function(ctrl)
{
  //TODO we might need to unbind
  ctrl.keypress(function (e) {

    if (e.which == 13) {

      //due to us capturing keyboard enter key bound to document
      if(chat.pressedenterfrom == "msgreply") {
        chat.pressedenterfrom = "";
      } else {
        chat.sendtypedmessage(ctrl);
      }
      e.preventDefault();

    } else {
      //is typing code
      if (chat.timer_typing === null) {
          //first time so let's start chat message
          chat.send_typing_info('start');
      } else {
          window.clearTimeout(chat.timer_typing);
      }
      chat.timer_typing = window.setTimeout( function() {
          chat.timer_typing = null;
          chat.send_typing_info('stop');
      }, 1000 );

    }
  });
};

chat.scrollintoview = function(setfocus)
{

  //if (!force) force = false;
  if (!setfocus) setfocus = false;

  if ($('.ce-executiontime').length > 0) {
    if (chat.canscroll) {
      $('.ce-executiontime').scrollintoview({
        complete: function(){ chat.initialload = false;}
      });
      if (setfocus) $('#msgreply').eq(0).focus();
    }
  } else {
    if (chat.canscroll) {
      $('#log .scrolltome').scrollintoview({
        complete: function(){ chat.initialload = false;}
      });
    }
  }
};

chat.send_typing_info = function(type)
{
    $.ajax({
        url: "index.php?fuse=admin&controller=plugin&action=doplugin&pluginaction=typing&plugin=livevisitor",
        type:'POST',
        data:{
            id: chat.roomid,
            subtype: type,
            chatterid: chat.user.chatterid,
            user: chat.user.fullname,
            email: chat.user.email
        },
        success:$.noop
    });
};

chat.send_login_info = function()
{

  $.ajax({
      url: "index.php?fuse=admin&controller=plugin&action=doplugin&pluginaction=loggedin&plugin=livevisitor",
      type:'POST',
      data:{
          chatterid: chat.user.chatterid,
          ispublic: (typeof(chat.roomispublic) == "undefined") ? 0 : chat.roomispublic,
          id: chat.roomid,
          userid: (typeof(clientexec) != "undefined" &&  typeof(clientexec.admin_id) != "undefined") ? clientexec.admin_id : 0,
          title: (top.document.title == "") ? "No Page Title" : top.document.title,
          user: chat.user.fullname,
          email: chat.user.email
      }
  });

};

chat.getuniqueid = function()
{
    var uniqueId = null;
    uniqueId = (new Date()).getTime();
    return (uniqueId++);
};

/*
 * jQuery scrollintoview() plugin and :scrollable selector filter
 *
 * Version 1.8 (14 Jul 2011)
 * Requires jQuery 1.4 or newer
 *
 * Copyright (c) 2011 Robert Koritnik
 * Licensed under the terms of the MIT license
 * http://www.opensource.org/licenses/mit-license.php
 */
(function(f){var c={vertical:{x:false,y:true},horizontal:{x:true,y:false},both:{x:true,y:true},x:{x:true,y:false},y:{x:false,y:true}};var b={duration:"fast",direction:"both"};var e=/^(?:html)$/i;var g=function(k,j){j=j||(document.defaultView&&document.defaultView.getComputedStyle?document.defaultView.getComputedStyle(k,null):k.currentStyle);var i=document.defaultView&&document.defaultView.getComputedStyle?true:false;var h={top:(parseFloat(i?j.borderTopWidth:f.css(k,"borderTopWidth"))||0),left:(parseFloat(i?j.borderLeftWidth:f.css(k,"borderLeftWidth"))||0),bottom:(parseFloat(i?j.borderBottomWidth:f.css(k,"borderBottomWidth"))||0),right:(parseFloat(i?j.borderRightWidth:f.css(k,"borderRightWidth"))||0)};return{top:h.top,left:h.left,bottom:h.bottom,right:h.right,vertical:h.top+h.bottom,horizontal:h.left+h.right}};var d=function(h){var j=f(window);var i=e.test(h[0].nodeName);return{border:i?{top:0,left:0,bottom:0,right:0}:g(h[0]),scroll:{top:(i?j:h).scrollTop(),left:(i?j:h).scrollLeft()},scrollbar:{right:i?0:h.innerWidth()-h[0].clientWidth,bottom:i?0:h.innerHeight()-h[0].clientHeight},rect:(function(){var k=h[0].getBoundingClientRect();return{top:i?0:k.top,left:i?0:k.left,bottom:i?h[0].clientHeight:k.bottom,right:i?h[0].clientWidth:k.right}})()}};f.fn.extend({scrollintoview:function(j){j=f.extend({},b,j);j.direction=c[typeof(j.direction)==="string"&&j.direction.toLowerCase()]||c.both;var n="";if(j.direction.x===true){n="horizontal"}if(j.direction.y===true){n=n?"both":"vertical"}var l=this.eq(0);var i=l.closest(":scrollable("+n+")");if(i.length>0){i=i.eq(0);var m={e:d(l),s:d(i)};var h={top:m.e.rect.top-(m.s.rect.top+m.s.border.top),bottom:m.s.rect.bottom-m.s.border.bottom-m.s.scrollbar.bottom-m.e.rect.bottom,left:m.e.rect.left-(m.s.rect.left+m.s.border.left),right:m.s.rect.right-m.s.border.right-m.s.scrollbar.right-m.e.rect.right};var k={};if(j.direction.y===true){if(h.top<0){k.scrollTop=m.s.scroll.top+h.top}else{if(h.top>0&&h.bottom<0){k.scrollTop=m.s.scroll.top+Math.min(h.top,-h.bottom)}}}if(j.direction.x===true){if(h.left<0){k.scrollLeft=m.s.scroll.left+h.left}else{if(h.left>0&&h.right<0){k.scrollLeft=m.s.scroll.left+Math.min(h.left,-h.right)}}}if(!f.isEmptyObject(k)){if(e.test(i[0].nodeName)){i=f("html,body")}i.animate(k,j.duration).eq(0).queue(function(o){f.isFunction(j.complete)&&j.complete.call(i[0]);o()})}else{f.isFunction(j.complete)&&j.complete.call(i[0])}}return this}});var a={auto:true,scroll:true,visible:false,hidden:false};f.extend(f.expr[":"],{scrollable:function(k,i,n,h){var m=c[typeof(n[3])==="string"&&n[3].toLowerCase()]||c.both;var l=(document.defaultView&&document.defaultView.getComputedStyle?document.defaultView.getComputedStyle(k,null):k.currentStyle);var o={x:a[l.overflowX.toLowerCase()]||false,y:a[l.overflowY.toLowerCase()]||false,isRoot:e.test(k.nodeName)};if(!o.x&&!o.y&&!o.isRoot){return false}var j={height:{scroll:k.scrollHeight,client:k.clientHeight},width:{scroll:k.scrollWidth,client:k.clientWidth},scrollableX:function(){return(o.x||o.isRoot)&&this.width.scroll>this.width.client},scrollableY:function(){return(o.y||o.isRoot)&&this.height.scroll>this.height.client}};return m.y&&j.scrollableY()||m.x&&j.scrollableX()}})})(jQuery);
