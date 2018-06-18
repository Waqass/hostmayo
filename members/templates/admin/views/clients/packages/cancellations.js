var packages = packages || {};

$(document).ready(function() {
    packages.grid = new RichHTML.grid({
        el: 'cancellations-grid',
        url: 'index.php?fuse=clients&action=getpendingcancellations&controller=packages',
        baseParams: { limit: clientexec.records_per_view, sort: 'id', dir: 'asc', filter: -1 },
        root: 'results',
        editable: true,
        columns: [
            {
                dataIndex:  "id",
                xtype:      "checkbox"
            },{
                id: "id",
                dataIndex: "id",
                text: lang("ID"),
                sortable: true,
                width: 40
            },{
                id: "name",
                align: "left",
                text: lang("Package"),
                dataIndex: "name",
                sortable: true,
                renderer: function(text, row) {
                    return "<a href='index.php?fuse=clients&controller=userprofile&view=profileproduct&id=" +row.id + "&frmClientID=" + row.customerid + "'>"+row.name+"</a>";
                }
            },{
                id: "customer",
                align: "left",
                text: lang("Customer"),
                dataIndex: "customer",
                sortable: true,
                renderer: function(text, row) {
                    return "<a href='index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=" + row.customerid + "'>" + row.customer + "</a>";
                }
            },{
                id: "reason",
                text: lang("Reason"),
                dataIndex: "reason",
                sortable: false
            },{
                id: "requestdate",
                align: "center",
                text: lang("Request Date & Time"),
                dataIndex: "requestdate",
                sortable: false
            }, {
                id: "when",
                align: "center",
                text: lang("Cancel On"),
                dataIndex: "when",
                sortable: true
            }

        ].concat(packages.config.customFields)
    });
    packages.grid.render();

    $('#cancellations-grid-filter').change(function(){
        packages.grid.reload({params:{start:0, limit:$(this).val()}});
    });

    $('#cancellations-grid-status-filter').change(function(){
        packages.grid.reload({params:{start:0, filter:$(this).val()}});
    });

    $(packages.grid).bind({
        "rowselect": function(event,data) {
          if (data.totalSelected > 0) {
                $('#cancel-button').removeAttr('disabled');
            } else {
                $('#cancel-button').attr('disabled','disabled');
            }
        }
    });

    $('#cancel-button').click(function () {
        if ($(this).attr('disabled')) { return false; }
            var html = lang('Are you sure you want to cancel the selected package(s)');
            html += "<br/><div style='padding-top:8px;'><input type='checkbox' name='useplugin' id='useplugin' checked='checked'/> <span style='border-bottom: 1px solid #DFDFDF;cursor:help;' title='Cancel the selected packages then trigger the cancel action from their respective plugin.'>Use their respective plugins?</span></div>";
            RichHTML.msgBox(html, {
                type:"yesno"
            }, function(result) {
                RichHTML.mask();
                var useplugin = 0;
                if ( typeof(result.elements.useplugin) !== "undefined" ) {
                    useplugin = 1;
                }

                if(result.btn === lang("Yes")) {
                    $.post("index.php?controller=packages&action=cancelpackages&fuse=clients", {
                        ids: packages.grid.getSelectedRowIds(),
                        useplugin: useplugin,
                    },
                    function(data){
                        if ( data.error == true ) {
                            RichHTML.msgBox(data.message, { type: 'error'});
                        }
                        packages.grid.reload({params:{start:0}});
                }, 'json');
            }
            RichHTML.unMask();
        });
    });
});
