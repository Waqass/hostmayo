var ticketview = ticketview || {};
ticketview.ignoreHeartbeat = false;
ticketview.can_reply = false;
ticketview.editedOldMessage = '';
ticketview.getfirstTicketID='';
ticketview.getTicketID='';
ticketview.originalMessage = '';
ticketview.hasAttachments = false;


ticketview.preactionstodoonload = function(ticketid) {
    var stateObj = { ticket_id: ticketid};
    History.pushState(stateObj, clientexec.original_title, "index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter="+ticketList.searchfilter+"&id="+ticketid);
}

/**
 * Some methods we need to do once DOM is loaded
 * We move these here so that we can also call from the ticket dashboard plugin
 * @return void
 */
ticketview.actionstodoonload = function(ticketid)
{

    //let's update customer
    if ((clientexec.customerId == 0) && (clientexec.show_active_customer_panel_default)) {
        clientexec.load_active_user_panel(false);
        $('.btn-active-user-profile-toggle').show();
        $('.main').addClass("with-active-user");
        clientexec.pin_active_user_panel();
    } else if (clientexec.customerId == 0) {
        $('.btn-active-user-profile-toggle').show();
        $('.main').addClass("with-active-user");
    }

    if (ticketid != ticketview.ticketid) ticketview.clear_ticket();
    $('.all-tickets-view').hide();
    $('.active-ticket-view').show();

    if (ticketid == ticketview.ticketid) return;

    $(".ajaxloadedtab").hide();
    $('.ticket-active-tab').empty()
    $("#messages_loading").show();
    ToggleSecondOptionsPanel(0);

    ticketview.originalMessage = $('#message').text();

    ticketview.ticketid = ticketid;
    RichHTML.mask();
    $.ajax({
        url: 'index.php?fuse=support&action=getticket&controller=ticket',
        data: {
            id : ticketid
        },
        success : function(xhr) {
            var json = ce.parseResponse(xhr);

            if (json.error) {
                RichHTML.unMask();
                window.location = 'index.php?fuse=support&view=viewtickets&controller=ticket';
                return;
            }

            $("#messages_loading").hide();
            ticketview.current_log_count = 0;
            ticketview.getfirstTicketID = 0;
            ticketview.getTicketID=json.metadata.id;
            $.each(json.comments, function(index, value) {
                ticketview.current_log_count ++;
                if (value.logtype == 0) {
                    if(ticketview.current_log_count==0){
                        ticketview.getfirstTicketID=value.logid;
                    }
                    ticketview.add_new_log_message(value);
                } else {
                    ticketview.add_new_metalog_message(value);
                }

            });

            ticketview.ticketid = json.metadata.id
            ticketview.subscribe_value = json.metadata.subscribe_id;
            ticketview.ticket_type = json.metadata.ticket_type;

            ticketview.updateticket_view(json.metadata);
            ticketview.get_ticket_details();
            RichHTML.unMask();

            if (clientexec.customerId != json.metadata.userid) {
                clientexec.getSelectedProfileAndAvailableActions();
                clientexec.customerId = json.metadata.userid;
            }

        }
    });

    var showMetalogs = $.cookie('show-tktmetalog-' + gVer);
    if (typeof showMetalogs != 'undefined' && showMetalogs != '0') {
        $('#toggleStateLog').prop('checked', true);
    }

    $('#toggleStateLog').change(function() {
        var show;
        if (this.checked) {
            $('.metalog-data').show(500);
            show = 1;
        } else {
            $('.metalog-data').hide(500);
            show = 0;
        }
        $.cookie('show-tktmetalog-' + gVer, show, {expires: 365});
    });
};

ticketview.clear_ticket = function()
{
    $('#ticket-top-bar-alsoviewing').hide();
    $('.ticket_subject_name').val('');
    $('.ticket-time-elapsed').text('');
    $('.ticket-top-bar-num').text('...');
    $('.ticket-top-bar-status-name').text('...');
    $('.ticket-top-bar-status').removeClass('ticket-top-bar-status-closed');
    $('.ticket-top-bar-type .dropdown-toggle').text('...');
    $('.ticket-top-bar-assignedto .dropdown-toggle').text('...');
    $('.ticket-top-bar-assignedtopackage .dropdown-toggle').text('...');
    $('.ticket-top-bar-priority .dropdown-toggle').removeClass('ticket-top-bar-priority-1 ticket-top-bar-priority-2 ticket-top-bar-priority-3');

    //available actions on ticket
    $('.ticket-top-bar-rating').hide();
    $('.ticket-top-bar-rating .rating-text').hide();
    $('.ticket-action-subscription').hide();
    $('.ticket-action-spam').hide();
    $('.ticket-action-delete').hide();
    $('.ticket-action-migrate').hide();
    $('.ticket-action-close').hide();

    $('.ticket-reply').hide();
    $('.message').val('');

    ticketview.hasAttachments = false;
    $('#attachedfileblock > div').remove();


}

