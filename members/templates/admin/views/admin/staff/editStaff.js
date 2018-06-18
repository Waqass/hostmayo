$(document).ready(function() {
    $('#deleteStaffButton').click(function(e){
        e.preventDefault();

        RichHTML.msgBox(lang("Are you sure you want to delete this account? WARNING: All support ticket information for this user will be replaced to generic Tech Support"), {
            type: 'confirm'
        },function(result) {
            if ( result.btn == lang('Yes') ) {
                RichHTML.mask();
                $.post('index.php?fuse=admin&action=deleteadminaccount&controller=staff', { deleteid: deleteId }, function(data) {
                    window.location = 'index.php?fuse=admin&controller=staff&view=adminlist';
                }).fail(function() {
                    RichHTML.unMask();
                });
            }
        });
    });

    $('#dropdown_adminstatus').change(function(e) {
        $.post('index.php?fuse=admin&action=updateadminstatus&controller=staff', { adminId: adminid, statusid: e.val }, function (data) {
            ce.parseResponse(data);
        });
    });

    $('#dropdown_adminGroup').change(function(e) {
        $.post('index.php?fuse=admin&action=updateadmingroup&controller=staff', { adminId: adminid, groupid: e.val }, function (data) {
            ce.parseResponse(data);
        });
    });
});