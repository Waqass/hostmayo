hostinfo.newhostrecordid = 0;

$(document).ready(function(){
    customFields.load(hostinfo.jsonFields,function(data) {
        $('#hostinfo-div').append(data);
    }, function(){
        //clientexec.postpageload('#hostinfo-div');
        $('.form-actions').show();
    });
});

//delete the selected host entry
function hostrecords_deleteaddress(row)
{

    id = row.id.split("_");
    $("#hosttype_CT_"+id[1]).remove();
    $("#hostname_CT_"+id[1]).remove();
    $("#s2id_hosttype_CT_"+id[1]).remove();
    $("#hostaddress_CT_"+id[1]).remove();
    $("#CT_"+id[1]+"_hostdelete").remove();
    $("#hostdivider_CT_"+id[1]).remove();

    $('#CTT_'+id[1]).remove();
    $('span[for="CTT_'+id[1]+'"]').remove();
}

//add a new host entry
function hostrecords_addzoneentry()
{

    hostinfo.newhostrecordid++;

    var hosttype = $($('select.hosttype')[0]).clone();
    hosttype.attr('id',"hosttype_CT_"+hostinfo.newhostrecordid);
    hosttype.attr('name',"hosttype_CT_"+hostinfo.newhostrecordid);
    var hostname = $($('input.hostname')[0]).clone().show();
    hostname.attr('id',"hostname_CT_"+hostinfo.newhostrecordid);
    hostname.attr('name',"hostname_CT_"+hostinfo.newhostrecordid);
    hostname.val("");
    var hostaddress = $($('input.hostaddress')[0]).clone().show();
    hostaddress.attr('id',"hostaddress_CT_"+hostinfo.newhostrecordid);
    hostaddress.attr('name',"hostaddress_CT_"+hostinfo.newhostrecordid);
    hostaddress.val("");

    $('#addhostrecord').parent().before("<div id='hostdivider_CT_"+hostinfo.newhostrecordid+"' style='padding-top:10px;'></div>");
    $('#addhostrecord').parent().before(hostname);
    $('#addhostrecord').parent().before(hosttype);
    $('#addhostrecord').parent().before(hostaddress);

    $('#addhostrecord').parent().before('<button type="button" name="CT_'+hostinfo.newhostrecordid+'_hostdelete" id="CT_'+hostinfo.newhostrecordid+'_hostdelete" style="margin-left:10px;" class="rich-button btn" onclick="hostrecords_deleteaddress(this);"><span>Delete</span></button>');

    $("#hosttype_CT_"+hostinfo.newhostrecordid).select2({
        minimumResultsForSearch: 10,
        width:'resolve'
    });
}

$('#update-button').click(function() {
    $.post('index.php?fuse=clients&controller=products&action=savedomainhostrecords', $('#hostinfo').serialize(), function(data){
        var json = ce.parseResponse(data);
    });
});