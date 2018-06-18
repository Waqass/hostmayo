$(document).ready(function(){
	//click override
	$('.accordion-toggle').click(function(e){
		$('#faq-accordion').find('[data-toggle=collapse]').addClass('collapsed');
	});
});
