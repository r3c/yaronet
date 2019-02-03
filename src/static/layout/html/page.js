
yn = window.yn || {};

/*
** Wrap a callback to defer execution and prevent multiple triggering.
** delay:		delay before executing action in milliseconds
** callback:	original callback
** return:		wrapped callback
*/
yn.defer = function (delay, callback) {
	var handle;

	return function () {
		var applyArguments = arguments;
		var applyThis = this;

		if (handle !== undefined)
			clearTimeout(handle);

		handle = setTimeout(function () {
			callback.apply(applyThis, applyArguments);
		}, delay);
	};
};

/*
** Build callback displaying success or failure message from JQuery's response
** object, near given element.
** handle:	source handle
** success:	true to display success message, false otherwise
** return:	message display callback
*/
yn.feedback = function (handle, success) {
	var types = {
		'^(application|text)/json': function (text) {
			var json = $.parseJSON(text);
			var list = $('<ul>');

			if (typeof json !== 'object' || typeof json.alerts !== 'object')
				return;

			for (var i = 0; i < json.alerts.length; ++i)
				list.append($('<li>').text(json.alerts[i]));

			return list;
		},
		'^text/html': function (text) {
			return $('<div>').html(text);
		}
	};

	return function (jqr) {
		var alerts;
		var message;
		var type;

		type = jqr.getResponseHeader('Content-Type').toLowerCase();

		for (var pattern in types) {
			var regexp = new RegExp(pattern);

			if (!regexp.test(type))
				continue;

			try {
				message = types[pattern](jqr.responseText);

				break;
			}
			catch (e) {
			}
		}

		if (typeof message !== 'object')
			message = $('<div>').text('Error: ' + jqr.statusText);

		yn.notice(handle, success, message);
	};
};

/*
** Wrap promise object inside CSS loading enable/disable.
** promise:	promise object
** handle:	loading handle
** return:	promise
*/
yn.load = function (promise, handle) {
	handle
		.addClass('loading')
		.prop('disabled', true);

	return promise
		.always(function () {
			handle
				.prop('disabled', false)
				.removeClass('loading');
		});
};

/*
** Wait for promise result and insert as HTML body or display feedback message.
** promise:		promise object
** handle:		loading handle
** container:	target element to insert HTML into
** callback:	post-processing callback
** return:		false
*/
yn.load_html = function (promise, handle, container, callback) {
	yn.load(promise, handle)
		.done(function (html) {
			container.html(html);

			if (callback !== undefined)
				callback(container);
		})
		.fail(yn.feedback(handle));

	return false;
};

/*
** Display success or failure message near given element.
** handle:	source element handle for positioning
** success:	true to display success message, false otherwise
** message: jQuery DOM element or message text
*/
yn.notice = function (handle, success, message) {
	var container = $('<div class="notice">')
		.append(typeof message !== 'object' ? $('<div>').text(message) : message)
		.appendTo('body')
		.position({
			collision: 'fit',
			at: 'center center',
			of: handle,
			my: 'left top'
		})
		.addClass(success ? 'notice-ok' : 'notice-fail')
		.css('max-width', '320px')
		.css('zIndex', 9999)
		.delay(2000)
		.fadeOut(1000, function () {
			container.remove();
		});
};

/*
** Return number of seconds elapsed since epoch.
** return:	timestamp
*/
yn.now = function () {
	return Math.floor(new Date().getTime() / 1000);
};

/*
** Repeat action with interval depending on window focus state.
** resolution:	timer resolution in milliseconds
** high:		high frequency interval (when window has focus) in seconds
** low:			low frequency interval (when window doesn't have focus) in seconds
** callback:	action callback (elapsed), timer is stopped if "false" is returned
** return:		handle from setInterval call
*/
yn.pulse = function (resolution, high, low, callback) {
	var handle;
	var interval = high;
	var start = yn.now();
	var timeout = start + interval;

	$(window)
		.on('focus', function () { interval = high; })
		.on('blur', function () { interval = low; });

	handle = setInterval(function () {
		if (!interval)
			return;

		var current = yn.now();
		var next = current + interval;

		if (current >= timeout) {
			if (callback(current - start) === false)
				clearInterval(handle);

			timeout = next;
		}

		timeout = Math.min(next, timeout);
	}, resolution);

	return handle;
};

