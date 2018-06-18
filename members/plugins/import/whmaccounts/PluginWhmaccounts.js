$(document).ready(function() {

    $('#server-select').change(function(e){
        var serverId = $(this).val();
        if ( serverId == '--' ) {
            window.location = 'index.php?fuse=admin&view=viewimportplugins&plugin=whmaccounts&controller=importexport';
        }
        window.location = 'index.php?fuse=admin&view=viewimportplugins&plugin=whmaccounts&controller=importexport&server=' + serverId;
    });

    $('#import-product').click(function(e) {
        $('#importExportForm').submit();
    });
});