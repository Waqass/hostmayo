// JavaScript Document
var globalSearch = {};


// Create a closure so that we can define intermediary
// method pointers that don't collide with other items
// in the global name space.
(function(){
    // Store a reference to the original remove method.
    var originalLoadMethod = jQuery.fn.load;

    // Define overriding method.
    jQuery.fn.load = function(){
        //// Execute the original method.
        originalLoadMethod.apply( this, arguments );
        clientexec.postpageload();
    };
})();

if(typeof(RichHTML)!= "undefined"){
    $(RichHTML).bind({
        "preload": function (event) {
            ce.msg('Loading',true);
        },
        "postload": function (event) {
            $("#msg-div").hide();
        }
    });
}

//adding reallyvisible
jQuery.extend(
    jQuery.expr[ ":" ],
    { reallyvisible : function (a) {
        return !(jQuery(a).is(":hidden") || jQuery(a).parents(":hidden").length || jQuery(a).css('display')=='none');
    }}
  );

//this method is used in views that are possibly loaded when invoice view is loaded.
//we need to check that new elements are bound to clickanchorsafterchanges to ensure
//we can't navigate from view when changes are made
clientexec.bindLinksIfLeavingInvoiceViewIsPrevented = function()
{
    //lets rebind this click event for invoicelist.. it is needed to prevent redirecting from view when there are changes made
    if (typeof (invoiceview) !== "undefined" && typeof(invoiceview.changesmade) !== "undefined" && invoiceview.changesmade) {
        $('a:not(.btn):not(.rich-button),div.accordion-group').unbind('click',invoiceview.clickanchorsafterchanges);
        $('a:not(.btn):not(.rich-button),div.accordion-group').bind('click',invoiceview.clickanchorsafterchanges);
    }

};

/**
 * Send message to CE to raise an event via JS
 * @return {[type]} [description]
 */
clientexec.raiseevent = function(eventname,args)
{

    $.ajax({
        url: "index.php?fuse=admin&controller=index&action=raiseevent",
        type: 'POST',
        data: {
            eventname: eventname,
            args: args
        },
        success: function() {
        }
    });
};



clientexec.getfullcontact = function(forcepull)
{

    var tpl = "<div class='contact-card'> " +
        "<div class='box'> " +
            "<div class='column'> " +
                "<ul class='identity'> " +
                    "{{#contactInfo}}<li class='identity-fullname'>{{fullName}}</li> " +
                    "{{#has_org}}<li class='identity-org'>{{org}} ({{title}})</li>{{/has_org}}{{/contactInfo}}" +
                    "{{#has_organization}}<li class='org-title'>Past Organizations:</li>{{/has_organization}} " +
                    "{{#organizations}}<li class='org'>{{#title}}{{title}},{{/title}}{{name}} </li>{{/organizations}} " +
                "</ul> " +
                "{{#demographics}}<ul class='demographics'> " +
                    "<li class='demographics-location'><i class='icon-map-marker' style='color:#361B1B;padding-left: 4px;'></i>&nbsp;&nbsp;{{location}}</li> " +
                "</ul>{{/demographics}} " +
                "{{#has_sites}}<ul class='websites'> " +
                    "<li class='website'><i class='icon-globe' style='color:#557DFA;'></i>&nbsp;&nbsp;Websites ({{website_count}}) <i onclick=\"$('.websites .website').show();\" class='icon-chevron-down' style='color:rgb(226, 131, 43); cursor:pointer;'></i></li>" +
                    "{{#sites}}<li class='website website-small'><a href='{{url}}' target='_blank'>{{url}}</a></li>{{/sites}} " +
                "</ul>{{/has_sites}} " +
                "{{#has_interests}}<ul class='interests'> " +
                    "<li class='interest'><i class='icon-heart' style='color:#FF6C6C;'></i>&nbsp;&nbsp;Interests ({{interests_count}}) <i onclick=\"$('.interests .interest').show();\" class='icon-chevron-down' style='color:rgb(226, 131, 43); cursor:pointer;'></i></li>" +
                    "<li class='interest interest-small'>{{#interests}}{{tag}}, {{/interests}}</li> " +
                "</ul>{{/has_interests}} " +
                "{{#has_social}}<ul class='social'> " +
                    "{{#socials}}<li class='social-profile'> " +
                        "<a target='_blank' href='{{url}}'> " +
                            "<img src='https://api.fullcontact.com/images/socialProfileIcons/{{type}}.png'> " +
                        "</a> " +
                    "</li>{{/socials}} " +
                "</ul>{{/has_social}} " +
            "</div> " +
        "</div> " +
    "</div>";

    $('.full-contact-profile').html("<center style='margin-top:30px;margin-bottom:30px;'><img src='../images/loader.gif'></center>");
    $.getJSON('index.php?fuse=clients&controller=userprofile&forcepull='+forcepull+'&action=getfullcontact',function(response){

        $('.fullcontact-description').hide();
        $('.update-full-contact-btn').hide();
        $('.full-contact-btn').hide();

        if (!response.profile || response.profile.length == 0) {
            $('.full-contact-profile').html("<div class='api-error'><h1 style='text-align:center;'>Contact has no social data</h1><p>We have been unable to retrieve any social data for this contact.  Please try again after 90 days.</p></div>");
            $('.full-contact-profile').show();
            $('.fullcontact-description').show();
            $('.full-contact-btn').show();
            return;
        }
        var profile = response.profile[0];

        if (profile.status == "202") {
            $('.full-contact-profile').html("<div class='api-error'><h1 style='text-align:center;'>Social data being retrieved ...</h1><p>Your request is currently being processed. Please allow up to 10 minutes as we compile social data for this contact.</p></div>");
            $('.full-contact-btn').show();
            $('.fullcontact-description').show();
        } else if (profile.status == "404") {
            $('.full-contact-profile').html("<div class='api-error'><h1 style='text-align:center;'>Contact has no social data</h1><p>We have been unable to retrieve any social data for this contact.  Please try again after 90 days.</p></div>");
            $('.full-contact-btn').show();
            $('.fullcontact-description').show();
        } else if (profile.status != "200") {
            $('.full-contact-profile').html("<div class='api-error'><h1 style='text-align:center;'>Error collecting social data</h1><p>"+profile.message+"</p></div>");
            $('.full-contact-btn').show();
            $('.fullcontact-description').show();
        } else {
            items = clientexec.build_full_contact_json(profile);
            var output = Mustache.render(tpl, items);
            $('.full-contact-profile').html(output);
            $('.fullcontact-description').show();
            $('.update-full-contact-btn').show();
        }
        $('.full-contact-profile').show();

    });

}

