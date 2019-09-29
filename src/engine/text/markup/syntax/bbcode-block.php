<?php

defined('YARONET') or die;

require './engine/text/markup/syntax/bbcode-inline.php';

$syntax = array(
    '.' => array(
        array(Amato\Tag::ALONE, "\n")
    ),
    'align' => array(
        array(Amato\Tag::START, "[align=center]", array('w' => 'c')),
        array(Amato\Tag::START, "[align=left]", array('w' => 'l')),
        array(Amato\Tag::START, "[align=justify]", array('w' => 'j')),
        array(Amato\Tag::START, "[align=right]", array('w' => 'r')),
        array(Amato\Tag::STOP, "[/align]\n"),
        array(Amato\Tag::STOP, "[/align]")
    ),
    'box' => array(
        array(Amato\Tag::START, "[box=<[^][\n]{1,200}:t>]"),
        array(Amato\Tag::STOP, "[/box]\n"),
        array(Amato\Tag::STOP, "[/box]")
    ),
    'center' => array(
        array(Amato\Tag::START, "[center]"),
        array(Amato\Tag::STOP, "[/center]\n"),
        array(Amato\Tag::STOP, "[/center]")
    ),
    'cmd' => array(
        array(Amato\Tag::START, '[yncMd:159]', null, null, 'amato_syntax_bbcode_command_revert'),
        array(Amato\Tag::STOP, '[/yncMd:159]', null, null, 'amato_syntax_bbcode_command_revert')
    ),
    'code' => array(
        array(Amato\Tag::ALONE, "[code=<[-0-9A-Za-z._+]{1,16}:l>]<.*?:b>[/code]\n"),
        array(Amato\Tag::ALONE, "[code=<[-0-9A-Za-z._+]{1,16}:l>]<.*?:b>[/code]")
    ),
    'e' => array(
        array(Amato\Tag::ALONE, ':D', array('n' => 'grin'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, ':(', array('n' => 'sad'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, ':o', array('n' => 'embarrassed'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, ':)', array('n' => 'smile'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, ':p', array('n' => 'tongue'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, ';)', array('n' => 'wink'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, '=)', array('n' => 'happy'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, '%)', array('n' => 'cheeky'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, ':|', array('n' => 'neutral'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, ':S', array('n' => 'sorry'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xE2\x81\x89", array('n' => 'what'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xE2\x84\xB9", array('n' => 'info'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xE2\x98\x80", array('n' => 'sun'), 'amato_syntax_bbcode_emoji_convert'),
        // array (Amato\Tag::ALONE, "\xE2\x98\xBA", array ('n' => 'smile'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xE2\x9A\xBD", array('n' => 'soccer'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xE2\x9B\x84", array('n' => 'snowman'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xE2\x9B\x94", array('n' => 'forbidden'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xE2\x9C\x8F", array('n' => 'pencil'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xE2\x9C\x92", array('n' => 'nib'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xE2\x9D\x84", array('n' => 'snowflake'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xE2\x9D\xA4", array('n' => 'heart'), 'amato_syntax_bbcode_emoji_convert'),
        // array (Amato\Tag::ALONE, "\xF0\x9F\x8C\x9B", array ('n' => '#lune#'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\x80", array('n' => 'clover'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\x84", array('n' => 'mushroom'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\x85", array('n' => 'tomato'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\x8C", array('n' => 'banana'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\x8E", array('n' => 'apple'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\x95", array('n' => 'pizza'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\x97", array('n' => 'poultry'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\xA6", array('n' => 'icecream'), 'amato_syntax_bbcode_emoji_convert'),
        // array (Amato\Tag::ALONE, "\xF0\x9F\x8D\xA7", array ('n' => '#cornet#'), 'amato_syntax_bbcode_emoji_convert'),
        // array (Amato\Tag::ALONE, "\xF0\x9F\x8D\xA8", array ('n' => '#cornet#'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\xA9", array('n' => 'doughnut'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\xAA", array('n' => 'cookie'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\xB0", array('n' => 'cake'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8D\xBA", array('n' => 'beer'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8E\x81", array('n' => 'present'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8E\x82", array('n' => 'birthday'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8E\x83", array('n' => 'pumpkin'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8E\x84", array('n' => 'christmas'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8E\x85", array('n' => 'santa'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8E\xA4", array('n' => 'microphone'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8E\xB5", array('n' => 'note'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8E\xB8", array('n' => 'guitar'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8E\xBA", array('n' => 'trumpet'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8F\x81", array('n' => 'flag'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x8F\x86", array('n' => 'trophy'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x90\x87", array('n' => 'rabbit'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x90\x8A", array('n' => 'crocodile'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x90\x8C", array('n' => 'snail'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x90\x99", array('n' => 'octopus'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x90\x9D", array('n' => 'honeybee'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x90\x9F", array('n' => 'poisson'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x90\xA2", array('n' => 'turtle'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x90\xAE", array('n' => 'cow'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x91\xA7", array('n' => 'girl'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x91\xAE", array('n' => 'police'), 'amato_syntax_bbcode_emoji_convert'),
        // array (Amato\Tag::ALONE, "\xF0\x9F\x91\xB2", array ('n' => '#chinois#'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x91\xB8", array('n' => 'princess'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x91\xBB", array('n' => 'ghost'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x91\xBD", array('n' => 'alien'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x92\x80", array('n' => 'skull'), 'amato_syntax_bbcode_emoji_convert'),
        // array (Amato\Tag::ALONE, "\xF0\x9F\x92\x8F", array ('n' => 'bisoo'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x92\x90", array('n' => 'bouquet'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x92\x91", array('n' => 'couple'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x93\xB1", array('n' => 'mobile'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x93\xBA", array('n' => 'tv'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x94\x91", array('n' => 'key'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x82", array('n' => 'laught'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x84", array('n' => 'grin'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x88", array('n' => 'devil'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x89", array('n' => 'wink'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x8A", array('n' => 'smile'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x8D", array('n' => 'love'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x8E", array('n' => 'cool'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x90", array('n' => 'neutral'), 'amato_syntax_bbcode_emoji_convert'),
        // array (Amato\Tag::ALONE, "\xF0\x9F\x98\x91", array ('n' => 'neutral'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x98", array('n' => 'kiss'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x9B", array('n' => 'tongue'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\x9F", array('n' => 'sad'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\xA0", array('n' => 'angry'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\xA2", array('n' => 'mourn'), 'amato_syntax_bbcode_emoji_convert'),
        // array (Amato\Tag::ALONE, "\xF0\x9F\x98\xA6", array ('n' => 'angry'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\xA8", array('n' => 'fear'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\xAD", array('n' => 'cry'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x98\xAE", array('n' => 'embarrassed'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, "\xF0\x9F\x9A\x97", array('n' => 'car'), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, '##<[-0-9A-Za-z_]{1,32}:n>##', array('c' => ''), 'amato_syntax_bbcode_emoji_convert'),
        array(Amato\Tag::ALONE, '#<[0-9a-z]{1,16}:n>#', null, 'amato_syntax_bbcode_emoji_convert')
    ),
    'font' => array(
        array(Amato\Tag::START, '[font=<[0-9]{1,10}:p>]'),
        array(Amato\Tag::STOP, '[/font]')
    ),
    'h1' => array(
        array(Amato\Tag::START, "[h1]"),
        array(Amato\Tag::STOP, "[/h1]\n"),
        array(Amato\Tag::STOP, "[/h1]")
    ),
    'h2' => array(
        array(Amato\Tag::START, "[h2]"),
        array(Amato\Tag::STOP, "[/h2]\n"),
        array(Amato\Tag::STOP, "[/h2]")
    ),
    'h3' => array(
        array(Amato\Tag::START, "[h3]"),
        array(Amato\Tag::STOP, "[/h3]\n"),
        array(Amato\Tag::STOP, "[/h3]")
    ),
    'h4' => array(
        array(Amato\Tag::START, "[h4]"),
        array(Amato\Tag::STOP, "[/h4]\n"),
        array(Amato\Tag::STOP, "[/h4]")
    ),
    'hr' => array(
        array(Amato\Tag::ALONE, '[hr]')
    ),
    'img' => array(
        array(Amato\Tag::ALONE, "[img=<$syntax_pattern_url:t>]<$syntax_pattern_url:u>[/img]", null, 'amato_syntax_bbcode_image_convert'),
        array(Amato\Tag::ALONE, "[img]<$syntax_pattern_url:u>[/img]", null, 'amato_syntax_bbcode_image_convert')
    ),
    'list' => array(
        array(Amato\Tag::START, "[list]<\\s*#\n>[#]", array('o' => '1')),
        array(Amato\Tag::START, "[list]<\\s*#\n>[*]", array('u' => '1')),
        array(Amato\Tag::STEP, "<\\s*#\n>[#]", array('o' => '1')),
        array(Amato\Tag::STEP, "<\\s*#\n>[##]", array('o' => '2')),
        array(Amato\Tag::STEP, "<\\s*#\n>[###]", array('o' => '3')),
        array(Amato\Tag::STEP, "<\\s*#\n>[#=<[1-9][0-9]*:o>]"),
        array(Amato\Tag::STEP, "<\\s*#\n>[*]", array('u' => '1')),
        array(Amato\Tag::STEP, "<\\s*#\n>[**]", array('u' => '2')),
        array(Amato\Tag::STEP, "<\\s*#\n>[***]", array('u' => '3')),
        array(Amato\Tag::STEP, "<\\s*#\n>[*=<[1-9][0-9]*:u>]"),
        array(Amato\Tag::STOP, "[/list]\n"),
        array(Amato\Tag::STOP, "[/list]")
    ),
    'media' => array(
        array(Amato\Tag::ALONE, "<$syntax_pattern_http$syntax_pattern_url_safe:u>", null, 'amato_syntax_bbcode_media_convert'),
        array(Amato\Tag::ALONE, "[media]<$syntax_pattern_url:u>[/media]", null, 'amato_syntax_bbcode_media_convert'),
        array(Amato\Tag::ALONE, "[media=<$syntax_pattern_url:u>]", null, 'amato_syntax_bbcode_media_convert')
    ),
    'nsfw' => array(
        array(Amato\Tag::START, "[nsfw]"),
        array(Amato\Tag::STOP, "[/nsfw]\n"),
        array(Amato\Tag::STOP, "[/nsfw]")
    ),
    'poll' => array(
        array(Amato\Tag::ALONE, '[poll=<[0-9]{1,10}:i>]')
    ),
    'pre' => array(
        array(Amato\Tag::ALONE, "[pre]<.*?:b>[/pre]\n"),
        array(Amato\Tag::ALONE, "[pre]<.*?:b>[/pre]")
    ),
    'quote' => array(
        array(Amato\Tag::START, "[quote]"),
        array(Amato\Tag::STOP, "[/quote]\n"),
        array(Amato\Tag::STOP, "[/quote]"),
        array(Amato\Tag::START, "[cite]"),
        array(Amato\Tag::STOP, "[/cite]\n"),
        array(Amato\Tag::STOP, "[/cite]")
    ),
    'ref' => array(
        array(Amato\Tag::ALONE, './<[0-9]{1,10}:t>-<[0-9]{1,10}:p>', null, 'amato_syntax_bbcode_ref_convert'),
        array(Amato\Tag::ALONE, './<[0-9]{1,10}:p>', null, 'amato_syntax_bbcode_ref_convert')
    ),
    'slap' => array(
        array(Amato\Tag::ALONE, "!slap <[ -~]{1,50}:t>", null, 'amato_syntax_bbcode_slap_convert')
    ),
    'spoil' => array(
        array(Amato\Tag::START, '[spoiler]'),
        array(Amato\Tag::STOP, '[/spoiler]')
    ),
    'table' => array(
        array(Amato\Tag::START, "[table]<\\s*#\n>[|]", array('d' => '1')),
        array(Amato\Tag::START, "[table]<\\s*#\n>[||]", array('d' => '2')),
        array(Amato\Tag::START, "[table]<\\s*#\n>[|||]", array('d' => '3')),
        array(Amato\Tag::START, "[table]<\\s*#\n>[|=<[1-9][0-9]*:d>]"),
        array(Amato\Tag::START, "[table]<\\s*#\n>[^]", array('h' => '1')),
        array(Amato\Tag::START, "[table]<\\s*#\n>[^^]", array('h' => '2')),
        array(Amato\Tag::START, "[table]<\\s*#\n>[^^^]", array('h' => '3')),
        array(Amato\Tag::START, "[table]<\\s*#\n>[^=<[1-9][0-9]*:h>]"),
        array(Amato\Tag::STEP, "[-]<\\s*#\n>[|]", array('d' => '1', 'r' => '')),
        array(Amato\Tag::STEP, "[-]<\\s*#\n>[||]", array('d' => '2', 'r' => '')),
        array(Amato\Tag::STEP, "[-]<\\s*#\n>[|||]", array('d' => '3', 'r' => '')),
        array(Amato\Tag::STEP, "[-]<\\s*#\n>[|=<[1-9][0-9]*:d>]", array('r' => '')),
        array(Amato\Tag::STEP, "[-]<\\s*#\n>[^]", array('h' => '1', 'r' => '')),
        array(Amato\Tag::STEP, "[-]<\\s*#\n>[^^]", array('h' => '2', 'r' => '')),
        array(Amato\Tag::STEP, "[-]<\\s*#\n>[^^^]", array('h' => '3', 'r' => '')),
        array(Amato\Tag::STEP, "[-]<\\s*#\n>[^=<[1-9][0-9]*:h>]", array('r' => '')),
        array(Amato\Tag::STEP, "[|]", array('d' => '1')),
        array(Amato\Tag::STEP, "[||]", array('d' => '2')),
        array(Amato\Tag::STEP, "[|||]", array('d' => '3')),
        array(Amato\Tag::STEP, "[|=<[1-9][0-9]*:d>]"),
        array(Amato\Tag::STEP, "[^]", array('h' => '1')),
        array(Amato\Tag::STEP, "[^^]", array('h' => '2')),
        array(Amato\Tag::STEP, "[^^^]", array('h' => '3')),
        array(Amato\Tag::STEP, "[^=<[1-9][0-9]*:h>]"),
        array(Amato\Tag::STOP, "[/table]\n"),
        array(Amato\Tag::STOP, "[/table]")
    )
) + $syntax;
