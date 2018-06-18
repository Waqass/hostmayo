/**
 * Display Plugin dropdown list if the productData has a plugin
 */
packagemanager.displayPluginOptions = function()
{

    if (packagemanager.paneldescription !== "") {
        $('#paneldescriptiondiv').html(packagemanager.paneldescription).show();
    } else {
        $('#paneldescriptiondiv').hide();
    }

    if ($('#hiddenwarning').length > 0) {
         //let's add to the warning the accountnotonserver
         $('#accountwarning').text($('span[for="hiddenwarning"]').text());
         $('#accountwarning').css('display', 'table');
         $('#performonserver_wrapper').hide();
         $('#performonserver').attr('checked', false);
    } else{
        $('#accountwarning').hide();
    }

    if( packagemanager.hasplugin){
        if( ($('#updateviaplugin').length > 0) && packagemanager.showperformonserver) {

            //Do we add the warning here
            // Change the label on the update package checkbox
            if(packagemanager.producttype == 3) {
                 $('#performonserver_wrapper >span').text('Update at Registrar if necessary');
            } else {
                $('#performonserver_wrapper >span').text($('span[for="updateviaplugin"]').text());
            }
            if(packagemanager.producttype == 2 ) {
                $('#performonserver_wrapper').hide();
            } else {
                $('#performonserver_wrapper').show();
                $('#performonserver').attr('checked', true);
            }
             // Show the hidden domain tabs
             document.getElementById('settingtype_domaincontactinfo').style.visibility = 'visible';
             document.getElementById('settingtype_hostrecords').style.visibility = 'visible';
             document.getElementById('settingtype_nameservers').style.visibility = 'visible';

             $('#performonserver').bind('change',packagemanager.warnaboutsyncing);

        } else {

            $('#performonserver_wrapper').hide();
            $('#performonserver').attr('checked', false);
        }

    } else {
        //no plugin available for this product so let's hide plugin related options
        //check to see if dropdown plugin exists and if so hide it as plugin does not exist
        $('#divpluginactiondropdown').hide();
        $('#performonserver_wrapper').hide();
        $('#performonserver').attr('checked', false);
    }

};

//listener function for type of data being returned for the package
//I need to return some HTML for a field pulled from fields callback
packagemanager.returnFieldHTML = function(data)
{
    var length = 0;

    json = ce.parseResponse(data);
    if (json.error) {
        RichHTML.unMask();
        return;
    }

    packagemanager.permissions = json.productinfo.Permissions;
    packagemanager.fields = json.productFields;

    customFields.load(json.productFields,function(data) {
        $('#selectedproduct-view').append(data);
    }, function(){
        clientexec.postpageload('#selectedproduct-view');
        RichHTML.unMask();
    });

    // totalActualFields is the number of actaul html fields we are rendering.
    if (json.totalActualFields > 0) {
       //check to see if all fields are disabled... if so remove update btn
        if (customFields.getAllFieldsDisabled()){
            $('#btnUpdateProduct').hide();
            $('#btnoptionsseperator').hide();
        } else {
            $('#btnUpdateProduct').show();
            $('#btnoptionsseperator').show();
        }
    } else {
        $('#selectedproduct-view').append("<div style='clear:both;padding-top:10px;width:400px;color:gray;'><center>" + lang('No items found') + "</center></div>");
        $('#btnUpdateProduct').hide();
        $('#btnoptionsseperator').hide();
    }


    //call dispatch function for the newley selected setting panel (if exists)
    var funcName = packagemanager.activeTab+"_dispatch";

    //This if for the js for the dynamic panel
    if (eval("typeof " + funcName + " == 'function'")) {
        eval(funcName+"()");
    }


    $('#productidentifier').text(json.productinfo.name);
    $('#product_desc_details').text(json.productinfo.desc_details);
    packagemanager.displayPluginOptions();

    //update some fields by permission
    //if product added let's update permission
    if ( ($('#productstatus').length > 0) && (!packagemanager.permissions.changestatus) ){
        $('#productstatus').select2('disable');
    }

    if ( ($('#btnUpdateProduct').length > 0) && (!packagemanager.permissions.cansave) ){
        $('#btnUpdateProduct').attr('disabled','true');
    }

    if ( ($('#deletepackage').length > 0) && (!packagemanager.permissions.candelete) ){
        $('#deletepackage').attr('disabled','true');
    }

    //if general let's populate custom fields
    if(packagemanager.activeTab == "groupinfo") {
        packagemanager.toggleProduct();
    }

};

