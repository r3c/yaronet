<?php

define('YARONET', 'test');

require '../src/config.php';
require 'assert/http.php';
require 'assert/sql.php';
require 'test.php';

$config_test = array(
    'url'   => 'http://localhost'
);

function _id($prefix)
{
    return $prefix . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
}

function _match($pattern, $delimiter = '/')
{
    return $delimiter . preg_quote($pattern, $delimiter) . $delimiter;
}

// Create & edit message
function account_message_create($user, $recipient)
{
    account_user_signin($user);

    $message = array('sender' => $user['login'], 'text' => _id('text'));

    // Create
    HTTP::assert('messages')
        ->is_success()
        ->matches_html('.control .rss', '//');

    HTTP::assert('messages/new')
        ->is_success()
        ->matches_html('form input[name="to"]', '//');

    HTTP::assert('messages/new', array('text' => $message['text'], 'to' => $recipient['login']))
        ->redirects_to('/messages$/');

    return $message;
}

// Assert message
function account_message_read($user, $message)
{
    account_user_signin($user);

    HTTP::assert('messages')
        ->is_success()
        ->matches_html('.message .origin .from .login', _match($message['sender']))
        ->matches_html('.message .text', _match($message['text']));
}

// Assert memo
function account_memo_edit($user)
{
    account_user_signin($user);

    $memo = array('text' => _id('text'));

    HTTP::assert('memos/edit')
        ->is_success()
        ->matches_html('form textarea', '/name="text"/');

    HTTP::assert('memos/edit', $memo)
        ->redirects_to('/memos$/');

    HTTP::assert('memos')
        ->is_success()
        ->matches_html('.panel-body .markup', _match($memo['text']))
        ->matches_html('.control a.edit', '//');
}

// Create & edit user & profile
function account_user_create($forum_id = null)
{
    // Sign out
    HTTP::assert('users/signout')
        ->redirects_to('//');

    // Create
    HTTP::assert('users/signup')
        ->is_success()
        ->matches_html('h3', '//')
        ->matches_html('p.editorial', '//')
        ->matches_html('input[name=login]', '/type="text"/')
        ->matches_html('input[name=password-1]', '/type="password"/');

    $password = _id('password');
    $user = array('email' => _id('email') . '@example.com', 'login' => _id('login'), 'password-1' => $password, 'password-2' => $password);

    HTTP::assert('users/signup' . ($forum_id !== null ? '/' . $forum_id : ''), $user)
        ->is_success()
        ->matches_html('.notice-ok', '//');

    // Activate
    $user['id'] = SQL::value('SELECT id FROM account_user WHERE login = ?', array($user['login']));

    $recover_time = SQL::value('SELECT recover_time FROM account_user WHERE id = ?', array($user['id']));
    $recover = substr(hash_hmac('crc32b', $recover_time, $user['id']), 0, 8);

    HTTP::assert('users/' . $user['id'] . '/active')
        ->is_success()
        ->matches_html('h3', '//')
        ->matches_html('input[name=code]', '/type="text"/');

    HTTP::assert('users/' . $user['id'] . '/active?code=' . rawurlencode($recover))
        ->is_success()
        ->matches_html('.notice-ok', '//');

    // Check directory
    $http = HTTP::assert('profiles?query=' . rawurlencode($user['login']))
        ->is_success()
        ->matches_html('.grid .cell a[href*=profiles]', _match($user['login']));

    if ($forum_id !== null) {
        $http->matches_html('.grid .cell a[href*=forums]', '@href="[^"]*/forums/' . $forum_id . '@');

        HTTP::assert('profiles?forum=' . $forum_id . '&query=' . rawurlencode($user['login']))
            ->is_success()
            ->matches_html('.grid .cell a[href*=profiles]', _match($user['login']))
            ->matches_html('.grid .cell a[href*=forums]', '@href="[^"]*/forums/' . $forum_id . '@');
    }

    HTTP::assert('profiles.json?query=' . rawurlencode($user['login']))
        ->is_success()
        ->matches_json('items.0', _match($user['login']));

    // Sign-in
    account_user_signin($user);

    // Recover password
    HTTP::assert('users/recover')
        ->is_success()
        ->matches_html('h3', '//');

    HTTP::assert('users/recover', array('email' => $user['email']))
        ->is_success()
        ->matches_html('.notice-ok', '//');

    $recover_time = SQL::value('SELECT recover_time FROM account_user WHERE id = ?', array($user['id']));
    $recover = substr(hash_hmac('sha256', $recover_time, $user['id']), 0, 8);

    HTTP::assert('users/' . $user['id'] . '/reclaim', array('code' => $recover, 'password-1' => $password, 'password-2' => $password))
        ->is_success()
        ->matches_html('.notice-ok', '//');

    // Edit
    $user['avatar'] = 'gravatar';
    $user['login'] = _id('login');
    $user['signature'] = _id('signature');
    $user['template'] = 'html.kyanite';

    HTTP::assert('users/' . $user['id'] . '/edit', array('login' => $user['login'], 'template' => $user['template']))
        ->is_success()
        ->matches_html('body', '//');

    HTTP::assert('profiles/' . $user['id'] . '/edit', array('avatar' => $user['avatar'], 'forum' => '', 'signature' => $user['signature']))
        ->is_success()
        ->matches_html('body', '//');

    // Check directory
    HTTP::assert('profiles?query=' . rawurlencode($user['login']))
        ->is_success()
        ->matches_html('.grid .cell a[href*=profiles]', _match($user['login']))
        ->matches_html('.grid .cell.s6', '-/<a/');

    return $user;
}

