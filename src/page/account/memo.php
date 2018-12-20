<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Account\\Memo', './entity/account/memo.php');

function memo_edit($request, $logger, $sql, $display, $input, $user)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $memo = yN\Entity\Account\Memo::get_by_user($sql, $user->id) ?: new yN\Entity\Account\Memo();
    $memo->user_id = $user->id;

    if ($request->method === 'POST') {
        $alerts = array();

        if ($input->get_string('text', $text)) {
            $memo->convert_text($text, $request->router, $logger);
        }

        if (!$memo->save($sql, $alert)) {
            $alerts[] = $alert;
        }
    } else {
        $alerts = null;
    }

    $input->ensure('text', $memo->revert_text());

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-account-memo-edit.deval', 'account.memo.edit', array(
        'alerts' => $alerts,
        'memo' => $memo
    )));
}

function memo_view($request, $logger, $sql, $display, $input, $user)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $memo = yN\Entity\Account\Memo::get_by_user($sql, $user->id) ?: new yN\Entity\Account\Memo();

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-account-memo-view.deval', 'account.memo', array(
        'memo' => $memo
    )));
}
