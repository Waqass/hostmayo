var uninvoicedlist = {};

$(document).ready(function() {


    uninvoicedlist.renderDescription = function (value, record, el) {
    	var html, text = "";

    	html  = "<b>[#"+record.invoiceentryid+']</b> <a href="javascript:ShowEditInvoiceEntryWindow('+record.invoiceentryid+',false, false, '+record.clientid+');" id="'+record.invoiceentryid+'" title="Edit this entry"><b>'+record.descriptionplain+'</b></a>';
    	customerName = String.format("<a href='index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID={1}' style='text-shadow: none;color:#BD5A35'>{0}</a>",record.customername,record.clientid);

    	html += "<br/><span>"+text+" for "+customerName;

    	if (record.appliestoid > 0) {
    		html += " charge applies to <a style='color:#BD5A35;' href='index.php?fuse=clients&controller=userprofile&frmClientID="+record.clientid+"&view=profileproducts&packageid="+record.appliestoid+"'>product: #"+record.appliestoid+"</a>";
    	}

    	html += "</span>"

    	return html;
    }

    $('#uninvoicedgrid-filter').change(function(){
    	uninvoicedlist.grid.reload({params:{start:0,limit:$(this).val()}});
    });


	uninvoicedlist.renderDueDate = function (value, record, el) {
	    var html;
	    html  = "Due: <b>"+record.billdate+"</b>";
	    html += "<Br/>Price: "+record.billbalancedue;

		return html;
	}

    uninvoicedlist.grid = new RichHTML.grid({
        el: 'uninvoicedgrid',
        url: 'index.php?fuse=billing&controller=invoice&action=getuninvoiced',
        root: 'uninvoiced',
        totalProperty: 'totalcount',
	baseParams: { limit: clientexec.records_per_view, dir: 'asc'},
        columns: [
            {
                id:         "cb",
                dataIndex:  "clientidandinvoiceentryid",
                xtype:      "checkbox"
            },{
                id:         "description",
                text:     	lang("Description"),
                align:		"left",
                renderer:   uninvoicedlist.renderDescription,
                dataIndex:  "id",
                sortable: 	true
            }, {
	            id: 'billdate',
	            text: lang("Due Date"),
	            renderer: uninvoicedlist.renderDueDate,
	            dataIndex : "date",
	            width: 110,
	            align:"left",
                sortable: 	true
            }
        ]
    });

    // **** listeners to grid
    $(uninvoicedlist.grid).bind({
	    "rowselect": function(event,data) {
	        if (data.totalSelected > 0) {
	        	$('#btnDelCharges').removeAttr('disabled');
	        	$('#btnCreateInvoice').removeAttr('disabled');
	        } else {
	        	$('#btnDelCharges').attr('disabled','disabled');
	        	$('#btnCreateInvoice').attr('disabled','disabled');
	        }
	    }
    });

    // **** lets bind our buttons
    $('#btnDelCharges').click(
        function () {
            if ($(this).attr('disabled')) { return false; }
            RichHTML.msgBox(
                lang('Are you sure you want to delete the selected charges(s)'),
                {
                    type:"confirm"
                },
                function(result) {
                    if(result.btn === lang("Yes")) {
                        uninvoicedlist.grid.disable();
                        $.post(
                            "index.php?fuse=billing&controller=invoice&action=actoninvoice",
                            {itemstype:'uninvoiced',actionbutton:'deletebutton',items:uninvoicedlist.grid.getSelectedRowIds()},
                            function(data){
                                uninvoicedlist.grid.reload({params:{start:0}});
                            }
                        );
                    }
                }
            );
        }
    );


    $('#btnCreateInvoice').click(
        function () {
            if ($(this).attr('disabled')) { return false; }
            RichHTML.msgBox(
                lang('Are you sure you want to create invoice(s) with the selected charges(s)'),
                {
                    type:"confirm"
                },
                function(result) {
                    if(result.btn === lang("Yes")) {
                        uninvoicedlist.grid.disable();
                        $.post(
                            "index.php?fuse=billing&controller=invoice&action=actoninvoice",
                            {itemstype:'uninvoiced',actionbutton:'createinvoicebutton',items:uninvoicedlist.grid.getSelectedRowIds()},
                            function(data){
                                uninvoicedlist.grid.reload({params:{start:0}});
                            }
                        );
                    }
                }
            );
        }
    );


    uninvoicedlist.grid.render();

});