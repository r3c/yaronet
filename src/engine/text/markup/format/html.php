<?php

defined('YARONET') or die;

$format = array(
    '.'			=> array('amato_format_html_newline'),
    'a'			=> array('amato_format_html_anchor'),
    'align'		=> array('amato_format_html_align', 2),
    'b'			=> array(_amato_format_html_tag('b')),
    'box'		=> array('amato_format_html_box', 2),
    'c'			=> array('amato_format_html_color'),
    'center'	=> array('amato_format_html_center', 2),
    'cmd'		=> array(_amato_format_html_class('div', 'cmd'), 2),
    'code'		=> array('amato_format_html_code', 2),
    'e'			=> array('amato_format_html_emoji'),
    'font'		=> array('amato_format_html_font'),
    'h1'		=> array(_amato_format_html_class('div', 'h1')),
    'h2'		=> array(_amato_format_html_class('div', 'h2')),
    'h3'		=> array(_amato_format_html_class('div', 'h3')),
    'h4'		=> array(_amato_format_html_class('div', 'h4')),
    'hr'		=> array('amato_format_html_horizontal', 2),
    'i'			=> array(_amato_format_html_tag('i')),
    'img'		=> array('amato_format_html_image'),
    'link'		=> array('amato_format_html_link'),
    'list'		=> array('amato_format_html_list', 2),
    'media'		=> array('amato_format_html_media', 2),
    'nsfw'		=> array('amato_format_html_nsfw', 2),
    'poll'		=> array('amato_format_html_poll', 2),
    'pre'		=> array('amato_format_html_pre', 2),
    'quote'		=> array('amato_format_html_quote', 2),
    'ref'		=> array('amato_format_html_ref'),
    's'			=> array(_amato_format_html_class('span', 's')),
    'slap'		=> array('amato_format_html_slap'),
    'spoil'		=> array(_amato_format_html_class('span', 'spoil')),
    'sub'		=> array(_amato_format_html_tag('sub')),
    'sup'		=> array(_amato_format_html_tag('sup')),
    'table'		=> array('amato_format_html_table', 2),
    'u'			=> array(_amato_format_html_class('span', 'u'))
);

function _amato_format_html_class($tag, $class)
{
    return function ($body) use ($tag, $class) {
        return $body !== '' ? '<' . $tag . ' class="' . $class . '">' . $body . '</' . $tag . '>' : '';
    };
}

function _amato_format_html_escape($string)
{
    return htmlspecialchars($string, ENT_COMPAT, mb_internal_encoding());
}

function _amato_format_html_tag($id)
{
    return function ($body) use ($id) {
        return $body !== '' ? '<' . $id . '>' . $body . '</' . $id . '>' : '';
    };
}

function amato_format_html_align($body, $params)
{
    $aligns = array(
        'c'	=> 'center',
        'j'	=> 'justify',
        'l'	=> 'left',
        'r'	=> 'right'
    );

    return $body !== '' ? '<div style="text-align: ' . $aligns[$params['w']] . ';">' . $body . '</div>' : '';
}

function amato_format_html_anchor($body, $params)
{
    $url = Glay\Network\URI::create($params['u']);

    if ($url->host === null) {
        $extra = '';
        $href = (string)Glay\Network\URI::here()
            ->combine(yN\Engine\Network\URL::to_page())
            ->combine($url);
    } else {
        $extra = ' rel="nofollow noopener noreferrer"';
        $href = (string)$url;
    }

    return '<a href="' . _amato_format_html_escape($href) . '"' . $extra . '>' . ($body !== '' ? $body : _amato_format_html_escape($url)) . '</a>';
}

function amato_format_html_box($body, $params)
{
    return '<div class="box"><div class="box-head">' . _amato_format_html_escape($params['t']) . '</div><div class="box-body">' . $body . '</div></div>';
}

function amato_format_html_center($body)
{
    return $body !== '' ? '<div class="center">' . $body . '</div>' : '';
}

