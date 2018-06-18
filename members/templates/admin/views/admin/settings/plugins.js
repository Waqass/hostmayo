$(document).ready(function() {
    $('.gatewaydefault').bind('click',function(){

        $.ajax({
            url: "index.php?fuse=admin&action=MakeGatewayDefault",
            dataType: 'json',
            data: {gateway:$(this).attr('data-gateway')},
            success: function(json) {
                if (json.error) {
                    ce.msg(json.message);                    
                } else {
                    $('#vtabsBar .vtab.active').append($('#vtabsBar .vtab span.when'));
                    ce.msg(lang('Gateway updated properly'));
                    $('.gatewaydefault').html(lang('Already selected'));
                    $('.gatewaydefault').removeClass('link').unbind('click');
                }
            }
        });

    });
});