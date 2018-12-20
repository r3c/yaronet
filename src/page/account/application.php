<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Account\\Application', './entity/account/application.php');

function application_authorize($request, $logger, $sql, $display, $input, $user)
{
    global $time;

    $application = yN\Entity\Account\Application::get_by_identifier($sql, $request->get_or_fail('application'));

    if ($application === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Build data
    $data = array(
        'application' => $application->id,
        'time' => $time
    );

    $payload = $request->get_or_default('payload');

    if ($payload !== null) {
        $data['payload'] = (string)$payload;
    }

    if ($user->id !== null) {
        $data['login'] = $user->login;
        $data['user'] = $user->id;
    }

    // Sign data
    ksort($data);

    $data['signature'] = hash_hmac('sha256', http_build_query($data), $application->key);

    // Redirect to application URL
    $query = '?' . http_build_query($data);

    return Glay\Network\HTTP::go(Glay\Network\URI::create($application->url)->combine($query));
}
