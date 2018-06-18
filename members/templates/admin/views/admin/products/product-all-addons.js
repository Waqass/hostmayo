productview.addons = {};

productview.all_addons_load = function() {
    $('#product-tab-content').load('index.php?nolog=1&fuse=admin&controller=products&view=addonstab&groupid='+productview.groupid+'&productid='+productview.productid, productview.postloadactions_addons);
};

productview.postloadactions_addons = function(e)
{
    clientexec.postpageload('#product-tab-content');

    productview.addons.columns= [
        {
            id:        'cb',
            dataIndex: 'id',
            xtype:     'checkbox'
        },{
            id: 'drag',
            xtype: 'drag'
        },{
            id:        'name',
            dataIndex: 'name',
            text:      lang('Name'),
            sortable:  false,
            align:'left',
            renderer: function (text,record) {
                record.description = record.description.replace(/\"/g, "&quot;")
                return '<span class="tip-target" data-toggle="tooltip" data-html="true" data-placement="right" title="'+record.description+'" style="word-wrap:break-word;overflow:hidden;z-index:100">'+text+"</span>";
            }
    },{
        id:        'plugin_var',
        dataIndex: 'plugin_var',
        text:      lang('Plugin Variable'),
        sortable:  false,
        width: '490',
        align:'right'
    },{
        id: 'id',
        dataIndex:'id',
        hidden:true
    }
    ];

    productview.addons.grid = new RichHTML.grid({
        el: 'addons-list',
        url: 'index.php?fuse=admin&controller=products&action=getaddonsforproduct&productid='+productview.productid,
        root: 'addon',
        columns: productview.addons.columns
    });
    productview.addons.grid.render();

    // **** listeners to grid
    $(productview.addons.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#btnDelAddon').removeAttr('disabled');
            } else {
                $('#btnDelAddon').attr('disabled','disabled');
            }
        },
        "drop": function(event,data){
            $.ajax({
                url: 'index.php?fuse=admin&controller=products&action=saveproductaddonorder&productid='+productview.productid,
                dataType: 'json',
                type: 'POST',
                data: {sessionHash: gHash,ids:productview.addons.grid.getRowValues('id')},
                success: function(data) {
                    data = ce.parseResponse(data);
                }
            });
        }
    });

    $('#product-addons-toadd').on('change',function(e){
        if (e.val == -1) {
            $('#btnAddAddon').attr('disabled','disabled');
        } else {
            $('#btnAddAddon').removeAttr('disabled');
        }
    });

    $('#product-addons-type').on('change',function(e){
        $.post("index.php?fuse=admin&controller=products&action=saveproductaddonstyle", {
            productid:productview.productid,style:e.val
        },
        function(data){
            data = ce.parseResponse(data);
        });
    });

    $('#btnAddAddon').bind('click',function(e){
        e.preventDefault();
        RichHTML.msgBox('<b>'+lang('WARNING!')+'</b>'+'<br><br>'+lang("Existing customers will be applied the default value: 'None' option if available, or the first option if not.")+'<br>'+lang('Are you sure you want to add the selected addon?'),
            {type:"yesno"}, function(result) {
                if(result.btn === lang("Yes")) {
                    productview.addons.grid.disable();
                    $.post("index.php?fuse=admin&controller=products&action=addaddontoproduct", {
                        productid:productview.productid,
                        id:$('#product-addons-toadd').val()
                    },
                    function(data){
                        data = ce.parseResponse(data);
                        productview.addons.grid.reload({ params:{start:0} });
                    });
                    $("#product-addons-toadd").select2("val", "-1");
                }
            });
    });

    $('#btnDelAddon').bind('click',function(e){
        if ($(this).attr('disabled')) { return false; }
        e.preventDefault();
        RichHTML.msgBox('<b>'+lang('NOTICE:')+'</b>'+'<br><br>'+lang('Do you want existing customers to continue being charged this addon?'),
            {type:"confirm"}, function(result) {
                if(result.btn === lang("Yes")) {
                    productview.addons.grid.disable();
                    $.post("index.php?fuse=admin&controller=products&action=deleteaddonsfromproduct", {
                        keeprecurringfees:1,
                        productid:productview.productid,
                        ids:productview.addons.grid.getSelectedRowIds()
                    },
                    function(data){
                        productview.addons.grid.reload({ params:{start:0} });
                    });
                }else if(result.btn === lang("No")) {
                    productview.addons.grid.disable();
                    $.post("index.php?fuse=admin&controller=products&action=deleteaddonsfromproduct", {
                        keeprecurringfees:0,
                        productid:productview.productid,
                        ids:productview.addons.grid.getSelectedRowIds()
                    },
                    function(data){
                        productview.addons.grid.reload({ params:{start:0} });
                    });
                }
            });
    });

};
