var elements = [];
var airportIcon = L.divIcon({className: 'fas fa-map-marker-alt'});
var map = L.map('map', {
	center: getMapCenter(),
	zoom: getMapZoom(),
	worldCopyJump: true,
	minZoom: 3,
	preferCanvas: false
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
	return [Cookies.get("lat"), Cookies.get("lon")];
}

function getMapZoom()
{
	if (!Cookies.get("zoom"))
		Cookies.set("zoom", 5);
	return Cookies.get("zoom");
}

function mapStateSave(e)
{
	Cookies.set("zoom", e.target.getZoom());
	Cookies.set("lat", e.target.getCenter().lat);
	Cookies.set("lon", e.target.getCenter().lng);
}

function clearMap()
{
	$.each(elements, function() {
		map.removeLayer(this);
	});
}

function getFlight(id)
{
	$.ajax({
		cache: false,
		url: "get.php?t=0&cl=2",
		data: { id: id },
		success: function(data) {
			var c = '<h4>Member details:</h4>';
			c += '<table class="table table-hover table-sm">';
			c += '<th>VID:</th><td><a href="https://www.ivao.aero/Member.aspx?ID=' + data.vid + '" target="_blank">' + data.vid + '</a></td></tr>';
			c += '<th>Rating:</th><td><img src="img/ratings/' + data.rating + '.gif" title="' + data.rating + '" class="img-fluid"></td></tr>';
			c += '</table>';

			c += '<h4>Session data:</h4>';
			c += '<table class="table table-striped table-sm">';
			c += '<th>Callsign:</th><td>' + data.callsign + '</td></tr>';
			c += '<th>Connected:</th><td>' + data.connected_at + '</td></tr>';

			if (!data.online)
			{
				c += '<th>Disconnected:</th><td>' + data.disconnected_at + '</td></tr>';
				$("#txtPilotId").html(data.id);
			}
			else
				$("#txtPilotId").html(data.id + ' (online)');
			c += '<th>Duration:</th><td>' + data.duration + '</td></tr>';

			c += '<th>Client:</th><td>' + data.software + '</td></tr>';
			c += '<th>Simulator:</th><td>' + data.sim_type + '</td></tr>';
			c += '</table>';

			var eobt = data.fp_deptime.padStart(4, '0');
			var eet = Math.floor(data.fp_eet / 60).toString().padStart(2, '0') + (data.fp_eet % 60).toString().padStart(2, '0');
			var endurance = Math.floor(data.fp_endurance / 60).toString().padStart(2, '0') + (data.fp_endurance % 60).toString().padStart(2, '0');

			var atcfpl = '(FPL-' + data.callsign + '-' + data.fp_rule + data.fp_type + '<br>' + 
				'-' + data.fp_aircraft + '<br>' + 
				'-' + data.fp_departure + eobt + '<br>' + 
				'-' + data.fp_route + '<br>' +
				'-' + data.fp_destination + eet + ' ' + data.fp_alternate + ' ' + data.fp_alternate2 + '<br>' +
				'-' + data.fp_item18 + '<br>' +
				'E/' + endurance + ' P/' + data.fp_pob + ')';

			c += '<h4>ICAO flightplan:</h4>';
			c += '<p><code>' + atcfpl + '</code></p>';

			c += '<em>Last tracked at ' + data.updated_at + '</em>';

			$("#contentFlight").html(c);
			$("#modalFlight").modal("show");
		}
	});
}

function getATC(id)
{
	$.ajax({
		cache: false,
		url: "get.php?t=0&cl=1",
		data: { id: id },
		success: function(data) {
			var c = '<h4>Member details:</h4>';
			c += '<table class="table table-hover table-sm">';
			c += '<th>VID:</th><td><a href="https://www.ivao.aero/Member.aspx?ID=' + data.vid + '" target="_blank">' + data.vid + '</a></td></tr>';
			c += '<th>Rating:</th><td><img src="img/ratings/' + data.rating + '.gif" title="' + data.rating + '" class="img-fluid"></td></tr>';
			c += '</table>';

			c += '<h4>Session data:</h4>';
			c += '<table class="table table-striped table-sm">';
			c += '<th>Callsign:</th><td>' + data.callsign + '</td></tr>';
			c += '<th>Connected:</th><td>' + data.connected_at + '</td></tr>';
			if (!data.online)
			{
				c += '<th>Disconnected:</th><td>' + data.disconnected_at + '</td></tr>';
				$("#txtATCId").html(data.id);
			}
			else
				$("#txtATCId").html(data.id + ' (online)');
			c += '<th>Duration:</th><td>' + data.duration + '</td></tr>';

			c += '<th>Client:</th><td>' + data.software + '</td></tr>';
			c += '</table>';

			c += '<h4>Ops data (last recorded):</h4>';
			c += '<table class="table table-striped table-sm">';
			c += '<tr><th>Frequency:</th><td>' + data.frequency.toString().padEnd(7, '0') + ' MHz</td></tr>';
			c += '<tr><th>Latitude:</th><td>' + data.latitude + '°</td></tr>';
			c += '<tr><th>Longitude:</th><td>' + data.longitude + '°</td></tr>';
			c += '<tr><th>Radar range:</th><td>' + data.radar_range + ' nm</td></tr>';
			c += '<tr><th>ATIS:</th><td><code>' + data.atis.replace(/(\r\n\t|\n|\r\t|\\n)/gm, "<br>") + '</code></td></tr>';
			c += '</table>';

			c += '<em>Last tracked at ' + data.updated_at + '</em>';

			$("#contentATC").html(c);

			$("#modalATC").modal("show");
		}
	});
}

function loadSessions(vid = 0, callsign = '', cl = 0)
{
	clearMap();
	$('[name="vid"]').val(vid);
	$('[name="client"]').val(cl);

	// if (vid == 540147)
	// {
	// 	alert('You must not search for the BOSS!');
	// 	return;
	// }

	$.ajax({
		cache: false,
		url: "get.php?t=0",
		data: { vid: vid, cs: callsign, cl: cl },
		success: function(data) {
			var tbl = '<table class="table table-striped table-sm table-hover">';
			tbl += '<thead>';
			tbl += '<tr>';
			tbl += '<th></th>';
			tbl += '<th>Callsign</th>';
			tbl += '<th>Date</th>';
			tbl += '</tr>';
			tbl += '</thead>';
			tbl += '<tbody>';

			var twr = false, gnd = false, app = false, del = false;

			$.each(data.sessions, function() {
				if (this.online)
					tbl += '<tr class="table-success">';
				else
					tbl += '<tr>';

				if (this.type === "PILOT")
				{
					tbl += '<td><i class="fas fa-paper-plane" title="Flight" data-toggle="tooltip"></i></td>';
					tbl += '<td><a href="javascript:void(0)" onclick="getFlight(' + this.id + ')">' + this.callsign + '</a></td>';
				}
				if (this.type === "ATC")
				{
					tbl += '<td><i class="fas fa-broadcast-tower" title="ATC" data-toggle="tooltip"></i></td>';
					tbl += '<td><a href="javascript:void(0)" onclick="getATC(' + this.id + ')">' + this.callsign + '</a></td>';
				}
				tbl += '<td>' + this.connected_at + '</td>';
				tbl += '</tr>';

				// if (this.type === "ATC")
				// {
				// 	if (this.callsign.endsWith('_GND') && !gnd)
				// 	{
				// 		gnd = true;
				// 		console.log("Adding GND: ", this);
				// 		elements.push(
				// 			L.circle([this.latitude, this.longitude], 12000, {
				// 				weight: 1,
				// 				color: "#ff0",
				// 				opacity: 1,
				// 				fillOpacity: 0.5
				// 			}).addTo(map)
				// 		);
				// 	}
				// 	if (this.callsign.endsWith('_TWR') && !twr)
				// 	{
				// 		twr = true;
				// 		console.log("Adding TWR: ", this);
				// 		elements.push(
				// 			L.circle([this.latitude, this.longitude], 20000, {
				// 				weight: 1,
				// 				color: "#ff6666",
				// 				opacity: 0.5,
				// 				fillOpacity: 0.3
				// 			}).addTo(map)
				// 		);
				// 	}
				// 	if (this.callsign.endsWith('_APP') && !app)
				// 	{
				// 		app = true;
				// 		console.log("Adding APP: ", this);
				// 		elements.push(
				// 			L.circle([this.latitude, this.longitude], 40000, {
				// 				weight: 1,
				// 				color: "#00f",
				// 				opacity: 0.4,
				// 				fillOpacity: 0.2
				// 			}).addTo(map)
				// 		);
				// 	}
				// }

				if (this.type === "PILOT")
				{
					if (this.destination && this.departure)
					{
						if (this.destination.lat == this.departure.lat && this.destination.lon == this.departure.lon)
							console.log("Dep/dest matches, skipping flight: ", this);
						else
						{
							console.log("Adding flight: ", this);

							elements.push(
								L.Polyline.Arc([this.destination.lat, this.destination.lon], [this.departure.lat, this.departure.lon], {
									color: 'blue',
									weight: 1,
									opacity: 0.3,
									vertices: 200
								}).addTo(map)
							);
						}
					}
					else
						console.log("No airport coordinates, skipping flight: ", this);
				}
			});

			tbl += '</tbody>';
			tbl += '</table>';
			$("#tblSessions").html(tbl);

			$.each(data.airports, function() {
				console.log("Adding airport marker: ", this);

				elements.push(
					L.marker([this.lat, this.lon], {
						icon: airportIcon,
						title: this.icao
					}).addTo(map)
						.bindPopup(
							"<b>" + this.icao + "</b><br>"
							+ this.name + "<br><br>"
							+ "Arrivals: " + this.arrivals + "<br>"
							+ "Departures: " + this.departures
						)
				);
			});
		}
	});
}

$("#frmSearch").on('submit', function(e) {
	e.preventDefault();
	loadSessions($('[name="vid"]').val(), $('[name="callsign"]').val(), $('[name="client"]').val());
});

$(document).ready(function() {
	// loadSessions(0, 'EDDM%');
})