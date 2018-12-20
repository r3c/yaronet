<?php

defined('YARONET') or die;

Glay\using('yN\\Engine\\Network\\Email', './engine/network/email.php');
Glay\using('yN\\Engine\\Network\\URL', './engine/network/url.php');
Glay\using('yN\\Entity\\Account\\Activity', './entity/account/activity.php');
Glay\using('yN\\Entity\\Account\\Application', './entity/account/application.php');
Glay\using('yN\\Entity\\Account\\User', './entity/account/user.php');
Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');

function _user_secure(&$url)
{
    $url = Glay\Network\URI::here();

    if (config('engine.network.http.insecure', false) || $url->scheme === 'https') {
        return false;
    }

    $url->scheme = 'https';

    return true;
}

function user_active($request, $logger, $sql, $display, $input, $user)
{
    $active = yN\Entity\Account\User::get_by_identifier($sql, (int)$request->get_or_fail('user'));

    if ($active === null) {
        return Glay\Network\HTTP::code(404);
    }

    $code = trim($request->get_or_default('code', ''));

    if ($code !== '') {
        $alerts = array();
        $recover = $active->get_recover();

        // Verify recover code
        if (!$active->is_active) {
            if ($recover === null) {
                $alerts[] = 'code-expire';
            } elseif ($recover !== $code) {
                $alerts[] = 'code-invalid';
            }
        }

        // Save
        if (count($alerts) === 0) {
            $active->is_active = true;
            $active->recover_time = 0;

            if (!$active->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                $logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SYSTEM, 'user', $active->id, 'active');
            }
        }
    } else {
        $alerts = null;
    }

    // Render template
    $location = 'account.user.' . $active->id . '.active';

    return Glay\Network\HTTP::data($display->render('yn-account-user-active.deval', $location, array(
        'active' => $active,
        'alerts' => $alerts
    )));
}

