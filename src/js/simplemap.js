// simplemap.js
// Simple Map using Leaflet with GeoJSON
// By Abdullah Daud, chelahmy@gmail.com
// 10 January 2023

var map;
var map_id = "map";
var map_height = "240px";
var map_zoom_level = 13;

var get_bounding_box = function (gobj) {
	var x1, y1, x2, y2;
	
	if (typeof gobj.features !== "undefined") {
		var fs = gobj.features;
		for (var i in fs) {
			if (fs.hasOwnProperty(i)) {
				var f = fs[i];
				if (typeof f.geometry !== "undefined") {
					var g = f.geometry;
					if (typeof g.coordinates !== "undefined") {
						var c = g.coordinates;
						for (var j in c) {
							if (c.hasOwnProperty(j)) {
								var p = c[j];
								if (Array.isArray(p[0])) {
									for (var k in p) {
										if (p.hasOwnProperty(k)) {
											var pp = p[k];
											if (typeof x1 === "undefined")
												x1 = pp[0];
											else if (x1 > pp[0])
												x1 = pp[0];
											if (typeof x2 === "undefined")
												x2 = pp[0];
											else if (x2 < pp[0])
												x2 = pp[0];
											if (typeof y1 === "undefined")
												y1 = pp[1];
											else if (y1 > pp[1])
												y1 = pp[1];
											if (typeof y2 === "undefined")
												y2 = pp[1];
											else if (y2 < pp[1])
												y2 = pp[1];
										}
									}
								}
								else {
									if (typeof x1 === "undefined")
										x1 = p[0];
									else if (x1 > p[0])
										x1 = p[0];
									if (typeof x2 === "undefined")
										x2 = p[0];
									else if (x2 < p[0])
										x2 = p[0];
									if (typeof y1 === "undefined")
										y1 = p[1];
									else if (y1 > p[1])
										y1 = p[1];
									if (typeof y2 === "undefined")
										y2 = p[1];
									else if (y2 < p[1])
										y2 = p[1];
								}
							}
						}
					}
				}
			}
		}
	}
	
	return {x1: x1, y1: y1, x2: x2, y2: y2};
}

var is_bbox = function (bbox) {
	if (typeof bbox === "undefined")
		return false;
	if (typeof bbox.x1 === "undefined")
		return false;
	if (typeof bbox.y1 === "undefined")
		return false;
	if (typeof bbox.x2 === "undefined")
		return false;
	if (typeof bbox.y2 === "undefined")
		return false;		
	return true;
}

var show_map = function (geojson) {
	if (geojson.length <= 0)
		return;
		
	try {
		var gobj = JSON.parse(geojson);
		var bb = get_bounding_box(gobj);
		if (is_bbox(bb)) {
			var mx = (bb.x1 + bb.x2) / 2;
			var my = (bb.y1 + bb.y2) / 2;

			$("#" + map_id).height(map_height);
			
			map = L.map(map_id).setView([my, mx], map_zoom_level);
			
			map.addControl(new L.Control.Fullscreen());

			L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 19,
				attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
			}).addTo(map);
			
			L.geoJSON(gobj).addTo(map);
			
			// Remove Leaflet attribution
			//$('.leaflet-control-attribution').hide();
			map.attributionControl.setPrefix(false);
		}
	}
	catch (e) {
		console.log(e);
	}
}