/*
** Submit form adding double-send protection system, and optionally replacing
** regular HTTP request by Ajax request.
** form:		target form
** handle		event origin handle
** complete:	optional completion callback, switch to Ajax request if defined
** return:		true if event should continue to propagate, false otherwise
*/
yn.submit = function (form, handle, complete) {
	if (form.data('lock'))
		return false;

	handle
		.addClass('loading')
		.prop('disabled', true);

	form.data('lock', true);

	if (complete === undefined)
		return true;

	// Disable Ajax-based submission if FormData is not available
	if (window.FormData === undefined)
		return true;

	var settings = {
		contentType: false,
		data: new FormData(form.get(0)),
		processData: false,
		type: 'POST'
	};

	$.ajax(form.attr('action'), settings)
		.done(complete)
		.fail(function (jqr) {
			handle
				.removeClass('loading')
				.prop('disabled', false);

			form.data('lock', false);

			yn.feedback(handle || form)(jqr);
		});

	return false;
};

/*
** Create inner window on given handle.
** handle:	target handle, used to define window location
** title:	window title
** width:	window width in pixels
** height:	window height in pixels
** return:	window object
*/
yn.window = function (handle, title, width, height) {
	var buttonClose = $('<input class="close gly-button" type="button">');

	var container = $('<aside class="window">')
		.append($('<div class="form-thick glyph10 window-header">').text(title).append(buttonClose))
		.append($('<div class="window-inner">'))
		.width(Math.min(width, $(document).width()))
		.height(Math.min(height, $(document).height()))
		.appendTo('body')
		.position({
			collision: 'fit',
			at: 'center bottom',
			of: handle,
			my: 'center top'
		})
		.draggable({
			containment: 'window',
			handle: '.window-header'
		})
		.resizable({
			containment: 'document'
		})
		.on('mousedown', function () {
			var index = 1;

			$('aside.window').each(function () {
				index = Math.max((parseInt($(this).css('zIndex')) || 0) + 1, index);
			});

			$(this).css('zIndex', index);
		})
		.hide()
		.fadeIn(250);

	var close = function () {
		container.fadeOut(250, function () {
			container.remove();
		});
	};

	buttonClose.on('click keydown', close);
	container.triggerHandler('mousedown');

	return {
		close: close,
		inner: container.find('.window-inner')
	};
};

yn.urls = yn.urls || {};

