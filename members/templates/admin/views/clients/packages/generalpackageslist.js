var packages = packages || {};

$(document).ready(function() {
    packages.grid = new RichHTML.grid({
        el: 'generalpackages-grid',
        url: 'index.php?fuse=clients&action=getpackages&controller=packages',
        baseParams: { limit: clientexec.records_per_view, sort: 'id', dir: 'asc', filter: 'active', type: 0, groupid: packages.groupid},
        root: 'results',
        editable: true,
        columns: [{
            id:         "cb",
            dataIndex:  "id",
            xtype:      "checkbox"
        },{
            id: "id",
            dataIndex: "id",
            text: lang("ID"),
            sortable: true,
            width: 60
        },{
            id: "customer",
            text: lang("Customer"),
            dataIndex: "customer",
            sortable: true,
            renderer: function(text, row) {
                return "<a href='index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=" + row.customerid + "'>" + row.customer + "</a>";
            }

        },{
            id: "package",
            text: lang("Package"),
            dataIndex: "name",
            sortable: true,
            renderer: function(text, row) {
                return "<a href='index.php?fuse=clients&controller=userprofile&view=profileproduct&id=" + row.productid + "&frmClientID=" + row.customerid + "'>" + row.productname + "</a>";
            }

        },{
            id: "group",
            text: lang("Group"),
            dataIndex: "productgroupname",
            sortable: true

        },{
            id: "status",
            text: lang("Status"),
            dataIndex: "status",
            sortable: true,
            align: 'center',
            width: 100
        }
        ].concat(packages.config.customFields)
    });
    packages.grid.render();

    $('#generalpackages-grid-filter').change(function(){
        packages.grid.reload({params:{start:0, limit:$(this).val()}});
    });

    $('#generalpackages-grid-package-filter').change(function(){
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
        RichHTML.msgBox(lang('Are you sure you want to % these package(s)?', action), { type:"yesno" },
            function(result) {
                if ( result.btn === lang('Cancel') || result.btn === lang('No')  ) {
                    packages.grid.reload({params:{start:0}});
                    return;
                }
                // general hosting packages do not have servers, so never use plugin.
                usePlugin = 0;

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
                    packages.grid.reload({params:{start:0}});
                });
            }
        );
    });
});
