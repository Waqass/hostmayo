invoices = invoices || {};

$(document).ready(function() {
    // ** grid definition
    invoices.grid = new RichHTML.grid({
        el: 'invoices-grid',
        url: 'index.php?fuse=billing&controller=invoice&action=getinvoices',
        baseParams: { limit: 15, sort: 'id', dir: 'asc', filter:invoices.filter},
        root: 'invoices',
        totalProperty: 'total',
        columns: [{
            id: "id",
            dataIndex: "id",
            xtype: 'checkbox'
        },{
            id: "id",
            dataIndex: "id",
            text: lang("#"),
            align: "left",
            sortable: true,
            width: 50,
            renderer: function(value) {
                return '#' + value;
            }
        },{
            id: 'detailed_description',
            text: lang('Description'),
            dataIndex: 'detailed_description',
            align:"left",
            flex: 1,
            renderer: function(value, row) {
                return '<a href="index.php?fuse=billing&controller=invoice&view=invoice&id=' + row.id + '">' + ce.htmlspecialchars(value) + '</a>&nbsp;&nbsp;<span class="invoicepdflink"><a href="index.php?sessionHash=' + clientexec.sessionHash + '&fuse=billing&controller=invoice&action=generatepdfinvoice&invoiceid=' + row.id + '" target="_blank"><img class="pdfimage" src="templates/admin/images/document-pdf-text.png" border="0" data-toggle="tooltip" title="' + lang('View PDF Invoice') + '" /></a></span>';
            }
        },{
            id: 'billdate',
            text: lang('Due Date'),
            dataIndex: 'billdate',
            width: 100,
            align:"center",
            sortable: true,
        },{
            id: 'formatedbalancedue',
            text: lang('Due'),
            dataIndex: 'formatedbalancedue',
            width: 100,
            align:"center",
            sortable: true,
            renderer : function (val, row) {

                var font_class = "";
                var due = row.formatedbalancedue;
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
            id: 'amount',
            text: lang('Total'),
            dataIndex: 'amount',
            width: 80,
            align:"center",
            sortable: true,
            renderer : function (val, row) {

                var font_class = "";
                var due = row.amount;
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
            id: 'status_name',
            text: lang('Status'),
            dataIndex: 'status_name',
            width: 80,
            align:"center",
            sortable: false,
        }]
    });

    invoices.grid.render();

    $(invoices.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#sendInvoice').removeAttr('disabled');
            } else {
                $('#sendInvoice').attr('disabled','disabled');
            }
        }
    });

    $('#sendInvoice').click(function() {
        RichHTML.msgBox(lang('Are you sure you want to send the selected invoice(s)'),
        {
            type:"confirm"
        }, function(result) {
            if(result.btn === lang("Yes")) {
                invoices.grid.disable();

                var data = {
                        items:          invoices.grid.getSelectedRowIds(),
                        itemstype:      'invoices',
                        actionbutton:   'inv-send-smart'
                    };

                $.ajax({
                    url: "index.php?fuse=billing&controller=invoice&action=actoninvoice",
                    type: 'POST',
                    data:  data,
                    success:  function(xhr){
                        invoices.grid.enable();
                    }
                });
            }
        });
    });

    $('#filter-ul li > a').click(function() {
        $('#filter-ul li').removeClass('active');
        $(this).parent().addClass('active');
    });

});

invoices.filterBy = function(value) {
    invoices.filter = value;
    History.pushState({}, document.title, 'index.php?fuse=billing&controller=invoice&view=allinvoices&filter=' + invoices.filter);
    invoices.grid.reload({params:{start:0,filter:invoices.filter}});
}
