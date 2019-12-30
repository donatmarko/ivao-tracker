function clearMap()
{
	$.each(elements, function() {
		map.removeLayer(this);
	});
	console.log("Map elements cleared.");
}

function getFlight(id)
{
	$.ajax({
		cache: false,
		url: "api/get_tracker.php?cl=2",
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
				$("#txtSessionId").html('Flight session #' + data.id);
			}
			else
				$("#txtSessionId").html('Flight session #' + data.id + ' (online)');

			c += '<th>Duration:</th><td>' + data.duration + '</td></tr>';
			c += '<th>Client (simulator):</th><td>' + data.software + ' (' + data.sim_type + ')</td></tr>';
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

			c += '<h4>Ops data (last recorded):</h4>';
			c += '<table class="table table-striped table-sm">';
			c += '<tr><th>Altitude:</th><td>' + data.altitude + ' ft</td></tr>';
			c += '<tr><th>Groundspeed:</th><td>' + data.groundspeed + ' kts</td></tr>';
			c += '<tr><th>Mode-A:</th><td>' + data.mode_a + '</td></tr>';
			c += '</table>';

			c += '<em>Last tracked at ' + data.last_tracked_at + '</em>';

			console.log("Flight data loaded: ", data);
			$("#contentSession").html(c);
			$("#modalSession").modal("show");
		},
		error: function() {
			alert("Failed to load flight data.");
		}
	});
}

function getATC(id)
{
	$.ajax({
		cache: false,
		url: "api/get_tracker.php?cl=1",
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
				$("#txtSessionId").html('ATC session #' + data.id);
			}
			else
				$("#txtSessionId").html('ATC session #' + data.id + ' (online)');

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

			c += '<em>Last tracked at ' + data.last_tracked_at + '</em>';

			console.log("ATC data loaded: ", data);
			$("#contentSession").html(c);
			$("#modalSession").modal("show");
		},
		error: function() {
			alert("Failed to load ATC data.");
		}
	});
}

function loadSessions(vid = 0, callsign = '', cl = 0)
{
	clearMap();
	$('[name="vid"]').val(vid);
	$('[name="client"]').val(cl);

	$.ajax({
		cache: false,
		url: "api/get_tracker.php",
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

			$.each(data.sessions, function() {
				tbl += this.online ? '<tr class="table-success">' : '<tr>';
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
	
});