// Sign-in
function account_user_signin($user)
{
    static $current;

    if (isset($current) && (int)$current['id'] === (int)$user['id']) {
        return;
    }

    HTTP::assert('users/signin')
        ->is_success()
        ->matches_html('form input[name=login]', '/type="text"/')
        ->matches_html('form input[name=password]', '/type="password"/')
        ->matches_html('form input[name=expire]', '//');

    HTTP::assert('users/signin', array('login' => $user['login'], 'password' => $user['password-1'], 'expire' => 'session'))
        ->redirects_to(_match(''));

    $current = $user;
}

// Update banned IP addresses
function board_ban_set($user, $forum, $addresses)
{
    account_user_signin($user);

    HTTP::assert('bans/' . $forum['id'], array('addresses' => implode(', ', $addresses)))
        ->is_success()
        ->matches_html('form input[name=addresses]', _match(implode(', ', $addresses)));
}

// Find in fresh bookmarks
function board_bookmark_assert($user, $topic, $fresh, $who)
{
    account_user_signin($user);

    HTTP::assert('bookmarks', array(), true)
        ->is_success()
        ->matches_html('a.active-first', _match($topic['name']))
        ->matches_html('a.new-first', _match($topic['name']));

    HTTP::assert('bookmarks/' . ($fresh ? 'f' : 's'))
        ->is_success()
        ->matches_html('a.' . ($fresh ? 'fresh' : 'stale') . '-' . $who, _match($topic['name']));
}

// Assert block statuses
function board_block_assert($user, $forum, $statuses)
{
    // View forum
    $http = HTTP::assert('forums/' . $forum['id'] . '-' . $forum['alias'])
        ->is_success();

    // Check block read state
    foreach ($statuses as $rank => $fresh) {
        $scope = '.forum .blocks #block-' . $rank;

        $http
            ->matches_html($scope . ' .status .' . ($fresh ? 'fresh' : 'stale'), '//');
    }
}

