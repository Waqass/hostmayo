var profile = {};
function UpdateStatus(data){
    oldstatus = data.newstatusid;
    oldstatusAliasto = data.newstatusAliasid;

    $.ajax({
        url: "index.php?fuse=clients&action=updateclientstatus&controller=user",
        success: function(data) {
            if (data.error) {
                //we do not need to repopulate the filters if there was a validation error
                msg(lang("There was an error when updating your status"));
            } else if(data.message) {
                msg(data.message);
            } else {
                msg(lang("Status has been updated successfully"));
                $('#header-client-status').text($('#dropdown_customerstatus').children("option").filter(":selected").text());
                if ( typeof userpackages != 'undefined'  ) {
                    userpackages.grid.reload({ params:{ start:0 } });
                }

                //update right panel
                $('.active-customer-panel').load("index.php?fuse=admin&view=activecustomer&nolog=1");
            }
        },
        data: {
            statusid: oldstatus,
            packageplugin: data.packageplugin,
            registrarplugin: data.registrarplugin,
            packageaction: data.packageaction
        }
    });
}

function checkStatus(newstatus,newstatusAliasto,statusname) {
    var returnarray = [];
    if(newstatus==oldstatus){ return false; }

    returnarray.packageplugin = 0;
    returnarray.registrarplugin = 0;
    returnarray.packageaction = 0;
    returnarray.newstatusid=newstatus;
    returnarray.newstatusAliasid=newstatusAliasto;
    returnarray.newstatusname=statusname;
    returnarray.statusbackground = "red";

    if (newstatusAliasto == oldstatusAliasto) {
        UpdateStatus(returnarray);
    }

    var hascallback = false;

    switch('' + newstatusAliasto){ // stupid dynamic languages
        case '0':
            break;
        case '1':
            if ((oldstatusAliasto < 1)&&(haspackages)) {
                hascallback = true;
                RichHTML.msgBox(lang('Do you wish to activate this clients packages as well?'),
                {
                    type:"confirm"
                }, function(result) {
                    if ( result.btn == lang('Cancel') ) {
                        return
                    } else if ( result.btn == lang('No') ) {
                        UpdateStatus(returnarray);
                        return;
                    }
                    returnarray.packageaction=1;
                    if ( hasplugin ) {
                        RichHTML.msgBox(lang('Do you wish to use the server plugin to activate these packages?'),
                        {
                            type:"confirm"
                        }, function(result) {
                            if (result.btn == lang('Yes')) {
                                returnarray.packageplugin=1;
                                returnarray.registrarplugin=1;
                            } else if(result.btn == lang('Cancel')) {
                                return;
                            }
                            UpdateStatus(returnarray);
                        });
                    } else {
                        UpdateStatus(returnarray);
                    }
                });
            }
            break;
        case '-1':
            if(haspackages){
                hascallback = true;
                RichHTML.msgBox(lang('Do you wish to suspend this clients packages as well?'),
                {
                    type:"confirm"
                }, function(result) {
                    if ( result.btn == lang('Cancel') ) {
                        return
                    } else if ( result.btn == lang('No') ) {
                        UpdateStatus(returnarray);
                        return;
                    }
                    returnarray.packageaction=1;
                    if ( hasplugin ) {
                        RichHTML.msgBox(lang('Do you wish to use the server plugin to suspend these packages?'),
                        {
                            type:"confirm"
                        }, function(result) {
                            if (result.btn == lang('Yes')) {
                                returnarray.packageplugin=1;
                            } else if(result.btn == lang('Cancel')) {
                                return;
                            }
                            UpdateStatus(returnarray);
                        });
                    } else {
                        UpdateStatus(returnarray);
                    }
                });
            }
            break;
        case '-2':
        case '-3':
            if(haspackages){
                hascallback = true;
                RichHTML.msgBox(lang('Do you wish to cancel this clients packages as well?'),
                {
                    type:"confirm"
                }, function(result) {
                    if ( result.btn == lang('Cancel') ) {
                        return
                    } else if ( result.btn == lang('No') ) {
                        UpdateStatus(returnarray);
                        return;
                    }
                    returnarray.packageaction=1;
                    if ( hasplugin ) {
                        RichHTML.msgBox(lang('Do you wish to use the server plugin to cancel these packages?'),
                        {
                            type:"confirm"
                        }, function(result) {
                            if (result.btn == lang('Yes')) {
                                returnarray.packageplugin=1;
                            } else if(result.btn == lang('Cancel')) {
                                return;
                            }
                            UpdateStatus(returnarray);
                        });
                    } else {
                        UpdateStatus(returnarray);
                    }
                });
            }
            break;
    }
    if (!hascallback) UpdateStatus(returnarray);
}

$(document).ready(function(){
    $('#dropdown_customerstatus').bind('click',function(){
        checkStatus($(this).val(), $(this).children("option").filter(":selected").data('aliasto'), $(this).children("option").filter(":selected").text());
    });

    $('.full-contact-btn,.update-full-contact-btn').bind('click',function(){
        forcepull = $(this).attr('data-force');
        clientexec.getfullcontact(forcepull);
    });


    $('#dropdown_customerGroup').bind('click',function(){
        $.ajax({
            url: "index.php?fuse=clients&controller=userprofile&action=updateclientgroup",
            success: function(data) {
                ce.parseResponse(data);
             },
            data: { groupid: $(this).val()}
         });

    });

    $('.register-guest-btn-wrapper .btn').bind('click', function() {
        RichHTML.msgBox(lang('Are you sure you wish to register this account?'), {
            type: 'confirm'
        }, function(result) {
            if (result.btn == lang('No') || result.btn == lang('Cancel')) {
                return;
            }

            $.post('index.php?fuse=clients&action=registerguestacccount', {
                user_id: clientexec.customerId
            }, function() {
                location.reload();
            });
        })
    });
});

profile.get_counts = function()
{
    $.getJSON('index.php?fuse=clients&action=getprofilecounts&controller=user',{id:clientexec.customerId},function(response){
        json = ce.parseResponse(response);

        $('.profile_notes_count').text(" ("+json.counts.notes_count+")");
        $('.profile_altaccounts_count').text(" ("+json.counts.altaccounts_count+")");
        $('.profile_ticket_count').text(" ("+json.counts.ticket_count+")");
        $('.profile_invoices_count').text(" ("+json.counts.invoice_count+")");
        $('.profile_recurring_count').text(" ("+json.counts.recurring_charges+")");
        $('.profile_packages_count').text(" ("+json.counts.package_count+")");
    });
}
