<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Bookmark', './entity/board/bookmark.php');
Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');

function _bookmark_watch($sql, $display, $user, $topic_id, $watch)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $topic = yN\Entity\Board\Topic::get_by_identifier($sql, $topic_id, null);

    if ($topic === null) {
        return Glay\Network\HTTP::code(404);
    }

    yN\Entity\Board\Bookmark::set_watch($sql, $user->id, $topic->id, $topic->posts, false, $watch);

    // Render template
    $location = 'board.bookmark.' . $topic->id . '.watch';

    return Glay\Network\HTTP::data($display->render('yn-board-bookmark-watch.deval', $location, array(
        'topic' => $topic
    )));
}

function bookmark_clear($request, $logger, $sql, $display, $input, $user)
{
    return _bookmark_watch($sql, $display, $user, (int)$request->get_or_fail('topic'), false);
}

function bookmark_list($request, $logger, $sql, $display, $input, $user)
{
    $count = max(min((int)$request->get_or_fail('count'), 100), 10);
    $what = (string)$request->get_or_fail('what');

    $from_bookmarks = function ($bookmarks) {
        return array_map(function ($bookmark) {
            return array($bookmark->topic, $bookmark->position);
        }, $bookmarks);
    };

    $from_topics = function ($topics) {
        return array_map(function ($topic) {
            return array($topic, $topic->bookmark !== null ? $topic->bookmark->position : 0);
        }, $topics);
    };

    $sources = array();

    if (strpos($what, 'f') !== false) {
        $sources['fresh'] = $from_bookmarks(yN\Entity\Board\Bookmark::get_by_profile($sql, $user->id, true, $count));
    }

    if (strpos($what, 's') !== false) {
        $sources['stale'] = $from_bookmarks(yN\Entity\Board\Bookmark::get_by_profile($sql, $user->id, false, $count));
    }

    if (strpos($what, 'l') !== false) {
        $sources['active'] = $from_topics(yN\Entity\Board\Topic::get_by_last_time($sql, yN\Entity\Board\Topic::ORDER_LAST, $count, $user->id));
    }

    if (strpos($what, 'n') !== false) {
        $sources['new'] = $from_topics(yN\Entity\Board\Topic::get_by_last_time($sql, yN\Entity\Board\Topic::ORDER_CREATE, $count, $user->id));
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-board-bookmark-list.deval', 'board.bookmark', array(
        'count' => $count,
        'sources' => $sources,
        'what' => $what
    )));
}

function bookmark_mark($request, $logger, $sql, $display, $input, $user)
{
    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $topic = yN\Entity\Board\Topic::get_by_identifier($sql, (int)$request->get_or_fail('topic'), null);

    if ($topic === null) {
        return Glay\Network\HTTP::code(404);
    }

    $position = (int)$request->get_or_fail('position');

    yN\Entity\Board\Bookmark::set_watch($sql, $user->id, $topic->id, $position, $position < $topic->posts, true);

    // Render template
    $location = 'board.bookmark.' . $topic->id . '.mark';

    return Glay\Network\HTTP::data($display->render('yn-board-bookmark-mark.deval', $location, array(
        'position' => $position,
        'topic' => $topic
    )));
}

function bookmark_set($request, $logger, $sql, $display, $input, $user)
{
    return _bookmark_watch($sql, $display, $user, (int)$request->get_or_fail('topic'), true);
}
