var activeSession = 0;

function getFlight(id)
{
	$.ajax({
		cache: false,
		url: "api/get_tracker.php",
		data: { id: id, with_path: 1 },
		success: function(data) {
			clearFltRoute();
			
			var html = '';
			html += '<h2>' + data.callsign + '</h2>';
			
			html += '<h4>Member details:</h4>';
			html += '<table class="table table-hover table-sm">';
			html += '<tr>';
			html += '<th>VID:</th>';
			html += '<td><a href="https://www.ivao.aero/Member.aspx?ID=' + data.vid + '" target="_blank">' + data.vid + '</a></td>';
			html += '</tr>';
			html += '<tr>';
			html += '<th>Rating:</th>';
			html += '<td><img src="img/ratings/' + data.rating + '.gif" title="' + data.rating + '" class="img-fluid"></td>';
			html += '</tr>';
			html += '<tr>';
			html += '<th>Software:</th>';
			html += '<td>' + data.software + '</td>';
			html += '</tr>';
			html += '</table>';
			
			$('#txtFlightData').html(html);
			
			for (var i = 0; i < data.paths.length; i++)
			{
				if (data.paths.length > i + 1)
				{
					var color = "";
					if (data.paths[i].altitude <= 2100)
						color = "#FFE700";
					else if (data.paths[i].altitude <= 6000)
						color = "#FF8C00";
					else if (data.paths[i].altitude <= 12000)
						color = "#00FF00";
					else if (data.paths[i].altitude <= 18000)
						color = "#00FFFF";
					else if (data.paths[i].altitude <= 24000)
						color = "#3D00FF";
					else if (data.paths[i].altitude <= 32000)
						color = "#FF00FF";
					else
						color = "#FF0033";
				
					fltRoute.push(L.polyline([[data.paths[i].latitude, data.paths[i].longitude], [data.paths[i + 1].latitude, data.paths[i + 1].longitude]], {color: color, weight: 2}).addTo(map));
				}
			}
		
			console.log('Flight data added:', data);
		},
		error: function() {
			alert("Failed to load flight route.");
		}
	});
}

function clearMapOnline()
{
	$.each(onlineElems, function() {
		map.removeLayer(this);
	});
	console.log("Online map elements cleared.");
}

function tabOnlineFlight(active)
{
	if (active)
	{
		$('#sidebar').removeClass('collapsed');
		$('#info').removeClass('active');
		$('#online_flight').addClass('active');
	}
	else
	{
		$('#sidebar').addClass('collapsed');
		$('#info').removeClass('active');
		$('#online_flight').removeClass('active');
	}
}

function loadOnlines()
{
	$.ajax({
		cache: false,
		url: "api/get_online.php",
		success: function(data) {
			clearMapOnline();

			$.each(data, function() {
				if (this.type == "PILOT" && this.on_ground == 0 && (this.fp_departure.length > 0 && this.fp_destination.length > 0))
				{	
					onlineElems.push(
						L.marker([this.latitude, this.longitude], {
							icon: iconPlane,
							rotationAngle: this.heading,
							rotationOrigin: 'center'
						}).on('click', function(e) {
							toggleRoute(this.id);
						}, this)
						.addTo(map).bindTooltip('<b>' + this.callsign + '</b>')
					);
					
					console.log('Flight marker added:', this);
				}
			});
		}
	});
}

function toggleRoute(id)
{
	if (activeSession == id)
	{
		clearFltRoute();
		activeSession = 0;
		tabOnlineFlight(false);
	}
	else
	{
		getFlight(id);
		activeSession = id;
		tabOnlineFlight(true);
	}	
}

function autoUpdate()
{
	loadOnlines();
	
	if (activeSession > 0)
	{
		getFlight(activeSession);
	}
	
	setTimeout(autoUpdate, 30000);
}

$(document).ready(function() {
	autoUpdate();
});
