<?php

namespace yN\Entity\Account;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Account\\User', './entity/account/user.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Memo extends \yN\Entity\Model
{
    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public static function get_by_user($sql, $user_id)
    {
        return self::entry_get_one($sql, array('user' => (int)$user_id));
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        if ($row !== null) {
            $this->text = $row[$ns . 'text'];
            $this->user = isset($row[$ns . 'user__id']) ? new User($sql, $row, $ns . 'user__') : null;
            $this->user_id = (int)$row[$ns . 'user'];
        } else {
            $this->text = \yN\Engine\Text\Markup::blank();
            $this->user = null;
            $this->user_id = null;
        }
    }

    public function convert_text($plain, $router, $logger)
    {
        $this->text = \yN\Engine\Text\Markup::convert('bbcode-block', $plain, \yN\Engine\Text\Markup::context($router, $logger, $this->user));
    }

    public function get_primary()
    {
        if ($this->user_id === null) {
            return null;
        }

        return array('user' => $this->user_id);
    }

    public function render_text($format, $router, $logger)
    {
        return \yN\Engine\Text\Markup::render($format, $this->text, \yN\Engine\Text\Markup::context($router, $logger, $this->user));
    }

    public function revert_text()
    {
        return \yN\Engine\Text\Markup::revert('bbcode-block', $this->text, \yN\Engine\Text\Markup::context());
    }

    public function save($sql, &$alert)
    {
        $blank_length = strlen(\yN\Engine\Text\Markup::blank());
        $text_length = strlen($this->text);

        if ($this->revert_text() === '') {
            return $this->delete($sql, $alert);
        } elseif ($text_length < $blank_length + 1 || $text_length > 32767) {
            $alert = 'text-length';
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
            'text'	=> $this->text,
            'user'	=> $this->user_id
        );
    }
}

Memo::$schema = new \RedMap\Schema(
    'account_memo',
    array(
        'text'	=> null,
        'user'	=> null
    ),
    '__',
    array(
        'user'	=> array(function () {
            return User::$schema;
        }, 0, array('user' => 'id'))
    )
);
