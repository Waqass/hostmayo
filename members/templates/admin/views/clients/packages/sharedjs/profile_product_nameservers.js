var nameservers_dispatch = function(){
    //setup environment for hosting
    packagemanager.paneldescription = "Point DNS Servers here. If you set your domain to use the default Name Servers, you will be able to use the plugin's additional services when available, for example Email services provided by eNom.";
};

//add a new host entry
function nameservers_ChangeUseDefaults()
{

    var useDefaults = $('#ns_usedefaults').val();

    if (useDefaults === "0") {
        //let's hide all name server fields
        $('#ns_usedefaults').nextUntil('#addnameserver_wrapper').show();
    } else {
        //let's show all name server fields
        $('#ns_usedefaults').nextUntil('#addnameserver_wrapper').hide();
    }

}

function nameservers_addnameserver()
{
    var useDefaults = $('#ns_usedefaults').val();
    if (useDefaults === "1") {
        RichHTML.error(lang('The setting <strong>Use Defaults</strong> must be set to No if you want to add your own Name Server'));
        return;
    }

    var nth = $('input.nameserver').length;
    var serverName  = 'Name Server '+nth;

    var hostname = $($('input.nameserver')[0]).clone().show();
    hostname.attr('id',"ns_"+nth);
    hostname.attr('name',"ns_"+nth);
    hostname.val("");

    var label = $('label[for="blankrecord"]').clone().show();
    label.attr('for',"ns_"+nth);
    label.html("Name Server "+nth);

    $('#addnameserver').parent().before(label);
    $('#addnameserver').parent().before(hostname);
    $('#addnameserver').parent().before('<button type="button" name="ns_'+nth+'_nameserverdelete" id="ns_'+nth+'_nameserverdelete" style="margin-left:10px;" class="rich-button btn" onclick="nameservers_deleteaddress(this);"><span>Delete</span></button>');

}

//delete the selected host entry
function nameservers_deleteaddress(row)
{

    id = row.id.split("_");

    $('label[for="ns_'+id[1]+'"]').remove();
    $("#ns_"+id[1]).remove();
    $("#ns_"+id[1]+"_nameserverdelete").remove();

}