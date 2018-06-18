var transactions = {};
$(document).ready(function(){

    transactions.grid = new RichHTML.grid({
        el: 'transactions-grid',
        url: "index.php?fuse=admin&action=doplugin&controller=plugin&type=snapin&plugin=cetransactions&pluginaction=getTransactions",
        baseParams: {
            userid: clientexec.customerId,
            limit: clientexec.records_per_view,
            sort: 'id',
            dir: 'asc'
        },
        editable: true,
        root: 'data',
        columns: [
        {
            text:       lang("Date"),
            dataIndex:  "transactiondate",
            align:      "left",
            width:      180,
            sortable:    true
        },{
            id: "invoiceid",
            text: lang("Invoice"),
            dataIndex: "invoiceid",
            width: 70,
            align: 'center',
            renderer: function(val, row) {
                var url = "index.php?controller=invoice&fuse=billing&frmClientID="+row.userid+"&view=invoice&invoiceid="+val;
                return "<a href='"+url+"'>#"+val+"</a>";
            },
            sortable: false
        },{
            text:       lang("Transaction Id"),
            dataIndex:  "transactionid",
            align:      "right",
            width:      150,
            sortable:    true
        },{
            text:       lang("Amount"),
            dataIndex:  "amount",
            align:      "right",
            hidden: true,
            width:      100,
            sortable:    true
        },{
            text:       lang("Result"),
            dataIndex: "response",
            align:      "left",
            flex: 1,
            sortable:    false,
            renderer: function(text, row) {
                return ce.htmlspecialchars(row.response);
            }
        },{
            text:       lang("Action"),
            dataIndex:  "action",
            align:      "center",
            width:      100,
            sortable:    true
        },{
            text:       lang("Plugin"),
            dataIndex:  "pluginused",
            align:      "center",
            renderer:   function(val, row) {
                if (row.last4 != "NA" && row.last4 != "" && row.last4 != "0000") {
                    val = val + "<br>(" + row.last4 + ')'
                }
                return val;
            },
            width:      100,
            sortable:    true
        }
        ]
    });
    transactions.grid.render();
});
