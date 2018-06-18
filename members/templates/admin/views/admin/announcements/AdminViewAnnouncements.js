var announcements = {
    postDateValue:                 '', // assigned in template
    postTimeValue:                 '', // assigned in template
    grid:                          {},
    window:                        {},
    selectAnnouncementsGridFilter: $('#selectAnnouncementsGridFilter'),
    buttonAdd:                     $('#buttonAdd'),
    buttonPublish:                 $('#buttonPublish'),
    buttonUnpublish:               $('#buttonUnpublish'),
    buttonDelete:                  $('#buttonDelete'),
    divAnnouncementsGrid:          $('#divAnnouncementsGrid'),
    changeAnnouncementStatus:      function(status) {
        $.post(
            'index.php?fuse=admin&action=publishunpublishannouncements&controller=announcements',
            {
                items:   announcements.grid.getSelectedRowIds(),
                publish: status
            },
            function(data){
                announcements.grid.reload({params:{start:0}});
                ce.parseActionResponse(data);
            }
        );
    }
};

$(document).ready(function() {

    announcements.divAnnouncementsGrid.on('click', 'a.announcement-title', function() {
        announcements.window.show({
            params: {
                id: $(this).attr('data-announcement-id')
            }
        });

        // setting this inside the options obj above doesn't work
        $('.window-title').text(lang('Edit') + ' - ' + $(this).text());
    });

    announcements.grid = new RichHTML.grid({
        el: 'divAnnouncementsGrid',
        url: 'index.php?fuse=admin&action=getannouncements&controller=announcements',
        root: 'announcements',
        totalProperty: 'totalcount',
        baseParams: { limit: clientexec.records_per_view },
        columns: [
            {
                id:        'cb',
                dataIndex: 'id',
                xtype:     'checkbox'
            }, {
                id:        'title',
                dataIndex: 'title',
                text:      lang('Title'),
                sortable:  true,
                renderer:  function(text, row) {
                    return '<a class="announcement-title" data-announcement-id="'+row.id+'">' +
                        ( row.published == '0' ? '<span style="color: gray; font-style: italic;">' + ce.htmlspecialchars(row.title) + '</span>' : ce.htmlspecialchars(row.title) ) +
                        '</a>'
                    ;
                },
                align : 'left',
                flex: 1
            }, {
                id:        'published',
                text:      lang('Published'),
                dataIndex: 'published',
                sortable:  true,
                width:     100,
                renderer:  function(text,row) {
                    if ( row.published == '0' ) {
                        return lang('No');
                    } else {
                        return lang('Yes');
                    }
                }
            }, {
                id:        'date',
                text:      lang('Post Date'),
                width:     150,
                dataIndex: 'postdate',
                sortable:  true
            }
        ]
    });
    announcements.grid.render();

    announcements.window = new RichHTML.window({
        id:         'announcement-window',
        grid:       announcements.grid,
        height:     '590',
        width:      '760',
        url:        'index.php?fuse=admin&view=announcement&controller=announcements',
        actionUrl:  'index.php?fuse=admin&action=saveannouncement&controller=announcements',
        showSubmit: true,
        title:      lang('Add Announcement'),
        onSubmit: function() {
          announcements.window.unMask();
          // do this otherwise the timepicker remains on screen (github issue #741)
          $('.timepicker').timepicker('hideWidget');
        }
    });

    announcements.buttonAdd.click(function(){
        announcements.window.show();
    });


    announcements.selectAnnouncementsGridFilter.change(function(){
        announcements.grid.reload({
            params:{
                start: 0,
                limit: $(this).val()
            }
        });
    });

    $(announcements.grid).bind({
        'rowselect': function(event,data) {
            if (data.totalSelected > 0) {

                var selectedRowData = announcements.grid.getSelectedRowData();
                var arrayLength = selectedRowData.length;
                var showPublish = true;
                var showUnpublish = true;
                for (var idx = 0; idx < arrayLength; idx++) {
                    if(selectedRowData[idx].published == '0'){
                        showUnpublish = false;
                    }else{
                        showPublish = false;
                    }
                }

                if(showPublish){
                    announcements.buttonPublish.prop('disabled', false);
                }else{
                    announcements.buttonPublish.prop('disabled', true);
                }

                if(showUnpublish){
                    announcements.buttonUnpublish.prop('disabled', false);
                }else{
                    announcements.buttonUnpublish.prop('disabled', true);
                }

                announcements.buttonDelete.prop('disabled', false);
            } else {
                announcements.buttonPublish.prop('disabled', true);
                announcements.buttonUnpublish.prop('disabled', true);
                announcements.buttonDelete.prop('disabled', true);
            }
        }
    });

    announcements.buttonDelete.click(function() {
        RichHTML.msgBox(
            lang('Are you sure you want to delete the selected announcement(s)'),
            {
                type: 'confirm'
            }, function(result) {
                if (result.btn === lang('Yes')) {
                    $.post(
                        'index.php?fuse=admin&action=deleteannouncements&controller=announcements',
                        {
                            items: announcements.grid.getSelectedRowIds()
                        },
                        function (data) {
                            announcements.grid.reload({params:{start:0}});
                            ce.parseActionResponse(data);
                        }
                    );
                }
            }
        );
    });

    announcements.buttonPublish.click(function () {
        announcements.changeAnnouncementStatus(1);
    });

    announcements.buttonUnpublish.click(function () {
        announcements.changeAnnouncementStatus(0);
    });

});
