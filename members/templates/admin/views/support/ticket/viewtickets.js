var ticketList = {
    intervalid:               0,
    intervalRate:             0,
    inputTicketSearch:        $('#inputTicketSearch'),
    inputIncludeClosed:       $('#inputIncludeClosed'),
    creationStart:            null,
    creationEnd:              null,
    nameSearch:               null,
    assignee:                 null,
    selectSearchType:         $('#selectSearchType'),
    spanTicketListTableLabel: $('#spanTicketListTableLabel'),
    spanOpenedToday:          $('#spanOpenedToday'),
    spanClosedToday:          $('#spanClosedToday'),
    spanRepliedToday:         $('#spanRepliedToday'),
    spanRatedToday:           $('#spanRatedToday'),
    grid:                     {},
    nameSearchObj:            {},

    SearchTroubleTickets: function() {
        var state = {
            search_filter_query: trim(ticketList.inputTicketSearch.val()),
            search_filter_searchType: ticketList.selectSearchType.val()
        };
        var url;

        var includeClosed = ticketList.inputIncludeClosed.prop('checked');

        if (state.search_filter_query == '') {
            state.search_filter = "open";
            state.search_filter_name =  (includeClosed ? lang('All') : lang('Open')) + ' ' +lang('Tickets');
            ticketList.spanTicketListTableLabel.html(state.search_filter_name);
            $('.favorite-filter').show();
            $('.favorite-filter').attr('data-filter-id',state.search_filter);
            url = "index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter="+state.search_filter;
        } else {
            if (includeClosed) { state.search_filter = 'searchclosed'; } else { state.search_filter = 'searchopen'; }
            state.search_filter_name = lang('Viewing') + ' ' + (includeClosed ? lang('all') : lang('open')) + ' '+lang('tickets with keyword(s)') + ' ' + '<span class="bold">' + state.search_filter_query+ '</span>';
            ticketList.spanTicketListTableLabel.html(state.search_filter_name);
            $('.favorite-filter').hide();
            $('.favorite-filter').attr('data-filter-id',0);
            url = "index.php?fuse=support&view=viewtickets&controller=ticket&filter="+state.search_filter+"&query="+state.search_filter_query+"&searchtype="+state.search_filter_searchType;
        }

        if (ticketList.creationStart) {
            state.search_filter_creationStart = ticketList.creationStart;
            url += "&creationStart="+encodeURIComponent(ticketList.creationStart);
        }
        if (ticketList.creationEnd) {
            state.search_filter_creationEnd = ticketList.creationEnd;
            url += "&creationEnd="+encodeURIComponent(ticketList.creationEnd);
        }

        if (ticketList.creationStart && ticketList.creationEnd) {
            state.search_filter_name += ' ' + lang('from % to %', ticketList.creationStart, ticketList.creationEnd);
        } else if (ticketList.creationStart) {
            state.search_filter_name += ' ' + lang('from %', ticketList.creationStart);
        } else if (ticketList.creationEnd) {
            state.search_filter_name += ' ' + lang('to %', ticketList.creationEnd);
        }

        if (ticketList.nameSearch) {
            state.search_filter_name += ' ' + lang('for customer %', ticketList.nameSearch);
            state.search_filter_nameSearch = ticketList.nameSearch;
            url += "&nameSearch="+encodeURIComponent(ticketList.nameSearch);
        }

        if (ticketList.assignee) {
            var assigneeLabel = $('select[name=ticket-assignee] option[value=' + ticketList.assignee + ']').text();
            state.search_filter_name += ' ' + lang('for assignee %', assigneeLabel);
            state.search_filter_assignee = ticketList.assignee;
            url += "&assignee="+encodeURIComponent(ticketList.assignee);
        }

        History.pushState(state, clientexec.original_title, url);
    },

    reload_ticket_grid: function(filter_id, filter_name, customerid, set_new_history) {

        var iscustom = false;
        if (filter_id.substr(0,7) == "custom_") {
            iscustom = true;
        }

        if (typeof(set_new_history) == "undefined") {
            set_new_history = false;
        }

        ticketList.grid.baseParams.customerid = 0;
        ticketList.searchfilter = filter_id;
        ticketList.searchfiltername = filter_name;
        ticketList.searchcustomerid = customerid;
        ticketList.searchquery = "";

        //force ticket refresh and to view list
        ticketview.ticketid = null;
        $('.all-tickets-view').show();
        $('.active-ticket-view').hide();

        ticketList.PopulateTicketFilters(filter_name, iscustom, false, set_new_history)
        ticketList.grid.reload({
             params:{
                 start:      0,
                 filter:     filter_id,
                 limit: $('#ticketsgrid-filter').val()
             }
        });

        $('#tktAdvancedSearch > a').show();
        $('#tktAdvancedSearch > div').hide();
    },

    PopulateTicketFilters: function(name, custom, render, updatehistory) {

        var history_url = "";
        var searchfilter = "";
        var customerid = 0;
        ticketList.grid.baseParams.start = 0;

        if (typeof(render)==="undefined") render=false;
        if (typeof(updatehistory)==="undefined") updatehistory=false;

        //check to see if we are supposed to be filtering by customerid
        if (typeof(ticketList.searchcustomerid) != "undefined") {
            ticketList.grid.baseParams.customerid = ticketList.searchcustomerid;
            customerid = ticketList.searchcustomerid;
        } else {
            ticketList.grid.baseParams.customerid = 0;
        }

        if ( typeof ticketList.viewingFromProfile === 'undefined' ) {
            history_url = "index.php?fuse=support&view=viewtickets&controller=ticket";
            searchfilter = "open";
        } else {
            history_url = "index.php?fuse=clients&controller=userprofile&view=profiletickets";
        }

        if (ticketList.searchfilter==="") {
            ticketList.grid.baseParams.filter = "open";
        } else if (ticketList.searchquery ==="") {
            //lets clear out search query so refreshes will not search it
            ticketList.inputTicketSearch.val('');

            ticketList.grid.baseParams.filter = ticketList.searchfilter;
            if (customerid == 0) {
                if ( typeof ticketList.viewingFromProfile === 'undefined' ) {
                    history_url = "index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter="+ticketList.searchfilter;
                    searchfilter = ticketList.searchtype;
                } else {
                   history_url = "index.php?fuse=clients&controller=userprofile&view=profiletickets";
                }
            } else {
                if ( typeof ticketList.viewingFromProfile === 'undefined' ) {
                    history_url = "index.php?fuse=support&view=viewtickets&controller=ticket&filter="+ticketList.searchfilter+"&customerid="+customerid;
                    searchfilter = ticketList.searchtype;
                } else {
                    history_url = "index.php?fuse=clients&controller=userprofile&view=profiletickets";
                }
            }
        } else if (ticketList.searchquery!="") {
            ticketList.grid.baseParams.query = ticketList.searchquery;
            ticketList.grid.baseParams.filter = ticketList.searchfilter;
            ticketList.grid.baseParams.searchtype = ticketList.searchtype;
            if ( typeof ticketList.viewingFromProfile === 'undefined' ) {
                history_url = "index.php?fuse=support&view=viewtickets&controller=ticket&filter="+ticketList.searchfilter+"&query="+ticketList.searchquery+"&searchtype="+ticketList.searchtype;
                searchfilter = ticketList.searchtype;
            } else {
                history_url = "index.php?fuse=clients&controller=userprofile&view=profiletickets";
            }
        }

        if (ticketList.creationStart != '') {
            ticketList.grid.baseParams.creationStart = ticketList.creationStart;
            history_url += '&creationStart='+encodeURIComponent(ticketList.creationStart);
        }
        if (ticketList.creationEnd != '') {
            ticketList.grid.baseParams.creationEnd = ticketList.creationEnd;
            history_url += '&creationEnd='+encodeURIComponent(ticketList.creationEnd);
        }
        if (ticketList.nameSearch != '') {
            ticketList.grid.baseParams.nameSearch = ticketList.nameSearch;
            history_url += '&nameSearch='+encodeURIComponent(ticketList.nameSearch);
        }

        if (ticketList.assignee) {
            ticketList.grid.baseParams.assignee = ticketList.assignee;
            history_url += '&assignee='+encodeURIComponent(ticketList.assignee);
        }

        if (typeof(custom) === "undefined") custom = false;
        if(custom) {
            label = "<a href='javascript:ticketList.loadCustomFilterWindow(\""+ticketList.searchfilter+"\");' id='ticketlist-editcustom-filter' title='Click to edit custom filter'>"+name+"</a>";
        } else {
            label = name;
        }
        ticketList.searchfiltername = label;
        ticketList.searchfiltername.replace(/'/g, "\\'");
        document.getElementById('spanTicketListTableLabel').innerHTML = lang("Viewing") + "&nbsp; " + ticketList.searchfiltername;
        //show favorite
        $('.favorite-filter').show();
        $('.favorite-filter').attr('data-filter-id',ticketList.searchfilter);
        if (ticketList.searchfilter == clientexec.favorite_filter) {
            $(".favorite-filter").trigger('click');
            $(".favorite-filter").addClass("is-favorite");
        } else {
            $(".favorite-filter").removeClass("is-favorite");
            $(".favorite-filter .icon-star").removeClass("icon-star").addClass("icon-star-empty");
        }

        if (render) {
            ticketList.grid.render();
        }

        var push_data = {};
        if (ticketview.ticketid != null) {
            push_data.invoice_id = ticketview.ticketid;
            history_url += "&id="+ticketview.ticketid;
        } else if (searchfilter != "") {
            push_data.searchfilter = searchfilter;
        }

        //return history_url;
        if(history_url != "" && updatehistory) {
            History.replaceState(push_data, document.title, history_url);
        }

    },

    loadCustomFilterWindow: function(id) {

        $.ajax({
            url: "index.php?fuse=support&action=getticketfilters",
            dataType: 'json',
            data: {
                customId: id
            },
            success: function(json) {

                for ( var i in json.options )
                {
                    if (json.options[i].type == "customfilter" && json.options[i].id == id) {
                        $('#filtername').attr('value',json.options[i].name);
                        if (json.options[i].private!=0) $("#filterprivate").attr("checked", true);
                        else $("#filterprivate").attr("checked", false);
                    }
                }
                $('#idfilter').val(id);

                ticketList.window = new RichHTML.window({
                    height: '90',
                    el: 'ticketlist-custom-filter',
                    actionUrl: 'index.php?fuse=support&controller=ticketfilter&action=save',
                    deleteUrl: 'index.php?fuse=support&controller=ticketfilter&action=delete',
                    showSubmit: true,
                    showDelete: true,
                    onSubmit: function(data,params) {
                        json = ce.parseResponse(data);
                        if (json.error) {
                            return;
                        }
                        RichHTML.mask();
                        window.location.href= "index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter=custom_"+json.id;
                    },
                    onDelete: function(data) {
                        json = ce.parseResponse(data);
                        if (json.error) {
                            return;
                        }
                        RichHTML.mask();
                        window.location.href= "index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter=open";
                    },
                    title: lang("Ticket Filter")
                });
                ticketList.window.show();
            }
        });

    },

    renderExpander: function(value, record, el) {

        $.ajax({
            url: "index.php?action=getlastxticketmessages&fuse=support",
            dataType: 'json',
            async: false,
            data: {
                ticketid: record.id
                },
            success: function(json) {

                expanderBody = "";
                for ( var i in json.logs )
                {

                    entry = json.logs[i];
                    if (typeof(entry) != "object") continue;

                    userText = entry.name;
                    privateText = "";

                    if (entry.skip) {
                        expanderBody += "<center><span style='color:#888;font-weight:bold;'>"+entry.msg+"</span></center>";
                    } else {
                        if (entry.isprivate == "1") privateText = " <span style='float:right;color:red;font-weight:bold;'>private</span>";
                        if (entry.isadmin) userText = "<span style='color:green;'>"+entry.name+"</span>";
                        expanderBody += "<div class='ext-expanderframe'>";
                        expanderBody += "<div class='ext-expanderframe-details'>";
                        expanderBody += "<b>"+lang("By")+":</b> "+userText+"&nbsp;&nbsp;<b>"+lang("Date")+":</b> "+entry.date+privateText;
                        expanderBody += "<div style='padding-top:8px;'>"+entry.msg+"</div>";
                        expanderBody += "</div>";
                        expanderBody += "</div>";
                    }
                }

            }
        });
        return expanderBody;

    },

    // renders Subject based on record
    renderSubject: function(value, record, el) {

        var colorstyle = "",priorityclass="";

        //we want to check if we just came from a query
        //so we can pass it to ticket to highlight the word
        if (record.query != "") {
            filter = "&expand=1&query=" + record.query;
        } else {
            filter="";
        }

        if ( record.subject == '' ) {
            record.subject = 'No Subject';
        }


        var attachment = "", recordId=0, colorstyle = "", title="";
        if (record.hasAttachment) {
            attachment = "<i class='icon-paper-clip' style='padding-left:4px;'></i>";
        }

        recordId = record.id;

        if(record.isguest){
            customerName = "<span style='color:gray;'>("+trim(ce.htmlspecialchars(record.submittedby))+")</span>";
        } else {

            if(record.usergroupcolor!=""){
                //customerName = String.format("<span class='customergroupstyle'><a href='index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID="+record.userid+"' style='background:{1};text-shadow: none;'>{0}</a></span>",record.submittedby,record.usergroupcolor);
                customerName = String.format("<span class='customergroupstyle' style='border-radius: 4px;padding:1px;background:{1}'>&nbsp;&nbsp;{0}&nbsp;&nbsp;</span>",ce.htmlspecialchars(record.submittedby),record.usergroupcolor);
            }else{
                customerName = String.format("{0}",ce.htmlspecialchars(record.submittedby));
            }
        }

        // if (filter == "") {
        //     subject = "<a href='index.php?fuse=support&id="+record.id+"&view=viewtickets&controller=ticket' "+colorstyle+">"+record.subject+"</a>";
        // } else {
        //     subject = "<a href='index.php?fuse=support&id="+record.id+"&view=viewtickets&controller=ticket' "+colorstyle+">"+record.subject+"</a>";
        // }

        if (ticketList.viewing_from_customer_profile) {
            subject = "<a href='index.php?fuse=support&id="+record.id+"&view=viewtickets&controller=ticket&userprofile=1' "+colorstyle+">"+record.subject+"</a>";
        } else {
            subject = "<a href='index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter="+ticketList.searchfilter+"&id="+record.id+"' "+colorstyle+">"+record.subject+"</a>";
        }

        date = record.datesubmittedwithformat;

        if (record.priority==1){
            priorityclass= "tickets-priority-1";
        }else if(record.priority==2){
            priorityclass= "tickets-priority-2";
        } else {
            priorityclass= "tickets-priority-3";
        }

        if (record.method == 0) {
            icon = '<i title="' + record.method_tooltip + '" class="icon-list-alt"></i>';
        } else if (record.method == 1) {
            icon = '<i title="' + record.method_tooltip + '" class="icon-bolt" style="color:orangered;"></i>';
        } else if (record.method == 2) {
            icon = '<i title="' + record.method_tooltip + '" class="icon-envelope-alt"></i>';
        } else {
            icon = "<img title='" + record.method_tooltip + "' src='"+relativePath+"templates/admin/images/ticket/method-"+record.method+".png' width='10' height='10' />";
        }

        title = "<span class='"+priorityclass+"'></span><span style='color:#999;'>#"+recordId+attachment+"</span>&nbsp;&nbsp;"+ subject;
        title += "<div style='padding-top:2px;'>"+icon+" from "+customerName+" posted on "+date+"</div>";

        return title;

    },

    switch_back_to_list : function(filter_id, filter_name, customerid, push_state) {

        //let's reset any also viewing
        $('#ticket-top-bar-alsoviewing').hide();
        $('.ticket-top-bar').removeClass('with_also_viewing');
        $('#content-header-title').removeClass('with_also_viewing');

        if (ticketList.back_to_profile) {
             RichHTML.mask();
             window.location.href= "index.php?fuse=clients&controller=userprofile&view=profiletickets";

        } else {
            this.switch_new_grid(filter_id, filter_name, customerid, push_state);
        }

    },

    switch_new_grid: function(filter_id, filter_name, customerid, push_state)
    {

        this.reload_ticket_grid( filter_id, filter_name, customerid, push_state);
        $('.all-tickets-view').show();
        $('.active-ticket-view').hide();
    },

    renderAssignedTo: function(value, record, el) {

        var html = "", ratingLabel = "";
        html =  record.assignedtofull;
        // html += "<br/>" + lang("Status") + " " + record.statusname;
        if (record.statussystem == -1 && record.rate > 0) {
            switch (record.rate) {
                case "1":
                    ratingLabel = "Outstanding";
                    break;
                case "2":
                    ratingLabel = "Good";
                    break;
                case "3":
                    ratingLabel = "Ok";
                    break;
                case "4":
                    ratingLabel = "Poor";
                    break;
            }

            if (trim(record.feedback) != "") {
                ratingLabel = "<span style='top: 0px;cursor:pointer;border-bottom: 1px solid #DFDFDF;' title='"+record.feedback+"'>"+ratingLabel+"</span>";
            }
            html += " (" + ratingLabel + ")";
        }

        return html;

    }
};

$(document).ready(function() {
    $(".favorite-filter").hover(function(){
        $(".favorite-filter .icon-star-empty").removeClass("icon-star-empty").addClass("icon-star");
    }, function(){
        if (!$(".favorite-filter").hasClass("is-favorite")) {
            $(".favorite-filter .icon-star").removeClass("icon-star").addClass("icon-star-empty");
        }
    });

    $(".favorite-filter").click(function(){
        //let's make this our favorite filter
        $(".favorite-filter .icon-star-empty").removeClass("icon-star-empty").addClass("icon-star");
        $(".favorite-filter").addClass("is-favorite");

        if (clientexec.favorite_filter != $(".favorite-filter").attr('data-filter-id')) {
            clientexec.favorite_filter = $(".favorite-filter").attr('data-filter-id');
            clientexec.updateCustomField('Support-FavoriteFilter',clientexec.favorite_filter);
            clientexec.update_ticket_filters();
            ce.msg(lang("Setting filter as favorite"));
        }

    });

    $("input[type=file]").change(function(e) {
        var el = $("input[type=file]");
        var path = el.val();
        var fileExt = path.split('\.').pop();
        var validExtns = $('input[name=validExtns]').val().trim();
        if (fileExt && validExtns != '*') {
            var valid = false;
            validExtns = validExtns.split(',');
            $.each(validExtns, function(ix, val) {
                if (fileExt.toLowerCase() == val.trim().toLowerCase()) {
                    valid = true;
                    return false;
                }
            });
            if (!valid) {
                // gotta unselect the file, as explained here:
                // http://stackoverflow.com/questions/1043957/clearing-input-type-file-using-jquery/1043969#1043969
                el.val('').replaceWith(el.clone(true));

                RichHTML.error(
                    lang("This file type is not accepted. Please select a different file.")
                );
                return;
            }
        }

        var fileNameIndex = path.lastIndexOf("\\") + 1;
        var filename = path.substr(fileNameIndex);
        $('.new-attachment-files').html($('<span class="file-upload-meta"/>').html( "<i class='icon-paper-clip'></i> "+lang("To attach")+ ": " +filename ));
    });

    $(".ticket-reply textarea").on("focus",function(){
        $(".ticket-reply textarea").addClass("expanded");
    });

    ticketList.grid = new RichHTML.grid({
        el:         'ticketsgrid',
        editable:  true,
        url:        'index.php?fuse=support&action=gettickets&controller=tickets',
        baseParams: {
            limit:  clientexec.records_per_view,
            dir:    'asc'
        },
        columns: [
            {
                xtype:      "expander",
                dataIndex:  "response",
                renderOnExpand: true,
                renderer:   ticketList.renderExpander
            },
            {
                id:         "cb",
                dataIndex:  "id",
                xtype:      "checkbox"
            },{
                id:         "subject",
                text:       lang("Subject"),
                align:      "left",
                renderer:   ticketList.renderSubject,
                dataIndex:  "id",
                sortable:   true,
                flex : 1
            },
            {
                id : "userid",
                text:       lang("Created By"),
                align:      "right",
                width:      140,
                hidden: true,
                renderer:   function(value,row) {
                    return row.submittedby+"<br/>Id: "+row.userid;
                },
                dataIndex:  "userid",
                sortable:   true
            },
            {
                id:         "assignedtoname",
                text:       lang("Assigned To"),
                align:      "right",
                width:      160,
                renderer:   ticketList.renderAssignedTo,
                dataIndex:  "assignedtoname",
                sortable:   true
            },
            {
                id:         "lastReply",
                text:       lang("Last Reply"),
                align:      "center",
                width:      90,
                dataIndex:  "lastReply",
                sortable:   true
            },
            {
                id:         "elapsed",
                text:       lang("Elapsed"),
                align:      "center",
                width:      75,
                renderer:   function(value,row) {
                    return row.timeelapsed;
                },
                dataIndex:  "timeelapsed",
                sortable:   true,
                hidden: true
            },
            {
                id : "status",
                text : lang("Status"),
                align:      "center",
                width:      125,
                dataIndex:  "status",
                renderer: function (vale,row)  {
                    return row.statusname;
                },
                sortable:   true,
            },{
                id : "messagetype",
                text : lang("Type"),
                align:      "center",
                width:      105,
                dataIndex:  "messagetype",
                renderer: function (vale,row)  {
                    return row.messagetypename;
                },
                sortable:   true,
                hidden: true
            }
        ]
    });

    $('#ticketsgrid-filter').change(function(){
        ticketList.grid.reload({
            params:{
                start:0,
                limit:$(this).val()
            }
        });
    });

    // **** listeners to grid
    $(ticketList.grid).bind({
        "rowselect": function(event,data) {

            if ( data.totalSelected > 1 ) {
                $('#btnMergeTickets').removeAttr('disabled');
            } else {
                $('#btnMergeTickets').attr('disabled','disabled');
            }

            if (data.totalSelected > 0) {

                $('#btnDelTicket').removeAttr('disabled');
                $('#btnMarkSpam').removeAttr('disabled');
                var open=0;
                var close=0;
                var ticketIds=new String(ticketList.grid.getSelectedRowIds());
                var ticketArray = ticketIds.split(',');
                for (var i = 0; i < ticketArray.length; i++) {
                    var NewTicketIds = ticketArray[i];
                    $.post("index.php?fuse=support&controller=ticket&action=status", {
                                    ids:NewTicketIds
                                },
                                function(data){
                                    if(data.status_system==-1){
                                      open=1;
                                      if(open==1 && close ==1) {
                                            $('#btnCloseTicket').attr('disabled','disabled');
                                            $('#btnCloseTicket').val('0');
                                        }
                                        else if(open==0 && close ==1) {
                                            $('#close').text(lang('Close Ticket(s)'));
                                            $('#btnCloseTicket').removeAttr('disabled');
                                            $('#btnCloseTicket').val('1');
                                        }
                                        else if(open==1 && close ==0) {
                                            $('#close').text('Re-Open Ticket(s)');
                                            $('#btnCloseTicket').removeAttr('disabled');
                                            $('#btnCloseTicket').val('2');
                                        }


                                  }
                                    else{
                                       close=1;
                                       if(open==1 && close ==1) {
                                            $('#btnCloseTicket').attr('disabled','disabled');
                                            $('#btnCloseTicket').val('0');
                                        }
                                        else if(open==0 && close ==1) {
                                            $('#close').text(lang('Close Ticket(s)'));
                                            $('#btnCloseTicket').removeAttr('disabled');
                                            $('#btnCloseTicket').val('1');
                                        }
                                        else if(open==1 && close ==0) {
                                            $('#close').text('Re-Open Ticket(s)');
                                            $('#btnCloseTicket').removeAttr('disabled');
                                            $('#btnCloseTicket').val('2');
                                        }

                                    }
                                });

                }

            } else {
                $('#btnDelTicket').attr('disabled','disabled');
                $('#btnMarkSpam').attr('disabled','disabled');
                $('#btnCloseTicket').attr('disabled','disabled');
            }
        }
    });

    $('body').on('click','.back-to-ticket-list', function(){
        History.go(-1);
    });

    // **** lets bind our buttons
    $('#btnDelTicket').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }
        RichHTML.msgBox(lang('Are you sure you want to delete the selected ticket(s)'),
                {type:"yesno"}, function(result) {
                    if(result.btn === lang("Yes")) {
                        ticketList.grid.disable();
                        $.post("index.php?fuse=support&controller=ticket&action=delete", {
                            ids:ticketList.grid.getSelectedRowIds()
                        },
                        function(data){
                            ticketList.grid.reload({
                                params:{
                                    start:0
                                }
                            });
                        });
                    }
                });
    });

    $('#btnMergeTickets').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }
        RichHTML.msgBox(
            lang('Are you sure you want to merge the selected tickets?'),
            {type:"yesno"},function(result) {
                if ( result.btn === lang("Yes") ) {
                    ticketList.grid.disable();
                    $.post("index.php?fuse=support&action=merge&controller=tickets", {
                        ids:ticketList.grid.getSelectedRowIds()
                    },
                    function(data) {
                        json = ce.parseResponse(data);
                        if ( json.success == true ) {
                            ticketId = json.ticketId;
                            window.location = 'index.php?fuse=support&view=viewtickets&controller=ticket&id=' + ticketId;
                        } else {
                            ticketList.grid.enable();
                            return;
                        }
                    });
                }
            }
        );
    });

     $('#btnCloseTicket').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }
        var  btnValue=$('#btnCloseTicket').val();
        var updateValue;
        var text;
        if(btnValue==1) { updateValue=-1; text=lang('Are you sure you want to close the selected ticket(s)');}
        if(btnValue==2) { updateValue=1; text=lang('Are you sure you want to re-open the selected ticket(s)');}
        RichHTML.msgBox(lang(text),
                {type:"yesno"}, function(result) {
                    if(result.btn === lang("Yes")) {
                        ticketList.grid.disable();
                        $.post("index.php?fuse=support&action=updateticketstatus&controller=ticket", {
                            newstatus:updateValue,
                            ids:ticketList.grid.getSelectedRowIds()
                        },
                        function(data){
                            ticketList.grid.reload({
                                params:{
                                    start:0
                                }
                            });
                        });
                    }
                });


    });

    // **** lets bind our buttons
    $('#btnMarkSpam').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }
        RichHTML.msgBox(
            lang('Are you sure you want to prevent tickets from being created from the selected ticket(s) email addresses?'),
            {type:"yesno"},function(result) {
                if(result.btn === lang("Yes")) {
                    ticketList.grid.disable();
                    $.post("index.php?fuse=support&action=addemailasspam", {
                        ids:ticketList.grid.getSelectedRowIds()
                    },
                    function(data){
                        ticketList.grid.reload({
                            params:{
                                start:0
                            }
                        });
                    });
                }
            }
        );
    });

    if (!ticketList.back_to_profile) {
        ticketList.PopulateTicketFilters(ticketList.defaultFilterName, ticketList.iscustom, true);
    }

    ticketList.inputTicketSearch
        .tooltip({
            placement: 'top'
        })
        .keyup(function(e) {
            if (e.which == 13) {
                ticketList.SearchTroubleTickets();
            }
        });

    ticketList.inputIncludeClosed.change(function() {
        ticketList.SearchTroubleTickets();
    });
    ticketList.selectSearchType.change(function() {
        ticketList.SearchTroubleTickets();
    });

    History.Adapter.bind(window,'statechange',function(){

        if (gView == "profiletickets") { return; }

        var State = History.getState();

        if (typeof(State.data.ticket_id) == "undefined") State.data.ticket_id = false;
        if (typeof(State.data.search_filter) == "undefined") State.data.search_filter = ticketList.searchfilter;
        if (typeof(State.data.search_filter_name) == "undefined") State.data.search_filter_name = ticketList.searchfiltername;
        if (typeof(State.data.search_customerid) == "undefined") State.data.search_customerid = ticketList.searchcustomerid;
        if (typeof(State.data.search_filter_query) == "undefined") State.data.search_filter_query = "";
        if (typeof(State.data.search_filter_creationStart) == "undefined") State.data.search_filter_creationStart = "";
        if (typeof(State.data.search_filter_creationEnd) == "undefined") State.data.search_filter_creationEnd = "";
        if (typeof(State.data.search_filter_nameSearch) == "undefined") State.data.search_filter_nameSearch = "";
        if (typeof(State.data.search_filter_assignee) == "undefined") State.data.search_filter_assignee = "";

        if (State.data.search_filter_query != "" || State.data.search_filter_creationStart != '' || State.data.search_filter_creationEnd != '' ||
                State.data.search_filter_nameSearch != '' || State.data.search_filter_assignee != '') {

            ticketList.grid.baseParams.customerid = 0;
            ticketList.grid.reload({
                params:{
                    start:      0,
                    filter:     State.data.search_filter,
                    query:      State.data.search_filter_query,
                    searchtype: State.data.search_filter_searchType,
                    creationStart: State.data.search_filter_creationStart,
                    creationEnd: State.data.search_filter_creationEnd,
                    nameSearch: State.data.search_filter_nameSearch,
                    assignee: State.data.search_filter_assignee
                }
            });
            ticketList.spanTicketListTableLabel.html(State.data.search_filter_name);
            $('.all-tickets-view').show();
            $('.active-ticket-view').hide();

        } else if (!State.data.ticket_id) {
            ticketList.grid.baseParams.creationStart = ticketList.creationStart = null;
            ticketList.grid.baseParams.creationEnd = ticketList.creationEnd = null;
            ticketList.grid.baseParams.nameSearch = ticketList.nameSearch = null;
            ticketList.grid.baseParams.assignee = ticketList.assignee = null;
            ticketList.switch_back_to_list(State.data.search_filter, State.data.search_filter_name, State.data.search_customerid, false);
        } else if (State.data.ticket_id){
            ticketList.grid.baseParams.creationStart = ticketList.creationStart = null;
            ticketList.grid.baseParams.creationEnd = ticketList.creationEnd = null;
            ticketList.grid.baseParams.nameSearch = ticketList.nameSearch = null;
            ticketList.grid.baseParams.assignee = ticketList.assignee = null;
            ticketview.actionstodoonload(State.data.ticket_id);
        }

    });
});
