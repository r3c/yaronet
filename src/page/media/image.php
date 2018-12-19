<?php

defined('YARONET') or die;

function image_render($request, $logger, $sql, $display, $input, $user)
{
    $name = (string)$request->get_or_fail('name');

    Glay\using('yN\\Engine\\Media\\Binary', './engine/media/binary.php');
    Glay\using('yN\\Engine\\Network\\URL', './engine/network/url.php');

    yN\Engine\Media\Binary::read($name, 100 * 24 * 60 * 60);

    return Glay\Network\HTTP::go(yN\Engine\Network\URL::to_static() . '/image/unknown.png');
}
