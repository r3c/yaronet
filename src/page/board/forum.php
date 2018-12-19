<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Block', './entity/board/block.php');
Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
Glay\using('yN\\Entity\\Board\\Log', './entity/board/log.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');
Glay\using('yN\\Engine\\Media\\Binary', './engine/media/binary.php');

function forum_active($request, $logger, $sql, $display, $input, $user)
{
    // Get forum by identifier
    $forum = yN\Entity\Board\Forum::get_by_identifier($sql, (int)$request->get_or_fail('forum'));

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Get latest active topics in forum
    $topics = yN\Entity\Board\Topic::get_by_last_time_forum($sql, $forum->id, yN\Entity\Board\Topic::ORDER_LAST, yN\Entity\Board\Topic::PAGE_SIZE, $user->id);

    // Render template
    $location = 'board.forum.' . $forum->id . '.active';

    return Glay\Network\HTTP::data($display->render('yn-board-forum-active.deval', $location, array(
        'forum'		=> $forum,
        'topics'	=> $topics
    ), $forum->template));
}

function forum_edit($request, $logger, $sql, $display, $input, $user)
{
    $forum_id = $request->get_or_default('forum');

    // Get or create requested forum
    if ($forum_id !== null) {
        $forum = yN\Entity\Board\Forum::get_by_identifier($sql, (int)$forum_id);

        if ($forum === null) {
            return Glay\Network\HTTP::code(404);
        }

        if (!$forum->allow_edit(yN\Entity\Board\Permission::check_by_forum($sql, $user, $forum))) {
            return Glay\Network\HTTP::code(403);
        }

        $data = array_diff_key($forum->revert(), $forum->get_primary());
    } else {
        $forum = new yN\Entity\Board\Forum();
        $data = array();
    }

    // Submit changes
    $new = $forum->id === null;

    if ($request->method === 'POST') {
        if ($user->id === null) {
            return Glay\Network\HTTP::code(401);
        }

        $alerts = array();
        $icon = null;

        // Disable edit for unconfirmed users
        if (!$user->is_active) {
            $alerts[] = 'user-active';
        }

        // Alias
        if ($input->get_string('alias', $alias)) {
            $forum->alias = trim($alias) ?: null;
        }

        // Description
        if ($input->get_string('description', $description)) {
            $forum->description = $description;
        }

        // Flags
        if ($input->get_array('flags', $flags)) {
            $forum->is_hidden = isset($flags['hidden']);
        }

        // Header
        if ($input->get_string('header', $header)) {
            $forum->convert_header(trim($header) ?: null, $request->router, $logger);
        }

        // Icon
        if ($input->get_image('icon', $image, $name)) {
            if ($image === null) {
                $alerts[] = 'icon-read';
            } else {
                // Try to convert to resized PNG if possible or keep original data otherwise
                $image->clamp(256);

                $icon = $image->create_png() ?: $image->data;

                $image->free();

                $forum->icon_tag = (int)sprintf('%u', crc32($icon));
                $forum->is_illustrated = true;
            }
        } elseif ($input->get_string('icon', $icon_source) && $icon_source === 'void') {
            $icon = '';

            $forum->icon_tag = 0;
            $forum->is_illustrated = false;
        }

        // Name
        if ($input->get_string('name', $name)) {
            $forum->name = trim($name);
        }

        // Preface
        if ($input->get_string('preface', $preface)) {
            $forum->convert_preface(trim($preface) ?: null, $request->router, $logger);
        }

        // Template
        if ($input->get_string('template', $template)) {
            $forum->template = yN\Engine\Text\Display::is_option($template) ? $template : null;
        }

        // Save
        if (count($alerts) === 0) {
            if (!$forum->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                // Update forum icon
                if ($icon !== null) {
                    if ($icon === '') {
                        yN\Engine\Media\Binary::delete('forum-' . $forum->id);
                    } else {
                        yN\Engine\Media\Binary::put('forum-' . $forum->id, $icon);
                    }
                }

                // Set default permissions
                if ($new) {
                    $permission_forum = yN\Entity\Board\Permission::fetch_by_profile_forum($sql, $user->id, $forum->id);
                    $permission_forum->can_change = true;
                    $permission_forum->can_read = true;
                    $permission_forum->can_write = true;

                    if (!$permission_forum->save($sql, $alert)) {
                        $alerts[] = 'permission-' . $alert;
                    }
                }

                yN\Entity\Board\Log::push($sql, $forum->id, $user->id, 'forum.edit', array_merge(
                    array_diff_assoc($forum->revert(), $data),
                    $new ? array('new' => true) : array()
                ));
            }
        }
    } else {
        $alerts = null;
    }

    $input->ensure('alias', $forum->alias);
    $input->ensure('description', $forum->description);
    $input->ensure('flags', array('hidden' => $forum->is_hidden ?: null));
    $input->ensure('header', $forum->revert_header());
    $input->ensure('name', $forum->name);
    $input->ensure('preface', $forum->revert_preface());
    $input->ensure('template', $forum->template);

    // Render template
    $location = 'board.forum.' . ($forum->id ?: 0) . '.edit';

    return Glay\Network\HTTP::data($display->render('yn-board-forum-edit.deval', $location, array(
        'alerts'	=> $alerts,
        'forum'		=> $forum,
        'new'		=> $new
    ), $forum->template));
}

function forum_list($request, $logger, $sql, $display, $input, $user)
{
    // Search forums by name
    $query = (string)$request->get_or_default('query', '');

    $forums = $query ?
        yN\Entity\Board\Forum::get_by_name_match($sql, $query) :
        yN\Entity\Board\Forum::get_random($sql, 10);

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-board-forum-list.deval', 'board.forum', array(
        'forums'	=> $forums
    )));
}

