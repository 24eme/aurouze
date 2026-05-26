$(function () {

	params = params || false;
	calendarExtra = params.calendarExtra || false;

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
        $(this).click(function () {
            var planifiable = $(this).data('planifiable');
            $.get(
            $('#calendrier').data('urlRead'), {
                planifiable: planifiable,
                service: encodeURI(location.href)
            }, function (response) {
                $('#modal-calendrier-infos').html(response);
                $('#modal-calendrier-infos').modal();
                $.callbackEventForm();
            }
            );
        });

    });

    /**
     * FullCalendar Settings
     */
    var doubleClick = false;
    $('#calendrier').fullCalendar({
        minTime: '00:00:00',
        maxTime:'24:00:00',
        height: (calendarExtra)? 915 : 810,
        customButtons: {
            prevButton: {
                text: '',
                click: function () {
                    window.location.href = $('#calendrier').data('urlPrev');
                },
                icon: 'left-single-arrow'
            },
            nextButton: {
                text: '',
                click: function () {
                    window.location.href = $('#calendrier').data('urlNext');
                },
                icon: 'right-single-arrow'
            }
        },
        dayRender: function (date, cell) {
            cell.css("background-color", "transparent");
        },
        header: false,
        lang: 'fr',
        defaultDate: $('#calendrier').data('gotoDate'),
        timeFormat: 'H:mm',
        allDaySlot: false,
        hiddenDays: (calendarExtra)? [0] : [6,0],
        eventBackgroundColor: "#fff",
        editable: true,
        droppable: true,
        slotEventOverlap: false,
        defaultView: $('#calendrier').data('view'),
				events:{
					url: $('#calendrier').data('urlPopulateHolidays'),
			    method: 'POST',
					color: '#aaa9a9',
			    textColor: 'black',
					editable: false
		    },
        eventSources: [
            {
                url: $('#calendrier').data('urlPopulate'),
                type: 'GET',
                data: {
                    title: 1,
                }
            }
        ],
        eventClick: function (event) {
            $.get(
                $('#calendrier').data('urlRead'), {
                id: event.id,
                service: encodeURI(location.href)
            }, function (response) {
                $('#modal-calendrier-infos').html(response);
                $('#modal-calendrier-infos').modal();
                $.callbackEventForm();
            }
            );
        },
        dayClick: function(date, jsEvent, view) {
          if(!doubleClick) {
            doubleClick = true;
            $.get(
                $('#calendrier').data('urlAddLibre'), {'start': date.format()}
                 , function (response) {
                    $('#modal-calendrier-infos').html(response);
                    $('#modal-calendrier-infos').on('shown.bs.modal', function() {
                        $('#modal-calendrier-infos').find('[autofocus="autofocus"]').focus();
                        $.callbackEventForm();
                        $(this).prop('disabled', false);
                    });
                    $('#modal-calendrier-infos').modal();
                }
            );
            setTimeout(function() { doubleClick = false; }, 1000);
            }
        },
        eventReceive: function (event) {
            $('#retour_technicien_btn').removeClass('hidden');
            $.post(
                $('#calendrier').data('urlAdd'), {
                id: null,
                start: event.start.format(),
                end: event.end.format()
            }, function (data) {
                event.id = data.id;
                event.backgroundColor = data.backgroundColor;
                event.textColor = data.textColor;
                event.retourMap = data.retourMap;
								event.livraison = data.livraison;
                $('#calendrier').fullCalendar('updateEvent', event);
            }
            );
        },
        drop: function () {
            if(!document.querySelector('#eventForm > * > *')) {
                $(this).remove();
            }
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
        eventRender: function(event, element) {
          if(event.retourMap){
             var url = event.retourMap;
             var dayOfMonth = event.start.format().substr(8,2);
             var month = event.start.format().substr(0,7).replace('-','');
             if(dayOfMonth > "20"){
                 var nextMonth = ""+(parseInt(month)+1);
                 url = url.replace("mois="+month,"mois="+nextMonth);
             }
             element.find(".fc-title").append('<a style="position:absolute; top: 0; right:0; opacity:0.2;" class="btn btn-default btn-xs " href="'+url+'"><span class="mdi mdi-map"></span></a>');
          }
					if(event.livraison){
						element.find(".fc-title").append('<span style="position:absolute; top: 0; right:0; font-size: 14pt" ><i class="mdi mdi-local-shipping"></i></span>');
					}
					if(event.rendezVousConfirme === false){
						element.find(".fc-title").append('<span class="text-muted" style="position:absolute; bottom: -10px; right: 0px;" ><span class="mdi mdi-add-alert"></span>&nbsp;N.C.</span>');
						element.find(".fc-bg").css("border", "1px solid red");
					}



        },
        eventAfterRender: function(event, element) {

          $.callbackCalendarDynamicButton();
        },

        eventAfterAllRender: function(view) {
          const joursSemaineObject = {};
          document.querySelectorAll("th[data-date]").forEach(function(e) {
            joursSemaineObject[e.dataset.date] = false;
          });

          view_start = view.start._i;
          view_end = view.end._d.toISOString().substring(0, 10);

          const eventsArray = $("#calendrier").fullCalendar("clientEvents", function(events){ return (moment(events.start).format("YYYY-MM-DD") >= view_start && view_end > moment(events.start).format("YYYY-MM-DD"))});

          Object.entries(joursSemaineObject).forEach(([key, value]) => {
            eventsArray.forEach(function(e) {
              rangeNuitDebut = moment(structuredClone(e.start));
              rangeNuitDebut = moment(rangeNuitDebut).set("hour",22);
              rangeNuitDebut = moment(rangeNuitDebut).set("minute",00);
              rangeNuitDebut = rangeNuitDebut.utc();

              rangeNuitFin = moment(structuredClone(e.end));
              rangeNuitFin = moment(rangeNuitFin).add(1,"days");
              rangeNuitFin = moment(rangeNuitFin).set("hour",05);
              rangeNuitFin = moment(rangeNuitFin).set("minute",00);
              rangeNuitFin = rangeNuitFin.utc();

              rangeNuitDebutSameDay = moment(structuredClone(e.start));
              rangeNuitDebutSameDay = moment(rangeNuitDebutSameDay).set("hour",00);
              rangeNuitDebutSameDay = moment(rangeNuitDebutSameDay).set("minute",00);
              rangeNuitDebutSameDay = rangeNuitDebutSameDay.utc();

              rangeNuitFinSameDay = moment(structuredClone(e.end));
              rangeNuitFinSameDay = moment(rangeNuitFinSameDay).set("hour",05);
              rangeNuitFinSameDay = moment(rangeNuitFinSameDay).set("minute",00);
              rangeNuitFinSameDay = rangeNuitFinSameDay.utc();

              rdvDateDebut = e.start;
              rdvDateFin = e.end;

              console.log(rangeNuitFin)

              if (((rdvDateDebut > rangeNuitDebut) && (rdvDateDebut < rangeNuitFin)) || ((rdvDateDebut > rangeNuitDebutSameDay) && (rdvDateDebut < rangeNuitFinSameDay)) || ((rdvDateFin > rangeNuitDebut) && (rdvDateFin < rangeNuitFin))) {
                eventDate = e.start._i.substring(0, 10);
                if (eventDate === key) {
                  joursSemaineObject[key] = true;
                }
              }
            });
          });

          document.querySelectorAll("th[data-date]").forEach(function(e) {
            Object.entries(joursSemaineObject).forEach(([key, value]) => {
              if (key == e.getAttribute("data-date") && value == true){
                  e.classList.add("rdv-nuit");
              } else if (e.classList.contains("rdv-nuit") && key == e.getAttribute("data-date") && value == false) {
                  e.classList.remove("rdv-nuit");
              }
            });
          });
        }

    });
});
