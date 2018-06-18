productaddon.newidcount = 1;
productaddon.product_type = null;
productaddon.old_product_groups = '';
productaddon.new_product_groups = '';

productaddon.clonenewoption = function()
{
    var newEl = $('.clone-addon-option').clone();
    newEl.removeClass('clone-addon-option');
    newEl.addClass('addon-option');
    newEl.attr('data-addon-id','0');
    newEl.attr('data-new-id',productaddon.newidcount++);
    newEl.show();
    $("#table-addonoptions").append(newEl);

    $("#table-addonoptions").tableDnD();

    $('.removeoption').unbind('click',productaddon.deleteaddonoption);
    $('.removeoption').bind('click',productaddon.deleteaddonoption);

};

productaddon.deleteaddonoption = function(e)
{
    if ($('.addon-option').length === 1){
        RichHTML.error("You can not delete all the options from an addon. You must have at least one option.");
    } else {
        if ($(this).data('candelete') != "1") {
            RichHTML.error("This option is in use. You can not delete it until you first change the following package to use another option: <br>" + $(this).data('candelete'));
        } else {
            $(this).closest('tr').remove();
        }
    }
};

productaddon.addaddonoption = function(e)
{
    $('.nooption').hide();
    productaddon.clonenewoption();
    productaddon.restrictPriceFormat();
};

productaddon.convertbooltoint = function(val)
{
    return (val) ? 1 : 0;
};

productaddon.load_plugin_variables = function()
{
    //let's build up plugin variable options
    $.get('index.php?fuse=admin&controller=addons&action=getaddonvariables&productType='+productaddon.grouptype,function(data){
        var selected = false;
        var div = 0;
        data = ce.parseResponse(data);
        $('#pluginoption').empty();
        $(data.addons).each(function(index,obj) {

            //let's set values for custom
            if (productaddon.pluginvar.indexOf("CUSTOM_") != -1) {
                $('#custompluginvariable_value').val(productaddon.pluginvar.replace("CUSTOM_",""));
                productaddon.pluginvar = "CUSTOM";
            }

            selected = (productaddon.pluginvar == obj.plugin_var) ? " selected='selected' " : "";
            if (obj.name == '---') {
                div++;
                $('#pluginoption').append('<option value="divider_'+div+'">---</option>');
            } else {
                $('#pluginoption').append('<option data-availablein="'+obj.available_in+'" data-description="'+obj.description+'" value="'+obj.plugin_var+'" '+selected+'>'+obj.name+'</option>');
            }

            $('#pluginoption').trigger('click');

        });
        $('#pluginoption').select2('val', productaddon.pluginvar);
    });
}

productaddon.reload_options = function(newGroupType)
{
    if(newGroupType == 3){

        $('.displayIfOther').each(function(a,row){
            $(this).hide();
        });

        $('.displayIfSSL').each(function(a,row){
            $(this).hide();
        });

        $('.displayIfDomain').each(function(a,row){
            $(this).show();
        });

        $('.displayIfDomainOrSSL').each(function(a,row){
            $(this).show();
        });

        $('.addon-option').each(function(a,row){
            $(this).find("input[name='price1']").val('');
            $(this).find("input[name='price1_force']").attr('checked', false);

            $(this).find("input[name='price3']").val('');
            $(this).find("input[name='price3_force']").attr('checked', false);

            $(this).find("input[name='price6']").val('');
            $(this).find("input[name='price6_force']").attr('checked', false);

            $(this).find("input[name='price12_force']").attr('checked', false);

            $(this).find("input[name='price24_force']").attr('checked', false);
        });
    }else{

        $('.displayIfOther').each(function(a,row){
            $(this).show();
        });

        $('.displayIfDomain').each(function(a,row){
            $(this).hide();
        });

        if(newGroupType == 2){
            $('.displayIfSSL').each(function(a,row){
                $(this).show();
            });
            $('.displayIfDomainOrSSL').each(function(a,row){
                $(this).show();
            });
        }else{
            $('.displayIfSSL').each(function(a,row){
                $(this).hide();
            });
            $('.displayIfDomainOrSSL').each(function(a,row){
                $(this).hide();
            });
        }

        $('.addon-option').each(function(a,row){
            $(this).find("input[name='price36']").val('');
            $(this).find("input[name='price48']").val('');
            $(this).find("input[name='price60']").val('');
            $(this).find("input[name='price72']").val('');
            $(this).find("input[name='price84']").val('');
            $(this).find("input[name='price96']").val('');
            $(this).find("input[name='price108']").val('');
            $(this).find("input[name='price120']").val('');
        });
    }
}

