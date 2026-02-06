(function ($)
{

    $(document).on("pageshow", "[data-role='page']", function () {
      $('div.ui-loader').hide();
    });

    window.onresize = updateSignaturesPads;

    var signaturesPad = [];
    var forms = [];
    var produitsCount = [];
    var niveauInfestationCount = [];
    var version = null;

    $(document).ready(function ()
    {
        $.initTourneeDate();
        $.initPhoenix();
        $.initNiveauInfestation();
        $.initProduits();
        updateSignaturesPads();
        $.initSaisie();
        $.initTransmission();
        $.initVersion();
        $.unactiveNotStaticLi();

    });

    $( document ).on( "pagecreate", function() {
        $( ".photopopup" ).on({
            popupbeforeposition: function() {
            var maxHeight = $( window ).height() - 60 + "px";
            $( ".photopopup img" ).css( "max-height", maxHeight );
            }
        });
    });

    $('.ajoutImage').click(function(e) {
        setTimeout(function() {
            $(this).removeClass("disabled");
        }, 1000);
        if($(this).hasClass('disabled')){
            e.preventDefault();
            return false;
        }else{
            $(this).addClass('disabled');
            setTimeout(function() {
                $(this).removeClass("disabled");
            }, 1000);
        }
    });

    $.initPhoenix = function(){
      $('.phoenix').each(function(){
        $(this).phoenix();
      });
    }

    $.initProduits = function(){
      $('.produits-list').each(function(){
        produitsCount[$(this).attr("data-id")] = $(this).children('li').length;
      });

      $('a.produits-ajout-lien').click(function(e) {
               e.preventDefault();
               var passageId = $(this).attr("data-id");

               var produitsList = $('#produits-liste-'+passageId);

               // grab the prototype template
               var newWidget = produitsList.attr('data-prototype');

               // replace the "__name__" used in the id and name of the prototype
               // with a number that's unique to your emails
               // end name attribute looks like name="contact[emails][2]"
                newWidget = newWidget.replace(/__name__/g, produitsCount[passageId]);

              // create a new list element and add it to the list
               var newLi = $('<li class="ui-li-static ui-body-inherit" ></li>').html(newWidget);
               newLi.appendTo(produitsList);
               var idPassageReplaced = passageId.replace(/-/g,'_');
               var newIdRow = "#mobile_"+idPassageReplaced+"_produits_"+produitsCount[passageId];
               $(newIdRow).find('select').selectmenu();
               $(newIdRow).find('input').textinput();
               produitsCount[passageId] = produitsCount[passageId] + 1;
           });
      }

      $.unactiveNotStaticLi = function(){
        $("li.notStatic").removeClass("ui-li-static");
      }

  $.initNiveauInfestation = function(){
      $('.niveauInfestation-list').each(function(){
        niveauInfestationCount[$(this).attr("data-id")] = $(this).children('li').length;
      });

      $('a.niveauInfestation-ajout-lien').click(function(e) {
               e.preventDefault();
               var passageId = $(this).attr("data-id");

               var niveauInfestationList = $('#niveauInfestation-liste-'+passageId);

               // grab the prototype template
               var newWidget = niveauInfestationList.attr('data-prototype');

               // replace the "__name__" used in the id and name of the prototype
               // with a number that's unique to your emails
               // end name attribute looks like name="contact[emails][2]"
                newWidget = newWidget.replace(/__name__/g, niveauInfestationCount[passageId]);

              // create a new list element and add it to the list
               var newLi = $('<li class="ui-li-static ui-body-inherit" ></li>').html(newWidget);
               newLi.appendTo(niveauInfestationList);
               var idPassageReplaced = passageId.replace(/-/g,'_');
               var newIdRow = "#passage_mobile_"+idPassageReplaced+"_niveauInfestation_"+niveauInfestationCount[passageId];
               $(newIdRow).find('select').selectmenu();
               $(newIdRow).find('input').textinput();
               niveauInfestationCount[passageId] = niveauInfestationCount[passageId] + 1;
           });
    };

    $.initSaisie = function () {

      $('form').each(function(){
          forms[$(this).closest('.rapport').attr('data-id')] = $(this);
      });

      $('.signature_vider').on("click",function(){
        if(!confirm("Êtes-vous sûr de vouloir effacer la signature ?")) {
          return;
        }
        var signaturePadIndex = "signature_pad_"+$(this).attr('data-id');
        let passageId = $(this).attr('data-id')
        var divs = document.querySelectorAll('canvas');
        [].forEach.call(divs, function(div) {
            if(signaturePadIndex == div.id){
              var idCanva = div.id;
              signaturesPad[idCanva].clear();
              var signatureHiddenCible = "input[data-cible='mobile_"+passageId+"_signatureBase64']";
              $(signatureHiddenCible).each(function(){ $(this).val(""); });
            }
          });
        });
    }

    $.initTransmission = function () {

      $(".transmission_rapport").on("click",function(){
        let transmission = true;
        if(document.getElementById('mobile_'+($(this).attr('data-id')))) {
            transmission = document.getElementById('mobile_'+($(this).attr('data-id')).replaceAll("-","_")+"_dureeRaw").value;
        }
        if(!transmission){
           location.href = "#rapport_"+$(this).attr('data-id');
           if(document.getElementById("comment_"+$(this).attr('data-id')+"_dureeRaw")){
              el = document.getElementById("comment_"+$(this).attr('data-id')+"_dureeRaw");
              el.parentNode.removeChild(el);
           }
           $(document.getElementById("mobile_"+$(this).attr('data-id').replaceAll("-","_")+"_dureeRaw")).parent().after("<span style='color:red;' id='comment_"+$(this).attr('data-id')+"_dureeRaw'>Merci de remplir le champ</span>");
        }
        else{
          var formToPost = forms[$(this).attr('data-id')];
          formToPost.serialize();
          formToPost.submit();
        }

      });
    }

    $.initVersion = function () {

      version = $("#version").attr('data-version');
      $.checkVersion();
      var urlVersion = $("#version").attr("data-url");
      if (urlVersion) {
         setInterval(function(){
          $.ajax({
                   type: "GET",
                   url: urlVersion,
                   success: function (data) {
                        result = JSON.parse(data);
                        $("#version").attr('data-version',result.version);
                        $.checkVersion();
                   }
               });
        }
      , 20000);
      }
    }

    $.checkVersion = function () {
      versionDiv = $("#version").attr('data-version');
      if(versionDiv != version){
        $(".reloadWarning").each(function(){
          $(this).show();
        });
      }else{
        $(".reloadWarning").each(function(){
          $(this).hide();
        });
      }
    }

    $.initTourneeDate = function(){
      $("#tourneesDate").on('change',function(){
        var date = $(this).val();
        var dateiso = date.split('/').reverse().join('-');
        window.location = $(this).attr('data-url-cible')+"/"+dateiso;
      })
    }


    function updateSignaturesPads(){
      var divs = document.querySelectorAll('canvas');
      [].forEach.call(divs, function(canvas) {
        var idCanva = canvas.id;
        var parent = $("#"+idCanva).parent();
        $("#"+idCanva).remove();
        parent.append("<canvas id='"+idCanva+"'></canvas>");
      });

      var newwidth = $(document).width()*0.85;
      var ratio = 1.0/2.90;
      $('.signature-pad').each(function(){
        $(this).css('width',newwidth);
        $(this).css('height',newwidth*ratio);
      });
      var divs = document.querySelectorAll('canvas');
      [].forEach.call(divs, function(canvas) {

        var idCanva = canvas.id;
        canvas.width = $("#"+idCanva).parent().width();
        canvas.height = $("#"+idCanva).parent().height();
      });
      var divs = document.querySelectorAll('canvas');
      [].forEach.call(divs, function(div) {
          let idCanva = div.id;
          delete signaturesPad[idCanva];
          signaturesPad[idCanva] = new SignaturePad(div);
          let idObject = $("#"+idCanva).parents('.signature').attr('data-id');
          let signatureHiddenCible = "input[data-cible='mobile_"+idObject+"_signatureBase64']";
          signaturesPad[idCanva].onEnd = function(e) {
            let signaturePad = this;
              $(signatureHiddenCible).each(function(){
                $(this).val(signaturePad.toDataURL());
              });
          };
          $(signatureHiddenCible).each(function(){
            if ($(this).val()) {
              signaturesPad[idCanva].fromDataURL($(this).val());
            }
          });
       });
    }

    function pageIsSelectmenuDialog( page ) {
        var isDialog = false,
            id = page && page.attr( "id" );
        $( ".filterable-select" ).each( function() {
            if ( $( this ).attr( "id" ) + "-dialog" === id ) {
                isDialog = true;
                return false;
            }
        });
        return isDialog;
    }
    $.mobile.document
        .on( "selectmenucreate", ".filterable-select", function( event ) {
            var input,
                selectmenu = $( event.target ),
                list = $( "#" + selectmenu.attr( "id" ) + "-menu" ),
                form = list.jqmData( "filter-form" );
            if ( !form ) {
                input = $( "<input data-type='search'></input>" );
                form = $( "<form style='padding-bottom: 10px;'></form>" ).append( input );
                input.textinput();
                list
                    .before( form )
                    .jqmData( "filter-form", form ) ;
                form.jqmData( "listview", list );
            }
            selectmenu
                .filterable({
                    input: input,
                    children: "> option[value]"
                })
                .on( "filterablefilter", function() {
                    selectmenu.selectmenu( "refresh" );
                })
                .on("change", function(event) {
                    input.val("");
                    input.change();
                    selectmenu.selectmenu( "refresh" );
                });
        })
        .on( "pagecontainerbeforeshow", function( event, data ) {
            var listview, form;

            if ( !pageIsSelectmenuDialog( data.toPage ) ) {
                return;
            }
            listview = data.toPage.find( "ul" );
            form = listview.jqmData( "filter-form" );
            data.toPage.jqmData( "listview", listview );
            listview.before( form );
        })
        .on( "pagecontainerhide", function( event, data ) {
            var listview, form;
            if ( !pageIsSelectmenuDialog( data.toPage ) ) {
                return;
            }
            listview = data.toPage.jqmData( "listview" ),
            form = listview.jqmData( "filter-form" );
            listview.before( form );
        });


})(jQuery);


function verifyDuree(id_passage,element){
    var duree = document.getElementById('mobile_'+id_passage.replaceAll("-","_")+"_dureeRaw").value;
    if(document.getElementById("comment_"+id_passage+"_dureeRaw")){
      el = document.getElementById("comment_"+id_passage+"_dureeRaw");
      console.log(el);
      el.parentNode.removeChild(el);
    }
    if(!duree){
      $(document.getElementById("mobile_"+id_passage.replaceAll("-","_")+"_dureeRaw")).parent().after("<span style='color:red;' id='comment_"+id_passage+"_dureeRaw'>Merci de remplir le champ</span>");
      event.preventDefault();
      document.getElementById("ancre-champ-duree").scrollIntoView();
    }
}
