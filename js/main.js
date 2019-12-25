$(document).ajaxStart(function() {
	$(".loader").show();
});
$(document).ajaxComplete(function() {
	$(".loader").hide();
	$('[data-toggle="tooltip"]').tooltip();
});

var iconPlane = L.icon({
	iconUrl: 'img/plane/plane24x24.png',
	iconSize: [24, 24]
});
var onlineElems = [];
var elements = [];
var airportIcon = L.divIcon({className: 'fas fa-map-marker-alt'});
var map = L.map('map', {
	center: getMapCenter(),
	zoom: getMapZoom(),
	worldCopyJump: true,
	minZoom: 3,
});
map.on('moveend', mapStateSave);
map.on('zoomend', mapStateSave);
L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', {
 	attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="http://cartodb.com/attributions">CartoDB</a>'
}).addTo(map);
var sidebar = L.control.sidebar('sidebar').addTo(map);

function getMapCenter()
{
	if (!Cookies.get("lat") || !Cookies.get("lon"))
	{
		Cookies.set("zoom", 5);
		Cookies.set("lat", 47);
		Cookies.set("lon", 15);
	}
	console.log("Map center loaded.");
	return [Cookies.get("lat"), Cookies.get("lon")];
}

function getMapZoom()
{
	if (!Cookies.get("zoom"))
		Cookies.set("zoom", 5);
	console.log("Map zoom loaded.");
	return Cookies.get("zoom");
}

function mapStateSave(e)
{
	Cookies.set("zoom", e.target.getZoom());
	Cookies.set("lat", e.target.getCenter().lat);
	Cookies.set("lon", e.target.getCenter().lng);
	console.log("Map settings saved.");
}