ticketview.updateticket_view = function(metadata)
{

    $('.ticket-top-bar-num').text("#"+metadata.id);
    $('.ticket_subject_name').val(metadata.subject);
    $('.ticket-time-elapsed').text(metadata.time_elapsed);

    //set status
    $('.ticket-top-bar-status-name').text(metadata.ticket_status_name);
    $('.ticket-top-bar-status li').removeClass('active');
    $('.ticket-top-bar-status li[data-id="'+metadata.ticket_status+'"]').addClass('active');
    if (metadata.ticket_status_closed) {
        $('.ticket-top-bar-status').addClass('ticket-top-bar-status-closed');
    }

    //set type
    $('.ticket-top-bar-type .dropdown-toggle').text(metadata.ticket_type_name);
    $('.ticket-top-bar-type li').removeClass('active');
    $('.ticket-top-bar-type li[data-id="'+metadata.ticket_type+'"]').addClass('active');

    //set priority
    $('.ticket-top-bar-priority .dropdown-toggle').addClass('ticket-top-bar-priority-'+metadata.priority);
    $('.ticket-top-bar-priority li').removeClass('active');
    $('.ticket-top-bar-priority li[data-id="'+metadata.priority+'"]').addClass('active');

    //available actions on ticket
    if (metadata.rating) {
        $('.ticket-top-bar-rating').show();
        $('.ticket-top-bar-rating .rating-rate').removeClass('rating-hastext').data('toggle', '').data('content', '').text(metadata.rating);
        if (metadata.rating_text) {
            $('.ticket-top-bar-rating .rating-rate').attr('title', lang("Comments")+(new Array(61).join('&nbsp;')));
            $('.ticket-top-bar-rating .rating-rate').addClass('rating-hastext').attr('data-toggle', 'popover-hover').attr('data-content', nl2br(metadata.rating_text));
        }
    }
    if (metadata.show_notification_action) {
        $('.ticket-action-subscription').show();
        $('.ticket-action-subscription #btnSubscribe').text(metadata.subscribe_title);
    }
    if (metadata.show_spam_action) {
        $('.ticket-action-spam').show();
    }
    if (metadata.show_delete_action) {
        $('.ticket-action-delete').show();
    }
    if (metadata.isGuest) {
        $('.ticket-action-migrate').show();
    }
    if (metadata.show_close_action) {
        $('.ticket-action-close').show();
    }

    //show reply
    ticketview.can_reply = metadata.can_reply_action;
    if (metadata.show_reply_action) {
        $('.ticket-reply').show();
    }

    //attachments
    if (metadata.attachments.length > 0) {
        ticketview.hasAttachments = true;
        $.each(metadata.attachments, function(index, attachment) {
            $('#attachedfileblock').append('<div style="font-size:10px !important;padding:2px;">' +
                    '<span>' +
                        '<span class="ticket-attachment">' + attachment.date_added + '</span> &nbsp;by:' + attachment.added_by_name + ' &nbsp;' + attachment.url +
                        '&nbsp;&nbsp;[<span style="color:red">&nbsp;' + attachment.delete_url + '&nbsp;</span>]&nbsp;' +
                    '</span>' +
                '</div>');
        });
        $('#attachedfileblock').show();
    }

}

ticketview.get_ticket_details = function()
{

    ticketview.startheartbeat();
    $('#tickettab_customfields_tab sup').css('visibility', 'hidden');
    setTimeout(function(){
        ticketview.getassignees();
        ticketview.getpackages();
        ticketview.loadkbarticlesfornewtickettype();
        ticketview.loadCustomFields();
        ticketview.loadPackageDetails();
    }, 700);

}


/**
 * Submit chagne to the ticket's priority via ajax
 * @param  obj e jQuery event
 * @return void
 */
ticketview.changepriority = function(e)
{

    var newprio = $(e).attr('data-id');

    $.ajax({
        url: 'index.php?fuse=support&action=setpriority&controller=ticket',
        success:function(t) {
            json = ce.parseResponse(t);
            $('.ticket-top-bar-priority .dropdown-toggle').removeClass('ticket-top-bar-priority-1').removeClass('ticket-top-bar-priority-2').removeClass('ticket-top-bar-priority-3');
            $('.ticket-top-bar-priority .dropdown-toggle').addClass('ticket-top-bar-priority-'+newprio);
            $('.ticket-top-bar-priority li').removeClass('active');
            $('.ticket-top-bar-priority li[data-id="'+newprio+'"]').addClass('active');
            clientexec.update_ticket_filters();
        },
        data: {
            ticket : ticketview.ticketid,
            priority: newprio
        }
    });
};

ticketview.changeassignee = function(e)
{

    var newuserid = $(e).attr('data-id');

    $.ajax({
        url: 'index.php?fuse=support&action=assignticket&controller=ticket',
        success: function(t) {
            json = ce.parseResponse(t);
            $('.ticket-top-bar-assignedto li').removeClass('active');
            $('.ticket-top-bar-assignedto li[data-id="'+newuserid+'"]').addClass('active');
            $('.ticket-top-bar-assignedto .dropdown-toggle').text(json.user_name);
            clientexec.update_ticket_filters();
        },
        data: {
            ticketId : ticketview.ticketid,
            clientId: newuserid
        }
    });
};

ticketview.changeassignedpackage = function(e)
{

    var packageId = $(e).attr('data-id');

    $.ajax({
        url: 'index.php?fuse=support&action=assignproductidtoticket&controller=ticket',
        success: function(t) {
            json = ce.parseResponse(t);
            $('.ticket-top-bar-assignedtopackage li').removeClass('active');
            $('.ticket-top-bar-assignedtopackage li[data-id="'+packageId+'"]').addClass('active');
            $('.ticket-top-bar-assignedtopackage .dropdown-toggle').text(json.package_name);
            $('#view-package-link').attr('href', 'index.php?fuse=clients&controller=userprofile&view=profileproduct&id=' + packageId);
            $('#view-package-link').show();
            clientexec.update_ticket_filters();
            ticketview.loadPackageDetails();
        },
        data: {
            ticketId : ticketview.ticketid,
            packageId: packageId
        }
    });
};

