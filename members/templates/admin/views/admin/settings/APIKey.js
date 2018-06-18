$(document).ready(function() {
    $('#generateKeyButton').click(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'index.php?fuse=admin&action=generateapikey&controller=settings',
            data: { sessionHash:gHash },
            type: 'POST',
            dataType: 'json',
            complete: function(response) {
                var responseJSON = $.parseJSON(response.responseText);
                $('#apikeystring').val(responseJSON['apikey']);
                msg(lang("New API Key has been generated"));
            }
        });
        return false;
    });
});