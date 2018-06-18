//vars used for hosting edit screen
var serverSharedIp = "";

var hosting_dispatch = function(){
    //setup environment for hosting
};

/**
 * Toggle selecting server in hosting editing screen
 */
function hostingToggleServer()
{
    var serverId = $('#serverid').val();
    if (serverId === "0") {
        $('#serverid').nextUntil('#btnUpdateProduct',':not(select,span)').hide();
    } else {
        $('#serverid').nextUntil('#btnUpdateProduct',':not(select,span)').show();

        RichHTML.mask();
        $.ajax({
            url: 'index.php?fuse=admin&action=getavailableipaddresses',
            success: function(xhr) {
                json = ce.parseResponse(xhr);

                //json.data is new ips
                $('#availableips').children().remove();
                $.each(json.data,function(index,object){
                    if (object[0] !== "") {
                        $('#availableips').append("<option value='"+object[0]+"'>"+object[1]+"</option>");
                    }
                });

                serverSharedIp = json.sharedip;
                //let's set shared to yes on when toggling server
                $('#usesharedip').select2('val',1);
                hostingToggleUseSharedIP();

                RichHTML.unMask();
            },
            data : { serverId: serverId, includeSharedIP: true, userPackageId: packagemanager.package_id },
            dataType: 'json'
        });

    }

}

/**
 * toggle available ip dropdownlist
 */
function hostingToggleAvailableIPs()
{
    $('#ipaddress').val($('#availableips').select2('val'));
}

/**
 * toggle using shared ip or not
 */
function hostingToggleUseSharedIP()
{

    if ($('#usesharedip').val() === "0") {
        $('#ipaddress').val(' --- ');

        //show availableips
        $('label[for="availableips"]').show();
        $('#availableips').select2("container").show();

        $('#availableips').select2("enable");
        $('#availableips').select2("val",{id:0,text: ' - ' + lang('Select IP Address') + ' - ' });
    } else {
        $('#availableips').select2("disable");

        //show availableips
        $('label[for="availableips"]').hide();
        $('#availableips').select2("container").hide();

        if (serverSharedIp === "") {
            serverSharedIp = $('#sharedip').val();
        }
        $('#availableips').select2("data",{id:0,text: '' });
        //put in ip address the shared ip
        $('#ipaddress').val(serverSharedIp);
    }
}