/**
 * Submit chagne to the ticket's type via ajax
 * @param  obj e jQuery event
 * @return void
 */
ticketview.settype = function(e)
{
    var new_type = $(e).attr('data-value');

    $.ajax({
        url: 'index.php?fuse=support&action=settype&controller=ticket',
        success: function(t) {
            json = ce.parseResponse(t);

            $('.ticket-top-bar-type li').removeClass('active');
            $('.ticket-top-bar-type li[data-id="'+new_type+'"]').addClass('active');
            $('.ticket-top-bar-type .dropdown-toggle').text(json.type_name);

            ticketview.ticket_type = new_type;
            ticketview.loadkbarticlesfornewtickettype();
            ticketview.loadCustomFields();
            clientexec.update_ticket_filters();
        },
        data: {
            ticket : ticketview.ticketid,
            type: new_type
        }
    });
};

ticketview.changesubjectxhr = null;
ticketview.xhrtimer = null;
ticketview.changesubject = function(e)
{
    if (ticketview.changesubjectxhr ) { ticketview.changesubjectxhr .abort(); } // If there is an existing XHR, abort it.
    clearTimeout(ticketview.xhrtimer); // Clear the timer so we don't end up with dupes.
    ticketview.xhrtimer = setTimeout(function() { // assign timer a new timeout
        ticketview.changesubjectxhr  = $.post('index.php?action=setticketsubject&fuse=support&controller=ticket',{
            ticketid : ticketview.ticketid,
            subject : $('.ticket_subject_name').val()
        }); // run ajax request and store in x variable (so we can cancel)
    }, 2000); // 2000ms delay, tweak for faster/slower

};

/**
 * Submit change to the ticket's status via ajax
 * @param  obj e jQuery event
 * @return void
 */
ticketview.changestatus = function(e, close)
{

    var new_status = $(e).attr('data-id');

    $.ajax({
        url: 'index.php?fuse=support&action=setstatus&controller=ticket',
        success: function(t) {
            json = ce.parseResponse(t);
            ticketview.updatestatus_ui(new_status, close);
            clientexec.update_ticket_filters();
        },
        data: {
            ticket : ticketview.ticketid,
            status: new_status
        }
    });
};

ticketview.updatestatus_ui = function(status_id, close)
{

    var statusName;
    $('.ticket-top-bar-status li').removeClass('active');
    $('.ticket-top-bar-status li[data-id="'+status_id+'"]').addClass('active');
    statusName = $('.ticket-top-bar-status li[data-id="'+status_id+'"] a').text();

    $('.ticket-top-bar-status .dropdown-toggle').text(statusName);
    $('.ticket-top-bar-status').removeClass('ticket-top-bar-status-closed');

    if (close) {
        $('.ticket-reply').hide();
        $('.ticket-top-bar-status').addClass('ticket-top-bar-status-closed');
    }  else if (ticketview.can_reply) {
        $('.ticket-reply').show();
    }
}

/**
 * let's check to see if kb articles are available for selected type
 * @return void
 */
ticketview.loadkbarticlesfornewtickettype = function(){
    $('#tickettab_messages_tab').trigger('click');
    $('.ajaxloadedtab').remove();

    $.ajax({
        url: 'index.php?fuse=support&controller=ticket&action=getticketadditionalinformation',
        success: function(t) {
            t = ce.parseResponse(t);
            //lets add the new tabs by cloning the tabs currently there
            $.each(t.tabs,
                function(intIndex, objvalue){
                    newTab = $('#tickettab_messages_tab').clone();
                    $(newTab).attr('id',"newtab"+intIndex);
                    $(newTab).addClass(objvalue.tabClass).addClass('ajaxloadedtab');
                    $(newTab).removeClass('active');
                    $('#ajaxTabs').append(newTab);
                    $('#newtab'+intIndex+' span').html('<a href="#">'+objvalue.ticketTabName+'</a>');
                    $('#newtab'+intIndex).attr("data-href",objvalue.ticketTabUrl);
                }
                );
            //ticketview.bindtickettabs();
        },
        data: {
            nolog : '1',
            mainView: gView,
            ticketId: ticketview.ticketid
        }
    });

};

ticketview.cleanup = function() {
    // conserve the signature if there was one
    $('#message').val(ticketview.originalMessage);

    $('input[type=file]').val("");
    $('.new-attachment-files').empty();
    ticketview.fetchLogs();
}

ticketview.submitTicket = function (event, ticketstatus, closed){
    event.preventDefault();

    //we are passing a private message so let's make sure status doesn't change from what is selected
    if (typeof(ticketstatus) == "undefined")  {
        if ($('#SupportForm #private').val() == "1") {
            ticketstatus = $('.ticket-top-bar-status li[data-id].active').attr('data-id');
        } else {
            ticketstatus = 3;
        }
    }

    $('#ticketstatus').val(ticketstatus);
    form = document.forms['SupportForm'];
    if (!ticketview.itemsFilledOut(form)) {
        return false;
    }
    var url = 'index.php?fuse=support&controller=ticket&action=addreplyticket';

    var data = $('#SupportForm').serializeArray();
    data.push({name: "troubleticketid",value: ticketview.ticketid});
    data.push({name: "userid", value: clientexec.admin_id});
    var fileBlobs = [];
    var fileInputs = $('input[type=file]');

    for (var i = 0; i < fileInputs.length; i++) {
        if ($('input[type=file]').get(i).value) {
            fileBlobs.push($('input[type=file]').get(i).files[0]);
        }
    }

    ticketview.ignoreHeartbeat = true;
    RichHTML.mask();
    if (fileBlobs.length == 0) {
        $.post(url, data, function() {
            ticketview.cleanup();
        }).fail(function() {
            ticketview.ignoreHeartbeat = false;
            RichHTML.unMask();
        }).success(function() {
            ticketview.updatestatus_ui(ticketstatus, closed);
        });;
    } else {
        $('#SupportForm').fileupload({url: url});
        $('#SupportForm').fileupload('send', {
            files: fileBlobs,
            formData: data
        }).success(function() {
            $('#SupportForm').fileupload('destroy');
            ticketview.cleanup();
            ticketview.updatestatus_ui(ticketstatus, closed);
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
            ticketview.ignoreHeartbeat = false;
            RichHTML.unMask('.active-ticket-view');
            RichHTML.error(err);
        });
    }

    $(".ticket-reply textarea").removeClass("expanded");
    $(".ticket-reply textarea").focus();
    clientexec.update_ticket_filters();

};

