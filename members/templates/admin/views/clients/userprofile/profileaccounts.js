profileAccounts = {};

$(document).ready(function() {

    profileAccounts.gridColumns = [{
        id: "cb",
        dataIndex: "id",
        xtype: "checkbox"
    }, {
        id: "email",
        dataIndex: "email",
        text: lang("Email"),
        align: "left",
        renderer: function(text, row) {
            return "<a onclick='profileAccounts.window.show({params:{id:"+row.id+"}});'>"+ce.htmlspecialchars(text)+"</a>";
        },
        flex: 1
    },{
        id: "sendnotifications",
        text: lang("Notifications?"),
        align: "center",
        dataIndex: "sendnotifications",
        width: 200
    }];

    profileAccounts.gridColumns.push({
        id: "sendnotifications",
        text: lang("Invoices?"),
        align: "center",
        dataIndex: "sendinvoice",
        width: 200
    });

    profileAccounts.gridColumns.push({
        id: "sendnotifications",
        text: lang("Support?"),
        align: "center",
        dataIndex: "sendsupport",
        width: 200
    });

    profileAccounts.grid = new RichHTML.grid({
        el: 'profileAccounts-grid',
        url: 'index.php?fuse=clients&controller=user&action=getclientalternateaccounts',
        root: 'results',
        baseParams: { sort: 'email', dir: 'asc'},
        columns: profileAccounts.gridColumns
    });

    profileAccounts.grid.render();

    $(profileAccounts.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteButton').removeAttr('disabled');
            } else {
                $('#deleteButton').attr('disabled','disabled');
            }
        }
    });

    profileAccounts.window = new RichHTML.window({
    	height: '100',
        width: '400',
        grid: profileAccounts.grid,
    	url: 'index.php?fuse=clients&view=altaccount&controller=userprofile',
    	actionUrl: 'index.php?action=saveprofileaccount&controller=userprofile&fuse=clients',
    	showSubmit: true,
    	title: lang("Manage Alternate Account"),
        onSubmit: function() {
            setTimeout(function() {
                profile.get_counts();
            },1000);
        }
    });

    $('#addAccountButton').click(function(){
        profileAccounts.window.show();
    });

    $('#deleteButton').click(function () {
        if ($(this).attr('disabled')) { return false; }
        RichHTML.msgBox(lang('Are you sure you want to delete the selected accounts(s)'),
        {
            type:"confirm"
        }, function(result) {
            if(result.btn === lang("Yes")) {
                $.post("index.php?fuse=clients&action=deleteprofileaccount&controller=userprofile", {
                    ids: profileAccounts.grid.getSelectedRowIds()
                },
                function(data){
                    profileAccounts.grid.reload({
                        params:{
                            start:0
                        }
                    });
                    setTimeout(function() {
                        profile.get_counts();
                    },1000);
                });
            }
        });
    });
});