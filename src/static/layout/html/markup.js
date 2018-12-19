
yn = window.yn || {};

yn.markup = function (frame) {
	// Enable HighlightJS on code blocks
	frame.find('.markup code').each(function () {
		if (window.hljs !== undefined)
			hljs.highlightBlock(this);
	});

	// Enable image zoom on compatible links
	if ($.prototype.fancybox !== undefined) {
		frame.find('.markup a.zoom').fancybox({
			helpers: {
				overlay: {
					locked: false
				}
			},
			live: false,
			minHeight: 16,
			minWidth: 16,
			padding: 4
		});
	}

	// Expand or collapse boxes on click
	frame.find('.markup .box .box-head').on('click keydown', function () {
		$(this).next('.box-body').slideToggle(200);
	});

	// Switch "not safe for work" state on click
	frame.find('.markup .nsfw .nsfw-head').on('click keydown', function () {
		var head = $(this);
		var body = head.next('.nsfw-body');

		if (body.is(':visible') || confirm(head.data('confirm')))
			body.slideToggle(200);
	});

	// Show poll and capture form submission
	frame.find('.markup .poll').each(function () {
		var poll = $(this);

		poll.load(poll.data('url'), function () {
			$(this).find('form').on('submit', function () {
				var form = $(this);

				return yn.submit(form, form.find('input[type=submit]'), function (html) {
					form.closest('.poll').html(html);
				});
			});
		});
	});

	// Enable post popup on ref tags
	frame.find('.markup .ref').on('click keydown', function () {
		var container = $('<div>')
			.css('position', 'absolute')
			.offset($(this).offset())
			.appendTo($('body'));

		return yn.markup_peek($(this), container, undefined, {});
	});

	// Switch spoiler visibility on click
	frame.find('.markup .spoil').on('click keydown', function () {
		$(this).toggleClass('hover');
	});
};

yn.markup_peek = function (handle, container, refresh, strings) {
	var hide = function () {
		container.remove();
	};

	var invoke = function (url, handle, callback) {
		yn.load($.get(url), handle)
			.done(callback)
			.fail(yn.feedback(handle));

		return false;
	};

	var show = function (url, handle) {
		yn.load_html($.get(url), handle, container, function (container) {
			yn.markup(container.slideDown(250));

			var form = container.find('form');
			var input = form.find('[data-draft]');

			container.find('a.peek-close').on('click', function () {
				container.slideUp(250, hide);

				return false;
			});

			container.find('a.peek-drop').on('click', function () {
				return confirm(strings.drop) && invoke($(this).attr('href'), $(this), function () {
					hide();
					refresh();
				});
			});

			container.find('a.peek-mark, a.peek-read').on('click', function () {
				return invoke($(this).attr('href'), $(this), function () {
					hide();
					refresh();
				});
			});

			container.find('a.peek-next, a.peek-previous').on('click', function () {
				return show($(this).attr('href'), $(this));
			});

			container.find('a.peek-quote').on('click', function () {
				invoke($(this).attr('href'), $(this), function (html) {
					var text = input.val();

					input.val((text !== '' ? text + "\n" : '') + $('<textarea>').html(html).val().trim() + "\n");

					form.slideDown(250, function () {
						input.focus();
					});
				});

				return false;
			});

			container.find('a.peek-reply').on('click', function () {
				form.slideToggle(250, function () {
					form.find('textarea').focus();
				});

				return false;
			});

			form.on('submit', function () {
				return yn.submit($(this), $(this).find('input[type=submit]'), function () {
					yn.controlFormDraftDetach(input);

					refresh();
				});
			});
		});

		return false;
	};

	container.hide();

	return show(handle.data('url-peek'), handle);
};

$(function () {
	yn.markup($(document));
});