ticketview.itemsFilledOut = function(form){
    strAlertMessage = lang("You must complete all the fields before proceeding");
    bolShowMessage = false;

    if (typeof document.forms['SupportForm'].messagetype != 'undefined' &&  document.forms['SupportForm'].messagetype.value == 0){
        strAlertMessage += '\n' + lang("Select a valid ticket type");
        bolShowMessage = true;
    }

    if (typeof document.forms['SupportForm'].subject  != 'undefined' && document.forms['SupportForm'].subject.value == ""){
        strAlertMessage += '\n' + lang("You can not leave the subject blank");
        bolShowMessage = true;
    }

    if (typeof document.forms['SupportForm'].message != 'undefined' && document.forms['SupportForm'].message.value == ""){
        strAlertMessage += '\n' + lang("You can not leave the message blank");
        bolShowMessage = true;
    }

    if (bolShowMessage){
        RichHTML.error(strAlertMessage);
        return false;
    }

    return true;
};

ticketview.bindtickettabs = function()
{

    $('body').on('click','#btnSubscribe',ticketview.ticketAdditionalNotification);
    $('body').on('click','#btnMarkAsSpam',ticketview.markTicketAsSpam);
    $('body').on('keyup','.ticket_subject_name', ticketview.changesubject);
    $('body').on('click','#btnDeleteTicket',function(){
        RichHTML.msgBox(lang("Are you sure you want to delete this ticket?"),{type:'yesno'},function(e){
            if (e.btn === lang("Yes")) {
                $.ajax({
                    url: 'index.php?fuse=support&controller=ticket&action=delete',
                    data: {
                        ids:ticketview.ticketid
                    },
                    success: function() {
                        window.location = "index.php?fuse=support&controller=ticket&view=viewtickets";
                    }
                });
            }
        });
    });
    $('body').on('click','.tickettab',function(e){

        if ($(this).hasClass('active')) return;

        var el = $(this);
        var id = el.attr('id');
        var url = "";

        var debugStr = clientexec.debugMinifier ? '&debug=true' : '';

        $('.tickettab.active').removeClass('active');
        el.addClass('active');

        $('.ticket-active-tab').html('');
        $('#messages_loading').show();

        if (id == "tickettab_customfields_tab") {
            $('.ticket-customfields').show();
            $('.ticket-package').hide();
            $('.ticket-active-tab').hide();
            $('#messages_loading').hide();

            //hide ticket reply elements
            $('.ticket-time-elapsed').hide();
            $('.ticket-reply').hide();
            $('#attachedfileblock').hide();
        }else if (id == "packagetab"){
            $('.ticket-customfields').hide();
            $('.ticket-package').show();
            $('.ticket-active-tab').hide();
            $('#messages_loading').hide();

            //hide ticket reply elements
            $('.ticket-time-elapsed').hide();
            $('.ticket-reply').hide();
            $('#attachedfileblock').hide();
        }else if (id == "tickettab_messages_tab"){
            //ticketview.startheartbeat();
            //we should be just hiding and unhiding divs here
            reloadticket = ticketview.ticketid;
            ticketview.ticketid = 0;
            ticketview.actionstodoonload(reloadticket);
            $('.ticket-active-tab').show();
            $('.ticket-customfields').hide();
            $('.ticket-package').hide();
            $('#messages_loading').hide();

            //show ticket reply elements
            $('.ticket-time-elapsed').show();
            $('.ticket-reply').show();
            if (ticketview.hasAttachments) {
                $('#attachedfileblock').show();
            }


        } else {
            $('.ticket-customfields').hide();
            $('.ticket-package').hide();
            $('.ticket-active-tab').load(el.data('href')+debugStr, function(){$('#messages_loading').hide();});
            $('.ticket-active-tab').show();

            //hide ticket reply elements
            $('.ticket-time-elapsed').hide();
            $('.ticket-reply').hide();
            if (ticketview.hasAttachments) {
                $('#attachedfileblock').hide();
            }


        }
        e.preventDefault();
    });
};


ticketview.markTicketAsSpam = function()
{
    RichHTML.msgBox(lang("Are you sure you want to mark this ticket as spam?"),{type:'yesno'},function(e){
        if (e.btn === lang("Yes")) {
            $.post("index.php?fuse=support&action=addemailasspam", {
                ids:[ticketview.ticketid]
            },
            function(){
                window.location = "index.php?fuse=support&controller=ticket&view=viewtickets";
            }
            );
        }
    });
};


