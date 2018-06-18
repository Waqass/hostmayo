var invoiceview = invoiceview || {};
invoiceview.newentries = 0;
invoiceview.showentryactions = true;
invoiceview.hoverentryid = 0;
invoiceview.changesmade = false;

$(document).ready(function(){

    var datePickerOpts = {
        format: clientexec.dateFormat == 'm/d/Y'? 'mm/dd/yyyy' : 'dd/mm/yyyy'
    };

    var changeDate = function(ev, callback) {
        var y = ev.date.getFullYear(),
        _m = ev.date.getMonth() + 1,
        m = (_m > 9 ? _m : '0'+_m),
        _d = ev.date.getDate(),
        d = (_d > 9 ? _d : '0'+_d);

        var formattedDate = clientexec.dateFormat == 'm/d/Y'? m + '/' + d + '/' + y : d + '/' + m + '/' + y;
        callback(formattedDate);
    };

    $('#btn-saveinvoice').click(function(){
        var invoiceentries = [];
        var invoiceentry;
        $("tr.invoiceentry-row:not('.entry-clone')").each(function(key, value) {
            invoiceentry = {};
            invoiceentry.entryid = $(value).attr('data-entryid');
            invoiceentry.name = $(value).find('td.invoiceentry-description span.edit-entry').text();
            invoiceentry.desc = $.br2nl($(value).find('td.invoiceentry-description span.edit-details').html());
            invoiceentry.appliestoid = $(value).attr('data-appliestoid');
            invoiceentry.taxable = $(value).find("td.invoiceentry-tax span.dropdown-toggle[data-varid]").attr('data-varid');
            invoiceentry.billingtypeid = $(value).find('td.invoiceentry-type span.dropdown-toggle[data-varid]').attr('data-varid');
            invoiceentry.priceformatted = $(value).find('td.invoiceentry-amount input.invoiceentry-price').val();
            invoiceentry.priceformatted = invoiceentry.priceformatted.toString();
            invoiceentry.price = accounting.unformat(invoiceentry.priceformatted.replace(invoiceview.currency.symbol, ""), invoiceview.currency.decimalssep);
            invoiceentry.taxamountformatted = $(value).find('td.invoiceentry-tax-amount div').text();
            invoiceentry.taxamountformatted = invoiceentry.taxamountformatted.toString();
            invoiceentry.taxamount = accounting.unformat(invoiceentry.taxamountformatted.replace(invoiceview.currency.symbol, ""), invoiceview.currency.decimalssep);
            invoiceentry.recurring = $(value).attr('data-isrecurring');
            invoiceentry.recurringappliestoid = $(value).attr('data-recurringappliestoid');
            invoiceentry.start = $(value).find('.edit-start').text(),
            invoiceentry.end = $(value).find('.edit-end').text(),
            invoiceentries.push(invoiceentry);
        });

        var invoiceswitches = [];
        var invoiceswitch;
        $("div.invoice-switch").each(function(key, value) {
            invoiceswitch = {};
            invoiceswitch.name = $(value).attr('data-preference-name');
            invoiceswitch.value = ($(value).find('input').is(':checked')) ? 1: 0;
            invoiceswitches.push(invoiceswitch);
        });

        $.post('index.php?fuse=billing&controller=invoice&action=saveinvoice',
            {
                invoicedate: $('span.show-invoiceDate').attr('data-date'),
                invoiceid: invoiceview.invoiceid,
                invoicetotal: invoiceview.total,
                invoicetax1: invoiceview.tax1total,
                invoicetax2: invoiceview.tax2total,
                invoiceentries: invoiceentries,
                invoicenote: $('#invoice-note').val(),
                invoiceswitches: invoiceswitches
            },
            function(xhr) {
                data = ce.parseResponse(xhr);
                if (data.error) return;

                invoiceview.newinvoice = false;
                invoiceview.changesmade = false;
                $('#view-invoice.content .changesalert').hide();
                $('#view-invoice.content h1.invoicelabel').text('Invoice: '+data.InvoiceID);
                invoiceview.invoiceid = data.InvoiceID;
                //replace temp new_x entry ids with the saved entry ids
                $.each(data.entries,function(a,b){
                    $('tr.invoiceentry-row[data-entryid="'+b['old']+'"]').attr('data-entryid',b['new']);
                });
                History.pushState({}, "", "index.php?fuse=billing&controller=invoice&view=invoice&invoiceid="+data.InvoiceID);
                window.location = "index.php?fuse=billing&controller=invoice&view=invoice&invoiceid="+data.InvoiceID;
            }
        );
    });

    $('span.show-invoiceDate')
        .datepicker(datePickerOpts)
        .on('changeDate', function(ev) {
          changeDate(ev, function(formattedDate) {
              $('span.show-invoiceDate').attr('data-date', formattedDate).datepicker('hide');
              $('#datedue-start-display').text(formattedDate);
              invoiceview.bindanchorsafterchanges();
          });
        });

    $('div#invoice-buttons a.btn:not(.dropdown-toggle), div#invoice-buttons ul.dropdown-menu li').click(function(button){
        if ( $(this).attr('disabled') ) {
            return;
        }

        invoiceview.disableButtons();
        $('span.btn-group').removeClass('open');

        var id = $(this).attr('data-actionname');

        if (id == 'inv-cancelsub') {
            RichHTML.msgBox(lang("Are sure you want to cancel the subscription tied to this invoice?"),{type:'yesno'},function(ret) {
                if (ret.btn == lang("No")) {
                    richgrid.enable();
                    return;
                }
                invoiceview.performaction(id);
            });
        } else if (id == "inv-markpaid") {
            RichHTML.msgBox(lang("Do you want to send a receipt?"),{type:'confirm'},
                function(ret) {
                    var sendReceipt = false;
                    if (ret.btn == lang("Yes")) {
                        sendReceipt = true;
                    } else if(ret.btn == lang("Cancel")) {
                        invoiceview.enableButtons(0);
                        return;
                    }
                    invoiceview.performaction(id,{sendreceipt:sendReceipt});
                    return;
                }
            );
        } else if(id == "inv-deleteinvoices"){
            RichHTML.msgBox(lang("Are sure you want to delete the selected invoice(s)."),{type:'yesno'},
                function(ret) {
                    if(ret.btn == lang("No")) {
                        invoiceview.enableButtons(0);
                        return;
                    }
                    invoiceview.performaction(id);
                });

        } else if(id == "inv-varpayment"){

            var balancedue = $('td#invoiceentries-balance').text();
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
                        invoiceview.enableButtons(0);
                        return;
                    } else {
                        invoiceview.performaction(id,ret.elements);
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

            if (invoiceview.AcceptCCNumber) {

                RichHTML.msgBox(lang('Enter your passphrase:'),
                    {type:'prompt',password:true},
                    function(result){
                        if(result.btn === lang("OK")) {
                            invoiceview.performaction(id,{passphrase:result.elements.value,acceptccnumber:invoiceview.AcceptCCNumber});
                        }else{
                            invoiceview.enableButtons(0);
                        }
                    }
                );
            } else {
                RichHTML.msgBox(lang("Are you sure you want to process the selected account(s)?"),
                    {type:'yesno'},function(result){
                       if(result.btn === lang("Yes")) {
                            invoiceview.performaction(id,{acceptccnumber:invoiceview.AcceptCCNumber});
                        }else{
                            invoiceview.enableButtons(0);
                        }
                });
            }

        } else {
            //all other actions do not need confirmations or prompts
            invoiceview.performaction(id);
        }

    });

    invoiceview.performaction = function(id,args) {
        var data = {
                items:          [invoiceview.invoiceid],
                itemstype:      'invoices',
                actionbutton:   id
            };

        $.extend(data,args);

        $.ajax({
            url: "index.php?fuse=billing&controller=invoice&action=actoninvoice",
            data:  data,
            success:  function(xhr){
                data = ce.parseResponse(xhr);
                if (data.error) {
                    invoiceview.enableButtons(0);
                    if(id == 'inv-merge-to'){
                        $('#view-invoice.content .icon_menu').show();
                        invoiceview.showentryactions = true;
                    }
                    return;
                }
                if(id == 'inv-deleteinvoices'){
                    window.location = "index.php?fuse=billing&controller=invoice&view=invoices";
                }else{
                    window.location = "index.php?fuse=billing&controller=invoice&view=invoice&invoiceid="+invoiceview.invoiceid;
                }
            }
        });
    };

    invoiceview.enableButtons = function(editSubscription) {
        $.ajax({
           url: "index.php?fuse=billing&controller=invoice&action=getinvoicebuttons",
           data: {invoices: [invoiceview.invoiceid], editSubscription: editSubscription},
           success: function(data) {

               invoiceview.AcceptCCNumber = data.buttons.acceptccnumber;

               $.each(data.buttons,function(name,val){
                   if (val) {
                       $('div#invoice-buttons li[data-actionname="inv-'+name+'"]').show();
                       $('div#invoice-buttons a[data-actionname="inv-'+name+'"]:not(.btn-group a)').show();
                   } else {
                       $('div#invoice-buttons li[data-actionname="inv-'+name+'"]').hide();
                       $('div#invoice-buttons a[data-actionname="inv-'+name+'"]:not(.btn-group a)').hide();
                   }
               });

               //if no options are available for the btngroup then hide it
               //this code hides all group buttons that do not have child elements
               //then sets the name and action of the btn to that of the top most option
               $('div#invoice-buttons span.btn-group').each(function(k,v) {
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
               $('#invoice-buttons .btn').removeAttr('disabled');
           }
        });
    };

    invoiceview.disableButtons = function() {
        $('#invoice-buttons').show();
        $('#invoice-buttons .btn').attr('disabled','disabled');
    };

    if (!invoiceview.newinvoice) {
        invoiceview.transactions = new RichHTML.grid({
            el: 'invoice-transactions',
            url: "index.php?fuse=billing&controller=invoice&action=getinvoicetransactions",
            baseParams: {
                invoiceid: invoiceview.invoiceid
            },
            root: 'invoiceentries',
            columns: [{
                text:       lang("Date"),
                dataIndex:  "transdate",
                align:      "left",
                width:      180
            },{
                text:       lang("Response"),
                dataIndex: "response",
                align:      "left",
                renderer: function(text, row) {
                    return ce.htmlspecialchars(row.response);
                }
            }
            ]
        });

        invoiceview.transactions.render();

        invoiceview.disableButtons();
        invoiceview.enableButtons(0);
    }

    invoiceview.renderBy = function(text,row){
        return row.logdate + "<br/><small>by: " + row.loguser + "</small>";
    };

    $('a#addinvoiceline').click(function(){

        invoiceview.newentries++;
        el = $('tr.entry-clone').clone();
        el.removeClass('entry-clone');
        el.attr('data-entryid',"new_"+invoiceview.newentries);
        el.attr('data-appliestoid',0);
        el.show();
        $('.footer-row').before(el);
        //$('table.invoice-entries tbody').append(el);

        //unbind all other clicks .. lets rebind
        $('tr span.edit-entry').unbind('click');
        $("tr[data-entryid]").unbind('hover');
        $('ul.dropdown-menu li a').unbind('click');
        $('input.invoiceentry-price').unbind('keypress');
        $('input.invoiceentry-price').unbind('blur');
        $('.icon_remove-wrap').unbind('hover');
        $('.icon_split-wrap').unbind('hover');
        $('.icon_merge-wrap').unbind('hover');
        $('.icon_menu').unbind('hover');
        $('.icon_remove-wrap div').unbind('click');
        $('.icon_split-wrap div').unbind('click');
        $('.icon_merge-wrap div').unbind('click');
        $('.icon_menu span.link').unbind('click');
        invoiceview.bindentries();
        invoiceview.bindanchorsafterchanges();
        el.find('td.invoiceentry-amount input.invoiceentry-price').blur(); //To show the tax price formatted
    });

    $('textarea.invoicenotes').bind('keypress',function(event){
        invoiceview.bindanchorsafterchanges();
    });

    if (!invoiceview.newinvoice && $('#invoice-events').length > 0 ) {
        invoiceview.events = new RichHTML.grid({
            el: 'invoice-events',
            root: 'data',
            totalProperty: 'totalcount',
            url: 'index.php?fuse=home&action=geteventlist&controller=events',
            baseParams: {
                selectedUserId: '',
                itemid: invoiceview.invoiceid,
                eventtype: 'billing',
                limit: clientexec.records_per_view},
            columns: [
                {
                    id:         "logaction",
                    text:       lang("Action"),
                    dataIndex:  "logaction",
                    align:      "left"
                },{
                    id:         "logdate",
                    text:       lang("Edited on"),
                    width:      150,
                    align:      "right",
                    dataIndex:  "logdate",
                    renderer:   invoiceview.renderBy
                }
            ]
        });

        invoiceview.events.render();

    }

    $('#view-invoice.content a#enterpayment').click(function(){
        var balancedue = $('td#invoiceentries-balance').text();
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
                if (ret.btn != "cancel") {

                    var data = {
                        items:          [invoiceview.invoiceid],
                        itemstype:      'invoices',
                        actionbutton:   'inv-varpayment'
                    };

                    $.ajax({
                        url: "index.php?fuse=billing&controller=invoice&action=actoninvoice",
                        data:  $.extend(data,ret.elements),
                        success:  function(xhr){
                            ce.parseResponse(xhr);
                            if (!ce.error) location.reload(true);
                        }
                    });
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
    });

    invoiceview.calculatetotals = function() {

        var price=0.00, paid = 0.00, balance = 0.00, tax1 = 0.00, tax2 = 0.00, totaltax = 0.00;

        invoiceview.total = 0.00;
        invoiceview.tax1total = 0.00;
        invoiceview.tax2total = 0.00;

        $('input.invoiceentry-price').each(function(type,el){
            priceformatted = $(el).val();
            priceformatted = priceformatted.toString();
            price = accounting.unformat(priceformatted.replace(invoiceview.currency.symbol, ""), invoiceview.currency.decimalssep);
            invoiceview.total += price;
            entrytaxed = $(el).closest('tr').find('td.invoiceentry-tax span.dropdown-toggle[data-varid]').attr('data-varid');

            entrytypeid = $(el).closest('tr').find('td.invoiceentry-type span.dropdown-toggle[data-varid]').attr('data-varid');
            if(entrytypeid == -3 && entrytaxed !== "0"){
                //Percentage coupons are calculated over other entries, and their taxes depend on which of those entries were taxable or not.
                //When the invoice gets saved, it is totally recalculated, fixing the values, so it is better to use the current tax value on the coupon
                entrytaxamountformatted = $(el).closest('tr').find('td.invoiceentry-tax-amount div').text();
                entrytaxamountformatted = entrytaxamountformatted.toString();
                entrytaxamount = accounting.unformat(entrytaxamountformatted.replace(invoiceview.currency.symbol, ""), invoiceview.currency.decimalssep);
                invoiceview.tax1total += entrytaxamount;
            }else{
                entrytax1formatted = invoiceview.calculatetaxforprice(price,entrytaxed,1,true);
                entrytax1formatted = entrytax1formatted.toString();
                invoiceview.tax1total += invoiceview.calculatetaxforprice(price,entrytaxed,1,false);
                entrytax2formatted = invoiceview.calculatetaxforprice(price,entrytaxed,2,true);
                entrytax2formatted = entrytax2formatted.toString();
                invoiceview.tax2total += invoiceview.calculatetaxforprice(price,entrytaxed,2,false);
            }
        });
        $('#invoiceentries-total').html(accounting.formatMoney(invoiceview.total, invoiceview.currency.symbol, invoiceview.currency.precision, invoiceview.currency.thousandssep, invoiceview.currency.decimalssep, invoiceview.currency.alignment));
        paidformatted = $('#invoiceentries-paid').text();
        paidformatted = paidformatted.toString();
        paid = accounting.unformat(paidformatted.replace(invoiceview.currency.symbol, ""), invoiceview.currency.decimalssep);
        balance = (invoiceview.total + invoiceview.tax1total + invoiceview.tax2total) - paid;
        totaltax = invoiceview.tax1total + invoiceview.tax2total;
        $('#invoiceentries-tax-total').html(accounting.formatMoney(totaltax, invoiceview.currency.symbol, invoiceview.currency.precision, invoiceview.currency.thousandssep, invoiceview.currency.decimalssep, invoiceview.currency.alignment));
        $('#invoiceentries-paid').html(accounting.formatMoney(paid, invoiceview.currency.symbol, invoiceview.currency.precision, invoiceview.currency.thousandssep, invoiceview.currency.decimalssep, invoiceview.currency.alignment));
        if(balance < 0) balance = 0;
        $('#invoiceentries-balance').html(accounting.formatMoney(balance, invoiceview.currency.symbol, invoiceview.currency.precision, invoiceview.currency.thousandssep, invoiceview.currency.decimalssep, invoiceview.currency.alignment));
    };

    invoiceview.bindanchorsafterchanges = function()
    {
        invoiceview.changesmade = true;
        //below is used when we should ASK before leaving present page
        clientexec.bindLinksIfLeavingInvoiceViewIsPrevented();
        $('#view-invoice.content .changesalert').show();

        //Disable buttons if there are changes pending to be saved on the invoice
        invoiceview.disableButtons();

        $('#view-invoice.content .newinvoicealert').hide();
        $('#view-invoice.content .icon_remove-wrap').hide();
        $('#view-invoice.content .icon_split-wrap').hide();
        $('#view-invoice.content .icon_merge-wrap').hide();
        $('#view-invoice.content .icon_menu').hide();

    };

    invoiceview.clickanchorsafterchanges = function(e)
    {
        var href = "";
        var el;

        //this bind might have been made when changes where made but we might have saved
        //so if saved lets return true.
        if (!invoiceview.changesmade) return true;

        if (e.currentTarget) el = e.currentTarget;
        else if (e.srcElement) el = e.srcElement;

        //the click came from global search
        if (e.currentTarget.id === "searchquerytextfield") {
            href = e.added.url;
        } else {
            //lets get the url we are supposed to be going to
            for (var x=0;x<el.attributes.length;x++) {
                if (el.attributes[x]) {
                    if (el.attributes[x].name === "href") {
                        href = el.attributes[x].value;
                    }
                }
            }
        }

        if (href === "") {
        }

        RichHTML.alert('Leaving now will cancel this item.  Are you sure you want to leave?',{},
            function(event) {
                if (event.btn === lang("Yes")) {
                    invoiceview.changesmade = false;
                    window.location.href = href;
                }
            }
        );

        //lets close all open dropdowns

        return false;
        //e.preventDefault();
        //e.stopPropagation();
    };

    /** set tax for a given price
    /* price = jquery field holding the price
    /* taxamount = jquery field holding the tax
    /* entrytaxed = is the entry taxed
     */
    invoiceview.calculatetotaltaxforprice = function(price,entrytaxed,returnvalue) {
        //if type lets add price
        //lets see if we are not taxed if so reset the multiplier
        if (!returnvalue) returnvalue = false;
        if (!entrytaxed || entrytaxed === "0") {
            if (returnvalue) return 0;
            else return 'NA';
        } else {
            tax1 = price  * (invoiceview.tax.multiplier1 - 1);
            if (invoiceview.tax.rate2compound) {
                tax2 = (price + tax1) * (invoiceview.tax.multiplier2 - 1);
            } else{
                tax2 = price  * (invoiceview.tax.multiplier2 - 1);
            }
            return accounting.formatMoney(accounting.toFixed(tax1 + tax2, 2), invoiceview.currency.symbol, invoiceview.currency.precision, invoiceview.currency.thousandssep, invoiceview.currency.decimalssep, invoiceview.currency.alignment);
        }
    };

    invoiceview.calculatetaxforprice = function(price,entrytaxed,level,format) {
        var tax;

        if (typeof(invoiceview.tax) === "undefined") return 0;

        if (level == 1) {
            tax = invoiceview.tax.multiplier1 - 1;
        } else {
            if (invoiceview.tax.rate2compound) {
                tax = (invoiceview.tax.multiplier2 - 1) * invoiceview.tax.multiplier1;
            } else {
                tax = invoiceview.tax.multiplier2 - 1;
            }
        }
        if (entrytaxed === "0") {
            return 0;
        } else {
            if(format){
                return accounting.formatMoney(accounting.toFixed(price * tax, 2), invoiceview.currency.symbol, invoiceview.currency.precision, invoiceview.currency.thousandssep, invoiceview.currency.decimalssep, invoiceview.currency.alignment);
            }else{
                return price * tax;
            }
        }
    };

    invoiceview.bindentries = function(){
        //lets keep the menu when we hover over it
        $('.icon_menu').bind('hover', function() {
            $(this).hide();}, function () { $(this).show();} );

        //delete the tr the remove wrap is set at
        $('.icon_remove-wrap').bind('click',function(e){
            RichHTML.msgBox(
                lang("Are you sure you want to delete this charge? If yes please make sure you also click <b>Save Changes</b>"),{type:'yesno'},
                function(ret) {
                    if(ret.btn == lang("No")) {
                        return;
                    }
                    $('tr[data-entryid="'+invoiceview.hoverentryid+'"]').remove();
                    $(this).closest('.icon_remove-wrap').hide();
                    invoiceview.calculatetotals();
                    invoiceview.bindanchorsafterchanges();
                }
            );
        });

        //create a new invoice with the entry on the split wrap is set at
        $('.icon_split-wrap').bind('click',function(e){
            invoiceview.disableButtons();
            $('#view-invoice.content .icon_menu').hide();
            invoiceview.showentryactions = false;
            RichHTML.msgBox(
                lang("Are you sure you want to split this charge into a new invoice?"),{type:'yesno'},
                function(ret) {
                    if(ret.btn == lang("No")) {
                        invoiceview.enableButtons(0);
                        $('#view-invoice.content .icon_menu').show();
                        invoiceview.showentryactions = true;
                        return;
                    }
                    invoiceview.performaction('inv-split',{entryid:invoiceview.hoverentryid});
                }
            );
        });

        //merge the entry to the indicated invoice on the merge wrap is set at
        $('.icon_merge-wrap').bind('click',function(e){
            invoiceview.disableButtons();
            $('#view-invoice.content .icon_menu').hide();
            invoiceview.showentryactions = false;
            RichHTML.msgBox(
                lang('Enter the invoice id:'),
                {type:'prompt'},
                function(result){
                    if(result.btn === lang("OK")) {
                        invoiceview.performaction('inv-merge-to',{entryid:invoiceview.hoverentryid,invoiceid:result.elements.value});
                    }else{
                        invoiceview.enableButtons(0);
                        $('#view-invoice.content .icon_menu').show();
                        invoiceview.showentryactions = true;
                        return;
                    }
                }
            );
        });

        $('.invoice-switch').on('switch-change', function (e, data) {
            var hasinvoiceentries = false;
            $("tr.invoiceentry-row:not('.entry-clone')").each(function(key, value) {
                hasinvoiceentries = true;
                return false;
            });
            if ( hasinvoiceentries ) {
                invoiceview.bindanchorsafterchanges();
            }
        });

        $('input.invoiceentry-price').bind('keypress blur',function(event){
            //validate this field as allowing only float
            if ( ( event.which == 13 ) || (event.type === "blur") ) {
                //lets check to see if the price was updated
                var priceformatted2 = $(this).val();
                priceformatted2 = priceformatted2.toString();
                var price = accounting.unformat(priceformatted2.replace(invoiceview.currency.symbol, ""), invoiceview.currency.decimalssep);
                var taxamountfield = $(this).closest('tr').find('td.invoiceentry-tax-amount div');
                var taxdropdownvalue = $(this).closest('tr').find('td.invoiceentry-tax span.dropdown-toggle[data-varid]').attr('data-varid');
                taxamountfield.html( invoiceview.calculatetotaltaxforprice(price,taxdropdownvalue) );

                if (parseFloat(price) === parseFloat($(this).attr('data-original'))) {
                    //no change was made
                    invoiceview.calculatetotals();
                } else {
                    if(invoiceview.currency.decimalssep === '&nbsp;'){
                        decimalssep = ' ';
                    }else{
                        decimalssep = invoiceview.currency.decimalssep;
                    }
                    if(invoiceview.currency.thousandssep === '&nbsp;'){
                        thousandssep = ' ';
                    }else{
                        thousandssep = invoiceview.currency.thousandssep;
                    }
                    $(this).val(accounting.formatMoney(price,"", invoiceview.currency.precision, thousandssep, decimalssep, invoiceview.currency.alignment));
                    invoiceview.calculatetotals();
                    invoiceview.bindanchorsafterchanges();
                }
            }
        });

        $('ul.dropdown-menu:not(".invoice-entry-actions, .admin-actions") li span').click(function(){
            var text = $(this).text();
            var price, multiplier = 1, showsavechanges = false;
            $(this).closest('tr td div.dropdown').find('span.dropdown-toggle[data-varid]').text(text).attr('data-varid',$(this).attr('data-varid'));
            invoiceview.bindanchorsafterchanges();

            price = $(this).closest('tr').find('input.invoiceentry-price');
            taxamount = $(this).closest('tr').find('td.invoiceentry-tax-amount div');

            if ($(this).filter('[data-price]').length > 0) {

                price.val(accounting.toFixed($(this).attr('data-price'),2));

                //lets get the taxdropdownvalue for this entry
                var taxdropdownvalue = $(this).closest('tr').find('td.invoiceentry-tax span.dropdown-toggle[data-varid]').attr('data-varid');

                taxamount.html(invoiceview.calculatetotaltaxforprice(price.val(),taxdropdownvalue));
                showsavechanges = true;
            } else {

                taxed = $(this).closest('tr').find('input.invoiceentry-price').attr('data-taxed');
                taxvalue = $(this).attr('data-varid');

                //let's reset price's tax
                $(this).closest('tr').find('input.invoiceentry-price').attr('data-taxed',taxvalue);
                //let's set the new taxed to span
                $(this).closest('tr').find('td.invoiceentry-tax span.dropdown-toggle[data-varid]').attr('data-varid',taxvalue);

                //let's take a look at how we are going to change the price based on the previous taxable setting
                if (taxed == 1 && taxvalue == 0) {
                    //if we were taxed and now we are switching to non taxed then we need to subtract the taxed amount
                    taxamount.text('NA');
                } else if (taxed==0 && taxvalue ==1){
                    //if we were not taxed and now we are then let's add tax
                    newamounttaxed =  (price.val()  * invoiceview.tax.multiplier1) - price.val();
                    newamounttaxed += (price.val()  * invoiceview.tax.multiplier2) - price.val();
                    taxamount.text(accounting.toFixed(newamounttaxed,2));
                }
            }

            if ($(this).filter('[data-description]').length > 0) {
                descId = $(this).closest('tr').find('span.edit-entry');
                $(descId).text($(this).attr('data-description'));
            }

            if ($(this).filter('[data-detail]').length > 0) {
                tdetail = $(this).closest('tr').find('span.edit-details');
                var details = $(this).attr('data-detail');
                // details = details.replace(/(?:\r\n|\r|\n)/g, '<br />');
                $(tdetail).text($(this).attr('data-detail'));
                // $(tdetail).html(details);
            }

            //changes were made
            price.trigger('blur');
        });

        //edit entry click action
        $('tr span.edit-entry').click(function(){
            var closestTr = $(this).closest('tr');
            var id = closestTr.attr('data-entryid');
            var appliestoid = closestTr.attr('data-appliestoid');
            var appliestoidId = $('tr[data-entryid='+id+'] span.edit-appliestoid');
            var descId = $('tr[data-entryid='+id+'] span.edit-entry');
            var detailId = $('tr[data-entryid='+id+'] span.edit-details');
            var startId = $('tr[data-entryid='+id+'] span.edit-start');
            var endId = $('tr[data-entryid='+id+'] span.edit-end');
            var content = '<label>Description</label><input type="text" style="width:305px;" name="description" required placeholder="Description" value="'+$(descId).text()+'" /><br/><br/>';
            content += "<label>Details</label><textarea placeholder='Details' name='detail'>"+$.br2nl($(detailId).html())+"</textarea><br/><br/>";

            content += "<label>Applies to package</label>";
            content += "<select name='appliestoid' style='width:320px;'>";
            content += "<option value='0'>None</option>";
            for (index = 0; index < invoiceview.packages.length; ++index) {
                selectedOption = '';
                if(invoiceview.packages[index]['id'] == appliestoid){
                    selectedOption = 'selected="selected"';
                }
                content += "<option "+selectedOption+" value='"+invoiceview.packages[index]['id']+"'>"+invoiceview.packages[index]['name']+"</option>";
            }
            content += "</select><br/><br/>";

            if (startId.first().text() != '') {
              content += "<a href='#' id='addDatesLink' class='hide'>"+lang("Add period start and end dates")+"</a>";
              content += "<fieldset class='editInvoiceEntryPopup'>";
              content += "<legend><a href='#'><i class='icon-remove-sign icon-large'></i>&nbsp&nbsp"+lang("Period Dates")+"</a></legend>";
              content += "<div class='row-fluid'>"
              content += "<div class='span6'>";
              content += "<label>"+lang("Start")+": <span class='link periodStart' data-date='" + $(startId).text() +"'>&nbsp;<span id='periodStart-display'>" + $(startId).text() + "</span></span></label>";
              content += "</div>";
              content += "<div class='span6'>";
              content += "<label>"+lang("End")+": <span class='link periodEnd' data-date='" + $(endId).text() +"'>&nbsp;<span id='periodEnd-display'>" + $(endId).text() + "</span></span></label>";
              content += "</div>";
              content += "</div>";
              content += "</fieldset><br>";
            } else {
              content += "<a href='#' id='addDatesLink'>Add period start and end dates</a>";
              content += "<fieldset class='editInvoiceEntryPopup' style='display:none'>";
              content += "<legend><a href='#'><i class='icon-remove-sign icon-large'></i>&nbsp&nbsp"+lang("Period Dates")+"</a></legend>";
              content += "<div class='row-fluid'>"
              content += "<div class='span6'>";
              content += "<label>"+lang("Start")+": <span class='link periodStart' data-date=''>&nbsp;<span id='periodStart-display'>"+lang("Select date")+"</span></span></label>";
              content += "</div>";
              content += "<div class='span6'>";
              content += "<label>"+lang("End")+": <span class='link periodEnd' data-date=''>&nbsp;<span id='periodEnd-display'>"+lang("Select date")+"</span></span></label>";
              content += "</div>";
              content += "</div>";
              content += "</fieldset><br>";
            }

            new RichHTML.window({
                content : content,
                hideTitle: true,
                showSubmit: true,
                buttons: {button1:{text:lang('Save'),onclick:function(self,ret){
                    $.each(ret.elements,function(a,b){
                        if(b['name'] === "description"){
                            $(descId).text(b['value']);
                        } else if (b['name'] === "detail") {
                            $(detailId).html($.nl2br(b['value']));
                        } else if (b['name'] === "appliestoid") {
                            closestTr.attr('data-appliestoid', b['value']);
                            if(b['value'] == 0){
                                $(appliestoidId).text('');
                            }else{
                                reference = '';
                                for (index = 0; index < invoiceview.packages.length; ++index) {
                                    if(invoiceview.packages[index]['id'] == b['value']){
                                        reference = invoiceview.packages[index]['name'];
                                    }
                                }
                                $(appliestoidId).text(reference+' - ');
                            }
                        }
                    });
                    var start = $('span.periodStart').attr('data-date');
                    var end = $('span.periodEnd').attr('data-date');
                    if (start && end) {
                        startId.text(start);
                        endId.text(end);
                        startId.parent('div').show();
                    } else {
                        startId.text('');
                        endId.text('');
                        startId.parent('div').hide();
                    }
                    self.hide();
                    invoiceview.bindanchorsafterchanges();
                }},button2:{text:lang('Cancel')}}
            }).show();
            clientexec.postpageload('.richwindow');

            $('#addDatesLink').click(function() {
              $(this).hide();
              $(this).next().show();
            });

            $('span.periodStart')
                .datepicker(datePickerOpts)
                .on('changeDate', function(ev) {
                  changeDate(ev, function(formattedDate) {
                      $('span.periodStart').attr('data-date', formattedDate).datepicker('hide');
                      $('#periodStart-display').text(formattedDate);
                  });
                });

            $('span.periodEnd')
                .datepicker(datePickerOpts)
                .on('changeDate', function(ev) {
                  changeDate(ev, function(formattedDate) {
                      $('span.periodEnd').attr('data-date', formattedDate).datepicker('hide');
                      $('#periodEnd-display').text(formattedDate);
                  });
                });

            $('.editInvoiceEntryPopup > legend a').click(function() {
                $('.editInvoiceEntryPopup').hide();
                $('#addDatesLink').show();
                $('span.periodStart').attr('data-date', '');
                $('#periodStart-display').text('Select date');
                $('span.periodEnd').attr('data-date', '');
                $('#periodEnd-display').text('Select date');
            });
        });

        $("tr[data-entryid]").hover(
          function () {
            invoiceview.hoverentryid = $(this).attr('data-entryid');
            if(invoiceview.showentryactions){
                $(this).find('.icon_remove-wrap').show();
                $(this).find('.icon_menu').show();
            }
          },
          function () {
            $(this).find('.icon_remove-wrap').hide();
            $(this).find('.icon_menu').hide();
          }
        );

    };

    invoiceview.bindentries();
    invoiceview.calculatetotals();

});
