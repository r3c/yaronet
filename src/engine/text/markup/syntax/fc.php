<?php

defined('YARONET') or die;

require './engine/text/markup/syntax/bbcode-inline.php';

$syntax = array(
    'e' => array(
        array(Amato\Tag::ALONE, ':)', array('n' => 'fc-00'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, ';)', array('n' => 'fc-01'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, ':D', array('n' => 'fc-02'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, '8)', array('n' => 'fc-03'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, ':o', array('n' => 'fc-04'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, '8|', array('n' => 'fc-05'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, ':(', array('n' => 'fc-06'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, ':}', array('n' => 'fc-07'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, ':J', array('n' => 'fc-08'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, ':E', array('n' => 'fc-09'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, ':#', array('n' => 'fc-10'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, ':p', array('n' => 'fc-11'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, '=)', array('n' => 'fc-12'), 'amato_syntax_fc_emoji_convert'),
        array(Amato\Tag::ALONE, '#<[-0-9a-z]{1,16}@n>#', null, 'amato_syntax_fc_emoji_convert')
    ),
    'link' => array(
        array(Amato\Tag::ALONE, "<$syntax_pattern_http$syntax_pattern_url@u>", null, 'amato_syntax_bbcode_anchor_convert', 'amato_syntax_bbcode_anchor_revert')
    )
);

function amato_syntax_fc_emoji_convert($type, &$params)
{
    Glay\using('yN\\Engine\\Media\\Emoji', './engine/media/emoji.php');

    return yN\Engine\Media\Emoji::check_native($params['n'], 'small');
}