ticketview.ticketAdditionalNotification = function()
{

    if(!ticketview.subscribe_value){

        $.ajax({
            url: 'index.php?fuse=support&action=subscribeToTicket',
            data: {
                ticketid:ticketview.ticketid
            },
            success: function(responseObj) {
                json = ce.parseResponse(responseObj);
                ticketview.subscribe_value = 1;
                $('#btnSubscribe').text(lang("Unsubscribe"));
                ce.msg(lang('You have subscribed to this ticket'));
                clientexec.update_ticket_filters();
            //clientexec.pluginMgr.process("ticketfilters");
            }
        });

    }else{

        $.ajax({
            url: 'index.php?fuse=support&action=unSubscribeToTicket',
            data: {
                additionalNotificationID:ticketview.subscribe_value
            },
            success: function(responseObj) {
                json = ce.parseResponse(responseObj);
                ticketview.subscribe_value = 0;
                $('#btnSubscribe').text(lang("Subscribe"));
                ce.msg(lang('You have unsubscribed from this ticket'));
                clientexec.update_ticket_filters();
            //clientexec.pluginMgr.process("ticketfilters");
            }
        });

    }
};

/**
* - Encodes HTML/XML tags into entities
* - Converts EOL into <br>
*/
ticketview.formatMessage = function(msg) {
    msg = ce.htmlspecialchars(msg);
    msg = nl2br(msg);
    return msg;
}

/**
 * Updates the custom fields
 * @return void
 */
ticketview.updatecustomfields = function()
{
    $('#ticketCustomFieldsForm').parsley( 'validate' );
    $.post('index.php?fuse=support&controller=ticket&action=savecustomfields',{
        ticketId: ticketview.ticketid,
        customfields: $('#ticketCustomFieldsForm').serializeArray()
    },function(t) {
        data = ce.parseResponse(t);
    });
};

ticketview.loadCustomFields = function() {
    $('#ticketCustomFieldsForm').empty();

    $.get('index.php?fuse=support&controller=ticket&action=getticketcustomfields',
        {
            ticketType: ticketview.ticket_type,
            ticketId: ticketview.ticketid
        },function(data){
            data = ce.parseResponse(data);
            customFields.load(data.fields,function(data) {
                $('#ticketCustomFieldsForm').append(data);
            }, function(){
                clientexec.postpageload('.ticket-active-tab');
            });
            if (data.fields.length > 0) {
                //check to see if all fields are disabled... if so remove update btn
                if (customFields.getAllFieldsDisabled()){
                    $('#ticketCustomFieldsSubmit').hide();
                } else {
                    $('#ticketCustomFieldsSubmit').show();
                    $('#ticketCustomFieldsSubmit').unbind('click');
                    $('#ticketCustomFieldsSubmit').bind('click',ticketview.updatecustomfields);
                }
                $('#tickettab_customfields_tab').show();
                $.each(data.fields, function(key, value) {
                    // ignore yes/no and dropdowns.  They always have a value, so it'll always show this if there's a dropdown or yes/no.
                    if (value.value && value.fieldtype != 1 && value.fieldtype != 9) {
                        $('#tickettab_customfields_tab sup').css('visibility', 'visible');
                        return false;
                    }
                });
            } else {
                $('#tickettab_customfields_tab').hide();
            }
    });
};

ticketview.loadPackageDetails = function() {
    $.get('index.php?fuse=support&controller=ticket&action=getticketpackagedetails', {
        ticketId: ticketview.ticketid
    }, function(res) {
        var data = ce.parseResponse(res).data;
        if (!data.length) {
            $('#packagetab').hide();
        } else {
            $('#ticket-package-group').text(data[0].group_name);
            $('#ticket-package-product').text(data[0].product_id + ' - ' + data[0].product_name);
            $('#ticket-package-status').text(data[0].status_name);
            $('#ticket-package-customfields').nextAll().empty();

            $.each(data[0].custom_fields, function(index, value) {
                $('#ticket-package-customfields').after($('<tr><td width="180"><b>' +
                ce.htmlspecialchars(value.name) +
                ':</b></td><td align="left">' +
                ce.htmlspecialchars(value.value) +
                '</td></tr>'));
            });

            $('#packagetab').show();
        }
    });
};

/**
 * Adds new messages based on pulses returns
 * @param obj log_data data returned for this log
 * @param bool add_log do we want to add the log
 */
