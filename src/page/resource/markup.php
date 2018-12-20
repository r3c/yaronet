<?php

defined('YARONET') or die;

function markup_render($request, $logger, $sql, $display, $input, $user)
{
    $format = (string)$request->get_or_fail('format');
    $syntax = (string)$request->get_or_fail('syntax');

    Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

    $formats = array(
        'html' => true
    );

    $syntaxes = array(
        'bbcode-block' => true,
        'bbcode-inline' => true
    );

    if (!isset($formats[$format]) || !isset($syntaxes[$syntax]) || !$input->get_string('text', $text)) {
        return Glay\Network\HTTP::code(400);
    }

    $context = yN\Engine\Text\Markup::context($request->router, $logger, $user);
    $text = yN\Engine\Text\Markup::render($format, yN\Engine\Text\Markup::convert($syntax, $text, $context), $context);

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-resource-markup-render.deval', 'resource.markup.render', array(
        'text' => $text
    )));
}
