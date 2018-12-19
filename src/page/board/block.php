<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Block', './entity/board/block.php');
Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
Glay\using('yN\\Entity\\Board\\Log', './entity/board/log.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
Glay\using('yN\\Entity\\Board\\Section', './entity/board/section.php');

function _block_get($sql, $user, $forum_id, $rank, &$block, &$http)
{
    $block = yN\Entity\Board\Block::get_by_forum__rank($sql, $forum_id, $rank);

    if ($block === null) {
        $http = Glay\Network\HTTP::code(404);

        return false;
    }

    $forum = $block->forum;

    if (!$forum->allow_edit(yN\Entity\Board\Permission::check_by_forum($sql, $user, $forum))) {
        $http = Glay\Network\HTTP::code(403);

        return false;
    }

    return true;
}

function block_delete($request, $logger, $sql, $display, $input, $user)
{
    if (!_block_get($sql, $user, (int)$request->get_or_fail('forum'), (int)$request->get_or_fail('rank'), $block, $http)) {
        return $http;
    }

    $forum = $block->forum;

    // Submit changes
    if ($request->method === 'POST') {
        $alerts = array();

        if (!$block->delete($sql, $alert)) {
            $alerts[] = $alert;
        }

        if (count($alerts) === 0) {
            yN\Entity\Board\Log::push($sql, $forum->id, $user->id, 'block.delete', $block->get_primary());
        }
    } else {
        $alerts = null;
    }

    // Render template
    $location = 'board.forum.' . $forum->id . '.delete';

    return Glay\Network\HTTP::data($display->render('yn-board-block-delete.deval', $location, array(
        'alerts'	=> $alerts,
        'block'		=> $block,
        'forum'		=> $forum
    ), $forum->template));
}

function block_edit($request, $logger, $sql, $display, $input, $user)
{
    $forum_id = (int)$request->get_or_fail('forum');
    $rank = $request->get_or_default('rank');

    // Load or create block
    if ($rank !== null) {
        if (!_block_get($sql, $user, $forum_id, (int)$rank, $block, $http)) {
            return $http;
        }

        $data = array_diff_key($block->revert(), $block->get_primary());
        $forum = $block->forum;
    } else {
        $data = array();
        $forum = yN\Entity\Board\Forum::get_by_identifier($sql, $forum_id);

        if ($forum === null) {
            return Glay\Network\HTTP::code(404);
        }

        if (!$forum->allow_edit(yN\Entity\Board\Permission::check_by_forum($sql, $user, $forum))) {
            return Glay\Network\HTTP::code(403);
        }

        $block = new yN\Entity\Board\Block();
        $block->forum_id = $forum_id;
    }

    // Submit changes
    if ($request->method === 'POST') {
        $alerts = array();

        // Rank
        if ($block->rank !== null && $input->get_number('move', $move)) {
            if (!$block->move($sql, max(min($move, 1024), 1), $alert)) {
                $alerts[] = $alert;
            }
        }

        // Section
        if ($input->get_string('section', $section_unique) && $section_unique !== '') {
            $section = yN\Entity\Board\Section::get_by_unique($sql, $section_unique);

            if ($section === null) {
                $alerts[] = 'section-unknown';
            } else {
                $block->section_id = $section->id;
                $block->text = null;
            }
        }

        // Text
        elseif ($input->get_string('text', $text)) {
            $block->convert_text(trim($text) ?: null, $router, $logger);
            $block->section_id = null;
        }

        // Save
        if (count($alerts) === 0) {
            $new = $block->rank === null;

            if (!$block->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                yN\Entity\Board\Log::push($sql, $forum->id, $user->id, 'block.edit', array_merge(
                        array_diff_assoc($block->revert(), $data),
                        $new ? array('new' => true) : array()
                ));
            }
        }
    } else {
        $alerts = null;
    }

    $input->ensure('move', $block->rank);
    $input->ensure('section', $block->section !== null ? $block->section->get_unique() : null);
    $input->ensure('text', $block->revert_text());

    // Get missing sections
    $sections = yN\Entity\Board\Section::get_by_forum__missing($sql, $forum->id);

    // Render template
    $location = 'board.forum.' . $forum->id . '.organize';

    return Glay\Network\HTTP::data($display->render('yn-board-block-edit.deval', $location, array(
        'alerts'	=> $alerts,
        'block'		=> $block,
        'forum'		=> $forum,
        'sections'	=> $sections
    ), $forum->template));
}
