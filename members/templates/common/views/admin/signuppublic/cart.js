cart = {};
cart.toggleproductaccordion = function(json)
{
	$('.selectedproduct-configuration-accordion').find('[data-toggle=collapse]').addClass('collapsed');
}

$(document).ready(function(){
	//click override
	$('.accordion-toggle').unbind('click',cart.toggleproductaccordion);
	$('.accordion-toggle').bind('click',cart.toggleproductaccordion);

	$('.btn-gotosetp2-submit').bind('click',function(e){
		e.preventDefault();
		paymentterm = $('.priceTerm_'+$(this).attr('data-package-id')+':checked').val();
		if(typeof paymentterm != "undefined"){
		    location.href = $(this).attr('href')+"&paymentterm="+paymentterm;
		}else{
		    location.href = $(this).attr('href');
		}
	});

	$('.priceTerm').bind('click',function(e) {
		var term = $(this).val();
		var productid = $(this).attr('data-package-id');

    var mainClass;
    if (js_pricing["term_"+productid+"_"+term].price_raw[1] < 1000) {
      mainClass = 'pricesBig';
    } else if (js_pricing["term_"+productid+"_"+term].price_raw[1] < 10000) {
      mainClass = 'pricesMedium';
    } else if (js_pricing["term_"+productid+"_"+term].price_raw[1] < 100000) {
      mainClass = 'pricesSmall';
    } else {
      mainClass = 'pricesXSmall';
    }
    $('.item_package_'+productid+' #pricesWrapper').removeClass('pricesBig pricesMedium pricesSmall pricesXSmall').addClass(mainClass);

		paymentterm = $('.item_package_'+productid+' .priceTerm_'+$(this).attr('data-package-id')+':checked').val();
		$('.item_package_'+productid+' .head_pricing').html(js_pricing["term_"+productid+"_"+term].term);
		$('.item_package_'+productid+' .price_large').html("<em>"+js_pricing["term_"+productid+"_"+term].price_raw[0]+"</em><div class='price_large_num'>"+js_pricing["term_"+productid+"_"+term].price_raw[1]+"</div>");
		$('.item_package_'+productid+' .price_small').html(js_pricing["term_"+productid+"_"+term].price_raw[2]);

	});
});

cart.get_package_customfields = function(fields)
{
	customFields.load(fields,function(data) {
        $('.customfields-wrapper').append(data);
    }, function(){
        clientexec.postpageload('.customfields-wrapper');
        $('.searching-customfields').remove();
        $('.cart2-continue-button').removeAttr('disabled');
        //RichHTML.unMask();
    });
}
