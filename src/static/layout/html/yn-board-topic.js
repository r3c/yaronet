
yn = window.yn || {};
yn.boardTopicStrings = yn.boardTopicStrings || {};

$(function () {
	$('.bind-post-new form').on('submit', function () {
		var form = $(this);
		var handle = form.find('input[type=submit]');
		var url = form.data('url-cross');

		if (form.data('cross-bypass'))
			return yn.submit(form, handle);

		form.data('cross-bypass', true);

		yn.controlFormDraftDetach(form.find('[data-draft]'));

		yn.load($.get(url), handle)
			.done(function (html) {
				form
					.find('.bind-cross-disclaimer')
					.hide();

				form
					.find('.bind-cross-warning')
					.show()
					.find('a').data('url-peek', url);
			})
			.fail(function (jqr) {
				if (jqr.status !== 404)
					yn.feedback(handle)(jqr);
				else
					form.submit();
			});

		return false;
	});

	$('.post').on('click keydown', '.action .edit', function (event) {
		if (event.which !== 1)
			return true;

		var handle = $(this);
		var post = handle.closest('.post');
		var height = post.find('.bind-markup').height();

		var edit_bind = function (container) {
			var form = container.find('form');

			container
				.find('.bind-control .accept')
				.on('click keydown', function (event) {
					if (event.type === 'click' || event.which === 32) {
						return yn.submit(form, $(this), function (html) {
							container.html(html);

							edit_bind(container);
							view_bind(container);
						})
					}
				})
				.end()
				.find('.bind-control .cancel')
				.on('click keydown', function (event) {
					if (event.type === 'click' || event.which === 32) {
						return yn.load_html($.get($(this).attr('href')), $(this), container, view_bind);
					}
				})
				.end()
				.find('textarea')
				.height(Math.max(height, 60))
				.focus();
		};

		var view_bind = function (container) {
			yn.markup(container);
		};

		yn.load_html($.get(handle.data('url-edit')), handle, post, edit_bind);

		return false;
	});

	$('.post').on('click keydown', '.action .quote', function (event) {
		var handle = $(this);

		if (event.which !== 1)
			return true;

		yn.load($.get(handle.data('url-quote')), handle)
			.done(function (html) {
				var input = $('.bind-post-new form textarea[name="text"]');
				var text = input.val();

				input
					.val((text !== '' ? text + "\n" : '') + $('<textarea>').html(html).val().trim() + "\n")
					.focus();

				$.scrollTo('.bind-post-new form', 500);
			})
			.fail(yn.feedback(handle));

		return false;
	});

	$('.post').on('click keydown', '.action .report', function () {
		var report = $(this).data('url-report');
		var window = yn.window($(this), $(this).data('title'), 480, 240);

		var accept = $('<input class="button accept" type="submit">')
			.val(yn.boardTopicStrings.accept)
			.on('click keydown', function (event) {
				var data = { reason: window.inner.find('textarea').val() };
				var handle = $(this);

				yn.load_html($.post(report, data), handle, window.inner);
			});

		window.inner.html($('<div class="form-cascade form-thick">')
			.append($('<div class="field">')
				.append($('<p>').text(yn.boardTopicStrings.notice)))
			.append($('<div class="field large">')
				.append($('<div class="value">')
					.append($('<textarea>').attr('placeholder', yn.boardTopicStrings.placeholder))))
			.append($('<div class="control field glyph10">')
				.append(accept)));

		return false;
	});

	$('.post').on('click keydown', '.show', function () {
		var container = $('<div>')
			.css('position', 'absolute')
			.offset($(this).offset())
			.appendTo($('body'));

		return yn.markup_peek($(this), container, undefined, {});
	});

	$('.post').on('click keydown', '.from .ignore', function () {
		return confirm(yn.boardTopicStrings.ignore);
	});

	$('.post').on('mouseup', '.text', function () {
		var element = $(this);

		yn.controlFormCopyMark(element, element.closest('.content').find('.action .copy'), yn.boardTopicStrings.copy);

		return false;
	});
});