/**
 * possibly synching issue we should warn about
 */
packagemanager.warnaboutsyncing = function()
{
    if(!$('#performonserver').is(':checked')) {
        $('#serverid').select2('enable', true);
        RichHTML.info("<b style='color:red;'>Warning:</b> You run the risk of unsyncing your product information on Clientexec with the product information on the server.  Only uncheck this option if you know what you are doing.");
    } else {
        $('#serverid').select2('disable', true);
    }
};


packagemanager.deleteProduct = function()
{
    var pluginadditionalmsg = "";
    if (packagemanager.hasplugin) pluginadditionalmsg = "<br/><br/><div class='alert'>Note: This only deletes the product in Clientexec and does not use the associated plugin.</div>";
    RichHTML.msgBox(lang('Are you sure you want to delete this package?' + pluginadditionalmsg),
        {type:"yesno"}, function(result) {
            if(result.btn === lang("Yes")) {
                RichHTML.mask();
                $.post("index.php?fuse=clients&controller=packages&action=admindeletepackages", {
                    ids: packagemanager.package_id
                },
                function(data){
                    var json = ce.parseResponse(data);
                    if (!json.error) {
                        window.location.href = "index.php?fuse=clients&controller=userprofile&view=profileproducts";
                    }
                });
            }
        });
};

/**
 * Called when we change the product
 * Note: custom fields are set by group and not product but we retrieve
 * @return void
 */
packagemanager.toggleProduct = function()
{

    //let's remove custom fields
    $('#customfieldsfollow').nextUntil('#customfieldsended').remove();
    $('#customfieldsfollow').remove();
    $('#customfieldsended').remove();
    $('span[for="customfieldsended"]').remove();
    $('span[for="customfieldsfollow"]').remove();

    //let's repoulate the custom fields
    var groupId = $('#subgroup').val();
    $.get('index.php?fuse=clients&action=getproductcustomfields',{
        packageId: packagemanager.package_id,
        groupId: groupId
    },function(xhr){
        if(packagemanager.activeTab == "groupinfo") {
            var json = ce.parseResponse(xhr);

            customFields.load(json.productFields,function(data) {
                $('#selectedproduct-view').append(data);
            }, function(){
                clientexec.postpageload('.selectedproduct-view');
            });

            RichHTML.unMask();
        }

    });
};

/**
 * Method to call when product group is changed
 * @return void
 */
packagemanager.toggleProductGroup = function()
{
    RichHTML.mask();
    var groupid = $('#group').val();
    $.get('index.php?fuse=admin&controller=products&action=getproducts',{
        groupid: groupid
    },function(xhr){
        //let's update the product ids
        var json = ce.parseResponse(xhr);

        $('#subgroup option').remove();
        $(json.results).each(function(key,obj){
            newoption = $('<option value="'+obj.id+'">'+obj.name+'</option>');
            $('#subgroup').append(newoption);
        });
        $('#select option:first-child').attr("selected", "selected");

        //let's select first item
        $('#subgroup').select2('val',$($('#subgroup option')[0]).val());
        $('#subgroup').change();
    });
};

/**
 * showManageSettingByPlugin description
 *
 * @param  string url Plugin view url
 * @return void
 */
