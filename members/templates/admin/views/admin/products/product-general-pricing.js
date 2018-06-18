productview.general_pricing_load = function() {
    $('#product-tab-content').load('index.php?nolog=1&fuse=admin&controller=products&view=pricingtabforgeneral&groupid='+productview.groupid+'&productid='+productview.productid, productview.postloadactions_generalpricing );
};

productview.postloadactions_generalpricing = function()
{
    $('.submit-pricing').click(function(e){
        e.preventDefault();
        var fielddata = $('#pricingtab').serializeArray();
        $.ajax({
            url: 'index.php?fuse=admin&tab=general&controller=products&action=saveproductpricing&packageId='+productview.productid+'&groupid='+productview.groupid,
            type: 'POST',
            data: fielddata,
            success : function(xhr) {
                json = ce.parseResponse(xhr);
            }
        });

    });
};