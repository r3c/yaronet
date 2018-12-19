<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');

class Ban extends \yN\Entity\Model
{
    const COUNT_MAX = 100;

    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public static function check($sql, $forum_id, $address)
    {
        return self::entry_get_one($sql, array('address' => (string)$address, 'forum' => (int)$forum_id)) !== null;
    }

    public static function delete_by_forum($sql, $forum_id)
    {
        return $sql->delete(self::$schema, array('forum' => (int)$forum_id)) !== null;
    }

    public static function get_by_forum($sql, $forum_id)
    {
        return self::entry_get_all($sql, array('forum' => (int)$forum_id));
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->address = (string)$row[$ns . 'address'];
            $this->forum = isset($row[$ns . 'forum__id']) ? new Forum($sql, $row, $ns . 'forum__') : null;
            $this->forum_id = (int)$row[$ns . 'forum'];
        } else {
            $this->address = null;
            $this->forum = null;
            $this->forum_id = null;
        }
    }

    public function get_primary()
    {
        if ($this->address === null || $this->forum_id === null) {
            return null;
        }

        return array('address' => $this->address, 'forum' => $this->forum_id);
    }

    public function save($sql, &$alert)
    {
        if ($this->address === null) {
            $alert = 'address-null';
        } elseif (!\Glay\Network\IPAddress::create($this->address)->is_valid()) {
            $alert = 'address-invalid';
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
            'address'	=> $this->address,
            'forum'		=> $this->forum_id
        );
    }
}

Ban::$schema = new \RedMap\Schema(
    'board_ban',
    array(
        'address'	=> null,
        'forum'		=> null
    ),
    '__',
    array(
        'forum'		=> array(function () {
            return Forum::$schema;
        }, 0, array('forum' => 'id'))
    )
);