function user_edit($request, $logger, $sql, $display, $input, $user)
{
    global $time;

    if (_user_secure($url)) {
        return Glay\Network\HTTP::go($url);
    }

    $forum_id = $request->get_or_default('forum');
    $user_id = $request->get_or_default('user');

    // Get or create user
    if ($user_id !== null) {
        if ($user_id === null) {
            return Glay\Network\HTTP::code(401);
        }

        $edit = yN\Entity\Account\User::get_by_identifier($sql, (int)$user_id);

        if ($edit === null) {
            return Glay\Network\HTTP::code(404);
        }

        // Clone reference when editing current user so that changes apply on this display
        if ($edit->id === $user->id) {
            $edit = $user;
        }

        // Cannot apply any change to another user unless administrator
        elseif (!$user->is_admin) {
            return Glay\Network\HTTP::code(403);
        }

        $profile = null;
    } else {
        if ($forum_id !== null) {
            $forum = yN\Entity\Board\Forum::get_by_identifier($sql, (int)$forum_id);
        } else {
            $forum = null;
        }

        $edit = new yN\Entity\Account\User();

        if ($forum !== null && $forum->template !== null) {
            $edit->is_uniform = true;
            $edit->template = $forum->template;
        }

        $profile = new yN\Entity\Board\Profile();
        $profile->forum_id = $forum !== null ? $forum->id : null;
    }

    // Submit changes
    $new = $edit->id === null;

    if ($request->method === 'POST') {
        $alerts = array();
        $change_email = false;
        $change_login = false;
        $change_password = false;

        // Captcha validation
        if ($new) {
            Glay\using('yN\\Engine\\Service\\ReCaptchaAPI', './engine/service/recaptcha.php');

            $captcha = new yN\Engine\Service\ReCaptchaAPI();

            $input->get_string('g-recaptcha-response', $captcha_response);

            if (!$captcha->check($captcha_response)) {
                $alerts[] = 'captcha-invalid';
            }
        }

        // Login update
        if ($input->get_string('login', $login)) {
            $edit->login = $login;

            $change_login = true;
        }

        // Password update
        if (($input->get_string('password-1', $password1) && $input->get_string('password-2', $password2) && $input->get_string('password', $password) && $password !== '') || $new) {
            if (!$new && (!$user->is_admin || $edit->id === $user->id) && yN\Entity\Account\User::authenticate_login($sql, $user->login, $password) === null) {
                $alerts[] = 'password-invalid';
            } elseif ($password1 === '') {
                $alerts[] = 'password-empty';
            } elseif ($password1 !== $password2) {
                $alerts[] = 'password-match';
            } else {
                $edit->set_secret($edit->mechanism, $password1);

                $change_password = true;
            }
        }

        // Active flag update
        if ($input->get_boolean('active', $active) && $user->is_admin) {
            $edit->is_active = $active;
        }

        // Admin flag update
        if ($input->get_boolean('admin', $admin) && $user->is_admin) {
            $edit->is_admin = $admin;
        }

        // Disabled flag update
        if ($input->get_boolean('disabled', $disabled) && $user->is_admin) {
            $edit->is_disabled = $disabled;
        }

        // Email update
        if (($input->get_string('email', $email) && $edit->email !== $email) || $new) {
            $edit->email = $email;
            $edit->is_active = false;
            $edit->recover_time = $time + yN\Entity\Account\User::TIME_RECOVER;

            $change_email = true;
        }

        // Flags
        if ($input->get_array('flags', $flags)) {
            $edit->is_uniform = isset($flags['uniform']);
        }

        // Language
        if ($input->get_string('language', $language)) {
            $edit->language = yN\Engine\Text\Internationalization::is_valid($language) ? $language : yN\Engine\Text\Internationalization::default_language();
        }

        // Options
        if ($input->get_array('options', $options)) {
            $edit->options = array_map(function ($v) {
                return $v ? '1' : null;
            }, array_slice($options, 0, 16));
        }

        // Template
        if ($input->get_string('template', $template)) {
            $edit->template = yN\Engine\Text\Display::is_option($template) ? $template : yN\Engine\Text\Display::default_template();
        }

        // Save
        if (count($alerts) === 0) {
            if (!$edit->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                // Create associated board profile if any
                if ($profile !== null) {
                    $profile->user_id = $edit->id;

                    if (!$profile->save($sql, $alert)) {
                        $alerts[] = 'profile-' . $alert;
                    }
                }

                // Send email on account creation or email address change
                if ($change_email) {
                    if ($edit->id === $user->id || $new) {
                        $recover = $edit->get_recover();
                        $url = Glay\Network\URI::here()->combine($request->router->url('account.user.active', array('code' => $recover, 'user' => $edit->id)));

                        $i18n = new yN\Engine\Text\Internationalization($edit->language);
                        $email_body = $i18n->format('yn.account.user.edit.email.body', array('new' => $new, 'recover' => $recover, 'url' => $url, 'user' => $edit));
                        $email_subject = $i18n->format('yn.account.user.edit.email', array('new' => $new));

                        $email = new yN\Engine\Network\Email($logger);

                        // Activate account if email couldn't be sent
                        if (!$email->send($email_subject, $email_body, array($edit->email => $edit->login))) {
                            $edit->is_active = true;

                            $edit->save($sql, $alert);
                        }
                    }

                    $logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SYSTEM, 'user', $edit->id, 'change email to "' . $edit->email . '"');
                }

                if ($change_login) {
                    $logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SYSTEM, 'user', $edit->id, 'change nick to "' . $edit->login . '"');
                }

                if ($change_password) {
                    if ($edit->id === $user->id || $user->id === null) {
                        $token = $edit->get_token($time + yN\Entity\Account\User::TIME_EXPIRE);

                        if ($token !== null) {
                            setcookie(yN\Entity\Account\User::COOKIE_NAME, $token, 0, yN\Engine\Network\URL::to_page(), yN\Entity\Account\User::COOKIE_DOMAIN);
                        }
                    }

                    $logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SYSTEM, 'user', $edit->id, 'change password');
                }
            }
        }
    } else {
        $alerts = null;
    }

    $input->ensure('active', $edit->is_active ? 1 : 0);
    $input->ensure('admin', $edit->is_admin ? 1 : 0);
    $input->ensure('disabled', $edit->is_disabled ? 1 : 0);
    $input->ensure('email', $edit->email);
    $input->ensure('flags', array('uniform' => $edit->is_uniform ?: null));
    $input->ensure('language', $edit->language);
    $input->ensure('login', $edit->login);
    $input->ensure('options', array_map(function ($v) {
        return $v ? '1' : null;
    }, $edit->options));
    $input->ensure('template', $edit->template);

    // Render template
    $location = 'account.user.' . $edit->id . '.edit';

    return Glay\Network\HTTP::data($display->render('yn-account-user-edit.deval', $location, array(
        'alerts' => $alerts,
        'edit' => $edit,
        'forum_id' => $forum_id,
        'new' => $new
    )));
}

function user_reclaim($request, $logger, $sql, $display, $input, $user)
{
    global $time;

    if (_user_secure($url)) {
        return Glay\Network\HTTP::go($url);
    }

    $reclaim = yN\Entity\Account\User::get_by_identifier($sql, (int)$request->get_or_fail('user'));

    if ($reclaim === null) {
        return Glay\Network\HTTP::code(404);
    }

    $recover = $reclaim->get_recover();

    if ($request->method === 'POST') {
        $alerts = array();

        // Verify recover code
        if ($recover === null) {
            $alerts[] = 'code-expire';
        } elseif (!$input->get_string('code', $code) || $code !== $recover) {
            $alerts[] = 'code-invalid';
        }

        // Password update
        if ($input->get_string('password-1', $password1) && $input->get_string('password-2', $password2)) {
            if ($password1 === '') {
                $alerts[] = 'password-empty';
            } elseif ($password1 !== $password2) {
                $alerts[] = 'password-match';
            } else {
                $reclaim->set_secret($reclaim->mechanism, $password1);
            }
        }

        // Save
        if (count($alerts) === 0) {
            $reclaim->is_active = true;
            $reclaim->recover_time = 0;

            if (!$reclaim->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                if ($reclaim->id === $user->id || $user->id === null) {
                    $token = $reclaim->get_token($time + yN\Entity\Account\User::TIME_EXPIRE);

                    if ($token !== null) {
                        setcookie(yN\Entity\Account\User::COOKIE_NAME, $token, 0, yN\Engine\Network\URL::to_page(), yN\Entity\Account\User::COOKIE_DOMAIN);
                    }
                }

                $logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SYSTEM, 'user', $reclaim->id, 'reclaim account');
            }
        }
    } else {
        $alerts = null;
    }

    // Render template
    $location = 'account.user.' . $reclaim->id . '.reclaim';

    return Glay\Network\HTTP::data($display->render('yn-account-user-reclaim.deval', $location, array(
        'alerts' => $alerts,
        'reclaim' => $reclaim
    )));
}

