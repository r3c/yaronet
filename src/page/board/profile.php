<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Bookmark', './entity/board/bookmark.php');
Glay\using('yN\\Entity\\Board\\Favorite', './entity/board/favorite.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
Glay\using('yN\\Entity\\Board\\Post', './entity/board/post.php');
Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');
Glay\using('yN\\Entity\\Board\\Subscription', './entity/board/subscription.php');
Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');
Glay\using('yN\\Engine\\Media\\Binary', './engine/media/binary.php');

function profile_delete($request, $logger, $sql, $display, $input, $user)
{
    $profile_id = (int)$request->get_or_fail('profile');

    if ($profile_id === $user->id || !$user->is_admin) {
        return Glay\Network\HTTP::code(403);
    }

    $profile = yN\Entity\Board\Profile::get_by_user($sql, $profile_id);

    if ($profile === null) {
        return Glay\Network\HTTP::code(404);
    }

    if ($request->method === 'POST') {
        $alerts = array();
        $alert_delete = false;
        $alert_save = false;

        // Disable user
        $profile->user->is_disabled = true;

        $alert_save = !$profile->user->save($sql, $alert) || $alert_save;

        // Cleanup bookmarks
        $alert_save = !yN\Entity\Board\Bookmark::delete_by_profile($sql, $profile->user_id) || $alert_save;
        $alert_save = !yN\Entity\Board\Favorite::delete_by_profile($sql, $profile->user_id) || $alert_save;
        $alert_save = !yN\Entity\Board\Permission::delete_by_profile($sql, $profile->user_id) || $alert_save;
        $alert_save = !yN\Entity\Board\Subscription::delete_by_profile($sql, $profile->user_id) || $alert_save;

        // Hide all posts from given profile
        $alert_save = !yN\Entity\Board\Post::set_state_by_profile($sql, $profile->user_id, yN\Entity\Board\Post::STATE_HIDDEN) || $alert_save;

        // Delete posts and topics if they don't exceed limit
        $limit = 1000;
        $posts = yN\Entity\Board\Post::get_by_profile($sql, $profile->user_id, $limit + 1);

        if (count($posts) < $limit + 1) {
            $groups = array();

            // Group posts, count and "in first position" flag by topic id
            foreach ($posts as $post) {
                $reference = $post->reference;
                $topic = $reference->topic;

                if (!isset($groups[$topic->id])) {
                    $groups[$topic->id] = array($topic, 0, false);
                }

                if ($reference->position === 1) {
                    $groups[$topic->id][2] = true;
                }

                ++$groups[$topic->id][1];
            }

            // Cleanup posts & topics
            foreach ($groups as $group) {
                list($topic, $count, $first) = $group;

                // Topic contains only posts from this profile and can be deleted
                if ($count === $topic->posts) {
                    $alert_delete = !$topic->delete($sql, $alert) || $alert_delete;
                }

                // Close topic if first post was from profile
                elseif ($first) {
                    $topic->is_closed = true;

                    $alert_save = !$topic->save($sql, $alert) || $alert_save;
                }
            }
        }

        // Prepare alerts
        if ($alert_delete) {
            $alerts[] = 'delete';
        }

        if ($alert_save) {
            $alerts[] = 'save';
        }
    } else {
        $alerts = null;
    }

    // Render template
    $location = 'board.profile.' . $profile->user_id . '.delete';

    return Glay\Network\HTTP::data($display->render('yn-board-profile-delete.deval', $location, array(
        'alerts'	=> $alerts,
        'profile'	=> $profile
    )));
}

function profile_edit($request, $logger, $sql, $display, $input, $user)
{
    $profile = yN\Entity\Board\Profile::get_by_user($sql, (int)$request->get_or_fail('profile'));

    if ($profile === null) {
        return Glay\Network\HTTP::code(404);
    }

    if ($profile->user_id !== $user->id && !$user->is_admin) {
        return Glay\Network\HTTP::code(403);
    }

    // Save changes
    if ($request->method === 'POST') {
        $alerts = array();
        $avatar = null;

        // Avatar
        if ($input->get_image('avatar', $image, $name)) {
            if ($image === null) {
                $alerts[] = 'avatar-read';
            } else {
                // Try to convert to resized PNG if possible or keep original data otherwise
                $image->clamp(128);

                $avatar = $image->create_png() ?: $image->data;

                $image->free();

                $profile->avatar = yN\Entity\Board\Profile::AVATAR_IMAGE;
                $profile->avatar_tag = (int)sprintf('%u', crc32($avatar));
            }
        } elseif ($input->get_string('avatar', $avatar_source)) {
            $sources = array('gravatar' => yN\Entity\Board\Profile::AVATAR_GRAVATAR, 'void' => yN\Entity\Board\Profile::AVATAR_NONE);

            if (isset($sources[$avatar_source])) {
                $avatar = '';

                $profile->avatar = $sources[$avatar_source];
                $profile->avatar_tag = 0;
            }
        }

        // Forum update
        if ($input->get_string('forum', $forum_name)) {
            if ($forum_name !== '') {
                $forum = yN\Entity\Board\Forum::get_by_name($sql, $forum_name);

                if ($forum === null) {
                    $alerts[] = 'forum';
                } else {
                    $profile->forum_id = $forum->id;
                }
            } else {
                $profile->forum_id = null;
            }
        }

        // Gender update
        if ($input->get_number('gender', $gender)) {
            $profile->gender = $gender;
        }

        // Signature update
        if ($input->get_string('signature', $signature)) {
            $profile->convert_signature(mb_substr($signature, 0, 600), $request->router, $logger);
        }

        // Save
        if (count($alerts) === 0) {
            if (!$profile->save($sql, $alert)) {
                $alerts[] = $alert;
            } elseif ($avatar !== null) {
                if ($avatar === '') {
                    yN\Engine\Media\Binary::delete('avatar-' . $profile->user_id);
                } else {
                    yN\Engine\Media\Binary::put('avatar-' . $profile->user_id, $avatar);
                }
            }
        }
    } else {
        $alerts = null;
    }

    $input->ensure('forum', $profile->forum !== null ? $profile->forum->name : null);
    $input->ensure('gender', $profile->gender);
    $input->ensure('signature', $profile->revert_signature());

    // Render template
    $location = 'board.profile.' . $profile->user_id . '.edit';

    return Glay\Network\HTTP::data($display->render('yn-board-profile-edit.deval', $location, array(
        'alerts'	=> $alerts,
        'profile'	=> $profile
    )));
}

function profile_list($request, $logger, $sql, $display, $input, $user)
{
    // Search users by login
    $count = 10;
    $forum_id = $request->get_or_default('forum');
    $page = max(min((int)$request->get_or_default('page', 0), 100), 0);
    $query = (string)$request->get_or_default('query', '');

    if ($forum_id !== null) {
        $profiles = $query ?
            yN\Entity\Board\Profile::get_by_forum__user_login_like($sql, (int)$forum_id, $query, $count + 1, $page * $count) :
            yN\Entity\Board\Profile::get_by_forum($sql, (int)$forum_id, $count + 1, $page * $count);
    } else {
        $profiles = $query ?
            yN\Entity\Board\Profile::get_by_user_login_like($sql, $query, $count + 1, $page * $count) :
            yN\Entity\Board\Profile::get_last($sql, $count + 1, $page * $count);
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-board-profile-list.deval', 'board.profile', array(
        'forum_id'		=> $forum_id,
        'page'			=> $page,
        'page_next'		=> count($profiles) > $count ? min($page + 1, 100) : null,
        'page_previous'	=> $page > 0 ? max($page - 1, 0) : null,
        'query'			=> $query,
        'profiles'		=> array_slice($profiles, 0, $count)
    )));
}

function profile_view($request, $logger, $sql, $display, $input, $user)
{
    $profile = yN\Entity\Board\Profile::get_by_user($sql, (int)$request->get_or_fail('profile'));

    if ($profile === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Render template
    $location = 'board.profile.' . $profile->user_id;

    return Glay\Network\HTTP::data($display->render('yn-board-profile-view.deval', $location, array(
        'profile'	=> $profile
    )));
}