ticketview.add_new_log_message = function(log_data, add_log) {

    var new_log = $("#message_template").clone().attr("id", "div_log_"+log_data.logid).removeClass("message_template");
    new_log.children("a").attr("name", "message-"+log_data.logid);
    new_log.children("#log_id").val(log_data.logid);

    if (log_data.isAdmin || log_data.authorId == '0') {
        new_log.find(".msgEntryHeader-ownerdiv").prepend(log_data.authorName);
    } else {
        new_log.find(".msgEntryHeader-ownerdiv").prepend("<a href='index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID="+log_data.authorId+"'>"+log_data.authorName+"</a>");
    }

    if (!log_data.viaEmail) {
        new_log.find(".viaEmail").remove();
    }

    if (!log_data.isAdmin) new_log.find(".msgEntryHeader-ownerdiv").addClass("submitted-by-user");

    new_log.find(".msgEntryHeader-actiondiv")
        .addClass(log_data.action_performed_class)
        .prepend(log_data.action_performed_label);

    new_log.find(".elapsed-time").attr({
        'href' : '#message-'+log_data.logid
    }).append(log_data.createdAt);

    if (!log_data.isAdmin && log_data.authorId != '0') {
        new_log.find(".ticket-user-email").text('<'+log_data.authorEmail+'>');
    }

    new_log.find(".msgEntry-left-profile-icon").attr("src", log_data.avatar_url);

    new_log.find(".editLinks_log").attr("id","editLinks_log_"+log_data.logid);

    new_log.find(".lu_edit").click(function(){
        ShowEditBox("log_"+log_data.logid);
        return false;
    });
    new_log.find(".lu_delete").click(function(){
        DeleteLog("log_"+log_data.logid,"support","DeleteTicketLog_Async");
        return false;
    });

    new_message = log_data.message;
    attachment_message = "";

    $.each(log_data.attachments,function(i,obj) {
        attachment_message += "<br/><a target='_blank' href='index.php?fuse=support&controller=ticket&view=getattachment&file_id="+obj.id+"' ><img title='"+obj.filename+"' class='attatchment-image' src='index.php?fuse=support&controller=ticket&view=getattachment&file_id="+obj.id+"' /></a><br/>";
    });

    // before v5 was released logs stored htmlentities, which need to be interpreted
    // so we skip the htmlspecialchars() filter
    if (log_data.createdAt_unix < 1396587600) {
      new_message = attachment_message + nl2br(new_message);
    } else {
      new_message = attachment_message + ticketview.formatMessage(new_message);
    }

    new_message = ce.linkify(new_message);

    // Messages are allowed to have br, div and p. So we only want to escape ampersands (e.g. for urls to show appropriately).
    new_log.find(".log_message").attr("id", "log_"+log_data.logid).html(new_message);

    if (!add_log) {
        if (ticketview.is_reply_on_top) {
            $(".ticket-active-tab").prepend(new_log.css("display", "block"));
        } else {
            $(".ticket-active-tab").append(new_log.css("display", "block"));
        }

     } else {
        new_log.css({
            boxShadow: "0 0 15px #FAE193"
        }).find(".msgEntryHeader").css({
            "border-top" : "none",
            "margin-top" : "1px"
        });

        if (ticketview.is_reply_on_top) {
            $(".ticket-active-tab").prepend(new_log);
            new_log.fadeIn(1000);
        } else {
            $(".ticket-active-tab").append(new_log);
            new_log.slideDown(1000);
        }

         setTimeout(function(){
            new_log.css({"display" : "block", "boxShadow" : "none", "border": "1px solid #FAE193"})
         }, 1500);

        //send notification if it isn't us making the change
        if (log_data.authorId != clientexec.admin_id) {
            $.gritter.add({
                title: "Ticket update",
                text: log_data.authorName+" has updated this ticket"
            });
        }
     }
};

ticketview.add_new_metalog_message = function(log_data) {
    var entryType;
    switch (log_data.logtype) {
        case -1:
        case 1:
            entryType = 'status';
            break;
        case 2:
            entryType = 'assignee';
            break;
        case 3:
            entryType = 'tkt-type';
            break;
        case 4:
            entryType = 'package';
            break;
        case 5:
            entryType = 'priority';
            break;
        case 6:
            entryType = 'added-attachment';
            break;
        case 7:
            entryType = 'deleted-attachment';
            break;
    }
    var newMetaLog = $('#metalog-' + entryType).clone()
        .attr('id', 'metalog-' + entryType + '-' + log_data.logid)
        .addClass('metalog-data');
    newMetaLog.children('.timestamp').html(log_data.createdAt);
    newMetaLog.children('.user').html(log_data.authorName);
    newMetaLog.children('.newstate').html(log_data.newstate == 'himself'? lang('himself') : log_data.newstate);

    if ($('#toggleStateLog').prop('checked')) {
        newMetaLog.show();
    } else {
        newMetaLog.hide();
    }

    if (ticketview.is_reply_on_top) {
        $(".ticket-active-tab").prepend(newMetaLog);
    } else {
        $(".ticket-active-tab").append(newMetaLog);
    }
}

ticketview.fetchLogs = function() {

    $.ajax({
        url: "index.php?fuse=support&action=getticketlog&controller=ticketlog&id="+ticketview.ticketid,
        dataType: "json",
        success: function(response) {
            var new_count = 0;

            $.each(response.logs, function(index, value) {
                new_count ++;
                if (new_count > ticketview.current_log_count) {
                    ticketview.current_log_count ++;
                    if (value.logtype == 0) {
                        ticketview.add_new_log_message(value, true);
                    } else {
                        ticketview.add_new_metalog_message(value);
                    }
                }
            });

            $('#attachedfileblock > div').remove();
            if (response.attachments.length > 0) {
                ticketview.hasAttachments = true;
                $.each(response.attachments, function(index, attachment) {
                    $('#attachedfileblock').append('<div style="font-size:10px !important;padding:2px;">' +
                            '<span>' +
                                '<span class="ticket-attachment">' + attachment.date_added + '</span> &nbsp;by:' + attachment.added_by_name + ' &nbsp;' + attachment.url +
                                '&nbsp;&nbsp;[<span style="color:red">&nbsp;' + attachment.delete_url + '&nbsp;</span>]&nbsp;' +
                            '</span>' +
                        '</div>');
                });
                $('#attachedfileblock').show();
            } else {
                ticketview.hasAttachments = false;
                $('#attachedfileblock').hide();
            }
        }
    }).always(function() {
        ticketview.ignoreHeartbeat = false;
        RichHTML.unMask('#view-viewticket');
    });
}

