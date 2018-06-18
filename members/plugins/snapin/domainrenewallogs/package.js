var domainrenewals = {};

$(document).ready(function() {

    domainrenewals.grid = new RichHTML.grid({
        el: 'domainrenewals-grid',
        width: "100%",
        editable : true,
        url: 'index.php?fuse=home&controller=events&action=geteventlist',
        baseParams: {
            sort: 'id',
            dir: 'desc',
            limit: clientexec.records_per_view,
            eventtype: 'domainrenewals'
        },
        root: 'data',
        totalProperty : 'totalcount',
        columns: [{
            text:     	"",
            dataIndex:  "id",
            xtype:      "checkbox"
        },{
            text:     	lang("Date"),
            dataIndex:  "logdate",
            align:      "center",
            width:      175,
            sortable:   true,
        },{
            text:       lang("Action"),
            dataIndex:  'logaction',
            align:      "center",
            flex: 1
        }]
    });
    domainrenewals.grid.render();

    $('#domainrenewals-grid-filter').change(function(){
        domainrenewals.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $(domainrenewals.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#export-button').removeAttr('disabled');
            } else {
                $('#export-button').attr('disabled','disabled');
            }
        }
    });

    $('#export-button').click(function(e){
        e.preventDefault();
        window.open(
            'index.php?action=exportdomainrenewals&controller=events&fuse=home&sessionHash=' + gHash + '&ids=' + domainrenewals.grid.getSelectedRowIds(),
            '_blank'
        );

    });
});