clientexec.getfullcontact_active = function()
{

    if ($('.full-contact-active .social-profile').length > 0) return;
    var tpl = "<div class='contact-card'> " +
        "<div class='box'> " +
            "<div class='column'> " +
                "{{#has_social}}<ul class='social'> " +
                    "{{#socials}}<li class='social-profile'> " +
                        "<a target='_blank' href='{{url}}'> " +
                            "<img src='https://api.fullcontact.com/images/socialProfileIcons/{{type}}.png'> " +
                        "</a> " +
                    "</li>{{/socials}} " +
                "</ul>{{/has_social}} " +
            "</div> " +
        "</div> " +
    "</div>";

    $.getJSON('index.php?fuse=clients&controller=userprofile&forcepull=0&action=getfullcontact',function(response){
        if ( response.profile === null ) return;
        if (!response.profile[0]) return;
        var profile = response.profile[0];
        if (profile.status == "200") {
            items = clientexec.build_full_contact_json(profile);
            var output = Mustache.render(tpl, items);
            $('.full-contact-active').html(output);
            $('.full-contact-active').show();
        } else {
            $('.full-contact-active').hide();
        }

    });

}

clientexec.build_full_contact_json = function(profile) {

    //lets show full contact info

    items = {
        contactInfo:profile.contactInfo,
        organizations:[],
        has_social : false,
        has_org: false,
        socials:[],
        has_sites: false,
        sites:[],
        website_count:0,
        interests_count:0,
        has_interests:false,
        interests:[],
    };

    if (profile.contactInfo && profile.contactInfo.websites) {
        $.each(profile.contactInfo.websites,function(i,o) {
            items.has_sites = true;
            items.sites.push({url:o.url});
            items.website_count++;
        });
    }

    if (profile.digitalFootprint && profile.digitalFootprint.topics) {
        $.each(profile.digitalFootprint.topics,function(i,o) {
            items.has_interests = true;
            items.interests.push({tag:o.value});
            items.interests_count++;
        });
    }

    if (profile.organizations) {
        $.each(profile.organizations,function(i,o){
            if (o.isPrimary && o.current) {
                items.contactInfo.title = o.title;
                items.contactInfo.org = o.name;
                items.has_org = true;
            } else {
                items.organizations.push({name:o.name,title:o.title});
                items.has_organization = true;
            }
        });
    }

    if (profile.demographics && profile.demographics.locationGeneral) {
        items.demographics = {};
        items.demographics.location = profile.demographics.locationGeneral;
    }

    $.each(profile['socialProfiles'], function(i,o){
        items.has_social = true;
        items.socials.push({name:o.typeName, url:o.url, type:o.type});
    });

    return items;
}
clientexec.events_while_away = [];
clientexec.addNewEvent = function(text,header,avataremail,date){

        clientexec.raise_badge_count();

        if (!header || trim(header)=="") {
            header = "Notice";
        } else {
            header = header;
        }

        if (avataremail && avataremail!="") {
            avataremail = ce.getAvatarUrl(avataremail,80,header);
        } else {
            avataremail = "../templates/admin/images/logo_in1.png";
        }

        event = {"text":text,"header":header,"url":avataremail, "time" : date};
        event.calctime = function () {

            //Might need to use else where so take note
            //If we are comparing against client (js) now for elapsed time with server timestamp
            //first get the offset of time between server and client so you can add it to the diff
            // var tzDifference = clientexec.server_offset * 60 + new Date().getTimezoneOffset();

            var time1 = Date.now();
            var time2 = new Date(this.time*1000);
            return Math.ceil( (((time1 - time2) / 1000)/60)) + "m";
        };

        clientexec.events_while_away.push(event);
        //let's only show if growl is closed
        if ($('.m-growl:visible').length == 0) {
            $('.m-growlicon').show();
        }

};

clientexec.hide_growl_events = function()
{

    $('.m-growl').hide();

    if (clientexec.events_while_away.length > 0) {
        $('.m-growlicon').show();
    } else {
        $('.m-growlicon').hide();
    }

}

clientexec.show_growl_events = function()
{
    $('.m-growlicon').hide();
    $('.m-growl').show();

    var tpl = '{{#arr}}<div class="m-entry" data-path="{{url}}"><span class="m-facelink"><img class="m-face" src="{{url}}"></span><div class="m-data"><div class="m-meta"><strong class="m-name">{{header}}</strong><span class="m-time">{{calctime}}</span></div><span class="m-link">{{{text}}}</span></div></div>{{/arr}}';
    var output = Mustache.render(tpl, {arr:clientexec.events_while_away});

    $('.m-entries').html(output);
    clientexec.events_while_away = [];
}

clientexec.popupKBArticle = function(id,title)
{
    if (typeof(title) === "undefined") title = "Knowledgebase Article";
    else {
        if (title.length > 45) title = title.substr(0,43)+"…";
    }
    clientexec.kbarticle = new RichHTML.window({
        height: '400',
        width: '550',
        url: 'index.php?fuse=knowledgebase&view=articlepopup&articleId='+id,
        title: title
    });
    clientexec.kbarticle.show();
};

/* pass parent filter you want to use if calling directly
 * Note: This is used if you want to call this method on dynamically
 * loaded content. Such as richhtml.window content or possibly expander on richhtml.grid */
