
yn = window.yn || {};

/*
** Enable completion feature on input field.
** From: https://jqueryui.com/autocomplete/#multiple-remote
*/
yn.controlFormComplete = function (target) {
	target.each(function () {
		var appendValue;
		var formInput = $(this);
		var peekValue;
		var userInput;

		// Configure multi-selection mode if enabled
		var limit = formInput.data('limit') || 10;
		var multiple = formInput.data('multiple');

		switch (multiple) {
			case "list":
			case "sort":
				var items = $('<ul class="selectable glyph10">')
					.insertAfter(formInput)
					.disableSelection();

				formInput.attr('type', 'hidden');

				appendValue = function (previous, current) {
					if (!current || current.length <= 0 || items.children('li').length >= limit)
						return current;

					var caption = $('<span class="label item">').text(current);
					var remove = $('<a class="icon delete" href="#">').on('click', function () {
						$(this).closest('li').remove();

						update();

						return false;
					});

					$('<li class="input-button handle">')
						.append(remove)
						.append(caption)
						.appendTo(items);

					update();

					return '';
				};

				peekValue = function (current) {
					return current;
				};

				var update = function () {
					var values = items
						.find('li')
						.map(function () {
							return $(this).find('.item').text();
						})
						.get()
						.join(',');

					formInput.val(values);
				};

				// Allow sorting values if requested
				if (multiple === "sort") {
					items
						.addClass('sortable')
						.sortable({
							placeholder: 'input-focus',
							update: update
						});
				}

				// Insert initial elements
				$.each(formInput.val().split(/\s*,\s*/), function () {
					appendValue('', this);
				});

				// Create new control for capturing user input
				userInput = $('<input type="text">')
					.on('keydown', function (event) {
						if (event.which !== 13)
							return;

						this.value = appendValue(this.value, this.value);

						event.preventDefault();

						return false;
					})
					.insertBefore(formInput);

				break;

			case "text":
				appendValue = function (previous, current) {
					return previous.split(/\s*,\s*/).slice(0, -1).concat([current, '']).join(', ');
				};

				peekValue = function (current) {
					return current.split(/\s*,\s*/).pop();
				};

				userInput = formInput;

				break;

			default:
				appendValue = function (previous, current) {
					return current;
				};

				peekValue = function (current) {
					return current;
				};

				userInput = formInput;

				break;
		}

		// Configure auto-complete callback if enabled
		var callback = formInput.data('callback');

		if (callback) {
			userInput
				.off('keydown')
				.autocomplete({
					focus: function () {
						return false;
					},
					select: function (event, ui) {
						this.value = appendValue(this.value, ui.item.value);

						return false;
					},
					source: function (request, response) {
						$.getJSON(callback, { query: peekValue(request.term) })
							.done(function (json) {
								response(json.items);
							})
							.fail(function () {
								response([]);
							});
					},
					minLength: 2
				});
		}
	});
};

yn.controlFormCopyFetch = function (value) {
	if (yn.controlFormCopyKey === undefined)
		return '';

	return yn.controlFormStore('yn.copy', yn.controlFormCopyKey, value || '');
};

yn.controlFormCopyMark = function (element, button, message) {
	var container;
	var html;

	// Get currently selected text (both standard and IE methods)
	if (window.getSelection !== undefined) {
		var selection = window.getSelection();

		if (selection.rangeCount > 0 && !selection.isCollapsed) {
			var range = selection.getRangeAt(0);

			for (container = range.commonAncestorContainer; container !== element.get(0) && container !== null;)
				container = container.parentNode;

			if (container !== null) {
				container = document.createElement('div');
				container.appendChild(range.cloneContents());

				html = container.innerHTML;
			}
			else
				html = '';
		}
		else
			html = '';
	}
	else
		html = '';

	// Hide copy button if no text was selected
	if (html === '') {
		button.hide();

		return;
	}

	// Show copy button and bind events otherwise
	button
		.off('click keydown')
		.on('click keydown', function (event) {
			var quote = html
				.trim()
				.replace(/<a[^>]+class="ref"[^>]*>([^<]*)<\/a>/g, '$1')
				.replace(/<a[^>]+href="([^"]*)"[^>]*>([^<]*)<\/a>/g, '[url=$1]$2[/url]')
				.replace(/<(b|em|h[1-4]|i|s|sub|sup|u)>(.*?)<\/\\1>/g, '[$1]$2[/$1]')
				.replace(/<blockquote>(.*?)<\/blockquote>/g, '[quote]$1[/quote]')
				.replace(/<img[^>]+class="emoji"[^>]+src="[^"]*"[^>]+alt="([^"]*)"[^>]*>/g, '#$1#')
				.replace(/<img[^>]+src="([^"]+)"[^>]*>/g, function (match, src) {
					// Convert image URL to absolute on replacement
					return '[img]' + $('<a>').attr('href', src).attr('href') + '[/img]';
				})
				.replace(/<br[^>]*>/g, "\n");

			var plain = $('<div>').html(quote).text();
			var append = '[quote][b]' + button.data('context') + '[/b]\n' + plain + '[/quote]';
			var text = yn.controlFormCopyFetch();

			if (text !== '')
				text = text + "\n";

			yn.controlFormCopyFetch(text + append);
			yn.notice(button, true, message);

			button.hide();

			if (window.getSelection !== undefined)
				window.getSelection().removeAllRanges();

			return false;
		})
		.show();
};

