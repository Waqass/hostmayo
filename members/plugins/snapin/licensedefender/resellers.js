licensedefender = {};
licensedefender.searchkey = '';

$(document).ready(function() {

    $('.clickable-link').click(function(e) {
        e.preventDefault();
        window.location = $(this).attr('data-url');
    });

    licensedefender.grid = new RichHTML.grid({
        el: 'resellerPanel-grid',
        url: 'index.php?fuse=admin&controller=plugin&action=doplugin&type=snapin&plugin=licensedefender&pluginaction=getResellerList',
        baseParams: { limit: clientexec.records_per_view, sort: 'license', dir: 'desc'},
        root: 'data',
        columns: [{
                id: "cb",
                dataIndex: "id",
                xtype: "checkbox"
            },{
                id: "reseller",
                text: lang("Reseller"),
                dataIndex: "username",
                renderer: function(text, row) {
                    return "<a style='cursor:pointer' id='reseller_"+row.id+"' title='Edit reseller: "+row.username+"' onclick='licensedefender.editReseller("+row.id+")'>"+row.username+"</a>";
                }
            },{
                id: "profile",
                text: lang("Profile"),
                dataIndex: "userurl",
                align: 'center',
                width: 150,
                renderer: function(text, row) {
                    if ( row.userurl == "") {
                        return "<span style='color:red;'>No record</font>";
                    } else {
                        return "<a href='index.php?fuse=clients&controller=userprofile&view=profileproducts&"+row.userurl+"'>View User Record</a>";
                    }
                }
            },{
                id: "total",
                text: lang("Total"),
                dataIndex: "license",
                align: 'center',
                width: 100
            },{
                id: "user",
                text: lang("Used"),
                dataIndex: "used",
                align: 'center',
                width: 150
            }
        ]
    });
    licensedefender.grid.render();
    licensedefender.window = new RichHTML.window({
        height: '300',
        grid: licensedefender.grid,
        content: '',
        actionUrl: '',
        showSubmit: true,
        title: lang("Add Reseller")
    });

    $('#addButton').click(function(){
        RichHTML.msgBox('', {
            type: 'prompt',
            content: '<input type="text" name="username" placeholder="Username" id="edit_username" autocomplete="off" /> <br />\
                    <input type="password" name="password" id="edit_password" autocomplete="off" /> <br/>\
                    <select name="group" id="edit_group">\
                        <option value="0">Reseller</option>\
                        <option value="3">Distributor</option>\
                    </select><br/>\
                    <input type="text" name="license" id="edit_license" placeholder="License Count" />\
                    <input type="hidden" name="resellerid" value="0" />',
            buttons: {
                button1: {
                    text: 'Save'
                },
                button2: {
                    text: 'Cancel',
                    type: 'cancel'
                }
            }
        },function (result) {
            if ( result.btn == 'Save' ) {
                $.post('index.php?fuse=admin&controller=plugin&action=doplugin&type=snapin&plugin=licensedefender&pluginaction=addeditReseller', {
                    iprange: '',
                    checkip: 0,
                    username: result.elements.username,
                    password: result.elements.password,
                    group: result.elements.group,
                    license: result.elements.license,
                    resellerid: result.elements.resellerid
                }, function(data) {
                    licensedefender.grid.reload();
                });
            }
        });
    });

    licensedefender.editReseller = function(id) {
        $.getJSON('index.php?fuse=admin&controller=plugin&action=doplugin&type=snapin', {
            plugin: 'licensedefender',
            pluginaction : 'getReseller',
            resellerid: id
        }, function(json) {
            RichHTML.msgBox('', {
                type: 'prompt',
                content: '<input type="text" name="username" placeholder="Username" id="edit_username" value="' + json.data.username + '" /> <br />\
                        <input type="password" name="password" id="edit_password" value="' + json.data.password + '" /> <br/>\
                        <select name="group" id="edit_group">\
                            <option value="0">Reseller</option>\
                            <option value="3">Distributor</option>\
                        </select><br/>\
                        <input type="text" name="license" id="edit_license" placeholder="License Count" value="' + json.data.license + '" />\
                        <input type="hidden" name="resellerid" value="'+ id + '" />',
                buttons: {
                    button1: {
                        text: 'Save'
                    },
                    button2: {
                        text: 'Cancel',
                        type: 'cancel'
                    }
                }
            },function (result) {
                if (result.btn == lang("Cancel")) return false;
                $.post('index.php?fuse=admin&controller=plugin&action=doplugin&type=snapin&plugin=licensedefender&pluginaction=addeditReseller', {
                    iprange: '',
                    checkip: 0,
                    username: result.elements.username,
                    password: result.elements.password,
                    group: result.elements.group,
                    license: result.elements.license,
                    resellerid: result.elements.resellerid
                }, function(data) {
                    licensedefender.grid.reload();
                });
            });
        });
    };

    licensedefender.searchReseller = function() {
        var searchkey = $('#searchReseller').val();
        licensedefender.searchkey = trim(searchkey);
        licensedefender.grid.reload({params:{start:0, searchkey:licensedefender.searchkey}});
        if (licensedefender.searchkey =="") {
             $('#searchkey').text("");
        } else {
             $('#searchkey').html("searching <span style='color:orange;'>"+licensedefender.searchkey+"</span>");
        }
    };

    $('#resellerPanel-grid-filter').change(function(){
        licensedefender.grid.reload({params:{start:0,limit:$(this).val()}});
    });

  $('#deleteButton').click(function() {
        if ($(this).attr('disabled')) return false;
        RichHTML.msgBox(lang('Are you sure you want to delete the selected resellers(s)'),
        {
            type:"confirm"
        }, function(result) {
            if(result.btn == lang("Yes")) {
                rows = licensedefender.grid.getSelectedRowIds();
                $.each(rows, function(i, id) {
                    $.post("index.php?fuse=admin&controller=plugin&action=doplugin&type=snapin", {
                        plugin: 'licensedefender',
                        pluginaction : 'deleteReseller',
                        resellerid: id
                    },
                    function(data){ });
                });
                licensedefender.grid.reload();
            }
        });
    });

    $(licensedefender.grid).bind({
        "load" : function(event,data) {
            if (data.jsonData.licensetotals) {
                $('#totalresellers_count').text(data.jsonData.licensetotals.totalresellers);
                $('#totallicenses_count').text(data.jsonData.licensetotals.allotted);
                $('#usedlicenses_count').text(data.jsonData.licensetotals.used);
            }
        },
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteButton').removeAttr('disabled');
            } else {
                $('#deleteButton').attr('disabled','disabled');
            }
        }
    });
});