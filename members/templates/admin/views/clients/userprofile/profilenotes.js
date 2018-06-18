$(document).ready(function() {

    profilenotes.isViewingArchived = 0;

    profilenotes.grid = new RichHTML.grid({
        el: 'profileNotes-grid',
        url: 'index.php?fuse=clients&action=clientnotes&controller=notes',
        root: 'results',
        baseParams: { sort: 'datesubmitted', dir: 'asc'},
        columns: [{
            xtype: "expander",
            dataIndex: "desc",
            renderer: function(text, row) {
                return '<br/><b>'+lang('Associated to Ticket Type')+':</b><b>&nbsp;' + row.tickettype + '&nbsp;&nbsp;</b><br/><br/><b>'+lang('Note')+':&nbsp;</b>' + nl2br(ce.htmlspecialchars(row.desc)) + '</p>';
            }
        },{
            id: "cb",
            dataIndex: "id",
            xtype: "checkbox"
        },{
            id: 'subject',
            dataIndex: 'subject',
            text: lang('Subject'),
            flex: 1,
            renderer: function(text, row) {
                return '<a onclick="ce.addNote(' + row.id+ ');">' + text + '</a>';
            }
        },{
            id: "datesubmitted",
            dataIndex: "datesubmitted",
            text: lang("Date Submitted"),
            align: "center",
            width: 150
        },{
            id: "staff",
            align: "left",
            text: lang("Entered By"),
            dataIndex: "staff",
            width: 300,
            align: "center",
        },{
            id: "visablebycustomer",
            text: lang("Viewable By Customer"),
            width: 140,
            align: "center",
            dataIndex: "visablebycustomer",
            renderer: function(text,row) {
                if (row.visablebycustomer == true){
                   return lang("Yes");
                } else {
                    return lang("No");
                }
            }
        },{
            id: "tickettype",
            text: lang("Ticket Type"),
            align: "center",
            width: 100,
            dataIndex: "tickettype"
        }]
    });

    profilenotes.grid.render();

    $('#profileNotes-grid-filterbystatus').change(function(){
        profilenotes.grid.reload({params:{start:0,archived:$(this).val()}});

        if ( $(this).val() == 1 ) {
            profilenotes.isViewingArchived = 1;
            $('#archiveButton span').text(lang('Un-Archive'));
        } else {
            profilenotes.isViewingArchived = 0;
            $('#archiveButton span').text(lang('Archive'));
        }
    });

    $(profilenotes.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteButton').removeAttr('disabled');
                $('#archiveButton').removeAttr('disabled');
            } else {
                $('#deleteButton').attr('disabled','disabled');
                $('#archiveButton').attr('disabled','disabled');
            }
        }
    });

    $('#addNoteButton').click(function(){
        ce.addNote();
    });

    $('#archiveButton').click(function () {
        $.post("index.php?fuse=clients&action=archiveclientnotes&controller=notes", {
            ids: profilenotes.grid.getSelectedRowIds(),
            isviewingarchived: profilenotes.isViewingArchived
        },
        function(data){
            profilenotes.grid.reload({
                params:{
                    start:0
                }
            });
        });
    });

    $('#deleteButton').click(function () {
        if ($(this).attr('disabled')) { return false; }
        RichHTML.msgBox(lang('Are you sure you want to delete the selected notes(s)'),
        {
            type:"confirm"
        }, function(result) {
            if(result.btn === lang("Yes")) {
                $.post("index.php?fuse=clients&action=deleteclientnote&controller=notes", {
                    ids: profilenotes.grid.getSelectedRowIds()
                },
                function(data){
                    profilenotes.grid.reload({
                        params:{
                            start:0
                        }
                    });
                    setTimeout(function() {
                        profile.get_counts();
                    },1000);
                });
            }
        });
    });
});
