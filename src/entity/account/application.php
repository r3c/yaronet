<?php

namespace yN\Entity\Account;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Account\\User', './entity/account/user.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');

class Application extends \yN\Entity\Model
{
    const MODEL_COST = 0;

    public static $schema;
    public static $schema_cache = null;

    public static function get_by_identifier($sql, $application_id)
    {
        return self::entry_get_one($sql, array('id' => (int)$application_id));
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        if ($row !== null) {
            $this->key = $row[$ns . 'key'];
            $this->id = (int)$row[$ns . 'id'];
            $this->name = $row[$ns . 'name'];
            $this->url = $row[$ns . 'url'];
            $this->user = isset($row[$ns . 'user__id']) ? new User($sql, $row, $ns . 'user__') : null;
            $this->user_id = (int)$row[$ns . 'user'];
        } else {
            $this->key = '';
            $this->id = null;
            $this->name = '';
            $this->url = '';
            $this->user = null;
            $this->user_id = null;
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
        throw new \Exception();
    }

    public function set_primary($key)
    {
        throw new \Exception();
    }

    protected function export()
    {
        return array(
            'id'	=> $this->id,
            'key'	=> $this->key,
            'name'	=> $this->name,
            'url'	=> $this->url,
            'user'	=> $this->user_id
        );
    }
}

Application::$schema = new \RedMap\Schema(
    'account_application',
    array(
        'id'	=> null,
        'key'	=> null,
        'name'	=> null,
        'url'	=> null,
        'user'	=> null
    ),
    '__',
    array(
        'user'	=> array(function () {
            return User::$schema;
        }, 0, array('user' => 'id'))
    )
);
