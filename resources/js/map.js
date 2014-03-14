var map = (function() {
        var lemap = {};

        lemap.init = function() {
            var map = L.map('map',{crs:L.CRS.EPSG3857}).setView([-15.79889,-47.866667],4);
            var land = L.tileLayer('http://{s}.tile3.opencyclemap.org/landscape/{z}/{x}/{y}.png')//.addTo(map);
            var ocm = L.tileLayer('http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png').addTo(map);
            var osm = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png')//.addTo(map);

            lemap.map = map;

            /*
            var Icon = L.Icon.extend(L.Icon.Default.prototype.options);
            var blueIcon = new Icon({iconUrl: 'resources/js/images/marker-blue.png'}),
                redIcon = new Icon({iconUrl: 'resources/js/images/marker-red.png'});
            */

            var markersOk = new L.MarkerClusterGroup();
            var pointsOk  = new L.layerGroup();

            var markersNok = new L.MarkerClusterGroup();
            var pointsNok  = new L.layerGroup();

            var rePoints  = {};
            var rePoints2  = {};

            for(var i in occurrences) {
                var feature = occurrences[i];
                if(!feature.geometry || !feature.geometry.coordinates[0] || !feature.geometry.coordinates[1]) continue;

                feature.geometry.coordinates = feature.geometry.coordinates.map(function(c) { return parseFloat(c);});

                //var icon = (feature.properties.valid?blueIcon:redIcon);
                //var icon = L.Icon.Default;

                var marker = L.marker(new L.LatLng(feature.geometry.coordinates[0],feature.geometry.coordinates[1]));
                marker.bindPopup(feature.content);

                var marker2 = L.marker(new L.LatLng(feature.geometry.coordinates[0],feature.geometry.coordinates[1]));
                marker2.bindPopup(feature.content);

                if(feature.properties.valid) {
                    markersOk.addLayer(marker);
                    pointsOk.addLayer(marker2);
                } else {
                    markersNok.addLayer(marker);
                    pointsNok.addLayer(marker2);
                }

                rePoints[feature.properties.occurrenceID] = marker;
                rePoints2[feature.properties.occurrenceID] = marker2;
            }

            map.addLayer(markersOk);
            map.addLayer(markersNok);

            var base = {
                Landscape: land,
                OpenCycleMap: ocm,
                OpenStreetMap: osm
            };

            var layers = {
                'Valid points': pointsOk,
                'Valid points clustered': markersOk,
                'Non-valid points': pointsNok,
                'Non-valid points clustered': markersNok,
            };

            if(typeof eooPolygon != "undefined" && eooPolygon) {
                var points = eooPolygon.substr(eooPolygon.indexOf('(')).replace(/[\(\)]/g,'').split(',').map(function(s) { return s.split(" ").map(function(s){return parseFloat(s)});}).map(function(point) { return new L.LatLng(point[1],point[0])});
                var polygon = L.polygon(points);
                layers[ 'EOO' ] = polygon;
            }

            L.control.layers(base,layers).addTo(map);
            L.control.scale().addTo(map);

            window.onhashchange = function() {
                if(location.hash.match(/occ-/)) {
                    $(".hero-unit").removeClass("active");
                    $(window.location.hash+"-unit").addClass("active");
                }
            }

            $(".to-map").click(function(evt){
                var id = $(evt.target).attr("rel");
                map.setView(rePoints[id]._latlng,10)
                setTimeout(function(){
                    rePoints[id].openPopup();
                    rePoints2[id].openPopup();
                },1000);
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
