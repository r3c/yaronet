
$(function () {
	$('.forums .forum').on('click', function () {
		window.location.href = $(this).find('a').attr('href');

		return false;
	});
});
