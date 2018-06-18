var viewinvoices = {};

$(document).ready(function() {

    richgrid = new RichHTML.grid({
        el: gridEl,
        width: "100%",
        editable : true,
        url: 'index.php?fuse=billing&controller=invoice&action=getinvoices',
        baseParams: {
            customerid: $('#invoice-userid').val(),
            sort: 'id',
            dir : 'desc',
            limit: clientexec.records_per_view,
            invoicefilter: 2,
            moduleview: "billing invoice list",
            packagefilter: packagemanager.package_id
        },
        root: 'invoices',
        totalProperty : 'totalcount',
        columns: [{
            text: "",
            xtype: "expander",
            escapeHTML: true,
            renderer:renderExpander,
            renderOnExpand: true
        },{
            text:     	"",
            dataIndex:  "id",
            xtype:      "checkbox"
        },{
            text:     lang("Invoice")+" #",
            dataIndex:  "id",
            align:      "center",
            width:      85,
            //flex: 1,
            sortable: true,
            renderer: function(text,row, el) {
                if ( viewinvoices.viewingFromProfile === true ) {
                    desc = "<a href='index.php?controller=invoice&fuse=billing&frmClientID="+row.customerid+"&view=invoice&invoiceid="+row.id+"&profile=1'>#"+text+"</a>";
                } else {
                    desc = "<a href='index.php?controller=invoice&fuse=billing&frmClientID="+row.customerid+"&view=invoice&invoiceid="+row.id+"'>#"+text+"</a>";
                }
                return desc + "  <span class='invoicepdflink'><a href='index.php?sessionHash="+gHash+"&fuse=billing&controller=invoice&action=generatepdfinvoice&invoiceid=" + row.invoiceid + "' target='_blank'><img class='pdfimage' src='../templates/admin/images/document-pdf-text.png' border='0' data-toggle='tooltip' title='"+ lang('View PDF Invoice') +"' /></a></span>";
            }
        },{
            text:       lang("Due"),
            dataIndex:  "billdate",
            align:      "right",
            width:      85,
            sortable: true,
            renderer: ce.dateRenderer
        },
        {
            text:   lang("Gateway"),
            width:	 "145",
            dataIndex: "paymenttype",
            sortable: true,
            align: "center",
            flex: 1,
            renderer: function(value,record) {
                var tSubscriptionId = '';
                if ( record.subscriptionid != null && record.subscriptionid != "" ) {
                   value = record.paymenttype+'<br/> <span style="font-size:9px;">'+record.subscriptionid+'</span>';
                } else {
                    value = record.paymenttype;
                }

                return value;
            }
        },
        {
            text:   lang("Pmt Reference"),
            width:	   "100",
            dataIndex: "billpmtref",
            sortable: true,
            align: "center",
            renderer: function(value,record) {
                value = record.billpmtref;
                return value;
            }
        },{
            text: lang("Amount"),
            width: 83,
            dataIndex: 'balancedue',
            sortable: true,
            align:"right",
            renderer : function (val, row) {
                var font_class = "";
                var due = row.simplebalancedue;
                if (due.length >= 18) {
                    font_class = "xxlong-currency";
                } else if (due.length >= 15) {
                    font_class = "xlong-currency";
                } else if (due.length >= 13) {
                    font_class = "long-currency";
                }
                return "<span class='"+font_class+"'>"+val+"</span>";
            }
        },{
            text: lang("Total"),
            width: 83,
            dataIndex: 'amount',
            sortable: true,
            align:"right",
            renderer : function (val, row) {
                var font_class = "";
                var due = row.simplebalancedue;
                if (due.length >= 18) {
                    font_class = "xxlong-currency";
                } else if (due.length >= 15) {
                    font_class = "xlong-currency";
                } else if (due.length >= 13) {
                    font_class = "long-currency";
                }
                return "<span class='"+font_class+"'>"+val+"</span>";
            }
        },
        {
            text:    lang("Status"),
            width: 70,
            dataIndex: "billstatus",
            align: "center",
            escapeHTML: false,
            renderer: renderStatus
        }
        ]
    });

    richgrid.render();

    function renderExpander(value,record,el){
        $.ajax({
            url: 'index.php?fuse=billing&controller=invoice&action=getstyledinvoicetransactions',
            dataType: 'json',
            async: false,
            data: {
                invoiceid:record.invoiceid
            },
            success: function(data) {
                html = data.invoicetransactions;
            }
        });
        return html;
    }

    function renderStatus(value,record, el){
        if(record.statusenum == -2){
            el.addClass = "invoiceoverdue";
        }else if (record.statusenum === 0){
        }else if(record.statusenum === 1){
            el.addClass = "invoicepaid";
        }else if (record.statusenum === -1){
            el.addClass = "invoicevoidrefund";
        }

        return value;
        //return String.format("{0}",value);
    }

    $(richgrid).bind({
        "load" : function(event,data) {
            viewinvoices.disableButtons();
        },
        "rowselect": function(event,data) {
            viewinvoices.disableButtons();
            if (data.totalSelected > 0) {
                viewinvoices.enableButtons();
            } else {
                viewinvoices.disableButtons();
            }
        }
    });

    viewinvoices.disableButtons = function() {
        $('#invoicelist-grid-buttons').show();
        $('#invoicelist-grid-buttons .btn').attr('disabled','disabled');
    };

    viewinvoices.enableButtons = function() {
        $.ajax({
            url: "index.php?fuse=billing&controller=invoice&action=getinvoicebuttons",
            data: {invoices: richgrid.getSelectedRowIds()},
            success: function(data) {

                viewinvoices.AcceptCCNumber = data.buttons.acceptccnumber;

                $.each(data.buttons,function(name,val){
                    if (val) {
                        $('div#invoicelist-grid-buttons li[data-actionname="inv-'+name+'"]').show();
                        $('div#invoicelist-grid-buttons a[data-actionname="inv-'+name+'"]:not(.btn-group a)').show();
                    } else {
                        $('div#invoicelist-grid-buttons li[data-actionname="inv-'+name+'"]').hide();
                        $('div#invoicelist-grid-buttons a[data-actionname="inv-'+name+'"]:not(.btn-group a)').hide();
                    }
                });

                //if no options are available for the btngroup then hide it
                //this code hides all group buttons that do not have child elements
                //then sets the name and action of the btn to that of the top most option
                $('div#invoicelist-grid-buttons span.btn-group').each(function(k,v) {
                    var li_filter = $(this).find('ul.dropdown-menu li[data-actionname]').filter(function() { return $(this).css("display") != "none"; });
                    if (li_filter.length == 0) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                    //lets make the top option the main option
                    $(this).find('a.btn:not(.dropdown-toggle)').attr('data-actionname',li_filter.first().attr('data-actionname'));
                    $(this).find('a.btn:not(.dropdown-toggle)').text(li_filter.first().text());
                });

                $('#invoicelist-grid-buttons .btn').removeAttr('disabled');

            }
        });
    };

    viewinvoices.paymentReferenceWindow = new RichHTML.window({
        height: '75',
        width: '260',
        grid: richgrid,
        url:       'index.php?fuse=billing&controller=invoice&view=paymentreference',
        actionUrl: 'index.php?fuse=billing&controller=invoice&action=savepaymentreference',
        showSubmit: true,
        title: lang("Add Payment Reference")
    });

    viewinvoices.performaction = function(id,args) {

        var data = {
            items:          richgrid.getSelectedRowIds(),
            itemstype:      'invoices',
            actionbutton:   id
        };

        $.extend(data,args);

        $.ajax({
            url: "index.php?fuse=billing&controller=invoice&action=actoninvoice",
            type: 'POST',
            data:  data,
            success:  function(xhr){
                richgrid.reload();
                ce.parseResponse(xhr);
                if (typeof profile !== "undefined") {
                    setTimeout(function() {
                        profile.get_counts();
                    },1000);
                }
            }
        });
    };

    $('#invoicelist-grid-filter').change(function(){
        richgrid.reload({
            params:{
                "start":0,
                "limit":$(this).val()
            }
        });
    });

    $('#invoicelist-grid-filterbystatus').change(function(){

        if ($(this).val() == "3") {
            $('#invoice-userid').val('');
            $('#td-for-userid').show();
        } else {
            $('#td-for-userid').hide();
            $('#viewing-invoices-text').text(lang("Viewing Invoices"));
            richgrid.reload({
                params:{
                    "start":0,
                    "customerid": $('#invoice-userid').val(),
                    "invoicefilter":$(this).val()
                }
            });
        }
    });

    $('div#invoicelist-grid-buttons a.btn:not(.dropdown-toggle), div#invoicelist-grid-buttons ul.dropdown-menu li').click(function(button){
        if ( $(this).attr('disabled') ) {
            return;
        }

        richgrid.disable();
        $('span.btn-group').removeClass('open');

        var id = $(this).attr('data-actionname');

        if (id == 'inv-cancelsub') {
            RichHTML.msgBox(lang("Are sure you want to cancel the subscription tied to this invoice?"),{type:'yesno'},function(ret) {
                if (ret.btn == lang("No")) {
                    richgrid.enable();
                    return;
                }
                viewinvoices.performaction(id);
            });
        } else if (id == "inv-markpaid") {
            RichHTML.msgBox(lang("Do you want to send a receipt?"),{type:'confirm'},
                function(ret) {
                    var sendReceipt = false;
                    if (ret.btn == lang("Yes")) {
                        sendReceipt = true;
                    } else if(ret.btn == lang("Cancel")) {
                        richgrid.enable();
                        return;
                    }
                    viewinvoices.performaction(id,{sendreceipt:sendReceipt});
                    return;
                }
            );
        } else if(id == "inv-deleteinvoices"){
            RichHTML.msgBox(lang("Are sure you want to delete the selected invoice(s)."),{type:'yesno'},
                function(ret) {
                    if(ret.btn == lang("No")) {
                        richgrid.enable();
                        return;
                    }
                    viewinvoices.performaction(id);
                });

        } else if(id == "inv-varpayment"){

            var balancedue = richgrid.getSelectedRowData()[0].simplebalancedue;
            RichHTML.msgBox('',
                {
                    type:'prompt',
                    content: 'Balance Due: '+balancedue+'<br/>'
                        +'<input type="text" name="paymentamount" class="required float" placeholder="'+lang("Amount")+'" /><br/><br/>'
                        +'<a href="#" id="addOptionalLink">'+lang("Add optional information")+'</a>'
                        +'<fieldset class="editOptionalPopup" style="display:none">'
                        +'<a href="#"><i class="icon-remove-sign icon-large"></i>&nbsp&nbsp'+lang("Remove Optional Information")+'</a>'
                        +'<div class="row-fluid">'
                        +'<input type="text" name="checknum" id="checknum" placeholder="'+lang("Payment Reference (Optional)")+'" /><br/>'
                        +'<input class="datepicker" style="width: 206px" type="text" name="paymentdate" id="paymentdate" placeholder="'+lang("Payment Date (Optional)")+'"/><br/>'
                        +'<input type="text" name="paymentprocessor" id="paymentprocessor" placeholder="'+lang("Payment Processor (Optional)")+'" />'
                        +'</div>'
                        +'</fieldset>'
                },
                function(ret){
                    if (ret.btn == lang("Cancel")) {
                        richgrid.enable();
                        return;
                    } else {
                        viewinvoices.performaction(id,ret.elements);
                    }
                }
            );
            clientexec.postpageload();

            $('#addOptionalLink').click(function() {
              $(this).hide();
              $(this).next().show();
            });

            $('.editOptionalPopup > a').click(function() {
                $('.editOptionalPopup').hide();
                $('#checknum').val('');
                $('#paymentdate').val('');
                $('#paymentprocessor').val('');
                $('#addOptionalLink').show();
            });

        } else if(id == "inv-process"){
            var selectedRowData = richgrid.getSelectedRowData();
            var arrayLength = selectedRowData.length;
            var askAboutCharge = false;
            for (var idx = 0; idx < arrayLength; idx++) {
                if(selectedRowData[idx].canbechargedtoday == 0){
                    askAboutCharge = true;
                    break;
                }
            }

            if(askAboutCharge){
                RichHTML.msgBox(lang("Some invoices are not due. Are you sure you want to proceed?"),
                    {type:'yesno'},function(result){
                       if(result.btn === lang("Yes")) {
                            //viewinvoices.AcceptCCNumber = false;
                            if (viewinvoices.AcceptCCNumber) {

                                RichHTML.msgBox(lang('Enter your passphrase:'),
                                    {type:'prompt',password:true},
                                    function(result){
                                        if(result.btn === lang("OK")) {
                                            viewinvoices.performaction(id,{passphrase:result.elements.value,acceptccnumber:viewinvoices.AcceptCCNumber});
                                        } else {
                                            richgrid.enable();
                                        }
                                    }
                                );
                            } else {
                                viewinvoices.performaction(id,{acceptccnumber:viewinvoices.AcceptCCNumber});
                            }
                        } else {
                            richgrid.enable();
                        }
                });
            } else {
                //viewinvoices.AcceptCCNumber = false;
                if (viewinvoices.AcceptCCNumber) {

                    RichHTML.msgBox(lang('Enter your passphrase:'),
                        {type:'prompt',password:true},
                        function(result){
                            if(result.btn === lang("OK")) {
                                viewinvoices.performaction(id,{passphrase:result.elements.value,acceptccnumber:viewinvoices.AcceptCCNumber});
                            } else {
                                richgrid.enable();
                            }
                        }
                    );
                } else {
                    RichHTML.msgBox(lang("Are you sure you want to process the selected account(s)?"),
                        {type:'yesno'},function(result){
                           if(result.btn === lang("Yes")) {
                                viewinvoices.performaction(id,{acceptccnumber:viewinvoices.AcceptCCNumber});
                            } else {
                                richgrid.enable();
                            }
                    });
                }
            }
        } else {
            //all other actions do not need confirmations or prompts
            viewinvoices.performaction(id);
        }

    });
});