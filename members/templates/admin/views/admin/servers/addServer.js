$(document).ready(function() {
    $('#pluginSelect').change(function() {
        getServerPluginOptions(server.id, $(this).val());
    });

    $('#saveButton').click(function() {
        $('#saveServerForm').submit();
    })

    $('#saveServerForm').validate({
        rules: {
            ignore: ":not(:visible)",
            required: {
                required: true
            }
        }
    });

    toggleTestConnectButton();

    $('#testButton').click(function(e){
        e.preventDefault();

        if ( server.id != 0 ) {
            $.getJSON('index.php?fuse=admin&controller=servers&action=testserverconnection&id=' + server.id, function(data) {
                type = 'info';
                if ( data.error == true ) {
                    type = 'alert';
                }
                RichHTML.msgBox(data.message,{
                    type: type
                });
            });
        } else {
            RichHTML.msgBox(lang('You must save your server first'),{
                type: 'alert'
            });
        }
    });
});



function getServerPluginOptions(serverId, plugin) {
    $.get('index.php?fuse=admin&controller=servers&action=getserverpluginoptions', { serverId: serverId, plugin: plugin }, function(data) {
        $('#pluginOptions').show();

        $('#pluginOptionsForm').empty();
        $(data.data).each(function(i, v) {
            newInput = ce.createPluginInput(v);
            if (newInput !== false) {
                $('#pluginOptionsForm').append($('<dt></dt>').append(newInput.label));
                $('#pluginOptionsForm').append($('<dd></dd>').append(newInput.input));
            }
        });
        server.canTestConnect = data.canTestConnect;
        toggleTestConnectButton();
        clientexec.postpageload();
    });
}

function toggleTestConnectButton()
{
    if ( server.canTestConnect == true ) {
        $('#testButton').show();
    } else {
        $('#testButton').hide();
    }
}