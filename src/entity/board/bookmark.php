<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');
\Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');

class Bookmark extends \yN\Entity\Model
{
    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public static function delete_by_profile($sql, $profile_id)
    {
        return $sql->delete(self::$schema, array('profile' => (int)$profile_id)) !== null;
    }

    public static function delete_by_profile__topic($sql, $profile_id, $topic_id)
    {
        return $sql->delete(self::$schema, array('profile' => (int)$profile_id, 'topic' => (int)$topic_id)) !== null;
    }

    public static function delete_by_topic($sql, $topic_id)
    {
        return $sql->delete(self::$schema, array('topic' => (int)$topic_id)) !== null;
    }

    public static function get_by_profile($sql, $profile_id, $fresh, $count)
    {
        return self::entry_get_all(
            $sql,
            array('fresh' => (bool)$fresh, 'profile' => (int)$profile_id, 'watch' => true, '+' => array(
                'profile'	=> null,
                'topic'		=> array('+' => array(
                    'section'	=> null
                ))
            )),
            array('time' => false),
            $count
        );
    }

    public static function set_fresh($sql, $topic_id, $position)
    {
        global $time;

        return $sql->update(
            self::$schema,
            array(
                'fresh'		=> true,
                'position'	=> new \RedMap\Min(max((int)$position, 0)),
                'time'		=> $time
            ),
            array(
                'topic'	=> (int)$topic_id
            )
        ) !== null;
    }

    public static function set_track($sql, $profile_id, $topic_id, $position)
    {
        global $time;

        return $sql->insert(self::$schema, array(
            'fresh'		=> false,
            'position'	=> new \RedMap\Max(max((int)$position, 0)),
            'profile'	=> (int)$profile_id,
            'time'		=> $time,
            'topic'		=> (int)$topic_id,
            'watch'		=> new \RedMap\Coalesce(0)
        ), \RedMap\Engine::INSERT_UPSERT) !== null;
    }

    public static function set_watch($sql, $profile_id, $topic_id, $position, $fresh, $watch)
    {
        global $time;

        return $sql->insert(self::$schema, array(
            'fresh'		=> (bool)$fresh,
            'position'	=> max((int)$position, 0),
            'profile'	=> (int)$profile_id,
            'time'		=> $time,
            'topic'		=> (int)$topic_id,
            'watch'		=> (bool)$watch
        ), \RedMap\Engine::INSERT_UPSERT) !== null;
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->fresh = (bool)$row[$ns . 'fresh'];
            $this->position = (int)$row[$ns . 'position'];
            $this->profile = isset($row[$ns . 'profile__user']) ? new Profile($sql, $row, $ns . 'profile__') : null;
            $this->profile_id = (int)$row[$ns . 'profile'];
            $this->time = (int)$row[$ns . 'time'];
            $this->topic = isset($row[$ns . 'topic__id']) ? new Topic($sql, $row, $ns . 'topic__') : null;
            $this->topic_id = (int)$row[$ns . 'topic'];
            $this->watch = (bool)$row[$ns . 'watch'];
        } else {
            $this->fresh = false;
            $this->position = 0;
            $this->profile = null;
            $this->profile_id = null;
            $this->time = $time;
            $this->topic = null;
            $this->topic_id = null;
            $this->watch = false;
        }
    }

    public function get_primary()
    {
        if ($this->topic_id === null || $this->position === null) {
            return null;
        }

        return array('topic' => $this->topic_id, 'position' => $this->position);
    }

    public function set_primary($key)
    {
        throw new \Exception();
    }

    protected function export()
    {
        return array(
            'fresh'		=> $this->fresh,
            'position'	=> $this->position,
            'profile'	=> $this->profile_id,
            'time'		=> $this->time,
            'topic'		=> $this->topic_id,
            'watch'		=> $this->watch
        );
    }
}

Bookmark::$schema = new \RedMap\Schema(
    'board_bookmark',
    array(
        'fresh'		=> null,
        'position'	=> null,
        'profile'	=> null,
        'time'		=> null,
        'topic'		=> null,
        'watch'		=> null
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