clientexec.postpageload = function(parent)
{
    if (!parent) parent = 'body';

    // Initialize Select2 on all <select> elements
    $(parent+' select:not([data-format], .disableSelect2AutoLoad)').select2({
        minimumResultsForSearch: 10,
        width:'resolve',

        // It's the responsability of the view, not the select2 plugin, to escape stuff
        // because sometimes we'd like to have HTML in the options, sometimes not.
        // Select2 does some bad escaping anyway, so these 3 directives fix it.

        /*&formatResult: function(o) {
            return $(o.element[0]).html();
        },
        escapeMarkup: function(m) {return m;}*/

        // commenting this one out for the moment cuz it's removing the X to remove items. Will read again when I remember what this was for...
        /*formatSelection: function (o, c) {
            c.text(o.text);
        }*/
    });
    $(parent+' select[data-format]:not(.disableSelect2AutoLoad)').select2({
        minimumResultsForSearch: 10,
        width:'resolve',
        formatResult: function (o){
            var fnc = $(o.element[0]).closest('select').data('format');
            return window[fnc](o);
        },
        formatSelection: function (o){ return o.text; }
    });
    $.each($(".select2-container"), function (i, n) {
        $(n).find('select').fadeTo(0, 0).width("0").height("0").css("left", "-10000000px").css("position","absolute"); // make the original select visible for validation engine and hidden for us
        //$(n).prepend($(n).next());
    });
    //end of select2

    //lets prevent disabled btn-groups from showing dropdown
    $(parent+" span.btn-group a.btn.dropdown-toggle").click(function(event) {
        if ($(this).attr('disabled') =='disabled') {
            event.preventDefault();
            return false;
        } else {
            return true;
        }
    });

    $(parent+' input.datepicker:not(.disableDatePickerAutoLoad)').datepicker({
        format: clientexec.dateFormat == 'm/d/Y'? 'mm/dd/yyyy' : 'dd/mm/yyyy',
        autoclose: true
    });
    $(parent+' input.timepicker:not(.disableTimePickerAutoLoad)').timepicker();

    /* convert any wysi fields */
    //'fullscreen:startFullscreen',
    $(parent+' textarea.wysihtml5:not([data-withembed])').redactor({parentSel: '#product-tab-content',autoresize: false,plugins: ['clips', 'rtl'], execCommandCallback: true,
        buttons: ['html', '|', 'formatting', '|', 'bold', 'italic', 'underline', '|', 'unorderedlist', 'orderedlist', '|', 'table', 'link', '|','fontcolor', 'backcolor', '|', 'alignment']});

    $(parent+' textarea.wysihtml5[data-withembed]').redactor({parentSel: '#product-tab-content',
		imageUpload: 'index.php?action=uploadimage&controller=articles&fuse=knowledgebase&sessionHash='+gHash,
        autoresize: false,plugins: ['clips', 'rtl'],execCommandCallback: true,
        buttons: ['html', '|', 'formatting', '|', 'bold', 'italic', 'underline', '|', 'unorderedlist', 'orderedlist', '|','image', 'video', 'file', 'table', 'link', '|','fontcolor', 'backcolor', '|', 'alignment']});

    if ( typeof(gHash) != "undefined" ) {
        $('<input>').attr({
            type: 'hidden',
            id: 'sessionHash',
            name: 'sessionHash',
            value: gHash
        }).appendTo('form');
    } else {
        console.log("hash not defined");
    }

    $(parent+" .change-grid-showrecords").select2("val", clientexec.records_per_view);

    //bind grid dropdown
    $(parent+' .change-grid-showrecords').unbind('change',clientexec.changegridshowrecords);
    $(parent+' .change-grid-showrecords').change(clientexec.changegridshowrecords);


    $('.tooltip').remove();
    // New method of tooltips for any element data-toggle="tooltip" title="%message%" data-tooltip-placement-"%left|top|right|bottom%" (optional)
    $(parent).tooltip({
        html:    true,
        selector: '[data-toggle=tooltip]'
    });

    // New method of tooltips for any element data-toggle="tooltip" title="%message%" data-tooltip-placement-"%left|top|right|bottom%" (optional)
    $(parent).popover({
        trigger: 'hover',
        selector: '[data-toggle=popover-hover]'
    });

    // disable the input on the select2 search when on mobile/tablet, to fix the keyboard from popping up on mobile.
    if (ce.isMobileOrTablet()) {
        $(".select2-search input").prop("readonly", true);
    }
};

clientexec.changegridshowrecords = function(){
    var self = $(this);
    $.ajax({
        url: "index.php?fuse=clients&controller=customfields&action=save",
        type: 'POST',
        data: {
            newvalue: self.val(),
            customfieldname: 'Records per page',
            staffid: clientexec.admin_id
        },
        success: function() {
            clientexec.records_per_view = self.val();
        }
    });
};

function staff_member_not_active()
{
    clientexec.focused = false;
    //TODO we should change status of user as away
}
var timeoutTime = 400*1000;
var timeoutTimer = setTimeout(staff_member_not_active, timeoutTime);

clientexec.load_active_user_panel = function(load_data)
{

    if (typeof(load_data) == "undefined") load_data = true;

    $('.active-customer-panel').show();
    $('.active-customer-panel').animate( {right: "0" }, 300, function() {$('.active-customer-panel').addClass("opened_panel");} );

    //let's only get the active customer if we don't have any html
    if ( (load_data) && ($.trim($('.active-customer-panel').text()) == "")) {
        $('.active-customer-panel').load("index.php?fuse=admin&view=activecustomer&nolog=1");
    }
}

clientexec.hide_active_user_panel = function()
{
    if($('.active-customer-panel').hasClass("opened_panel")){
        $('.active-customer-panel').animate({right: "-304px" }, 300, function(){ $('.active-customer-panel').removeClass("opened_panel").hide();});
    }
}

clientexec.pin_active_user_panel = function()
{
    var session_vars;
    if ($('body.with-active-cutomer-panel').length == 0) {

        $('body').addClass('with-active-cutomer-panel');
        $('.active-customer-panel').unbind('mouseenter mouseleave');
        session_vars = {
            showactivepanelstatus: 1
        };

        //pinning let's hard code right //not sure why css isn't getting it when we added class to body
        if ($('.mac-os').length == 0) {
            $('.active-customer-panel').css("right","16px");
        }

    } else {
        $('body').removeClass('with-active-cutomer-panel');
        $('.active-customer-panel').hover(function(){$('.active-customer-panel').show();},clientexec.hide_active_user_panel);
        session_vars = {
            showactivepanelstatus: 0
        };

        //unpin and make sure we move to right
        $('.active-customer-panel').css("right","0");

    }

    $.ajax({
        url: 'index.php?fuse=clients&action=updatesessionvar',
        type: 'POST',
        data: { fields : session_vars }
    });
}

$('body').on('click','.pin-active-profile',clientexec.pin_active_user_panel);

//binding to filter
$('body').on('click','.ticket-filter-link',function(e) {

    if ( (typeof(ticketList) != "undefined") && (typeof(ticketList.grid) != "undefined")  && (!ticketList.viewing_from_customer_profile)) {
        e.preventDefault();
        $('.ce-top-menu li .drop').addClass('hide-drop');
        setTimeout(function(){$('.ce-top-menu li .drop').removeClass('hide-drop')},500);
        //why am I passing time (I want to make sure we replay state otherwise if same filter it will not fire)
        History.replaceState({
            time:(new Date()).getTime(),
            search_filter:$(this).attr('data-filter-id'),
            search_filter_name:$(this).attr('data-filter-name'),
            search_customerid: 0
        },"", $(this).attr('href'));

    } else {
        return true;
    }

});

