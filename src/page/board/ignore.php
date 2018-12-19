<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Ignore', './entity/board/ignore.php');
Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');

function _ignore_flag($request, $sql, $display, $user, $set)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $target = yN\Entity\Board\Profile::get_by_user($sql, (int)$request->get_or_fail('target'));

    if ($target === null) {
        return Glay\Network\HTTP::code(404);
    }

    if ($set) {
        yN\Entity\Board\Ignore::set($sql, $user->id, $target->user_id);
    } else {
        yN\Entity\Board\Ignore::clear($sql, $user->id, $target->user_id);
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-board-ignore-flag.deval', 'board.ignore.' . $target->user_id));
}

function ignore_clear($request, $logger, $sql, $display, $input, $user)
{
    return _ignore_flag($request, $sql, $display, $user, false);
}

function ignore_set($request, $logger, $sql, $display, $input, $user)
{
    return _ignore_flag($request, $sql, $display, $user, true);
}
