/*
 * Ext JS Library 3.0 RC1
 * Copyright(c) 2006-2009, Ext JS, LLC.
 * licensing@extjs.com
 *
 * http://extjs.com/license
 */
var intervalid = 0;
var intervalRate = 0;
var eventlist = eventlist || {};

eventlist.renderUser = function(text,row){
    var text = text.replace(/\[color=(\w+)\](.+)/, '<font color="$1">$2</font>');
    return "<strong>"+ ce.htmlspecialchars(text) + "</strong><br/>Ip: "+row.logipaddress;
}

// eventlist.renderModifiedUser = function(text,row){
//    var text = text.replace(/\[color=(\w+)\](.+)/, '<font color="$1">$2</font>');
//    return text;
// }

eventlist.renderid = function(text,row) {
    return row.event_id;
}

eventlist.renderLogAction = function(text,row){


    var returnString = ce.htmlspecialchars(text);
    if (row.tpl == 'link') {
        returnString = returnString.replace(/\[link\]/, row.link);
    }else if(row.tpl=="clickForBody"){
        returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'email');\" href='#'>view</a>)",row.logaction,row.entryid);
    }else if(row.tpl=="clickForStaffNote"){
        returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'note');\" href='#'>view</a>)",row.logaction,row.entryid);
    }else if(row.tpl=="clickForProfileDetails"){
        returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'profile');\" href='#'>view</a>)", row.logaction,row.entryid);
    }else if(row.tpl=="clickForPaypalCallbackDetails"){
        returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'paypal');\" href='#'>view</a>)", row.logaction,row.entryid);
    }else if(row.tpl=="clickFor2CheckoutCallbackDetails"){
        returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'2checkout');\" href='#'>view</a>)", row.logaction,row.entryid);
    }else if(row.tpl=="clickForPackageDetails"){
        returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'package');\" href='#'>view</a>)", row.logaction,row.entryid);
    }

    return "<strong>"+lang("On")+" "+row.logdate+"</strong><br/>"+returnString;
};

eventlist.loadevents_grid = function()
{

    eventlist.grid = new RichHTML.grid({
        el: 'event-grid',
        root: 'data',
        totalProperty: 'totalcount',
        url: 'index.php?fuse=home&action=geteventlist&controller=events',
    baseParams: { eventtype: eventlist.eventType,
                      startdate: eventlist.startDateValue,
                      enddate: eventlist.endDateValue,
                      sort: 'logdate',
                      limit: clientexec.records_per_view},
        columns: [ {
            id: 'event_id',
            width: 45,
            text: "Id",
            dataIndex : "logdate",
            renderer: eventlist.renderid,
            align:"right",
            sortable: true
        },{
                id: 'loguser',
                text: lang("User"),
                width: 150,
                dataIndex : "loguser",
                renderer: eventlist.renderUser,
                sortable: false
            }
            /*,{
                id: 'logipaddress',
                text: lang("IP Address"),
                dataIndex : "logipaddress",
                width: 150,
                sortable: false,
                align:      "center",
            }*/
            ,{
                id: 'logaction',
                text: lang("Action"),
                dataIndex : "logaction",
                renderer: eventlist.renderLogAction,
                sortable: false,
                align:"left"
            }/*,{
                id: 'logmodifieduser',
                text: lang("Modified User"),
                dataIndex : "logmodifieduser",
                renderer: eventlist.renderModifiedUser,
                width: 150,
                sortable: false
            }*/
        ]
    });
    eventlist.grid.render();
}


$(document).ready(function(){

    $('#btnSubmitSearch').click(function () {
       eventlist.startDateValue = $("#startdate").val();
       eventlist.endDateValue = $("#enddate").val();
       eventlist.eventType = $('#eventtype').val();

       eventlist.grid.reload({params:{
           startdate: eventlist.startDateValue,
           enddate: eventlist.endDateValue,
           eventtype: eventlist.eventType}
       });
    });

     // **** start click binding
    $('#events-grid-filter').change(function(){
        eventlist.grid.reload({params:{start:0,limit:$(this).val()}});
    });
    eventlist.loadevents_grid();

});
