var coupons =  {};
coupons.viewingArchived = false;

$(document).ready(function() {
    if ( document.getElementById("couponFilter").value == -1 ) {
        coupons.viewingArchived = true;
        $('#archiveCouponButton span').text(lang('Unarchive'));
    }

    coupons.grid = new RichHTML.grid({
        el: 'coupons-grid',
        metaEl: 'coupons-grid-metadata',
        url: 'index.php?fuse=billing&action=getcoupons&controller=coupon',
        root: 'coupons',
        baseParams: { id: 'asc', active: document.getElementById("couponFilter").value },
        columns: [{
            xtype: "expander",
            dataIndex: "response",
            renderer: function(text, row) {
                if ( row.duration == "---" ) row.duration = "Indefinite";

                html = '';
                html += "<b>Code</b>: <span style='color:green'>"+ row.code+"</span></b>";
                html += "<br/><b>Description</b>: "+ row.description+"</b>";
                html += "<br/><b>Duration</b>: "+ row.duration+"</b>";
                html += "<br/><b>Applies To</b>: " + row.packages;
                html += "<br/><b>Taxable</b>: " + row.taxable;
                return html;
            }
        },{
            id: "id",
            dataIndex: "id",
            xtype: "checkbox"
        }, {
            id: "name",
            dataIndex: "name",
            text: lang("Name"),
            sortable: false,
            renderer: function(text, row) {
                var tooltip = row.description.replace(/"/g, "&#34;");
                return '<a data-toggle="tooltip" title="' + tooltip + '" href="index.php?fuse=billing&view=addeditcoupon&controller=coupon&id=' + row.id + '&viewingArchived=' + coupons.viewingArchived +'">' + row.name + '</a>';
            },
            flex: 1
        },{
            id: 'code',
            text: lang('Code'),
            dataIndex: 'code',
            sortable: false,
            width: 150,
        },{
            id: "discount",
            text:  lang("Discount"),
            dataIndex: "discount",
            sortable: false,
            width: 100
        },{
            id: "recurring",
            text:  lang("Recurring"),
            dataIndex: "recurring",
            sortable: false,
            width: 60
        },{
            id: "billingcycles",
            text:  lang("Billing Cycles"),
            dataIndex: "billingcycles",
            sortable: false,
            width: 150
        },{
            id: "ineffect",
            text:  lang("In Effect"),
            dataIndex: "ineffect",
            sortable: false,
            width: 50
        }]
    });

    coupons.grid.render();

     $(coupons.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteCouponButton').removeAttr('disabled');
                $('#archiveCouponButton').removeAttr('disabled');
            } else {
                $('#deleteCouponButton').attr('disabled','disabled');
                $('#archiveCouponButton').attr('disabled','disabled');
            }
        }
    });

      $('#deleteCouponButton').click(function () {
        RichHTML.msgBox(lang('Are you sure you want to delete the selected coupon(s)?'),
        {
            type:"confirm"
        }, function(result) {
            if ( result.btn === lang("Yes") ) {
                $.post("index.php?fuse=billing&action=delete&controller=coupon", { ids: coupons.grid.getSelectedRowIds() },
                function(){
                    coupons.grid.reload({params:{start:0}});
                });
            }
        });
    });

    $('#archiveCouponButton').click(function () {
        if( coupons.viewingArchived ) {
            requestURL = "index.php?fuse=billing&action=unarchive&controller=coupon";
            msg = lang('Are you sure you want to unarchive the selected coupon(s)?');
        } else {
            requestURL = "index.php?fuse=billing&action=archive&controller=coupon";
            msg = lang('Are you sure you want to archive the selected coupon(s)?');
        }

        RichHTML.msgBox(msg,
        {
            type:"confirm"
        }, function(result) {
            if ( result.btn === lang("Yes") ) {
                $.post(requestURL, { ids: coupons.grid.getSelectedRowIds() },
                function(){
                    coupons.grid.reload({params:{start:0}});
                });
            }
        });
    });

    $('#addCouponButton').click(function() {
        window.location = "index.php?fuse=billing&view=addeditcoupon&controller=coupon&viewingArchived=" + coupons.viewingArchived;
    })
});