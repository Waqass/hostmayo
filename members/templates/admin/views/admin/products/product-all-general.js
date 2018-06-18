productview.all_general_load = function() {
    $('#product-tab-content').load('index.php?nolog=1&fuse=admin&controller=products&view=generaltab&groupid='+productview.groupid+'&productid='+productview.productid, productview.postloadactions_general);
};

productview.postloadactions_general = function(e)
{
    clientexec.postpageload('#product-tab-content');
    $("#bundledproducts").select2({formatSelection: function(item){
            var name = item.text;
            if (productview.bundleProducts.optionalIds.indexOf(item.id) >= 0 ) {
                name = name + " (optional)";
            }
            return "<span class='select2-bundledoption' data-id='"+item.id+"'>"+name+"</span>";
        }
    });
    $("#bundledproducts").on("change", function(e) {
        //let's ask if we want this to be an optional bundled product
        //productview.askifbundleisoptional(e);
    });
    $('#mainlanguageproductname').focus();
    $('#stockEnable').click(function(){
        $('.stockcontrolswitch').toggle();
    });
    $('#bundledomain').click(function(){
        $('.bundle_domains_fields').toggle();
        if ($('.bundle_domains_fields:visible').length == 0) {
            $("#domain_bundles_dropdown").select2("val", "0");
        }
    });
    $('#bundlessl').click(function(){
        $('.bundle_ssl_fields').toggle();
        if ($('.bundle_ssl_fields:visible').length == 0) {
            $("#ssl_bundles_dropdown").select2("val", "0");
        }
    });

    $('.submit-general').click(function(e){
        e.preventDefault();

        var pass = true;

        //if type = 3 we want to make sure first char is not a period
        if (productview.productType == 3) {
            if (trim($('#mainlanguageproductname').val()).charAt(0) == "." ) {
                $('#mainlanguageproductname').val($('#mainlanguageproductname').val().substr(1));
            }
            if(!$('#mainlanguageproductname').valid()){
                pass = false;
            }
        }else{
            if(!$(".nav-pills li:first").hasClass("active")){
                $(".default-language-tab").click();
            }

            if(!$('#mainlanguageproductname').valid()){
                pass = false;
            }
            if(!$('#mainlanguagecontent').valid()){
                pass = false;
            }
        }

        if (!pass) {
            return false;
        }

        var fielddata = $('#generaltab').serializeArray();
        fielddata.push({name: 'bundledOptionalIds', value:productview.bundleProducts.optionalIds});
        fielddata.push({name: 'producttype', value: productview.producttype});

        $.ajax({
            url: 'index.php?fuse=admin&tab=general&controller=products&action=saveproduct&packageId='+productview.productid+'&groupid='+productview.groupid,
            type: 'POST',
            data: fielddata,
            success : function(xhr) {
                json = ce.parseResponse(xhr);

                //let's readd the additional tabs we need if
                //we just saved a new product
                if (json.isnew) {
                    productview.productid = json.packageId;
                    $('ul.productnav li span').unbind('click',productview.bindproducttabs);
                    productview.addtabs(productview.producttype);
                    //let's reload main panel
                    $('ul.productnav li span[data-type="all-general"]').trigger('click');
                    History.pushState({}, "", window.location.href.toString()+"&id="+json.packageId);
                }
            }
        });

    });
};

productview.askifbundleisoptional = function(e)
{

    if (e.added) {
        RichHTML.alert('Is this an optional bundled product?<br/>Clicking Yes allows customer to skip this product.','', function(ret) {
            var optional = false;
            var el = $('.select2-bundledoption[data-id="'+e.added.id+'"]');
            if (ret.btn == lang('Yes')){
                optional = true;
                el.text(el.text()+" (optional)");
            }

            //lets see if this was in our bundledProducts array if optional
            //lets remove this item from bundledoptional array and readd if optional was selected
            if (productview.bundleProducts.optionalIds.indexOf(e.added.id) >= 0 ) {
                productview.bundleProducts.optionalIds.splice(productview.bundleProducts.optionalIds.indexOf(e.added.id), 1);
            }

            //let's readd if optional
            if (optional) {
                productview.bundleProducts.optionalIds.push(e.added.id);
            }
        });
    } else if (e.removed) {
        //let's remove this id from optional if it existed in the optionalids array
        if (productview.bundleProducts.optionalIds.indexOf(e.removed.id) >= 0 ) {
            productview.bundleProducts.optionalIds.splice(productview.bundleProducts.optionalIds.indexOf(e.removed.id), 1);
        }
    }

};
