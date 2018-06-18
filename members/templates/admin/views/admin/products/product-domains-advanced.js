productview.domains_advanced_load = function() {
    $('#product-tab-content').load('index.php?nolog=1&fuse=admin&controller=products&view=advancedtabfordomains&groupid='+productview.groupid+'&productid='+productview.productid, productview.postloadactions_domains_advanced);
};

productview.postloadactions_domains_advanced = function()
{
    $('#adv_enableNamesuggest').click(function(){
        $('.namesuggestswitch').toggle();
    });

    $('.submit-advancedfordomain').click(function(e){
        e.preventDefault();
        $.ajax({
            url: 'index.php?fuse=admin&tab=general&controller=products&action=savedomainadvanced&productid='+productview.productid+'&groupid='+productview.groupid,
            type: 'POST',
            data: $('#domainadvancedtab').serializeArray(),
            success : function(xhr) {
                json = ce.parseResponse(xhr);
                if (!json.error) {
                    $('ul.productnav li span[data-type="domains-advanced"]').trigger('click');
                }
            }
        });
    });

};