// Create & edit link block
function board_block_create_link($user, $forum, $section)
{
    account_user_signin($user);

    // Create
    HTTP::assert('blocks/new/' . $forum['id'])
        ->is_success()
        ->matches_html('.path h1', '//');

    $unique = $section['forum'] . ', ' . $section['name'];

    HTTP::assert('blocks/new/' . $forum['id'], array('section' => $unique))
        ->redirects_to('@forums/' . $forum['id'] . '/organize$@');

    // Get rank and assert contents
    HTTP::assert('forums/' . $forum['id'] . '/organize')
        ->is_success()
        ->matches_html('.blocks tr[id^=block-]', '/id="block-([0-9]*)"/', $match, true)
        ->matches_html('.blocks #block-' . $match[1] . ' .link a', '@href="[^"]*/sections/' . $forum['id'] . '-' . $section['id'] . '-[^"]*"@');

    $block = array('forum' => $forum['id'], 'rank' => $match[1], 'section' => $section['id'], 'text' => null);

    // Edit
    HTTP::assert('blocks/' . $forum['id'] . '-' . $block['rank'] . '/edit')
        ->is_success()
        ->matches_html('.path h1', '//')
        ->matches_html('form input[name=section]', _match($section['name']))
        ->matches_html('form textarea[name=text]', '-//');

    // FIXME: update section
    //$unique = FIXME;

    HTTP::assert('blocks/' . $forum['id'] . '-' . $block['rank'] . '/edit', array('section' => $unique))
        ->redirects_to('@forums/' . $forum['id'] . '/organize$@');

    HTTP::assert('forums/' . $forum['id'] . '/organize')
        ->matches_html('.blocks #block-' . $block['rank'] . ' .link a', '@href="[^"]*/sections/' . $forum['id'] . '-' . $block['section'] . '-[^"]*"@');

    return $block;
}

// Create & edit text block
function board_block_create_text($user, $forum)
{
    account_user_signin($user);

    // Create
    HTTP::assert('blocks/new/' . $forum['id'])
        ->is_success()
        ->matches_html('.path h1', '//');

    $block = array('text' => _id('text'));

    HTTP::assert('blocks/new/' . $forum['id'], $block)
        ->redirects_to('@forums/' . $forum['id'] . '/organize$@');

    // Get rank and assert contents
    HTTP::assert('forums/' . $forum['id'] . '/organize')
        ->is_success()
        ->matches_html('.blocks tr[id^=block-]', '/id="block-([0-9]*)"/', $match, true)
        ->matches_html('.blocks #block-' . $match[1] . ' td', _match($block['text']));

    $block['rank'] = $match[1];

    // Edit
    HTTP::assert('blocks/' . $forum['id'] . '-' . $block['rank'] . '/edit')
        ->is_success()
        ->matches_html('.path h1', '//')
        ->matches_html('form input[name=section]', '-//')
        ->matches_html('form textarea[name=text]', _match($block['text']));

    $block['text'] = _id('text');

    HTTP::assert('blocks/' . $forum['id'] . '-' . $block['rank'] . '/edit', array('text' => $block['text']))
        ->redirects_to('@forums/' . $forum['id'] . '/organize$@');

    HTTP::assert('forums/' . $forum['id'] . '/organize')
        ->matches_html('.blocks #block-' . $block['rank'] . ' td', _match($block['text']));

    return $block;
}

// Reorder blocks
function board_block_rank($user, $forum, $pairs)
{
    account_user_signin($user);

    // Reorder and update block properties
    foreach ($pairs as &$pair) {
        $block =& $pair[0];
        $move = $pair[1];

        HTTP::assert('blocks/' . $forum['id'] . '-' . $block['rank'] . '/edit', array('move' => $move))
            ->redirects_to('@forums/' . $forum['id'] . '/organize$@');

        if ((int)$block['rank'] !== (int)$move) {
            foreach ($pairs as &$pair_update) {
                $block_update =& $pair_update[0];

                if ($block['rank'] < $move) {
                    if ($block_update['rank'] > $block['rank'] && $block_update['rank'] <= $move) {
                        --$block_update['rank'];
                    }
                } elseif ($block['rank'] > $move) {
                    if ($block_update['rank'] >= $move && $block_update['rank'] < $block['rank']) {
                        ++$block_update['rank'];
                    }
                }
            }

            $block['rank'] = $move;
        }
    }

    // View forum
    $http = HTTP::assert('forums/' . $forum['id'] . '-' . $forum['alias'])
        ->is_success();

    foreach ($pairs as &$pair) {
        $block =& $pair[0];
        $scope = '.forum .blocks #block-' . $block['rank'];

        if ($block['text'] !== null) {
            $http
                ->matches_html($scope, _match($block['text']));
        } else {
            $http
                ->matches_html($scope . ' .link a', '//')
                ->matches_html($scope . ' .link div', '//');
        }
    }
}

