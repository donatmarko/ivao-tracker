function loadOnlines()
{
	$.ajax({
		cache: false,
		url: "get.php?t=1",
		success: function(data) {
			clearMapOnline();

			$.each(data, function() {
				if (this.type === "PILOT" && !this.on_ground && (this.fp_departure.length > 0 && this.fp_destination.length > 0))
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
						}).on('click', function() { }).addTo(map)
					);
				}
			});
		}
	});
}