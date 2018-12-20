<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Account\\Message', './entity/account/message.php');
Glay\using('yN\\Entity\\Board\\Ban', './entity/board/ban.php');
Glay\using('yN\\Entity\\Board\\Bookmark', './entity/board/bookmark.php');
Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
Glay\using('yN\\Entity\\Board\\Log', './entity/board/log.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
Glay\using('yN\\Entity\\Board\\Post', './entity/board/post.php');
Glay\using('yN\\Entity\\Board\\Reference', './entity/board/reference.php');
Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');

function post_edit($request, $logger, $sql, $display, $input, $user)
{
    global $address;
    global $time;

    $position = $request->get_or_default('position');
    $topic_id = (int)$request->get_or_fail('topic');

    // Get or create requested post
    if ($position !== null) {
        $reference = yN\Entity\Board\Reference::get_by_position($sql, $topic_id, (int)$position, $user->id);

        if ($reference === null) {
            return Glay\Network\HTTP::code(404);
        }

        $post = $reference->post;
        $topic = $reference->topic;

        $data = array_diff_key($post->revert(), $post->get_primary());
    } else {
        $topic = yN\Entity\Board\Topic::get_by_identifier($sql, $topic_id, $user->id);

        if ($topic === null) {
            return Glay\Network\HTTP::code(404);
        }

        $post = new yN\Entity\Board\Post();
        $post->create_profile_id = $user->id;

        $reference = new yN\Entity\Board\Reference();
        $reference->topic_id = $topic->id;

        $data = array();
    }

    $section = $topic->section;
    $access = yN\Entity\Board\Permission::check_by_topic($sql, $user, $section, $topic);
    $new = $post->id === null;

    // Check permissions
    if ($new ? !$topic->allow_reply($access) : !$post->allow_edit($access, $user->id)) {
        return Glay\Network\HTTP::code(403);
    }

    // Reject user if a ban is active
    if (yN\Entity\Board\Ban::check($sql, $section->forum_id, $address->string)) {
        return Glay\Network\HTTP::code(403);
    }

    // Get specified or parent forum
    $forum_id = (int)$request->get_or_default('forum', $section->forum_id);

    if ($section->forum_id !== $forum_id) {
        $forum = yN\Entity\Board\Forum::get_by_identifier($sql, $forum_id);
    } else {
        $forum = $section->forum;
    }

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Submit changes
    if ($request->method === 'POST') {
        if ($user->id === null) {
            return Glay\Network\HTTP::code(401);
        }

        $alerts = array();
        $permissions = array();
        $sends = array();

        // Disable edit for unconfirmed users
        if (!$user->is_active) {
            $alerts[] = 'user-active';
        }

        // Caution
        if ($topic->allow_moderate($access) && $input->get_string('caution', $caution)) {
            $post->caution = $caution ?: null;
        }

        // State
        if ($topic->allow_moderate($access) && $input->get_number('state', $state)) {
            $post->state = $state;
        }

        // Text
        if ($input->get_string('text', $text)) {
            // Handle special "!verb" commands
            if (preg_match_all('/^!(call|grant|invite|kick|reset) (.*?)(?: ([0-9]+)([dh]))?$/m', $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) > 0) {
                $i18n = new yN\Engine\Text\Internationalization($user->language);

                foreach (array_slice(array_reverse($matches), 0, 100) as $match) {
                    switch (isset($match[4][0]) ? $match[4][0] : '') {
                        case 'd':
                            $expire = (int)$match[3][0] * 86400 + $time;

                            break;

                        case 'h':
                            $expire = (int)$match[3][0] * 3600 + $time;

                            break;

                        default:
                            $expire = null;

                            break;
                    }

                    $login = $match[2][0];
                    $type = $match[1][0];

                    $target = yN\Entity\Board\Profile::get_by_user_login($sql, $login);

                    if ($target === null) {
                        $result = $i18n->format('yn.board.post.edit.command.target', array('login' => $login));
                    } elseif ($target->user_id === $user->id) {
                        $result = $i18n->format('yn.board.post.edit.command.self');
                    } else {
                        $permission = yN\Entity\Board\Permission::fetch_by_profile_topic($sql, $target->user_id, $topic->id);

                        if ($expire !== null) {
                            $permission->expire = $expire;
                        }

                        switch ($type) {
                            case 'call':
                                $allow = true;
                                $topic_position = $topic->posts + 1;
                                $url = Glay\Network\URI::here()->combine($request->router->url('board.topic.view', array('topic' => $topic->id, 'topic_hint' => $topic->hint, 'page' => yN\Entity\Board\Topic::get_page($topic_position), '_template' => null), 'post-' . $topic_position));

                                $message = new yN\Entity\Account\Message();
                                $message->convert_text($i18n->format('yn.board.post.edit.command.type.call.message', array('caller' => $user, 'name' => $topic->render_name('text', $request->router, $logger), 'url' => $url)), $request->router, $logger);
                                $message->sender_id = $user->id;

                                $sends[] = array($message, $target->user_id);

                                $permission->can_read = true;
                                $permission->can_write = true;
                                $result = $i18n->format('yn.board.post.edit.command.type.call', array('expire' => $expire, 'target' => $target));

                                break;

                            case 'grant':
                                $allow = $topic->allow_moderate($access);
                                $permission->can_change = true;
                                $permission->can_read = true;
                                $permission->can_write = true;
                                $result = $i18n->format('yn.board.post.edit.command.type.grant', array('expire' => $expire, 'target' => $target));

                                break;

                            case 'invite':
                            case 'reset':
                                $allow = $topic->allow_moderate($access);
                                $permission->can_change = null;
                                $permission->can_read = null;
                                $permission->can_write = null;
                                $result = $i18n->format('yn.board.post.edit.command.type.reset', array('expire' => $expire, 'target' => $target));

                                break;

                            case 'kick':
                                $allow = $topic->allow_moderate($access);
                                $permission->can_write = false;
                                $result = $i18n->format('yn.board.post.edit.command.type.kick', array('expire' => $expire, 'target' => $target));

                                break;

                            default:
                                $allow = true;
                                $result = $i18n->format('yn.board.post.edit.command.unknown', array('type' => $type));

                                break;
                        }

                        if ($allow) {
                            $permissions[] = $permission;
                        } else {
                            $result = $i18n->format('yn.board.post.edit.command.access', array('type' => $type));
                        }
                    }

                    // FIXME: should be non-bbcode compatible [markup-bbcode]
                    $text = substr_replace($text, '[yncMd:159]' . $result . '[/yncMd:159]', $match[0][1], strlen($match[0][0]));
                }
            }

            // FIXME: special /me tag [markup-bbcode]
            $text = preg_replace("/^(\/me)([^\r\n]+)/ism", "[b][color=909]\xE2\x80\xA2 " . $user->login . " $2[/color][/b]\n", $text);

            // Flag edit if modified by non-author, already modified before, or not in last position
            if ($post->create_profile_id !== $user->id || $post->edit_profile_id !== null || ($reference->position !== null && $reference->position < $topic->posts)) {
                $post->edit_profile_id = $user->id;
                $post->edit_time = $time;
            }

            $post->convert_text($text, $request->router, $logger, $topic->id);
        } else {
            $text = $post->revert_text();
        }

        // Save
        if (count($alerts) === 0) {
            if (!$post->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                $reference->post_id = $post->id;

                if ($reference->position === null && !$reference->save($sql, $alert)) {
                    $alerts[] = 'reference-' . $alert;
                } else {
                    // Save pending permission changes
                    foreach ($permissions as $permission) {
                        if (!$permission->save($sql, $alert)) {
                            $alerts[] = 'permission-' . $alert;
                        }
                    }

                    // Send pending messages
                    foreach ($sends as $send) {
                        list($message, $recipient_id) = $send;

                        if (!yN\Entity\Account\Message::send($sql, $message, array($recipient_id), $alert)) {
                            $alerts[] = 'message-' . $alert;
                        }
                    }

                    // Track and watch parent topic on new post
                    if ($new) {
                        $topic->last_time = $time;
                        $topic->save($sql, $alert);

                        yN\Entity\Board\Profile::invalidate($sql, $user->id);
                        yN\Entity\Board\Section::invalidate($sql, $section->id);
                        yN\Entity\Board\Subscription::set_position($sql, $section->id, $topic->id, $reference->position - 1);
                    }

                    // Invalidate & track parent topic on new or edit last post
                    if ($new || ($reference->position === $topic->posts && !$topic->is_closed)) {
                        if (!$input->get_number('posts', $posts)) {
                            $posts = $topic->posts;
                        }

                        yN\Entity\Board\Bookmark::set_fresh($sql, $topic->id, $reference->position - 1);
                        yN\Entity\Board\Bookmark::set_watch($sql, $user->id, $topic->id, min($reference->position, $posts + 1), $reference->position > $posts + 1, true);
                        yN\Entity\Board\Section::set_fresh($sql, $section->id, $user->id);
                    }

                    yN\Entity\Board\Log::push($sql, $section->forum_id, $user->id, 'post.edit', array_merge(
                        $reference->get_primary(),
                        array_diff_assoc($post->revert(), $data),
                        $new ? array('new' => true) : array()
                    ));
                }
            }
        }
    } else {
        $quote = $request->get_or_default('quote');

        if ($quote !== null) {
            $quote_reference = yN\Entity\Board\Reference::get_by_position($sql, $topic->id, (int)$quote, $user->id);

            if ($quote_reference === null) {
                return Glay\Network\HTTP::code(404);
            }

            $quote_post = $quote_reference->post;

            if (!$quote_post->allow_view($access, $user->id)) {
                return Glay\Network\HTTP::code(403);
            }

            $i18n = new yN\Engine\Text\Internationalization($user->language);
            $context = $i18n->format('yn.board.post.edit.context', array(
                'position' => $quote_reference->position,
                'post' => $quote_post,
                'topic' => $topic
            ));

            // FIXME: should be non-bbcode compatible [markup-bbcode]
            $text = '[quote][b]' . $context . "[/b]\n" . $quote_post->revert_text() . '[/quote]';
        } else {
            $text = $post->revert_text();
        }

        $alerts = null;
    }

    $input->ensure('caution', $post->caution ?: '');
    $input->ensure('state', $post->state);
    $input->ensure('text', $text);

    // Render template
    $location = 'board.forum.' . $forum->id . '.' . $topic->section_id . '.' . $topic->id . '.' . ($reference->position ?: 0) . '.edit';

    return Glay\Network\HTTP::data($display->render('yn-board-post-edit.deval', $location, array(
        'access' => $access,
        'alerts' => $alerts,
        'forum' => $forum,
        'new' => $new,
        'post' => $post,
        'reference' => $reference,
        'topic' => $topic
    ), $forum->template));
}

function post_report($request, $logger, $sql, $display, $input, $user)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $reference = yN\Entity\Board\Reference::get_by_position($sql, (int)$request->get_or_fail('topic'), (int)$request->get_or_fail('position'), $user->id);

    if ($reference === null) {
        return Glay\Network\HTTP::code(404);
    }

    $post = $reference->post;
    $topic = $reference->topic;
    $section = $topic->section;
    $access = yN\Entity\Board\Permission::check_by_topic($sql, $user, $section, $topic);

    if (!$post->allow_view($access, $user->id)) {
        return Glay\Network\HTTP::code(403);
    }

    // Search for moderators
    $recipients = array();

    foreach (yN\Entity\Board\Permission::get_by_forum($sql, $section->forum_id) as $permission) {
        if ($permission->can_change) {
            $admin = yN\Entity\Account\User::get_by_identifier($sql, $permission->profile_id);

            if ($admin !== null) {
                $recipients[$admin->id] = $admin->login;
            }
        }
    }

    foreach (yN\Entity\Board\Permission::get_by_section($sql, $topic->section_id) as $permission) {
        if ($permission->can_change) {
            $admin = yN\Entity\Account\User::get_by_identifier($sql, $permission->profile_id);

            if ($admin !== null) {
                $recipients[$admin->id] = $admin->login;
            }
        }
    }

    foreach (yN\Entity\Account\User::get_by_is_admin($sql) as $admin) {
        $recipients[$admin->id] = $admin->login;
    }

    // Prepare message text
    $position = $reference->position;
    $url = $request->router->url('board.topic.view', array('topic' => $topic->id, 'topic_hint' => $topic->hint, 'page' => yN\Entity\Board\Topic::get_page($position), '_template' => null), 'post-' . $position);
    $absolute = Glay\Network\URI::here()->combine($url);

    $i18n = new yN\Engine\Text\Internationalization($user->language);
    $text = $i18n->format('yn.board.post.report.text', array(
        'author' => $user,
        'reason' => $input->get_string('reason', $reason) ? $reason : '',
        'url' => $absolute
    ));

    // Send message
    $message = new yN\Entity\Account\Message();
    $message->convert_text($text, $request->router, $logger);
    $message->sender_id = $user->id;

    $alerts = array();

    if (!yN\Entity\Account\Message::send($sql, $message, array_keys($recipients), $alert)) {
        $alerts[] = $alert;
    }

    $location = 'board.forum.' . $section->forum_id . '.' . $topic->section_id . '.' . $topic->id . '.' . $reference->position . '.report';

    return Glay\Network\HTTP::data($display->render('yn-board-post-report.deval', $location, array(
        'alerts' => $alerts
    ), $section->forum->template));
}

