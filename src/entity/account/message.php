<?php

namespace yN\Entity\Account;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Account\\User', './entity/account/user.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Message extends \yN\Entity\Model
{
    const EXPIRE_DURATION = 86400 * 1000; // 1000 days

    const MODEL_COST = 1;

    public static $schema;
    public static $schema_box;
    public static $schema_cache = null;

    public static function check($sql, $user_id)
    {
        return self::entry_get_one(
            $sql,
            array(
                '+'	=> array(
                    'box'		=> array(
                        '!recipient'	=> (int)$user_id,
                        'hidden'		=> false,
                        'read'			=> false
                    ),
                    'sender'	=> null
                )
            ),
            array('id' => true),
            1
        );
    }

    public static function clean($sql)
    {
        global $time;

        // FIXME: replace with RedMap equivalent [sql-hardcode]
        return $sql->client->execute('DELETE m, c FROM account_message m JOIN account_message_copy c ON c.message = m.id WHERE m.time < ?', array($time - self::EXPIRE_DURATION)) !== null;
    }

    public static function delete_by_identifier($sql, $message_id)
    {
        // Make sure no other message recipient other than sender already read message
        // FIXME: replace with RedMap equivalent [sql-hardcode]
        if (count($sql->client->select('SELECT 1 FROM account_message m JOIN account_message_copy c ON c.message = m.id AND c.recipient != m.sender AND c.read != 0 WHERE m.id = ?', array((int)$message_id))) !== 0) {
            return false;
        }

        return $sql->client->execute('DELETE m, c FROM account_message m JOIN account_message_copy c ON c.message = m.id WHERE m.id = ?', array((int)$message_id)) !== null;
    }

    public static function get_by_identifier__recipient($sql, $message_id, $user_id)
    {
        return self::entry_get_one($sql, array('id' => (int)$message_id, '+' => array('box' => array('!recipient' => (int)$user_id, 'hidden' => false), 'sender' => null)));
    }

    public static function get_by_identifier__sender($sql, $message_id, $user_id)
    {
        return self::entry_get_one($sql, array('id' => (int)$message_id, 'sender' => (int)$user_id, '+' => array('box' => array('!recipient' => (int)$user_id, 'hidden' => false))));
    }

    public static function get_by_recipient($sql, $user_id, $other_id, $from, $count)
    {
        // FIXME: replace with RedMap equivalent [sql-hardcode]
        if ($other_id !== null) {
            $filter = 'SELECT DISTINCT message FROM ' .
            '(' .
                'SELECT DISTINCT message FROM account_message_copy WHERE (? IS NULL OR message < ?) AND recipient = ? AND hidden = 0 ' .
                'UNION ALL ' .
                'SELECT DISTINCT message FROM account_message_copy WHERE (? IS NULL OR message < ?) AND recipient = ? AND hidden = 0 ' .
            ') AS i GROUP BY i.message HAVING COUNT(*) = 2 ORDER BY message DESC LIMIT ?';
            $params = array($from, $from, $user_id, $from, $from, $other_id, $count);
        } else {
            $filter = 'SELECT DISTINCT message FROM account_message_copy WHERE (? IS NULL OR message < ?) AND recipient = ? AND hidden = 0 ORDER BY message DESC LIMIT ?';
            $params = array($from, $from, $user_id, $count);
        }

        $rows = $sql->client->select(
            'SELECT ' .
                'c.message message, c.recipient recipient, c.hidden hidden, c.read `read`, ' .
                'm.id message__id, m.sender message__sender, m.time message__time, m.text message__text, ' .
                's.create_time message__sender__create_time, s.email message__sender__email, s.recover_time message__sender__recover_time, s.id message__sender__id, s.is_admin message__sender__is_admin, s.is_active message__sender__is_active, s.is_disabled message__sender__is_disabled, s.is_favorite message__sender__is_favorite, s.is_uniform message__sender__is_uniform, s.language message__sender__language, s.login message__sender__login, s.mechanism message__sender__mechanism, s.pulse_time message__sender__pulse_time, s.secret message__sender__secret, s.template message__sender__template, s.options message__sender__options, ' .
                'r.create_time recipient__create_time, r.email recipient__email, r.recover_time recipient__recover_time, r.id recipient__id, r.is_admin recipient__is_admin, r.is_active recipient__is_active, r.is_disabled recipient__is_disabled, r.is_favorite recipient__is_favorite, r.is_uniform recipient__is_uniform, r.language recipient__language, r.login recipient__login, r.mechanism recipient__mechanism, r.pulse_time recipient__pulse_time, r.secret recipient__secret, r.template recipient__template, r.options recipient__options ' .
            'FROM account_message_copy c ' .
            'JOIN (' . $filter . ') f ON f.message = c.message ' .
            'JOIN account_message m ON m.id = c.message ' .
            'JOIN account_user s ON s.id = m.sender ' .
            'JOIN account_user r ON r.id = c.recipient ' .
            'ORDER BY ' .
                'c.message DESC, r.login ASC',
            $params
        );

        $messages = array();

        foreach ($rows as $row) {
            $box = new MessageCopy($sql, $row, '');
            $id = $box->message_id;

            if (!isset($messages[$id])) {
                $messages[$id] = array($box->message, array());
            }

            $messages[$id][1][] = $box;
        }

        return array_values($messages);
    }

    public static function hide_copy($sql, $message_id, $user_id)
    {
        return MessageCopy::hide($sql, $message_id, $user_id);
    }

