var map = (function() {
        var lemap = {};

        lemap.init = function() {
            var map = L.map('map',{crs:L.CRS.EPSG3857}).setView([-15.79889,-47.866667],4);
            var land = L.tileLayer('http://{s}.tile3.opencyclemap.org/landscape/{z}/{x}/{y}.png')//.addTo(map);
            var ocm = L.tileLayer('http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png').addTo(map);
            var osm = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png')//.addTo(map);

            lemap.map = map;

            var markers = new L.MarkerClusterGroup();
            var points  = new L.layerGroup();

            var rePoints  = {};

            for(var i in occurrences) {
                var feature = occurrences[i];
                if(!feature.geometry || !feature.geometry.coordinates[0] || !feature.geometry.coordinates[1]) continue;
                var marker = L.marker(new L.LatLng(feature.geometry.coordinates[0],feature.geometry.coordinates[1]));
                marker.bindPopup(feature.content);
                markers.addLayer(marker);
                var marker2 = L.marker(new L.LatLng(feature.geometry.coordinates[0],feature.geometry.coordinates[1]));
                marker2.bindPopup(feature.content);
                points.addLayer(marker2);

                rePoints[feature.properties.occurrenceID] = marker2;
            }

            map.addLayer(markers);

            var base = {
                Landscape: land,
                OpenCycleMap: ocm,
                OpenStreetMap: osm
            };

            var layers = {
                'Points': points,
                'Points clustered': markers,
            };

            L.control.layers(base,layers).addTo(map);

            window.onhashchange = function() {
                if(location.hash.match(/occ-/)) {
                    $(".hero-unit").removeClass("active");
                    $(window.location.hash).addClass("active");
                }
            }

            $(".to-map").click(function(evt){
                var id = $(evt.target).attr("rel");
                rePoints[id].openPopup();
                location.hash="map";
            });

            $(".hero-unit").click(function(evt) {
                $(".hero-unit").removeClass("active");
                $(this).addClass("active");
            });

            lemap.points = rePoints;

            $("body").on('click','a[href="#occ"]', function() { 
                L.Util.requestAnimFrame(map.invalidateSize,map,!1,map._container);
            });
        };

        return lemap;
})();
