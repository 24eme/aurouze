(function ($)
{

    $(document).ready(function ()
    {
        $.initClickInputAddon();
        $.initAjaxPost();
        $.initSelect2();
        $.initSelect2Ajax();
        $.initModal();
        $.initTooltips();
        $.initHamzaStyle();
        $.initDynamicCollection();
        $.initDatePicker();
        $.initPeriodePicker();
        $.initTimePicker();
        $.initFormEventAjax();
        $.initSwitcher();
        $.initModalPassage();
        $.initBtnSwitch();
        $.initCollapseCheckbox();
        $.initTextSelector();
        $.initLinkInPanels();
        $.initRdvLink();
        $.initSearchActif();
        $.initLinkCalendar();
        $.initQueryHash();
        $.initMap();
        $.initTypeheadFacture();
        $.initTypeheadSearchable();
        $.initTypeheadSearchableCheckbox();
        $.initSomme();
        $.initReconduction();
        $.initRelance();
        $.initButtonLoading();
        $.initPopupRelancePdf();
        $.initModificationContrat();
        $.initAcceptationContrat();
        $.initAllFactureSearch();
        $.initTrCollapse();
        $.initTourneeDatepicker();
        $.initAttachements();
        $.initMoreInfo();
        $.initTransfertContrat();
        $.initPaiementsAutoSave();
        $.initCommentairesPlanif();
        $.initDevisForm();
        $.initAutocompleteAdresse();
        $.initMapForAdresse();
        $.initHighLight();
        $.initFacture();
    });

    $.initClickInputAddon = function(){
      $(".input-group-addon").click(function(e){
        $(this).prev().click();
        $(this).prev().focus();
      });
    }

    $.initTrCollapse = function() {
    	$('.tr-collapse').click(function(){
    		if ($($(this).data('show')).is(':visible')) {
    			$($(this).data('hide')).hide();
    			$(this).find('.glyphicon').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
    		} else {
    			$($(this).data('show')).show();
    			$(this).find('.glyphicon').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
    		}
    	});
    }

    $.initAllFactureSearch = function() {
    	$('body').on('click', '.all-factures', function(){
    		var parent = $(this).parent().siblings('.select2-ajax');
    		if($(this).prop('checked')) {
    			parent.attr('data-url', parent.attr('data-url').replace('facture/rechercher', 'facture/all/rechercher'));
    		} else {
    			parent.attr('data-url', parent.attr('data-url').replace('facture/all/rechercher', 'facture/rechercher'));
    		}
        $.initSelect2Ajax();
    	});
    }

    $.initModificationContrat = function() {
        $('#contrat_commanditaire').on('change', function (e) {
            if($(this).val()) {
                $('#contrat_devisInterlocuteur').attr('disabled', 'disabled');
                $('#row_contrat_devisInterlocuteur').css('opacity', '0.2');
            } else {
                $('#contrat_devisInterlocuteur').removeAttr('disabled', 'disabled');
                $('#row_contrat_devisInterlocuteur').css('opacity', '1');
            }
        });
        $('#contrat_commanditaire').change();
    }

    $.initAcceptationContrat = function() {
    	 $.updateAcceptationContratButton();
      $("#contrat_acceptation_dateAcceptation").on('change',function(){
        $.updateAcceptationContratButton();
      });
      $("#contrat_acceptation_dateDebut").on('change',function(){
        $.updateAcceptationContratButton();
      });
    }

    $.updateAcceptationContratButton = function(){
      var dateAcceptation = $("#contrat_acceptation_dateAcceptation").val();
      var dateDebut = $("#contrat_acceptation_dateDebut").val();
      if(dateAcceptation && dateDebut){
          $("#contrat_acceptation_button_row button#contrat_acceptation_save").removeAttr("disabled");
      }else{
          $("#contrat_acceptation_button_row button#contrat_acceptation_save").attr("disabled","disabled");
      }

    }

    $.initDevisForm = function() {
      var devis_produit_total = document.getElementById("devis_produit_total")

      if (devis_produit_total == null) {
        return false;
      }

      var devis_produit = document.getElementById('devis_produit')

      var updateTotal = function (table) {
        var trs = table.querySelectorAll('tr')
        var total = 0
        devis_produit_total.innerText = 0.0

        trs.forEach(function (tr) {
          var qte = tr.querySelector('input.quantite')

          if (qte == null) return

          qte = qte.value

          if (tr.querySelector('input.tt-input').value.length == 0 || isNaN(qte)) {
            qte = 0
          }

          total += qte * tr.querySelector('.prix-unitaire').value
        })

        devis_produit_total.innerText = Math.round(total * 100) / 100
      }

      devis_produit_total.innerText = 0.0

      updateTotal(devis_produit)
      devis_produit.addEventListener('input', function() {
        updateTotal(devis_produit)
      })

      if('#devis_dateAcceptation'){
        $("button#generer_et_planifier").removeAttr("disabled");
      }else{
        $("button#generer_et_planifier").attr("disabled","disabled");
      }

      $('#devis_dateAcceptation').on('change', function () {
        if($(this).val()){
          $("button#generer_et_planifier").removeAttr("disabled");
        }else{
          $("button#generer_et_planifier").attr("disabled","disabled");
        }
      });
    }

    $.initPopupRelancePdf = function() {
        $('#relancePdfPopup').modal('show');
    }


    $.initButtonLoading = function() {
        $('.btn-loading-submit').parents('form').on('submit', function () {
            $(this).find('.btn-loading-submit').button('loading')
        });
        $('.btn-loading').on('click', function () {
            $(this).button('loading');
        });


    }

    $.initModal = function() {
        $('.modal.openOnLoad').modal('show');
    }

    $.initReconduction = function(){
      $("form#formContratsAReconduire").each(function(){
        $(".typeContrat").on("change", function(){
            $("form#formContratsAReconduire").submit();
        });
        $(".dateRenouvellement").on("change", function(){
            $("form#formContratsAReconduire").submit();
        });
      });

        $('.lien_pas_de_reconduction').on('click', function() {
            if(!confirm('Ne plus jamais reconduire ce contrat ?')) {
                return false;
            }

            $.get($(this).attr('href'));
            $(this).parents('tr').fadeOut(500, function() {$(this).parents('tr').remove();});
            return false;
        });
    }

    $.initRelance = function(){
        $('.relance_lien_cloturer').on('click', function() {
            if(!confirm('Êtes vous sûr de vouloir cloturer cette facture ?')) {
                return false;
            }

            $.get($(this).attr('href'));
            $(this).parents('tr').fadeOut(500, function() {$(this).parents('tr').remove();});
            return false;
        });
        $('.commentaire').each(function(){
            $(this).on('blur', function (event, state) {

                var commentaire = $(this);
                var id = commentaire.attr("data-id");
                var value = commentaire.val();
                var urlCom = commentaire.attr("data-url");
                if (urlCom) {
                     $.ajax({
                         type: "POST",
                         url: commentaire.data('url'),
                         data: {id: id, value: value}
                     });
                 }
            });
        });

        $('.relance_lien_envoyer_mail').on('click', function(e) {
            e.preventDefault();
            if(!confirm('Êtes-vous sûrs de vouloir envoyer le mail?')) {
                return false;
            }

            const element = this;
            const ligne = this.parentNode.parentNode;
            const textarea = ligne.querySelector('textarea');

            fetch($(this).attr('href'))
           .then(function(response) {
             if(response.ok){
               return response.text();
             }
             else{
              alert("IL Y A UNE ERREUR, LE MAIL N'A PAS PU ETRE ENVOYE");
              throw new Error(response.status);
             }
           })
           .then(function(text) {

             if(element.dataset.relance == 1){
               ligne.style.backgroundColor = "#d9edf7";
             }
             if(element.dataset.relance == 2){
               ligne.style.backgroundColor = "#fcf8e3";
             }
             if(text.length > 0){
               textarea.value = text+"\nR"+element.dataset.relance+" le "+new Date().toLocaleDateString("fr");
             }
             else{
               textarea.value = "R"+element.dataset.relance+" le "+new Date().toLocaleDateString("fr");
             }
             element.parentNode.removeChild(element);
             textarea.dispatchEvent(new Event("blur"));
           })
           .catch(error => console.error('Error: ', error));
           return false; //au cas ou;
        });
    }

    $.initFacture = function(){
      $('.mail_facture').on('click',function(){
        if(!confirm('Êtes-vous sûrs de vouloir envoyer le mail?')) {
            return false;
        }
      });
    }

    $.initSomme = function () {
        $('.nombreSomme').blur(function () {
            var total = 0.0;
            $('.nombreSomme').each(function () {
                if ($(this).val() && $(this).val()!= "") {
                    total += parseFloat($(this).val().replace(',', '.'));
                }
            });
            var totalToPrint = total.toFixed(2).toString().replace('.', ',');
            $(".sommeTotal").html(totalToPrint);
        });
    }
    $.initLinkCalendar = function () {
        $('#calendrier .fc-day-header').each(function () {
            if ($(this).data('date')) {
                var content = '<a href="' + ($('#calendrier').data('url-date')).replace('-d', $(this).data('date')) + '">' + $(this).text() + '</a>';
                $(this).html(content);
            }
        });
        $('#calendrier .fc-day-number').each(function () {
            if ($(this).data('date')) {
                var content = '<a href="' + ($('#calendrier').data('url-date')).replace('-d', $(this).data('date')) + '">' + $(this).text() + '</a>';
                $(this).html(content);
            }
        });
        $('.fc-time-grid-event .fc-bg').each(function(){

          console.log(this);
        });
    };

    $.callbackCalendarDynamicButton = function(){
        $("#calendrier").find('.fc-event-container').each(function(){
          $(this).mouseover(function(){
           $(this).find('.fc-content .fc-title a').css('opacity',1);
         }).mouseout(function(){
             $(this).find('.fc-content .fc-title a').css('opacity',0.2);
           });
        });
    }

    $.initSearchActif = function () {
        $('form input[type="checkbox"][data-search-actif="1"]').each(function () {

            $(this).parents('form').find('select').attr('data-nonactif', "0");
            $(this).click(function () {
                $(this).parents('form').find('select').attr('data-nonactif', ($(this).is(':checked') ? "1" : "0"));
                $.initSelect2Ajax();
            })
        });
    }

    $.initRdvLink = function () {
        $('.rdv-deplanifier-link').click(function (e) {
            e.preventDefault();
            var link = $(this).attr('href');
            $.post(link, function (data) {
                document.location.reload();
            });
        });

        $('.rdv-modifier-link').click(function (e) {
            e.preventDefault();
            $('#modal-calendrier-infos').load($(this).attr('href'), function () {
                $.callbackEventForm();
            });
        });
    }

    $.initLinkInPanels = function () {
        $('.panel-heading a.stopPropagation').click(function (e) {
            e.stopPropagation();
        });
    }

    $.initTextSelector = function () {
        $('.text-selector').click(function () {
            $(this).select();
        });
    }
    $.initCollapseCheckbox = function () {

        $('.collapse-checkbox').click(function () {
            if ($(this).is(':checked')) {
                $($(this).data('target')).collapse('hide');
            } else {
                $($(this).data('target')).collapse('show');
            }
        });
    }

    $.initSwitcher = function () {
        $('.switcher').each(function () {
            var state = $(this).is(':checked');
            $(this).bootstrapSwitch('state', state);
        });
        $('.switcher').on('switchChange.bootstrapSwitch', function (event, state) {

            var checkbox = $(this);
            var etat = state ? 1 : 0;
            if (checkbox.attr("data-url")) {
                $.ajax({
                    type: "POST",
                    url: checkbox.data('url'),
                    data: {etat: etat}
                });
            }
        });
    }

    $.initBtnSwitch = function () {
        $('.btn-switcher').click(function () {
            $($(this).data('hide')).hide();
            $($(this).data('show')).show();
        });
    }

    $.initDatePicker = function () {
        $('.datepicker').datepicker({autoclose: true, todayHighlight: true, toggleActive: true, language: "fr"});
    }

    $.initPeriodePicker = function () {
        var periodePicker = $('.periodepicker').datepicker({format: "mm/yyyy", viewMode: "months", minViewMode: "months", autoclose: true, todayHighlight: true, toggleActive: true, language: "fr", orientation: "right"});
        periodePicker
	        .on('changeDate', function(e) {
	            $('.periodepicker').parent('form').submit();
	        })
	        .on('clearDate', function(e) {
	            $('.periodepicker').parent('form').submit();
	        });
    }

    $.initTimePicker = function () {
        $('.input-timepicker').each(function () {
            var defaultTiming = ($(this).attr('data-default')) ? $(this).attr('data-default') : '';
            $(this).timepicker({
                format: 'HH:ii p',
                autoclose: true,
                showMeridian: false,
                startView: 1,
                maxView: 1,
                defaultTime: "" + defaultTiming
            });
        });
    }

    $.initDynamicCollection = function () {
        $('.dynamic-collection-item').on('click', '.dynamic-collection-remove', function (e) {
            e.preventDefault();
            $(e.delegateTarget).remove();
        });
        $('body').on('click', '.dynamic-collection-add', function (e) {
            e.preventDefault();
            var collectionTarget = $(this).data('collection-target');
            var collectionHolder = $(collectionTarget);
            collectionHolder.data('index', collectionHolder.find(':input').length);
            var prototype = collectionHolder.data('prototype');
            var index = collectionHolder.data('index');
            var item = $(prototype.replace(/__name__/g, index));
            collectionHolder.data('index', index + 1);
            collectionHolder.append(item);
            $(item).on('click', '.dynamic-collection-remove', function (e) {
                e.preventDefault();
                $(e.delegateTarget).remove();
            });
            $(item).find('input, select').eq(0).focus();
            $.callbackDynamicCollection();

            if (item[0].dataset.repeat !== undefined && item[0].dataset.repeat.length > 0 && item[0].previousElementSibling) {
                const el = item[0]
                const previous = el.previousElementSibling;

                (el.dataset.repeat.split('|') || []).forEach(function (sel) {
                    const old = previous.querySelector(sel);
                    const newEl = el.querySelector(sel);
                    newEl.value = old.value;
                    newEl.dispatchEvent(new Event('change'))
                });
            }
        });
    }

    $.initFormEventAjax = function () {
        $('#eventForm').submit(function () {
            $('#modal-calendrier-infos').find('button[type="submit"]').button('loading');
            var form = $(this);
            var request = $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize()
            });
            request.done(function (msg) {
                try {
                    $.parseJSON(msg);
                    location.reload();
                } catch (e) {
                    $('#modal-calendrier-infos').html(msg);
                    $.callbackEventForm();
                }
            });
            request.fail(function (jqXHR, textStatus) {
                $('#modal-calendrier-infos').html(jqXHR.responseText);
                $.callbackEventForm();
                $.callbackDynamicCollection();
            });
            return false;
        });
    }

    $.callbackDynamicCollection = function () {
        $.initSelect2();
        $.initSelect2Ajax();
        $.initDatePicker();
        $.initTimePicker();
        $.initTypeheadFacture();
    }

    $.callbackEventForm = function () {
        $.initSelect2();
        $.initSelect2Ajax();
        $.initDatePicker();
        $.initTimePicker();
        $.initFormEventAjax();
        $.initRdvLink();
        $.initMoreInfo();
    }

    $.initTooltips = function () {
        $('[data-toggle="tooltip"], .toggle-tooltip').tooltip({ 'html' : true });
    }

    $.initAjaxPost = function ()
    {

        var notificationError = $('#ajax_form_error_notification');
        var notificationProgress = $('#ajax_form_progress_notification');
        $(document).ajaxError(
                function (event, xhr, settings) {
                    if (settings.type === "POST") {
                        notificationError.show();
                    }
                }
        );
        $(document).ajaxSuccess(
                function (event, xhr, settings) {
                    if (settings.type === "POST") {
                        notificationError.hide();
                    }
                }
        );
        $(document).ajaxSend(
                function (event, xhr, settings) {
                    if (settings.type === "POST") {
                        notificationError.hide();
                        notificationProgress.show();
                    }
                }
        );
        $(document).ajaxComplete(
                function (event, xhr, settings) {
                    if (settings.type === "POST") {
                        notificationProgress.hide();
                    }
                }
        );
    };
    $.initSelect2 = function () {
        $('.select2-simple').each(function () {
            $(this).select2({
                theme: 'bootstrap',
                allowClear: true
            });
        });
    }

    $.initSelect2Ajax = function () {
        $('.select2-ajax').each(function () {
            var urlComponent = $(this).attr('data-url') + "?";
            if ($(this).attr('data-nonactif') == '1') {
                urlComponent += "nonactif=1";
            } else {
                urlComponent += "nonactif=0";
            }
            $(this).select2({
                theme: 'bootstrap',
                minimumInputLength: 2,
                allowClear: true,
                ajax: {
                    type: "GET",
                    url: urlComponent,
                    delay: 500,
                    data: function (params) {
                        var queryParameters = {
                            term: params.term

                        }
                        return queryParameters;
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    }
                }
            });
        });
    }

    $(".select2SubmitOnChange").on("change", function (e) {
        if ($(this).val()) {
            $(this).parents('form').submit();
        }
    });

    $.initTypeheadFacture = function () {
        if (!$('#formProduitsSuggested').length) {
            return;
        }
        var produits = $('#formProduitsSuggested').data('produits');

        var produitsSource = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('libelle'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            local: produits
        });

        $('td > .typeahead').typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        },
        {
            limit: 9,
            name: 'produits',
            display: 'libelle',
            source: produitsSource,
            templates: {
                suggestion: function (e) {
                    var libelle = e.libelle + "<span class='text-muted'> à " + e.prix + " €</span>";
                    if (e.conditionnement) {
                        libelle += "<small> (" + e.conditionnement + ")</small>";
                    }
                    return $("<div>" + libelle + "</div>");
                }
            }
        });

        $('.typeahead').bind('typeahead:select', function (ev, suggestion) {
            $(this).parents(".dynamic-collection-item").find('.prix-unitaire').val(suggestion.prix);
        });
    }

    $.initTypeheadSearchableCheckbox = function () {
        if (!$('#searchable').length || !$('#searchable').find("input[type=checkbox]").length) {
            return;
        }

        $('#searchable').find("input[type=checkbox]").on('click', function() {
            $('#searchable .typeahead').typeahead('destroy');
            $.initTypeheadSearchable();
        });
    }

    $.initTypeheadSearchable = function () {
        if (!$('#searchable').length) {
            return;
        }

        var checkbox = $('#searchable').find("input[type=checkbox]");
        var url = $('#searchable').data('url')+"?q=%QUERY&inactif="+((checkbox && checkbox.prop('checked'))? "1" : "0");
        var type = $('#searchable').data('type');
        var target = $('#searchable').data('target');

        $('#searchable .typeahead').typeahead({
    	  hint: false,
    	  highlight: true,
    	  minLength: 1
    	},
    	{
          limit: 9,
    	  source: new Bloodhound({
              datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
              queryTokenizer: Bloodhound.tokenizers.whitespace,
              remote: {
                url: url,
                wildcard: '%QUERY'
              }
            }),
          display: "libelle",
          async: true,
          templates: {
              suggestion: function (e) {
            	  if (type == 'societe') {
	            	  var result = '<i class="mdi mdi-'+e.icon+' mdi-lg"></i>&nbsp;'+e.libelle+' <small>n°&nbsp;'+e.identifiant+'</small>';
	            	  if (!e.actif) {
	            		  result = result+' <small><label class="label label-xs label-danger">SUSPENDU</label></small>';
	            	  }
	            	  if (target) {
	            		  return $('<div class="searchable_result"><a href="'+(target.replace('_id_', e.id)).replace('_object_', e.object)+'">'+result+'</a></div>');
	            	  } else {
	            		  return $('<div class="searchable_result">'+result+'</div>');
	            	  }
            	  }
            	  if (type == 'contrat') {
	            	  var result = e.type+' <small class="text-'+e.color+'">'+e.statut+'</small> n°<strong>'+e.identifiant+'</strong> '+e.periode+' <small class="text-muted">'+e.garantie+'</small> '+e.prix+' €';
	            	  if (target) {
	            		  return $('<div class="searchable_result"><a href="'+target.replace('_id_', e.id)+'">'+result+'</a></div>');
	            	  } else {
	            		  return $('<div class="searchable_result">'+result+'</div>');
	            	  }
            	  }
            	  return '';
              },
              notFound: function(query) {
            	  if (target) {
            		  return "<div class=\"searchable_result tt-suggestion tt-selectable\"><span class=\"glyphicon glyphicon-search\"></span> <a id=\"search_more_submit\" href=\"\">Cliquez ici pour optimiser la recherche \""+query.query+"\" dans la recherche avancée</a></div>";
            	  }

              },
              footer: function(query, suggestions) {
            	  if (target) {
	                return "<div class=\"searchable_result tt-suggestion tt-selectable\"><span class=\"glyphicon glyphicon-search\"></span> <a id=\"search_more_submit\" href=\"\">Cliquez ici pour optimiser la recherche \""+query.query+"\" dans la recherche avancée</div></a>";
	              }
              }
          }
        });

        $('#searchable').on("click", "#search_more_submit", function() {
            $('#searchable form').submit();
            return false;
        });

        $('#searchable .typeahead').bind('typeahead:cursorchange', function (event, suggestion) {
            $('#societe_choice_societes').val($('.typeahead').typeahead('val'));
        });

        $('#searchable .typeahead').bind('typeahead:asyncreceive', function (event, suggestion) {
            $('#searchable').find(".tt-dataset .tt-suggestion:first").addClass('tt-cursor');
        });

        $('#searchable .typeahead').bind('typeahead:select', function(ev, suggestion) {
        	if (target) {
        		document.location.href=(target.replace('_id_', suggestion.id)).replace('_object_', suggestion.object);
        	}
        });

    }

    $.initModalPassage = function () {
        $('#modal-calendrier-infos').on('show.bs.modal', function (event) {
            var link = $(event.relatedTarget);
            if (link.length) {
                $('#modal-calendrier-infos').html("");
                $('#modal-calendrier-infos').load(link.attr('href'), function () {
                    $.callbackEventForm();
                });
            }
        })
    }

    $.initHamzaStyle = function () {
        $('.hamzastyle').each(function () {
            var select2 = $(this);
            var words = [];
            $('.hamzastyle-item').each(function () {
                words = words.concat(JSON.parse($(this).attr('data-words')));
            });
            var words = unique(words.sort());
            var data = [];
            for (key in words) {
                if ((words[key] + "").length > 1) {
                    data.push({id: words[key] + "", text: (words[key] + "")});
                }
            }


            select2.select2({
                theme: 'bootstrap',
                multiple: true,
                data: data
            })
        });

        $(document).find('.hamzastyle').on("change", function (e) {
            var select2Data = $(this).select2("data");
            var selectedWords = [];
            for (key in select2Data) {
                selectedWords.push(select2Data[key].text);
            }

            if (!selectedWords.length) {
                document.location.hash = "";
            } else {
                document.location.hash = encodeURI("#filtre=" + JSON.stringify(selectedWords));
            }
        });
    }

    $.initQueryHash = function () {
        $(window).on('hashchange', function () {
            if ($(document).find('.hamzastyle').length) {
                var params = jQuery.parseParams(location.hash.replace("#", ""));
                var filtres = [];
                if (params.filtre && params.filtre.match(/\[/)) {
                    filtres = JSON.parse(params.filtre);
                } else if (params.filtre) {
                    filtres.push(params.filtre);
                }

                var select2Data = [];
                for (key in filtres) {
                    select2Data.push(filtres[key]);
                }

                $(document).find('.hamzastyle').trigger("change");
                $(document).find('.hamzastyle').val(select2Data).trigger("change");
                var exportfiltre = $(document).find('.hamzastyle').attr('data-hamzastyle-export');
                var ids = [];
                $(document).find('.hamzastyle-item').each(function () {
                    var words = JSON.parse($(this).attr('data-words'));
                    var find = true;
                    for (key in filtres) {
                        var word = filtres[key];
                        if (words.indexOf(word) === -1) {
                            find = false;
                        }
                    }
                    if (find) {
                        $(this).show();
                        if(exportfiltre && !$(this).hasClass('show-print')){
                          ids.push($(this).attr('id'));
                        }
                    } else {
                        $(this).hide();
                    }
                });
                if(exportfiltre){
                  $(exportfiltre).val(JSON.stringify(ids));
                }
            }
            if ($(document).find('.nav.nav-tabs').length) {
                var params = jQuery.parseParams(location.hash.replace("#", ""));
                if (params.tab) {
                    $('.nav.nav-tabs a[aria-controls="' + params.tab + '"]').tab('show');
                }
            }
        });
        if (location.hash) {
            $(window).trigger('hashchange');
        }
    }

    $.initMap = function () {
        if ($('#map').length) {
            var lat = 48.8593829;
            var lon = 2.347227;
            var zoom = 0;
            if ($('#map').attr('data-lat') && $('#map').attr('data-lon')) {
                lat = $('#map').data('lat');
                lon = $('#map').data('lon');
            }
            if($('#map').attr('data-zoom')){
                zoom = $('#map').data('zoom');
            }

            var map = L.map('map').setView([lat, lon], zoom);

            L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var geojson = JSON.parse($('#map').attr('data-geojson'));
            var markers = [];
            var hoverTimeout = null;
            var hasHistoryRewrite = false;
            if ($('#map').attr('data-historyrewrite')){
              hasHistoryRewrite = $('#map').data('historyrewrite');
            }
            L.geoJson(geojson,
                    {
                        onEachFeature: function (feature, layer) {
                            if ($('#liste_passage').length) {
                                layer.on('click', function (e) {
                                    $('.leaflet-marker-icon').css('opacity', '0.5');
                                    $(e.target._icon).css('opacity', '1');
                                    e.target.setZIndexOffset(1001);
                                    if (hoverTimeout) {
                                        clearTimeout(hoverTimeout);
                                    }
                                    e.target.bindPopup("<b>"+e.target.feature.properties.nom+"</b>").openPopup();

                                    hoverTimeout = setTimeout(function () {
                                        $('#liste_passage .panel').blur();
                                        var element = $('#' + e.target.feature.properties._id);
                                        var list = $('#liste_passage');
                                        list.scrollTop(0);
                                        list.scrollTop(element.position().top - (list.height() / 2) + (element.height()));
                                        element.focus();
                                        $(document.getElementById("zoom-"+e.target.feature.properties._id)).trigger('click');
                                    }, 400);


                                });

                                layer.on('mouseover', function (e) {
                                    $('.leaflet-marker-icon').css('opacity', '0.5');
                                    $(e.target._icon).css('opacity', '1');
                                    e.target.setZIndexOffset(1001);
                                    if (hoverTimeout) {
                                        clearTimeout(hoverTimeout);
                                    }
                                    e.target.bindPopup("<b>"+e.target.feature.properties.nom+"</b>").openPopup();
                                });
                                layer.on('mouseout', function (e) {
                                    if (hoverTimeout) {
                                        clearTimeout(hoverTimeout);
                                    }
                                    e.target.setZIndexOffset(900);
                                    $('#' + e.target.feature.properties._id).blur();
                                    $('.leaflet-marker-icon').css('opacity', '1');
                                });

                            }
                        },
                        pointToLayer: function (feature, latlng) {
                            var marker = L.marker(latlng, {icon: L.ExtraMarkers.icon({
                                    icon: feature.properties.icon,
                                    markerColor: feature.properties.color,
                                    iconColor: feature.properties.colorText,
                                    shape: 'circle',
                                    prefix: 'mdi',
                                    svg: true
                                })});
                            markers[feature.properties._id] = marker;
                            return marker;
                        }
                    }
            ).addTo(map);

            var refreshListFromMapBounds = function(){
              var filtre = window.location.hash;
              if(!filtre){
                $('div#liste_passage div.panel').each(function(){
                    var hasMarker = markers[$(this).attr('id')] != undefined ;
                    const hasGeo = $(this).hasClass('no-geojson') == false
                    if (! hasGeo) {
                        $(this).show()
                        return true
                    }
                    if(!hasMarker){
                      $(this).hide();
                    }
                    else{
                      $(this).show();
                    }
                });
                for (var id in markers) {
                  var marker = markers[id];
                  if(map.getBounds().contains(marker._latlng)){
                    $('div#liste_passage div#'+id).show();
                  }else{
                    $('div#liste_passage div#'+id).hide();
                  }
                }
              }
            }

            if(!zoom){
              var markersArr = [];
              for (id in markers) {
                  var latlng = markers[id]._latlng;
                  markersArr.push(latlng);
              }
              var bounds = new L.LatLngBounds(markersArr);
              map.fitBounds(bounds);
            }else{
              refreshListFromMapBounds();
            }

            $('#liste_passage .panel').hover(function () {
                var marker = markers[$(this).attr('id')];
                if(typeof marker != 'undefined' && marker){
                  $('.leaflet-marker-icon').css('opacity', '0.3');
                  $(marker._icon).css('opacity', '1');
                  marker.setZIndexOffset(1001);
                }
            }, function () {
                var marker = markers[$(this).attr('id')];
                if(typeof marker != 'undefined' && marker){
                  marker.setZIndexOffset(900);
                  $('.leaflet-marker-icon').css('opacity', '1');
                }
            });

            $('#liste_passage .mdi-zoom-in').click(function () {
                var marker = markers[$(this).parent().parent().parent().attr('id')];
                if(typeof marker != 'undefined' && marker){
                  if(document.location.hash != ""){
                    $(window).trigger('hashchange');
                  }
                  map.setZoomAround(marker._latlng,15);
                  map.panTo(marker._latlng);
                  marker.bindPopup("<b>"+marker.feature.properties.nom+"</b>").openPopup();
                }
            });

            $('#liste_passage .btn-more-info').click(function () {
                map.closePopup();
            });


            if(hasHistoryRewrite){
              map.on('moveend', function(){
                var center = map.getCenter();
                var hash = window.location.hash;
                history.pushState(null, null, "?lat="+center.lat+"&lon="+center.lng+"&zoom="+ map.getZoom()+hash);
                refreshListFromMapBounds();
              });
            }


            $(window).on('hashchange', function () {
                if(document.location.hash != ""){
                  map.closePopup();
                }
                var visibleMarkers = [];
                $('#liste_passage .panel').each(function () {
                    if (!$(this).is(':visible')) {
                        var marker = markers[$(this).attr('id')];
                        if(typeof marker != 'undefined' && marker){
                          $(marker._icon).css('opacity', '0');
                          $(marker._icon).addClass('hidden');
                          $(marker._shadow).addClass('hidden');
                          marker.setZIndexOffset(1001);
                        }
                    } else {
                        var marker = markers[$(this).attr('id')];
                        if(typeof marker != 'undefined' && marker){
                          $(marker._icon).css('opacity', '1');
                          $(marker._icon).removeClass('hidden');
                          $(marker._shadow).removeClass('hidden');
                          marker.setZIndexOffset(900);
                          visibleMarkers.push(marker._latlng);
                        }
                    }

                });
                if(document.location.hash !=""){
                  var bounds = new L.LatLngBounds(visibleMarkers);
                  map.fitBounds(bounds);
                }
                refreshListFromMapBounds();
            });
        }
    }

    $.initMoreInfo = function () {
      $('#liste_passage .mdi-zoom-in').click(function () {
        var div = $(this).next();
        var divInfoPassage = document.getElementById('info-passage');

        $(divInfoPassage).html("<pre>Chargement...</pre>");
        $.get(div.data('url'), function (result) {
            $(divInfoPassage).html(result);
        })
        .fail(function () {
            $(divInfoPassage).html("<pre>Erreur lors du chargement des informations</pre>");
            button.text(' Réessayer');
        });
        var clicked_div = document.getElementsByClassName("clicked-div");
        if(clicked_div.length > 0){
          $(clicked_div[0]).css("border-color","");
          $(clicked_div[0]).removeClass("clicked-div");
        }
        $(this).parent().parent().parent().css("border-color","black");
        $(this).parent().parent().parent().addClass("clicked-div");
      });


      $(".btn-more-info").on("click", function () {
        var div = $(this).prev();
        var divInfoPassage = document.getElementById('info-passage');

        $(divInfoPassage).html("<pre>Chargement...</pre>");
        $.get(div.data('url'), function (result) {
            $(divInfoPassage).html(result);
        })
        .fail(function () {
            $(divInfoPassage).html("<pre>Erreur lors du chargement des informations</pre>");
            $(this).text(' Réessayer');
        });

        var clicked_div = document.getElementsByClassName("clicked-div");
        if(clicked_div.length > 0){
          $(clicked_div[0]).css("border-color","");
          $(clicked_div[0]).removeClass("clicked-div");
        }
        $(this).css("border-color","black");
        $(this).addClass("clicked-div");
      });
    }

    $.initTourneeDatepicker = function () {
      $("#tournees-choice-datetimepicker").change(function(){
        var url = $(this).find('input').attr('data-url');
        var date = $(this).find('input').val();
        var dateiso = date.split('/').reverse().join('-');
        window.location = url+'/'+dateiso;
      });
    }

    $.initAttachements = function(){

        $('.thumbnail').each(function(){
            var modal = $('#'+$(this).data('cible'));
            var img = $(this).find('img');
            var modalImg = modal.find("#img");
            var captionText = modal.find(".caption");
            var close = modal.find(".modal-viewer-close");
            $(this).click(function(){

                if(modal.css("display") == "none"){
                    modal.css("display", "block");
                    modalImg.attr('src',img.attr('src'));
                }else{
                    modal.css("display", "none");
                }
            });

            modal.click(function(){
                if($(this).css("display") != "none"){
                    $(this).css("display", "none");
                }
            });

            close.click(function() {
                modal.css("display", "none");
            });

        });
    }

    $.initTransfertContrat = function() {
        $('#contrat_transfert_societe').on('change', function (e) {
        	var societe = $(this).val();
        	var url = $(this).data('etablissements');
            if(societe) {
            	url = url.replace('__societe_id__', societe);
            	var opts = '<option value="" selected="selected"></option>';
                $('#input-etablissements .select2-selection__rendered').attr('title', '');
                $('#input-etablissements .select2-selection__rendered').html('');
            	$.get(url, function (result) {
            		for(i in result) {
            			console.log(i+' '+result[i]);
            			opts += '<option value="'+i+'">'+result[i]+'</option>';
            		}
                	$('.select2-etablissements').html(opts);
                    $('.select2-etablissements').removeAttr('disabled', 'disabled');
                });
            } else {
            	$('.select2-etablissements').html('<option value="" selected="selected"></option>');
                $('.select2-etablissements').attr('disabled', 'disabled');
                $('#input-etablissements .select2-selection__rendered').attr('title', '');
                $('#input-etablissements .select2-selection__rendered').html('');
            }
        });
        $('#contrat_transfert_societe').change();
    }

    $.initPaiementsAutoSave = function() {
        $(document).on('blur','.paiement_row input', function(){
          var row = $(this).parents(".paiement_row");
          var type_reglement = row.find("select[id$='typeReglement']");
          var moyen_paiement = row.find("select[id$='moyenPaiement']");
          var libelle = row.find("input[id$='libelle']");
          var facture = row.find("select[id$='facture']");
          var date_paiement = row.find("input[id$='datePaiement']");
          var montant = row.find("input[id$='montant']");
          var hiddenFactureMontantTTC = row.find("input[id$='factureMontantTTC']");
          if(!montant.val()){
            montant.val(0.0);
          }
          var numLigne = row.parent().index();

          if(type_reglement.val() && moyen_paiement.val() && libelle.val() && facture.val() && date_paiement.val() && montant.val()){
            var form = $(this).parents('form[name="paiements"]');
            var url = form.attr('data-url-ajax-row');
            var id = form.data('id');
            if(parseFloat(montant.val().replace(',','.')) > 0.0 ){
              if(parseFloat(montant.val().replace(',','.')) == parseFloat(hiddenFactureMontantTTC.val().replace(',','.'))){
                row.parent().addClass("alert-success");
                row.parent().removeClass("alert-warning");
              }else{
                row.parent().addClass("alert-warning");
                row.parent().removeClass("alert-success");
              }
            }else{
              row.parent().removeClass("alert-success");
              row.parent().removeClass("alert-warning");
            }
            $.ajax({
              type: "POST",
              url: url,
              data: {
                      idLigne: numLigne,
                      type_reglement : type_reglement.val(),
                      moyen_paiement : moyen_paiement.val(),
                      libelle : libelle.val(),
                      facture : facture.val(),
                      date_paiement : date_paiement.val(),
                      montant : montant.val()
                    }
            });
          }
        });
    }

    $.initCommentairesPlanif = function() {
      $('body').on('click',".commentaire_lien",function (event) {
          event.preventDefault();
          var url = $(this).attr('data-url')+"?service="+encodeURIComponent(window.location.href);
          window.location.href = url;
        });

    }


    $.initAutocompleteAdresse = function () {
        $("#societe_edition_adresse_adresse").autocomplete({
        source: function (request, response) {
          $.ajax({
              url: "https://api-adresse.data.gouv.fr/search/?q="+$("input[name='societe_edition[adresse][adresse]']").val(),
              data: { q: request.term },
              dataType: "json",
              success: function (data) {
                  response($.map(data.features, function (item) {
                      return { label : item.properties.label, value : item.properties.name, postcode : item.properties.postcode, city : item.properties.city, lat : item.geometry.coordinates[1], lon : item.geometry.coordinates[0]};
                  }));
              }
          });
        },

        select: function(event, ui) {
            $('#societe_edition_adresse_codePostal').val(ui.item.postcode);
            $("#societe_edition_adresse_commune").val(ui.item.city);
            $("#societe_edition_adresse_lat").val(ui.item.lat);
            $("#societe_edition_adresse_lon").val(ui.item.lon);
            $("#societe_edition_adresse_adresse").click();
        }
        });

        $("#etablissement_adresse_adresse").autocomplete({
        source: function (request, response) {
          $.ajax({
              url: "https://api-adresse.data.gouv.fr/search/?q="+$("input[name='etablissement_[adresse][adresse]']").val(),
              data: { q: request.term },
              dataType: "json",
              success: function (data) {
                  response($.map(data.features, function (item) {
                      return { label : item.properties.label, value : item.properties.name, postcode : item.properties.postcode, city : item.properties.city, lat : item.geometry.coordinates[1], lon : item.geometry.coordinates[0]};
                  }));
              }
          });
        },

        select: function(event, ui) {
            $('#etablissement_adresse_codePostal').val(ui.item.postcode);
            $("#etablissement_adresse_commune").val(ui.item.city);
            $("#etablissement_adresse_lat").val(ui.item.lat);
            $("#etablissement_adresse_lon").val(ui.item.lon);
            $("#etablissement_adresse_adresse").click();
        }
        });
    }

    $.initMapForAdresse = function(){
      if ($('#mapForLatLng').length) {
        var lat = 48.8593829;
        var lon = 2.347227;
        var notIdentifie = true;

        if($('#societe_edition_adresse_lat').val()){
          lat=$('#societe_edition_adresse_lat').val();
          var notIdentifie = false;
        }
        if($('#societe_edition_adresse_lon').val()){
          lon=$('#societe_edition_adresse_lon').val();
          var notIdentifie = false;
        }
        var map = L.map('mapForLatLng').setView([lat, lon], 13);
        var marker = L.marker([lat,lon]).addTo(map);
        var latlon = new L.LatLng(lat, lon);
        marker.setLatLng(latlon);
        if(notIdentifie==true){
          marker.bindPopup("<small> Déplacez moi </small>").openPopup();
        }
        L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        map.on('dblclick', onMapClick);

        $("#societe_edition_adresse_adresse").bind("propertychange change click keyup input paste",changeMarkerPlace);
        $("#societe_edition_adresse_lat").bind("propertychange change click keyup input paste",changeMarkerPlace);
        $("#societe_edition_adresse_lon").bind("propertychange change click keyup input paste",changeMarkerPlace);

        function changeMarkerPlace(){
          var lat = 48.8593829;
          var lon = 2.347227;
          var notIdentifie = true;

          if($("#societe_edition_adresse_lat").val()){
            lat = $("#societe_edition_adresse_lat").val();
            var notIdentifie = false;
          }
          if($("#societe_edition_adresse_lon").val()){
            lon = $("#societe_edition_adresse_lon").val();
            var notIdentifie = false;
          }
          map.eachLayer((layer) => {
            if(layer._latlng){
              map.removeLayer(layer);
            }
          });
          var marker = L.marker([lat,lon]).addTo(map);
          if(notIdentifie==true){
            marker.bindPopup("<small> Déplacez moi </small>").openPopup();
          }
          map.addLayer(marker);
          map.setZoomAround(marker._latlng,13);
          map.panTo(marker._latlng);
        }

        function onMapClick(e) {
          map.eachLayer((layer) => {
            if(layer._latlng){
              map.removeLayer(layer);
            }
          });
          marker = new L.marker(e.latlng, {draggable:'true'});
          marker.on('dragend', function(event){
            var marker = event.target;
            var position = marker.getLatLng();
            marker.setLatLng(new L.LatLng(position.lat, position.lng),{draggable:'true'});
            $("#societe_edition_adresse_lat").val(marker._latlng.lat);
            $("#societe_edition_adresse_lon").val(marker._latlng.lng);
          });
          map.addLayer(marker);
          $("#societe_edition_adresse_lat").val(marker._latlng.lat);
          $("#societe_edition_adresse_lon").val(marker._latlng.lng);
        };
      }

      if($('#mapForLatLngEtablissement').length){

        var lat = 48.8593829;
        var lon = 2.347227;
        var notIdentifie = true;

        if($('#etablissement_adresse_lat').val()){
          lat=$('#etablissement_adresse_lat').val();
          notIdentifie = false;
        }
        if($('#etablissement_adresse_lon').val()){
          lon=$('#etablissement_adresse_lon').val()
          notIdentifie = false;
        }
        var map = L.map('mapForLatLngEtablissement').setView([lat, lon], 13);
        var marker = L.marker([lat,lon]).addTo(map);
        var latlon = new L.LatLng(lat, lon);
        marker.setLatLng(latlon);

        if(notIdentifie==true){
          marker.bindPopup("<small> Déplacez moi </small>").openPopup();
        }

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        map.on('dblclick', onMapClick);

        const resizeObserver = new ResizeObserver(() => {
          map.invalidateSize();
        });
        const mapDiv = document.getElementById("mapForLatLngEtablissement");
        resizeObserver.observe(mapDiv);

        function onMapClick(e) {
          map.eachLayer((layer) => {
            if(layer._latlng){
              map.removeLayer(layer);
            }
          });
          marker = new L.marker(e.latlng, {draggable:'true'});
          marker.on('dragend', function(event){
            var marker = event.target;
            var position = marker.getLatLng();
            marker.setLatLng(new L.LatLng(position.lat, position.lng),{draggable:'true'});
            $("#etablissement_adresse_lat").val(marker._latlng.lat);
            $("#etablissement_adresse_lon").val(marker._latlng.lng);
          });
          map.addLayer(marker);
          $("#etablissement_adresse_lat").val(marker._latlng.lat);
          $("#etablissement_adresse_lon").val(marker._latlng.lng);
        };

        $("#etablissement_adresse_adresse").bind("propertychange change click keyup input paste",changeMarkerPlace);
        $("#etablissement_adresse_lat").bind("propertychange change click keyup input paste",changeMarkerPlace);
        $("#etablissement_adresse_lon").bind("propertychange change click keyup input paste",changeMarkerPlace);


        function changeMarkerPlace(){
          var lat = 48.8593829;
          var lon = 2.347227;
          var notIdentifie = true;

          if($("#etablissement_adresse_lat").val()){
            lat = $("#etablissement_adresse_lat").val();
            var notIdentifie = false;
          }
          if($("#etablissement_adresse_lon").val()){
            lon = $("#etablissement_adresse_lon").val();
            var notIdentifie = false;
          }

          map.eachLayer((layer) => {
            if(layer._latlng){
              map.removeLayer(layer);
            }
          });
          var marker = L.marker([lat,lon]).addTo(map);

          if(notIdentifie==true){
            marker.bindPopup("<small> Déplacez moi </small>").openPopup();
          }
          map.addLayer(marker);
          map.setZoomAround(marker._latlng,13);
          map.panTo(marker._latlng);
        }

      }


    }

    $.initHighLight = function(){
      $(".highlight").click(function(e){
        const les = document.getElementsByTagName("tr");
        for(var i= 0; i < les.length; i++)
        {
          les[i].style.border = "none";
        }
        document.getElementById(this.dataset.id).style.border = "3px dashed  darkblue";
        document.getElementById(this.dataset.id).scrollIntoView({
            behavior: 'auto',
            block: 'center',
            inline: 'center'
        });
      });
    }
}
)(jQuery);
