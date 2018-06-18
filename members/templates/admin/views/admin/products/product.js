var productview = productview || {};

$(document).ready(function(){
    $('ul.productnav').append('<li><span data-type="all-general" data-toggle="tab"><a href="#">General</a></span></li>');
    if (productview.productid > 0) {
      productview.addtabs(productview.producttype);
    } else {
        //the js files with all- prefix is for all product types
        $('ul.productnav li span').bind('click',productview.bindproducttabs);
    }
    //trigger general
    $('ul.productnav li span[data-type="all-general"]').trigger('click');

});


productview.bindproducttabs = function(e)
{
    var self = this;
    e.preventDefault();
    $(self).tab('show');
    $('#product-tab-content').html("<img class='content-loading' src='../images/loader.gif' />");
    ce.lazyLoad('../templates/admin/views/admin/products/product-'+$(self).attr('data-type')+'.js', function () {
        var fn = "productview."+$(self).attr('data-type')+"_load";
        fn=fn.replace("-","_");
        eval(fn + '()');
    });
};

productview.addtabs = function(packagegrouptype)
{

    //let's add tabs based on type
    switch(packagegrouptype)
    {
        case 0:
            //General
            $('ul.productnav').append('<li><span data-type="general-pricing" data-toggle="tab"><a href="#">'+lang('Pricing')+'</a></span></li>');
            $('ul.productnav').append('<li><span data-type="all-addons" data-toggle="tab"><a href="#">'+lang('Addons')+'</a></span></li>');
            break;
        case 1:
            //hosting
            $('ul.productnav').append('<li><span data-type="general-pricing" data-toggle="tab"><a href="#">'+lang('Pricing')+'</a></span></li>');
            $('ul.productnav').append('<li><span data-type="all-addons" data-toggle="tab"><a href="#">'+lang('Addons')+'</a></span></li>');
            $('ul.productnav').append('<li><span data-type="hosting-advanced" data-toggle="tab"><a href="#">'+lang('Advanced & Plugin Settings')+'</a></span></li>');
            break;
        case 2:
            //ssl
            $('ul.productnav').append('<li><span data-type="ssl-pricing" data-toggle="tab"><a href="#">'+lang('Pricing')+'</a></span></li>');
            $('ul.productnav').append('<li><span data-type="all-addons" data-toggle="tab"><a href="#">'+lang('Addons')+'</a></span></li>');
            $('ul.productnav').append('<li><span data-type="ssl-advanced" data-toggle="tab"><a href="#">'+lang('Advanced & Plugin Settings')+'</a></span></li>');
            break;
        case 3:
            //domains
            $('ul.productnav').append('<li><span data-type="domains-pricing" data-toggle="tab"><a href="#">'+lang('Pricing')+'</a></span></li>');
            $('ul.productnav').append('<li><span data-type="all-addons" data-toggle="tab"><a href="#">'+lang('Addons')+'</a></span></li>');
            $('ul.productnav').append('<li><span data-type="domains-advanced" data-toggle="tab"><a href="#">'+lang('Advanced & Plugin Settings')+'</a></span></li>');
            break;
    }

    $('ul.productnav li span').unbind('click',productview.bindproducttabs);
    $('ul.productnav li span').bind('click',productview.bindproducttabs);

};