$(document).ready(function() {
    clientexec.badge = 0;
    clientexec.favicon = new Favico({
        animation : 'popFade'
    });

    RichHTML.prefixLabel = gVer;

    if ($('body.with-active-cutomer-panel').length == 0) $('.active-customer-panel').hover(function(){$('.active-customer-panel').show();},clientexec.hide_active_user_panel);
    $('#header .active-customer').hover(clientexec.load_active_user_panel,function(){ return true; });

    //let's load active-customer after some time
    if (clientexec.customerId > 0) {
        var timeout = 5000;
        //let's see if we need to load fast since we don't have any cache
        if ($('.active-customer-panel').text() == "") {
            timeout = 0;
            $('.active-customer-panel').html("<div class='loading-active-customer'>"+lang("Loading")+" ...</div>");
        }
        setTimeout(function(){
            $('.active-customer-panel').load("index.php?fuse=admin&view=activecustomer&nolog=1");
        }, timeout);
    }

    //Add some code to set unfocused (and timed out)
    //when we are clicking away from CE and when we are inactive for a while
    $(window).blur(function() {
        clientexec.focused = false;
        //No need to change status of user
    });

    $('body').bind('mousemove keydown', function(event) {
        clientexec.focused = true;
        //time to reset the title bar

        clientexec.badge = 0;
        clientexec.favicon.badge(0);

        clearTimeout(timeoutTimer);
        timeoutTimer = setTimeout(staff_member_not_active, timeoutTime);
    });

    // set it so that dom elements are initialized first thing before page specific elements.
    clientexec.postpageload();
    clientexec.pluginMgr = new PluginMgr({items:7, start:0});
    clientexec.pluginMgr.startAutoPlugins();

    /**
     * Update the selected customer plugin
     * @return  void
     */
    clientexec.getSelectedProfileAndAvailableActions = function()
    {

        $('.active-customer-panel').html("<div class='loading-active-customer'>"+lang("Loading")+" ...</div>");
        $('.active-customer-panel').load("index.php?fuse=admin&view=activecustomer&nolog=1");

    };

    $('button.closepluginpanel').bind('click',function(e){
        $('div.pluginframe').hide();
        $('div.pluginframe-header').hide();
        $('#selected-leftmenu-icon-tick').remove();
        $('.withpluginpanel').removeClass('withpluginpanel');
        $('.leftmenu-icon-active').removeClass('leftmenu-icon-active');
        clientexec.pluginMgr.active_panel = "";

        var session_vars = {
            pluginpanelPosition: "none",
            leftbarplugins: $('.lefmenu-plugin-icons').html()
        };
        $.ajax({
            url: 'index.php?fuse=clients&action=updatesessionvar',
            type: 'POST',
            data: { fields : session_vars }
        });

        $(window).trigger('resize');
        if (clientexec.myChart) clientexec.myChart._resize();

    });

    /**
     * Toggles the hidden right panel in the event you want to
     * add some view specific options
     * @return void
     */
    clientexec.togglerightpanel = function()
    {
        $('.rightframe').toggle();
        $('.maincontainer').toggleClass('withoutrightpanel');
        $(window).trigger('resize');
        if (clientexec.myChart) clientexec.myChart._resize();
    };

    //if we are viewing the activecustomers lets call to update
    //selected customer in the event we changed it.
    if (clientexec.customerChanged) {
        clientexec.getSelectedProfileAndAvailableActions();
    }


    //Prepare History Obj
    var History = window.History; // Note: We are using a capital H instead of a lower h

    globalSearch.formatResult = function (result) {
        var markup = '<div class="search-item"';
        if (result.tooltip) {
            markup += ' data-toggle="tooltip" title="'+result.tooltip+'" data-placement="right"';
        }
        markup += '><div class="type">'+result.type+'</div><div class="name bold">'+result.name+'</div>';
        if (result.extra) {
            markup += result.extra;
        }
        markup += '</div>';
        return markup;
    };
    globalSearch.formatSelection = function (selection) {
        return selection.url;
    };

    $('.btn-advanced-search').bind("click",function(){
        clientexec.advancedsearch = {};
        clientexec.advancedsearchwin = new RichHTML.window({
            url          : 'index.php?fuse=clients&controller=users&view=advancedsearch',
            title       : lang('Advanced Search'),
            width       : '300',
            height      : '200',
            left       : $('.btn-advanced-search').offset().left - 280,
            top         : '40',
            buttons     : {  button1:{text:"search","onclick":function(){ clientexec.advancedsearch.submitCustomUserSearch(); }}, button2:{text:"cancel",type:"cancel"} }
        });
        clientexec.advancedsearchwin.show();
    });

    $("#searchquerytextfield").select2({
        placeholder: lang("Quick Search") + " ...",
        minimumInputLength: 3,
        width: '198px',
        multiple: true, // workaround to keep input within the text box
        openOnEnter: false,
        quietMillis: 500, // delay in milliseconds from stopped input before query is sent
        formatResult: globalSearch.formatResult,
        formatSelection: globalSearch.formatSelection,
        dropdownCssClass: 'globalsearch-dropdown-active',
        maximumSelectionSize: 1,
        allowClear: false,
        id: function (choice) {
            return choice.url;
        },
        ajax: {
            url: "index.php?fuse=admin&action=getsearchresults",
            dataType: 'json',
            quietMillis: 500,
            data: function (term, page) {
                return {
                    query: term,
                    limit: 10, // get 10 results at a time
                    page: page
                };
            },
            results: function (data, page) {
                if (data.total) { globalSearch.totalResults = data.total; }
                var more = (page * 10) < globalSearch.totalResults;
                return {results: data.matches, more: more};
            }
        }
    }).change(function() {
        if ((typeof(invoiceview) !== 'undefined' ) && ( invoiceview.changesmade)) {
            invoiceview.clickanchorsafterchanges(e);
            return false;
        } else {
            window.location = $(this).val();
        }
    });

    //binding updating if available for chat
    //should only check this if livevisitor plugin is active
    if ($.inArray("livevisitor", clientexec.sidebarplugins.names) > -1) {
        $('.profile-dropdown-switches').show();
        $('.profile-bottom-divider').show();
        $('.switch-chatstatus').on('switch-change', function (e, data) {
            var val = (data.value) ? 1 : 0;
            clientexec.updateCustomField('ChatStatus',val);
        });
    }

    clientexec.eventwindow = new RichHTML.window({
        height: '200',
        width: '550',
        hideTitle: true
    });

    //from plugin manager
    clientexec.initialize_sidepanel_plugins();

    heartbeat.add({
        name: 'index.php?fuse=admin&action=getvitals',
        delay: 1,
        pulse: 15,
        callback: function(response){
            ce.checkRedirectLogin(response);
            if (response.needyourattention.length > 0) {
                clientexec.updatevitals(response.needyourattention);
            }

            if (typeof(response.onlineusers) != "undefined") {
                clientexec.updateonlineusers(response.onlineusers);
                // $(document).trigger('onlineusers-updated');
            }

            if (response.newEvents.length > 0) {
                clientexec.updateEvents(response.newEvents);
            }

            if (typeof(response.ticketfilters) != "undefined") {
                clientexec.updateticketfiltercounts(response.ticketfilters);
            }

            if (response.ataglance && typeof dashboard != 'undefined') {
                dashboard.renderAtAGlance(response.ataglance);
            }
        }
    });

    // clientexec.add_intercom();
    $(document).on('click','.m-growlicon',function() {
        clientexec.show_growl_events();
    });

    $(document).on('click','.m-remove-growls',function() {
        clientexec.hide_growl_events();
    });

});

