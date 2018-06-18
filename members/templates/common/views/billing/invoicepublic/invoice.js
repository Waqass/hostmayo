invoice = invoice || {};

$(document).ready(function(){

    $('.btn-pay-invoice').bind('click',function(){
        invoice.showPaymentOptions();
    });

    $('.btn-apply-credit').bind('click',function(){
        invoice.apply_account_credit();
    });

});

invoice.apply_account_credit = function()
{
    location.href = "index.php?sessionHash="+clientexec.sessionHash+"&fuse=billing&controller=invoice&action=applyaccountcredit&id="+invoice.id;
}

invoice.toggle_gateway = function(gatewayName) {

    var el = document.getElementById(invoice.selected_gateway+'-extraFields');
    if(el) {
        el.style.display = 'none';
        el.style.visibility = 'hidden';
    }
    var el = document.getElementById(gatewayName+'-extraFields');
    if(el) {
        el.style.display = 'block';
        el.style.visibility = 'visible';
    }

    invoice.selected_gateway = gatewayName;

    $('.payment_method_selected').removeClass('payment_method_selected');
    $('.payment_method_'+gatewayName).parent().addClass('payment_method_selected');

    if(invoice.selected_gateway == 'stripecheckout'){
        $('.paymentbutton1').hide();
        $('.paymentbutton2').show();
    }else{
        $('.paymentbutton2').hide();
        $('.paymentbutton1').show();
    }
}