packagemanager.showManageSettingByPlugin = function(url)
{
    location.href = url;
}

/**
* Show settings for the product by a given setting type like billing, hosting, general
*/
packagemanager.showManageSettingByType = function(typeid) {
    packagemanager.updateAvailablePluginActions();
    var url;
    url = "index.php?fuse=clients&controller=userprofile&view=profileproduct&selectedtab="+typeid+"&id="+packagemanager.package_id;
    if ($('#selectedproduct-view').length == 0) {
        location.href = url;
        return;
    }

    RichHTML.mask();

    $('.snapin_view').remove();
    $('#btnoptionsseperator').hide();

    //we reset this showperformonserver to true so that we can change based on setting type
    packagemanager.showperformonserver = true;
    packagemanager.paneldescription = "";

    document.getElementById("selectedproduct-view").innerHTML = "";

    $(".vtab").removeClass('active');
    $('#settingtype_'+typeid).addClass("active");
    $('#settingtype').val(typeid);

    packagemanager.activeTab = typeid;

    $.get('index.php?fuse=clients&action=admingetclientproductfields',
        {
            settingtype: typeid,
            packageId: packagemanager.package_id
        },  packagemanager.returnFieldHTML);

    url = "index.php?fuse=clients&controller=userprofile&view=profileproduct&selectedtab="+packagemanager.activeTab+"&id="+packagemanager.package_id;
    History.pushState({}, window.title, url);

};

/**
 * show left tabs based on product type
 * @return void
 */
packagemanager.showlefttabsavailabletoproduct = function()
{
    for (var i in packagemanager.panels)
    {
        if(document.getElementById('settingtype_'+packagemanager.panels[i]) != undefined) {
            document.getElementById('settingtype_'+packagemanager.panels[i]).style.display = "";
        }

        //lazy load any javascript for type specific settings
        if($.inArray(packagemanager.panels[i],packagemanager.arrayOfLazyLoadingPanels) > -1) {
            ce.lazyLoad([relativePath+'templates/admin/views/clients/packages/sharedjs/profile_product_'+packagemanager.panels[i]+'.js'], function () {});
        }
    }
};

/**
 * hide custom field price when needed
 * @return {[type]} [description]
 */
packagemanager.billingChangedCustomPrice = function()
{
    if ($('#usecustomprice').val() == "1") {
        $('label[for="customprice"]').show();
        $('#customprice').show();
    } else {
        $('label[for="customprice"]').hide();
        $('#customprice').hide();
    }
};

/**
* Toggle recurring billing dropdown
*/
packagemanager.billingToggleRecurring = function()
{
    var idoffirstoption;
    //we need to show an error msg if there are no recurring fees for this product
    if ($('#billingcycle option').length === 0) {
        RichHTML.error("This product type does not have any configured billing cycles.");
        $('#recurring').select2("val",0);
        return;
    }

    if ($('#recurring').val() == "1") {
        //let's show the billing fields we need with recurring set to 1
        $('#recurring').nextUntil('#btnUpdateProduct',':not(select)').show();
        $('span[for="hiddenwarning"]').hide();
        idoffirstoption = $($('#billingcycle option')[0]).attr('value');
        if (idoffirstoption == 0 && $('#billingcycle option').length > 1) {
            idoffirstoption = $($('#billingcycle option')[1]).attr('value');
        }
        $('#billingcycle').select2('val',idoffirstoption);
        packagemanager.billingChangedCustomPrice();
    } else {
        //let's show the billing fields we need with recurring set to 1
        $('#recurring').nextUntil('#btnUpdateProduct',':not(select)').hide();
    }

    //packagemanager.billingChangedBillingCycle();
};

packagemanager.billingChangedBillingCycle = function() {
    RichHTML.info('Changing the Recurring or Billing Cycle of the product can affect its addons.<br>Please make sure to update any active addons after updating the billing information.');
};

/**
 * Update the product
 * @return void
 */