productaddon.restrictPriceFormat = function()
{
        $('.price').each(function() {
            $(this).keydown(function (e) {
                // Allow: backspace, delete, tab, escape, enter and .
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                     // Allow: Ctrl+A
                    (e.keyCode == 65 && e.ctrlKey === true) ||
                     // Allow: home, end, left, right
                    (e.keyCode >= 35 && e.keyCode <= 39)) {
                         // let it happen, don't do anything
                         return;
                }
                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
        });
    }

$(document).ready(function(){
    productaddon.old_product_groups = $("#product-groups").val();
    productaddon.new_product_groups = $("#product-groups").val();

    if (productaddon.pluginvar != "NONE") {
        $('.pluginvaluecell').show();
    }

    $("#table-addonoptions").tableDnD();

    $('#product-groups').select2({
        width: '100%',
        tokenSeparators: [',']
    });

    $('#product-groups').on("change", function(e) {
        productaddon.new_product_groups = $("#product-groups").val();
        if (e.added) {
            var label = $(e.added.element).closest("optgroup").data("grouptype");
            $('select').find('optgroup[data-grouptype!="'+label+'"]').remove();

            //Reload available cycles
            productaddon.reload_options(label);
            productaddon.grouptype = label;

            // if ( $('#pluginoption').val() == "NONE" || $('#pluginoption').val() == "CUSTOM" ) {
            if ( $('#pluginoption').val() == "NONE" ) {
                //we haven't loaded any custom vars for a specific type so let's load
                //the list
                productaddon.load_plugin_variables();
            } else {
                //let's not load the plugin variable dropdown since we already have something selected
                //which means that the list was already loaded
            }


        } else if (e.removed){
            //what to do when we remove all product types
            //we should check if plugins has been setup
            //if it hasn't allow showing all products
            if ( $(this).val() === null && $('#pluginoption').val() == "NONE") {
                //lets clear all options we have left and readd (we only had filtered by type and we want to include all)
                $('#product-groups optgroup').remove();
                $.each(productaddon.productgroups,function(index,value){
                    groupel = $('<optgroup value="0" data-grouptype="'+value[0].typeid+'" label="Groups of type:'+value[0].type+'"></optgroup>');
                    //echo "<optgroup value='0' data-grouptype='".$cat[0]['typeid']."'  label='Groups of type: ".$key."'>";
                    $.each(value,function(index2,value2){
                        groupel.append('<option value="'+value2.id+'">'+value2.name+'</option>');
                    });
                    $('#product-groups').append(groupel);
                });

                //let's reset plugin variable
                productaddon.grouptype = "";
                productaddon.load_plugin_variables();

            }
        }

    });

    productaddon.load_plugin_variables();

    if (productaddon.grouptype !== -1) {
        $('select').find('optgroup[data-grouptype!="'+productaddon.grouptype+'"]').remove();
    }

    $('#pluginoption').bind('click',function(e){

        $('#custompluginvariable').hide();
        $('#pluginvar_description').hide();
        $('#pluginvar_availablein').hide();

        if ($('#pluginoption').val() == "CUSTOM") {
            $('#custompluginvariable').show();
            $('.pluginvaluecell').show();
        } else if ($('#pluginoption').val() == "NONE") {
            $('.pluginvaluecell').hide();
        } else if ($('#pluginoption').val().indexOf('divider_') != -1) {
            $('#pluginoption').select2("val", "NONE");
            $('.pluginvaluecell').hide();
        } else {
            if ($(this).find('option:selected').data('availablein') !== "") {
                $('#pluginvar_description').text($(this).find('option:selected').data('description'));
                $('#pluginvar_availablein').text("Supported Plugins: " + $(this).find('option:selected').data('availablein'));
            } else {
                $('#pluginvar_description').text("");
                $('#pluginvar_availablein').html("<span style='color:red;'>Not integrated with any plugin.</span><br/>It is recommended that at least one option for this addon is set to 'Open Ticket'.</span>");
            }
            $('#pluginvar_description').show();
            $('#pluginvar_availablein').show();
            $('.pluginvaluecell').show();
        }

    });

    $('.add-product-addon').bind('click',productaddon.addaddonoption);

    $('.removeoption').bind('click',productaddon.deleteaddonoption);

    $('.submit-addoon').bind('click',function(e){
        var pass = true;

        if(!$(".nav-pills li:first").hasClass("active")){
            $(".default-language-tab").click();
        }
        if(!$('#mainlanguageproduct-addon-name').valid()){
            pass = false;
        }
        if(!$('#mainlanguageproduct-addon-description').valid()){
            pass = false;
        }
        $('.addon-option').each(function(a,row){
            if( typeof $(this).find("input[name='optionname']").val() !== "undefined" ){
                if(!$(this).find("input[name='optionname']").valid()){
                    pass = false;
                }
            }else{
                $(this).find(".language.mainlanguageoptionname").each(function(a,row){
                    if(!$(this).find("input").valid()){
                        pass = false;
                    }
                });
            }
        });

        if (!pass) {
            return false;
        }

        RichHTML.mask();
        e.preventDefault();

        if ($('.addon-option').length === 0){
            RichHTML.error("You must have at least one addon option before saving.");
            return;
        }

        $('.submit-addoon').addClass("disabled");

        var sortKey = 0;
        var addonpricing = [];
        var addonoption = {};
        //lets get prices
        $('.addon-option').each(function(a,row){
            addonoption = {};
            addonoption.id = $(this).data('addon-id');

            if( typeof $(this).find("input[name='optionname']").val() !== "undefined" ){
                addonoption.detail = $(this).find("input[name='optionname']").val();
            }else{
                $(this).find(".language").each(function(a,row){
                    var fieldName = $(this).find("input").attr("name");
                    var fieldValue = $(this).find("input").val();
                    addonoption[fieldName] = fieldValue;
                });
            }

            addonoption.price0 = $(this).find("input[name='price0']").val();

            addonoption.price1 = $(this).find("input[name='price1']").val();
            addonoption.price1Force = productaddon.convertbooltoint($(this).find("input[name='price1_force']").is(':checked'));

            addonoption.price3 = $(this).find("input[name='price3']").val();
            addonoption.price3Force = productaddon.convertbooltoint($(this).find("input[name='price3_force']").is(':checked'));

            addonoption.price6 = $(this).find("input[name='price6']").val();
            addonoption.price6Force = productaddon.convertbooltoint($(this).find("input[name='price6_force']").is(':checked'));

            addonoption.price12 = $(this).find("input[name='price12']").val();
            addonoption.price12Force = productaddon.convertbooltoint($(this).find("input[name='price12_force']").is(':checked'));

            addonoption.price24 = $(this).find("input[name='price24']").val();
            addonoption.price24Force = productaddon.convertbooltoint($(this).find("input[name='price24_force']").is(':checked'));

            addonoption.price36 = $(this).find("input[name='price36']").val();
            addonoption.price36Force = productaddon.convertbooltoint($(this).find("input[name='price36_force']").is(':checked'));

            addonoption.price48 = $(this).find("input[name='price48']").val();
            addonoption.price48Force = productaddon.convertbooltoint($(this).find("input[name='price48_force']").is(':checked'));

            addonoption.price60 = $(this).find("input[name='price60']").val();
            addonoption.price60Force = productaddon.convertbooltoint($(this).find("input[name='price60_force']").is(':checked'));

            addonoption.price72 = $(this).find("input[name='price72']").val();
            addonoption.price72Force = productaddon.convertbooltoint($(this).find("input[name='price72_force']").is(':checked'));

            addonoption.price84 = $(this).find("input[name='price84']").val();
            addonoption.price84Force = productaddon.convertbooltoint($(this).find("input[name='price84_force']").is(':checked'));

            addonoption.price96 = $(this).find("input[name='price96']").val();
            addonoption.price96Force = productaddon.convertbooltoint($(this).find("input[name='price96_force']").is(':checked'));

            addonoption.price108 = $(this).find("input[name='price108']").val();
            addonoption.price108Force = productaddon.convertbooltoint($(this).find("input[name='price108_force']").is(':checked'));

            addonoption.price120 = $(this).find("input[name='price120']").val();
            addonoption.price120Force = productaddon.convertbooltoint($(this).find("input[name='price120_force']").is(':checked'));

            addonoption.pluginVarValue = $(this).find("input[name='plugin_var_value']").val();
            addonoption.sortKey = sortKey++;
            addonoption.openticket = productaddon.convertbooltoint($(this).find("input[name='openticket']").is(':checked'));
            addonoption.newid = $(this).data('new-id');
            addonpricing.push(addonoption);

        });

        var fielddata = {
            id: productaddon.id,
            addonpricing: addonpricing,
            pluginoption: $('#pluginoption').val(),
            custompluginvariable_value: $('#custompluginvariable_value').val(),
            productgroups: $("#product-groups").val()
        };


        $('.language.text.name').each(function(a,row){
            var fieldName = $(this).find("input").attr("name");
            var fieldValue = $(this).find("input").val();
            fielddata[fieldName] = fieldValue;
        });

        $('.language.textarea').each(function(a,row){
            var fieldName = $(this).find("textarea").attr("name");
            var fieldValue = $(this).find("textarea").getCode();

            //redactor uses this for empty descriptions sometimes
            if (fieldValue == "<p><br></p>") fieldValue = "";

            fielddata[fieldName] = fieldValue;
        });

        var all_old_are_new_product_groups = true;
        if(productaddon.old_product_groups !== null && productaddon.old_product_groups.length > 0){
            all_old_are_new_product_groups = productaddon.old_product_groups.every(
                function (val){
                    if(productaddon.new_product_groups !== null && productaddon.new_product_groups.length > 0){
                        return productaddon.new_product_groups.indexOf(val) >= 0;
                    }else{
                        return false;
                    }
                }
            );
        }

        if(!all_old_are_new_product_groups){
            e.preventDefault();
            RichHTML.msgBox('<b>'+lang('NOTICE:')+'</b>'+'<br><br>'+lang('You have modified the availability to some Product Groups.')+'<br>'+lang('Do you want existing customers to continue being charged this addon?'),
                {type:"confirm"}, function(result) {
                    if(result.btn === lang("Yes")) {
                        fielddata['keeprecurringfees'] = 1;
                    }else if(result.btn === lang("No")) {
                        fielddata['keeprecurringfees'] = 0;
                    }else{
                        RichHTML.unMask();
                        $('.submit-addoon').removeClass("disabled");
                        return false;
                    }

                    $.post('index.php?fuse=admin&controller=addons&action=saveproductaddon', fielddata,
                        function(data){
                            data = ce.parseResponse(data);
                            if (!data.error) {
                                // no error so redirect to addons list
                                window.location = 'index.php?fuse=admin&controller=addons&view=productaddons&groupid=' + $("#product-groups").val();
                            }
                            RichHTML.unMask();
                            $('.submit-addoon').removeClass("disabled");
                        }
                    );
                });
        }else{
            $.post('index.php?fuse=admin&controller=addons&action=saveproductaddon', fielddata,
                function(data){
                    data = ce.parseResponse(data);
                    if (!data.error) {
                        // no error so redirect to addons list
                        window.location = 'index.php?fuse=admin&controller=addons&view=productaddons&groupid=' + $("#product-groups").val();
                    }
                    RichHTML.unMask();
                    $('.submit-addoon').removeClass("disabled");
                }
            );
        }
    });

    productaddon.restrictPriceFormat();
});