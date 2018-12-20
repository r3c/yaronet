<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Block', './entity/board/block.php');
Glay\using('yN\\Entity\\Board\\Bookmark', './entity/board/bookmark.php');
Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
Glay\using('yN\\Entity\\Board\\Log', './entity/board/log.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
Glay\using('yN\\Entity\\Board\\Section', './entity/board/section.php');
Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');

function section_edit($request, $logger, $sql, $display, $input, $user)
{
    $section_id = $request->get_or_default('section');

    // Get or create requested section
    if ($section_id !== null) {
        $section = yN\Entity\Board\Section::get_by_identifier($sql, (int)$section_id);

        if ($section === null) {
            return Glay\Network\HTTP::code(404);
        }

        if (!$section->allow_edit(yN\Entity\Board\Permission::check_by_section($sql, $user, $section))) {
            return Glay\Network\HTTP::code(403);
        }

        $forum = $section->forum;
        $data = array_diff_key($section->revert(), $section->get_primary());
    } else {
        $forum = yN\Entity\Board\Forum::get_by_identifier($sql, (int)$request->get_or_fail('forum'));

        if ($forum === null) {
            return Glay\Network\HTTP::code(404);
        }

        if (!$forum->allow_edit(yN\Entity\Board\Permission::check_by_forum($sql, $user, $forum))) {
            return Glay\Network\HTTP::code(403);
        }

        $section = new yN\Entity\Board\Section();
        $section->forum = $forum;
        $section->forum_id = $forum->id;

        $data = array();
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
    $new = $section->id === null;

    if ($request->method === 'POST') {
        $alerts = array();

        // Disable edit for unconfirmed users
        if (!$user->is_active) {
            $alerts[] = 'user-active';
        }

        // Access
        if ($input->get_number('access', $access)) {
            $section->access = $access;
        }

        // Description
        if ($input->get_string('description', $description)) {
            $section->description = $description;
        }

        // Flags
        if ($input->get_array('flags', $flags)) {
            $section->is_delegated = isset($flags['delegated']);
        }

        // Header
        if ($input->get_string('header', $header)) {
            $section->convert_header(trim($header) ?: null, $request->router, $logger);
        }

        // Name
        if ($input->get_string('name', $name)) {
            $section->name = trim($name);
        }

        // Reach
        if ($input->get_number('reach', $reach)) {
            $section->reach = $reach;
        }

        // Save
        if (count($alerts) === 0) {
            if (!$section->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                // Append to parent forum
                if ($new) {
                    $block = new yN\Entity\Board\Block();
                    $block->forum_id = $section->forum_id;
                    $block->section_id = $section->id;

                    if (!$block->save($sql, $alert)) {
                        $alerts[] = 'block-' . $alert;
                    }
                }

                yN\Entity\Board\Log::push($sql, $forum->id, $user->id, 'section.edit', array_merge(
                    array_diff_assoc($section->revert(), $data),
                    $new ? array('new' => true) : array()
                ));
            }
        }
    } else {
        $alerts = null;
    }

    $input->ensure('access', $section->access);
    $input->ensure('description', $section->description);
    $input->ensure('flags', array('delegated' => $section->is_delegated ?: null));
    $input->ensure('header', $section->revert_header());
    $input->ensure('name', $section->name);
    $input->ensure('reach', $section->reach);

    // Render template
    $location = 'board.forum.' . $forum->id . '.' . ($section->id ?: 0) . '.edit';

    return Glay\Network\HTTP::data($display->render('yn-board-section-edit.deval', $location, array(
        'alerts' => $alerts,
        'forum' => $forum,
        'section' => $section,
        'new' => $new
    ), $forum->template));
}

function section_list($request, $logger, $sql, $display, $input, $user)
{
    // Match sections by name
    $sections = yN\Entity\Board\Section::get_by_name_match($sql, (string)$request->get_or_fail('query'));

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-board-section-list.deval', 'board.forum.section.list', array(
        'sections' => $sections
    )));
}

