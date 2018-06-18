//create a namespace just to keep things clean
var livevisitor = {};

//to access CE's sidepanel plugin functions we need accessor to plugin infrastructure via
//the ceSidebarPlugin passing pluginname - same name given to dir name this plugin resides in
livevisitor.plugin = new ceSidebarPlugin({pluginname:"livevisitor"});
livevisitor.userCount = 0; //used to determine if we have populated the list initially
livevisitor.map = null;
livevisitor.markersArray = [];
livevisitor.newchats = [];


$(document).ready(function(){

    //if ($.cookie('achatid') == null) {
      //$.cookie('achatid',chat.getuniqueid());
      $.cookie('achatid',clientexec.admin_id);
    //}

    livevisitor.plugin.addHeartBeat({
        name: 'visitorpoll',
        delay:4,
        pulse:25,
        args: {
            chatterid:  $.cookie('achatid')
        },
        callback: function(response) {
            if (response) {
              livevisitor.process(response);
            }
        }
    });


    $(document).on("click",".rooms-dropdown-menu li a",function(){
      //chat.close_room
      //1366675027543
      //if it is close room let's remove

      var roomid = $(this).attr('data-roomid');
      var action = $(this).attr('data-action');

      livevisitor.plugin.callAction({
        name: 'actiononroom',
        args: {
            roomid: roomid,
            actiontoperform: action
        },
        callback: function(response) {
          if (action == "closeroom") {
            $('.listofrooms .news-content a[data-roomid="'+roomid+'"]').parent().parent().remove();
            if ($('.visitor[data-roomid="'+roomid+'"]').parent().filter('.visitor-active').length > 0) {
              chat.closeactiveroom();
            }
          }
        }
      });

    });

    $(document).on("click", ".visitorlink", function(){
        livevisitor.longitude = $(this).attr('data-long');
        livevisitor.latitude = $(this).attr('data-lat');
        chat.roomid = 0;
        chat.activeroomchatterid = 0;
        chat.track = null;
        livevisitor.loadmap();
    });

    $(document).on("click",'.roomlink',livevisitor.preloadchatwindow);
    $(document).on("click",'.recentlistchats-content .visitor',function(){
        var roomid = $(this).attr('data-roomid');
        $('.recentlistchats .visitor-active').removeClass('visitor-active');
        $('.recentlistchats .visitor[data-roomid="'+roomid+'"]').parent().addClass('visitor-active');
        livevisitor.plugin.setContent();
        RichHTML.mask();
        setTimeout(function(){
          window.location.href= "index.php?fuse=admin&controller=plugin&view=doplugin&pluginaction=showadminpanel&plugin=livevisitor&roomid="+roomid;
        },500)

    });

    //let's write out the sounds we will use
    livevisitor.writeaudio();
});


livevisitor.writeaudio = function()
{

  audio = '<audio preload="auto" id="chatrequest">';
  audio += '<source class="ogg_src" src="../plugins/dashboard/livevisitor/assets/bell.ogg" type=\'audio/ogg; codecs="vorbis"\' />';
  audio += '<source class="mp3_src" src="../plugins/dashboard/livevisitor/assets/bell.mp3" type=\'audio/mpeg; codecs="mp3"\' />';
  audio += '</audio>';
  audio += '<audio preload="auto" id="soundreceived">';
  audio += '<source class="ogg_src" src="../plugins/dashboard/livevisitor/assets/message_received.ogg" type=\'audio/ogg; codecs="vorbis"\' />';
  audio += '<source class="mp3_src" src="../plugins/dashboard/livevisitor/assets/message_received.mp3" type=\'audio/mpeg; codecs="mp3"\' />';
  audio += '</audio>';
  audio += '<audio preload="auto" id="chatvisitor">';
  audio += '<source class="ogg_src" src="../plugins/dashboard/livevisitor/assets/visitor.ogg" type=\'audio/ogg; codecs="vorbis"\' />';
  audio += '</audio>';

  $('.main').append(audio);

};

/**
 * Main method for processing heartbeat call of rooms and visitors
 * @param  {[type]} e [description]
 * @return {[type]}   [description]
 */
livevisitor.process = function(e)
{
    var self = livevisitor;
    $.get('../plugins/dashboard/livevisitor/plugin.mustache',
        function(template) {
            var items;
            var track = {};
            var visitors = [];
            var chats = [];

            //lets set a default in the event admin is not viewing a room
            if (typeof(chat.roomid) == "undefined") {
                chat.roomid = 0;
                chat.activeroomchatterid = 0;
                chat.track = null;
            }

            //we should remove all items that are also in chats
            $.each(e.visitors,function(index,obj){

              //console.debug(1);
              //console.debug('.recentlistchats-content .visitor[data-ip="'+obj.ip+'"][data-roomid="'+chat.roomid+'"]');
              if ($('.recentlistchats-content .visitor[data-ip="'+obj.ip+'"][data-roomid="'+chat.roomid+'"]').length > 0 )
              {

                //this is info for the open frame
                track.title = obj.data.current_session.title;
                track.url = obj.data.current_session.url;
                if ( (typeof(chat.track) === "undefined") || chat.track === null || chat.track.url !== track.url) {
                    chat.add_footprint_dom(track);
                }
                chat.track = track;
              }

              //check to see if we have a chat with this user
              if ($('.recentlistchats-content .visitor[data-ip="'+obj.ip+'"]').length === 0) {
                //console.debug(obj);
                visitors.push(obj);
              }
            });

            $.each(e.chats,function(index,obj){
              //check to see if we have a chat with this user if so let's not show him as a visitor
              if (obj.roomid == chat.roomid) obj.isactive = "true";
              chats.push(obj);

              //let's check to see if it is a chat request
              //where no admin's are viewing
              if (obj.users.length === 0) {
                livevisitor.new_chat_request();
              }

            });
            //items = {chats:chats};

            //console.debug(e);
            //console.debug(visitors);
            items = {visitors:visitors,chats:chats};

            //set cache for this plugin for plugin manager
            var thtml = Mustache.render(template, items);
            //used when debugging so that panel doesn't update all the time when styling
            //if ($('.visitorlistplugin:visible').length === 0) self.plugin.setContent(thtml);
            self.plugin.setContent(thtml);

            if ($('#map-canvas').length > 0) livevisitor.addMarkers();
        }
    );

    //let's see what we do with counts
    if (e.waitingchats > 0) {
      livevisitor.plugin.setCount(e.waitingchats,'badge-important');
    } else if (e.totalunreadchats > 0) {
      livevisitor.plugin.setCount(e.totalunreadchats,'badge-warning');
    } else if (e.visitors) {
      livevisitor.plugin.setCount(e.visitors.length,'badge-info');
    }


};

