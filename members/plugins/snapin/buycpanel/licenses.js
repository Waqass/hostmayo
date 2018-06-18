buycpanel = {};


$(document).ready(function() {
    buycpanel.grid = new RichHTML.grid({
        el: 'buycpanel-grid',
        url: 'index.php?fuse=admin&action=doplugin&controller=plugin&type=snapin&plugin=buycpanel&pluginaction=list',
        baseParams: { filter: 'active'},
        root: 'data',
        columns: [{
            id: "cb",
            dataIndex: "id",
            xtype: "checkbox"
        },{
            id: "package",
            text: lang("Package"),
            dataIndex: "package",
            flex: 1,
            renderer: function(text, row) {
                return '<a onclick="buycpanel.window.show({params:{id:'+row.id+'}});">' + text + '</a>'
            }
        },{
            id: "ip",
            text: lang("IP Address"),
            dataIndex: "ip",
            width: 150
        },{
            id: "status",
            text: lang("Status"),
            dataIndex: "status",
            width: 150
        },{
            id: "next_renewal",
            text: lang("Renewal"),
            dataIndex: "next_renewal",
            width: 100
        }
        ]
    });
    buycpanel.grid.render();

    $('#buycpanel-grid-filterbystatus').change(function(){
        buycpanel.grid.reload({params:{filter:$(this).val()}});
    });

    $('#addButton').click(function() {
        buycpanel.window.show();
    });

    buycpanel.window = new RichHTML.window({
        id: 'license-window',
        escClose: false,
        grid: buycpanel.grid,
        showSubmit: true,
        actionUrl: 'index.php?fuse=admin&action=doplugin&controller=plugin&type=snapin&plugin=buycpanel&pluginaction=savelicense',
        title: lang("Manage BuycPanel License"),
        url: 'index.php?fuse=admin&view=viewsnapin&controller=snapins&plugin=buycpanel&v=license',
        width: 300,
        height: 300
    });

    $(buycpanel.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#cancelButton').removeAttr('disabled');
            } else {
                $('#cancelButton').attr('disabled','disabled');
            }
        }
    });

    $('#cancelButton').click(function() {
        if ($(this).attr('disabled')) return false;
        RichHTML.msgBox(lang('Are you sure you want to cancel the selected license(s)'),
        {
            type:"confirm"
        }, function(result) {
            if(result.btn == lang("Yes")) {
                rows = buycpanel.grid.getSelectedRowData();
                $.each(rows, function(i, data) {
                    ip = data.ip;
                    $.post("index.php?fuse=admin&controller=plugin&action=doplugin&type=snapin", {
                        plugin: 'buycpanel',
                        pluginaction : 'cancelLicense',
                        ip: ip
                    },
                    function(data){ });
                });
                buycpanel.grid.reload();
            }
        });
    });
});