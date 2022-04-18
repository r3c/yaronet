
yn = window.yn || {};
yn.boardBookmarkStrings = yn.boardBookmarkStrings || {};

$(function () {
	var attach = function (sources, links) {
		sources.find('.bind-name').on('click', function (event) {
			if (!window.opener || location.href.indexOf('?popup=1') < 0)
				return true;

			window.opener.location = this.href;

			setTimeout(refresh, 2000);

			event.preventDefault();

			return false;
		});

		sources.find('.bind-show').on('click', function () {
			var parent = $(this).closest('.bind-topic');
			var handle = parent.find('.bind-name');
			var peek = parent.find('.bind-peek');

			if (peek.length < 1) {
				peek = $('<div>').hide().addClass('bind-peek').appendTo(parent);
			}

			return yn.markup_peek(handle, peek, refresh, yn.boardBookmarkStrings);
		});

		links.find('a.update').on('click', function () {
			refresh();

			return false;
		});
	};

	var browse = function (event) {
		// Don't handle event if a combo key was pressed
		if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey)
			return;

		// Disable browsing on textarea
		var tag = event.target.tagName.toLowerCase();

		if (tag === 'input' || tag === 'select' || tag === 'textarea')
			return;

		// Find and disable current selection
		var current = $('.bind-name.hl').first().parent();
		var replace = [];
		var peek = $('.peek:not([id])');

		switch (event.which) {
			case 32: // Space
				peek.find('.peek-read').focus().triggerHandler('click');

				break;

			case 37: // Arrow left
				if (peek.find('form textarea').is(':visible'))
					peek.find('.peek-reply').triggerHandler('click');
				else
					peek.find('.peek-close').focus().triggerHandler('click');

				break;

			case 38: // Arrow up
				if (peek.length > 0)
					peek.find('.peek-previous:visible').focus().triggerHandler('click');
				else {
					for (var parent = current; parent.length > 0 && replace.length === 0; parent = parent.parent())
						replace = parent.prev().find('.topic').last().parent();

					if (replace.length === 0)
						replace = $('.bind-name').last().parent();
				}

				break;

			case 39: // Arrow right
				if (peek.length > 0)
					peek.find('.peek-reply').focus().triggerHandler('click');
				else
					current.find('.bind-show').focus().triggerHandler('click');

				break;

			case 40: // Arrow down
				if (peek.length > 0)
					peek.find('.peek-next:visible').focus().triggerHandler('click');
				else {
					for (var parent = current; parent.length > 0 && replace.length === 0; parent = parent.parent())
						replace = parent.next().find('.topic').first().parent();

					if (replace.length === 0)
						replace = $('.bind-name').first().parent();
				}

				break;

			case 68: // D
				peek.find('.peek-drop').focus().triggerHandler('click');

				break;

			case 77: // M
				peek.find('.peek-mark').focus().triggerHandler('click');

				break;

			case 81: // Q
				peek.find('.peek-quote').focus().triggerHandler('click');

				break;

			default:
				return;
		}

		// Highlight current selection
		if (replace.length > 0) {
			current.find('a').removeClass('hl');
			replace.find('a').addClass('hl');
		}

		return false;
	};

	var refresh = function () {
		var target = $('#yn-bookmark-sources').addClass('refresh');
		var url = target.data('url-refresh');

		$.get(url)
			.always(function () {
				target
					.removeClass('refresh')
					.trigger('resize');
			})
			.done(function (html) {
				target.html(html);

				attach(target, $());
			})
			.fail(yn.feedback(target));
	};

	$(document).on('keydown', browse);

	attach($('#yn-bookmark-sources'), $('#yn-bookmark-links'));
});