function amato_format_html_code($body, $params)
{
    return '<code class="lang-' . _amato_format_html_escape($params['l']) . '">' . _amato_format_html_escape($params['b']) . '</code>';
}

function amato_format_html_color($body, $params)
{
    return $body !== '' ? '<span style="color: #' . _amato_format_html_escape($params['h']) . ';">' . $body . '</span>' : '';
}

function amato_format_html_emoji($body, $params, $closing, $context)
{
    if ($context->router === null) {
        return '';
    }

    Glay\using('yN\\Engine\\Media\\Emoji', './engine/media/emoji.php');

    if (isset($params['c'])) {
        $alt = '#' . $params['n'] . '#';
        $src = yN\Engine\Media\Emoji::url_custom($context->router, $params['n']);
    } else {
        $alt = $params['n'];
        $src = yN\Engine\Media\Emoji::url_native($params['n']);
    }

    return '<img class="emoji" src="' . _amato_format_html_escape($src) . '" alt="' . _amato_format_html_escape($alt) . '" />';
}

function amato_format_html_font($body, $params)
{
    return $body !== '' ? '<span style="font-size: ' . max(min((int)$params['p'], 300), 50) . '%; line-height: 100%;">' . $body . '</span>' : '';
}

function amato_format_html_horizontal()
{
    return '<hr />';
}

function amato_format_html_image($body, $params)
{
    Glay\using('yN\\Engine\\Media\\Widget', './engine/media/widget.php');

    $image = $params['u'];
    $widget = new yN\Engine\Media\Widget($image, 'image', $params['c']);

    return $widget->html($params->get('t', false)) ?: amato_format_html_anchor($body, $params);
}

function amato_format_html_link($body, $params)
{
    return amato_format_html_anchor('[lien]', $params);
}

function amato_format_html_list($body, $params, $closing)
{
    // Read parameters from last tag
    $o = $params->last('o');

    if ($o) {
        $level = max(min((int)$o, 8), 1);
        $tag = 'o';
    } else {
        $level = max(min((int)$params->last('u', 1), 8), 1);
        $tag = 'u';
    }

    // Update HTML buffer
    $buffer = $params->get('buffer', '');
    $stack = $params->get('stack', '');

    for (; strlen($stack) > $level; $stack = substr($stack, 1)) {
        $buffer .= '</li></' . $stack[0] . 'l>';
    }

    if (strlen($stack) === $level) {
        $buffer .= '</li><li>';
    }

    for (; strlen($stack) < $level; $stack = $tag . $stack) {
        $buffer .= '<' . $tag . 'l class="list"><li>';
    }

    $buffer .= $body;

    // Reset flags, save current buffer and tags stack
    $params->forget('o');
    $params->forget('u');

    $params['buffer'] = $buffer;
    $params['stack'] = $stack;

    // Render list by closing pending tags if any
    if (!$closing) {
        return '';
    }

    for (; strlen($stack) > 0; $stack = substr($stack, 1)) {
        $buffer .= '</li></' . $stack[0] . 'l>';
    }

    return $buffer;
}

function amato_format_html_media($body, $params)
{
    Glay\using('yN\\Engine\\Media\\Widget', './engine/media/widget.php');

    $widget = new yN\Engine\Media\Widget($params['u'], $params['t'], $params->get('c'));

    return $widget->html() ?: amato_format_html_anchor($body, $params);
}

function amato_format_html_newline()
{
    return '<br />';
}

function amato_format_html_nsfw($body, $params, $closing, $context)
{
    $i18n = new yN\Engine\Text\Internationalization($context->user->language);

    $confirm = $i18n->format('system.media.markup.html.nsfw.confirm');
    $head = $i18n->format('system.media.markup.html.nsfw.head');

    return '<div class="nsfw"><div class="nsfw-head" data-confirm="' . _amato_format_html_escape($confirm) . '">' . _amato_format_html_escape($head) . '</div><div class="nsfw-body">' . $body . '</div></div>';
}

