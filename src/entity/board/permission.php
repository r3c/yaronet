<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
\Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');
\Glay\using('yN\\Entity\\Board\\Section', './entity/board/section.php');
\Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');

class Permission
{
    public static $schema_forum;
    public static $schema_section;
    public static $schema_topic;

    public static function check_by_forum($sql, $user, $forum)
    {
        global $time;

        $access = new GlobalPermissionAccess($user);

        if ($user->id !== null) {
            $rows = $sql->select(self::$schema_forum, array('profile' => $user->id, 'forum' => (int)$forum->id, 'expire|gt' => $time));

            foreach ($rows as $row) {
                $access->update(new Permission($sql, $row));
            }
        }

        return $access;
    }

    public static function check_by_section($sql, $user, $section)
    {
        global $time;

        $access = new LocalPermissionAccess($user, $section);

        if ($user->id !== null) {
            $rows1 = $sql->select(self::$schema_section, array('profile' => $user->id, 'section' => (int)$section->id, 'expire|gt' => $time));
            $rows2 = $sql->select(self::$schema_forum, array('profile' => $user->id, 'forum' => (int)$section->forum_id, 'expire|gt' => $time));

            foreach (array_merge($rows1, $rows2) as $row) {
                $access->update(new Permission($sql, $row));
            }
        }

        return $access;
    }

    public static function check_by_topic($sql, $user, $section, $topic)
    {
        global $time;

        $access = new LocalPermissionAccess($user, $section);

        if ($user->id !== null) {
            $rows1 = $sql->select(self::$schema_topic, array('profile' => $user->id, 'topic' => (int)$topic->id, 'expire|gt' => $time));
            $rows2 = $sql->select(self::$schema_section, array('profile' => $user->id, 'section' => (int)$section->id, 'expire|gt' => $time));
            $rows3 = $sql->select(self::$schema_forum, array('profile' => $user->id, 'forum' => (int)$section->forum_id, 'expire|gt' => $time));

            foreach (array_merge($rows1, $rows2, $rows3) as $row) {
                $access->update(new Permission($sql, $row));
            }
        }

        return $access;
    }

    public static function clean($sql)
    {
        global $time;

        $schemas = array(self::$schema_forum, self::$schema_section, self::$schema_topic);
        $success = true;

        foreach ($schemas as $schema) {
            $success = $sql->delete($schema, array('expire|le' => $time)) !== null && $success;
            // FIXME: [sql-memory] reclaim memory
            //$success = $sql->wash ($schema) && $success;
            $success = $sql->client->execute('OPTIMIZE TABLE `' . $schema->table . '`') !== null && $success;
        }

        return $success;
    }

    public static function delete_by_profile($sql, $profile_id)
    {
        $success = $sql->delete(self::$schema_forum, array('profile' => $profile_id)) !== null;
        $success = $sql->delete(self::$schema_section, array('profile' => $profile_id)) !== null && $success;
        $success = $sql->delete(self::$schema_topic, array('profile' => $profile_id)) !== null && $success;

        return $success;
    }

    public static function fetch_by_profile_forum($sql, $profile_id, $forum_id)
    {
        global $time;

        $values = array('forum' => (int)$forum_id, 'profile' => (int)$profile_id);
        $rows = $sql->select(self::$schema_forum, $values + array('expire|gt' => $time));

        return new Permission($sql, count($rows) !== 0 ? $rows[0] : $values);
    }

    public static function fetch_by_profile_section($sql, $profile_id, $section_id)
    {
        global $time;

        $values = array('profile' => (int)$profile_id, 'section' => (int)$section_id);
        $rows = $sql->select(self::$schema_section, $values + array('expire|gt' => $time));

        return new Permission($sql, count($rows) !== 0 ? $rows[0] : $values);
    }

    public static function fetch_by_profile_topic($sql, $profile_id, $topic_id)
    {
        global $time;

        $values = array('profile' => (int)$profile_id, 'topic' => (int)$topic_id);
        $rows = $sql->select(self::$schema_topic, $values + array('expire|gt' => $time));

        return new Permission($sql, count($rows) !== 0 ? $rows[0] : $values);
    }