/**
 * we call this when an action performed requires immediate ticket count updates
 * Warning: This call will clear the shared file cache for getvitals so let's call this as infrequently as possible
 * @return void
 */
clientexec.update_ticket_filters = function()
{
    $.get('index.php?fuse=support&controller=ticketfilter&action=getticketfilter',function(response){
        clientexec.updateticketfiltercounts(response);
    });
}


clientexec.eventdetailwindow = function(id, entryType)
{
    if (entryType == "email") {
        clientexec.eventwindow.setUrl('index.php?fuse=clients&view=historyfullemail');
    } else if (entryType == "note"){
        clientexec.eventwindow.setUrl('index.php?fuse=clients&view=eventlogstaffnote');
    } else {
        clientexec.eventwindow.setUrl('index.php?fuse=clients&view=eventdetails');
    }
    clientexec.eventwindow.show({params:{entryId:id,entryType:entryType}});
}


clientexec.removeURLParameter = function(url, parameter)
{
  var urlparts= url.split('?');

  if (urlparts.length>=2)
  {
      var urlBase=urlparts.shift(); //get first part, and remove from array
      var queryString=urlparts.join("?"); //join it back up

      var prefix = encodeURIComponent(parameter)+'=';
      var pars = queryString.split(/[&;]/g);
      for (var i= pars.length; i-->0;)               //reverse iteration as may be destructive
          if (pars[i].lastIndexOf(prefix, 0)!==-1)   //idiom for string.startsWith
              pars.splice(i, 1);
      url = urlBase+'?'+pars.join('&');
  }
  return url;
}

clientexec.notifybrowser = function(title,msg,timeout) {

  if (!timeout) timeout = 3500;

  if (!window.webkitNotifications) { return; }

  //var msg = $(msg).text();
  var havePermission = window.webkitNotifications.checkPermission();

  if (havePermission === 0) {
    // 0 is PERMISSION_ALLOWED
    var notification = window.webkitNotifications.createNotification(
      '',
      "(ce) "+title, msg
    );
    notification.onclick = function () {
      //window.open("http://stackoverflow.com/a/13328397/1269037");
      notification.close();
    };
    notification.show();

    if (timeout > 0) {
      // Hide the notification after the timeout
      setTimeout(function(){
        notification.cancel();
      }, timeout);
    }

  } else {
      window.webkitNotifications.requestPermission();
  }
};

clientexec.updateCustomField = function(customfieldname,newvalue, staffid, callback)
{
    //if we are not passing a user to update the custom field for then assume
    //we are trying to update our own custom field
    if (typeof(staffid) == "undefined") staffid = clientexec.admin_id;
    if (typeof(callback) == "undefined") callback = function(){};
    $.ajax({
        url: "index.php?fuse=clients&controller=customfields&action=save",
        type: 'POST',
        data: {
            newvalue: newvalue,
            staffid: staffid,
            customfieldname: customfieldname
        },
        success: function(response) {
            callback(response);
        }
    });
};

clientexec.updateticketfiltercounts = function(response) {
    var menu_item;
    var waiting_tickets_count = 0;
    var sidebar = $('#ticketfilters').length > 0;
    if (response.filters.length > 0) {
       if (sidebar) {
            $('#ticketfilters ul').empty();
            $('#ticketfilters #addTicketFilter').show();
        }

        $.each(response.filters,function(i,obj) {

            menu_item = $('.nav_support a[data-filter-id="'+obj.ticketfilter_id+'"]');
            if ($(menu_item).find('.menu-counter').length == 0) {
                $(menu_item).prepend($('<span class="menu-counter label label-inverse">'));
            }

            if (obj.ticketfilter_count == 0) {
                $(menu_item).find('.menu-counter').removeClass('label-important').addClass('label-inverse').text(obj.ticketfilter_count);
            } else {
                $(menu_item).find('.menu-counter').addClass('label-important').removeClass('label-inverse').text(obj.ticketfilter_count);
            }

            if (obj.ticketfilter_id == clientexec.favorite_filter) {
                waiting_tickets_count = obj.ticketfilter_count;
            }

            // ignore other peeps' filters
            if (sidebar && menu_item.length > 0) {
                $('#ticketfilters ul').append('<li><span class="menu-counter label label-inverse">' +
                    obj.ticketfilter_count +
                    '</span>\n<a href="index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter=' +
                    ce.htmlspecialchars(obj.ticketfilter_id) +
                    '">' +
                    ce.htmlspecialchars(obj.ticketfilter_name) +
                    '</a></li>');
            }
        });

        if (sidebar) {
            ticketfilters.plugin.setContent();
        }
    }

    //tickets waiting response
    if (waiting_tickets_count == 0) {
        $('.nav_support .topmenu-count-label').removeClass('label-important').addClass('label-inverse').text('');
        $('.nav_support > a').attr('href','#');
    } else if ($('.nav_support .topmenu-count-label').length == 0) {
        var count = $('<span>').addClass('topmenu-count-label label label-important').text(waiting_tickets_count);
        $('.nav_support').append(count);
        $('.nav_support .topmenu-count-label').addClass('label-important').removeClass('label-inverse');
        if (!ce.isMobile()) {
          $('.nav_support > a').attr('href','index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter='+clientexec.favorite_filter);
        }
    } else {
        $('.nav_support .topmenu-count-label').text(waiting_tickets_count);
        $('.nav_support .topmenu-count-label').addClass('label-important').removeClass('label-inverse');
        if (!ce.isMobile()) {
          $('.nav_support > a').attr('href','index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter='+clientexec.favorite_filter);
        }
    }
};

/**
 * checks global clientexec namespace looking to see if an id is online
 * @param  int id staff id
 * @return bool
 */
clientexec.checkifuserisonline = function (id)
{
    var found = false;

    if (!clientexec.whoisonline.onlineusers) return found;
    for (var j = 0; j < clientexec.whoisonline.onlineusers.length; j++)
    {
        if (clientexec.whoisonline.onlineusers[j].id == id) found = true;
    }
    return found;
};