// Forum assert
function board_forum_assert($user, $forum)
{
    account_user_signin($user);

    // Check search
    HTTP::assert('forums?query=' . rawurlencode($forum['name']))
        ->is_success()
        ->matches_html('.grid .cell a', _match($forum['name']));

    HTTP::assert('forums.json?query=' . rawurlencode($forum['name']))
        ->is_success()
        ->matches_json('items.0', _match($forum['name']));

    // Check organize page
    HTTP::assert('forums/' . $forum['id'] . '/organize')
        ->is_success()
        ->matches_html('h3', '//');
}

// Forum create & edit
function board_forum_create($user)
{
    account_user_signin($user);

    // Create
    HTTP::assert('forums/new')
        ->is_success()
        ->matches_html('.path h1', '//')
        ->matches_html('.caption label', '//');

    $forum = array('alias' => _id('alias'), 'description' => _id('description'), 'name' => _id('name'));

    HTTP::assert('forums/new', $forum)
        ->redirects_to('@forums/([0-9]+)-' . preg_quote($forum['alias'], '@') . '$@', $match);

    $forum['id'] = $match[1];

    // Edit
    HTTP::assert('forums/' . $forum['id'] . '/edit')
        ->is_success()
        ->matches_html('.path h1', '//');

    $forum['header'] = _id('header');
    $forum['preface'] = _id('preface');

    HTTP::assert('forums/' . $forum['id'] . '/edit', array('header' => $forum['header'], 'preface' => $forum['preface']))
        ->is_success()
        ->matches_html('body', '//');

    HTTP::assert('forums/' . $forum['id'] . '-' . $forum['alias'])
        ->is_success()
        ->matches_html('.page-header .logo', _match($forum['header']))
        ->matches_html('.path h1', _match($forum['name']))
        ->matches_html('.forum .panel-body', _match($forum['preface']))
        ->matches_html('.forum .link', '//');

    board_forum_assert($user, $forum);

    return $forum;
}

// Grant permission on section
function board_permission_section($user, $forum, $section, $target)
{
    HTTP::assert('sections/' . $section['id'] . '/permission')
        ->is_success()
        ->matches_html('.path h1', '//');

    HTTP::assert('sections/' . $section['id'] . '/permission', array('insert[read]' => '1', 'insert[write]' => '1', 'login' => $target['login']))
        ->is_success()
        ->matches_html('.grid .cell', _match($target['login']));
}

// Assert post contents
function board_post_assert($user, $forum, $topic, $post, $check_user)
{
    account_user_signin($user);

    $scope = '#post-' . $post['position'];

    $http = HTTP::assert('topics/' . $topic['id'] . '/' . ceil($post['position'] / 30), array(), false, true)
        ->is_success()
        ->matches_html('.path h1', _match($topic['name']))
        ->matches_html($scope . ' .body .text', _match($post['text']));

    if ($check_user) {
        $http
            ->matches_html($scope . ' .body .edit', '@posts/' . $topic['id'] . '-([0-9]+)/edit@')
            ->matches_html($scope . ' .body .bottom', _match($user['signature']))
            ->matches_html($scope . ' .body .bottom img.avatar', '/www\\.gravatar\\.com/')
            ->matches_html($scope . ' .body .from .login', _match($user['login']));
    }

    HTTP::assert('posts/' . $forum['id'] . '-' . $topic['id'] . '-' . $post['position'] . '.frame')
        ->is_success()
        ->matches_html('.text', _match($post['text']));

    HTTP::assert('posts/' . $topic['id'] . '-' . $post['position'] . '.frame')
        ->is_success()
        ->matches_html('.text', _match($post['text']));

    HTTP::assert('posts/new/' . $topic['id'] . '-' . $post['position'])
        ->is_success()
        ->matches_html('form textarea', '@[quote].*\\(\\./' . $topic['id'] . '-' . $post['position'] . '\\).*[/quote]@');
}

// Create post in given topic
function board_post_create($user, $forum, $topic, $command = null)
{
    account_user_signin($user);

    HTTP::assert('posts/new/' . $topic['id'])
        ->is_success()
        ->matches_html('.path h1', '//');

    $post = array('text' => $command ?: _id('text'));

    HTTP::assert('posts/new/' . $topic['id'], $post)
        ->redirects_to('@topics/[0-9]+-.*#post-([0-9]+)@', $match);

    $post['position'] = $match[1];

    if ($command !== null) {
        $post['text'] = '';
    }

    board_post_assert($user, $forum, $topic, $post, true);

    return $post;
}

