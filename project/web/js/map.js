(function ($)
{

    $(document).ready(function ()
    {
        if($('#map').length) {
            var lat = 48.8593829;
            var lon = 2.347227;
            if($('#map').attr('data-lat') && $('#map').attr('data-lon')){
                lat = $('#map').data('lat');
                lon = $('#map').data('lon');
            }
            
            var map = L.map('map').setView([lat, lon], 12);

            L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var geojson = JSON.parse($('#map').attr('data-geojson'));
            var markers = [];
            var hoverTimeout = null;

            L.geoJson(geojson, 
                {
                    onEachFeature: function (feature, layer) {
                        if($('#liste_passage').length) {
                            layer.on('mouseover', function(e) {
                                $('.leaflet-marker-icon').css('opacity', '0.5');
                                $(e.target._icon).css('opacity', '1');
                                e.target.setZIndexOffset(1001);
                                if(hoverTimeout) {
                                    clearTimeout(hoverTimeout);
                                }
                                hoverTimeout = setTimeout(function(){
                                    $('#liste_passage .list-group-item').blur();
                                    var element = $('#'+e.target.feature.properties._id);
                                    var list = $('#liste_passage');
                                    list.scrollTop(0);
                                    list.scrollTop(element.position().top - (list.height()/2) + (element.height()));
                                    element.focus();
                                }, 400);
                            });
                            layer.on('mouseout', function(e) {
                                if(hoverTimeout) {
                                    clearTimeout(hoverTimeout);
                                }
                                e.target.setZIndexOffset(900);
                                $('#'+e.target.feature.properties._id).blur();
                                $('.leaflet-marker-icon').css('opacity', '1');
                            });

                            layer.on('click', function(e) {
                                document.location.href= $('#'+e.target.feature.properties._id).attr('href');
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

            $('#liste_passage .list-group-item').hover(function () {
                var marker = markers[$(this).attr('id')];
                $('.leaflet-marker-icon').css('opacity', '0.3');
                $(marker._icon).css('opacity', '1');
                marker.setZIndexOffset(1001);
            }, function () {
                var marker = markers[$(this).attr('id')];
                marker.setZIndexOffset(900);
                $('.leaflet-marker-icon').css('opacity', '1');
            });
            
            $(window).on('hashchange', function () {
                $('#liste_passage .list-group-item').each(function () {
                    if (!$(this).is(':visible')) {
                        var marker = markers[$(this).attr('id')];
                        $(marker._icon).css('opacity', '0');
                        $(marker._icon).addClass('hidden');
                        $(marker._shadow).addClass('hidden');
                        marker.setZIndexOffset(1001);
                    }else{
                        var marker = markers[$(this).attr('id')];
                        $(marker._icon).css('opacity', '1');
                        $(marker._icon).removeClass('hidden');
                        $(marker._shadow).removeClass('hidden');
                        marker.setZIndexOffset(900);
                    }

                });
            });
        }
    });

})(jQuery);