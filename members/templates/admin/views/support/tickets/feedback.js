var feedback = {};

$(document).ready(function() {

    feedback.expanderRenderer = function(text,row)
    {
        text = nl2br(row.feedback);
        return text;
    }

    feedback.grid = new RichHTML.grid({
        el: 'feedback-grid',
        root: "data",
        totalProperty: "total",
        url: 'index.php?fuse=support&controller=tickets&action=getfeedback',
        baseParams: { limit: clientexec.records_per_view, filter: $('#feedback-grid-filterby').val(), sort: 'id', dir: 'desc'},
        columns: [{
                dataIndex:  "comment",
                xtype:      "expander",
                renderer:   feedback.expanderRenderer
            },{
                id:         "id",
                dataIndex:  "id",
                text:       lang("Ticket ID"),
                align:      "center",
                sortable:   true,
                width:      150,
                renderer:   function(text, row) {
                    str = "<a href='index.php?fuse=support&view=viewtickets&controller=ticket&searchfilter=closed&id=" + row.id + "'>" + text + '</a>';
                    return str;
                }
            },{
                id:         "customer",
                dataIndex:  "customer",
                text:       lang("Customer"),
                align:      "left",
                flex: 1,
                renderer:   function(text, row) {
                    str = "<a href='index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=" + row.userid + "'>" + text + '</a>';
                    return str;
                }

            },{
                id:         "rate",
                dataIndex:  "rate",
                text:       lang("Rating"),
                align:      "center",
                sortable:   false,
                width:      150,
                renderer:   function(text, row){
                    switch(text) {
                        case '0':
                            return lang('No Rating');
                        case '1':
                            return lang('Excellent');
                        case '2':
                            return lang('Good');
                        case '3':
                            return lang('Not Great');
                        case '4':
                            return lang('Poor');
                    }
                }
            }
        ]
    });
    feedback.grid.render();

    $('#feedback-grid-filterby').change(function(){
        feedback.grid.reload({params:{start:0,filter:$(this).val()}});
    });

    $('#feedback-grid-filter').change(function(){
        feedback.grid.reload({
            params:{
                start:0,
                limit:$(this).val()
            }
        });
    });
});