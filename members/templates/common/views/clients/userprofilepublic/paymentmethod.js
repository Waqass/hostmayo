paymentmethod = paymentmethod || {};

$(document).ready(function(){
    paymentmethod.get_cc_info(paymentmethod.selectedplugin);

    $('.btn-update-payment-method').bind('click',function(e){
        var valid = $('.update-payment-method-frm').parsley( 'validate' );
        if (valid) $('.update-payment-method-frm').submit();
    });

    $('.paymenttype-list').bind('change',function(){
        paymentmethod.get_cc_info($(this).val());
    });

    $('#deleteccbutton').bind('click',function(){
        paymentmethod.delete_cc();
    });

});

paymentmethod.get_cc_info = function(selectedplugin)
{
    $.get("index.php?fuse=admin&action=getpaymentplugindetails",{plugin:  selectedplugin},paymentmethod.process_plugin_details);
}

paymentmethod.delete_cc = function()
{
    $.post('index.php?fuse=clients&controller=index&action=deleteccnumber', {}, function(response) {
        var data = ce.parseResponse(response);
        if (data.error) {
            return false;
        }
        window.location = "index.php?fuse=clients&controller=userprofile&view=paymentmethod";
    });
}

paymentmethod.process_plugin_details = function(json)
{

    if(json.error){
        return false;
    }

    //show subscription options
    if(json.usingsubscriptionoptions){
        if (document.getElementById('use_recurring') !=null) document.getElementById('use_recurring').style.display = 'block';
    }else{
        if (document.getElementById('use_recurring') !=null) document.getElementById('use_recurring').style.display = 'none';
    }

    //show edit vault or CIM payment information
    if(json.showeditvaultpaymentinformation){
        if (document.getElementById('btnupdatepaymentmethod') !=null) document.getElementById('btnupdatepaymentmethod').style.display = 'none';

        if (document.getElementById('authnetcimpaymentinformation') !=null) document.getElementById('authnetcimpaymentinformation').style.display = 'none';
        if (document.getElementById('authnetcimpaymentiframe') !=null) document.getElementById('authnetcimpaymentiframe').src = '';

        if (document.getElementById('stripecheckoutpaymentinformation') !=null) document.getElementById('stripecheckoutpaymentinformation').style.display = 'none';

        if (document.getElementById('vaultpaymentiframe') !=null && json.customercurrentpaymenttype == 'quantumvault'){
            if (document.getElementById('vaultpaymentinformation') !=null) document.getElementById('vaultpaymentinformation').style.display = 'block';
            document.getElementById('vaultpaymentiframe').src = "index.php?fuse=admin&view=viewpluginurl&plugintoshow=quantumvault";
        }else{
            if (document.getElementById('vaultpaymentinformation') !=null) document.getElementById('vaultpaymentinformation').style.display = 'none';
            document.getElementById('vaultpaymentiframe').src = '';
            var valid = $('.update-payment-method-frm').parsley( 'validate' );
            if (valid) $('.update-payment-method-frm').submit();
        }
    }else if(json.showeditauthnetcimpaymentinformation){
        if (document.getElementById('btnupdatepaymentmethod') !=null) document.getElementById('btnupdatepaymentmethod').style.display = 'none';

        if (document.getElementById('vaultpaymentinformation') !=null) document.getElementById('vaultpaymentinformation').style.display = 'none';
        if (document.getElementById('vaultpaymentiframe') !=null) document.getElementById('vaultpaymentiframe').src = '';

        if (document.getElementById('stripecheckoutpaymentinformation') !=null) document.getElementById('stripecheckoutpaymentinformation').style.display = 'none';

        if (document.getElementById('authnetcimpaymentiframe') !=null && json.customercurrentpaymenttype == 'authnetcim'){
            if (document.getElementById('authnetcimpaymentinformation') !=null) document.getElementById('authnetcimpaymentinformation').style.display = 'block';
            document.getElementById('authnetcimpaymentiframe').src = "index.php?fuse=admin&view=viewpluginurl&plugintoshow=authnetcim";
        }else{
            if (document.getElementById('authnetcimpaymentinformation') !=null) document.getElementById('authnetcimpaymentinformation').style.display = 'none';
            document.getElementById('authnetcimpaymentiframe').src = '';
            var valid = $('.update-payment-method-frm').parsley( 'validate' );
            if (valid) $('.update-payment-method-frm').submit();
        }
    }else if(json.showeditstripecheckoutpaymentinformation){
        if (document.getElementById('btnupdatepaymentmethod') !=null) document.getElementById('btnupdatepaymentmethod').style.display = 'none';

        if (document.getElementById('vaultpaymentinformation') !=null) document.getElementById('vaultpaymentinformation').style.display = 'none';
        if (document.getElementById('vaultpaymentiframe') !=null) document.getElementById('vaultpaymentiframe').src = '';

        if (document.getElementById('authnetcimpaymentinformation') !=null) document.getElementById('authnetcimpaymentinformation').style.display = 'none';
        if (document.getElementById('authnetcimpaymentiframe') !=null) document.getElementById('authnetcimpaymentiframe').src = '';

        if (json.customercurrentpaymenttype == 'stripecheckout'){
            if (document.getElementById('stripecheckoutpaymentinformation') !=null) document.getElementById('stripecheckoutpaymentinformation').style.display = 'block';
            openHandler();
        }else{
            if (document.getElementById('stripecheckoutpaymentinformation') !=null) document.getElementById('stripecheckoutpaymentinformation').style.display = 'none';
            var valid = $('.update-payment-method-frm').parsley( 'validate' );
            if (valid) $('.update-payment-method-frm').submit();
        }
    }else{
        if (document.getElementById('vaultpaymentinformation') !=null) document.getElementById('vaultpaymentinformation').style.display = 'none';
        if (document.getElementById('vaultpaymentiframe') !=null) document.getElementById('vaultpaymentiframe').src = '';

        if (document.getElementById('authnetcimpaymentinformation') !=null) document.getElementById('authnetcimpaymentinformation').style.display = 'none';
        if (document.getElementById('authnetcimpaymentiframe') !=null) document.getElementById('authnetcimpaymentiframe').src = '';

        if (document.getElementById('stripecheckoutpaymentinformation') !=null) document.getElementById('stripecheckoutpaymentinformation').style.display = 'none';

        if (document.getElementById('btnupdatepaymentmethod') !=null) document.getElementById('btnupdatepaymentmethod').style.display = 'block';
    }

    if (json.description != "") {
        $('.plugin_description').html("<p>"+json.description+"</p>");
        $('.plugin_description').show();
    } else {
        $('.plugin_description').hide();
    }

    //show cc info
    if(json.showccoptions){
        if (document.getElementById('creditcardinfo') !=null) document.getElementById('creditcardinfo').style.display = 'block';
    }else{
        if (document.getElementById('creditcardinfo') !=null) document.getElementById('creditcardinfo').style.display = 'none';
        return;
        //return because the remaining options are only for cc
    }

    //cc validation field check
    if(json.awaitingvalidation!=""){

        if (document.getElementById('awaitingvalidation') !=null) document.getElementById('awaitingvalidation').innerHTML = json.awaitingvalidation;
        if (document.getElementById('awaitingvalidationspan') !=null) document.getElementById('awaitingvalidationspan').style.display = '';
        if (document.getElementById('ccnumberspan') !=null) document.getElementById('ccnumberspan').style.display = 'none';
        if (document.getElementById('deleteccspan') !=null) document.getElementById('deleteccspan').style.display = 'none';
        if (document.getElementById('newccspan') !=null) document.getElementById('newccspan').style.display = "none";
        $('#lastfourspan').hide();

    }else{

        //console.debug(json.awaitingvalidation);

        if(json.last4!=""){
            if (document.getElementById('newccspan') !=null) document.getElementById('newccspan').style.display = "";
            if (document.getElementById('ccnumberspan') !=null) document.getElementById('ccnumberspan').style.display = 'none';
            if (document.getElementById('lastfourspan') !=null) document.getElementById('lastfourspan').style.display = '';
            $('#lastfour').val("xxxxxxxxxxxx"+json.last4);
            $('#lastfourspan').show();
            if (document.getElementById('deleteccspan') !=null) document.getElementById('deleteccspan').style.display = '';
        }else{
            if (document.getElementById('newccspan') !=null) document.getElementById('newccspan').style.display = "none";
            if (document.getElementById('ccnumberspan') !=null) document.getElementById('ccnumberspan').style.display = '';
            if (document.getElementById('lastfourspan') !=null) document.getElementById('lastfourspan').style.display = 'none';
            $('#lastfour').val("");
            $('#lastfourspan').hide();
            if (document.getElementById('deleteccspan') !=null) document.getElementById('deleteccspan').style.display = 'none';
        }
        if (document.getElementById('awaitingvalidationspan') !=null) document.getElementById('awaitingvalidationspan').style.display = 'none';
    }

    //if cc then which cards do we show
    if (document.getElementById('visa_logo') !=null) document.getElementById('visa_logo').style.display = json.visastyle;
    if (document.getElementById('mastercard_logo') !=null) document.getElementById('mastercard_logo').style.display = json.mastercardstyle;
    if (document.getElementById('americanexpress_logo') !=null) document.getElementById('americanexpress_logo').style.display = json.americanexpressstyle;
    if (document.getElementById('discover_logo') !=null) document.getElementById('discover_logo').style.display = json.discoverstyle;

    if (document.getElementById('lasercard_logo') !=null) document.getElementById('lasercard_logo').style.display = json.lasercardstyle;
    if (document.getElementById('dinersclub_logo') !=null) document.getElementById('dinersclub_logo').style.display = json.dinersclubstyle;
    if (document.getElementById('switch_logo') !=null) document.getElementById('switch_logo').style.display = json.switchstyle;
    //for card validation
    if (document.getElementById('validccbits') !=null) document.getElementById('validccbits').value= json.visabit+''+json.mcbit+''+json.amexbit+''+json.discbit+''+json.laserbit+''+json.dinersbit+''+json.switchbit;


}
