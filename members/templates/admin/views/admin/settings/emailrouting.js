var emailRouting = {
    dom: {
        divContentSlideWrapper: $('#div-contentSlideWrapper'),
        divEmailRoutingList: $('#div-emailRoutingList'),
        divvtabContent: $('#vtabContent'),
        buttonFilters: $('#button-filters'),
        buttonAddRule: $('#button-addRule'),
        divEmailRoutingGrid: $('#div-emailRouting-grid'),
        buttonDeleteRule: $('#button-deleteRule')
    },
    renderer: {
        rule: function(text, row) {
            return '<span class="bold">'+lang('Users Affected')+':</span> '+row.userType+'<br />'+
                '<span class="bold">'+lang('Source')+':</span> '+row.type+'<br />'+
                '<span class="bold">'+lang('Email’s Checked')+':</span> '+row.emails+'<br />'+
                '<span class="bold">'+lang('Open Ticket ?')+':</span> '+row.createTicket;
        },
        name: function(text, row) {
            return '<a href="index.php?fuse=support&controller=routing&view=rule&id='+row.id+'">'+text+'</a>';
        }
    },
    window: {
        filters: new RichHTML.window({
            id: 'div-routingFilters',
            escClose: true,
            showSubmit: true,
            width: '557',
            height: '315',
            title: lang('Email Routing Filters'),
            url: 'index.php?fuse=support&controller=routing&view=viewfilters',
            actionUrl: 'index.php?fuse=support&controller=routing&action=savefilters',
        })
    }

};

emailRouting.grid = new RichHTML.grid({
    el: 'div-emailRouting-grid',
    url: 'index.php?fuse=support&controller=routing&action=getlist',
    root: 'groups',
    columns: [
        {
            xtype: 'expander',
            id: 'row-expand',
            renderer: emailRouting.renderer.rule
        },{
            xtype: 'checkbox',
            id: 'row-checkbox',
            dataIndex: 'id'
        },{
            text:       "",
            xtype:      "drag"
        },{
            id: 'name',
            dataIndex: 'name',
            text: lang('Name'),
            sortable: false,
            renderer: emailRouting.renderer.name,
            flex: 1
        },{
            id: 'template',
            dataIndex: 'autoresponder',
            text: lang('Autoresponder'),
            sortable: false,
            width: 125
        },{
            id: 'id',
            dataIndex : "id",
            hidden:true
        },
    ]
});

$(document).ready(function(){

    $(emailRouting.grid).bind({
         "drop": function (event, data) {
            $.ajax({
                url: 'index.php?fuse=support&controller=routing&action=updateorder',
                dataType: 'json',
                type: 'POST',
                data: {
                    ids: emailRouting.grid.getRowValues('id')
                },
                success: function(data) {}
            });
        },
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                emailRouting.dom.buttonDeleteRule.prop('disabled', false);
            } else {
                emailRouting.dom.buttonDeleteRule.prop('disabled', true);
            }
        }
    });

    emailRouting.dom.buttonDeleteRule.click(function(e) {
        RichHTML.msgBox(lang('Are you sure you want to delete the selected routing rules(s)'),
            {
                type:'confirm'
            }, function(result) {
                if ( result.btn === lang("Yes") ) {
                    $.post('index.php?fuse=support&controller=routing&action=delete', { routingIds: emailRouting.grid.getSelectedRowIds() },
                    function(data){
                        emailRouting.grid.reload();
                    });
                }
            }
        );
        e.preventDefault();
    });

    emailRouting.dom.buttonAddRule.click(function(e) {
        window.location.href = 'index.php?fuse=support&controller=routing&view=rule&id=0';
        e.preventDefault();
    });

    emailRouting.dom.buttonFilters.click(function(){
        emailRouting.window.filters.show();
    });

    emailRouting.grid.render();

});