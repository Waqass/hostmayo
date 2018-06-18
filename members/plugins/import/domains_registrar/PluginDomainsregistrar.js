var domains = {}

$(document).ready(function() {

    domains.grid = new RichHTML.grid({
        el: 'domains-grid',
        url: 'index.php?fuse=clients&action=getdomainlist&controller=index',
        root: 'domains',
        totalProperty: 'totalcount',
        baseParams: { registrar: '--', limit: clientexec.records_per_view },
        columns: [{
                id:         "cb",
                dataIndex:  "id",
                xtype:      "checkbox"
            },{
                id:         "domain",
                text:     	lang("Domain"),
                dataIndex:  "domain"
            },{
                id:         "expiry",
                text:     	lang("Expiry Date"),
                width:      100,
                dataIndex:  "expiry"
            }
        ]
    });

    domains.grid.render();

    $('#domains-grid-filterbyregistrar').change(function(){
        domains.grid.reload({params:{start:0,registrar:$(this).val()}});
    });

    $('#domains-grid-filter').change(function(){
        domains.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $(domains.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#importDomainButton').removeAttr('disabled');
            } else {
                $('#importDomainButton').attr('disabled','disabled');
            }
        }
    });

    $('#importDomainButton').click(function (e) {
        RichHTML.mask();
        e.preventDefault();
        if ($(this).attr('disabled')) return false;

        var strItems = '';
        $.each(domains.grid.getSelectedRowIds(), function(i, val) {
            strItems += val + ' ';

        });

        $.post('index.php?fuse=clients&action=importdomain&controller=index', {
            domains: strItems,
            registrar: $('#domains-grid-filterbyregistrar').val()
        },function(data){
            domains.grid.reload({params:{start:0}});
            $('#domains_results').html(data);
            RichHTML.unMask();
        });
    });
});