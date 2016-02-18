$(function () {
    /**
     * Dropping Elements
     */
    $('#fc-events .event').each(function () {
        $(this).data('event', {
            title: $.trim($(this).data('title')),
            stick: true
        });
        $(this).draggable({
            zIndex: 999,
            revert: true,
            revertDuration: 0
        });

    });
    /**
     * FullCalendar Settings
     */
    $('#calendrier').fullCalendar({
        minTime: '06:00:00',
        maxTime: '18:00:00',
        height: 600,
        header: {
            left: 'prev, title, next',
            right: 'month, agendaWeek, agendaDay',
        },
        lang: 'fr',
        timeFormat: 'H:mm',
        allDaySlot: false,
        editable: true,
        droppable: true,
        slotEventOverlap: false,
        hiddenDays: [0],
        defaultView: "agendaWeek",
        eventSources: [
            {
                url: $('#calendrier').data('urlPopulate'),
                type: 'POST',
                data: {
                    title: 1,
                }
            }
        ],
        eventClick: function (event) {
            $.post(
                    $('#calendrier').data('urlRead'), {
                id: event.id,
                light: 0
            }, function (response) {
                $('#modal-title').text(event.title);
                $('#modal-body').html(response);
                $('#modal-calendrier-infos').modal();
            }
            );
        },
        eventReceive: function (event) {
            $.post(
                    $('#calendrier').data('urlUpdate'), {
                id: null,
                start: event.start.format(),
                end: event.end.format()
            }, function (data) {
                event.id = data.id;
            }
            );
        },
        drop: function () {
            $(this).remove();
        },
        eventResize: function (event) {
            $.post(
                    $('#calendrier').data('urlUpdate'), {
                id: event.id,
                start: event.start.format(),
                end: event.end.format()
            });
        },
        eventDrop: function (event) {
            $.post(
                $('#calendrier').data('urlUpdate'), {
                id: event.id,
                start: event.start.format(),
                end: event.end.format()
            });
        },
    });
    /**
     * FullCalendar Light Settings
     */
    $('#calendrier-light').fullCalendar({
        minTime: '06:00:00',
        maxTime: '18:00:00',
        height: 600,
        header: {
            left: 'prev, title, next',
            right: 'agendaWeek, agendaDay',
        },
        lang: 'fr',
        timeFormat: ' ',
        allDaySlot: false,
        editable: false,
        droppable: false,
        slotEventOverlap: false,
        hiddenDays: [0],
        defaultView: "agendaWeek",
        eventAfterRender: function(event, element, view) {
            $(element).css('max-width','20%');
        },
        eventSources: [
            {
                url: $('#calendrier-light').data('urlPopulate'),
                type: 'POST',
                data: {
                    title: 0,
                }
            }
        ],
        eventClick: function (event) {
            $.post(
                    $('#calendrier-light').data('urlRead'), {
                id: event.id,
                light: 1
            }, function (response) {
                $('#modal-title').text(event.title);
                $('#modal-body').html(response);
                $('#modal-calendrier-infos').modal();
            }
            );
        },
    });
});