    public static function get_by_forum($sql, $forum_id)
    {
        global $time;

        $permissions = array();
        $rows = $sql->select(
            self::$schema_forum,
            array('forum' => (int)$forum_id, 'expire|gt' => $time, '+' => array('profile' => null)),
            array('+' => array('profile' => array('+' => array('user' => array('login' => true)))))
        );

        foreach ($rows as $row) {
            $permissions[] = new Permission($sql, $row);
        }

        return $permissions;
    }

    public static function get_by_section($sql, $section_id)
    {
        global $time;

        $permissions = array();
        $rows = $sql->select(
            self::$schema_section,
            array('section' => (int)$section_id, 'expire|gt' => $time, '+' => array('profile' => null)),
            array('+' => array('profile' => array('+' => array('user' => array('login' => true)))))
        );

        foreach ($rows as $row) {
            $permissions[] = new Permission($sql, $row);
        }

        return $permissions;
    }

    public static function get_by_topic($sql, $topic_id)
    {
        global $time;

        $permissions = array();
        $rows = $sql->select(
            self::$schema_topic,
            array('topic' => (int)$topic_id, 'expire|gt' => $time, '+' => array('profile' => null)),
            array('+' => array('profile' => array('+' => array('user' => array('login' => true)))))
        );

        foreach ($rows as $row) {
            $permissions[] = new Permission($sql, $row);
        }

        return $permissions;
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        if ($row !== null) {
            $this->can_change = isset($row[$ns . 'can_change']) ? (int)$row[$ns . 'can_change'] !== 0 : null;
            $this->can_read = isset($row[$ns . 'can_read']) ? (int)$row[$ns . 'can_read'] !== 0 : null;
            $this->can_write = isset($row[$ns . 'can_write']) ? (int)$row[$ns . 'can_write'] !== 0 : null;
            $this->forum = isset($row[$ns . 'forum__id']) ? new Forum($sql, $row, $ns . 'forum__') : null;
            $this->forum_id = isset($row[$ns . 'forum']) ? (int)$row[$ns . 'forum'] : null;
            $this->expire = isset($row[$ns . 'expire']) ? (int)$row[$ns . 'expire'] : 2147483647;
            $this->profile = isset($row[$ns . 'profile__user']) ? new Profile($sql, $row, $ns . 'profile__') : null;
            $this->profile_id = (int)$row[$ns . 'profile'];
            $this->section = isset($row[$ns . 'section__id']) ? new Section($sql, $row, $ns . 'section__') : null;
            $this->section_id = isset($row[$ns . 'section']) ? (int)$row[$ns . 'section'] : null;
            $this->topic = isset($row[$ns . 'topic__id']) ? new Topic($sql, $row, $ns . 'topic__') : null;
            $this->topic_id = isset($row[$ns . 'topic']) ? (int)$row[$ns . 'topic'] : null;
        } else {
            $this->can_change = null;
            $this->can_read = null;
            $this->can_write = null;
            $this->forum = null;
            $this->forum_id = null;
            $this->expire = 2147483647;
            $this->profile = null;
            $this->profile_id = null;
            $this->section = null;
            $this->section_id = null;
            $this->topic = null;
            $this->topic_id = null;
        }
    }

    public function save($sql, &$alert)
    {
        global $time;

        if ($this->profile_id === null) {
            $alert = 'profile-null';

            return false;
        }

        if ($this->topic_id !== null) {
            $schema = self::$schema_topic;
            $values = array('topic' => $this->topic_id);
        } elseif ($this->section_id !== null) {
            $schema = self::$schema_section;
            $values = array('section' => $this->section_id);
        } elseif ($this->forum_id !== null) {
            $schema = self::$schema_forum;
            $values = array('forum' => $this->forum_id);
        } else {
            $alert = 'target-null';

            return false;
        }

        $values['expire'] = $this->expire;
        $values['profile'] = $this->profile_id;

        if (($this->can_change !== null || $this->can_read !== null || $this->can_write !== null) && $this->expire > $time) {
            $values['can_change'] = $this->can_change;
            $values['can_read'] = $this->can_read;
            $values['can_write'] = $this->can_write;

            $success = $sql->insert($schema, $values, \RedMap\Engine::INSERT_REPLACE) !== null;
        } else {
            $success = $sql->delete($schema, $values) !== null;
        }

        if ($success) {
            return true;
        }

        $alert = 'sql';

        return false;
    }
}

