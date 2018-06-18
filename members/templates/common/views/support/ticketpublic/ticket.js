ticketview = ticketview || {};
$(document).ready(function(){
    $('.btn-reply').bind('click',function(){
        var status = $(this).attr('data-status');
        $('input[name="ticketstatus"]').val(status);

        var valid = $('.frm-ticket').parsley( 'validate' );
        if (valid) $('.frm-ticket').submit();
    });

    $('input[type=file]').bootstrapFileInput();
    $('input[type=file]').change(submitticket.selectedFile);

    $('#new-file-button').bind('click',function(e){
        e.preventDefault();
        submitticket.cloneAttchInput();
    });

    submitticket.cloneAttchInput();
    ticketview.loadCustomFields();
});

$('#close-ticket-button').bind('click', function() {
    window.location = closeTicketURL;
});

submitticket.removeFileField = function (id) {
    document.getElementById(id).parentNode.parentNode.removeChild(document.getElementById(id).parentNode);
    submitticket.uploadFieldCount--;
};

ticketview.filterBy = function(el,filter) {
    $(el).closest('.nav-pills').find('li').removeClass('active')
    $(el).closest('li').addClass('active');
    if (filter == "messages") {
        $('.ticket-log, .frm-ticket').show();
        $('.ticket-custom-fields').hide();
    } else {
        $('.ticket-log, .frm-ticket').hide();
        $('.ticket-custom-fields').show();
    }
}

/**
 * Updates the custom fields
 * @return void
 */
ticketview.updatecustomfields = function()
{
    $('#ticketCustomFieldsForm').parsley( 'validate' );
    $.post('index.php?fuse=support&controller=ticket&action=savecustomfields',{
        ticketId: ticketview.ticket_id,
        customfields: $('#ticketCustomFieldsForm').serializeArray()
    },function(t) {
        data = ce.parseResponse(t);
    });
};

ticketview.loadCustomFields = function() {

    $('#ticketCustomFieldsForm').empty();

    $.getJSON('index.php?fuse=support&controller=ticket&action=getticketcustomfields',
        {
            ticketId: ticketview.ticket_id
        },function(data){
            data = ce.parseResponse(data);
            if (data.count > 0) {
                $('.ticket-nav-tabs').show();
            }

            customFields.load(data.fields,function(data) {
                $('#ticketCustomFieldsForm').append(data);
            }, function(){
                clientexec.postpageload('.ticket-active-tab');
            });

            if (data.fields.length > 0) {
                //check to see if all fields are disabled... if so remove update btn
                if (customFields.getAllFieldsDisabled()){
                    $('#ticketCustomFieldsSubmit').hide();
                } else {
                    $('#ticketCustomFieldsSubmit').show();
                    $('#ticketCustomFieldsSubmit').unbind('click');
                    $('#ticketCustomFieldsSubmit').bind('click',ticketview.updatecustomfields);
                }

                // $.each(data.fields, function(key, value) {
                //   if (value.value) {
                //     $('#tickettab_customfields_tab sup').css('visibility', 'visible');
                //     return false;
                //   }
                // });
            }

    });


};