/**
 * Initialize Map
 * @return {[type]} [description]
 */
livevisitor.initializeMap = function() {
  var mapOptions = {
    center: new google.maps.LatLng(livevisitor.latitude,livevisitor.longitude),
    zoom: 2,
    disableDefaultUI: true,
    mapTypeId: google.maps.MapTypeId.ROADMAP
  };
  livevisitor.map = new google.maps.Map(document.getElementById("map-canvas"),
      mapOptions);

  livevisitor.addMarkers();
};

/**
 * Add marker to new visitor
 * @return {[type]} [description]
 */
livevisitor.addMarkers = function()
{


  //let's remove all markers
  if (livevisitor.markersArray.length > 0) {
    for (var i = 0; i < livevisitor.markersArray.length; i++) {
      livevisitor.markersArray[i].setMap(null);
    }
    livevisitor.markersArray.length = 0;
  }

  $(".visitorlink").each(function(){
      var latitude = $(this).attr('data-lat'),
          longitude = $(this).attr('data-long');

      // Creating a marker and positioning it on the map
      var marker = new google.maps.Marker({
        position: new google.maps.LatLng(latitude,longitude),
        map: livevisitor.map
      });
      livevisitor.markersArray.push(marker);
  });
};


/**
 * map loader handler
 * @return {[type]} [description]
 */
livevisitor.loadmap = function()
{
    if ($('#map-canvas').length > 0) {
        livevisitor.initializeMap();
        return;
    }

    $('.ce-container .content').html('<div id="map-canvas" style="height:600px;" />');
    var script = document.createElement("script");
    script.type = "text/javascript";
    script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyBrlNR0PNWEZUOuAg4Y2Xgvb0rMYXZ2NC8&sensor=false&callback=livevisitor.initializeMap";
    document.body.appendChild(script);

};

/**
 * Get chat window ready
 * @param  {[type]} e [description]
 * @return {[type]}   [description]
 */
livevisitor.preloadchatwindow = function(e)
{

  $('.recentlistchats-content .no-data').remove();

  var roomid = $(this).attr('data-roomid');
  var roomname = $(this).parent().find('.news-title').text();

  //Let's add a new entry log immediately
  $('.recentlistchats .visitor-active').removeClass('visitor-active');

  if ($('.recentlistchats .visitor[data-roomid="'+roomid+'"]').length === 0) {

    var html = '<div class="accordion-heading item visitor-active">' +
       '<div class="visitor" data-roomid="'+roomid+'" data-ip="0">' +
         '<div style="float:left;">' +
           '<span class="visitor_ip">'+roomname+'</span>' +
         '</div>' +
         '<div style="float:right;"><span class="visitor_timeago"></span></div>' +
         '<div class="visitor_viewing" style="clear:both;">Internal</div>' +
        '</div>' +
      '</div>';
    $('.recentlistchats-content').append(html);

  }

  $('.recentlistchats .visitor[data-roomid="'+e.roomid+'"]').parent().addClass('visitor-active');


  livevisitor.listofrooms.hide();
  $('.recentlistchats-content .visitor[data-roomid="'+roomid+'"]').trigger('click');

};

/**
 * Handler for chat room click
 * @param  {[type]} e [description]
 * @return {[type]}   [description]
 */
livevisitor.loadchatwindow = function(roomid)
{

  //ui set active tab
  if (typeof(roomid) == "undefined"){
    $('.recentlistchats .visitor-active').removeClass('visitor-active');
    //TODO set active based on roomid
    //$(this).parent().addClass('visitor-active');
    //let's remove any counts we have
  }

  //call action to update room list
  livevisitor.forcelistupdate();
  $('.hello-users [data-chatterid]').remove();
};

/**
 * Sound to play when customer is requesting chat
 * @param  boolean playsound
 * @return void
 */
livevisitor.new_chat_request = function() {
    document.getElementById('chatrequest').play();
};

/**
 * Forcing a room list update so that we don't have to wait for heartbeat
 * @return {[type]} [description]
 */
livevisitor.forcelistupdate = function()
{
  //call action to update room list
  livevisitor.plugin.callAction({
    name: 'visitorpoll',
    args: {
        chatterid:  $.cookie('achatid')
    },
    callback: function(response) {
        if (response) {
          livevisitor.process(response);
        }
    }
  });
};