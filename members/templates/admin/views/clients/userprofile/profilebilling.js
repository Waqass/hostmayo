$().ready(function(){

    $('#btnUpdate').bind('click',function(e){
        e.preventDefault();
        if (checkCreditBalance($("#customerdata")[0])) {
            var data = $("#customerdata").serializeArray();
            $.post("index.php?fuse=clients&controller=userprofile&action=updateprofilebilling", data, function(xhr){
                json = ce.parseResponse(xhr);
                if (!json.error) window.location.href = "index.php?fuse=clients&controller=userprofile&view=profilebilling";
            });
        }
        return false;
    });

    if ($('#viewcclink').length > 0) {
        $('#viewcclink').click(function(){
            RichHTML.msgBox('Enter your passphrase:',
            {type:'prompt',password:true},
            function(result){
                if(result.btn === lang("OK")) {
                    var mywin = window.open('','cewindow','scrollbars=no,menubar=no,location=no,width=200,height=10,resizable=no');
                    var passphrase = encodeURIComponent(result.elements.value);
                    mywin.location.href = "index.php?fuse=billing&controller=creditcard&pp=" + passphrase + "&view=viewccnumber";
                    mywin.focus('',300);
                }
            });
        });
    }

    if ($('#btnDeletecc').length > 0) {
        $('#btnDeletecc').click(function(){
            RichHTML.msgBox('Are you sure you want to delete the credit card on file?',{type:'yesno'},function(result){
                if(result.btn === lang("Yes")) {
                    RichHTML.mask();
                    window.location = "index.php?fuse=clients&controller=userprofile&view=profilebilling&deleteCCNumber=true";
                }
            });
        });
    }

    if ($('#btnValidatecc').length > 0) {
        $('#btnValidatecc').click(function(){
            RichHTML.msgBox('Enter your passphrase:',
            {type:'prompt',password:true},
            function(result){
                if(result.btn === lang("OK")) {
                    RichHTML.mask();
                    var requesturl = "index.php?fuse=billing&controller=creditcard&action=validateccnumber";
                    $.ajax({
                        type: 'POST',
                        url: requesturl,
                        success: function(xhr) {
                            json = ce.parseResponse(xhr);
                            if (json.success) {
                                window.location = "index.php?fuse=clients&controller=userprofile&view=profilebilling";
                            } else {
                                RichHTML.unMask();
                            }
                        },
                        data: {
                            passphrase: result.elements.value
                        }
                    });
                }
            });
        });
    }

    $('dd:visible:odd').css("background-color","#F8F8F8 ");
});
