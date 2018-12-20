<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Board\\Bookmark', './entity/board/bookmark.php');
\Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
\Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');
\Glay\using('yN\\Entity\\Board\\Reference', './entity/board/reference.php');
\Glay\using('yN\\Entity\\Board\\Section', './entity/board/section.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Topic extends \yN\Entity\Model
{
    const DRIFT_MAX = 30;

    const MODEL_COST = 2;

    const ORDER_CREATE = 0;
    const ORDER_LAST = 1;

    const PAGE_SIZE = 30;

    const WEIGHT_MAX = 127;
    const WEIGHT_MIN = 0;

    public static $schema;
    public static $schema_cache;
    public static $schema_load;

    public static function flush($sql)
    {
        return $sql->delete(self::$schema_cache) !== null;
    }

    public static function get_by_identifier($sql, $topic_id, $profile_id)
    {
        $relations = array(
            'section' => array('+' => array('forum' => null))
        );

        if ($profile_id !== null) {
            $relations['bookmark'] = array('!profile' => (int)$profile_id);
        }

        return self::entry_get_one($sql, array('id' => (int)$topic_id, '+' => $relations));
    }

    public static function get_by_last_time($sql, $order, $count, $profile_id)
    {
        global $time;

        switch ($order) {
            case self::ORDER_CREATE:
                $order = array('id' => false);

                break;

            case self::ORDER_LAST:
                $order = array('last_time' => false);

                break;

            default:
                $order = array();

                break;
        }

        $relations = array(
            'section' => array(
                'access|ge' => Section::ACCESS_PUBLIC,
                'reach|ge' => Section::REACH_GLOBAL
            )
        );

        if ($profile_id !== null) {
            $relations['bookmark'] = array('!profile' => (int)$profile_id, array('~' => 'or', 'watch|eq' => false, 'watch|is' => null));
        }

        $filters = array(
            'is_closed' => 0,
            'last_time|ge' => $time - 30 * 86400,
            '+' => $relations
        );

        return self::entry_get_all($sql, $filters, $order, $count);
    }

    public static function get_by_last_time_forum($sql, $forum_id, $order, $count, $profile_id)
    {
        global $time;

        switch ($order) {
            case self::ORDER_CREATE:
                $order = array('id' => false);

                break;

            case self::ORDER_LAST:
                $order = array('last_time' => false);

                break;

            default:
                $order = array();

                break;
        }

        $relations = array(
            'section' => array(
                'access|ge' => Section::ACCESS_PUBLIC,
                'reach|ge' => Section::REACH_LOCAL,
                '+' => array(
                    'forum' => array('id' => (int)$forum_id)
                )
            )
        );

        if ($profile_id !== null) {
            $relations['bookmark'] = array('!profile' => (int)$profile_id);
            $relations['permission'] = array('!profile' => (int)$profile_id);
            $relations['section']['+']['permission'] = array('!profile' => (int)$profile_id);
        }

        $filters = array(
            'is_closed' => 0,
            'last_time|ge' => $time - 10 * 86400,
            '+' => $relations
        );

        return self::entry_get_all($sql, $filters, $order, $count);
    }

    public static function get_by_section($sql, $section_id, $profile_id, $page)
    {
        $relations = array('section' => null);

        // FIXME: filter visible topics only [hack-private-topics]
        if ((int)$section_id === 1000) {
            $relations['permission_must'] = array('!profile' => (int)$profile_id, 'can_read' => true);
        }

        if ($profile_id !== null) {
            $relations['bookmark'] = array('!profile' => (int)$profile_id);
            $relations['permission'] = array('!profile' => (int)$profile_id);
        }

        return self::entry_get_all(
            $sql,
            array('section' => (int)$section_id, '+' => $relations),
            array('weight' => false, 'last_time' => false),
            Section::PAGE_SIZE,
            $page * Section::PAGE_SIZE
        );
    }

    public static function get_page($position)
    {
        return ceil($position / self::PAGE_SIZE);
    }

    public static function invalidate($sql, $topic_id)
    {
        return self::cache_delete($sql, array('id' => (int)$topic_id));
    }

    protected static function on_cache(&$cache)
    {
        $cache['hint'] = self::sanitize_name($cache['hint']);
    }

    private static function sanitize_name($name)
    {
        \Glay\using('yN\\Engine\\Network\\URL', './engine/network/url.php');

        return \yN\Engine\Network\URL::sanitize_path(\yN\Engine\Text\Markup::render('text', $name, \yN\Engine\Text\Markup::context())) ?: null;
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            if ($sql !== null) {
                self::cache_check($sql, $row, $ns, array('id'));
            }

            $this->bookmark = isset($row[$ns . 'bookmark__position']) ? new Bookmark($sql, $row, $ns . 'bookmark__') : null;
            $this->create_profile = isset($row[$ns . 'cache__create_profile__user']) ? new Profile($sql, $row, $ns . 'cache__create_profile__') : null;
            $this->create_time = isset($row[$ns . 'cache__create_time']) ? (int)$row[$ns . 'cache__create_time'] : null;
            $this->hint = $row[$ns . 'cache__hint'];
            $this->id = (int)$row[$ns . 'id'];
            $this->is_closed = (int)$row[$ns . 'is_closed'] !== 0;
            $this->last_profile = isset($row[$ns . 'cache__last_profile__user']) ? new Profile($sql, $row, $ns . 'cache__last_profile__') : null;
            $this->last_time = (int)$row[$ns . 'last_time'];
            $this->name = $row[$ns . 'name'];
            $this->permission = isset($row[$ns . 'permission__profile']) ? new Permission($sql, $row, $ns . 'permission__') : new Permission();
            $this->posts = isset($row[$ns . 'cache__posts']) ? (int)$row[$ns . 'cache__posts'] : null;
            $this->section = isset($row[$ns . 'section__id']) ? new Section($sql, $row, $ns . 'section__') : null;
            $this->section_id = (int)$row[$ns . 'section'];
            $this->weight = (int)$row[$ns . 'weight'];
        } else {
            $this->bookmark = null;
            $this->create_profile = null;
            $this->create_time = null;
            $this->hint = null;
            $this->id = null;
            $this->is_closed = false;
            $this->last_profile = null;
            $this->last_time = $time;
            $this->name = \yN\Engine\Text\Markup::blank();
            $this->permission = new Permission();
            $this->posts = null;
            $this->section = null;
            $this->section_id = null;
            $this->weight = 0;
        }
    }

    public function allow_edit($access)
    {
        return $access->can_change;
    }

    public function allow_moderate($access)
    {
        return $access->can_change;
    }

    public function allow_reply($access)
    {
        return $access->can_read && $access->can_write && !$this->is_closed;
    }

    public function allow_view($access)
    {
        return $access->can_change || $access->can_read;
    }

    public function convert_name($plain, $router, $logger)
    {
        $user = $this->create_profile !== null ? $this->create_profile->user : null;

        $this->name = \yN\Engine\Text\Markup::convert('topic', $plain, \yN\Engine\Text\Markup::context($router, $logger, $user, $this->id));
    }

    public function get_primary()
    {
        if ($this->id === null) {
            return null;
        }

        return array('id' => $this->id);
    }

    public function render_name($format, $router, $logger)
    {
        $user = $this->create_profile !== null ? $this->create_profile->user : null;

        return \yN\Engine\Text\Markup::render($format, $this->name, \yN\Engine\Text\Markup::context($router, $logger, $user, $this->id));
    }

    public function revert()
    {
        return array(
            'id' => $this->id,
            'is_closed' => $this->is_closed,
            'last_time' => $this->last_time,
            'name' => $this->revert_name(),
            'section' => $this->section_id,
            'weight' => $this->weight
        );
    }

    public function revert_name()
    {
        return \yN\Engine\Text\Markup::revert('topic', $this->name, \yN\Engine\Text\Markup::context());
    }

    public function save($sql, &$alert)
    {
        $blank_length = strlen(\yN\Engine\Text\Markup::blank());
        $name_length = strlen($this->name);

        if ($name_length < $blank_length + 1 || $name_length > 256) {
            $alert = 'name-length';
        } elseif ($this->section_id === null) {
            $alert = 'section-null';
        } elseif ($this->weight < self::WEIGHT_MIN || $this->weight > self::WEIGHT_MAX) {
            $alert = 'weight-invalid';
        } else {
            return parent::save($sql, $alert);
        }

        return false;
    }

    public function set_primary($key)
    {
        $this->id = $key;
    }

    protected function export()
    {
        return array(
            'id' => $this->id,
            'is_closed' => $this->is_closed,
            'last_time' => $this->last_time,
            'name' => $this->name,
            'section' => $this->section_id,
            'weight' => $this->weight
        );
    }

    protected function on_touch($sql, $exists)
    {
        $this->hint = self::sanitize_name($this->name);

        if (!$exists) {
            Bookmark::delete_by_topic($sql, $this->id);
            Reference::delete_by_topic($sql, $this->id);
        }

        if ($this->section_id !== null) {
            Section::invalidate($sql, $this->section_id);
        }
    }
}