function post_view($request, $logger, $sql, $display, $input, $user)
{
    $reference = yN\Entity\Board\Reference::get_by_position($sql, (int)$request->get_or_fail('topic'), (int)$request->get_or_fail('position'), $user->id);

    if ($reference === null) {
        return Glay\Network\HTTP::code(404);
    }

    $post = $reference->post;
    $topic = $reference->topic;
    $section = $topic->section;
    $access = yN\Entity\Board\Permission::check_by_topic($sql, $user, $section, $topic);

    if (!$post->allow_view($access, $user->id)) {
        yN\Entity\Board\Bookmark::delete_by_profile__topic($sql, $user->id, $topic->id);

        return Glay\Network\HTTP::code(403);
    }

    // Get specified or parent forum
    $forum_id = (int)$request->get_or_default('forum', $section->forum_id);

    if ($section->forum_id !== $forum_id) {
        $forum = yN\Entity\Board\Forum::get_by_identifier($sql, $forum_id);
    } else {
        $forum = $section->forum;
    }

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Render template
    $location = 'board.forum.' . $forum->id . '.' . $topic->section_id . '.' . $topic->id . '.' . $reference->position;

    return Glay\Network\HTTP::data($display->render('yn-board-post-view.deval', $location, array(
        'access' => $access,
        'forum' => $forum,
        'reference' => $reference,
        'topic' => $topic // FIXME: topic is required because reference.topic.section is null when accessed in board.post.view frame through board.topic.view
    ), $forum->template));
}