$(function () {
	// Enable tooltips on links with title
	if ($.prototype.tooltip !== undefined) {
		$(document).tooltip
			({
				items: 'a.bind-tooltip',
				position: {
					my: 'center bottom-16',
					at: 'center top',
					using: function (position, feedback) {
						$('<div>')
							.addClass('arrow')
							.addClass(feedback.vertical)
							.css('left', ((feedback.target.left - feedback.element.left + feedback.target.width / 2) / feedback.element.width * 100) + '%')
							.appendTo(this);

						$(this).css(position);
					},
					within: document.body
				},
				show: {
					delay: 250
				}
			});
	}

	// Attach global events
	$(document)
		// Form submit function to all forms except "raw" ones.
		.on('submit', 'form:not(.bind-raw)', function () {
			var question = $(this).data('confirm');

			if (question && !confirm(question))
				return false;

			return yn.submit($(this), $(this).find('input[type=submit]'));
		})

		// Display message window on click
		.on('click', 'a[href].bind-message', function (event) {
			if (event.which !== 1)
				return true;

			var button = $('#yn-message');
			var source = $(this);
			var window = yn.window(source, button.text(), 640, 480);

			var delete_hide_bind = function (container) {
				container
					.find('form')
					.on('submit', function () {
						return yn.submit($(this), $(this).find('input[type=submit]'), function (html) {
							container.html(html);

							delete_hide_bind(container);
							list_bind(container);
						});
					})
					.end()
					.find('form .back')
					.on('click keydown', function () {
						return list_load($(this).attr('href'), $(this));
					})
					.end();
			};

			var delete_hide_load = function (url, handle, question) {
				return confirm(question) && yn.load_html($.post(url), handle, window.inner, function (container) {
					delete_hide_bind(container);
					list_bind(container);
				});
			};

			var edit_bind = function (container) {
				container
					.find('form')
					.on('submit', function () {
						return yn.submit($(this), $(this).find('input[type=submit]'), function (html) {
							container.html(html);

							edit_bind(container);
							list_bind(container);
						});
					})
					.end()
					.find('form .back')
					.on('click keydown', function () {
						return list_load($(this).attr('href'), $(this));
					})
					.end();

				yn.markup(container);
			};

			var edit_load = function (url, handle) {
				return yn.load_html($.get(url), handle, window.inner, edit_bind);
			};

			var list_bind = function (container, scrollTo) {
				if (scrollTo !== undefined)
					container.scrollTo(scrollTo, 500);

				container
					.find('.bind-control')
					.find('.delete, .hide')
					.on('click keydown', function () {
						return delete_hide_load($(this).attr('href'), $(this), $(this).data('confirm'));
					})
					.end()
					.find('.first')
					.on('click keydown', function () {
						return list_load($(this).attr('href'), $(this), '0%');
					})
					.end()
					.find('.edit, .message, .reply, .reply-all')
					.on('click keydown', function () {
						return edit_load($(this).attr('href'), $(this));
					})
					.end()
					.find('.prev')
					.on('click keydown', function () {
						return list_load($(this).attr('href'), $(this), '100%');
					})
					.end()
					.find('form')
					.on('submit', function () {
						return list_load($(this).attr('action') + '?' + $(this).serialize(), $(this).find('input[type=submit]'));
					})
					.end();

				yn.markup(container);
			};

			var list_load = function (url, handle, scrollTo) {
				return yn.load_html($.get(url), handle, window.inner, function (container) {
					list_bind(container, scrollTo);
				});
			};

			// Load edit frame (on click on a login) or messages list (otherwise)
			if (button.attr('id') !== source.attr('id'))
				return edit_load(source.data('url-edit') || source.attr('href'), source);
			else
				return list_load(button.data('url-list'), source);
		});

	// Display memo on click
	$('#yn-memo').on('click', function (event) {
		if (event.which !== 1)
			return true;

		var button = $(this);
		var window = yn.window(button, button.text(), 480, 320);

		var edit_bind = function (container) {
			container
				.find('form')
				.on('submit', function () {
					return yn.submit($(this), $(this).find('input[type=submit]'), function (html) {
						edit_bind(container.html(html));
					});
				})
				.end()
				.find('.bind-control .back')
				.off('click keydown')
				.on('click keydown', function () {
					return view_load($(this));
				})
				.end();
		};

		var edit_load = function (handle) {
			return yn.load_html($.get(button.data('url-edit')), handle, window.inner, edit_bind);
		};

		var view_bind = function (container) {
			container
				.find('.bind-control .edit')
				.off('click keydown')
				.on('click keydown', function () {
					return edit_load($(this));
				});
		};

		var view_load = function (handle) {
			return yn.load_html($.get(button.data('url-view')), handle, window.inner, view_bind);
		};

		return view_load($(this));
	});

	// Expand menu on click
	$('#yn-menu').on('click', function (event) {
		if (event.which !== 1)
			return true;

		$(this)
			.removeClass('menu-notify')
			.toggleClass('menu-open');
	});

	// Reset message notification and open window on click
	$('#yn-message').on('click', function (event) {
		$(this).removeClass('message-notify');
	});

	// Enable popup windows on bookmark link
	$('.bookmark1').on('click', function () {
		window.open($(this).attr('href') + '?popup=1', $(this).text(), 'scrollbars=1,resizable=1,width=600,height=500');

		return false;
	});

	// Reload activities periodically, stop after 3 hours
	yn.pulse(5000, 30, 4 * 60, function (elapsed) {
		var element = $('#yn-menu-users');

		element.load(element.data('url-pulse'));

		return elapsed < 3 * 60 * 60;
	});
});