Topic::$schema_cache = new \RedMap\Schema(
    'board_topic_cache',
    array(
        'create_profile' => null,
        'create_time' => null,
        'hint' => null,
        'id' => null,
        'last_profile' => null,
        'posts' => null
    ),
    '__',
    array(
        'create_profile' => array(function () {
            return Profile::$schema;
        }, \RedMap\Schema::LINK_IMPLICIT | \RedMap\Schema::LINK_OPTIONAL, array('create_profile' => 'user')),
        'last_profile' => array(function () {
            return Profile::$schema;
        }, \RedMap\Schema::LINK_IMPLICIT | \RedMap\Schema::LINK_OPTIONAL, array('last_profile' => 'user')),
    )
);

Topic::$schema_load = new \RedMap\Schema(
    'board_topic',
    array(
        'create_profile' => '(SELECT p.create_profile FROM board_post p JOIN board_reference r ON r.post = p.id WHERE r.topic = @id AND r.position = 1 AND p.state = 0 LIMIT 1)',
        'create_time' => '(SELECT p.create_time FROM board_post p JOIN board_reference r ON r.post = p.id WHERE r.topic = @id ORDER BY r.position LIMIT 1)',
        'hint' => '(SELECT name FROM board_topic WHERE id = @id)',
        'id' => null,
        'last_profile' => '(SELECT p.create_profile FROM board_post p JOIN board_reference r ON r.post = p.id WHERE r.topic = @id AND p.state = 0 ORDER BY r.position DESC LIMIT 1)',
        'posts' => '(SELECT COALESCE(MAX(position), 0) FROM board_reference WHERE topic = @id)'
    ),
    '__',
    array(
        'create_profile' => array(function () {
            return Profile::$schema;
        }, \RedMap\Schema::LINK_IMPLICIT | \RedMap\Schema::LINK_OPTIONAL, array('create_profile' => 'user')),
        'last_profile' => array(function () {
            return Profile::$schema;
        }, \RedMap\Schema::LINK_IMPLICIT | \RedMap\Schema::LINK_OPTIONAL, array('last_profile' => 'user')),
    )
);

Topic::$schema = new \RedMap\Schema(
    'board_topic',
    array(
        'id' => null,
        'is_closed' => null,
        'last_time' => null,
        'name' => null,
        'section' => null,
        'weight' => null
    ),
    '__',
    array(
        'bookmark' => array(function () {
            return Bookmark::$schema;
        }, \RedMap\Schema::LINK_OPTIONAL, array('id' => 'topic', '!profile' => 'profile')),
        'cache' => array(Topic::$schema_cache, \RedMap\Schema::LINK_IMPLICIT | \RedMap\Schema::LINK_OPTIONAL, array('id' => 'id')),
        'permission' => array(function () {
            return Permission::$schema_topic;
        }, \RedMap\Schema::LINK_OPTIONAL, array('id' => 'topic', '!profile' => 'profile')),
        'permission_must' => array(function () {
            return Permission::$schema_topic;
        }, 0, array('id' => 'topic', '!profile' => 'profile')), // [hack-private-topics]
        'section' => array(function () {
            return Section::$schema;
        }, 0, array('section' => 'id'))
    )
);