    public static function read_all($sql, $user_id)
    {
        return MessageCopy::read_all($sql, $user_id);
    }

    public static function send($sql, $message, $recipients, &$alert)
    {
        if ($message->id !== null) {
            if (MessageCopy::is_read($sql, $message->id, $message->sender_id)) {
                $alert = 'read';

                return false;
            }

            return $message->save($sql, $alert);
        }

        if (!$message->save($sql, $alert)) {
            return false;
        }

        $result = true;

        foreach ($recipients as $recipient) {
            $box = new MessageCopy();
            $box->message_id = $message->id;
            $box->read = $message->sender_id === (int)$recipient;
            $box->recipient_id = (int)$recipient;

            if (!$box->save($sql, $alert)) {
                $result = false;
            }
        }

        return $result;
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->box = isset($row[$ns . 'box__message']) ? new MessageCopy($sql, $row, $ns . 'box__') : null;
            $this->id = (int)$row[$ns . 'id'];
            $this->sender = isset($row[$ns . 'sender__id']) ? new User($sql, $row, $ns . 'sender__') : null;
            $this->sender_id = (int)$row[$ns . 'sender'];
            $this->text = $row[$ns . 'text'];
            $this->time = (int)$row[$ns . 'time'];
        } else {
            $this->box = null;
            $this->id = null;
            $this->sender = null;
            $this->sender_id = null;
            $this->text = \yN\Engine\Text\Markup::blank();
            $this->time = $time;
        }
    }

    public function convert_text($plain, $router, $logger)
    {
        $this->text = \yN\Engine\Text\Markup::convert('bbcode-block', $plain, \yN\Engine\Text\Markup::context($router, $logger, $this->sender));
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
        return \yN\Engine\Text\Markup::render($format, $this->text, \yN\Engine\Text\Markup::context($router, $logger, $this->sender));
    }

    public function revert_text()
    {
        return \yN\Engine\Text\Markup::revert('bbcode-block', $this->text, \yN\Engine\Text\Markup::context());
    }

    public function save($sql, &$alert)
    {
        $blank_length = strlen(\yN\Engine\Text\Markup::blank());
        $text_length = strlen($this->text);

        if ($this->sender_id === null) {
            $alert = 'sender-null';
        } elseif ($text_length < $blank_length + 1 || $text_length > 32767) {
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
            'id'		=> $this->id,
            'sender'	=> $this->sender_id,
            'text'		=> $this->text,
            'time'		=> $this->time
        );
    }
}

class MessageCopy extends \yN\Entity\Model
{
    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public static function is_read($sql, $message_id, $user_id)
    {
        return self::entry_get_one($sql, array('message' => (int)$message_id, 'read' => true, 'recipient|ne' => (int)$user_id)) !== null;
    }


    public static function hide($sql, $message_id, $user_id)
    {
        return $sql->update(self::$schema, array('hidden' => true, 'read' => true), array('message' => (int)$message_id, 'recipient' => (int)$user_id)) !== null;
    }

    public static function read_all($sql, $user_id)
    {
        return $sql->update(self::$schema, array('read' => true), array('recipient' => (int)$user_id)) !== null;
    }


    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->hidden = (int)$row[$ns . 'hidden'] !== 0;
            $this->message = isset($row[$ns . 'message__id']) ? new Message($sql, $row, $ns . 'message__') : null;
            $this->message_id = (int)$row[$ns . 'message'];
            $this->read = (int)$row[$ns . 'read'] !== 0;
            $this->recipient = isset($row[$ns . 'recipient__id']) ? new User($sql, $row, $ns . 'recipient__') : null;
            $this->recipient_id = (int)$row[$ns . 'recipient'];
        } else {
            $this->hidden = false;
            $this->message = null;
            $this->message_id = null;
            $this->read = false;
            $this->recipient = null;
            $this->recipient_id = null;
        }
    }

    public function get_primary()
    {
        if ($this->message_id === null || $this->recipient_id === null) {
            return null;
        }

        return array('message' => $this->message_id, 'recipient' => $this->recipient_id);
    }

    public function save($sql, &$alert)
    {
        if ($this->message_id === null) {
            $alert = 'message-null';
        } elseif ($this->recipient_id === null) {
            $alert = 'recipient-null';
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
            'hidden'	=> $this->hidden,
            'message'	=> $this->message_id,
            'read'		=> $this->read,
            'recipient'	=> $this->recipient_id
        );
    }
}

Message::$schema = new \RedMap\Schema(
    'account_message',
    array(
        'id'		=> null,
        'sender'	=> null,
        'text'		=> null,
        'time'		=> null
    ),
    '__',
    array(
        'box'		=> array(function () {
            return MessageCopy::$schema;
        }, \RedMap\Schema::LINK_OPTIONAL, array('id' => 'message', '!recipient' => 'recipient')),
        'sender'	=> array(function () {
            return User::$schema;
        }, 0, array('sender' => 'id'))
    )
);

MessageCopy::$schema = new \RedMap\Schema(
    'account_message_copy',
    array(
        'hidden'	=> null,
        'message'	=> null,
        'read'		=> null,
        'recipient'	=> null
    ),
    '__',
    array(
        'message'	=> array(Message::$schema, 0, array('message' => 'id')),
        'recipient'	=> array(function () {
            return User::$schema;
        }, 0, array('recipient' => 'id'))
    )
);
