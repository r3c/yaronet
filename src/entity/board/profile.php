<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Account\\User', './entity/account/user.php');
\Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Profile extends \yN\Entity\Model
{
    const AVATAR_GRAVATAR = 1;
    const AVATAR_IMAGE = 2;
    const AVATAR_NONE = 0;

    const GENDER_FEMALE = 1;
    const GENDER_MALE = 0;
    const GENDER_NONE = 2;

    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache;
    public static $schema_load;

    public static function flush($sql)
    {
        return $sql->delete(self::$schema_cache) !== null;
    }

    public static function get_by_forum($sql, $forum_id, $count, $offset)
    {
        return self::entry_get_all(
            $sql,
            array(
                'forum' => (int)$forum_id,
                '+' => array(
                    'forum' => null
                )
            ),
            array('user' => false),
            $count,
            $offset
        );
    }

    public static function get_by_forum__user_login_like($sql, $forum_id, $login, $count, $offset)
    {
        $login = str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), (string)$login);

        if (mb_strlen($login) < 1) {
            return array();
        }

        return self::entry_get_all(
            $sql,
            array(
                'forum' => (int)$forum_id,
                '+' => array(
                    'forum' => null,
                    'user' => array('login|like' => $login . '%', 'is_disabled' => 0)
                )
            ),
            array(
                '+' => array(
                    'user' => array('pulse_time' => false)
                )
            ),
            $count,
            $offset
        );
    }

    public static function get_by_user($sql, $user_id)
    {
        return self::entry_get_one($sql, array('+' => array('forum' => null), 'user' => (int)$user_id));
    }

    public static function get_by_user_login($sql, $user_login)
    {
        return self::entry_get_one($sql, array('+' => array('user' => array('login' => trim($user_login)))));
    }

    public static function get_by_user_login_like($sql, $login, $count, $offset)
    {
        $login = str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), (string)$login);

        if (mb_strlen($login) < 1) {
            return array();
        }

        return self::entry_get_all(
            $sql,
            array(
                '+' => array(
                    'forum' => null,
                    'user' => array('login|like' => $login . '%', 'is_disabled' => 0)
                )
            ),
            array(
                '+' => array(
                    'user' => array('pulse_time' => false)
                )
            ),
            $count,
            $offset
        );
    }

    public static function get_last($sql, $count, $offset)
    {
        return self::entry_get_all(
            $sql,
            array(
                '+' => array('forum' => null)
            ),
            array('user' => false),
            $count,
            $offset
        );
    }

    public static function invalidate($sql, $user_id)
    {
        return self::cache_delete($sql, array('user' => (int)$user_id));
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            if ($sql !== null) {
                self::cache_check($sql, $row, $ns, array('user'));
            }

            $this->avatar = (int)$row[$ns . 'avatar'];
            $this->avatar_tag = (int)$row[$ns . 'avatar_tag'];
            $this->forum = isset($row[$ns . 'forum__id']) ? new Forum($sql, $row, $ns . 'forum__') : null;
            $this->forum_id = isset($row[$ns . 'forum']) ? (int)$row[$ns . 'forum'] : null;
            $this->gender = max(min((int)$row[$ns . 'gender'], 2), 0);
            $this->posts = isset($row[$ns . 'cache__posts']) ? (int)$row[$ns . 'cache__posts'] : 0;
            $this->signature = $row[$ns . 'signature'];
            $this->user = isset($row[$ns . 'user__id']) ? new \yN\Entity\Account\User($sql, $row, $ns . 'user__') : null;
            $this->user_id = (int)$row[$ns . 'user'];
        } else {
            $this->avatar = self::AVATAR_GRAVATAR;
            $this->avatar_tag = 0;
            $this->forum = null;
            $this->forum_id = null;
            $this->gender = self::GENDER_NONE;
            $this->posts = 0;
            $this->signature = \yN\Engine\Text\Markup::blank();
            $this->user = null;
            $this->user_id = null;
        }

        $this->signature_render = array();
    }

    public function convert_signature($plain, $router, $logger)
    {
        $this->signature = \yN\Engine\Text\Markup::convert('bbcode-inline', $plain, \yN\Engine\Text\Markup::context($router, $logger, $this->user));
    }

    public function get_primary()
    {
        if ($this->user_id === null) {
            return null;
        }

        return array('user' => $this->user_id);
    }

    public function render_avatar($router)
    {
        switch ($this->avatar) {
            case self::AVATAR_GRAVATAR:
                return $this->user !== null ? '//www.gravatar.com/avatar/' . md5(strtolower($this->user->email)) . '?d=identicon' : null;

            case self::AVATAR_IMAGE:
                return $router->url('media.image.render', array('name' => 'avatar-' . (int)$this->user_id, 'tag' => $this->avatar_tag));

            default:
                return null;
        }
    }

    public function render_signature($format, $router, $logger)
    {
        if (!isset($this->signature_render[$format])) {
            $this->signature_render[$format] = \yN\Engine\Text\Markup::render($format, $this->signature, \yN\Engine\Text\Markup::context($router, $logger, $this->user));
        }

        return $this->signature_render[$format];
    }

    public function revert_signature()
    {
        return \yN\Engine\Text\Markup::revert('bbcode-inline', $this->signature, \yN\Engine\Text\Markup::context());
    }

    public function save($sql, &$alert)
    {
        $blank_length = strlen(\yN\Engine\Text\Markup::blank());
        $signature_length = strlen($this->signature);

        if ($this->avatar !== self::AVATAR_GRAVATAR && $this->avatar !== self::AVATAR_IMAGE && $this->avatar !== self::AVATAR_NONE) {
            $alert = 'avatar-invalid';
        } elseif ($this->gender !== self::GENDER_FEMALE && $this->gender !== self::GENDER_MALE && $this->gender !== self::GENDER_NONE) {
            $alert = 'gender-invalid';
        } elseif ($signature_length < $blank_length || $signature_length > 1024) {
            $alert = 'signature-length';
        } elseif ($this->user_id === null) {
            $alert = 'user-null';
        } else {
            return parent::save($sql, $alert);
        }

        return false;
    }

    public function set_primary($key)
    {
        $this->user_id = $key;
    }

    protected function export()
    {
        return array(
            'avatar' => $this->avatar,
            'avatar_tag' => $this->avatar_tag,
            'forum' => $this->forum_id,
            'gender' => max(min($this->gender, 2), 0),
            'signature' => $this->signature,
            'user' => $this->user_id
        );
    }
}

Profile::$schema_cache = new \RedMap\Schema(
    'board_profile_cache',
    array(
        'posts' => null,
        'user' => null
    )
);

Profile::$schema_load = new \RedMap\Schema(
    'board_profile',
    array(
        'posts' => '(SELECT COUNT(*) FROM board_post WHERE create_profile = @user)',
        'user' => null
    )
);

Profile::$schema = new \RedMap\Schema(
    'board_profile',
    array(
        'avatar' => null,
        'avatar_tag' => null,
        'forum' => null,
        'gender' => null,
        'signature' => null,
        'user' => null
    ),
    '__',
    array(
        'cache' => array(Profile::$schema_cache, \RedMap\Schema::LINK_IMPLICIT | \RedMap\Schema::LINK_OPTIONAL, array('user' => 'user')),
        'forum' => array(Forum::$schema, \RedMap\Schema::LINK_OPTIONAL, array('forum' => 'id')),
        'user' => array(\yN\Entity\Account\User::$schema, \RedMap\Schema::LINK_IMPLICIT, array('user' => 'id'))
    )
);