function ShowEditBox(id)
{
    // save the old content so we can use it if we hit cancel
    ticketview.editedOldMessage = document.getElementById(id).innerHTML;
    var regexp = /(\n)/gm;
    ticketview.editedOldMessage=ticketview.editedOldMessage.replace(regexp, '');
    document.getElementById(id).innerHTML = "<textarea class='ticket-msg-textbox' wrap='virtual' rows=5 id='ch"+id+"'>"+rmbr(ticketview.editedOldMessage)+"</textarea>";
    document.getElementById('editLinks_'+id).innerHTML = "<a style='font-size:11px;' href=\"javascript:SaveEditMessage('"+id+"');\">"+lang('Save')+"</a>&nbsp;&nbsp;<a style='font-size:11px;' href=\"javascript:CancelEditMessage('"+id+"');\">"+lang('Cancel')+"</a>";
    document.getElementById('ch'+id).focus();
}

function CancelEditMessage(id)
{
    document.getElementById(id).innerHTML = nl2br(ticketview.editedOldMessage);
    document.getElementById('editLinks_'+id).innerHTML = "<a style='font-size:11px;' href=\"javascript:ShowEditBox('"+id+"');\">"+lang('Edit')+"</a>&nbsp;&nbsp;<a style='font-size:11px;' href=\"javascript:DeleteLog('"+id+"');\">"+lang('Delete')+"</a>";
}

/**
 * Saves edits made to a particular log
 * @param void
 */
function SaveEditMessage(log)
{
    var message = document.getElementById('ch'+log).value;
    var ticket = ticketview.ticketid;
    var logid = log.substring(4,log.length);

    $.ajax({
        url: 'index.php?fuse=support&action=editticketlog&controller=ticketlog',
        type: 'POST',
        data: {
            log:logid,
            message:message,
            id:ticket
        },
        success: function(t){
            json = ce.parseResponse(t);
            document.getElementById('log_'+logid).innerHTML = nl2br(message);
            document.getElementById('editLinks_log_'+logid).innerHTML = "<a style='font-size:11px;' href=\"javascript:ShowEditBox('log_"+logid+"');\">"+lang('Edit')+"</a>&nbsp;&nbsp;<a style='font-size:11px;' href=\"javascript:DeleteLog('log_"+logid+"');\">"+lang('Delete')+"</a>";
        }
    });


}

/**
 * Delete a log entry for this ticket
 * @param void
 */
function DeleteLog(log)
{
  var logid = log.substring(4,log.length);
  var text='';
  var deletetag=0;
  //popup a warning that the entire ticket will be deleted if they delete the first log.
  //process should be to allow deleting ONLY if there are more if it is the only log  then state that the ticket will be deleted
  if(ticketview.getfirstTicketID==logid)
    {
      text="Deleting this comment will delete the entire ticket. Are you sure you want to delete this ticket?";
      deletetag=1;
    }
  else
    {
      deletetag=0;
      text="Are you sure you want to delete this reply?";
    }

    RichHTML.msgBox(lang(text),{type:'yesno'},function(e){
        if (e.btn === lang("Yes")) {
            if(deletetag==0){
                $.ajax({

                        url: 'index.php?fuse=support&controller=ticketlog&action=delete',
                        data: {
                            log:logid
                        },
                        success: function(t){
                            $('#div_log_'+logid).remove();
                            //we need to calculate total again
                            ticketview.current_log_count = $('.ticket-active-tab .msgEntry').length;
                        }
                 });
            }
            else{
                $.ajax({

                        url: 'index.php?fuse=support&controller=ticket&action=delete',
                        data: {
                            ids:ticketview.getTicketID
                        },
                        success: function(t){
                            window.location.replace('index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter=open');
                        }
                 });
            }

        }
    });
}

function ToggleSecondOptionsPanel(id)
{
    //0 normal reply , 1 internal commment
    $('.ticket-reply-option li').removeClass('active');
    if (id == 0){
        $('#secondOptionsPanel').show();
        $('.ticket-reply-option li.reply-option-reply').addClass('active');
        document.getElementById('private').value = 0;
    } else if (id == 1) {
        $('.ticket-reply-option li.reply-option-internal').addClass('active');
        $('#secondOptionsPanel').hide();
        document.getElementById('private').value = 1;
    }
}


function AddCannedResponse(id)
{
    //obtain response text via ajax - with signature attached
    RichHTML.mask();
    $.ajax({
        url: 'index.php?fuse=support&controller=cannedresponse&action=getcannedresponse&id=' + ticketview.getTicketID,
        success: function(t) {
            RichHTML.unMask();
            json = ce.parseResponse(t);
            // textarea contents should never be escaped (I think)
            document.getElementById('message').value = json.response + ticketview.originalMessage;
        },
        data: {
            responseid : id
        }
    });
}


function someFilesFieldsEmpty(element)
{
    var emptyEvaluation = true;

    var newElement = document.getElementById(element);

    var newArrayCollection = newElement.getElementsByTagName("input");

    for(var i=0; i < newArrayCollection.length; i++){

        if(newArrayCollection[i].value != ''){
            emptyEvaluation = false;
        }
    }

    return emptyEvaluation;
}

var globalNumberFieldCount = 1;

//used in ticketview.js
function removefilefield(id){
    document.getElementById(id).parentNode.parentNode.removeChild(document.getElementById(id).parentNode);
}

