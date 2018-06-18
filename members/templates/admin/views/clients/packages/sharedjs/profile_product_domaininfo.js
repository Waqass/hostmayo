//dispatch sets up setting panel for panel type of domaininfo
var domaininfo_dispatch = function(){

    if ( packagemanager.permissions.cansave == true ) {

        //Assign Order Id
        if ($('#registrarorderid').length > 0) {
            $('#registrarorderid').after('<button id="btnassignorderid" class="rich-button" style="margin-left:5px;" type="button"><span>'+lang('Reassign')+'</span></button>');
            $('#btnassignorderid').bind('click',function(){
                RichHTML.prompt('Please enter the order id:',{allowblank:true},domaininfoAssignOrderId);
            });
        }

        //Assign Trasnfer Id
        if ($('#transferid').length > 0) {
            $('#transferid').after('<button id="btnassigntransferid" class="rich-button" style="margin-left:5px;" type="button"><span>'+lang('Reassign')+'</span></button>');
            $('#btnassigntransferid').bind('click',function(){
                RichHTML.prompt('Please enter the transfer id:',{allowblank:true},domaininfoAssignTransferId);
            });
        }
    }

    if ($('#registrarlock').length > 0) {
        $('#registrarlock').after('<button id="btntogglelock" class="rich-button" style="margin-left:5px;" type="button"><span>'+lang('Toggle')+'</span></button>');
        $('#btntogglelock').bind('click',function(){

            RichHTML.msgBox(lang("Are you sure you want to toggle the Registrar Lock option via the selected plugin?"),{type:'yesno'},function(data) {
                if (data.btn == lang('Yes')) {
                    if ( $('#registrarlock').val() == lang('Enabled') ) {
                        lockValue = 0;
                    } else {
                        lockValue = 1;
                    }
                    packagemanager.plugincallactionpost('SetRegistrarLock',lockValue);
                }
            });

        });
    }

    domaininfoToggleRegistrar();

    //TODO 5.0 check to see how valid the below error checking code is valid after migration
    //Checking to see what warnings we can show
    // Work out what fields we have and their values so we can show an error message if required
    showErrorBox = false;
    isTransferring = false;

    for(var iterator = 0; iterator < packagemanager.fields.length; iterator++){

        var currentFieldData = packagemanager.fields[iterator];
        // Check if we have an Unknown returned
        if(currentFieldData.id == 'purchasestatus' && currentFieldData.value == 'Unknown') {
            showErrorBox = true;
        }

        // Only way we can check for a domain transfer is look for EPP Code
        if(currentFieldData.id == 'eppCode') {
            isTransferring = true;
        }
    }

    // Do we have something to show
    if(showErrorBox && document.getElementById('accountwarning')) {
        if(isTransferring) {
            // Set text to transfer
            $('#accountwarning').text('Unable to get domain information, this is typically because the domain transfer has not yet completed.');
        } else {
            // Set default text
            $('#accountwarning').text('Unable to get domain information, domain might not be tied to registrar.');
        }
        // Show the box
        $('#accountwarning').show();
    }

};

function pluginaction_DomainTransferWithPopup()
{
    RichHTML.prompt('Please enter your EPP code:', {}, function(data){
        if ( data.btn == lang("OK") ) {
            packagemanager.plugincallactionpost('DomainTransferWithPopup',data.elements.value);
        }
    });
    return false;
}

//perform action after assigning an existing order id
function domaininfoAssignOrderId(data) {

    if (data.btn == lang("Cancel")) return;
    text = data.elements.value;

    //add hidden field for new orderid
    $('#productsettingsform').append("<input type='hidden' name='neworderid' value='"+text+"'></input>");
    $('#registrarorderid').val(text);
    domaininfoToggleRegistrar();
    //now submit as a save
    $('#btnUpdateProduct').click();
}

function domaininfoAssignTransferId(data){

    if (data.btn == lang("Cancel")) return;
    text = data.elements.value;

    //add hidden field for new orderid
    $('#productsettingsform').append("<input type='hidden' name='newtransferid' value='"+text+"'></input>");
    $('#transferid').val(text);
    domaininfoToggleRegistrar();
    //now submit as a save
    $('#btnUpdateProduct').click();

}

/**
 * toggle registrar dropdown
 * @return {[type]} [description]
 */
function domaininfoToggleRegistrar() {


    if ( ($('#registrar').val() != "0") && ($('#registrarorderid').val() == lang("Unknown")) ) {
        $('label[for="registrarorderid"]').show();
        $('#registrarorderid').show();
        $('#btnassignorderid').show();

        $('label[for="registartionstatus"]').show();
        $('#registartionstatus').show();
    } else {
        if ($('#registrar').val() == "0") {
            $('label[for="registrarorderid"]').hide();
            $('#registrarorderid').hide();
            $('#btnassignorderid').hide();

            $('label[for="registartionstatus"]').hide();
            $('#registartionstatus').hide();

        } else {
            $('label[for="registartionstatus"]').show();
            $('#registartionstatus').show();
            $('#btnassignorderid').show();

            $('label[for="registrarorderid"]').show();
            $('#registrarorderid').show();
        }
    }

    if ( $('#registartionstatus').length > 0 ) {
        //if we have an orderid then set to manually registered if status is Not Yet Registred
        if ( ($('#registartionstatus').val() == lang("Not Yet Registered")) &&
            ($('#registrarorderid').val() != lang("Unknown")) ){
                $('#registartionstatus').val("Registered");
        }
    }

}