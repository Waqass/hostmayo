paymentmethod = paymentmethod || {};

$(document).ready(
    function(){
        paymentmethod.get_cc_info(paymentmethod.selectedplugin);
        $('.btn-update-payment-method').bind(
            'click',
            function(e) {
                var valid = $('.update-payment-method-frm').parsley('validate');
                if (valid) {
                    $('.update-payment-method-frm').submit();
                }
            }
        );
        $('.paymenttype-list').bind(
            'change',
            function() {
                paymentmethod.get_cc_info($(this).val());
            }
        );
        $('#deleteccbutton').bind(
            'click',
            function() {
                paymentmethod.delete_cc();
            }
        );
    }
);

paymentmethod.get_cc_info = function(selectedplugin)
{
    $.get(
        "index.php?fuse=admin&action=getpaymentplugindetails",
        {
            plugin: selectedplugin
        },
        paymentmethod.process_plugin_details
    );
}

paymentmethod.delete_cc = function()
{
    $.post(
        'index.php?fuse=clients&controller=index&action=deleteccnumber',
        {},
        function (response) {
            var data = ce.parseResponse(response);
            if (data.error) {
                return false;
            }
            window.location = "index.php?fuse=clients&controller=userprofile&view=paymentmethod";
        }
    );
}

paymentmethod.process_plugin_details = function(json)
{

    if (json.error) {
        return false;
    }

    //show subscription options
    if (json.usingsubscriptionoptions) {
        if (document.getElementById('use_recurring') != null) {
            document.getElementById('use_recurring').style.display = 'block';
        }
    } else {
        if (document.getElementById('use_recurring') != null) {
            document.getElementById('use_recurring').style.display = 'none';
        }
    }

    //Display Payment Information for special plugins
    if (document.getElementById('btnupdatepaymentmethod') != null) {
        document.getElementById('btnupdatepaymentmethod').style.display = 'none';
    }
    if ($('.PaymentInformation').length > 0) {
        $('.PaymentInformation').hide();
    }
    if ($('.PaymentIframe').length > 0) {
        $('.PaymentIframe').attr('src', '');
    }
    if ($('.'+json.internalname+'PaymentInformation').length > 0) {
        if (json.customercurrentpaymenttype == json.internalname) {
            $('.'+json.internalname+'PaymentInformation').show();
            if ($('.'+json.internalname+'PaymentIframe').length > 0) {
                $('.'+json.internalname+'PaymentIframe').attr('src', 'index.php?fuse=admin&view=viewpluginurl&plugintoshow='+json.internalname);
            } else if (json.openHandler) {
                window[json.internalname+"OpenHandler"]();
            }
        } else {
            var valid = $('.update-payment-method-frm').parsley( 'validate' );
            if (valid) {
                $('.update-payment-method-frm').submit();
            }
        }
    } else {
        if (document.getElementById('btnupdatepaymentmethod') != null) {
            document.getElementById('btnupdatepaymentmethod').style.display = 'block';
        }
    }

    if (json.description != "") {
        $('.plugin_description').html("<p>" + json.description + "</p>");
        $('.plugin_description').show();
    } else {
        $('.plugin_description').hide();
    }

    //show cc info
    if (json.showccoptions) {
        if (document.getElementById('creditcardinfo') != null) {
            document.getElementById('creditcardinfo').style.display = 'block';
        }
    } else {
        if (document.getElementById('creditcardinfo') != null) {
            document.getElementById('creditcardinfo').style.display = 'none';
        }

        //return because the remaining options are only for cc
        return;
    }

    //cc validation field check
    if (json.awaitingvalidation != "") {
        if (document.getElementById('awaitingvalidation') != null) {
            document.getElementById('awaitingvalidation').innerHTML = json.awaitingvalidation;
        }
        if (document.getElementById('awaitingvalidationspan') != null) {
            document.getElementById('awaitingvalidationspan').style.display = '';
        }
        if (document.getElementById('ccnumberspan') != null) {
            document.getElementById('ccnumberspan').style.display = 'none';
        }
        if (document.getElementById('deleteccspan') != null) {
            document.getElementById('deleteccspan').style.display = '';
        }
        if (document.getElementById('newccspan') != null) {
            document.getElementById('newccspan').style.display = "none";
        }
        if (document.getElementById('lastfourspan') != null) {
            document.getElementById('lastfourspan').style.display = 'none';
        }
    } else {
        //console.debug(json.awaitingvalidation);
        if (json.last4 != "") {
            if (document.getElementById('newccspan') != null) {
                document.getElementById('newccspan').style.display = "";
            }
            if (document.getElementById('ccnumberspan') != null) {
                document.getElementById('ccnumberspan').style.display = 'none';
            }
            if (document.getElementById('lastfourspan') != null) {
                document.getElementById('lastfourspan').style.display = '';
            }
            if (document.getElementById('lastfour') != null) {
                $('#lastfour').val("xxxxxxxxxxxx" + json.last4);
            }
            if (document.getElementById('lastfour2') != null) {
                document.getElementById('lastfour2').textContent = "xxxxxxxxxxxx" + json.last4;
            }
            if (document.getElementById('deleteccspan') != null) {
                document.getElementById('deleteccspan').style.display = '';
            }
        } else {
            if (document.getElementById('newccspan') != null) {
                document.getElementById('newccspan').style.display = "none";
            }
            if (document.getElementById('ccnumberspan') != null) {
                document.getElementById('ccnumberspan').style.display = '';
            }
            if (document.getElementById('lastfourspan') != null) {
                document.getElementById('lastfourspan').style.display = 'none';
            }
            if (document.getElementById('lastfour') != null) {
                $('#lastfour').val("");
            }
            if (document.getElementById('lastfour2') != null) {
                document.getElementById('lastfour2').textContent = "None";
            }
            if (document.getElementById('deleteccspan') != null) {
                document.getElementById('deleteccspan').style.display = 'none';
            }
        }
        if (document.getElementById('awaitingvalidationspan') != null) {
            document.getElementById('awaitingvalidationspan').style.display = 'none';
        }
    }

    //if cc then which cards do we show
    if (document.getElementById('visa_logo') != null) {
        document.getElementById('visa_logo').style.display = json.visastyle;
    }
    if (document.getElementById('mastercard_logo') != null) {
        document.getElementById('mastercard_logo').style.display = json.mastercardstyle;
    }
    if (document.getElementById('americanexpress_logo') != null) {
        document.getElementById('americanexpress_logo').style.display = json.americanexpressstyle;
    }
    if (document.getElementById('discover_logo') != null) {
        document.getElementById('discover_logo').style.display = json.discoverstyle;
    }
    if (document.getElementById('lasercard_logo') != null) {
        document.getElementById('lasercard_logo').style.display = json.lasercardstyle;
    }
    if (document.getElementById('dinersclub_logo') != null) {
        document.getElementById('dinersclub_logo').style.display = json.dinersclubstyle;
    }
    if (document.getElementById('switch_logo') != null) {
        document.getElementById('switch_logo').style.display = json.switchstyle;
    }

    //for card validation
    if (document.getElementById('validccbits') != null) {
        document.getElementById('validccbits').value= json.visabit + '' + json.mcbit + '' + json.amexbit + '' + json.discbit + '' + json.laserbit + '' + json.dinersbit + '' + json.switchbit;
    }


}
