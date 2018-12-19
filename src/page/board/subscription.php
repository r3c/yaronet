<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
Glay\using('yN\\Entity\\Board\\Section', './entity/board/section.php');
Glay\using('yN\\Entity\\Board\\Subscription', './entity/board/subscription.php');

function _subscription_state($sql, $display, $user, $section_id, $state)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $section = yN\Entity\Board\Section::get_by_identifier($sql, $section_id);

    if ($section === null) {
        return Glay\Network\HTTP::code(404);
    }

    $access = yN\Entity\Board\Permission::check_by_section($sql, $user, $section);

    if (!$section->allow_view($access) && $state) {
        return Glay\Network\HTTP::code(403);
    }

    yN\Entity\Board\Subscription::set_state($sql, $section->id, $user->id, $state);

    // Render template
    $location = 'board.subscription.' . $section->id . '.state';

    return Glay\Network\HTTP::data($display->render('yn-board-subscription-state.deval', $location, array(
        'section'	=> $section
    )));
}

function subscription_clear($request, $logger, $sql, $display, $input, $user)
{
    return _subscription_state($sql, $display, $user, (int)$request->get_or_fail('section'), false);
}

function subscription_set($request, $logger, $sql, $display, $input, $user)
{
    return _subscription_state($sql, $display, $user, (int)$request->get_or_fail('section'), true);
}
