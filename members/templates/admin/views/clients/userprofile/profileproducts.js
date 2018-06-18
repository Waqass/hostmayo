var userpackages = userpackages || {};

$(document).ready(function(){

    $('.btn-welcome-email').bind('click',function(){
        var self = this;
        if ($(this).attr('disabled')) { return false; }
        $(this).attr('disabled','disabled');
        $.post("index.php?fuse=clients&controller=packages&action=sendwelcomeemail", {
            ids:userpackages.grid.getSelectedRowIds()
        },
        function(data){
            ce.parseResponse(data);
            $(self).removeAttr('disabled');
        });
    });

    $('.btn-delete-products').bind('click',function(){
        if ($(this).attr('disabled')) { return false; }

        RichHTML.msgBox(lang('Are you sure you want to delete the selected package(s)'),
                {type:"yesno"}, function(result) {
                    if(result.btn === lang("Yes")) {
                        userpackages.grid.disable();
                        $.post("index.php?fuse=clients&controller=packages&action=admindeletepackages", {
                            ids:userpackages.grid.getSelectedRowIds()
                        },
                        function(data){
                            ce.parseResponse(data);
                            userpackages.grid.reload({ params:{ start:0 } });
                            setTimeout(function() {
                                profile.get_counts();
                            },1000);
                        });
                    }
                });
    });

    userpackages.grid = new RichHTML.grid({
        el: "domains-grid",
        url: "index.php?fuse=clients&action=admingetuserpackages&filteroncustomer=1",
        baseParams: { limit:100, sort: 'id', dir: 'asc'},
        totalProperty: 'totalcount',
        editable: true,
        root: 'results',
        columns: [{
            id: 'id',
            xtype: 'checkbox',
            dataIndex: 'productid',
            text: ''
        },{
            id: 'name',
            dataIndex: 'id',
            align: 'left',
            sortable: true,
            text: lang('Description'),
            flex: 1,
            renderer: function( text, record, el) {
                var name;
                name = '<div><a href="index.php?fuse=clients&controller=userprofile&view=profileproduct&id=' + record.productid + '" >' + ce.htmlspecialchars(record.name) + '</a>';
                name += "<div style='padding-top:4px;'>Group: "+record.productgroupname+" "+record.desc_details+"</div>";
                return name;
            }
        },{
            text: lang("Next Payment Date"),
            width: '127',
            sortable: false,
            align:'center',
            dataIndex: 'renewal',
            renderer: function (text, record) {
                if (record.recurringcharges == "----") {
                return "----";
                } else {
                    return '<a href="index.php?fuse=clients&controller=userprofile&view=profilerecurringcharges">' + record.renewal + '</a>';
                }
            }
        },{
            text: lang("Invoice Amount"),
            width: '127',
            sortable: false,
            align:'center',
            dataIndex: 'recurringcharges',
            hidden: true
        },{
            text: lang("Status"),
            width: '70',
            sortable: true,
            align:'center',
            dataIndex: 'status',
            renderer: function (text, record, el)
            {
                el.addClass = record.status_class;
                return record.status;
            }
        }
        ].concat(userpackages.config.customFields)
    });
    userpackages.grid.render();

    $(userpackages.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('.btn-delete-products').removeAttr('disabled');
                $('.btn-welcome-email').removeAttr('disabled');
            } else {
                $('.btn-delete-products').attr('disabled','disabled');
                $('.btn-welcome-email').attr('disabled','disabled');
            }
        }
    });

    $('#addproductbutton').bind('click',function(){
        userpackages.addproductwin = new RichHTML.window({
            url: 'index.php?view=profileaddproduct&fuse=clients&controller=userprofile',
            actionUrl: 'index.php?fuse=clients&action=saveproductforcustomer&controller=index',
            width: '350',
            height: '160',
            title: lang('Add a new product'),
            showSubmit: true,
            onSubmit: function(json){
                if ( json.error === false && json.newid > 0 ) {
                    window.location.href = "index.php?fuse=clients&controller=userprofile&view=profileproduct&id="+json.newid;
                }
            }
        });
        userpackages.addproductwin.show();

    });

    $('#userpackages-grid-package-filter').change(function(){
        userpackages.grid.reload({
            params:{
                start: 0,
                status: $(this).val(),
                type: $('#userpackages-grid-package-type-filter').val()
            }
        });
    });

    $('#userpackages-grid-package-type-filter').change(function(){
        userpackages.grid.reload({
            params:{
                start: 0,
                type: $(this).val(),
                status: $('#userpackages-grid-package-filter').val()
            }
        });
    });
});