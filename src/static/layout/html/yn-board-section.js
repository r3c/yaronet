
$(function () {
	$('.bind-topic-new form').on('submit', function () {
		var form = $(this);

		yn.controlFormDraftDetach(form.find('[data-draft]'));

		return yn.submit(form, form.find('input[type=submit]'));
	});

	$('.section .new').on('click', function () {
		$.scrollTo('.bind-topic-new form', 500);

		return false;
	});

	$('.topics .link').on('click', function () {
		window.location.href = $(this).find('a.name').attr('href');

		return false;
	});

	$('.topics .link a').on('click', function (event) {
		event.stopPropagation();
	});
});
