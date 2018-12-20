<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Survey\\Poll', './entity/survey/poll.php');

function poll_edit($request, $logger, $sql, $display, $input, $user)
{
    if ($request->method === 'POST') {
        $alerts = array();

        // Parameters
        $choices = $input->get_string('choices', $choices_string)
            ? array_filter(array_map('trim', explode(',', $choices_string)), 'strlen')
            : array();

        $question = $input->get_string('question', $question)
            ? trim($question)
            : '';

        $type = $input->get_number('type', $type)
            ? $type
            : yN\Entity\Survey\Poll::TYPE_SINGLE;

        // Save
        $poll = yN\Entity\Survey\Poll::create($sql, $question, $type, $choices, $alert);

        if ($poll === null) {
            $alerts[] = $alert;
        }
    } else {
        $alerts = null;
        $poll = null;
    }

    return Glay\Network\HTTP::data($display->render('yn-survey-poll-edit.deval', 'survey.poll.' . ($poll !== null ? (int)$poll->id : 0) . '.edit', array(
        'alerts' => $alerts,
        'poll' => $poll
    )));
}

function poll_view($request, $logger, $sql, $display, $input, $user)
{
    $poll = yN\Entity\Survey\Poll::get_by_identifier($sql, $request->get_or_fail('poll'), $user->id);

    if ($poll === null) {
        return Glay\Network\HTTP::code(404);
    }

    return Glay\Network\HTTP::data($display->render('yn-survey-poll-view.deval', null, array(
        'poll' => $poll
    )));
}

function poll_vote($request, $logger, $sql, $display, $input, $user)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $poll = yN\Entity\Survey\Poll::get_by_identifier($sql, $request->get_or_fail('poll'), $user->id);

    if ($poll === null) {
        return Glay\Network\HTTP::code(404);
    }

    $choices = $input->get_array('choices', $choices_string)
        ? array_unique(array_map('intval', $choices_string))
        : array();

    if ($poll->type === yN\Entity\Survey\Poll::TYPE_SINGLE) {
        $choices = array_splice($choices, 0, 1);
    }

    $ranks = array_intersect($choices, array_map(function ($choice) {
        return $choice->rank;
    }, $poll->choices));

    if (!yN\Entity\Survey\Poll::submit($sql, $poll->id, $user->id, $ranks)) {
        return Glay\Network\HTTP::code(400);
    }

    return Glay\Network\HTTP::data($display->render('yn-survey-poll-vote.deval', 'survey.poll.' . $poll->id . '.vote', array(
        'poll' => $poll
    )));
}
