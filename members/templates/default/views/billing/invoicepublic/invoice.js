invoice = invoice || {};

$(document).ready(
    function() {
        $('.btn-pay-invoice').bind(
            'click',
            function() {
                invoice.showPaymentOptions();
            }
        );
        $('.btn-apply-credit').bind(
            'click',
            function() {
                invoice.apply_account_credit();
            }
        );
    }
);

invoice.apply_account_credit = function() {
    location.href = "index.php?sessionHash=" + clientexec.sessionHash + "&fuse=billing&controller=invoice&action=applyaccountcredit&id=" + invoice.id;
}

invoice.toggle_gateway = function(gatewayName) {
    var el = document.getElementById(invoice.selected_gateway + '-extraFields');
    if (el) {
        el.style.display = 'none';
        el.style.visibility = 'hidden';
    }
    var el = document.getElementById(gatewayName + '-extraFields');
    if (el) {
        el.style.display = 'block';
        el.style.visibility = 'visible';
        $('.makeDefaultNoCC').hide();
        $('.makeDefaultCC').show();
    } else {
        $('.makeDefaultCC').hide();
        $('.makeDefaultNoCC').show();
    }
    invoice.selected_gateway = gatewayName;
    $('.payment_method_selected').removeClass('payment_method_selected');
    $('.payment_method_' + gatewayName).parent().addClass('payment_method_selected');

    if ($('.paymentbutton2').length > 0) {
        $('.paymentbutton2').hide();
    }
    if ($('.'+invoice.selected_gateway+'paymentbutton').length > 0) {
        $('.paymentbutton1').hide();
        $('.'+invoice.selected_gateway+'paymentbutton').show();
    } else {
        $('.paymentbutton1').show();
    }
}

invoice.sendInvoice = function(invoiceId) {
    RichHTML.msgBox(lang('Are you sure you want to send the selected invoice?'),
    {
        type:"yesno"
    }, function(result) {
        if(result.btn === lang("Yes")) {
            RichHTML.mask();
            var data = {
                    items:          invoiceId,
                    itemstype:      'invoices',
                    actionbutton:   'inv-send-smart'
                };

            $.ajax({
                url: "index.php?fuse=billing&controller=invoice&action=actoninvoice",
                type: 'POST',
                data:  data,
                success:  function(xhr){
                    RichHTML.unMask();
                    RichHTML.alert(
                        lang('Invoice has been successfully sent'),
                        {
                            buttons: {
                                button1: {
                                    text: "OK",
                                    type: "OK"
                                },
                            }
                        }
                    );
                }
            });
        }
    });
};