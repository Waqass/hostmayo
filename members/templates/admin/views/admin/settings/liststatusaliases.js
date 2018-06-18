
var packagealias = packagealias || {};
$(document).ready(function() {

    packagealias.columns = [
    {
        id:        'cb',
        dataIndex: 'id',
        xtype:     'checkbox',
        renderer: function(text, row, el) {
            if ( row.system == '1' ) {
                el.addClass = 'hide-checkbox';
            }
        }
    },{
        text: '',
        xtype: 'drag'
    },{
        id:        'name',
        dataIndex: 'name',
        text:      lang('Name'),
        sortable:  false,
        align:'left',
        renderer: function(text, row) {
            return "<a onclick='packagealias.aliaswindow.show({params:{id:"+row.id+", type:" + packagealias.typeId + "}});'>"+text+"</a>";
        },
        flex: 1
    },{
        text: lang("Alias To"),
        align:'center',
        dataIndex: 'aliasto',
    },{
        text: lang("System Status"),
        align:'center',
        dataIndex: 'system',
        width: 100,
        renderer: function(text,row) {
            if ( text == '1' ) {
                return lang('Yes');
            } else {
                return lang('No');
            }
        }
    },{
        id: 'id',
        dataIndex: "id",
        hidden: true
    }];

    $('#add-alias-button').click(function(){
        packagealias.aliaswindow.show({params:{id:0,type:packagealias.typeId}});
    });

    $('#delete-alias-button').click(function () {
        RichHTML.msgBox(lang('Are you sure you want to delete the selected alias(es)'),
        {
            type:"confirm"
        }, function(result) {
            if ( result.btn === lang("Yes") ) {
                $.post("index.php?fuse=admin&action=delete&controller=statusalias", { ids: packagealias.grid.getSelectedRowIds() },
                function(data){
                    jsonData = ce.parseResponse(data);
                    if ( jsonData.success == true ) {
                        packagealias.grid.reload();
                    }
                });
            }
        });
    });

    packagealias.grid = new RichHTML.grid({
        el: 'alias-list',
        url: 'index.php?fuse=admin&controller=statusalias&action=get&type=' + packagealias.typeId,
        root: 'results',
        columns: packagealias.columns
    });

    packagealias.aliaswindow = new RichHTML.window({
        url: 'index.php?fuse=admin&controller=statusalias&view=statusalias',
        actionUrl: 'index.php?fuse=admin&controller=statusalias&action=save',
        width: '300',
        height: '165',
        grid: packagealias.grid,
        showSubmit: true,
        title: lang("Add/Edit Status Alias")
    });

    packagealias.grid.render();

    $(packagealias.grid).bind({
        "drop": function (event, data) {
            $.ajax({
                url: 'index.php?fuse=admin&controller=statusalias&action=updateorder',
                dataType: 'json',
                type: 'POST',
                data: {
                    ids: packagealias.grid.getRowValues('id')
                },
                success: function(data) {}
            });
        },
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#delete-alias-button').removeAttr('disabled');
            } else {
                $('#delete-alias-button').attr('disabled','disabled');
            }
        }
    });
});