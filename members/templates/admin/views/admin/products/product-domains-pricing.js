productview.tlds = {};

productview.domains_pricing_load = function() {
    $('#product-tab-content').load('index.php?nolog=1&fuse=admin&controller=products&view=pricingtabfordomains&groupid='+productview.groupid+'&productid='+productview.productid, productview.postloadactions_domainspricing );
};

productview.postloadactions_domainspricing = function()
{

    productview.tlds.columns = [
        {
            id:        'cb',
            dataIndex: 'id',
            xtype:     'checkbox',
            renderer: function(text,record,el)
            {
                if(record.period == "none") el.addClass = "hide-checkbox";
            }
        },{
        id:        'period',
        dataIndex: 'period',
        text:      lang('Billing Period(s)'),
        sortable:  false,
        align:'left',
        flex: 1,
        renderer: function(text,record) {
            var label;
            if (text==0) {
                label = lang("One Time");
            } else if (text==1) {
                label = text + " "+lang("Year");
            } else if (text != "none") {
                label = text + " "+lang("Years");
            } else {
                label = "<span style='position: relative;left: -30px;' class='label label-important'>No pricing exists for this tld.  Click to add one.</span>";
            }

            return "<a href='javascript:void(0)' onclick='productview.tlds.showperiodwindow(\""+record.tldraw+"\",\""+text+"\");' class='link'>"+label+"</a>";
        }
    },{
        id:        'price',
        dataIndex: 'price',
        text:      lang('Price'),
        sortable:  false,
        width: '60',
        align:'right',
        renderer: function(text,record) {
            if (record.period == "none") return "";
            return accounting.formatMoney(text, productview.currency.symbol, productview.currency.precision, productview.currency.thousandssep, productview.currency.decimalssep, productview.currency.alignment);
        }
    },{
        id:        'transfer',
        dataIndex: 'transfer',
        text:      lang('Transfer'),
        sortable:  false,
        width: '60',
        align:'right',
        renderer: function(text,record) {
            if (record.period == "none") return "";
            if ( text == '' ) { return lang('N/A') }
            return accounting.formatMoney(text, productview.currency.symbol, productview.currency.precision, productview.currency.thousandssep, productview.currency.decimalssep, productview.currency.alignment);
        }
    },{
        id:        'renew',
        dataIndex: 'renew',
        text:      lang('Renew'),
        sortable:  false,
        width: '60',
        align:'right',
        renderer: function(text,record) {
            if (record.period == "none") return "";
            return accounting.formatMoney(text, productview.currency.symbol, productview.currency.precision, productview.currency.thousandssep, productview.currency.decimalssep, productview.currency.alignment);
        }
    }
    ];

    productview.tlds.grid = new RichHTML.grid({
        el: 'tld-list',
        url: 'index.php?fuse=admin&controller=products&action=gettldsgrid&productId='+productview.productid,
        root: 'results',
        startCollapsed: true,
        columns: productview.tlds.columns
    });
    productview.tlds.grid.render();
    //onload of grid do the following

    productview.tlds.periodwindow = new RichHTML.window({
        id: 'groupwindow',
        url: 'index.php?fuse=admin&controller=products&view=tldperiod',
        actionUrl: 'index.php?fuse=admin&controller=products&action=savetldperiod',
        width: '320',
        height: '150',
        grid: productview.tlds.grid,
        showSubmit: true,
        title: lang("TLD Setup Window")
    });

    //let's determine if we want to make the product groups editable
    productview.tlds.showperiodwindow = function(tld,period) {
        if(!period) period = "none";
        productview.tlds.periodwindow.show({params:{period:period,tld:tld,productid:productview.productid}});
    };

    // **** listeners to grid
    $(productview.tlds.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#btnDelTLDPeriod').removeAttr('disabled');
            } else {
                $('#btnDelTLDPeriod').attr('disabled','disabled');
            }
        }
    });

    $('#taxdomainorders').click(function () {
        $.post("index.php?fuse=admin&controller=products&action=updatedomaintax",
        {productid:productview.productid,taxable: $(this).is(':checked')},
        function(data){
            json = ce.parseResponse(data);
        });
    });

    $('#latefee').change(function () {
        $.post("index.php?fuse=admin&controller=products&action=updatedomainlatefee",
        {productid:productview.productid,latefee: $(this).val()},
        function(data){
            json = ce.parseResponse(data);
        });
    });

    // **** lets bind our buttons
    $('#btnDelTLDPeriod').click(function () {
        if ($(this).attr('disabled')) { return false; }

        RichHTML.msgBox(lang('Are you sure you want to delete the selected TLD billing period(s)'),
            {type:"yesno"}, function(result) {
                if(result.btn === lang("Yes")) {
                    productview.tlds.grid.disable();
                    $.post("index.php?fuse=admin&controller=products&action=deleteTldPeriod", {
                        productid:productview.productid,ids:productview.tlds.grid.getSelectedRowIds()
                    },
                    function(data){
                        productview.tlds.grid.reload({ params:{start:0} });
                    });
                }
            });
    });


};