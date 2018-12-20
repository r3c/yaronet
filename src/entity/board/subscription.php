<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Board\\Bookmark', './entity/board/bookmark.php');
\Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');
\Glay\using('yN\\Entity\\Board\\Section', './entity/board/section.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');

class Subscription extends \yN\Entity\Model
{
    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public static function delete_by_profile($sql, $profile_id)
    {
        return $sql->delete(self::$schema, array('profile' => (int)$profile_id)) !== null;
    }

    public static function delete_by_section($sql, $section_id)
    {
        return $sql->delete(self::$schema, array('section' => (int)$section_id)) !== null;
    }

    public static function set_position($sql, $section_id, $topic_id, $position)
    {
        global $time;

        return $sql->source(
            Bookmark::$schema,
            array(
                'fresh' => array(\RedMap\Engine::SOURCE_VALUE, 1),
                'position' => array(\RedMap\Engine::SOURCE_VALUE, new \RedMap\Min($position)),
                'profile' => array(\RedMap\Engine::SOURCE_COLUMN, 'profile'),
                'time' => array(\RedMap\Engine::SOURCE_VALUE, $time),
                'topic' => array(\RedMap\Engine::SOURCE_VALUE, (int)$topic_id),
                'watch' => array(\RedMap\Engine::SOURCE_VALUE, 1)
            ),
            \RedMap\Engine::INSERT_UPSERT,
            self::$schema,
            array('section' => (int)$section_id)
        ) !== null;
    }

    public static function set_state($sql, $section_id, $profile_id, $flag)
    {
        // FIXME: cannot subscribe to private section even if it appears readable [hack-private-topics]
        if ((int)$section_id === 1000) {
            return true;
        }

        if ($flag) {
            return $sql->insert(self::$schema, array('section' => (int)$section_id, 'profile' => (int)$profile_id), \RedMap\Engine::INSERT_REPLACE) !== null;
        } else {
            return $sql->delete(self::$schema, array('section' => (int)$section_id, 'profile' => (int)$profile_id)) !== null;
        }
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
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
        if ($this->section_id === null || $this->profile_id === null) {
            return null;
        }

        return array('section' => $this->section_id, 'profile' => $this->profile_id);
    }

    public function save($sql, &$alert)
    {
        if ($this->profile_id === null) {
            $alert = 'profile-null';
        } elseif ($this->section_id === null) {
            $alert = 'section-null';
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
            'profile' => $this->profile_id,
            'section' => $this->section_id
        );
    }
}

Subscription::$schema = new \RedMap\Schema(
    'board_subscription',
    array(
        'profile' => null,
        'section' => null
    ),
    '__',
    array(
        'profile' => array(function () {
            return Profile::$schema;
        }, 0, array('profile' => 'user')),
        'section' => array(function () {
            return Section::$schema;
        }, 0, array('section' => 'id'))
    )
);
