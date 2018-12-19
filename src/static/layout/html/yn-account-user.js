
$(function () {
	var input = $('form select[name=template]');
	var image = $('<img>');
	var path = $('link[href*=base\\.css], link[href*=base\\.less]').attr('href');

	$('<div class="preview">')
		.append(image)
		.appendTo(input.parent());

	input
		.on('change', function () {
			if (path) {
				var preview = $(this).val().replace(/^.*\./, '') + '/glyph/preview.png';

				image.attr('src', path.replace(/[^\/]*\/[^\/]*$/, preview));
			}
		})
		.triggerHandler('change');
});
