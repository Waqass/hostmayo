var dashboard = dashboard || {
    renderAtAGlance: function(payload) {
        // count can contain html (currency)
        var at_a_glance_template = '{{#arr}}<div class="count-block {{#yesterday}}count-block-yesterday{{/yesterday}}">{{{count}}}<span class="count-block-label">{{name}}</span>{{#yesterday}}<div class="count-yesterday"><span></span>Yesterday: {{{yesterday}}}</div>{{/yesterday}}</div>{{/arr}}';
        var output = Mustache.render(at_a_glance_template, {arr: payload});
        $('.dashboard-counts .count-blocks').html(output);

        //update style for currency
        var cur_count = $('.dashboard-counts .count-block > .ataglance-currency-item').length;
        if (cur_count > 2) {
            $('.dashboard-counts .count-block > .ataglance-currency-item').parent().addClass('count-block-smaller');
        } else if (cur_count > 1) {
            $('.dashboard-counts .count-block > .ataglance-currency-item').parent().addClass('count-block-small');
        }

        //update style for yesterday currency
        var cur_count = $('.dashboard-counts .count-yesterday > .ataglance-currency-item').length;
        if (cur_count > 2) {
            $('.dashboard-counts .count-yesterday > .ataglance-currency-item').parent().addClass('count-yesterday-smaller');
        } else if (cur_count > 1) {
            $('.dashboard-counts .count-yesterday > .ataglance-currency-item').parent().addClass('count-yesterday-small');
        }



    },
    orders: {
        renderPendingOrders: function(payload) {
            if ( payload.length > 0 ) {
                var orders_template = '{{#arr}}<tr><td>{{{dateActivated}}}</td><td style="text-align: center"><a href="index.php?fuse=clients&controller=userprofile&view=profileproduct&groupinfo&id={{{pendingpackageid}}}&frmClientID={{{pendingpackageuserid}}}">{{{pendingpackagereference}}}</a></td><td style="text-align: center"><a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID={{{pendingpackageuserid}}}">{{{pendingpackagecustomername}}}</a></td><td style="text-align: center">{{{packagepaidstatus}}}</tr>{{/arr}}';
                var output = Mustache.render(orders_template, {arr: payload});
            } else {
                var output = "<tr><td colspan='5'><center>" + lang('No Pending Orders') + '</center></td></tr>';
            }
            $('.orders-table tbody').html(output);
        },
        getPendingOrders: function() {
            if ( $('.orders-table').length > 0 ) {
                $.ajax({
                    url: 'index.php?fuse=clients&action=getpendingorderslist',
                    dataType: 'json',
                    success: function(response) {
                        response = ce.parseResponse(response);
                        dashboard.orders.renderPendingOrders(response.data);
                    }
                });
            }
        }
    },
    tickets: {
        renderFavoriteTickets: function(payload) {

            if ( payload.length > 0 ) {
                var ticket_template = '{{#arr}}<tr><td><a href="index.php?fuse=support&view=viewtickets&controller=ticket&id={{{id}}}">#{{{id}}} - {{{subject}}}</a></td><td>{{{submittedby}}}</td><td>{{{assignedtofull}}}</td><td>{{{timeelapsed}}}</td><td>{{{statusname}}}</td></tr>{{/arr}}';
                var output = Mustache.render(ticket_template, {arr: payload});
            } else {
                var output = "<tr><td colspan='5'><center>" + lang('No Tickets Found') + '</center></td></tr>';
            }
            $('.favorited-tickets-table tbody').html(output);
        },
        getFavoriteTickets: function() {
            if ( dashboard.ticketfilter != '' ) {
                $.ajax({
                    url: 'index.php?fuse=support&action=gettickets&controller=tickets&dir=asc&start=0&limit=15&filter='+dashboard.ticketfilter,
                    dataType: 'json',
                    success: function(response) {
                        response = ce.parseResponse(response);
                        $('.favorite-tickets-label').text(dashboard.ticketfilter_name);
                        dashboard.tickets.renderFavoriteTickets(response.items);
                    }
                });
            }
        }
    },
    vital: {
        getTodayAtAGlance:function() {
            $.ajax({
                url: 'index.php?fuse=home&controller=index&action=getataglance',
                dataType: 'json',
                success: function(response) {
                    response = ce.parseResponse(response);
                    if (response.ataglance.length > 0) {
                        dashboard.renderAtAGlance(response.ataglance);
                    }
                }
            });

        },

        automation_status_template : "{{#arr}}<tr><td>{{{name}}}</td><td><center>{{last}}</center></td><td><center>{{next}}</center></td><td><center>{{{status}}}</center></td></tr>{{/arr}}{{^arr}}<tr><td colspan='4'><center>" + lang("There are no services enabled") + "</center></td></tr>{{/arr}}",

        getAutomationStatus: function() {
            if ( $('.tbl-need-your-attention').length > 0 ) {
                $.ajax({
                    url: 'index.php?fuse=home&controller=index&action=getautomationstatus',
                    dataType: 'json',
                    success: function(response) {
                        response = ce.parseResponse(response);
                        var output = Mustache.render(dashboard.vital.automation_status_template, {arr:response.automationstatus});
                        $('.tbl-need-your-attention tbody').html(output);
                    }
                });
            }
        }
    },
    events: {
        activeService: null,
        getUpcomingEvents: function(service) {
            if ( service == '' ) return;
            RichHTML.mask();
            $.ajax({
                url: 'index.php?fuse=home&controller=index&action=getupcomingevents',
                data: {
                    service: service
                },
                dataType: 'json',
                success: function (response) {
                    var output = response.output;
                    $('.selected-automation-name').text(response.servicename);
                    $.get('../templates/admin/views/home/index/upcomingevents.mustache', function(template) {
                        var items = {
                            headers: output.headers,
                            data: output.data,
                            extract: function() {
                                returnString = '';
                                $.each(this, function(i,o) {
                                    returnString += '<td>' + o + '</td>';
                                });
                                return returnString;
                            }
                        };
                        $('#upcoming-events-table').html(Mustache.render(template, items));
                        RichHTML.unMask();
                    });
                }
            });
        }
    }
};

