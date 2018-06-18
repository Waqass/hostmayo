var datefields = new Array();

$().ready(function(){

    $('#reset-password').click(function(e){
        e.preventDefault();
        RichHTML.msgBox(lang('Are you sure you want to reset the password of this client?'), {
            type:"yesno"
        }, function(result) {
            if (result.btn === lang("Yes")) {
                RichHTML.mask();
                $.ajax({
                    url: 'index.php?fuse=clients&controller=user&action=resetpass',
                    type: 'POST',
                    data: { id: $('#resetpass-customer-id').val() },
                    success: function (json) {
                        ce.parseResponse(json);
                        RichHTML.unMask();
                    }
                });
            }
        });
    });


    $('#deleteclient').click(function(){

        RichHTML.msgBox(lang('Are you sure you want to delete this customer?'), {
            type:"confirm"
        }, function(result) {
            if (result.btn === lang("Yes")) {
                RichHTML.msgBox(lang("Do you want to delete this customer's packages using the respective server plugin(s)?"), {
                    type:'confirm'
                }, function (innerResult) {

                    if ( innerResult.btn === lang('Cancel') ) {
                        return;
                    }

                    var contactForm = $("#frmdeleteclient");

                    if ( innerResult.btn === lang('Yes') ) {
                        $('#deletewithplugin').val(1)
                    }

                    $.ajax({
                        url: contactForm.attr( 'action' ),
                        type: contactForm.attr( 'method' ),
                        data: contactForm.serialize(),
                        success: function (json){
                            ce.parseResponse(json);
                            if ( json.success == true ) {
                                window.location = "index.php?fuse=clients&controller=user&view=viewusers";
                            }
                        }
                    });
                });
            }
        });
        return false;
    });

    $("#updatecontact").click(function() {

        var contactForm = $("#customerdata");
        $.ajax( {
            url: contactForm.attr( 'action' ),
            type: contactForm.attr( 'method' ),
            data: contactForm.serialize(),
            success: function (json){
                ce.parseResponse(json);
            }
        } );

        return false;
    });

});