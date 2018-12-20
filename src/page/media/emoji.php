<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Security\\Cost', './entity/security/cost.php');
Glay\using('yN\\Engine\\Media\\Emoji', './engine/media/emoji.php');

function emoji_edit($request, $logger, $sql, $display, $input, $user)
{
    // Submit changes
    if ($request->method === 'POST') {
        if ($user->id === null) {
            return Glay\Network\HTTP::code(401);
        }

        $alerts = array();

        if (!$input->get_image('emoji', $image, $name)) {
            $alerts[] = 'emoji-null';
        } elseif ($image === null) {
            $alerts[] = 'emoji-read';
        } else {
            if (strlen($image->data) > 64 * 1024 || $image->x < 10 || $image->x > 128 || $image->y < 10 || $image->y > 128) {
                $alerts[] = 'emoji-size';
            } elseif (yN\Engine\Media\Emoji::check_custom($name)) {
                $alerts[] = 'emoji-name';
            } elseif (!\yN\Entity\Security\Cost::accept($sql, 10)) {
                $alerts[] = 'emoji-cost';
            } elseif (!yN\Engine\Media\Emoji::put_custom($name, $image->data)) {
                $alerts[] = 'emoji-save';
            } else {
                $logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SYSTEM, 'upload', $user->login, 'uploaded emoji "' . $name . '"');

                $input->ensure('insert', yN\Engine\Media\Emoji::get_custom_tag($name));
            }

            $image->free();
        }
    } else {
        $alerts = null;
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-media-emoji-edit.deval', 'media.emoji.edit', array(
        'alerts' => $alerts
    )));
}

function emoji_list($request, $logger, $sql, $display, $input, $user)
{
    Glay\using('yN\\Engine\\Media\\Emoji', './engine/media/emoji.php');

    $prefix = trim($request->get_or_default('prefix', ''));
    $type = $request->get_or_fail('type');

    switch ($type) {
        case 'custom':
            if ($prefix !== '') {
                $emojis = yN\Engine\Media\Emoji::list_custom($request->router, $prefix);
                $search = false;
            } else {
                $emojis = array();
                $search = true;
            }

            break;

        default:
            $emojis = yN\Engine\Media\Emoji::list_native($type, $prefix);
            $search = false;

            break;
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-media-emoji-list.deval', 'media.emoji.list', array(
        'emojis' => $emojis,
        'search' => $search,
        'type' => $type
    )));
}
