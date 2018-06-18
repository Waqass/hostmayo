var teamCalendar = {
    divCalendar:    $('#divCalendar'),
    vars: {},
    editWindow: new RichHTML.window({
        height:     '388',
        width:      '601',
        url:        'index.php?fuse=home&controller=calendar&view=eventdetail',
        actionUrl:  'index.php?fuse=home&controller=calendar&action=save',
        deleteUrl:  'index.php?fuse=home&controller=calendar&action=delete',
        showSubmit: true,
        title:      lang('Event Details'),
        buttons: {
            delete: {
                text: lang('Delete')
            },
            submit: {
                text: lang('Save'),
                type: 'submit'
            },
            cancel: {
                text: lang('Cancel'),
                type: 'cancel'
            }
        },
        onSubmit: function(response) {
            ce.parseActionResponse(response);
            if (response.newID) {
                teamCalendar.divCalendar.fullCalendar('refetchEvents');
            } else {
                teamCalendar.vars.event.title = response.save.title;
                teamCalendar.vars.event.description = response.save.description;
                teamCalendar.vars.event.start = new Date(response.save.start);
                teamCalendar.vars.event.end = new Date(response.save.end);
                teamCalendar.vars.event.backgroundColor = response.save.backgroundColor;
                teamCalendar.divCalendar.fullCalendar('updateEvent', teamCalendar.vars.event);
            }
        },
        onDelete: function(response) {
            ce.parseActionResponse(response);
            if (response.success) {
                teamCalendar.divCalendar.fullCalendar('removeEvents', teamCalendar.vars.event.id);
            }
        }
    })
}

$(document).ready(function(){
    teamCalendar.divCalendar.fullCalendar({
        header: {
            left:   'today',
            center: 'prev month,agendaWeek,agendaDay next',
            right:  'title'
        },
        weekMode: 'liquid',
        selectable: true,
        selectHelper: true,
        editable: true,
        timeFormat: 'h(:mm)tt',
        monthNames: teamCalendar.calendarNames.monthNames,
        monthNamesShort: teamCalendar.calendarNames.monthNamesShort,
        dayNames: teamCalendar.calendarNames.dayNames,
        dayNamesShort: teamCalendar.calendarNames.dayNamesShort,
        disableDragging: true,
        disableResizing: true,
        buttonText: {
            prev: '<i style="margin-top: 3px;" class="icon-arrow-left"></i>',
            next: '<i style="margin-top: 3px;" class="icon-arrow-right"></i>'
        },
        select: function(startDate, endDate, allDay) {
            teamCalendar.editWindow.show({
                params: {
                    id: 0,
                    start: startDate.format('Y-m-d H'),
                }
            });
        },
        events: function(startDate, endDate, callback) {
            $.ajax({
                url: 'index.php?fuse=home&controller=calendar&action=getevents',
                data: {
                    startDate: startDate.format('Y-m-d H:i:s'),
                    endDate: endDate.format('Y-m-d H:i:s')
                },
                success: function(response) {
                    callback(response.events);
                }
            });
        },
        eventClick: function(event) {
            teamCalendar.vars.event = event;
            teamCalendar.editWindow.show({
                params: {
                    id: event.id
                }
            });
        },
        eventRender: function(event, element, view) {
            if (view.name != 'month' || !event.description) { return; }
            element.parent().addClass('tooltipnowrap');
            element
                .attr('title', '<div style="font-size: 100%;">' + event.description + '</div>')
                .tooltip({
                    placement: 'bottom',
                    html: true
                })
            ;
        }
    });
});