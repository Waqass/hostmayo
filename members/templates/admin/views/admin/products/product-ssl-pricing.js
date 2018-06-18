productview.ssl_pricing_load = function() {
    $('#product-tab-content').load('index.php?nolog=1&fuse=admin&controller=products&view=pricingtabforssl&groupid='+productview.groupid+'&productid='+productview.productid, productview.postloadactions_sslpricing );
};

productview.postloadactions_sslpricing = function()
{
    $('.submit-pricing').click(function(e){
        e.preventDefault();
        var fielddata = $('#pricingtab').serializeArray();
        $.ajax({
            url: 'index.php?fuse=admin&tab=general&controller=products&action=saveproductpricingssl&packageId='+productview.productid+'&groupid='+productview.groupid,
            type: 'POST',
            data: fielddata,
            success : function(xhr) {
                json = ce.parseResponse(xhr);
            }
        });
    });
};