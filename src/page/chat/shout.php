<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Chat\\Shout', './entity/chat/shout.php');

function shout_delete($request, $logger, $sql, $display, $input, $user)
{
    if (!$user->is_admin) {
        return Glay\Network\HTTP::code(403);
    }

    $shout = yN\Entity\Chat\Shout::get_by_id($sql, (int)$request->get_or_fail('shout'));

    if ($shout === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Submit changes
    if ($request->method === 'POST') {
        $alerts = array();

        if (!$shout->delete($sql, $alert)) {
            $alerts[] = $alert;
        }
    } else {
        $alerts = null;
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-chat-shout-delete.deval', 'chat.shout.' . $shout->id . '.delete', array(
        'alerts'	=> $alerts,
        'shout'		=> $shout
    )));
}

function shout_edit($request, $logger, $sql, $display, $input, $user)
{
    global $time;

    $shout = new yN\Entity\Chat\Shout();

    // Save changes
    if ($request->method === 'POST') {
        $alerts = array();

        // Captcha validation
        if ($user->id === null) {
            Glay\using('yN\\Engine\\Service\\ReCaptchaAPI', './engine/service/recaptcha.php');

            $captcha = new yN\Engine\Service\ReCaptchaAPI();

            $input->get_string('g-recaptcha-response', $captcha_response);

            if (!$captcha->check($captcha_response)) {
                $alerts[] = 'captcha-invalid';
            }
        }

        // Nick
        if ($user->id !== null) {
            $shout->is_guest = false;
            $shout->nick = $user->login;
        } else {
            $input->get_string('nick', $nick);

            $shout->is_guest = true;
            $shout->nick = trim($nick);

            if (yN\Entity\Account\User::get_by_login($sql, $shout->nick) !== null) {
                $alerts[] = 'nick-user';
            }
        }

        // Text
        if ($input->get_string('text', $text)) {
            $shout->convert_text($text, $request->router, $logger);
        }

        // Save
        if (count($alerts) === 0) {
            if (!$shout->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                $words = explode(' ', $shout->revert_text());

                if (count($words) >= 2 && $words[0] === '!seen') {
                    $i18n = new yN\Engine\Text\Internationalization($user->language);

                    $reply = new yN\Entity\Chat\Shout();
                    $reply->is_guest = false;
                    $reply->nick = 'Boo';

                    if ($words[1] === $user->login) {
                        $text = $i18n->format('yn.chat.shout.edit.command.seen.self');
                    } else {
                        $seen_user = yN\Entity\Account\User::get_by_login($sql, implode(' ', array_slice($words, 1)));

                        if ($seen_user !== null) {
                            $text = $i18n->format('yn.chat.shout.edit.command.seen.duration', array('elapsed' => $time - $seen_user->pulse_time, 'login' => $seen_user->login));
                        } else {
                            $text = $i18n->format('yn.chat.shout.edit.command.seen.unknown');
                        }
                    }

                    $reply->convert_text($text, $request->router, $logger);
                    $reply->save($sql, $alert);
                }
            }
        }
    } else {
        $alerts = null;
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-chat-shout-edit.deval', 'chat.shout.' . ($shout->id ?: 0) . '.edit', array(
        'alerts'	=> $alerts,
        'shout'		=> $shout
    )));
}