// Check post can't be created in given topic
function board_post_create_403($user, $topic)
{
    account_user_signin($user);

    HTTP::assert('posts/new/' . $topic['id'], array('text' => _id('text')))
        ->is_forbidden();
}

// Report a post
function board_post_report($user, $topic, $post)
{
    account_user_signin($user);

    $report = array('reason' => _id('reason'), 'sender' => $user['login'], 'topic' => $topic['id']);

    HTTP::assert('posts/' . $topic['id'] . '-' . $post['position'] . '/report.frame', array('reason' => $report['reason']))
        ->is_success()
        ->matches_html('.notice-ok', '//');

    return $report;
}

// Search for reported post
function board_post_report_check($user, $report)
{
    account_user_signin($user);

    HTTP::assert('messages')
        ->is_success()
        ->matches_html('.message .origin .from', _match($report['sender']))
        ->matches_html('.message .text', _match($report['reason']))
        ->matches_html('.message .text a[href*=topics]', _match($report['topic']));
}

// Search for post in forum
function board_search_execute($user, $forum, $post, $author, $exists)
{
    account_user_signin($user);

    $scope = '#post-' . $post['position'];

    HTTP::assert('searches/new/' . $forum['id'])
        ->is_success()
        ->matches_html('form input[name="query"]', '//');

    $params = array('query' => $post['text']);

    if ($author !== null) {
        $params['login'] = $author['login'];
    }

    HTTP::assert('searches/new/' . $forum['id'], $params)
        ->redirects_to('@searches/([0-9]+)$@', $match);

    HTTP::assert('searches/' . $match[1])
        ->is_success()
        ->matches_html($scope . ' .body .text', ($exists ? '+' : '-') . _match($post['text']));
}

// Assert section contents
function board_section_assert($user, $section)
{
    account_user_signin($user);

    // Check display
    HTTP::assert('sections/' . $section['id'], array(), false, true)
        ->is_success()
        ->matches_html('.section .markup', _match($section['header']))
        ->matches_html('.section .panel-body h3', _match($section['name']));

    // Check search
    HTTP::assert('sections.json?query=' . rawurlencode($section['name']))
        ->is_success()
        ->matches_json('items.0', _match($section['name']));
}

// Create section & edit
function board_section_create($user, $forum, $access)
{
    account_user_signin($user);

    // Create
    HTTP::assert('sections/new/' . $forum['id'])
        ->is_success()
        ->matches_html('.path li', '//')
        ->matches_html('form input[name="description"]', '//')
        ->matches_html('form input[name="name"]', '//');

    $section = array('access' => $access, 'description' => _id('description'), 'forum' => $forum['id'], 'name' => _id('name'));

    HTTP::assert('sections/new/' . $forum['id'], $section)
        ->redirects_to('@sections/([0-9]+)-' . preg_quote($section['name'], '@') . '$@', $match);

    $section['id'] = (int)$match[1];

    // Get rank
    HTTP::assert('forums/' . $forum['id'] . '/organize')
        ->is_success()
        ->matches_html('.blocks tr[id^=block-]', '/id="block-([0-9]*)"/', $match, true);

    $block = array('forum' => $forum['id'], 'rank' => $match[1], 'section' => $section['id'], 'text' => null);

    // Edit
    HTTP::assert('sections/' . $section['id'] . '/edit')
        ->is_success()
        ->matches_html('.path li', '//')
        ->matches_html('form input[name="description"]', _match($section['description']))
        ->matches_html('form input[name="name"]', _match($section['name']));

    $section['access'] = $access;
    $section['header'] = _id('header');
    $section['reach'] = '2';

    HTTP::assert('sections/' . $section['id'] . '/edit', $section)
        ->is_success()
        ->matches_html('body', '//');

    board_section_assert($user, $section);

    return array($section, $block);
}

// Merge section into another
function board_section_merge($user, $section, $into)
{
    HTTP::assert('sections/' . $section['id'] . '/merge')
        ->is_success()
        ->matches_html('h3', '//');

    HTTP::assert('sections/' . $section['id'] . '/merge', array('into' => $into['id']))
        ->redirects_to('@forums/' . $into['forum'] . '-@');

    HTTP::assert('sections/' . $section['id'])
        ->is_not_found();
}