function section_merge($request, $logger, $sql, $display, $input, $user)
{
    $from_section = yN\Entity\Board\Section::get_by_identifier($sql, (int)$request->get_or_fail('section'));

    if ($from_section === null) {
        return Glay\Network\HTTP::code(404);
    }

    if (!$from_section->allow_edit(yN\Entity\Board\Permission::check_by_section($sql, $user, $from_section))) {
        return Glay\Network\HTTP::code(403);
    }

    // Submit changes
    if ($request->method === 'POST' && $input->get_string('into', $into)) {
        $alerts = array();
        $into_section = yN\Entity\Board\Section::get_by_unique($sql, $into);

        if ($into_section === null) {
            $alerts[] = 'into-unknown';
        } elseif ($from_section->id === $into_section->id) {
            $alerts[] = 'into-self';
        } elseif (!$into_section->allow_edit(yN\Entity\Board\Permission::check_by_section($sql, $user, $into_section))) {
            $alerts[] = 'into-access';
        } elseif (!$from_section->merge($sql, $into_section->id, $alert)) {
            $alerts[] = $alert;
        } else {
            yN\Entity\Board\Log::push($sql, $from_section->forum_id, $user->id, 'section.merge', array('id' => $from_section->id, 'into' => $into_section->id));
        }
    } else {
        $alerts = null;
    }

    // Render template
    $forum = $from_section->forum;
    $location = 'board.forum.' . $forum->id . '.' . $from_section->id . '.merge';

    return Glay\Network\HTTP::data($display->render('yn-board-section-merge.deval', $location, array(
        'alerts' => $alerts,
        'forum' => $forum,
        'section' => $from_section
    ), $forum->template));
}

function section_permission($request, $logger, $sql, $display, $input, $user)
{
    $section = yN\Entity\Board\Section::get_by_identifier($sql, (int)$request->get_or_fail('section'));

    if ($section === null) {
        return Glay\Network\HTTP::code(404);
    }

    if (!$section->allow_edit(yN\Entity\Board\Permission::check_by_section($sql, $user, $section))) {
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
                $permission = yN\Entity\Board\Permission::fetch_by_profile_section($sql, $profile->user_id, $section->id);
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
                    $permission = yN\Entity\Board\Permission::fetch_by_profile_section($sql, $profile->user_id, $section->id);
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

    $forum = $section->forum;
    $location = 'board.forum.' . $forum->id . '.' . $section->id . '.permission';

    return Glay\Network\HTTP::data($display->render('yn-board-section-permission.deval', $location, array(
        'alerts' => $alerts,
        'forum' => $forum,
        'permissions' => yN\Entity\Board\Permission::get_by_section($sql, $section->id),
        'section' => $section
    ), $forum->template));
}

function section_view($request, $logger, $sql, $display, $input, $user)
{
    $page = max((int)$request->get_or_fail('page'), 1);

    // Get requested section
    $section = yN\Entity\Board\Section::get_by_identifier($sql, (int)$request->get_or_fail('section'), $user->id);

    if ($section === null) {
        return Glay\Network\HTTP::code(404);
    }

    if ($request->get_or_default('section_hint') !== $section->hint) {
        return Glay\Network\HTTP::go($request->router->url('board.section.view', array('forum' => $request->get_or_default('forum'), 'section' => $section->id, 'section_hint' => $section->hint, 'page' => $page)), Glay\Network\HTTP::REDIRECT_PERMANENT);
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

    // Check access
    $access = yN\Entity\Board\Permission::check_by_section($sql, $user, $section);

    if (!$section->allow_view($access)) {
        return Glay\Network\HTTP::code(403);
    }

    // Retrieve topics
    $topics = yN\Entity\Board\Topic::get_by_section($sql, $section->id, $user->id, $page - 1);

    // Flag section as read
    yN\Entity\Board\Section::set_read($sql, $section->id, $user->id);

    // Render template
    $location = 'board.forum.' . $forum->id . '.' . $section->id;

    return Glay\Network\HTTP::data($display->render('yn-board-section-view.deval', $location, array(
        'access' => $access,
        'forum' => $forum,
        'page' => $page,
        'section' => $section,
        'topics' => $topics
    ), $forum->template));
}
