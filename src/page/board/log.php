<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Account\\User', './entity/account/user.php');
Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
Glay\using('yN\\Entity\\Board\\Log', './entity/board/log.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');

function log_list($request, $logger, $sql, $display, $input, $user)
{
    global $time;

    // Get parent forum
    $forum = yN\Entity\Board\Forum::get_by_identifier($sql, (int)$request->get_or_fail('forum'));

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    if (!$forum->allow_edit(yN\Entity\Board\Permission::check_by_forum($sql, $user, $forum))) {
        return Glay\Network\HTTP::code(403);
    }

    $filter_address = null;
    $filter_date = strtotime('today', $time);
    $filter_profile = null;

    // Apply filters
    if ($request->method === 'POST') {
        $alerts = array();

        // Filter by IP address
        if ($input->get_string('address', $address) && $address !== '') {
            $filter_address = $address;
        }

        // Filter by date
        if ($input->get_string('date', $date_string) && $date_string !== '') {
            $date = date_create_from_format('Y-m-d', $date_string);

            if ($date === false) {
                $alerts[] = 'date-format';
            } else {
                $date->setTime(0, 0, 0);

                $filter_date = (int)$date->format('U');
            }
        }

        // Filter by login
        if ($input->get_string('login', $login) && $login !== '') {
            $filter_user = yN\Entity\Account\User::get_by_login($sql, $login);

            if ($filter_user === null) {
                $alerts[] = 'login-unknown';
            } else {
                $filter_profile = $filter_user->id;
            }
        }
    } else {
        $alerts = null;
    }

    $input->ensure('date', date('Y-m-d', $time));

    // Get last logs
    $logs = yN\Entity\Board\Log::get_by_forum__time__profile__address($sql, $forum->id, $filter_date, $filter_date + 86400, $filter_profile, $filter_address);

    // Render template
    $location = 'board.forum.' . $forum->id . '.log';

    return Glay\Network\HTTP::data($display->render('yn-board-log-list.deval', $location, array(
        'alerts'	=> $alerts,
        'forum'		=> $forum,
        'logs'		=> $logs
    ), $forum->template));
}