function forum_organize($request, $logger, $sql, $display, $input, $user)
{
    $forum = yN\Entity\Board\Forum::get_by_identifier($sql, (int)$request->get_or_fail('forum'));

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    $access = yN\Entity\Board\Permission::check_by_forum($sql, $user, $forum);

    if (!$forum->allow_edit($access)) {
        return Glay\Network\HTTP::code(403);
    }

    // Get forum blocks
    $blocks = yN\Entity\Board\Block::get_by_forum($sql, $forum->id, $user->id);

    // Render template
    $location = 'board.forum.' . $forum->id . '.organize';

    return Glay\Network\HTTP::data($display->render('yn-board-forum-organize.deval', $location, array(
        'access'	=> $access,
        'blocks'	=> $blocks,
        'forum'		=> $forum
    ), $forum->template));
}

function forum_permission($request, $logger, $sql, $display, $input, $user)
{
    $forum = yN\Entity\Board\Forum::get_by_identifier($sql, (int)$request->get_or_fail('forum'));

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    if (!$forum->allow_edit(yN\Entity\Board\Permission::check_by_forum($sql, $user, $forum))) {
        return Glay\Network\HTTP::code(403);
    }

    // Submit changes
    if ($request->method === 'POST') {
        $alerts = array();

        // Insert new permission
        if ($input->get_string('login', $login) && $login !== '') {
            $profile = yN\Entity\Board\Profile::get_by_user_login($sql, $login);

            if ($profile === null) {
                $alerts[] = 'login-unknown';
            } else {
                $input->get_array('insert', $insert);

                $can_change = isset($insert['change']) ? $insert['change'] : '';
                $can_read = isset($insert['read']) ? $insert['read'] : '';
                $can_write = isset($insert['write']) ? $insert['write'] : '';

                // Update permission and save
                $permission = yN\Entity\Board\Permission::fetch_by_profile_forum($sql, $profile->user_id, $forum->id);
                $permission->can_change = $can_change === '1' ? true : ($can_change === '0' ? false : null);
                $permission->can_read = $can_read === '1' ? true : ($can_read === '0' ? false : null);
                $permission->can_write = $can_write === '1' ? true : ($can_write === '0' ? false : null);

                if (!$permission->save($sql, $alert)) {
                    $alerts[] = $alert;
                }
            }
        }

        // Update existing permissions
        if ($input->get_array('updates', $updates)) {
            foreach (array_slice($updates, 0, 1024, true) as $profile_id => $update) {
                // Load profile by user ID or ignore
                $profile = yN\Entity\Board\Profile::get_by_user($sql, (int)$profile_id);

                if ($profile === null) {
                    $alerts[] = 'profile-unknown';
                } else {
                    $can_change = isset($update['change']) ? $update['change'] : '';
                    $can_read = isset($update['read']) ? $update['read'] : '';
                    $can_write = isset($update['write']) ? $update['write'] : '';

                    // Update permission and save
                    $permission = yN\Entity\Board\Permission::fetch_by_profile_forum($sql, $profile->user_id, $forum->id);
                    $permission->can_change = $can_change === '1' ? true : ($can_change === '0' ? false : null);
                    $permission->can_read = $can_read === '1' ? true : ($can_read === '0' ? false : null);
                    $permission->can_write = $can_write === '1' ? true : ($can_write === '0' ? false : null);

                    if (!$permission->save($sql, $alert)) {
                        $alerts[] = $alert;
                    }
                }
            }
        }
    } else {
        $alerts = null;
    }

    // Remove current user from permissions list to avoid self-removal (visual-only safety net)
    $permissions = array_filter(yN\Entity\Board\Permission::get_by_forum($sql, $forum->id), function ($permission) use ($user) {
        return $permission->profile_id !== $user->id;
    });

    $location = 'board.forum.' . $forum->id . '.permission';

    return Glay\Network\HTTP::data($display->render('yn-board-forum-permission.deval', $location, array(
        'alerts'		=> $alerts,
        'forum'			=> $forum,
        'permissions'	=> $permissions
    ), $forum->template));
}

function forum_view($request, $logger, $sql, $display, $input, $user)
{
    // Get forum by identifier
    $forum = yN\Entity\Board\Forum::get_by_identifier($sql, (int)$request->get_or_fail('forum'));

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    if ($request->get_or_default('forum_alias') !== $forum->alias) {
        return Glay\Network\HTTP::go($request->router->url('board.forum.view', array('forum' => $forum->id, 'forum_alias' => $forum->alias)), Glay\Network\HTTP::REDIRECT_PERMANENT);
    }

    // Get forum blocks
    $blocks = yN\Entity\Board\Block::get_by_forum($sql, $forum->id, $user->id);

    // FIXME: should filter private sections with no access [hack-private-topics]

    // Get last created & active topics
    $from_topics = function ($topics) {
        return array_map(function ($topic) {
            return array($topic, $topic->bookmark !== null ? $topic->bookmark->position : 0);
        }, $topics);
    };

    $sources = array(
        'active'	=> $from_topics(yN\Entity\Board\Topic::get_by_last_time_forum($sql, $forum->id, yN\Entity\Board\Topic::ORDER_LAST, 10, $user->id)),
        'new'		=> $from_topics(yN\Entity\Board\Topic::get_by_last_time_forum($sql, $forum->id, yN\Entity\Board\Topic::ORDER_CREATE, 10, $user->id))
    );

    // Render template
    $location = 'board.forum.' . $forum->id;

    return Glay\Network\HTTP::data($display->render('yn-board-forum-view.deval', $location, array(
        'access'	=> yN\Entity\Board\Permission::check_by_forum($sql, $user, $forum),
        'blocks'	=> $blocks,
        'forum'		=> $forum,
        'sources'	=> $sources
    ), $forum->template));
}