packagemanager.updateactivepanel = function(e)
{

    var valid = $('#productsettingsform').parsley( 'validate' );
    if(!valid) return;

    RichHTML.mask();

    // Enable any disabled select fields, so we still pass them with the POST
    var disabledFields = $('#productsettingsform').find('select:disabled').removeAttr('disabled');

    $.ajax({
        type: "POST",
        url: 'index.php?fuse=clients&action=adminupdateuserpackage',
        data: $('#productsettingsform').serializeArray(),
        success: function(xhr){
            var json = ce.parseResponse(xhr);

            if (json.error) {
                // Re-disable any select fields since we errored
                disabledFields.attr('disabled','disabled');
                RichHTML.unMask();
                return;
            }
            window.location.href = "index.php?fuse=clients&controller=userprofile&view=profileproduct&selectedtab="+packagemanager.activeTab+"&id="+packagemanager.package_id;
        },
        dataType: 'json'
    });
};


//perform a plugin action
/**
 * Perform action on plugin via server call to callpluginaction
 * @param  string pluginaction      action name to call to doaction on plugin
 * @param  string args              additional args to pass for this action
 * @return void
 */
packagemanager.plugincallaction = function(pluginaction,args) {

    //call special methods for actions if they exist
    var funcName = "pluginaction_"+pluginaction;

    if (eval("typeof " + funcName + " == 'function'")) {
        eval(funcName+"()");
    } else {

        //we only want to call if we are not calling another function
        //as that other function will handle when it wants to call the plugin action method
        if (args) {
            args = "";
        }

        RichHTML.msgBox(lang('Are you sure you want to run the <strong>'+pluginaction+'</strong> action using your plugin?'),
        {type:"yesno"}, function(result) {
            if(result.btn === lang("Yes")) {
                packagemanager.plugincallactionpost(pluginaction,args);
            }
        });

    }
};

/**
 * Should only be called only after confirmation box is presented to user confirming plugin action is desired
 * @param  string pluginaction      action name to call to doaction on plugin
 * @param  string args              additional args to pass for this action
 * @return void
 */
packagemanager.plugincallactionpost = function(pluginaction,args)
{
    RichHTML.mask();
    $.ajax({
        url: 'index.php?fuse=clients&action=callpluginaction',
        success: function(xhr) {
            var json = ce.parseResponse(xhr);
            packagemanager.showManageSettingByType(packagemanager.activeTab);
            packagemanager.updateAvailablePluginActions();
        },
        data: {
            id:packagemanager.package_id,
            actioncmd:pluginaction,
            additionalArgs: args
        }});
};

/**
 * Update the plugin drop down actions
 */
packagemanager.updateAvailablePluginActions = function()
{
    $('#divpluginactiondropdown').hide();
    $.ajax({
        type: 'GET',
        url: 'index.php?fuse=clients&action=getavailablepluginactions',
        data: { id:packagemanager.package_id },
        success: function(xhr) {
            var json = ce.parseResponse(xhr);

            $('#plugindropdown li').remove();
            $.each(json.pluginActions,function(index,object){
                $('#plugindropdown').append("<li><a onclick='packagemanager.plugincallaction(\""+object.cmd+"\");'>"+object.label+"</a></li>");
            });

            if (json.pluginActions.length === 0) {
                $('#divpluginactiondropdown').hide();
            } else {
                $('#divpluginactiondropdown').show();
            }
        },
        dataType: 'json'
    });
};

$(document).ready(function(){
    packagemanager.showlefttabsavailabletoproduct();
    $(".vtab").removeClass('active');

    if (gView == "profileproduct") {
        packagemanager.showManageSettingByType(packagemanager.activeTab);
        $('#btnUpdateProduct').bind('click',packagemanager.updateactivepanel);
    } else {
        //must be a snapin
        $('#product_plugin_'+packagemanager.activeTab).addClass("active");
    }
});