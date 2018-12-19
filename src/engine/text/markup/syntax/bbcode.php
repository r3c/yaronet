<?php

defined('YARONET') or die;

function _amato_syntax_bbcode_url($string)
{
    return Glay\Network\URI::create(preg_match('#^https?://#i', $string) === 1 ? $string : 'http://' . $string);
}

function amato_syntax_bbcode_anchor_convert($type, &$params)
{
    if (!isset($params['u'])) {
        return true;
    }

    $base = Glay\Network\URI::here()->combine(yN\Engine\Network\URL::to_page());
    $href = _amato_syntax_bbcode_url($params['u']);

    if (!$base->valid || !$href->valid) {
        return false;
    }

    $base->fragment = null;
    $base->query = null;

    $url = $href->relative($base);

    if ($url->host === null && (string)$url !== '') {
        $href = $url;
    }

    $params['u'] = (string)$href;

    return true;
}

function amato_syntax_bbcode_anchor_revert($type, &$params)
{
    if (isset($params['u'])) {
        $params['u'] = (string)\Glay\Network\URI::here()
            ->combine(yN\Engine\Network\URL::to_page())
            ->combine($params['u']);
    }

    return true;
}

function amato_syntax_bbcode_command_revert($type, &$params)
{
    return false;
}

function amato_syntax_bbcode_emoji_convert($type, &$params)
{
    Glay\using('yN\\Engine\\Media\\Emoji', './engine/media/emoji.php');

    if (isset($params['c'])) {
        return yN\Engine\Media\Emoji::check_custom($params['n']);
    } else {
        return yN\Engine\Media\Emoji::check_native($params['n'], 'normal');
    }
}

function amato_syntax_bbcode_image_convert($type, &$params, $context)
{
    Glay\using('yN\\Engine\\Media\\Widget', './engine/media/widget.php');

    $url = _amato_syntax_bbcode_url($params['u']);

    if (!$url->valid) {
        return false;
    }

    $image = (string)$url;
    $widget = yN\Engine\Media\Widget::detect($image, $context->logger);

    if ($widget === null || $widget->type !== 'image') {
        return false;
    }

    if (isset($params['t']) && !is_numeric($params['t'])) {
        $url = _amato_syntax_bbcode_url($params['t']);

        if (!$url->valid) {
            return false;
        }

        $thumb = (string)$url;
        $widget = yN\Engine\Media\Widget::detect($thumb, $context->logger);

        if ($widget === null || $widget->type !== 'image') {
            return false;
        }

        $params['t'] = $thumb;
    }

    $params['c'] = $widget->code;
    $params['u'] = $image;

    return true;
}

function amato_syntax_bbcode_media_convert($type, &$params, $context)
{
    if ($context->nb_media >= 5) {
        return false;
    }

    ++$context->nb_media;

    Glay\using('yN\\Engine\\Media\\Widget', './engine/media/widget.php');

    $url = _amato_syntax_bbcode_url($params['u']);

    if (!$url->valid) {
        return false;
    }

    $detect = (string)$url;
    $widget = yN\Engine\Media\Widget::detect($detect, $context->logger);

    if ($widget === null) {
        return false;
    }

    if ($widget->code !== null) {
        $params['c'] = $widget->code;
    }

    $params['t'] = $widget->type;
    $params['u'] = $detect;

    return true;
}

function amato_syntax_bbcode_newline_convert($type, &$params, $context)
{
    if ($context->nb_newline >= 5) {
        return false;
    }

    ++$context->nb_newline;

    return true;
}

function amato_syntax_bbcode_ref_convert($type, &$params, $context)
{
    if (isset($params['t'])) {
        return true;
    } elseif ($context->topic_id === null) {
        return false;
    }

    $params['t'] = $context->topic_id;

    return true;
}

function amato_syntax_bbcode_slap_convert($type, &$params, $context)
{
    if ($context->user === null) {
        return false;
    }

    $params['s'] = $context->user->login;

    return true;
}
