<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
\Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');

class Favorite extends \yN\Entity\Model
{
    const COUNT_MAX = 50;

    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public static function delete_by_profile($sql, $profile_id)
    {
        return $sql->delete(self::$schema, array('profile' => (int)$profile_id)) !== null;
    }

    public static function get_by_forum($sql, $profile_id, $forum_id)
    {
        return self::entry_get_one($sql, array('profile' => (int)$profile_id, 'forum' => (int)$forum_id, '+' => array('forum' => null)));
    }

    public static function get_by_profile($sql, $profile_id)
    {
        return self::entry_get_all($sql, array('profile' => (int)$profile_id, '+' => array('forum' => null)), array('rank' => true));
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->forum = isset($row[$ns . 'forum__id']) ? new Forum($sql, $row, $ns . 'forum__') : null;
            $this->forum_id = (int)$row[$ns . 'forum'];
            $this->profile = isset($row[$ns . 'profile__user']) ? new Profile($sql, $row, $ns . 'profile__') : null;
            $this->profile_id = (int)$row[$ns . 'profile'];
            $this->rank = (int)$row[$ns . 'rank'];
        } else {
            $this->forum = null;
            $this->forum_id = null;
            $this->profile = null;
            $this->profile_id = null;
            $this->rank = 0;
        }
    }

    public function get_primary()
    {
        if ($this->profile_id === null) {
            return null;
        }

        return array('profile' => $this->profile_id, 'rank' => $this->rank);
    }

    public function save($sql, &$alert)
    {
        if ($this->profile_id === null) {
            $alert = 'profile-null';
        } elseif ($this->forum_id === null) {
            $alert = 'forum-null';
        } else {
            return parent::save($sql, $alert);
        }

        return false;
    }

    public function set_primary($key)
    {
        throw new \Exception();
    }

    protected function export()
    {
        return array(
            'forum' => $this->forum_id,
            'profile' => $this->profile_id,
            'rank' => $this->rank
        );
    }
}

Favorite::$schema = new \RedMap\Schema(
    'board_favorite',
    array(
        'forum' => null,
        'profile' => null,
        'rank' => null
    ),
    '__',
    array(
        'forum' => array(function () {
            return Forum::$schema;
        }, 0, array('forum' => 'id')),
        'profile' => array(function () {
            return Profile::$schema;
        }, 0, array('profile' => 'user'))
    )
);