// Subscribe to section
function board_section_subscribe($user, $section)
{
    account_user_signin($user);

    HTTP::assert('subscriptions/' . $section['id'] . '/set')
        ->redirects_to('@sections/([0-9]+)-@');
}

// Assert topic and child posts contents
function board_topic_assert($user, $forum, $section, $topic, $posts)
{
    HTTP::assert('sections/' . $section['id'], array(), false, true)
        ->is_success()
        ->matches_html('.section h2', '/Section/')
        ->matches_html('.topics .link a.name', _match($topic['name']));

    HTTP::assert('topics/' . $topic['id'], array(), false, true)
        ->is_success()
        ->matches_html('.path h1', _match($topic['name']));

    foreach ($posts as $post) {
        board_post_assert($user, $forum, $topic, $post, false);
    }
}

// Check topic can't be viewed
function board_topic_assert_403($user, $topic)
{
    account_user_signin($user);

    HTTP::assert('topics/' . $topic['id'], array(), false, true)
        ->is_forbidden();
}

// Create new topic in given section
function board_topic_create($user, $forum, $section)
{
    account_user_signin($user);

    HTTP::assert('topics/new/' . $section['id'])
        ->is_success()
        ->matches_html('.path h1', '//');

    $topic_name = _id('name');
    $post_text = _id('text');

    HTTP::assert('topics/new/' . $section['id'], array(
        'name' => $topic_name,
        'text' => $post_text
    ))
        ->redirects_to('@topics/([0-9]+)@', $match);

    $topic = array(
        'id' => $match[1],
        'name' => $topic_name
    );

    $post = array(
        'position' => '1',
        'text' => $post_text
    );

    if ($section['access'] >= 1) {
        HTTP::assert('forums/' . $forum['id'] . '-' . $forum['alias'])
            ->is_success()
            ->matches_html('.forum h3', _match($forum['name']))
            ->matches_html('.last .sources .source .topics', '//')
            ->matches_html('.last a.active-self', _match($topic['name']))
            ->matches_html('.last a.new-self', _match($topic['name']));

        HTTP::assert('forums/' . $forum['id'] . '/active')
            ->is_success()
            ->matches_html('.topics .link a.name', _match($topic['name']));
    }

    board_topic_assert($user, $forum, $section, $topic, array($post));

    return array($topic, $post);
}

// Check topic can't be created in given section
function board_topic_create_403($user, $section)
{
    account_user_signin($user);

    HTTP::assert('topics/new/' . $section['id'], array('name' => _id('name'), 'text' => _id('text')))
        ->is_forbidden();
}

// Drift existing topic
function board_topic_drift($user, $forum, $section, $source, $posts)
{
    account_user_signin($user);

    HTTP::assert('topics/drift/' . $source['id'])
        ->is_success()
        ->matches_html('.path h1', '//');

    $topic_name = _id('name');

    HTTP::assert('topics/drift/' . $source['id'], array(
        'name' => $topic_name,
        'positions' => implode(',', array_map(function ($post) {
            return $post['position'];
        }, $posts)),
        'section' => $forum['name'] . ', ' . $section['name']
    ))
        ->redirects_to('@topics/([0-9]+)@', $match);

    $position = 1;
    $topic = array(
        'id' => $match[1],
        'name' => $topic_name
    );

    array_unshift($posts, array('text' => ''));

    foreach ($posts as &$post) {
        $post['position'] = $position++;
    }

    board_topic_assert($user, $forum, $section, $topic, $posts);

    return $topic;
}

// Change topic name, rank and parent section
function board_topic_edit($user, $topic, $forum, $section)
{
    account_user_signin($user);

    HTTP::assert('topics/' . $topic['id'] . '/edit')
        ->is_success()
        ->matches_html('form input[name=name]', _match($topic['name']))
        ->matches_html('form input[name=section]', '/type="text"/')
        ->matches_html('form input[name=weight]', '/0/');

    $section_move = $forum['name'] . ', ' . $section['name'];
    $topic['name'] .= ' : ' . _id('name');
    $topic['weight'] = 42;

    $h1 = HTTP::assert('topics/' . $topic['id'] . '/edit', array('name' => $topic['name'], 'weight' => $topic['weight'], 'section' => $section_move))
        ->is_success();

    $h2 = HTTP::assert('topics/' . $topic['id'] . '/edit')
        ->is_success();

    foreach (array($h1, $h2) as $http) {
        $http
            ->matches_html('form input[name=name]', _match($topic['name']))
            ->matches_html('form input[name=section]', _match($section_move))
            ->matches_html('form input[name=weight]', _match($topic['weight']));
    }

    return $topic;
}

