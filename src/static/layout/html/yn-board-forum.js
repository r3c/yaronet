
$(function () {
	$('.blocks .link').on('click', function (event) {
		if (event.which !== 1 || event.target.nodeName === 'A')
			return true;

		window.location.href = $(this).find('a.name').attr('href');

		return false;
	});
});
