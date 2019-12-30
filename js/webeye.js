var activeSession = 0;

function getFlight(id)
{
	$.ajax({
		cache: false,
		url: "api/get_opsdata.php",
		data: { id: id },
		success: function(data) {
			clearFltRoute();
			var latlons = [];
			
			$.each(data, function() {
				latlon = [this.latitude, this.longitude];
				latlons.push(latlon)
			});
			
			fltRoute.push(L.polyline(latlons, {color: 'red'}).addTo(map));
			
			
			$.ajax({
				cache: false,
				url: "api/get_tracker.php",
				data: { id: id },
				success: function(data) {
					var html = '';
					html += '<h2>' + data.callsign + '</h2>';
					
					$('#txtFlightData').html(html);
				}
			});
			
			console.log('Flight route added:', data);
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
					var rule = '';
					switch (this.fp_rule)
					{
						case 'I':
							rule = 'IFR';
							break;
						case 'V':
							rule = 'VFR';
							break;
						default:
							rule = this.fp_rule;
							break;
					}
					
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
