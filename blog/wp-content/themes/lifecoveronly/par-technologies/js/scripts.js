function format(comma, period) {
	  comma = comma || ',';
	  period = period || '.';
	  var split = this.toString().split('.');
	  var numeric = split[0];
	  var decimal = split.length > 1 ? period + split[1] : '';
	  var reg = /(\d+)(\d{3})/;
	  while (reg.test(numeric)) {
	    numeric = numeric.replace(reg, '$1' + comma + '$2');
	  }
	  return numeric + decimal;
	}

$(document).ready(function () {

	var oldval;
	$('#value').live('keyup', function(){
	    var newval = format.call($(this).val().split(',').join(''),',','.');
	    if(newval != oldval) {
	        $(this).val(newval);
	        oldval = newval;
	    }
	});
	
	// update links to they popup
	$("#contact").attr("href","javascript:void(0)");
	$("#privacy").attr("href","javascript:void(0)");
	$("#terms").attr("href","javascript:void(0)");

	$('.find').css("visibility","visible");
							
	$('#find_postcode').click(function(){
		if($('#postcode').val().length<1) {
			alert("Please enter a valid UK postcode to proceed.");
			return false;
		} else {
			$.colorbox({href:"/ajax/postcode.php?pc=" + encodeURI($('#postcode').val())});
			return false;
		}
	}); 
	
	$('#form-button').click(function() {
		$('#quote-form').submit();
	});
	
	$('.popup-wrapper').hide();
	$('.faq-reveal').click(function () {
		var $this = $(this);
		var $faqText = $('.faq-text');
		// The text that is hidden or visible
		var $thisWrapper = $this.parents('li').find('.faq-text');
		// Boolean: True if the text is in its expanded state
		var $isVisible = $thisWrapper.is(':visible');
		// We will set the opacity & text
		// at the end of this function
		var opacity = ($isVisible) ? 0 : 1;
		if ($isVisible) {
			$thisWrapper.slideUp(100);
			$this.removeClass('active');
		} else {
			$faqText.slideUp(100);
			$('.faq-reveal').removeClass('active');
			$thisWrapper.slideDown(100);
			$this.addClass('active');
		}
		// Set text & opacity
		$thisWrapper.animate({
			opacity: opacity
		}, 1);
	});
	var $colHeight = ($('.leftCol').height()) - 215 + 'px';
	$('.rightCol').css('height', $colHeight);
	var $popupWrapper = $('.popup-wrapper');
	var $faqText = $('.faq-text');
	$('#privacy').click(function () {
		if ($('.privacy.popup-wrapper').is(':visible')) {
			$('.privacy.popup-wrapper').fadeOut(500);
		} else {
			$popupWrapper.fadeOut(500);
			$faqText.slideUp(100);
			$('.faq-reveal').removeClass('active');
			$('.privacy.popup-wrapper').fadeIn(500);
		}
	});
	$('#contact').click(function () {
		if ($('.contact.popup-wrapper').is(':visible')) {
			$('.contact.popup-wrapper').fadeOut(500);
		} else {
			$popupWrapper.fadeOut(500);
			$faqText.slideUp(100);
			$('.faq-reveal').removeClass('active');
			$('.contact.popup-wrapper').fadeIn(500);
		}
	});
	$('#terms').click(function () {
		if ($('.terms.popup-wrapper').is(':visible')) {
			$('.terms.popup-wrapper').fadeOut(500);
		} else {
			$popupWrapper.fadeOut(500);
			$faqText.slideUp(100);
			$('.faq-reveal').removeClass('active');
			$('.terms.popup-wrapper').fadeIn(500);
		}
	});
	$('.close-button').click(function () {
		$popupWrapper.fadeOut(500);
		$('.popup-wrapper').removeClass('active');
	});
    $(".select").selectbox();
    $( ".radio" ).buttonset();
    $(".tooltip-help").tooltip({position: {collision: "fit", my: "bottom", at: "top-15"}});
    $('.dob').find('.sbHolder:first-child').addClass('dob-selector');
});