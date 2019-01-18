var llat = 0, llon = 0, clat = 0, clon = 0;
var timer;

$("#frmVid").on("submit", function(e) {
	e.preventDefault();
	$("#vid").attr("readonly", true);
	$("#monitor").show();
	check();

	if (!timer)
		timer = setInterval(function() { check(); }, 180000);
});

function check()
{
	$.ajax({
		cache: false,
		url: "get.php?t=1",
		data: { vid: $("#vid").val() },
		success: function(data) {
			$("#callsign").html(data.callsign);

			clat = data.latitude;
			clon = data.longitude;
			$("#current").html("Lat: " + clat + "<br>Lon: " + clon);
			$("#last").html("Lat: " + llat + "<br>Lon: " + llon);

			var status = $("#status");
			if (llat == clat && llon == clon)
			{
				status.attr("class", "alert alert-danger");
				status.html("Coordinates seems to match. <b>Disconnect and restart your IvAp!</b>");
			}
			else
			{
				status.attr("class", "alert alert-success");
				status.html("Coordinates are different. All is OK.");
			}

			llat = clat;
			llon = clon;
		}
	});
}