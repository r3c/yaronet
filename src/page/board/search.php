<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Account\\User', './entity/account/user.php');
Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
Glay\using('yN\\Entity\\Board\\Search', './entity/board/search.php');

function search_edit($request, $logger, $sql, $display, $input, $user)
{
    // Get forum by identifier
    $forum = yN\Entity\Board\Forum::get_by_identifier($sql, (int)$request->get_or_fail('forum'));

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Perform search using given query and filters
    if ($request->method === 'POST') {
        $alerts = array();
        $filter_profile = null;

        // Get search query
        if (!$input->get_string('query', $query)) {
            $query = '';
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

        if (count($alerts) !== 0) {
            $search = null;
        } elseif (!yN\Entity\Board\Search::execute($sql, $query, $user->id, $forum->id, $filter_profile, $search, $alert)) {
            $alerts[] = $alert;
        }
    }

    // Display search form
    else {
        $alerts = null;
        $search = null;
    }

    // Render template
    $location = 'board.forum.' . $forum->id . '.search';

    return Glay\Network\HTTP::data($display->render('yn-board-search-edit.deval', $location, array(
        'alerts'	=> $alerts,
        'forum'		=> $forum,
        'search'	=> $search
    ), $forum->template));
}

function search_view($request, $logger, $sql, $display, $input, $user)
{
    // Get search by identifier
    $search = yN\Entity\Board\Search::get_by_identifier($sql, (int)$request->get_or_fail('search'));

    if ($search === null) {
        return Glay\Network\HTTP::code(404);
    }

    if ($search->profile_id !== $user->id) {
        return Glay\Network\HTTP::code(403);
    }

    $forum = $search->forum;

    // Browse results
    $results = yN\Entity\Board\Search::get_results($sql, $search->id, $user->id, max((int)$request->get_or_fail('from'), 0));
    $access = yN\Entity\Board\Permission::check_by_forum($sql, $user, $search->forum);

    $from = (int)$request->get_or_fail('from');
    $from_next = count($results) > yN\Entity\Board\Search::RESULT_PAGE ? $from + yN\Entity\Board\Search::RESULT_PAGE : null;
    $from_previous = $from > 0 ? max($from - yN\Entity\Board\Search::RESULT_PAGE, 0) : null;

    for ($i = 0; $i < count($results); ++$i) {
        $reference = $results[$i]->reference;
        $post = $reference->post;
        $topic = $reference->topic;

        // Filter posts according to permissions
        $local = $access->localize($topic->section);
        $local->update($topic->permission);

        if ((!$post->allow_edit($local, $user->id) && $post->state === yN\Entity\Board\Post::STATE_HIDDEN) || !$post->allow_view($local, $user->id)) {
            array_splice($results, $i, 1);

            --$i;
        }
    }

    // Render template
    $location = 'board.forum.' . $forum->id . '.search';

    return Glay\Network\HTTP::data($display->render('yn-board-search-view.deval', $location, array(
        'access'		=> $access,
        'forum'			=> $forum,
        'from_next'		=> $from_next,
        'from_previous'	=> $from_previous,
        'results'		=> array_slice($results, 0, yN\Entity\Board\Search::RESULT_PAGE),
        'search'		=> $search
    ), $forum->template));
}
