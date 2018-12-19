<?php

defined('YARONET') or die;

function language_list($request, $logger, $sql, $display, $input, $user)
{
    return Glay\Network\HTTP::data($display->render('yn-media-language-list.deval', 'media.language.list'));
}
