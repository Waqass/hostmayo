var packages = packages || {};

$(document).ready(function() {
    packages.grid = new RichHTML.grid({
        el: 'sslpackages-grid',
        url: 'index.php?fuse=clients&action=getpackages&controller=packages',
        baseParams: { limit: clientexec.records_per_view, sort: 'id', dir: 'asc', filter: 'active', type: 2, groupid: packages.groupid},
        root: 'results',
        editable: true,
        columns: [{
                id:         "cb",
                dataIndex:  "id",
                xtype:      "checkbox"
            },{
                id: "domainname",
                text: lang("Domain Name"),
                dataIndex: "domainname",
                sortable: true,
                renderer: function(text, row) {
                    return "<a href='index.php?fuse=clients&controller=userprofile&view=profileproduct&id=" +row.id + "&frmClientID=" + row.customerid + "'>"+row.domainname+"</a><br/> Product ID: " + row.id;
                },
                flex: 1
            },{
                id: 'type',
                text: lang('Type'),
                dataIndex: 'productname',
                sortable: true,
                renderer: function(text, row) {
                    return row.productname + '<br/>(' + row.registrar + ')';
                },
                width: 200
            },{
                id: "customer",
                text: lang("Customer"),
                dataIndex: "customer",
                sortable: true,
                renderer: function(text, row, el) {
                    el.addStyle = 'vertical-align: top';
                    return "<a href='index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=" + row.customerid + "'>" + row.customer + "</a>";
                },
                width: 150
            },{
                id: "expires",
                text: lang("Expiration Date"),
                dataIndex: "expires",
                sortable: true,
                width: 125,
                renderer: function(text, row, el) {
                    el.addStyle = 'vertical-align: top';
                    return text;
                }
            },{
                id: "status",
                text: lang("Status"),
                dataIndex: "status",
                sortable: true,
                width: 100,
                renderer: function(text, row, el) {
                    el.addStyle = 'vertical-align: top';
                    return text;
                }
            }
        ].concat(packages.config.customFields)
    });
    packages.grid.render();

    $('#sslpackages-grid-filter').change(function(){
        packages.grid.reload({params:{start:0, limit:$(this).val()}});
    });

    $('#sslpackages-grid-package-filter').change(function(){
        packages.grid.reload({params:{start:0, filter:$(this).val()}});
    });

    $(packages.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('.multi-action-button').removeAttr('disabled');
            } else {
                $('#btn-send-reminder').attr('disabled','disabled');
            }
        }
    });

    $('.dropdown-menu:not(".admin-actions") li a.action-button').click(function(e) {
        if ($(this).attr('disabled')) { return false; }
        action = $(this).attr('data-action');
        RichHTML.msgBox(lang('Are you sure you want to % these certificate(s)?', action), { type:"confirm" },
            function(result) {
                if ( result.btn === lang('Cancel') || result.btn === lang('No') ) {
                    packages.grid.reload({params:{start:0}});
                    return;
                }
                usePlugin = 0;

                if ( action == 'delete' ) {
                    action = 'deletepackages';
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
