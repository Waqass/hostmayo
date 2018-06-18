hostrecords_dispatch = function(){
    //we don't need to show the perform on server option as all of these changes only apply to server
    packagemanager.paneldescription = lang("Point your domain to a web site by pointing to an IP Address, or forward to another site, or point to a temporary page (known as 'Parking'), and more. These records are also known as sub-domains (e.g. webmail.example.com).");
    //setup environment for hosting

};

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

    packagemanager.newhostrecordid++;

    var hosttype = $($('select.hosttype')[0]).clone();
    hosttype.attr('id',"hosttype_CT_"+packagemanager.newhostrecordid);
    hosttype.attr('name',"hosttype_CT_"+packagemanager.newhostrecordid);
    var hostname = $($('input.hostname')[0]).clone().show();
    hostname.attr('id',"hostname_CT_"+packagemanager.newhostrecordid);
    hostname.attr('name',"hostname_CT_"+packagemanager.newhostrecordid);
    hostname.val("");
    var hostaddress = $($('input.hostaddress')[0]).clone().show();
    hostaddress.attr('id',"hostaddress_CT_"+packagemanager.newhostrecordid);
    hostaddress.attr('name',"hostaddress_CT_"+packagemanager.newhostrecordid);
    hostaddress.val("");

    $('#addhostrecord').parent().before("<div id='hostdivider_CT_"+packagemanager.newhostrecordid+"' style='padding-top:10px;'></div>");
    $('#addhostrecord').parent().before(hostname);
    $('#addhostrecord').parent().before(hosttype);
    $('#addhostrecord').parent().before(hostaddress);

    $('#addhostrecord').parent().before('<button type="button" name="CT_'+packagemanager.newhostrecordid+'_hostdelete" id="CT_'+packagemanager.newhostrecordid+'_hostdelete" style="margin-left:10px;" class="rich-button btn" onclick="hostrecords_deleteaddress(this);"><span>'+lang('Delete')+'</span></button>');

    $("#hosttype_CT_"+packagemanager.newhostrecordid).select2({
        minimumResultsForSearch: 35,
        width:'resolve'
    });

}
