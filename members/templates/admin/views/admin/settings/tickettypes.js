var tickettypes = {};
$(document).ready(function() {

    //name renderer
    tickettypes.renderName = function(text,row,el){
        var name = row.ticketTypeName;
        if(canedittickettypes){
            name = '<a data-toggle="tooltip" title="' + lang('Click to edit this ticket type') + '" onclick="tickettypes.window.show({params:{id:'+row.ticketTypeId+'}});">' + name + '</a>';
        }
        return name;
    };

    //expander renderer non delayed renderer as we get the data on initial request
    tickettypes.renderExpander = function(text,row,el){
        var expanderBody = "";
        if (row.ticketTypeSystemId>0){
            expanderBody += "<b style='color:red;'>System Type - may not be deleted.</b><br/><br/>";
        }
        if (row.ticketTypeDescription=="") row.ticketTypeDescription = lang("None");
            expanderBody += "<b>Description</b>: "+ row.ticketTypeDescription+" </b>";
            expanderBody += "<br/><b>Default Assignee</b>: " + row.ticketTypeDefaultAssignee;
            return expanderBody;
    }

    tickettypes.grid = new RichHTML.grid({
        el:'tickettypes-grid',
        width: "100%",
        url: 'index.php?fuse=support&action=gettickettypes&controller=tickettype',
        root: 'groups',
        totalProperty : 'totalcount',
        baseParams: {
            sessionHash: gHash,
            includesystem:0,
            limit: 150,
            filter: ""
        },
        columns: [{
            id: "id",
            dataIndex: "id",
            xtype: "checkbox",
            renderer: function(text, row, el) {
                if ( row.canDeleteTicketType === 0 ) {
                    el.addClass = 'hide-checkbox';
                }
            }
        },{
            text: "",
            xtype: "expander",
            escapeHTML: true,
            renderer:tickettypes.renderExpander
        },{
            text:     	"",
            xtype:      "drag"
        },{
            id: 'ticketTypeId',
            dataIndex : "ticketTypeId",
            hidden:true
        },{
            text: lang("Name"),
            align: 'left',
            renderer: tickettypes.renderName,
            dataIndex : "ticketTypeName",
            flex: 1
        },{
            text: lang("Enabled"),
            width: 80,
            align: 'center',
            dataIndex : "ticketTypeEnabled"
        },{
            text: lang("Tickets"),
            width: 80,
            align: 'center',
            dataIndex : "numTickets"
        }]
    });

    tickettypes.grid.render();

    //lets add handler to checkbox for including system ticket types
    $('#includesystemtypes').click(function(){
        var includesystem = 0;
        if ($(this).is (':checked')) {
            includesystem = 1;
        }
        tickettypes.grid.reload({params:{"start":0,"includesystem":includesystem}});
    });

    $(tickettypes.grid).bind({
        "drop": function (event, data) {
            $.ajax({
                url: 'index.php?fuse=support&controller=tickettype&action=updateorder',
                dataType: 'json',
                type: 'POST',
                data: {sessionHash: gHash,ids:tickettypes.grid.getRowValues('ticketTypeId')},
                success: function(data) {}
            });
        },
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteTypeButton').removeAttr('disabled');
            } else {
                $('#deleteTypeButton').attr('disabled','disabled');
            }
        }
    });

    if ( canedittickettypes == true ) {
        $('#addButtonTd').show();
    }

    tickettypes.window = new RichHTML.window({
        height: '500',
        width: '280',
        grid: tickettypes.grid,
        url: 'index.php?fuse=support&view=tickettype&controller=tickettype',
        actionUrl: 'index.php?action=update&controller=tickettype&fuse=support',
        showSubmit: true,
        title: lang("Manage Ticket Type")
    });

    $('#addTypeButton').click(function(){
        tickettypes.window.show();
    });

    $('#deleteTypeButton').click(function () {
        RichHTML.msgBox(lang('Are you sure you want to delete the selected ticket types(s)?'),
        {
            type:"confirm"
        }, function(result) {
            if ( result.btn == lang("Yes") ) {
                $.post("index.php?fuse=support&controller=tickettype&action=delete", { ids: tickettypes.grid.getSelectedRowIds() },
                function(){
                    tickettypes.grid.reload({params:{start:0}});
                });
            }
        });
    });
});