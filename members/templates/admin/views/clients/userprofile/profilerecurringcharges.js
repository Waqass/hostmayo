var recurringCharges = {
    dom: {

    },
    renderer: {
        name: function(name, row) {
            var tSubscriptionId = '';
            if ( row.subscriptionid != null && row.subscriptionid != "" ) {
               tSubscriptionId = '<br><span class="addition_description">Subscription: '+row.subscriptionid+' - <a onclick="recurringCharges.cancelSub(\''+row.subscriptionid+'\');" href="#">Cancel Subscription</a></span>';
            }
            name = '<a onclick="recurringCharges.window.show({params:{id:'+row.id+'}}); $(\'#rich-button-submit\').hide();" data-toggle="tooltip" data-html="true" title="<b>Description</b>: '+row.desc+'">'+ce.htmlspecialchars(row.name)+'</a> '+row.disablegenerate + tSubscriptionId;
            return name;
        },
        taxable: function(name, row) {
            if ( row.taxable == 'off' ) {
                return lang('No');
            } else {
                return lang('Yes');
            }
        },
        cycle: function(name, row) {
            montlyusage = '';
            if(row.monthlytotal != '-'){
                montlyusage = " "+row.monthlyused+"/"+row.monthlytotal;
            }
            return row.paymentterm_word+montlyusage;
        }
    }
};
recurringCharges.grid = new RichHTML.grid({
    el: 'div-grid-recurringCharges',
    url: 'index.php?fuse=billing&controller=recurring&action=getrecurringcharges',
    root: 'data',
    groupField: 'package',
    baseParams: { sort: 'nextdate', dir: 'asc'},
    columns: [
        {
            id: 'id',
            dataIndex: 'id',
            xtype: 'checkbox',
            renderer: function(text, row, el) {
                if( row.billingtypeid == -1 || (row.readonly && !row.candelete) ){
                    el.addClass = 'hide-checkbox';
                }
            }
        },
        {
            id: 'nextDate',
            dataIndex: 'nextdate',
            text: lang('Next Date'),
            sortable: true,
            align: 'center',
            width: 100
        },
        {
            id: 'billingType',
            dataIndex: 'billingtype',
            text: lang('Billing Type'),
            sortable: true,
            align: 'center',
            width: 150
        },
        {
            id: 'name',
            dataIndex: 'name',
            text: lang('Name'),
            sortable: true,
            align: 'left',
            renderer: recurringCharges.renderer.name,
            flex: 1
        },
        {
            dataIndex: 'amount',
            text: lang('Amount'),
            align: 'center',
            width: 150
        },
        {
            dataIndex: 'taxable',
            text: lang('Taxable'),
            align: 'center',
            renderer: recurringCharges.renderer.taxable,
            width: 75
        },
        {
            dataIndex: 'cycle',
            text: lang('Cycle'),
            sortable: false,
            align: 'center',
            renderer: recurringCharges.renderer.cycle,
            width: 100
        }
    ]
});

recurringCharges.window = new RichHTML.window({
    height: '510',
    width: '515',
    grid: recurringCharges.grid,
    url: 'index.php?fuse=billing&view=recurringcharge&controller=recurring',
    actionUrl: 'index.php?action=save&controller=recurring&fuse=billing',
    showSubmit: true,
    title: lang("Add Recurring Charge"),
    onSubmit: function(data) {
        setTimeout(function() {
            profile.get_counts();
        },1000);
    }
});

recurringCharges.cancelSub = function(subscriptionId) {
    RichHTML.msgBox(lang("Are sure you want to cancel this subscription?"),{type:'yesno'},function(ret) {
        if (ret.btn == lang("No")) {
            recurringCharges.grid.enable();
            return;
        }

        $.ajax({
            url: "index.php?fuse=billing&controller=recurring&action=cancelsubscription",
            type: 'POST',
            data:  { id: subscriptionId },
            success:  function(xhr){
                ce.parseResponse(xhr);
                recurringCharges.grid.reload();
            }
        });
    });
};

$(document).ready(function(){
    recurringCharges.grid.render();

    $(recurringCharges.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteEntry').removeAttr('disabled');
            } else {
                $('#deleteEntry').attr('disabled','disabled');
            }
        }
    });

    $('#button-addCharge').click(function(){
        recurringCharges.window.show();
        $('#rich-button-submit').hide();
    });

    $('#deleteEntry').click(function() {
        RichHTML.msgBox(lang('Are you sure you want to delete the selected recurring fee(s)'),
        {
            type:"confirm"
        }, function(result) {
            if(result.btn === lang("Yes")) {
                $.post("index.php?action=delete&controller=recurring&fuse=billing", {
                    ids:recurringCharges.grid.getSelectedRowIds()
                },
                function(data){
                    recurringCharges.grid.reload();
                    setTimeout(function() {
                        profile.get_counts();
                    },1000);
                });
            }
        });
    });
});