class PermissionAccess
{
    public function __construct($default)
    {
        $this->can_change = $default;
        $this->can_change_last = null;
        $this->can_read = $default;
        $this->can_read_last = null;
        $this->can_write = $default;
        $this->can_write_last = null;
    }

    public function update($permission)
    {
        if ($permission === null) {
            return;
        }

        if ($permission->can_change !== null) {
            $this->can_change = $permission->can_change;
            $this->can_change_last = $permission->can_change;
        }

        if ($permission->can_read !== null) {
            $this->can_read = $permission->can_read;
            $this->can_read_last = $permission->can_read;
        }

        if ($permission->can_write !== null) {
            $this->can_write = $permission->can_write;
            $this->can_write_last = $permission->can_write;
        }
    }
}

class GlobalPermissionAccess extends PermissionAccess
{
    public function __construct($user)
    {
        parent::__construct($user->is_admin);

        $this->user = $user;
    }

    public function localize($section)
    {
        $access = new LocalPermissionAccess($this->user, $section);
        $access->can_change_last = $this->can_change_last;
        $access->can_read_last = $this->can_read_last;
        $access->can_write_last = $this->can_write_last;

        if ($access->can_change_last !== null) {
            $access->can_change = $access->can_change_last;
        }

        if ($access->can_read_last !== null) {
            $access->can_read = $access->can_read_last;
        }

        if ($access->can_write_last !== null) {
            $access->can_write = $access->can_write_last;
        }

        $access->update($section->permission);

        return $access;
    }
}

class LocalPermissionAccess extends PermissionAccess
{
    public function __construct($user, $section)
    {
        parent::__construct($user->is_admin);

        $this->can_read = $this->can_read || $section->access > Section::ACCESS_PRIVATE;
        $this->can_write = $this->can_write || $user->id !== null && $section->access > Section::ACCESS_PUBLIC;
    }
}

Permission::$schema_forum = new \RedMap\Schema(
    'board_permission_forum',
    array(
        'can_change'	=> null,
        'can_read'		=> null,
        'can_write'		=> null,
        'expire'		=> null,
        'forum'			=> null,
        'profile'		=> null,
        'section'		=> 'NULL',
        'topic'			=> 'NULL'
    ),
    '__',
    array(
        'forum'		=> array(function () {
            return Forum::$schema;
        }, 0, array('forum' => 'id')),
        'profile'	=> array(function () {
            return Profile::$schema;
        }, 0, array('profile' => 'user'))
    )
);

Permission::$schema_section = new \RedMap\Schema(
    'board_permission_section',
    array(
        'can_change'	=> null,
        'can_read'		=> null,
        'can_write'		=> null,
        'expire'		=> null,
        'forum'			=> 'NULL',
        'profile'		=> null,
        'section'		=> null,
        'topic'			=> 'NULL'
    ),
    '__',
    array(
        'profile'	=> array(function () {
            return Profile::$schema;
        }, 0, array('profile' => 'user')),
        'section'	=> array(function () {
            return Section::$schema;
        }, 0, array('section' => 'id'))
    )
);

Permission::$schema_topic = new \RedMap\Schema(
    'board_permission_topic',
    array(
        'can_change'	=> null,
        'can_read'		=> null,
        'can_write'		=> null,
        'expire'		=> null,
        'forum'			=> 'NULL',
        'profile'		=> null,
        'section'		=> 'NULL',
        'topic'			=> null
    ),
    '__',
    array(
        'profile'	=> array(function () {
            return Profile::$schema;
        }, 0, array('profile' => 'user')),
        'topic'		=> array(function () {
            return Topic::$schema;
        }, 0, array('topic' => 'id'))
    )
);
