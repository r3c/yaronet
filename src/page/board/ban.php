<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Ban', './entity/board/ban.php');
Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');

function ban_edit($request, $logger, $sql, $display, $input, $user)
{
    $forum = yN\Entity\Board\Forum::get_by_identifier($sql, (int)$request->get_or_fail('forum'));

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    if (!$forum->allow_edit(yN\Entity\Board\Permission::check_by_forum($sql, $user, $forum))) {
        return Glay\Network\HTTP::code(403);
    }

    // Save changes
    if ($request->method === 'POST') {
        $alerts = array();

        if ($input->get_string('addresses', $addresses_string)) {
            $addresses = array_filter(array_map('trim', explode(',', $addresses_string)), 'strlen');

            if (count($addresses) > yN\Entity\Board\Ban::COUNT_MAX) {
                $alerts[] = 'addresses-length';
            }

            // Remove previous favorites
            elseif (!yN\Entity\Board\Ban::delete_by_forum($sql, $forum->id)) {
                $alerts[] = 'delete';
            }

            // Insert new favorites
            else {
                foreach ($addresses as $address) {
                    $ban = new yN\Entity\Board\Ban();
                    $ban->address = $address;
                    $ban->forum_id = $forum->id;

                    if (!$ban->save($sql, $alert)) {
                        $alerts[] = $alert;
                    }
                }
            }
        }
    } else {
        $alerts = null;
    }

    // Retrieve current favorites
    $addresses = array();

    foreach (yN\Entity\Board\Ban::get_by_forum($sql, $forum->id) as $ban) {
        $addresses[] = $ban->address;
    }

    $input->ensure('addresses', implode(', ', $addresses));

    // Render template
    $location = 'board.ban.' . $forum->id . '.edit';

    return Glay\Network\HTTP::data($display->render('yn-board-ban-edit.deval', $location, array(
        'alerts' => $alerts,
        'forum' => $forum
    )));
}
