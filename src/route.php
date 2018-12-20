<?php

$routes = array(
    '!prefix' => yN\Engine\Network\URL::to_page(),
    '!suffix' => '(.<_template:[.0-9A-Za-z]+>)',

    'account.activity.' => array('activities', array(
        'list' => array('', 'GET', 'call', 'activity_list', './page/account/activity.php'),
        'pulse' => array('/pulse', 'GET', 'call', 'activity_pulse', './page/account/activity.php')
    )),
    'account.application.' => array('applications', array(
        '' => array('/<application:\\d+>', array(
            'authorize' => array('/authorize', 'GET', 'call', 'application_authorize', './page/account/application.php')
        ))
    )),
    'account.memo.' => array('memos', array(
        'edit' => array('/edit', 'GET,POST', 'call', 'memo_edit', './page/account/memo.php'),
        'view' => array('', 'GET', 'call', 'memo_view', './page/account/memo.php')
    )),
    'account.message.' => array('messages', array(
        '' => array('/<message:\\d+>', array(
            'delete' => array('/delete', 'GET,POST', 'call', 'message_delete', './page/account/message.php'),
            'edit' => array('/edit', 'GET,POST', 'call', 'message_edit', './page/account/message.php'),
            'hide' => array('/hide', 'GET,POST', 'call', 'message_hide', './page/account/message.php')
        )),
        'list' => array('(_<from:\\d+>)', 'GET', 'call', 'message_list', './page/account/message.php'),
        'new' => array('/new(/<reply:\\d+>)', 'GET,POST', 'call', 'message_edit', './page/account/message.php')
    )),
    'account.user.' => array('users', array(
        '' => array('/<user:\\d+>', array(
            'active' => array('/active', 'GET', 'call', 'user_active', './page/account/user.php'),
            'edit' => array('/edit', 'GET,POST', 'call', 'user_edit', './page/account/user.php'),
            'reclaim' => array('/reclaim', 'GET,POST', 'call', 'user_reclaim', './page/account/user.php'),
            'view' => array('', 'GET', 'call', 'user_view', './page/account/user.php')
        )),
        'recover' => array('/recover', 'GET,POST', 'call', 'user_recover', './page/account/user.php'),
        'signin' => array('/signin', 'GET,POST', 'call', 'user_signin', './page/account/user.php'),
        'signout' => array('/signout', 'GET', 'call', 'user_signout', './page/account/user.php'),
        'signup' => array('/signup(/<forum:\\d+>)', 'GET,POST', 'call', 'user_edit', './page/account/user.php')
    )),

    'board.ban.' => array('bans/<forum:\\d+>', array(
        'edit' => array('', 'GET,POST', 'call', 'ban_edit', './page/board/ban.php')
    )),
    'board.block.' => array('blocks', array(
        '' => array('/<forum:\\d+>-<rank:\\d+>', array(
            'delete' => array('/delete', 'GET,POST', 'call', 'block_delete', './page/board/block.php'),
            'edit' => array('/edit', 'GET,POST', 'call', 'block_edit', './page/board/block.php')
        )),
        'new' => array('/new/<forum:\\d+>', 'GET,POST', 'call', 'block_edit', './page/board/block.php')
    )),
    'board.bookmark.' => array('bookmarks', array(
        '' => array('/<topic:\\d+>', array(
            'clear' => array('/clear', 'GET', 'call', 'bookmark_clear', './page/board/bookmark.php'),
            'mark' => array('/mark/<position:\\d+>', 'GET', 'call', 'bookmark_mark', './page/board/bookmark.php'),
            'set' => array('/set', 'GET', 'call', 'bookmark_set', './page/board/bookmark.php')
        )),
        'list' => array('(/<what:[flns]{1,4}:ln>)(-<count:\\d+:20>)', 'GET', 'call', 'bookmark_list', './page/board/bookmark.php')
    )),
    'board.favorite.' => array('favorites(/<profile:\\d+>)', array(
        'edit' => array('', 'GET,POST', 'call', 'favorite_edit', './page/board/favorite.php')
    )),
    'board.forum.' => array('forums', array(
        '' => array('/<forum:\\d+>', array(
            'active' => array('/active', 'GET', 'call', 'forum_active', './page/board/forum.php'),
            'edit' => array('/edit', 'GET,POST', 'call', 'forum_edit', './page/board/forum.php'),
            'organize' => array('/organize', 'GET', 'call', 'forum_organize', './page/board/forum.php'),
            'permission' => array('/permission', 'GET,POST', 'call', 'forum_permission', './page/board/forum.php'),
            'view' => array('(-<forum_alias:[-[%:alnum%:]]*>)', 'GET', 'call', 'forum_view', './page/board/forum.php')
        )),
        'list' => array('', 'GET', 'call', 'forum_list', './page/board/forum.php'),
        'new' => array('/new', 'GET,POST', 'call', 'forum_edit', './page/board/forum.php')
    )),
    'board.ignore.' => array('ignores/<target:\\d+>', array(
        'clear' => array('/clear', 'GET', 'call', 'ignore_clear', './page/board/ignore.php'),
        'set' => array('/set', 'GET', 'call', 'ignore_set', './page/board/ignore.php')
    )),
    'board.log.' => array('logs/', array(
        'list' => array('<forum:\\d+>', 'GET,POST', 'call', 'log_list', './page/board/log.php')
    )),
    'board.post.' => array('posts', array(
        '' => array('/(<forum:\\d+>-)<topic:\\d+>-<position:\\d+>', array(
            'edit' => array('/edit', 'GET,POST', 'call', 'post_edit', './page/board/post.php'),
            'report' => array('/report', 'POST', 'call', 'post_report', './page/board/post.php'),
            'view' => array('', 'GET', 'call', 'post_view', './page/board/post.php')
        )),
        'new' => array('/new/<topic:\\d+>(-<quote:\\d+>)', 'GET,POST', 'call', 'post_edit', './page/board/post.php')
    )),
    'board.profile.' => array('profiles', array(
        '' => array('/<profile:\\d+>', array(
            'delete' => array('/delete', 'GET,POST', 'call', 'profile_delete', './page/board/profile.php'),
            'edit' => array('/edit', 'GET,POST', 'call', 'profile_edit', './page/board/profile.php'),
            'view' => array('', 'GET', 'call', 'profile_view', './page/board/profile.php')
        )),
        'list' => array('', 'GET', 'call', 'profile_list', './page/board/profile.php')
    )),
    'board.search.' => array('searches', array(
        '' => array('/<search:\\d+>', array(
            'view' => array('(/<from:\\d+:0>)', 'GET', 'call', 'search_view', './page/board/search.php')
        )),
        'new' => array('/new/<forum:\\d+>', 'GET,POST', 'call', 'search_edit', './page/board/search.php')
    )),
    'board.section.' => array('sections', array(
        '' => array('/(<forum:\\d+>-)<section:\\d+>', array(
            'edit' => array('/edit', 'GET,POST', 'call', 'section_edit', './page/board/section.php'),
            'merge' => array('/merge', 'GET,POST', 'call', 'section_merge', './page/board/section.php'),
            'permission' => array('/permission', 'GET,POST', 'call', 'section_permission', './page/board/section.php'),
            'view' => array('(-<section_hint:[-[%:alnum%:]]*>)(/<page:\\d+:1>)', 'GET', 'call', 'section_view', './page/board/section.php')
        )),
        'list' => array('', 'GET', 'call', 'section_list', './page/board/section.php'),
        'new' => array('/new/<forum:\\d+>', 'GET,POST', 'call', 'section_edit', './page/board/section.php')
    )),
    'board.subscription.' => array('subscriptions', array(
        '' => array('/<section:\\d+>', array(
            'clear' => array('/clear', 'GET', 'call', 'subscription_clear', './page/board/subscription.php'),
            'set' => array('/set', 'GET', 'call', 'subscription_set', './page/board/subscription.php')
        ))
    )),
    'board.topic.' => array('topics', array(
        '' => array('/(<forum:\\d+>-)<topic:\\d+>', array(
            'delete' => array('/delete', 'GET,POST', 'call', 'topic_delete', './page/board/topic.php'),
            'edit' => array('/edit', 'GET,POST', 'call', 'topic_edit', './page/board/topic.php'),
            'view' => array('(-<topic_hint:[-[%:alnum%:]]*>)(/<page:\\d+:1>)', 'GET', 'call', 'topic_view', './page/board/topic.php')
        )),
        'drift' => array('/drift/<source:\\d+>', 'GET,POST', 'call', 'topic_edit', './page/board/topic.php'),
        'new' => array('/new/<section:\\d+>', 'GET,POST', 'call', 'topic_edit', './page/board/topic.php')
    )),

    'chat.shout.' => array('shouts', array(
        '' => array('/<shout:\\d+>', array(
            'delete' => array('/delete', 'GET,POST', 'call', 'shout_delete', './page/chat/shout.php')
        )),
        'new' => array('/new', 'GET,POST', 'call', 'shout_edit', './page/chat/shout.php')
    )),

    'help.page.' => array('pages', array(
        '' => array('/<label:[-[%:alnum%:]]+>(_<language:[-[%:alnum%:]]+>)', array(
            'edit' => array('/edit', 'GET,POST', 'call', 'page_edit', './page/help/page.php'),
            'view' => array('', 'GET', 'call', 'page_view', './page/help/page.php')
        ))
    )),

    'home' => array('(index.php)', 'GET', 'call', 'home', './page/home.php'),

    'media.emoji.' => array('emojis', array(
        'list' => array('/list/<type:[a-z]+>', 'GET', 'call', 'emoji_list', './page/media/emoji.php'),
        'new' => array('/new', 'GET,POST', 'call', 'emoji_edit', './page/media/emoji.php')
    )),
    'media.image.' => array('images', array(
        'render' => array('/<name:[-[%:alnum%:]]+>(/<tag:[0-9]+>)', 'GET', 'call', 'image_render', './page/media/image.php')
    )),
    'media.language.' => array('languages', array(
        'list' => array('', 'GET', 'call', 'language_list', './page/media/language.php')
    )),

    'resource.markup.' => array('markups', array(
        'render' => array('/<syntax:[-a-z]+>/<format:[-a-z]+>', 'POST', 'call', 'markup_render', './page/resource/markup.php')
    )),

    'survey.poll.' => array('polls', array(
        '' => array('/<poll:\\d+>', array(
            'view' => array('', 'GET', 'call', 'poll_view', './page/survey/poll.php'),
            'vote' => array('/vote', 'POST', 'call', 'poll_vote', './page/survey/poll.php')
        )),
        'new' => array('/new', 'GET,POST', 'call', 'poll_edit', './page/survey/poll.php')
    )),

    'system.check.' => array('checks', array(
        'php' => array('/php', 'GET', 'call', 'check_php', './page/system/check.php')
    )),
    'system.task.' => array('tasks', array(
        'clean' => array('/clean', 'GET', 'call', 'task_clean', './page/system/task.php'),
        'flush' => array('/flush', 'GET', 'call', 'task_flush', './page/system/task.php')
    ))
);
