<?php

defined('YARONET') or die;

function check_php($request, $logger, $sql, $display, $input, $user)
{
    if (!$user->is_admin) {
        return Glay\Network\HTTP::code(403);
    }

    ob_start();
    phpinfo();

    return Glay\Network\HTTP::data(ob_get_flush());
}
