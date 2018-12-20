<?php

namespace yN\Entity\Chat;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Shout extends \yN\Entity\Model
{
    const MODEL_COST = 2;

    public static $schema;
    public static $schema_cache = null;

    public static function clean($sql)
    {
        $last = self::entry_get_one($sql, array(), array('id' => false), 1);

        return $last !== null && $sql->delete(self::$schema, array('id|lt' => $last->id - 500)) !== null;
    }

    public static function get_by_id($sql, $shout_id)
    {
        return self::entry_get_one($sql, array('id' => (int)$shout_id));
    }

    public static function get_last($sql, $limit)
    {
        return self::entry_get_all($sql, array(), array('id' => false), (int)$limit);
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->id = (int)$row[$ns . 'id'];
            $this->is_guest = (int)$row[$ns . 'is_guest'] !== 0;
            $this->nick = $row[$ns . 'nick'];
            $this->text = $row[$ns . 'text'];
            $this->time = (int)$row[$ns . 'time'];
        } else {
            $this->id = null;
            $this->is_guest = false;
            $this->nick = '';
            $this->text = \yN\Engine\Text\Markup::blank();
            $this->time = $time;
        }
    }

    public function convert_text($plain, $router, $logger)
    {
        $this->text = \yN\Engine\Text\Markup::convert('fc', $plain, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function get_primary()
    {
        if ($this->id === null) {
            return null;
        }

        return array('id' => $this->id);
    }

    public function render_text($format, $router, $logger)
    {
        return \yN\Engine\Text\Markup::render($format, $this->text, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function revert_text()
    {
        return \yN\Engine\Text\Markup::revert('fc', $this->text, \yN\Engine\Text\Markup::context());
    }

    public function save($sql, &$alert)
    {
        $blank_length = strlen(\yN\Engine\Text\Markup::blank());
        $nick_length = strlen($this->nick);
        $text_length = strlen($this->text);

        if ($nick_length < 1 || $nick_length > 64) {
            $alert = 'nick-length';
        } elseif ($text_length < $blank_length + 1 || $text_length > 256) {
            $alert = 'text-length';
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
            'is_guest' => $this->is_guest,
            'nick' => $this->nick,
            'text' => $this->text,
            'time' => $this->time
        );
    }
}

Shout::$schema = new \RedMap\Schema('chat_shout', array(
    'id' => null,
    'is_guest' => null,
    'nick' => null,
    'text' => null,
    'time' => null
));