yn.controlFormDraftAttach = function (target) {
	target.each(function () {
		var input = $(this);
		var key = input.data('draft');

		if (!input.val())
			input.val(yn.controlFormStore('yn.draft', key));

		input
			.data('draft-alive', true)
			.on('keyup', yn.defer(500, function () {
				if (input.data('draft-alive'))
					yn.controlFormStore('yn.draft', key, input.val());
			}));
	});
};

yn.controlFormDraftDetach = function (target) {
	target.each(function () {
		var key = $(this)
			.data('draft-alive', false)
			.data('draft');

		yn.controlFormDraftClear(key);
	});
};

yn.controlFormDraftClear = function (key) {
	yn.controlFormStore('yn.draft', key, '');
};

/*
** Populate markup window upon markup button click.
** handle:	button handle
** url:		markup resource URL
*/
yn.controlFormMarkup = function (handle, url) {
	var editor = handle.closest('.markItUpContainer').find('.markItUpEditor');
	var window = yn.window(handle, handle.text(), 640, 480);

	var editor_insert = function (value) {
		editor.markItUp('insert', { replaceWith: value });
	};

	var emoji_edit_bind = function (container) {
		container
			.find('.bind-emoji')
			.on('submit', function () {
				return yn.submit($(this), $(this).find('input[type=submit]'), function (html) {
					emoji_edit_bind(container.html(html));
				});
			})
			.end()
			.find('.bind-emoji .back')
			.on('click keydown', function () {
				return yn.load_html($.get($(this).attr('href')), $(this), container, emoji_list_bind);
			})
			.end()
			.find('.bind-emoji input[name=insert]')
			.each(function () {
				editor_insert($(this).val());
			})
			.end();
	};

	var emoji_list_bind_result = function (container) {
		container
			.find('a')
			.on('click keydown', function () {
				editor_insert($(this).text());

				return false;
			});
	};

	var emoji_list_bind = function (container) {
		container
			.find('.bind-emoji input[name=prefix]')
			.on('keyup', yn.defer(200, function () {
				var handle = $(this);
				var prefix = handle.val();

				if (!prefix)
					return;

				var container = handle.closest('.bind-emoji').find('.bind-emoji-list');
				var promise = $.get(handle.closest('form').prop('action'), { prefix: prefix });

				yn.load_html(promise, handle, container, emoji_list_bind_result);
			}))
			.trigger('keyup')
			.end()
			.find('.bind-emoji .send')
			.on('click keydown', function () {
				return yn.load_html($.get($(this).attr('href')), $(this), container, emoji_edit_bind);
			})
			.end();
	};

	var language_list_bind = function (container) {
		container
			.find('.bind-language')
			.on('submit', function () {
				var language = $(this).find('select[name=language]').val();
				var source = $(this).find('textarea[name=source]').val();

				editor_insert('[code=' + language + ']' + source + '[/code]');

				return false;
			});
	};

	var poll_edit_bind = function (container) {
		container
			.find('.bind-poll')
			.on('submit', function () {
				var submit = $(this).find('input[type=submit]');

				return yn.submit($(this), submit, function (html) {
					poll_edit_bind(container.html(html));
				});
			})
			.end()
			.find('.bind-poll input[name=code]')
			.each(function () {
				editor_insert($(this).val());
			})
			.end();
	};

	yn.load_html($.get(url), handle, window.inner, function (container) {
		emoji_list_bind(container);
		emoji_list_bind_result(container);
		language_list_bind(container);
		poll_edit_bind(container);
	});
};

/*
** Store strings by key into local storage.
** group:	local storage variable name
** key:		item key
** value:	string to store and retrieve old value, undefined to retrieve only
** return:	previously stored value if any, empty string otherwise
*/
yn.controlFormStore = function (group, key, value) {
	// Ensure compatibility with store feature
	if (window.JSON === undefined || window.localStorage === undefined)
		return '';

	// Fetch and decode records
	var records;

	try {
		records = JSON.parse(localStorage.getItem(group)) || {};
	}
	catch (e) {
		records = {};
	}

	// Search for record and delete expired ones
	var count = 0;
	var fetch = '';
	var time = yn.now();

	for (var old in records) {
		if (records[old][1] <= time)
			delete records[old];
		else {
			if (key === old)
				fetch = records[old][0];

			++count;
		}
	}

	// Assign or delete record
	if (value) {
		if (records[key] === undefined)
			++count;

		records[key] = [value, time + 86400];
	}
	else if (value !== undefined) {
		if (records[key] !== undefined)
			--count;

		delete records[key];
	}

	// Update storage
	try {
		if (count > 0)
			localStorage.setItem(group, JSON.stringify(records));
		else
			localStorage.removeItem(group);
	}
	catch (e) {
		// Storage full or not available
	}

	return fetch;
};
