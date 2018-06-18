var templates = {}

$(document).ready(function() {
    templates.grid = new RichHTML.grid({
        el: 'emails-grid',
        metaEl: 'emails-grid-metadata',
        url: 'index.php?fuse=admin&action=getemailtemplates&controller=emails',
        root: 'templates',
        baseParams: { templatetype: 2 },
        columns: [{
            id: "id",
            dataIndex: "id",
            xtype: "checkbox"
        }, {
            id: "templateName",
            dataIndex: "templateName",
            text: lang("Name"),
            sortable: false,
            renderer: function(text, row) {
                var tooltip = '<b>Description</b>: ' + row.templateDescription;
                return '<a onclick="templates.window.show({params:{id:'+row.id+'}});" data-toggle="tooltip" data-placement="right" data-html="true" title="'+ tooltip + '">' + row.templateName + '</a>';
            },
            flex: 1
        },{
            id: "templateTypeName",
            text:  lang("Type"),
            dataIndex: "templateTypeName",
            sortable: false,
            width: 250
        }]
    });
    templates.grid.render();

    $(templates.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteTemplateButton').removeAttr('disabled');
            } else {
                $('#deleteTemplateButton').attr('disabled','disabled');
            }
        }
    });

    $('#deleteTemplateButton').click(function () {
        RichHTML.msgBox(lang('Are you sure you want to delete the selected email templates(s)?'),
        {
            type:"yesno"
        }, function(result) {
            if ( result.btn === lang("Yes") ) {
                var rows = templates.grid.getSelectedRowData();
                var defaultTemps = false;
                var otherTemps = false;
                $.each(rows, function (i, row) {
                    if ( row.templateType != 1 && row.templateType != 2 && row.templateType != 8  && row.templateType != 9)  {
                        defaultTemps = true;
                    } else {
                        otherTemps = true;
                    }
                });
                if (defaultTemps == true) {
                    RichHTML.msgBox(lang('You can not delete the system email templates.'),
                    {
                        type: 'error'
                    });
                }
                if (otherTemps == false) {
                    return;
                }
                $.post("index.php?fuse=admin&controller=emails&action=deleteemailtemplate", { ids: templates.grid.getSelectedRowIds() },
                function(xhr){
                    json = ce.parseResponse(xhr);
                    templates.grid.reload({params:{start:0}});
                });
            }
        });
    });

    var templatetype = 2;

    $('#emails-grid-type').change(function(){
        templatetype = $(this).val();
        templates.grid.reload({params:{start:0,templatetype:$(this).val()}});
    });

    templates.window = new RichHTML.window({
        height: '475',
        width: '615',
        grid: templates.grid,
        url: 'index.php?fuse=admin&view=emailtemplate&controller=emails',
        actionUrl: 'index.php?action=saveemailtemplate&controller=emails&fuse=admin',
        showSubmit: true,
        title: lang("Email Template Window")
    });

     $('#addTemplateButton').click(function(){
        templates.window.show({params:{id:0, templatetype: templatetype}});
    });
});