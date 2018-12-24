<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Favorite', './entity/board/favorite.php');
Glay\using('yN\\Entity\\Chat\\Shout', './entity/chat/shout.php');

function home($request, $logger, $sql, $display, $input, $user)
{
    // Get personal and popular favorites
    $favorite_forum = function ($favorite) {
        return $favorite->forum;
    };

    $personal = array_map($favorite_forum, yN\Entity\Board\Favorite::get_by_profile($sql, $user->is_favorite ? $user->id : 0));
    $popular = array_map($favorite_forum, yN\Entity\Board\Favorite::get_by_profile($sql, 1));
    $random = yN\Entity\Board\Forum::get_random($sql, 4);

    // Get latest shouts
    $shouts = yN\Entity\Chat\Shout::get_last($sql, 30);

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-home.deval', 'home', array(
        'personal' => $personal,
        'popular' => $popular,
        'random' => $random,
        'shouts' => $shouts
    )));
}
