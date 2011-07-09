(function() { 
	$('a[href^="#"]').live('click', function() {
		var elem = $('#' + $(this).attr('href').split('#')[1]);
		var prevColor = elem.css('backgroundColor');

		elem.css('backgroundColor', '#FFFF88');
		setTimeout(function() {
			elem.animate({backgroundColor: prevColor}, 15000);
		}, 5000);
	});
})();

$(document).ready(function() {
	var parts = window.location.href.split('#');
	if(parts.length < 2) return;

	var elem = $('#' + parts[1]);
	var prevColor = elem.css('backgroundColor');

	elem.css('backgroundColor', '#FFFF88');
	setTimeout(function() {
		elem.animate({backgroundColor: prevColor}, 15000);
	}, 5000);
});