clientexec.updateonlineusers = function(response)
{

    if (!response.users) return;
    var showAlerts;

    // Only show online users alerts if this isn't the first load
    if (clientexec.whoisonline.userCount > 0) {
        showAlerts = true;
    } else {
        showAlerts = false;
    }

    // Update the count if it has changed
    if (clientexec.whoisonline.userCount != response.count) {
        //we only want userCount to be online users
        clientexec.whoisonline.userCount = response.count;
    }

    $.each(response.users,function(index,objValue){
        if ( (showAlerts) && (objValue.type=="staff") && (!clientexec.checkifuserisonline(objValue.id)) && (objValue.id != clientexec.admin_id) ) {
            // clientexec.addNewEvent(objValue.name + ' has logged on','Login System',objValue.email);
        } else if (objValue.id == clientexec.admin_id) {
            clientexec.whoisonline.me = objValue;
        }

        if (objValue.type == "staff") {
            clientexec.show_staff(objValue);
        }
    });

    clientexec.whoisonline.onlineusers = response.users;
    clientexec.remove_staff(response.users);
    if( typeof(response.offlineusers) != "undefined" ){
         clientexec.whoisonline.offlineusers = response.offlineusers;
    }

}

/**
 * removes staff if they are not online
 * @return {[type]}       [description]
 */
clientexec.remove_staff = function() {
    var email;
    $('.leftbar-staff-list img').each(function(i,o) {
        email = $(o).attr('data-email');
        if (!clientexec.is_online(email)) {
            $('.leftbar-staff-list [data-email="'+email+'"]').remove();
        }
    });
}

/**
 * uses whoisonline to determine if a particular email is online
 * @param  {[type]}  useremail [description]
 * @return {Boolean}           [description]
 */
clientexec.is_online = function(useremail) {
    var returnval = false;
    $.each(clientexec.whoisonline.onlineusers, function(i,o) {
        if (o.email == useremail) {
            returnval = true;
        }
    })
    return returnval;
}

/**
 * Let's show online staff members
 * @param  {[type]} user [description]
 * @return {[type]}      [description]
 */
clientexec.show_staff = function(user) {
    if (user.id == clientexec.admin_id) return;
    if ($('.leftbar-staff-list [data-email="'+user.email+'"]').length == 0) {
        $('.leftbar-staff-list').append('<img class="staff-image" data-email="'+user.email+'" src="'+ce.getAvatarUrl(user.email,80,user.name)+'" />');
    } else {
    }
}

$( window ).resize(function() {
    if ( $(window).height() < 500) {
        $('.leftbar-staff-list').slideUp();
    } else {
        $('.leftbar-staff-list').slideDown();
    }
});

clientexec.updatevitals = function(needyourattention) {
    var pending_users_num = 0;
    var ouststanding_invoices_count = 0;
    var pending_items_total = 0;

    if (needyourattention.length > 0) {
         $.each(needyourattention,function(i,obj) {

            if (obj['menu-class'] == "menu-pending-orders") {
                pending_users_num = obj['count-raw'];
            } else if (obj['menu-class'] == "menu-invoices-ready" && clientexec.settings.billing.showReadyInvoiceCount ) {
                ouststanding_invoices_count = parseInt(ouststanding_invoices_count) + parseInt(obj['count-raw']);
            } else if (obj['menu-class'] == "menu-cc-invoices-ready" && clientexec.settings.billing.showReadyCCInvoiceCount ) {
                ouststanding_invoices_count = parseInt(ouststanding_invoices_count) + parseInt(obj['count-raw']);
            } else if (obj['menu-class'] == "menu-cc-invoices-failed" && clientexec.settings.billing.showFailedCCCount ) {
                ouststanding_invoices_count = parseInt(ouststanding_invoices_count) + parseInt(obj['count-raw']);
            }

            if ($('.'+obj['menu-class']+' .menu-counter').length == 0) {
                $('.'+obj['menu-class']).prepend($('<span class="menu-counter label label-inverse">'))
            }

            $('.'+obj['menu-class']).attr('href',obj['link']);

            if (obj['count'] == 0) {
                $('.'+obj['menu-class']+' .menu-counter').removeClass('label-important').addClass('label-inverse').text(obj['count']);
            } else {
                pending_items_total++;
                $('.'+obj['menu-class']+' .menu-counter').addClass('label-important').removeClass('label-inverse').text(obj['count']);
            }

         });
    }

    //late invoices
    if (ouststanding_invoices_count == 0) {
        $('.nav_billing .topmenu-count-label').removeClass('label-important').addClass('label-inverse');
        // Update text so we can go from X to 0 if need be.
        $('.nav_billing .topmenu-count-label').text(ouststanding_invoices_count);
    } else if ($('.nav_billing .topmenu-count-label').length == 0) {
        new_el = $('<span>').addClass('topmenu-count-label label label-important').text(ouststanding_invoices_count);
        $('.nav_billing').append(new_el);
    } else {
        $('.nav_billing .topmenu-count-label').removeClass('label-inverse').addClass('label-important').text(ouststanding_invoices_count);
    }

    //let's add count to dashboard menu
    if (pending_items_total == 0) {
        $('.nav_dashboard .topmenu-count-label').removeClass('label-important').addClass('label-inverse');
    } else if ($('.nav_dashboard .topmenu-count-label').length == 0) {
        new_el = $('<span>').addClass('topmenu-count-label label label-important').text(pending_items_total);
        $('.nav_dashboard').append(new_el);
    } else {
        $('.nav_dashboard .topmenu-count-label').removeClass('label-inverse').addClass('label-important').text(pending_items_total);
    }

};

clientexec.raise_badge_count = function()
{
    clientexec.favicon.badge(++clientexec.badge);
}

clientexec.updateEvents = function(newEvents) {
    var newEventCount = newEvents.length;
    var new_el = null;
    var firsttime = false;

    if (clientexec.last_event_id == 0) {
        firsttime = true;
    }

    if (newEventCount > 0) {
        newEvents.sort(function(a,b){
            if (a.eventid < b.eventid) return -1;
            if (a.eventid > b.eventid) return 1;
            return 0;
        });

        for(var x=0; x < newEventCount; x++) {
            //only show events we didn't cause or have seen
            if ( (newEvents[x].userid != clientexec.admin_id) && (newEvents[x].eventid > clientexec.last_event_id) ) {
            // if ( (newEvents[x].eventid > clientexec.last_event_id) ) {
                var text = ce.htmlspecialchars(newEvents[x].subject);
                if (newEvents[x].tpl && newEvents[x].tpl == 'link') {
                    text = text.replace(/\[link\]/, newEvents[x].link);
                }
                if (!firsttime) clientexec.addNewEvent(text,ce.htmlspecialchars(newEvents[x].fullName),newEvents[x].email,newEvents[x].date);
                // clientexec.addNewEvent(text,ce.htmlspecialchars(newEvents[x].fullName),newEvents[x].email,newEvents[x].date);
                clientexec.last_event_id = newEvents[x].eventid;
            }

        }

    }

}

