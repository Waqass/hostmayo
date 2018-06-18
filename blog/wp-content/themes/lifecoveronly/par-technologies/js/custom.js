$(document).ready(function(){
	var agent = navigator.userAgent;
	var isIphone = ((agent.indexOf('iPhone') != -1) || (agent.indexOf('iPod') != -1)) ;
	if (isIphone) {
		if(browserTester('chrome')) {
			$('.main_left_part').css('width','58%');
		} else if(browserTester('safari')) {
			$('.main_left_part').css('width','58%');
		}
	}
	
	function browserTester(browserString) {
		return navigator.userAgent.toLowerCase().indexOf(browserString) > -1;
	}
});