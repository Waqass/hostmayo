var departments = {
    dom: {
        buttonAddDepartment: $('#addDepartmentButton'),
        buttonDeleteDepartment: $('#deleteDepartmentButton')
    },
    grid: new RichHTML.grid({
        el: 'div-departments-grid',
        url: 'index.php?fuse=support&controller=department&action=listdepartment',
        root: 'groups',
        totalProperty: 'totalcount',
        baseParams: { limit: clientexec.records_per_view, sort: 'name', dir: 'asc' },
        columns: [{
            xtype: "expander",
            dataIndex: "response",
            renderer: function(text, row) {
                if ( row.groupMembers === "" ) row.groupMembers = "None";
                if ( row.staffMembers === "" ) row.staffMembers = "None";
                html = "<b>Number of Routes</b>: "+ row.applicableRoutingRules;
                html += "<br/><b>Groups</b>: " + row.groupMembers;
                html += "<br/><b>Staff</b>: " + row.staffMembers;
                return html;
            }
        },{
            id: "cb",
            dataIndex: "id",
            xtype: "checkbox"
        }, {
            id: "name",
            dataIndex: "name",
            text: lang("Department Name"),
            sortable: true,
            renderer: function (text, row, el) {
                return '<a class="a-department-link" data-department-id="'+row.id+'">'+row.name+'</a>';
            },
            flex: 1
        },{
            id: "lead",
            text:  lang("Lead"),
            dataIndex: "lead",
            sortable: false,
            width: 200
        }]
    })
};
departments.window = new RichHTML.window({
    id: 'div-addEditDepartment',
    escClose: false,
    grid: departments.grid,
    showSubmit: true,
    actionUrl: 'index.php?fuse=support&controller=department&action=savedepartment',
    width: '575',
    minHeight: '250',
    title: lang("Add/Edit Department"),
    url: 'index.php?fuse=support&controller=department&view=department'
});

$(document).ready(function() {
    $('#departments-grid-filter').change(function(){
        departments.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $(departments.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                departments.dom.buttonDeleteDepartment.prop('disabled', false);
            } else {
                departments.dom.buttonDeleteDepartment.prop('disabled', true);
            }
        }
    });
    $('#div-departments-grid').on('click', '.a-department-link', function(e) {
        departments.window.show({
            params: {
                id: $(this).attr('data-department-id')
            }
        });
        e.preventDefault();
    });

    departments.grid.render();

    departments.dom.buttonAddDepartment.click(function() {
        departments.window.show();
    });

    departments.dom.buttonDeleteDepartment.click(function () {
        RichHTML.msgBox(lang('Are you sure you want to delete the selected departments(s)'),
        {
            type:"confirm"
        }, function(result) {
            if ( result.btn === lang("Yes") ) {
                $.post("index.php?fuse=admin&controller=settings&action=deletedepartment", { departmentIds: departments.grid.getSelectedRowIds() },
                function(data){
                    departments.grid.reload({params:{start:0}});
                });
            }
        });
    });
});