$(document).ready(function(){

    $('.active-service-select').bind('click', function() {
        dashboard.events.getUpcomingEvents($(this).attr('data-value'));
    });

    //if we have cache data let's delay this call to allow other background
    //calls that are needed quicker
    if ($('.dashboard-counts[data-has-cache-data]').length > 0) {
        window.setTimeout(dashboard.vital.getTodayAtAGlance,2000);
    } else {
        dashboard.vital.getTodayAtAGlance();
    }

    dashboard.vital.getAutomationStatus();
    dashboard.tickets.getFavoriteTickets();
    dashboard.orders.getPendingOrders();

    dashboard.pos = 1;

    $('.delete-event').click(function(e){
        var self = this;
        var event_id = $(this).attr('data-event-id');
        $.post('index.php?fuse=home&controller=events&action=deleteevent',{event_id:event_id},function(response){
            response = ce.parseResponse(response);
            if (!response.error) {
                $(self).parent().parent().remove();
            }
        });
    });

    $('.btn-delete-warnings').bind('click', function(e) {
        e.preventDefault();

        $.post('index.php?fuse=home&controller=events&action=deleteerrorevents',function(response){
            response = ce.parseResponse(response);
            $('.recent-error-table tbody').empty();
            $('.recent-error-table tbody').append("<tr><td colspan='3'><center>"+lang("No errors or warnings found")+"</center></td></tr>");
        });

    });

    $('.graph-slider-btn-prev').click(function() {

        if ($('.prev[disabled]').length > 0) return;
        if (dashboard.pos == 1) return;

        $('.prev').attr('disabled','true');
        $('.next').attr('disabled','true');
        var marginLeft = parseInt($('.graph_buttons').css('marginLeft'),10);
        marginLeft = marginLeft + (4*$(".overview li").width());

        $('.graph_buttons').animate({marginLeft: marginLeft},function()
        {
            dashboard.pos -= 4;
            $('.prev').removeAttr('disabled');
            $('.next').removeAttr('disabled');
        });
    });

    $('.graph-slider-btn-next').click(function() {

        if ((dashboard.total_num_of_reports - dashboard.pos) < 4) return;
        if ($('.next[disabled]').length > 0) return;

        $('.prev').attr('disabled','true');
        $('.next').attr('disabled','true');
        var marginLeft = parseInt($('.graph_buttons').css('marginLeft'),10);
        marginLeft = marginLeft - (4*$(".overview li").width());

        $('.graph_buttons').animate({marginLeft: marginLeft},function()
        {
            dashboard.pos += 4;
            $('.next').removeAttr('disabled');
            $('.prev').removeAttr('disabled');
        });
    });

    $('.overview li').on('click',function(){
        //let's set style
        $('.overview li').removeClass('active');
        $('.overview li').addClass('inactive');
        $(this).removeClass('inactive').addClass('active');
        clientexec.populate_report($(this).attr('data-report-value'),'#myChart',{indashboard:1});
    });


    $('.count-settings').on('click',function(){
        dash_settings = new RichHTML.window({
            height: '340',
            width: '350',
            url: 'index.php?fuse=home&view=dashboardsettings',
            actionUrl: 'index.php?action=savedashboardsettings&fuse=home',
            showSubmit: true,
            title: lang('Dashboard Settings'),
            onSubmit: function(response) {
            if ( response.success === true ) {
                window.location = 'index.php?fuse=home&view=dashboard';
            }
        }
        });
        dash_settings.show();
    });

});