// Post on FlashChat
function chat_shout_create($user)
{
    account_user_signin($user);

    $shout = array('text' => _id('text'));

    HTTP::assert('shouts/new', $shout)
        ->redirects_to('//');

    HTTP::assert('')
        ->is_success()
        ->matches_html('.flashchat .author', _match($user['login']))
        ->matches_html('.flashchat .text', _match($shout['text']));

    return $shout;
}

// Ensure readable pages are served on error
function error_assert()
{
    HTTP::assert('invalid/route')
        ->is_not_found()
        ->matches_html('body', '//');

    HTTP::assert('invalid/route.json')
        ->is_not_found()
        ->matches_html('body', '//');

    HTTP::assert('forums/123456789')
        ->is_not_found()
        ->matches_html('body', '//');

    HTTP::assert('forums/123456789.json')
        ->is_not_found()
        ->matches_json('alerts.0', '/site/');
}

// Browse home page
function home_assert()
{
    HTTP::assert('')
        ->is_success()
        ->matches_html('.flashchat h2', '//')
        ->matches_html('.index h2', '//');
}

test_start();

// Error checks
error_assert();

/*
** User 1
*/

// Create account and assert memo, home page and shouts
$account_user_1 = account_user_create();

account_memo_edit($account_user_1);

home_assert();
chat_shout_create($account_user_1);

// Create forum 1 with one single section
$board_forum_1 = board_forum_create($account_user_1);

list($board_section_1_1, $board_block_1_1) = board_section_create($account_user_1, $board_forum_1, 2 /* open */);

// Create forum 2 with three sections, a text block and a link to section from forum 1
$board_forum_2 = board_forum_create($account_user_1);

list($board_section_2_1, $board_block_2_1) = board_section_create($account_user_1, $board_forum_2, 2 /* open */);
list($board_section_2_2, $board_block_2_2) = board_section_create($account_user_1, $board_forum_2, 1 /* silent */);
list($board_section_2_3, $board_block_2_3) = board_section_create($account_user_1, $board_forum_2, 0 /* hidden */);
$board_block_2_4 = board_block_create_text($account_user_1, $board_forum_2);
$board_block_2_5 = board_block_create_link($account_user_1, $board_forum_2, $board_section_1_1);

board_block_rank($account_user_1, $board_forum_2, array(array(&$board_block_2_1, 1), array(&$board_block_2_2, 4), array(&$board_block_2_3, 5), array(&$board_block_2_4, 3), array(&$board_block_2_5, 2)));

list($board_topic_2_1_1, $board_post_2_1_1_1) = board_topic_create($account_user_1, $board_forum_2, $board_section_2_1);
list($board_topic_2_2_1, $board_post_2_2_1_1) = board_topic_create($account_user_1, $board_forum_2, $board_section_2_2);

board_block_assert($account_user_1, $board_forum_2, array(1 => false, 3 => false, 5 => false));
board_bookmark_assert($account_user_1, $board_topic_2_2_1, false, 'self');

list($board_topic_2_3_1, $board_post_2_3_1_1) = board_topic_create($account_user_1, $board_forum_2, $board_section_2_3);

$board_post_2_1_1_2 = board_post_create($account_user_1, $board_forum_2, $board_topic_2_1_1);

/*
** User 2
*/

// Create account
$account_user_2 = account_user_create($board_forum_2['id']);

// Create and configure forum
$board_forum_3 = board_forum_create($account_user_2);

list($board_section_3_1, $board_block_3_1) = board_section_create($account_user_2, $board_forum_3, 2 /* open */);

board_block_rank($account_user_2, $board_forum_3, array(array(&$board_block_3_1, 1)));

