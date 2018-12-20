<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Account\\Message', './entity/account/message.php');

function message_delete($request, $logger, $sql, $display, $input, $user)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $message = yN\Entity\Account\Message::get_by_identifier__sender($sql, (int)$request->get_or_fail('message'), $user->id);

    if ($message === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Submit changes
    if ($request->method === 'POST') {
        $alerts = array();

        if (!yN\Entity\Account\Message::delete_by_identifier($sql, $message->id)) {
            $alerts[] = 'save';
        }
    } else {
        $alerts = null;
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-account-message-delete.deval', 'account.message.delete', array(
        'alerts' => $alerts,
        'message' => $message
    )));
}

function message_edit($request, $logger, $sql, $display, $input, $user)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $message_id = $request->get_or_default('message');
    $reply_id = $request->get_or_default('reply');

    // Get or create requested message
    if ($message_id !== null) {
        $message = yN\Entity\Account\Message::get_by_identifier__sender($sql, (int)$message_id, $user->id);

        if ($message === null) {
            return Glay\Network\HTTP::code(404);
        }

        $reply = null;
    } else {
        $message = new yN\Entity\Account\Message();
        $message->sender_id = $user->id;

        $reply = $reply_id !== null ? yN\Entity\Account\Message::get_by_identifier__recipient($sql, (int)$reply_id, $user->id) : null;
    }

    // Submit changes
    $new = $message->id === null;

    if ($request->method === 'POST') {
        $alerts = array();
        $recipients = array($user->id => true);

        // Create boxes when message is sent for the first time
        if ($input->get_string('to', $to) && $new) {
            foreach (array_slice(array_filter(array_map('trim', explode(',', $to)), 'strlen'), 0, 50) as $login) {
                $recipient = yN\Entity\Account\User::get_by_login($sql, $login);

                if ($recipient !== null) {
                    $recipients[$recipient->id] = true;
                } else {
                    $alerts[] = 'login-unknown';
                }
            }
        }

        if (count($recipients) < 2 && $new) {
            $alerts[] = 'login-empty';
        }

        // Set text
        if ($input->get_string('text', $text)) {
            $message->convert_text($text, $request->router, $logger);
        }

        // Save
        if (count($alerts) === 0) {
            if (!yN\Entity\Account\Message::send($sql, $message, array_keys($recipients), $alert)) {
                $alerts[] = $alert;
            }
        }
    } else {
        $alerts = null;
    }

    $input->ensure('text', $message->revert_text());

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-account-message-edit.deval', 'account.message.edit', array(
        'alerts' => $alerts,
        'message' => $message,
        'new' => $new,
        'reply' => $reply
    )));
}

function message_hide($request, $logger, $sql, $display, $input, $user)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $message = yN\Entity\Account\Message::get_by_identifier__recipient($sql, (int)$request->get_or_fail('message'), $user->id);

    if ($message === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Submit changes
    if ($request->method === 'POST') {
        $alerts = array();

        if (!yN\Entity\Account\Message::hide_copy($sql, $message->id, $user->id)) {
            $alerts[] = 'save';
        }
    } else {
        $alerts = null;
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-account-message-hide.deval', 'account.message.hide', array(
        'alerts' => $alerts,
        'message' => $message
    )));
}

function message_list($request, $logger, $sql, $display, $input, $user)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $count = 20;
    $from = $request->get_or_default('from');
    $login = (string)$request->get_or_default('login', '');

    if ($login !== '') {
        $other = yN\Entity\Account\User::get_by_login($sql, $login);
        $other_id = $other !== null ? $other->id : 0;
    } else {
        $other_id = null;
    }

    $messages = yN\Entity\Account\Message::get_by_recipient($sql, $user->id, $other_id, $from, $count + 1);

    if (count($messages) > $count) {
        $messages = array_slice($messages, 0, $count);
        $prev = $messages[$count - 1][0]->id;
    } else {
        $prev = null;
    }

    yN\Entity\Account\Message::read_all($sql, $user->id);

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-account-message-list.deval', 'account.message', array(
        'from' => $from,
        'login' => $login ?: null,
        'messages' => $messages,
        'prev' => $prev
    )));
}
