<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Account\\Activity', './entity/account/activity.php');

function activity_list($request, $logger, $sql, $display, $input, $user)
{
    if (!$user->is_admin) {
        return Glay\Network\HTTP::code(403);
    }

    $activities = yN\Entity\Account\Activity::browse($sql);

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-account-activity-list.deval', 'account.activity', array(
        'activities'	=> $activities
    )));
}

function activity_pulse($request, $logger, $sql, $display, $input, $user)
{
    return Glay\Network\HTTP::data($display->render('yn-account-activity-pulse.deval', null, array()));
}