function createNewFilter()
{

    //let's hide menu
    $('.ce-top-menu li .drop').addClass('hide-drop');
    setTimeout(function(){$('.ce-top-menu li .drop').removeClass('hide-drop')},500);

    customfilterwin = new RichHTML.window({
        url: 'index.php?fuse=support&controller=ticketfilter&view=viewadd',
        actionUrl: 'index.php?fuse=support&action=create&controller=ticketfilter',
        title: lang('Ticket Search'),
        width: '800',
        height: '300',
        showSubmit: true,
        onSubmit: function(response) {
            var filterName = 'custom_' + response.filterID;
            if ( response.error == false ) {
                window.location.href = "index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter="+filterName;
            }
        }
    });
    customfilterwin.show();
}

function moveGuestToCustomer()
{
    RichHTML.msgBox(lang("NOTE: This operation will move all the tickets from this Email to the user whose ID you will enter.\nAlso, this Email will be added as an alternate address for future support requests from this user."),{type: 'yesno'}, function(response){
        if (response.btn == lang("Yes")) {
            RichHTML.prompt(lang("Migrate Guest to User ID:"),{},function(response){
                if (response.btn == lang("OK")) {
                    RichHTML.mask();
                    new_user_id = response.elements.value;
                    $.post("index.php?fuse=clients&action=setowner&controller=userprofile",{userId:new_user_id},function(response){
                        json = ce.parseResponse(response);
                        if (!json.error) {
                            window.location.href = "index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID="+new_user_id;
                        } else {
                            RichHTML.unMask();
                        }
                    })
                }
            });
        }
    });

}

/**
* Selected customer note
*/
profilenotes = {};
ce.addNote = function(noteId) {
    if ( profilenotes.grid === 'undefined' ) { profilenotes.grid = null }

    if ( typeof noteId === 'undefined' ) {
        noteId = 0;
    }

    profilenotes.window = new RichHTML.window({
        height: '315',
        url: 'index.php?fuse=clients&controller=notes&view=addnotes' + '&id=' + noteId,
        actionUrl: 'index.php?fuse=clients&action=save&controller=notes' + '&id=' + noteId,
        grid: profilenotes.grid,
        showSubmit: true,
        title: lang("Add Staff Note"),
        onSubmit: function() {
            if ( typeof profile !== 'undefined' ) {
                setTimeout(function() {
                    profile.get_counts();
                },1000);
            }
        }
    });
    profilenotes.window.show();
};

/**
* Selected customer add support ticket
*/
supportticket = {};
supportticket.ticketId = 0;
ce.addSupportTicket = function(a1,a2) {
    supportticket.window = new RichHTML.window({
        height: '425',
        width: '500',
        url: 'index.php?fuse=support&controller=ticket&view=viewaddticket',
        actionUrl: 'index.php?fuse=support&action=saveticket&controller=ticket&ignore=1',
        showSubmit: true,
        title: lang("Add Support Ticket"),
        beforeSubmit: function() {
            if ($("#supportticket-tickettype").select2("val") === '') {
                var err = lang("You must fill in all the required fields before submitting your ticket.");
                RichHTML.error(err);
                return false;
            }

            var formSelector = supportticket.window.form;
            var fileBlobs = [];
            var fileInputs = $("#file-upload-field");

            for (var i = 0; i < fileInputs.length; i++) {
                if ($("#file-upload-field").get(i).value) {
                    fileBlobs.push($("#file-upload-field").get(i).files[0]);
                }
            }

            if (fileBlobs.length == 0) {
                supportticket.window.options.actionUrl = 'index.php?fuse=support&action=saveticket&controller=ticket';
            } else {
                var url = 'index.php?fuse=support&action=saveticket&controller=ticket';
                var data = $(formSelector).serializeArray();
                $(formSelector).fileupload({url: url});
                $(formSelector).fileupload('send', {
                    files: fileBlobs,
                    formData: data
                }).success(function(res) {
                    if ( res.success === true ) {
                        supportticket.ticketId = res.ticketId;
                        window.location = 'index.php?fuse=support&id=' + supportticket.ticketId + '&view=viewtickets&controller=ticket';
                    }
                }).fail(function(res) {
                    var err = lang("There was an error with this operation");
                    try {
                        var json = $.parseJSON(res.responseText);
                        err = json.message;
                    } catch(ex) {
                        // response is invalid json, but might still contain an error string
                        // (see issue #1024)
                        var matches = /"message":"(.*)"/.exec(res.responseText);
                        if (matches && matches[1]) {
                            err = matches[1];
                        }
                    }
                    RichHTML.error(err);
                });
            }
        },
        onSubmit: function(response) {
            if ( response.success === true ) {
                if (typeof response.ticketId !== 'undefined') {
                    supportticket.ticketId = response.ticketId;
                }
                if (supportticket.ticketId != 0) {
                    window.location = 'index.php?fuse=support&id=' + supportticket.ticketId + '&view=viewtickets&controller=ticket';
                }
            }
        }
    });
    supportticket.window.show();
};

/**
* Selected customer add email
*/
emailcustomer = {};
ce.addEmailCustomer = function() {
    // ** define window object to handle edits and loads
    emailcustomer.window = new RichHTML.window({
        height: '400',
        width: '500',
        url: 'index.php?fuse=clients&controller=email&view=viewaddemail',
        actionUrl: 'index.php?fuse=clients&controller=email&action=sendemail',
        showSubmit: true,
        title: lang("Email Customer"),
        onSubmit: function(xhr) {
            if (xhr.error) {
                ce.errormsg("There was an error sending the email: " + xhr.message);
            } else {
                ce.msg("email has been sent");
            }
        }
    });
    emailcustomer.window.show();
};

function togglePackageListType(types) {
    typesArray = types.split('_');
    type = typesArray[0];
    groupId = '';
    if(typesArray[1] !== undefined){
        groupId = '&groupid='+typesArray[1];
    }
    if (type == 'domain') {
        window.location = 'index.php?fuse=clients&controller=packages&view=domainslist'+groupId;
    } else if (type == 'hosting') {
        window.location = 'index.php?fuse=clients&controller=packages&view=hostingpackagelist'+groupId;
    } else if (type =='general') {
        window.location = 'index.php?fuse=clients&controller=packages&view=generalpackageslist'+groupId;
    } else if (type == 'ssl') {
        window.location = 'index.php?fuse=clients&controller=packages&view=sslpackagelist'+groupId;
    }
}

ce.htmlentities = function(html) {
    return $('<div/>').text(html).html();
};
ce.unhtmlentities = function(text) {
    return $('<div/>').html(text).text();
};