ticketview.getassignees = function ()
{
    $.ajax({
        url: "index.php?fuse=support&controller=ticket&action=assignlistticket&id="+ticketview.ticketid,
        dataType: "json",
        success: function(response) {
            var li,className;
            var active_id = 0;
            $('.ticket-top-bar-assignedto ul').empty();
            $.each(response.options,function(index, obj){

                className = "";
                if (obj.active) {
                    $('.ticket-top-bar-assignedto .dropdown-toggle').text(obj.assigneeLabel);
                    active_id = '.ticket-top-bar-assignedto li[data-id="'+obj.assigneeId+'"]';
                }

                li = $('<li data-id="'+obj.assigneeId+'">');
                if (obj.department) {
                    $(li).html("<a href='javascript:void(0);' data-id='"+obj.assigneeId+"' onclick='ticketview.changeassignee(this);'><strong>"+lang("Dept.")+" "+obj.assigneeLabel+"</strong></a>");
                } else {

                    $(li).html("<a href='javascript:void(0);' data-id='"+obj.assigneeId+"' onclick='ticketview.changeassignee(this);'><img src='"+ce.getAvatarUrl(obj.email, 20, obj.assigneeLabel)+"'> <span style='position:relative;top:3px;'>"+obj.assigneeLabel+"</span></a>");
                }
                $('.ticket-top-bar-assignedto ul').append(li);
            });

            if (active_id!=0) $(active_id).addClass('active');
            else $('.ticket-top-bar-assignedto .dropdown-toggle').text(lang('Unassigned'));

        }
    })
}

ticketview.getpackages = function ()
{
    $.ajax({
        url: "index.php?fuse=support&controller=ticket&action=getpackages&id="+ticketview.ticketid,
        dataType: "json",
        success: function(response) {
            var li,className;
            var active_id = 0;
            var active_package_id;
            $('.ticket-top-bar-assignedtopackage ul').empty();

            // if there's no packages, just remove the ul.
            if ( response.packages.length == 0 ) {
                $('.ticket-top-bar-assignedtopackage ul').remove();
            }

            $.each(response.packages,function(index, obj){

                className = "";
                if (obj.active) {
                    $('.ticket-top-bar-assignedtopackage .dropdown-toggle').text(obj.name);
                    active_id = '.ticket-top-bar-assignedtopackage li[data-id="'+obj.id+'"]';
                    active_package_id = obj.id;
                }

                li = $('<li data-id="'+obj.id+'">');
                $(li).html("<a href='javascript:void(0);' data-id='"+obj.id+"' onclick='ticketview.changeassignedpackage(this);'><strong>"+ce.htmlspecialchars(obj.name)+"</strong></a>");
                $('.ticket-top-bar-assignedtopackage ul').append(li);
            });

            if (active_id!=0) {
                $(active_id).addClass('active');
                $('#view-package-link').attr('href', 'index.php?fuse=clients&controller=userprofile&view=profileproduct&id=' + active_package_id);
                $('#view-package-link').show();
            }
            else $('.ticket-top-bar-assignedtopackage .dropdown-toggle').text(lang('No Package'));

        }
    })
}

ticketview.add_other_viewers = function() {

    var usersViewingSamePage="";

    //let's make sure we have me
    if (typeof(clientexec.whoisonline.me) == "undefined") {
        return;
    }

    var myself = clientexec.whoisonline.me;

    //let's get my target so we can match with logged in users
    my_target = myself.lastview['t'].split('|');

    $.each(clientexec.whoisonline.onlineusers,function(index,objValue){

        //update if someone is viewing our view
        if ( (objValue.id != clientexec.admin_id) && (gFuse==objValue.lastview['m']) && ("getticket"==objValue.lastview['v']) ) {
            if (objValue.lastview['t']==="") {
                return;
            } else {
                m = objValue.lastview['t'].split('|');
                if (my_target[1] == m[1]) {
                    usersViewingSamePage += objValue.name + ", ";
                }
            }
        }

    });

    //let's show who is viewing this page with us
    if (usersViewingSamePage!=="") {
        usersViewingSamePage = lang("Others viewing: ") + usersViewingSamePage.substring(0, usersViewingSamePage.length-2);
        $('#ticket-top-bar-alsoviewing').html(usersViewingSamePage);
        $('#ticket-top-bar-alsoviewing').show();
        $('.ticket-top-bar').addClass('with_also_viewing');
        $('#content-header-title').addClass('with_also_viewing');
    } else {
        $('#ticket-top-bar-alsoviewing').hide();
        $('.ticket-top-bar').removeClass('with_also_viewing');
        $('#content-header-title').removeClass('with_also_viewing');
    }

}

/**
 * heartbeat of adding messages
 * @return void
 */
ticketview.startheartbeat = function () {

    if ($("#message_template").length > 0) {

        if (heartbeat.check('index.php?fuse=support&action=logcountticketlog&controller=ticketlog')) {
            heartbeat.remove('index.php?fuse=support&action=logcountticketlog&controller=ticketlog');
        }

        heartbeat.add({
            name: 'index.php?fuse=support&action=logcountticketlog&controller=ticketlog',
            delay: 5,
            pulse: 10,
            args : {
                id : ticketview.ticketid
            },
            callback: function(response) {
                //we are also going to check who is whatching this view with us
                ticketview.add_other_viewers();

                if (response.ticket_id!==ticketview.ticketid) return;
                if (!ticketview.ignoreHeartbeat && response.count > ticketview.current_log_count) {
                    ticketview.fetchLogs();
                }
            }
        });

    }
};

ticketview.hideHelp = function() {
  $.post(
    'index.php?fuse=clients&controller=customfields&action=hidehelp',
    {
      helpId: 'ticketCustomFields'
    }
  );
};