function user_recover($request, $logger, $sql, $display, $input, $user)
{
    global $time;

    if ($request->method === 'POST') {
        $alerts = array();

        // Captcha validation
        Glay\using('yN\\Engine\\Service\\ReCaptchaAPI', './engine/service/recaptcha.php');

        $captcha = new yN\Engine\Service\ReCaptchaAPI();

        $input->get_string('g-recaptcha-response', $captcha_response);

        if (!$captcha->check($captcha_response)) {
            $alerts[] = 'captcha-invalid';
        }

        // Email lookup
        $lost = $input->get_string('email', $email) ? yN\Entity\Account\User::get_by_email($sql, $email) : null;

        if ($lost === null) {
            $alerts[] = 'email-unknown';
        } else {
            $lost->recover_time = $time + yN\Entity\Account\User::TIME_RECOVER;
        }

        // Save
        if (count($alerts) === 0) {
            if (!$lost->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                // Send email
                $recover = $lost->get_recover();
                $url_active = Glay\Network\URI::here()->combine($request->router->url('account.user.active', array('code' => $recover, 'user' => $lost->id)));
                $url_reclaim = Glay\Network\URI::here()->combine($request->router->url('account.user.reclaim', array('code' => $recover, 'user' => $lost->id)));

                $i18n = new yN\Engine\Text\Internationalization($lost->language);
                $email_body = $i18n->format('yn.account.user.recover.email.body', array('recover' => $recover, 'url_active' => $url_active, 'url_reclaim' => $url_reclaim, 'user' => $lost));
                $email_subject = $i18n->format('yn.account.user.recover.email');

                $email = new yN\Engine\Network\Email($logger);
                $email->send($email_subject, $email_body, array($lost->email => $lost->login));

                $logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SYSTEM, 'user', $lost->id, 'recover password');
            }
        }
    } else {
        $alerts = null;
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-account-user-recover.deval', 'account.user.recover', array(
        'alerts' => $alerts
    )));
}

function user_signin($request, $logger, $sql, $display, $input, $user)
{
    global $time;

    if (_user_secure($url)) {
        return Glay\Network\HTTP::go($url);
    }

    if ($request->method === 'POST') {
        $alerts = array();

        if ($input->get_number('expire', $expire)) {
            $expire_cookie = $time + $expire;
            $expire_token = $expire_cookie;
        } else {
            $expire_cookie = 0;
            $expire_token = $time + yN\Entity\Account\User::TIME_EXPIRE;
        }

        $signin = $input->get_string('login', $login) && $input->get_string('password', $password)
            ? yN\Entity\Account\User::authenticate_login($sql, $login, $password)
            : null;

        if ($signin === null) {
            $alerts[] = 'fail';
        }

        // Write authentication token to cookies
        else {
            $token = $signin->get_token($expire_token);

            if ($token !== null) {
                setcookie(yN\Entity\Account\User::COOKIE_NAME, $token, $expire_cookie, yN\Engine\Network\URL::to_page(), yN\Entity\Account\User::COOKIE_DOMAIN);
            }
        }
    } else {
        $input->ensure('expire', 8640000);

        $alerts = null;
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-account-user-signin.deval', 'account.user.signin', array(
        'alerts' => $alerts,
        'target' => $input->get_string('target', $target) ? $target : null
    )));
}

function user_signout($request, $logger, $sql, $display, $input, $user)
{
    global $address;

    yN\Entity\Account\Activity::leave($sql, $address->string);

    setcookie(yN\Entity\Account\User::COOKIE_NAME, '', 0, yN\Engine\Network\URL::to_page(), yN\Entity\Account\User::COOKIE_DOMAIN);

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-account-user-signout.deval', 'account.user.signout'));
}

function user_view($request, $logger, $sql, $display, $input, $user)
{
    $user_id = $request->get_or_default('user', $user->id);

    if ($user_id === null) {
        return Glay\Network\HTTP::code(401);
    }

    return Glay\Network\HTTP::go($request->router->url('board.profile.view', array('profile' => $user_id)));
}
