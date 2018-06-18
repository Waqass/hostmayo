var productaddons = {};

productaddons.grid = new RichHTML.grid({
    el: 'addon-list',
    url: 'index.php?fuse=admin&controller=addons&action=admingetaddons',
    root: 'addons',
    baseParams: { filter: $('#addon-list-filter').val()},
    columns: [
    {
        text: "",
        xtype: "expander",
        renderOnExpand: true,
        renderer: function(value,record,el){
            var content = "<div style='padding-left:23px;padding-bottom:10px;'>";
            content += "<strong>"+lang("Description")+":</strong> " + ce.unhtmlentities(record.description);
            content += "<br/><strong>"+lang("Plugin Variable")+":</strong> " + record.plugin_var_name.replace("CUSTOM_","");
            content += "<br/><strong>"+lang("Used in products")+":</strong>";
            $.ajax({
              url: 'index.php?fuse=admin&controller=addons&action=admingetaddonsused',
              dataType: 'json',
              async: false,
              data: {addonid: record.id},
              success: function(data) {
                data = ce.parseResponse(data);
                if (data.products.length > 0) {
                    $.each(data.products,function(index, product) {
                        content = content + "<div>"+product.productname+" "+"("+product.productid+")</div>";
                    });
                } else {
                    content += '<div>'+ lang("Not used in any products") +"</div>";
                }
                content = content +  "</div>";
              }
            });
            return content;
        }
    },{
        id:        'cb',
        dataIndex: 'id',
        xtype:     'checkbox'
    },{
        id: 'name',
        dataIndex: 'name',
        text: 'Name',
        align: 'left',
        renderer: function(text,record) {
            if ( text == '' ) {
                text = 'N/A';
            }
            text = "<a href='index.php?fuse=admin&controller=addons&view=productaddon&id="+record.id+"'>"+text+"</a>";
            if (record.plugin_var_name !== "None") {
                text = text + " <span style='float:right;' class='label label-inverse'>" +lang("Plugin Variable")+": " + record.plugin_var_name.replace("CUSTOM_","") +"</span>";
            }
            return text;
        }
    }]
});

$(document).ready(function(){

    productaddons.grid.render();

    $('#addon-list-filter').change(function(){
        productaddons.grid.reload({params:{filter:$(this).val()}});
    });

    $('#addaddon').bind('click',function(){
        window.location.href = 'index.php?fuse=admin&controller=addons&view=productaddon';
    });

    // **** listeners to grid
    $(productaddons.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected === 1) {
                $('#buttonDelete').removeAttr('disabled');
            } else {
                $('#buttonDelete').attr('disabled','disabled');
            }
        }
    });

    $('#buttonDelete').bind('click',function(){
        if ($(this).attr('disabled')) {
            return false;
        }

        RichHTML.msgBox('<b>'+lang('NOTICE:')+'</b>'+'<br><br>'+lang('Do you want existing customers to continue being charged this addon?'),
            {type:"confirm"}, function(result) {
                if(result.btn === lang("Yes")) {
                    productaddons.grid.disable();
                    $.post("index.php?fuse=admin&controller=addons&action=deleteaddonwithrevalidation", {
                        keeprecurringfees:1,
                        ids:productaddons.grid.getSelectedRowIds()
                    },
                    function(data){
                        data = ce.parseResponse(data);
                        productaddons.grid.reload();
                    });
                }else if(result.btn === lang("No")) {
                    productaddons.grid.disable();
                    $.post("index.php?fuse=admin&controller=addons&action=deleteaddonwithrevalidation", {
                        keeprecurringfees:0,
                        ids:productaddons.grid.getSelectedRowIds()
                    },
                    function(data){
                        data = ce.parseResponse(data);
                        productaddons.grid.reload();
                    });
                }
            });
    });
});

