<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Account\\Activity', './entity/account/activity.php');
Glay\using('yN\\Entity\\Board\\Log', './entity/board/log.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');
Glay\using('yN\\Entity\\Board\\Search', './entity/board/search.php');
Glay\using('yN\\Entity\\Board\\Section', './entity/board/section.php');
Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');
Glay\using('yN\\Entity\\Chat\\Shout', './entity/chat/shout.php');
Glay\using('yN\\Entity\\Security\\Cost', './entity/security/cost.php');

function task_clean($request, $logger, $sql, $display, $input, $user)
{
    if (!$user->is_admin) {
        return Glay\Network\HTTP::code(403);
    }

    // Delete expired data in SQL tables
    yN\Entity\Account\Activity::clean($sql);
    yN\Entity\Account\Message::clean($sql);
    yN\Entity\Board\Log::clean($sql);
    yN\Entity\Board\Permission::clean($sql);
    yN\Entity\Board\Search::clean($sql);
    yN\Entity\Chat\Shout::clean($sql);
    yN\Entity\Security\Cost::clean($sql);

    // Delete expired log files
    $logger->clean(30 * 24 * 60 * 60); // 30 days

    return Glay\Network\HTTP::data('');
}

function task_flush($request, $logger, $sql, $display, $input, $user)
{
    if (!$user->is_admin) {
        return Glay\Network\HTTP::code(403);
    }

    $messages = array();

    // Truncate memory tables
    if (!yN\Entity\Board\Profile::flush($sql)) {
        $messages[] = "cannot flush profile cache";
    }

    if (!yN\Entity\Board\Section::flush($sql)) {
        $messages[] = "cannot flush section cache";
    }

    if (!yN\Entity\Board\Topic::flush($sql)) {
        $messages[] = "cannot flush topic cache";
    }

    // Remove cache files
    $caches = array(
        'engine.network.route.cache',
        'engine.text.display.cache',
        'engine.text.i18n.cache'
    );

    foreach ($caches as $cache) {
        $directory = config($cache, null);

        if ($directory === null) {
            continue;
        } elseif (!is_dir($directory)) {
            $messages[] = "cannot empty invalid directory '$directory'";
        }

        foreach (scandir($directory) as $file) {
            if (strlen($file) < 1 || $file[0] === '.') {
                continue;
            }

            unlink($directory . '/' . $file);
        }
    }

    return Glay\Network\HTTP::data(implode(', ', $messages));
}
