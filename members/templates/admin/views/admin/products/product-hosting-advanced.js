productview.hosting_advanced_load = function() {
    $('#product-tab-content').load('index.php?nolog=1&fuse=admin&controller=products&view=advancedtabforhosting&groupid='+productview.groupid+'&productid='+productview.productid, productview.postloadactions_hosting_advanced );
};

productview.postloadactions_hosting_advanced = function()
{

    clientexec.postpageload('.serverselectionfield');

    $('.submit-hosting-advanced').click(function(e){
        e.preventDefault();
        var fielddata = $('#advancedtab').serializeArray();
        $.ajax({
            url: 'index.php?fuse=admin&tab=general&controller=products&action=saveadvancedforhosting&productid='+productview.productid+'&groupid='+productview.groupid,
            type: 'POST',
            data: {pluginFieldData: fielddata, servers: $("#servers").val()},
            success : function(xhr) {
                json = ce.parseResponse(xhr);
            }
        });

    });

    $("#servers").on("change", function(e) {
        //let's ask if we want this to be an optional bundled product
        productview.checkifsameproducttype(e);
    });

    //let's load any fields for selected plugins
    if (trim(productview.hosting.pluginid) !== "") {
        productview.getserverpluginoptions(productview.hosting.pluginid);
        if (productview.hosting.servers.length > 1) $('.multipleservers').show();
    }

};

/**
 * get all the server plugin options for a given pluginid
 * @param  int pluginid name of plugin cpane, plesk ,etc
 * @return void
 */
productview.getserverpluginoptions = function(pluginid)
{
    $.ajax({
        url: 'index.php?fuse=admin&tab=general&controller=products&action=getproductpluginoptions&productid='+productview.productid+'&groupid='+productview.groupid,
        type: 'POST',
        data: {pluginid: pluginid, serverids: $("#servers").val()},
        success : function(xhr) {
            json = ce.parseResponse(xhr);

            if (json.data.length === 0) {
                $('.nooption').show();
                return;
            }

            //we need to add a divider .. this is just ui
            var clone = $('.rowtoclone-fieldset').clone();
            clone.removeClass('donotdelete').removeClass('rowtoclone-fieldset').show();
            clone.html("<hr/><span style='position: relative;top: -16px;' class='label label-info'>Plugin options available for the selected servers</span>");
            $('.lastadvancedoptionadded').after(clone);
            //remove this class name to add to last item added
            $('.lastadvancedoptionadded').removeClass('lastadvancedoptionadded');
            clone.addClass('lastadvancedoptionadded');

            //let's add the new fields
            $.each( json.data, function( key, value ) {
                productview.clonefieldandaddmetdata(value);
            });
            $.each($('tr'), function(index, value) {
                var attr = $(this).attr('isreseller');
                if (typeof attr !== 'undefined' && attr !== false){
                    if(attr == 1){
                        togglereseller();
                    }
                }
            });

            clientexec.postpageload('#product-tab-content:not(".donotdelete")');
        }
    });
};

/**
 * Clones a field by data values
 * @param  array data array containing all values necessary to clone a data field
 * @return void
 */
productview.clonefieldandaddmetdata = function(data)
{
    var label, clone = $('.rowtoclone-'+data.type).clone();
    clone.removeClass('donotdelete').removeClass('rowtoclone-'+data.type);

    if (typeof data.toggle !== 'undefined' && data.toggle !== false){
        clone.attr('toggle',data.toggle);
    }else{
        clone.show();
    }

    if (data.type === "text" || data.type ==="password") {
        clone.find('input').attr('id',data.name).attr('name',data.name).attr('value',data.value);
    } else if (data.type === "check" || data.type === "yesno") {
        // Checkboxes value needs to be false not 0, for un-checked
        clone.find('input').attr('id',data.name).attr('name',data.name);

        if ( data.value == '1' ) {
            clone.find('input').attr('checked','checked');
        }

        if (typeof data.isreseller !== 'undefined' && data.isreseller !== false){
            clone.change(function() {
                togglereseller();
            });
            if ( data.value == '1' ) {
                clone.attr('isreseller',1);
            }
        }

    } else if (data.type == "dropdown") {
        clone.find('select').attr('id',data.name).attr('name',data.name);
        $.each(data.dropdownoptions, function(key, value) {
            if (data.value == value[0]) selected = "selected";
            else selected = "";
            clone.find('select')
                .append($("<option "+selected+"></option>")
                .attr("value",value[0])
                .text(value[1]));
        });
    }

    //lets add a label
    label = clone.find('.clonelabel');
    label.text(data.label);
    //let's add description as tooltip
    if ( data.description && (trim(data.description) !== "") ) {
        label.addClass('tip-target');
        label.attr('data-placement','right');
        label.attr('title',data.description);
        label.tooltip();
    }
    $('.lastadvancedoptionadded').after(clone);

    //remove this class name to add to last item added
    $('.lastadvancedoptionadded').removeClass('lastadvancedoptionadded');
    clone.addClass('lastadvancedoptionadded');
};

/**
 * Get's plugin names of existing servers so that we don't add different plugins
 * @param  serverid id
 * @return pluginname
 */
productview.getpluginnameforserverid = function(id)
{
    var pluginname = "nopluginfound";
    $.each(productview.hosting.servers,function(index,value){
        if (value.serverid == id) {
            pluginname = value.plugin;
        }
    });

    if (pluginname === "nopluginfound") {
        alert('error server not found');
    }

   return pluginname;
};


//check when selecting server that we only select a server that
//has the same control panel of others selected
productview.checkifsameproducttype = function(e)
{
    var plugin;
    var resetservers = false;
    var servers = [];
    var existingplugin = "";
    //let's see what control panel's we have selected already

    if ( (e.val.length > 1) && (e.added) ) {

        plugin = productview.getpluginnameforserverid(e.added.id);
        $.each(e.val,function(index,value){
            if (value != e.added.id) {
                //let's check plugins
                existingplugin = productview.getpluginnameforserverid(value);
                if (!resetservers && existingplugin != plugin) {
                    RichHTML.msgBox('Your server\'s plugin setting need to match.  You will need to clear all servers with plugin: '+ existingplugin +' to add that server',{type:'error'});
                    resetservers = true;
                }
                servers.push(value);
            }
        });

        if (resetservers) {
            //let's reset server since the new addition didn't match
            $("#servers").select2("val", servers);
        } else {
            //let's not reload the
            //productview.getserverpluginoptions(existingplugin);
            $('.multipleservers').show();
        }

    } else if ( (e.val.length === 1) && (e.added) ) {

        servers = $("#servers").select2("val");
        existingplugin = productview.getpluginnameforserverid(servers[0]);
        productview.getserverpluginoptions(existingplugin);

        $('.multipleservers').hide();

    } else if (e.removed && e.val.length === 0) {

        $('tr:not(".donotdelete")').remove();
        $($('tr.donotdelete')[2]).addClass('lastadvancedoptionadded');
        $('.nooption').hide();

    } else if ( (e.val.length === 1) && (e.removed) ) {

        //hide when removing
        $('.multipleservers').hide();
    }



};

