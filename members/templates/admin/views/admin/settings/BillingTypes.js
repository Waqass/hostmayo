var billingTypes = {};
billingTypes.viewingArchived = 0;

$(document).ready(function() {
    billingTypes.grid = new RichHTML.grid({
        el: 'billingTypes-grid',
        url: 'index.php?fuse=billing&action=get&controller=billingtype',
        root: 'types',
        totalProperty: 'totalcount',
        baseParams: {
            limit: clientexec.records_per_view,
            sort: 'name',
            dir: 'asc',
            archived: '0'
        },
        columns: [{
            id: "cb",
            dataIndex: "id",
            xtype: "checkbox"
        }, {
            id: "name",
            dataIndex: "name",
            align:"left",
            text: lang("Name"),
            sortable: false,
            renderer: function(text, row) {
                return "<a onclick='billingTypes.window.show({params:{id:"+row.id+"}});'>"+text+"</a>";
            }
        },{
            id: "description",
            align:"left",
            text:  lang("Invoice Description"),
            dataIndex: "description",
            sortable: false,
            flex: 1
        },{
            id: "price",
            text:  lang("Price"),
            dataIndex: "price",
            sortable: false,
            width: 150,
            align: 'center'
        },{
            id: "count",
            text:  lang("Count"),
            dataIndex: "count",
            align:"center",
            sortable: false,
            width: 50

        }]
    });
    billingTypes.grid.render();

    $('#billingTypes-grid-filter').change(function(){
        billingTypes.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $('#billingTypes-grid-filterbystatus').change(function(){
        billingTypes.grid.reload({params:{start:0,archived:$(this).val()}});
        billingTypes.viewingArchived = $(this).val();

        if ( $(this).val() == 0 ) {
            $('#archiveTypesButton span').text(lang('Archive'));
        } else {
            $('#archiveTypesButton span').text(lang('Unarchive'));
        }
    });

    $(billingTypes.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteTypesButton').removeAttr('disabled');
                $('#archiveTypesButton').removeAttr('disabled');
            } else {
                $('#deleteTypesButton').attr('disabled','disabled');
                $('#archiveTypesButton').attr('disabled','disabled');
            }
        }
    });

     $('#deleteTypesButton').click(function () {
        RichHTML.msgBox(lang('Are you sure you want to delete the selected billing types(s)'),
        {
            type:"confirm"
        }, function(result) {
            if ( result.btn === lang("Yes") ) {
                $.post("index.php?fuse=billing&action=delete&controller=billingtype", { ids: billingTypes.grid.getSelectedRowIds() },
                function(data){
                    jsonData = ce.parseResponse(data);
                    //if ( jsonData.success == true ) {
                        billingTypes.grid.reload({params:{start:0}});
                    //}
                });
            }
        });
    });

    $('#archiveTypesButton').click(function () {
        var url = '';
        if ( billingTypes.viewingArchived == 0 ) {
            url = 'index.php?fuse=billing&action=archive&controller=billingtype';
        } else {
            url = 'index.php?fuse=billing&action=unarchive&controller=billingtype';
        }
        $.post(url, { ids: billingTypes.grid.getSelectedRowIds() },
        function(data) {
            billingTypes.grid.reload({params:{start:0}});
        });
    });

    billingTypes.window = new RichHTML.window({
        height: '300',
        width: '280',
        grid: billingTypes.grid,
        url: 'index.php?fuse=billing&view=billingtype&controller=billingtype',
        actionUrl: 'index.php?action=save&controller=billingtype&fuse=billing',
        showSubmit: true,
        title: lang("Add/Edit Billing Type")
    });

    $('#addTypeButton').click(function(){
        billingTypes.window.show();
    });
});