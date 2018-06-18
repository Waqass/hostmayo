processinvoice = {};
processinvoice.initial = 0;
processinvoice.actionperformed = "";

processinvoice.processing_win = new RichHTML.window({
    height: '60',
    title: lang("Please wait"),
    content: "<span class='processing-label'>"+lang("Preparing data")+'...</span><br/>'+'<div class="invoice-progress progress progress-striped active"><div class="bar" style="width: 0%;"></div></div>',
    hideButtons: true,
    escClose: false,
    buttons: {button1:{text:lang('Stop Processing'),onclick:function(a){
        goCompleted();
      }
    }}
});


function startCreateInvoices()
{
   processinvoice.processing_win.show();  
   processinvoice.pollingInvoiceCreator();
}

processinvoice.pollingInvoiceCreator = function()
{
  processinvoice.actionperformed = "generate";
  $.post("index.php?fuse=billing&controller=invoice&action=generateinvoice",
       {
           initialnum: processinvoice.initial
       },function(xhr) {
          res = ce.parseResponse(xhr);

           //do we need to update global vars
           res.percentage = res.percentage*100;
          
           if (res.doaction == "close") {  
              $('.processing-label').text(lang("Completed"));            
              $('.invoice-progress .bar').css('width','100%')
              timerID = setTimeout("goCompleted();",res.timer);
           } else if (res.doaction == "gonext") {
              $('.processing-label').text(lang("Generating Invoice(s)")+res.invoice+" for "+res.statusline);
              $('.invoice-progress .bar').css('width',parseInt(res.percentage)+'%')
              processinvoice.initial = res.initial;
              timerID = setTimeout("processinvoice.pollingInvoiceCreator();",res.timer);
           }

       }
    );
}

function startProcessingInvoices(tIncludeDeclined, tPassphrase){
   processinvoice.processing_win.show();
  
   //set variables for this transaction
   processinvoice.includeDeclined = tIncludeDeclined;
   processinvoice.passphrase = tPassphrase; 
   processinvoice.pollingInvoiceProcessor();
  
}

processinvoice.pollingInvoiceProcessor = function()
{
  processinvoice.actionperformed = "process";
  $.post("index.php?fuse=billing&controller=invoice&action=processinvoice",
     {
         initialnum: processinvoice.initial,
         include_declined:processinvoice.includeDeclined,
         passphrase:processinvoice.passphrase

     },function(xhr) {
        res = ce.parseResponse(xhr);

         //do we need to update global vars
         res.percentage = res.percentage*100;
        
         if (res.doaction == "close") {  
            $('.processing-label').text(lang("Completed"));            
            $('.invoice-progress .bar').css('width','100%')
            timerID = setTimeout("goCompleted();",res.timer);
         } else if (res.doaction == "gonext") {
            $('.processing-label').text(lang("Processing Invoice(s)")+res.invoice+" for "+res.statusline);
            $('.invoice-progress .bar').css('width',parseInt(res.percentage)+'%')
            processinvoice.initial = res.initial;
            timerID = setTimeout("processinvoice.pollingInvoiceProcessor();",res.timer);
         }

     }
    );

}

function goCompleted(){
    this.location="index.php?fuse=billing&controller=invoice&view=processinvoices&phase=completed&performed="+processinvoice.actionperformed;
}

$(document).ready(function(){
  $('#include_passphrase').on('click',function(){
    if($(this).is(":checked")){
      $('#cc_passphrase').show();
    } else {
      $('#cc_passphrase input').val('');
      $('#cc_passphrase').hide();
    }
  });
}); 