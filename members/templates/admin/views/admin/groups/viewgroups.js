var groups = {};

$(document).ready(function() {

      groups.grid = new RichHTML.grid({
        el: 'group-grid',
        url: 'index.php?fuse=admin&controller=groups&action=getcustomergrouplist',
        root: 'groups',
        baseParams: { sort: 'id', dir: 'asc'},
        columns: [{
                text: "",
                dataIndex: "description",
                xtype: "expander",
                renderer: function(text, row, el) {
                    return "<strong>"+lang("Description:")+"</strong><br/>"+row.description;
                }
            },{
                id: "group",
                dataIndex: "groupname",
                text: lang("Group"),
                align: "left",
                flex: 1
            },{
                id:         "color",
                text:       lang("Color"),
                width:      70,
                align:      'center',
                dataIndex:  "groupColorText",
                renderer: function(text, row, el) {
                    if ( row.useDefault ) {
                       return "Default";
                    } else {
                        el.addStyle = 'color:#fff;background-color: ' + row.groupColor;
                        return row.groupColor;
                    }
                }
            },{
                id: "members",
                text: lang("Members"),
                dataIndex: "groupMembers",
                align: "center",
                width: 60
            }
        ]
    });
    groups.grid.render();

    groups.window = new RichHTML.window({
        height: '325',
        width: '300',
        grid: groups.grid,
        url: 'index.php?fuse=admin&view=addcustomergroupform&controller=groups',
        actionUrl: 'index.php?fuse=admin&action=savecustomergroup&controller=groups',
        showSubmit: true,
        title: lang("Add/Edit Customer Group")
    });

    $('#addGroupButton').click(function(){
        groups.window.show();
    });

    $(document).on("click", '.deleteRoleLink', function(event){
        var role_id = $(this).attr("data-role-id");

        RichHTML.msgBox(lang('Are you sure you want to delete the selected customer groups(s)?'),
        {
            type:"yesno"
        }, function(result) {
            if (result.btn === lang("Yes")) {
                $.post("index.php?fuse=admin&action=deletecustomergroup&controller=groups", {ids: [role_id]},
                function (data) {
                    ce.parseResponse(data);
                    groups.grid.reload({params:{start:0}});
                });
            }
        });
    });
});