$(document).ready(function(){
    customFields.load(contactinfo.jsonFields,function(data) {
        $('#contactinfo-div').append(data);
    }, function(){
        clientexec.postpageload('#contactinfo-div');
        $('.form-actions').show();
    });
});


$('#update-button').click(function() {
    $.post('index.php?fuse=clients&controller=products&action=savedomaincontactinfo', $('#contactinfo').serialize(), function(data){
        var json = ce.parseResponse(data);
    });
});