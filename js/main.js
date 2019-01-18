$(document).ajaxStart(function() {
	$(".loader").show();
});
$(document).ajaxComplete(function() {
	$(".loader").hide();
	$('[data-toggle="tooltip"]').tooltip();
});