<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Board\\Block', './entity/board/block.php');
\Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
\Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
\Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');
\Glay\using('yN\\Entity\\Board\\Subscription', './entity/board/subscription.php');
\Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Section extends \yN\Entity\Model
{
    const ACCESS_OPEN = 2;
    const ACCESS_PRIVATE = 0;
    const ACCESS_PUBLIC = 1;

    const MODEL_COST = 5;

    const PAGE_SIZE = 25;

    const REACH_GLOBAL = 2;
    const REACH_LOCAL = 1;
    const REACH_NONE = 0;

    public static $schema;
    public static $schema_cache;
    public static $schema_load;

    public static function flush($sql)
    {
        return $sql->delete(self::$schema_cache) !== null;
    }

    public static function get_by_identifier($sql, $section_id, $profile_id = null)
    {
        $relations = array('forum' => null);

        if ($profile_id !== null) {
            $relations['subscription'] = array('!profile' => (int)$profile_id);
        }

        return self::entry_get_one($sql, array('id' => (int)$section_id, '+' => $relations));
    }

    public static function get_by_forum($sql, $forum_id)
    {
        return self::entry_get_all(
            $sql,
            array('forum' => (int)$forum_id),
            array('name' => true)
        );
    }

    public static function get_by_forum__missing($sql, $forum_id)
    {
        return self::entry_get_all(
            $sql,
            array('+' => array('block' => array('forum|is' => null)), 'forum' => (int)$forum_id),
            array('name' => true)
        );
    }

    public static function get_by_name_match($sql, $name)
    {
        $name = str_replace(array('+', '-', '(', ')', '~', '*', '"'), '', (string)$name);

        if (mb_strlen($name) < 2) {
            return array();
        }

        return self::entry_get_all($sql, array('name|mb' => $name . '*', '+' => array('forum' => null)), array('name' => true), 10);
    }

    public static function get_by_unique($sql, $unique)
    {
        if (is_numeric($unique)) {
            return self::entry_get_one($sql, array('id' => (int)$unique));
        }

        if (!preg_match('/^([^,]*),\\s*(.*)$/', $unique, $matches)) {
            return null;
        }

        if (!is_numeric($matches[1])) {
            $forum = Forum::get_by_name($sql, trim($matches[1]));

            if ($forum === null) {
                return null;
            }

            $forum_id = $forum->id;
        } else {
            $forum_id = (int)$matches[1];
        }

        return self::entry_get_one($sql, array('forum' => $forum_id, 'name' => trim($matches[2])));
    }

    public static function get_page($position)
    {
        return ceil($position / self::PAGE_SIZE);
    }

    public static function invalidate($sql, $section_id)
    {
        return self::cache_delete($sql, array('id' => (int)$section_id));
    }

    public static function set_fresh($sql, $section_id, $profile_id)
    {
        return $sql->delete(SectionRead::$schema, array('profile|ne' => (int)$profile_id, 'section' => (int)$section_id)) !== null;
    }

    public static function set_read($sql, $section_id, $profile_id)
    {
        return $sql->insert(SectionRead::$schema, array('profile' => (int)$profile_id, 'section' => (int)$section_id), \RedMap\Engine::INSERT_REPLACE) !== null;
    }

    protected static function on_cache(&$cache)
    {
        $cache['hint'] = self::sanitize_name($cache['hint']);
    }

    private static function sanitize_name($name)
    {
        \Glay\using('yN\\Engine\\Network\\URL', './engine/network/url.php');

        return \yN\Engine\Network\URL::sanitize_path($name) ?: null;
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        if ($row !== null) {
            if ($sql !== null) {
                self::cache_check($sql, $row, $ns, array('id'));
            }

            $this->access = (int)$row[$ns . 'access'];
            $this->description = $row[$ns . 'description'];
            $this->forum = isset($row[$ns . 'forum__id']) ? new Forum($sql, $row, $ns . 'forum__') : null;
            $this->forum_id = (int)$row[$ns . 'forum'];
            $this->header = $row[$ns . 'header'];
            $this->hint = $row[$ns . 'cache__hint'];
            $this->id = (int)$row[$ns . 'id'];
            $this->is_delegated = (int)$row[$ns . 'is_delegated'] !== 0;
            $this->last_topic = isset($row[$ns . 'cache__last_topic__id']) ? new Topic($sql, $row, $ns . 'cache__last_topic__') : null;
            $this->name = $row[$ns . 'name'];
            $this->permission = isset($row[$ns . 'permission__profile']) ? new Permission($sql, $row, $ns . 'permission__') : new Permission();
            $this->reach = (int)$row[$ns . 'reach'];
            $this->read = isset($row[$ns . 'read__profile']) ? new SectionRead($sql, $row, $ns . 'read__') : null;
            $this->subscription = isset($row[$ns . 'subscription__section']) ? new Subscription($sql, $row, $ns . 'subscription__') : null;
            $this->topics = isset($row[$ns . 'cache__topics']) ? (int)$row[$ns . 'cache__topics'] : 0;
        } else {
            $this->access = self::ACCESS_OPEN;
            $this->description = '';
            $this->forum = null;
            $this->forum_id = null;
            $this->header = null;
            $this->hint = '';
            $this->id = null;
            $this->is_delegated = false;
            $this->last_topic = null;
            $this->name = '';
            $this->permission = new Permission();
            $this->reach = self::REACH_GLOBAL;
            $this->read = null;
            $this->subscription = null;
            $this->topics = 0;
        }
    }

    public function allow_edit($access)
    {
        return $access->can_change;
    }

    public function allow_publish($access)
    {
        return $access->can_write || $this->id === 1000; // FIXME: second part of the condition shouldn't exist [hack-private-topics]
    }

    public function allow_view($access)
    {
        return $access->can_read || $this->id === 1000; // FIXME: second part of the condition shouldn't exist [hack-private-topics]
    }

    public function convert_header($plain, $router, $logger)
    {
        $this->header = \yN\Engine\Text\Markup::convert('bbcode-block', $plain, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function get_primary()
    {
        if ($this->id === null) {
            return null;
        }

        return array('id' => $this->id);
    }

    public function get_unique()
    {
        return ($this->forum !== null && mb_strpos($this->forum->name, ',') === false ? $this->forum->name : $this->forum_id) . ', ' . $this->name;
    }

    public function merge($sql, $into_id, &$alert)
    {
        if ($sql->update(Topic::$schema, array('section' => $into_id), array('section' => $this->id)) === null ||
            $sql->delete(Block::$schema, array('section' => $this->id)) === null ||
            $sql->delete(Permission::$schema_section, array('section' => $this->id)) === null) {
            $alert = 'sql';

            return false;
        }

        if (!$this->delete($sql, $alert)) {
            return false;
        }

        self::invalidate($sql, $into_id);

        return true;
    }

    public function render_header($format, $router, $logger)
    {
        return \yN\Engine\Text\Markup::render($format, $this->header, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function revert()
    {
        return array(
            'access' => $this->access,
            'description' => $this->description,
            'header' => $this->revert_header(),
            'id' => $this->id,
            'is_delegated' => $this->is_delegated,
            'name' => $this->name,
            'reach' => $this->reach
        );
    }

    public function revert_header()
    {
        return \yN\Engine\Text\Markup::revert('bbcode-block', $this->header, \yN\Engine\Text\Markup::context());
    }

    public function save($sql, &$alert)
    {
        $blank_length = strlen(\yN\Engine\Text\Markup::blank());
        $description_length = strlen($this->description);
        $header_length = strlen((string)$this->header);
        $name_length = strlen($this->name);

        if ($this->access !== self::ACCESS_PRIVATE && $this->access !== self::ACCESS_PUBLIC && $this->access !== self::ACCESS_OPEN) {
            $alert = 'access-invalid';
        } elseif ($description_length < 0 || $description_length > 256) {
            $alert = 'description-length';
        } elseif ($this->forum_id === null) {
            $alert = 'forum-null';
        } elseif ($this->header !== null && ($header_length < $blank_length + 1 || $header_length > 32767)) {
            $alert = 'header-length';
        } elseif ($name_length < 2 || $name_length > 128) {
            $alert = 'name-length';
        } elseif ($this->reach !== self::REACH_GLOBAL && $this->reach !== self::REACH_LOCAL && $this->reach !== self::REACH_NONE) {
            $alert = 'reach-invalid';
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
            'access' => $this->access,
            'description' => trim($this->description),
            'forum' => $this->forum_id,
            'header' => $this->header,
            'id' => $this->id,
            'is_delegated' => $this->is_delegated,
            'name' => trim($this->name),
            'reach' => $this->reach
        );
    }

    protected function on_touch($sql, $exists)
    {
        $this->hint = self::sanitize_name($this->name);
    }
}

class SectionRead extends \yN\Entity\Model
{
    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->profile = isset($row[$ns . 'profile__id']) ? new Profile($sql, $row, $ns . 'profile__') : null;
            $this->profile_id = (int)$row[$ns . 'profile'];
            $this->section = isset($row[$ns . 'section__id']) ? new Section($sql, $row, $ns . 'section__') : null;
            $this->section_id = (int)$row[$ns . 'section'];
        } else {
            $this->profile = null;
            $this->profile_id = null;
            $this->section = null;
            $this->section_id = null;
        }
    }

    public function get_primary()
    {
        if ($this->profile_id === null || $this->section_id === null) {
            return null;
        }

        return array('profile' => $this->profile_id, 'section' => $this->section_id);
    }

    public function set_primary($key)
    {
        throw new \Exception();
    }

    protected function export()
    {
        return array(
            'profile' => $this->profile_id,
            'section' => $this->section_id
        );
    }
}

Section::$schema_cache = new \RedMap\Schema(
    'board_section_cache',
    array(
        'hint' => null,
        'id' => null,
        'last_topic' => null,
        'topics' => null
    ),
    '__',
    array(
        'last_topic' => array(function () {
            return Topic::$schema;
        }, \RedMap\Schema::LINK_IMPLICIT | \RedMap\Schema::LINK_OPTIONAL, array('last_topic' => 'id'))
    )
);

Section::$schema_load = new \RedMap\Schema(
    'board_section',
    array(
        'hint' => '(SELECT name FROM board_section WHERE id = @id)',
        'id' => null,
        'last_topic' => '(SELECT id FROM board_topic WHERE section = @id ORDER BY last_time DESC LIMIT 1)',
        'topics' => '(SELECT COUNT(*) FROM board_topic WHERE section = @id)'
    ),
    '__',
    array(
        'last_topic' => array(function () {
            return Topic::$schema;
        }, \RedMap\Schema::LINK_IMPLICIT | \RedMap\Schema::LINK_OPTIONAL, array('last_topic' => 'id'))
    )
);

Section::$schema = new \RedMap\Schema(
    'board_section',
    array(
        'access' => null,
        'description' => null,
        'forum' => null,
        'header' => null,
        'id' => null,
        'is_delegated' => null,
        'name' => null,
        'reach' => null
    ),
    '__',
    array(
        'block' => array(function () {
            return Block::$schema;
        }, \RedMap\Schema::LINK_OPTIONAL, array('id' => 'section')),
        'cache' => array(Section::$schema_cache, \RedMap\Schema::LINK_IMPLICIT | \RedMap\Schema::LINK_OPTIONAL, array('id' => 'id')),
        'forum' => array(function () {
            return Forum::$schema;
        }, 0, array('forum' => 'id')),
        'permission' => array(function () {
            return Permission::$schema_section;
        }, \RedMap\Schema::LINK_OPTIONAL, array('id' => 'section', '!profile' => 'profile')),
        'read' => array(function () {
            return SectionRead::$schema;
        }, \RedMap\Schema::LINK_OPTIONAL, array('id' => 'section', '!profile' => 'profile')),
        'subscription' => array(function () {
            return Subscription::$schema;
        }, \RedMap\Schema::LINK_OPTIONAL, array('id' => 'section', '!profile' => 'profile'))
    )
);

SectionRead::$schema = new \RedMap\Schema(
    'board_section_read',
    array(
        'profile' => null,
        'section' => null
    ),
    '__',
    array(
        'profile' => array(function () {
            return Profile::$schema;
        }, 0, array('profile' => 'user')),
        'section' => array(Section::$schema, 0, array('section' => 'id'))
    )
);