function amato_format_html_poll($body, $params, $closing, $context)
{
    $url = _amato_format_html_escape($context->router->url('survey.poll.view', array('poll' => (int)$params['i'], '_template' => 'frame')));

    return '<div class="poll" data-url="' . $url . '"></div>';
}

function amato_format_html_pre($body, $params)
{
    $html = _amato_format_html_escape(preg_replace("/^\n|\n$/", '', $params['b']));

    if (mb_strpos($params['b'], "\n") !== false) {
        return '<pre class="pre">' . $html . '</pre>';
    } elseif ($html !== '') {
        return '<span class="pre">' . $html . '</span>';
    }

    return '';
}

function amato_format_html_quote($body)
{
    return $body !== '' ? '<blockquote>' . $body . '</blockquote>' : '';
}

function amato_format_html_ref($body, $params, $closing, $context)
{
    if ($context->router === null) {
        return '';
    }

    $position = $params['p'];
    $topic = $params['t'];

    $peek = $context->router->url('board.post.view', array('topic' => $topic, 'position' => $position, 'peek' => 1, '_template' => 'frame')); // FIXME: remove template-specific variable "peek" [html-peek]
    $href = $peek; // FIXME: should link to board.post.view without template instead (but doesn't exist)

    return '<a class="ref" href="' . _amato_format_html_escape($href) . '" data-url-peek="' . _amato_format_html_escape($peek) . '">./' . _amato_format_html_escape($position) . '</a>';
}

function amato_format_html_slap($body, $params)
{
    return '!slap ' . _amato_format_html_escape($params['t']) . '<br /><span style="color: #990099;">&bull; ' . _amato_format_html_escape($params['s']) . ' slaps ' . _amato_format_html_escape($params['t']) . ' around a bit with a large trout !</span><br />';
}

function amato_format_html_table($body, $params, $closing)
{
    // Read parameters from last tag
    $h = $params->last('h');

    if ($h) {
        $span = max(min((int)$h, 8), 1);
        $tag = 'h';
    } else {
        $span = max(min((int)$params->last('d', 1), 8), 1);
        $tag = 'd';
    }

    // Update HTML buffer
    $rows = $params->get('rows', array());

    if (count($rows) === 0 || $params->last('r') !== null) {
        $rows[] = array('', 0);
    }

    // Update HTML buffer
    $align_left = mb_substr($body, -2) === '  ';
    $align_right = mb_substr($body, 0, 2) === '  ';

    if ($align_left && $align_right) {
        $style = ' style="text-align: center;"';
    } elseif ($align_left) {
        $style = ' style="text-align: left;"';
    } elseif ($align_right) {
        $style = ' style="text-align: right;"';
    } else {
        $style = '';
    }

    $colspan = $span > 1 ? ' colspan="' . $span . '"' : '';
    $current = count($rows) - 1;

    $rows[$current][0] .= '<t' . $tag . $colspan . $style . '>' . $body . '</t' . $tag . '>';
    $rows[$current][1] += $span;

    // Reset flags, save current rows
    $params->forget('d');
    $params->forget('h');
    $params->forget('r');

    $params['rows'] = $rows;

    // Render table by merging computed rows, extending their span when needed
    if (!$closing || count($rows) === 0) {
        return '';
    }

    $buffer = '';
    $width = 0;

    foreach ($rows as $row) {
        $width = max($row[1], $width);
    }

    foreach ($rows as $row) {
        list($append, $span) = $row;

        $buffer .=
            '<tr>' .
                ($append) .
                ($span < $width ? '<td ' . ($width > $span + 1 ? ' colspan="' . ($width - $span) . '"' : '') . '></td>' : '') .
            '</tr>';
    }

    return '<table class="table">' . $buffer . '</table>';
}
