//dispatch sets up setting panel for panel type of domaininfo
var certinfo_dispatch = function(){
    if ( packagemanager.permissions.cansave == true ) {
        // (re)assign certificate id
        if ( $('#certid').length > 0 ) {
            btn = $('<button id="reassignbutton" style="margin-left: 5px" class="rich-button">Reassign</button>');
            $('#certid').after(btn);

            $('#reassignbutton').click(function(e){
                e.preventDefault();
                 RichHTML.prompt('Please enter the certificate id:',{allowblank:true},certinfoAssignCertId);
            });
        }

        var appendee = '#status';
        if ( $('#csr').length > 0 ) {
            var appendee = '#csr';
        }

        btn = $('<button id="viewCSRDetails" style="margin-left: 5px" class="rich-button">CSR Details</button>')
        $(appendee).after(btn);

        $('#viewCSRDetails').click(function(e){
            e.preventDefault();
            viewDetailsWindow = new RichHTML.window({
                height: '200',
                width : '350',
                url   : 'index.php?fuse=clients&controller=userprofile&view=viewcsrdetails&id=' + packagemanager.package_id,
                title : lang('CSR Details')
            });
            viewDetailsWindow.show();
        });
    }
}

function certinfoAssignCertId(response){
    if ( response.btn == lang('OK') ) {

        $('#certid').removeAttr('disabled');
        $('#certid').val(response.elements.value);
        $('#btnUpdateProduct').click();

    }
}