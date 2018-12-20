<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Model', './entity/model.php');

class Log extends \yN\Entity\Model
{
    const EXPIRE_DURATION = 86400 * 100; // 100 days

    const MODEL_COST = 0;

    public static $schema;
    public static $schema_cache = null;

    public static function clean($sql)
    {
        global $time;

        // FIXME: [sql-memory] reclaim memory
        return
            $sql->delete(self::$schema, array('time|le' => $time - self::EXPIRE_DURATION)) !== null &&
            $sql->client->execute('OPTIMIZE TABLE `' . self::$schema->table . '`') !== null;
    }

    public static function get_by_forum__time__profile__address($sql, $forum_id, $time_from, $time_to, $profile, $address)
    {
        $filters = array(
            'forum' => (int)$forum_id,
            'time|gt' => (int)$time_from,
            'time|le' => (int)$time_to,
            '+' => array('forum' => null, 'profile' => null)
        );

        if ($address !== null) {
            $filters['address'] = $address;
        }

        if ($profile !== null) {
            $filters['profile'] = (int)$profile;
        }

        return self::entry_get_all($sql, $filters);
    }

    public static function get_by_identifier($sql, $log_id)
    {
        return self::entry_get_one($sql, array('id' => (int)$log_id));
    }

    public static function push($sql, $forum_id, $profile_id, $type, $data)
    {
        $log = new Log();
        $log->data = $data;
        $log->forum_id = (int)$forum_id;
        $log->profile_id = (int)$profile_id;
        $log->type = $type;

        return $log->save($sql, $alert);
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $address;
        global $time;

        if ($row !== null) {
            $this->address = $row[$ns . 'address'];
            $this->data = $row[$ns . 'data'] !== '' ? json_decode($row[$ns . 'data'], true) : array();
            $this->forum = isset($row[$ns . 'forum__id']) ? new Forum($sql, $row, $ns . 'forum__') : null;
            $this->forum_id = (int)$row[$ns . 'forum'];
            $this->id = (int)$row[$ns . 'id'];
            $this->profile = isset($row[$ns . 'profile__user']) ? new Profile($sql, $row, $ns . 'profile__') : null;
            $this->profile_id = $row[$ns . 'profile'] !== null ? (int)$row[$ns . 'profile'] : null;
            $this->time = (int)$row[$ns . 'time'];
            $this->type = $row[$ns . 'type'];
        } else {
            $this->address = $address->string;
            $this->data = array();
            $this->forum = null;
            $this->forum_id = null;
            $this->id = null;
            $this->profile = null;
            $this->profile_id = null;
            $this->time = $time;
            $this->type = '';
        }
    }

    public function get_primary()
    {
        if ($this->id === null) {
            return null;
        }

        return array('id' => $this->id);
    }

    public function save($sql, &$alert)
    {
        if ($this->forum_id === null) {
            $alert = 'forum-null';
        } elseif ($this->profile_id === null) {
            $alert = 'profile-null';
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
            'address' => $this->address,
            'data' => json_encode($this->data),
            'forum' => $this->forum_id,
            'id' => $this->id,
            'profile' => $this->profile_id,
            'time' => $this->time,
            'type' => $this->type
        );
    }
}

Log::$schema = new \RedMap\Schema(
    'board_log',
    array(
        'address' => null,
        'data' => null,
        'forum' => null,
        'id' => null,
        'profile' => null,
        'time' => null,
        'type' => null
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
