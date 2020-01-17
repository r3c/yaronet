<?php

defined('YARONET') or die;

if (!function_exists('_amato_syntax_bbcode_url')) {
    require './engine/text/markup/syntax/bbcode.php';
}

$syntax_pattern_http = 'https?%://';
$syntax_pattern_url = '(?:[!%#$%%&\'()*+,./0-9:;=?%@_~-]|\\pL)+';
$syntax_pattern_url_safe = '(?:[!%#$%%&\'()*+,./0-9:;=?%@_~-]|\\pL)*(?:[%#$%%&\'*+/0-9=?%@_~-]|\\pL)';
$syntax = array(
    '.' => array(
        array(Amato\Tag::ALONE, "\n", null, 'amato_syntax_bbcode_newline_convert')
    ),
    'a' => array(
        array(Amato\Tag::ALONE, "[url]<$syntax_pattern_url@u>[/url]", null, 'amato_syntax_bbcode_anchor_convert', 'amato_syntax_bbcode_anchor_revert'),
        array(Amato\Tag::ALONE, "<$syntax_pattern_http$syntax_pattern_url_safe@u>", null, 'amato_syntax_bbcode_anchor_convert', 'amato_syntax_bbcode_anchor_revert'),
        array(Amato\Tag::START, "[url=<$syntax_pattern_url@u>]", null, 'amato_syntax_bbcode_anchor_convert', 'amato_syntax_bbcode_anchor_revert'),
        array(Amato\Tag::STOP, '[/url]', null, 'amato_syntax_bbcode_anchor_convert', 'amato_syntax_bbcode_anchor_revert')
    ),
    'b' => array(
        array(Amato\Tag::START, '[b]'),
        array(Amato\Tag::STOP, '[/b]')
    ),
    'c' => array(
        array(Amato\Tag::START, '[color=<%#?#><[0-9A-Fa-f]{3}@h>]'),
        array(Amato\Tag::START, '[color=<%#?#><[0-9A-Fa-f]{6}@h>]'),
        array(Amato\Tag::STOP, '[/color]')
    ),
    'i' => array(
        array(Amato\Tag::START, '[i]'),
        array(Amato\Tag::STOP, '[/i]')
    ),
    's' => array(
        array(Amato\Tag::START, '[s]'),
        array(Amato\Tag::STOP, '[/s]')
    ),
    'sub' => array(
        array(Amato\Tag::START, '[sub]'),
        array(Amato\Tag::STOP, '[/sub]')
    ),
    'sup' => array(
        array(Amato\Tag::START, '[sup]'),
        array(Amato\Tag::STOP, '[/sup]')
    ),
    'u' => array(
        array(Amato\Tag::START, '[u]'),
        array(Amato\Tag::STOP, '[/u]')
    )
);
