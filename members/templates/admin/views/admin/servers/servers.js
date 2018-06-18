var serverList = {};

$(document).ready(function() {
    serverList.grid = new RichHTML.grid({
        el: 'servers-grid',
        url: 'index.php?fuse=admin&action=getserversgridlist&controller=servers',
        root: 'data',
        baseParams: { sort: 'name', dir: 'asc'},
        columns: [{
                text: "",
                dataIndex: "status_message",
                xtype: "expander",
                renderer: function(text, row, el) {
                    if ($.trim(row.status_message) == "") row.status_message = lang("No status message");
                    return "<strong>"+lang("Status Message:")+"</strong><br/>"+row.status_message;
                }
            },{
                id: "cb",
                dataIndex: "id",
                xtype: "checkbox"
            },{
                id: "name",
                dataIndex: "name",
                text: lang("Server Name"),
                align: "left",
                renderer: function(text, row) {
                    if ($.trim(row.status_message) != "") {
                        return  String.format("<a href='index.php?fuse=admin&view=addeditserver&controller=servers&id={1}'>{0}</a> <i style='color:red' class='icon-exclamation'></i>", row.name, row.id);
                    } else {
                        return  String.format("<a href='index.php?fuse=admin&view=addeditserver&controller=servers&id={1}'>{0}</a>", row.name, row.id);
                    }

                }
            },{
                id: "hostname",
                text: lang("Hostname"),
                dataIndex: "hostname",
            },{
                id: "sharedip",
                text: lang("Shared IP"),
                width: 150,
                dataIndex: "sharedip"
            },{
                id: "plugin",
                text: lang("Plugin"),
                dataIndex: "plugin",
                width: 100
            },{
                id: "domains_quota",
                text: lang("Quota"),
                dataIndex: "domains_quota",
                width: 150
            },{
                id: "cost",
                text: lang("Monthly Cost"),
                dataIndex: "cost",
                width: 100
            },{
                id: "provider",
                text: lang("DataCenter / Provider"),
                dataIndex: "provider",
                width: 150
            }
        ]
    });

   serverList.grid.render();

   $(serverList.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteButton').removeAttr('disabled');
            } else {
                $('#deleteButton').attr('disabled','disabled');
            }
        }
    });

    $('#deleteButton').click(function () {
        if ($(this).attr('disabled')) { return false; }
        RichHTML.msgBox(lang('Are you sure you want to delete the selected servers(s)'),
        {
            type:"confirm"
        }, function(result) {
            if (result.btn === lang("Yes")) {
                $.post("index.php?fuse=admin&action=delete&controller=servers", {ids: serverList.grid.getSelectedRowIds()},
                function (data) {
                    ce.parseResponse(data);
                    serverList.grid.reload({params:{start:0}});
                });
            }
        });
    });

});