//Simulates PHP's date function
Date.prototype.format = function(format) {
    var returnStr = '';
    var replace = Date.replaceChars;
    for (var i = 0; i < format.length; i++) {
        var curChar = format.charAt(i);
        if (i - 1 >= 0 && format.charAt(i - 1) == "\\") {
            returnStr += curChar;
        } else if (replace[curChar]) {
            returnStr += replace[curChar].call(this);
        } else if (curChar != "\\"){
            returnStr += curChar;
        }
    }
    return returnStr;
};

Date.replaceChars = {
    shortMonths: [lang('Jan'), lang('Feb'), lang('Mar'), lang('Apr'), lang('May'), lang('Jun'), lang('Jul'), lang('Aug'), lang('Sep'), lang('Oct'), lang('Nov'), lang('Dec')],
    longMonths: [lang('January'), lang('February'), lang('March'), lang('April'), lang('May'), lang('June'), lang('July'), lang('August'), lang('September'), lang('October'), lang('November'), lang('December')],
    shortDays: [lang('Sun'), lang('Mon'), lang('Tue'), lang('Wed'), lang('Thu'), lang('Fri'), lang('Sat')],
    longDays: [lang('Sunday'), lang('Monday'), lang('Tuesday'), lang('Wednesday'), lang('Thursday'), lang('Friday'), lang('Saturday')],

    // Day
    d: function() { return (this.getDate() < 10 ? '0' : '') + this.getDate(); },
    D: function() { return Date.replaceChars.shortDays[this.getDay()]; },
    j: function() { return this.getDate(); },
    l: function() { return Date.replaceChars.longDays[this.getDay()]; },
    N: function() { return this.getDay() + 1; },
    S: function() { return (this.getDate() % 10 == 1 && this.getDate() != 11 ? 'st' : (this.getDate() % 10 == 2 && this.getDate() != 12 ? 'nd' : (this.getDate() % 10 == 3 && this.getDate() != 13 ? 'rd' : 'th'))); },
    w: function() { return this.getDay(); },
    z: function() { var d = new Date(this.getFullYear(),0,1); return Math.ceil((this - d) / 86400000); }, // Fixed now
    // Week
    W: function() { var d = new Date(this.getFullYear(), 0, 1); return Math.ceil((((this - d) / 86400000) + d.getDay() + 1) / 7); }, // Fixed now
    // Month
    F: function() { return Date.replaceChars.longMonths[this.getMonth()]; },
    m: function() { return (this.getMonth() < 9 ? '0' : '') + (this.getMonth() + 1); },
    M: function() { return Date.replaceChars.shortMonths[this.getMonth()]; },
    n: function() { return this.getMonth() + 1; },
    t: function() { var d = new Date(); return new Date(d.getFullYear(), d.getMonth(), 0).getDate() }, // Fixed now, gets #days of date
    // Year
    L: function() { var year = this.getFullYear(); return (year % 400 == 0 || (year % 100 != 0 && year % 4 == 0)); },   // Fixed now
    o: function() { var d  = new Date(this.valueOf());  d.setDate(d.getDate() - ((this.getDay() + 6) % 7) + 3); return d.getFullYear();}, //Fixed now
    Y: function() { return this.getFullYear(); },
    y: function() { return ('' + this.getFullYear()).substr(2); },
    // Time
    a: function() { return this.getHours() < 12 ? 'am' : 'pm'; },
    A: function() { return this.getHours() < 12 ? 'AM' : 'PM'; },
    B: function() { return Math.floor((((this.getUTCHours() + 1) % 24) + this.getUTCMinutes() / 60 + this.getUTCSeconds() / 3600) * 1000 / 24); }, // Fixed now
    g: function() { return this.getHours() % 12 || 12; },
    G: function() { return this.getHours(); },
    h: function() { return ((this.getHours() % 12 || 12) < 10 ? '0' : '') + (this.getHours() % 12 || 12); },
    H: function() { return (this.getHours() < 10 ? '0' : '') + this.getHours(); },
    i: function() { return (this.getMinutes() < 10 ? '0' : '') + this.getMinutes(); },
    s: function() { return (this.getSeconds() < 10 ? '0' : '') + this.getSeconds(); },
    u: function() { var m = this.getMilliseconds(); return (m < 10 ? '00' : (m < 100 ? '0' : '')) + m; },
    // Timezone
    e: function() { return "Not Yet Supported"; },
    I: function() { return "Not Yet Supported"; },
    O: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + '00'; },
    P: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + ':00'; }, // Fixed now
    T: function() { var m = this.getMonth(); this.setMonth(0); var result = this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/, '$1'); this.setMonth(m); return result;},
    Z: function() { return -this.getTimezoneOffset() * 60; },
    // Full Date/Time
    c: function() { return this.format("Y-m-d\\TH:i:sP"); }, // Fixed now
    r: function() { return this.toString(); },
    U: function() { return this.getTime() / 1000; }
};

/*
 * works like .indexOf, except will return the index of the first object that contains the same properties/values
 * can also work with objects that have more properties than the needle
 * ex. [{a:1,b:2,c:3},{a:2,b:"foo",c:3},{a:{zz:1,yy:2},b:"bar"}]
 * .indexOfObject({a:1,b:2,c:3}) - returns 0
 * .indexOfObject({c:3}) - returns 0
 * .indexOfObject({b:"2"}) - returns -1 (type mismatch, 2 !== "2")
 * .indexOfObject({a:{yy:2}}) - returns 2
 * .indexOfObject({a:{yy:2},c:3}) - returns -1 (first property matches, but doesn't have second property)
 */

if (!Array.prototype.indexOfObject) { // copied from Mozilla reference with added funtion for comparing object properties
    Array.prototype.indexOfObject = function (searchElement /*, fromIndex */ ) {
        "use strict";
        if (this == null) {
            throw new TypeError();
        }
        var t = Object(this);
        var len = t.length >>> 0;
        if (len === 0) {
            return -1;
        }
        var n = 0;
        if (arguments.length > 1) {
            n = Number(arguments[1]);
            if (n != n) { // shortcut for verifying if it's NaN
                n = 0;
            } else if (n != 0 && n != Infinity && n != -Infinity) {
                n = (n > 0 || -1) * Math.floor(Math.abs(n));
            }
        }
        if (n >= len) {
            return -1;
        }
        var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
        for (; k < len; k++) {
            if (compareObj(searchElement, t[k]) !== -1) {
                return k;
            }
        }
        return -1;

        function compareObj(needle, haystack) {
            for (var key in needle) {
                if (!haystack.hasOwnProperty(key)) {
                    return -1;
                } else if (typeof (haystack[key]) !== typeof (needle[key])) {
                    return -1;
                } else {
                    if (typeof (needle[key]) == 'object') {
                        if (!compareObj(needle[key], haystack[key])) { return false; }
                    } else {
                        if (haystack[key] !== needle[key]) {
                            return -1;
                        }
                    }
                }
            }
            return 1;
        }
    };
}
