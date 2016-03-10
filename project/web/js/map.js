(function ($)
{

    $(document).ready(function ()
    {
        if($('#map').length) {
            var map = L.map('map').setView([48.8593829, 2.347227], 12);

            L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var geojson = JSON.parse($('#map').attr('data-geojson'));
            var markers = [];
            L.geoJson(geojson, 
                {
                    onEachFeature: function (feature, layer) {
                        layer.bindPopup(feature.properties.nom);
                    },
                    pointToLayer: function (feature, latlng) {
                        var marker = L.marker(latlng, {icon: L.ExtraMarkers.icon({
                                                    icon: feature.properties.icon,
                                                    markerColor: feature.properties.color,
                                                    iconColor: 'black',
                                                    shape: 'circle',
                                                    prefix: 'mdi'
                                                })});
                        markers.push(marker);
                        
                        return marker;
                    }
                }
            ).addTo(map);
        }

    });

})(jQuery);