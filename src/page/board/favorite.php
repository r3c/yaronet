<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Favorite', './entity/board/favorite.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');

function favorite_edit($request, $logger, $sql, $display, $input, $user)
{
    $profile_id = (int)$request->get_or_default('profile', $user->id);

    if ($profile_id !== $user->id && !$user->is_admin) {
        return Glay\Network\HTTP::code(403);
    }

    $edit = yN\Entity\Account\User::get_by_identifier($sql, $profile_id);

    if ($edit === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Save changes
    if ($request->method === 'POST') {
        $alerts = array();

        if ($input->get_string('forums', $forums_string)) {
            $forums = array_filter(array_map('trim', explode(',', $forums_string)), 'strlen');

            if (count($forums) > yN\Entity\Board\Favorite::COUNT_MAX) {
                $alerts[] = 'forums-length';
            }

            // Remove previous favorites
            elseif (!yN\Entity\Board\Favorite::delete_by_profile($sql, $edit->id)) {
                $alerts[] = 'delete';
            }

            // Insert new favorites
            else {
                $edit->is_favorite = false;

                foreach ($forums as $name) {
                    $forum = yN\Entity\Board\Forum::get_by_name($sql, $name);

                    if ($forum === null) {
                        $alerts[] = 'forum-unknown';

                        continue;
                    }

                    $favorite = new yN\Entity\Board\Favorite();
                    $favorite->forum_id = $forum->id;
                    $favorite->profile_id = $edit->id;

                    if (!$favorite->save($sql, $alert)) {
                        $alerts[] = $alert;
                    } else {
                        $edit->is_favorite = true;
                    }
                }

                // Save user favorite flag
                if (!$edit->save($sql, $alert)) {
                    $alerts[] = 'user';
                }
            }
        }
    } else {
        $alerts = null;
    }

    // Retrieve current favorites
    $forums = array();

    foreach (yN\Entity\Board\Favorite::get_by_profile($sql, $edit->id) as $favorite) {
        $forums[] = $favorite->forum->name;
    }

    $input->ensure('forums', implode(', ', $forums));

    // Render template
    $location = 'board.favorite.' . $edit->id . '.edit';

    return Glay\Network\HTTP::data($display->render('yn-board-favorite-edit.deval', $location, array(
        'alerts'	=> $alerts,
        'edit'		=> $edit
    )));
}
