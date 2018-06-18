$(document).ready(function(){

    $('#cancel-button').click(function() {
        window.location = 'index.php?fuse=clients&controller=products&view=products';
    });

     $('#submit-button').click(function(e){
        var valid = $('#cancel-form').parsley( 'validate' );
    });
});