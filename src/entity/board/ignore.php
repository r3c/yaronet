<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');

class Ignore extends \yN\Entity\Model
{
    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public static function clear($sql, $profile_id, $target_id)
    {
        return $sql->delete(self::$schema, array('profile' => (int)$profile_id, 'target' => (int)$target_id)) !== null;
    }

    public static function set($sql, $profile_id, $target_id)
    {
        return $sql->insert(self::$schema, array('profile' => (int)$profile_id, 'target' => (int)$target_id), \RedMap\Engine::INSERT_REPLACE) !== null;
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->profile = isset($row[$ns . 'profile__user']) ? new Profile($sql, $row, $ns . 'profile__') : null;
            $this->profile_id = (int)$row[$ns . 'profile'];
            $this->target = isset($row[$ns . 'target__user']) ? new Profile($sql, $row, $ns . 'target__') : null;
            $this->target_id = (int)$row[$ns . 'target'];
        } else {
            $this->profile = null;
            $this->profile_id = null;
            $this->target = null;
            $this->target_id = null;
        }
    }

    public function get_primary()
    {
        if ($this->profile_id === null || $this->target_id === null) {
            return null;
        }

        return array('profile' => $this->profile_id, 'target' => $this->target_id);
    }

    public function set_primary($key)
    {
        throw new \Exception();
    }

    protected function export()
    {
        return array(
            'profile' => $this->profile_id,
            'target' => $this->target_id
        );
    }
}

Ignore::$schema = new \RedMap\Schema(
    'board_ignore',
    array(
        'profile' => null,
        'target' => null
    )
);
