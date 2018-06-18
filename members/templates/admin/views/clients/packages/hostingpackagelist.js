var packages = packages || {};

$(document).ready(function() {
    packages.grid = new RichHTML.grid({
        el: 'hostingpackages-grid',
        url: 'index.php?fuse=clients&action=getpackages&controller=packages',
        baseParams: { limit: clientexec.records_per_view, sort: 'id', dir: 'asc', filter: 'active', type: 1, groupid: packages.groupid},
        root: 'results',
        editable: true,
        columns: [{
            id:         "cb",
            dataIndex:  "id",
            xtype:      "checkbox"
        }, {
            id: "id",
            dataIndex: "id",
            text: lang("ID"),
            sortable: true,
            align:"center",
            width: 50
        },{
            id: "customer",
            text: lang("Customer"),
            dataIndex: "customer",
            width:170,
            align: "left",
            sortable: true,
            renderer: function(text, row) {
                return "<a href='index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=" + row.customerid + "'>" + row.customer + "</a>";
            }
        },{
            id: "package",
            text: lang("Package"),
            dataIndex: "name",
            sortable: true,
            align: "left",
            flex: 1,
            renderer: function(text, row) {
                return "<a href='index.php?fuse=clients&controller=userprofile&view=profileproduct&frmClientID=" + row.customerid + "&id=" + row.productid + "'>" + row.name + "</a>";
            }
        },{
            id: "group",
            text: lang("Group"),
            dataIndex: "productgroupname",
            sortable: true,
            align: "center",
            width: 160

        },{
            id: "status",
            text: lang("Status"),
            dataIndex: "status",
            align: "center",
            sortable: true,
            width: 90
        }].concat(packages.config.customFields)
    });
    packages.grid.render();

    $('#hostingpackages-grid-filter').change(function(){
        packages.grid.reload({params:{start:0, limit:$(this).val()}});
    });

    $('#hostingpackages-grid-package-filter').change(function(){
        packages.grid.reload({params:{start:0, filter:$(this).val()}});
    });

    $(packages.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('.multi-action-button').removeAttr('disabled');
            } else {
                $('.multi-action-button').attr('disabled','disabled');
            }
        }
    });

    $('.dropdown-menu:not(".admin-actions") li a').click(function(e) {
        if ($(this).attr('disabled')) { return false; }
        action = $(this).attr('data-action');
        RichHTML.msgBox(lang('Do you want to use the respective server plugin(s) to % these package(s)?', action), { type:"confirm" },
            function(result) {
                if ( result.btn === lang('Cancel') ) {
                    packages.grid.reload({params:{start:0}});
                    return;
                } else if ( result.btn === lang('Yes') ) {
                    usePlugin = 1;
                } else {
                    usePlugin = 0;
                }

                if ( action == 'delete' ) {
                    action = 'deletepackages';
                } else if ( action == 'suspend' ) {
                    action = 'suspendpackages';
                } else if ( action == 'unsuspend') {
                    action = 'unsuspendpackages';
                } else  if ( action == 'cancel' ) {
                    action = 'cancelpackages';
                } else {
                    packages.grid.reload({params:{start:0}});
                    return;
                }

                $.post("index.php?fuse=clients&controller=packages&action=" + action + "&useplugin=" + usePlugin, { ids: packages.grid.getSelectedRowIds() }, function(data) {
                    ce.parseResponse(data);
                    packages.grid.reload({params:{start:0}});
                });
            }
        );
    });
});