list($board_topic_3_1_1, $board_post_3_1_1_1) = board_topic_create($account_user_2, $board_forum_3, $board_section_3_1);

// Ban user 1 from forum 2
board_ban_set($account_user_2, $board_forum_3, array('127.0.0.1'));

// Send message to user 1
$account_message_1 = account_message_create($account_user_2, $account_user_1);

account_message_read($account_user_2, $account_message_1);

// Send posts to forum
$board_post_2_1_1_3 = board_post_create($account_user_2, $board_forum_2, $board_topic_2_1_1);
$board_report_1 = board_post_report($account_user_2, $board_topic_2_1_1, $board_post_2_1_1_3);
$board_topic_2_1_2 = board_topic_drift($account_user_2, $board_forum_2, $board_section_2_1, $board_topic_2_1_1, array($board_post_2_1_1_1, $board_post_2_1_1_2));

board_block_assert($account_user_2, $board_forum_2, array(1 => false, 3 => true, 5 => true));
board_bookmark_assert($account_user_2, $board_topic_2_1_1, false, 'other');
board_section_subscribe($account_user_2, $board_section_2_1);
board_topic_create_403($account_user_2, $board_section_2_2);
board_post_create_403($account_user_2, $board_topic_2_2_1);
board_topic_assert_403($account_user_2, $board_topic_2_3_1);

// Search for post from user 1 in silent section
board_search_execute($account_user_2, $board_forum_2, $board_post_2_2_1_1, $account_user_1, true);

// Search for post from any user in hidden section
board_search_execute($account_user_2, $board_forum_2, $board_post_2_3_1_1, null, false);

/*
** User 1
*/

// Sign-in as user 1, read posts from forum, grant permissions to user 2
account_message_read($account_user_1, $account_message_1);

// Check status was set to "fresh" for section_2_1 due to post from user 2
board_block_assert($account_user_1, $board_forum_2, array(1 => true, 3 => false, 5 => false));

// Check report sent from user 2
board_post_report_check($account_user_1, $board_report_1);

// Check bookmark indicates new post from user 2
board_bookmark_assert($account_user_1, $board_topic_2_1_1, true, 'self');

// Rename, move and change order of topic 2.1
$board_topic_2_2_1 = board_topic_edit($account_user_1, $board_topic_2_2_1, $board_forum_2, $board_section_2_3);

// Grant permissions to user 2 on sections 2 and 3
board_permission_section($account_user_1, $board_forum_2, $board_section_2_2, $account_user_2);
board_permission_section($account_user_1, $board_forum_2, $board_section_2_3, $account_user_2);

// Create new topic 3.1, kick user 2 from it
list($board_topic_2_1_3) = board_topic_create($account_user_1, $board_forum_2, $board_section_2_1);

board_post_create($account_user_1, $board_forum_2, $board_topic_2_1_3, '!kick ' . $account_user_2['login']);

// Create post in previously existing topic to ensure subscription applies even when added after the topic
board_post_create($account_user_1, $board_forum_2, $board_topic_2_1_1, 'Something');

// Check status was reset to "stale" for section_2_1
board_block_assert($account_user_1, $board_forum_2, array(1 => false, 3 => false, 5 => false));

// Create open section 4 and merge section 1 into it
list($board_section_2_4) = board_section_create($account_user_1, $board_forum_2, 2);

board_section_merge($account_user_1, $board_section_2_1, $board_section_2_4);

// Try to post in forum 2 while being banned
board_post_create_403($account_user_1, $board_topic_3_1_1);
board_topic_create_403($account_user_1, $board_section_3_1);

/*
** User 2
*/

// Check bookmarks, create topics in sections 1 & 2, post to section 3, read section 4
board_bookmark_assert($account_user_2, $board_topic_2_1_1, true, 'other');
board_bookmark_assert($account_user_2, $board_topic_2_1_3, true, 'first');
board_topic_create($account_user_2, $board_forum_2, $board_section_2_2);
$board_post_2_3_1_1 = board_post_create($account_user_2, $board_forum_2, $board_topic_2_3_1);
board_search_execute($account_user_2, $board_forum_2, $board_post_2_3_1_1, null, true);
board_section_assert($account_user_2, $board_section_2_3);
board_post_create_403($account_user_2, $board_topic_2_1_3);

test_stop();
