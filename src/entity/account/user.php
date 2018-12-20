<?php

namespace yN\Entity\Account;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Model', './entity/model.php');

class User extends \yN\Entity\Model
{
    const COOKIE_DOMAIN = null;
    const COOKIE_NAME = 'token';

    const MODEL_COST = 1;

    const TIME_EXPIRE = 86400;
    const TIME_PULSE = 300;
    const TIME_RECOVER = 86400;

    public static $schema;
    public static $schema_cache = null;

    public static function authenticate_login($sql, $login, $input)
    {
        $user = self::get_by_login($sql, $login);

        if ($user === null) {
            return null;
        }

        $fields = explode(':', $user->secret);

        switch ($fields[0]) {
            case 'sha256':
            case 'sha512':
                if (count($fields) !== 3 || $fields[2] !== hash_hmac($fields[0], $input, $fields[1])) {
                    return null;
                }

                break;

            default:
                return null;
        }

        return $user;
    }

    public static function authenticate_token($sql, $token)
    {
        global $time;

        // Retrieve user information from token
        $buffer = @base64_decode($token);
        $clear = $buffer !== false ? @gzinflate($buffer) : false;
        $fields = $clear !== false ? explode(':', $clear) : array();

        if (count($fields) < 2 || $fields[1] < $time) {
            return null;
        }

        $user = self::get_by_identifier($sql, $fields[0]);

        if ($user === null) {
            return null;
        }

        // Validate token against known data
        $message = $user->get_message($fields[1]);

        switch ($user->mechanism) {
            case 'sha256':
            case 'sha512':
                if (count($fields) !== 4 || hash_hmac($user->mechanism, $message, $fields[2]) !== $fields[3]) {
                    return null;
                }

                break;

            default:
                return null;
        }

        // Refresh pulse if expired
        if ($user->pulse_time + self::TIME_PULSE < $time) {
            $user->pulse_time = $time;
            $user->save($sql, $alert);
        }

        return $user;
    }

    public static function get_by_email($sql, $email)
    {
        return self::entry_get_one($sql, array('email' => trim($email)));
    }

    public static function get_by_is_admin($sql)
    {
        return self::entry_get_all($sql, array('is_admin' => 1));
    }

    public static function get_by_identifier($sql, $user_id)
    {
        return self::entry_get_one($sql, array('id' => (int)$user_id));
    }

    public static function get_by_login($sql, $login)
    {
        return self::entry_get_one($sql, array('login' => trim($login)));
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->create_time = (int)$row[$ns . 'create_time'];
            $this->email = $row[$ns . 'email'];
            $this->id = (int)$row[$ns . 'id'];
            $this->is_active = (int)$row[$ns . 'is_active'] !== 0;
            $this->is_admin = (int)$row[$ns . 'is_admin'] !== 0;
            $this->is_disabled = (int)$row[$ns . 'is_disabled'] !== 0;
            $this->is_favorite = (int)$row[$ns . 'is_favorite'] !== 0;
            $this->is_uniform = (int)$row[$ns . 'is_uniform'] !== 0;
            $this->language = $row[$ns . 'language'];
            $this->login = $row[$ns . 'login'];
            $this->mechanism = $row[$ns . 'mechanism'];
            $this->options = array();
            $this->pulse_time = (int)$row[$ns . 'pulse_time'];
            $this->recover_time = (int)$row[$ns . 'recover_time'];
            $this->secret = $row[$ns . 'secret'];
            $this->template = $row[$ns . 'template'];

            $options = $row[$ns . 'options'];
            $length = strlen($options);

            for ($i = 0; $i < $length; $i = $j + 1) {
                $key = '';

                for ($j = $i; $j < $length && $options[$j] !== '='; ++$j) {
                    if ($options[$j] === '\\' && $j + 1 < $length) {
                        ++$j;
                    }

                    $key .= $options[$j];
                }

                $i = $j + 1;

                if ($i >= $length) {
                    break;
                }

                $value = '';

                for ($j = $i; $j < $length && $options[$j] !== ';'; ++$j) {
                    if ($options[$j] === '\\' && $j + 1 < $length) {
                        ++$j;
                    }

                    $value .= $options[$j];
                }

                $this->options[$key] = $value;
            }
        } else {
            $language = \yN\Engine\Text\Internationalization::default_language();

            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && preg_match_all('/([A-Za-z]+)(?:-[A-Za-z]+)?\\s*(?:;\\s*q=([.0-9]+))?/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches) !== false) {
                $accepts = count($matches[0]);
                $highest = 0;

                for ($i = 0; $i < $accepts; ++$i) {
                    $score = $matches[2][$i] !== '' ? (float)$matches[2][$i] : 1;

                    if ($score <= $highest) {
                        continue;
                    }

                    $candidate = strtolower($matches[1][$i]);

                    if (\yN\Engine\Text\Internationalization::is_valid($candidate)) {
                        $language = $candidate;
                        $highest = $score;
                    }
                }
            }

            $this->create_time = $time;
            $this->email = '';
            $this->id = null;
            $this->is_active = false;
            $this->is_admin = false;
            $this->is_disabled = false;
            $this->is_favorite = false;
            $this->is_uniform = false;
            $this->language = $language;
            $this->login = '';
            $this->mechanism = 'sha256';
            $this->options = array();
            $this->pulse_time = $time;
            $this->recover_time = $time + self::TIME_RECOVER;
            $this->secret = '';
            $this->template = \yN\Engine\Text\Display::default_template();
        }
    }

    public function get_option($key, $value = null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $value;
    }

    public function get_primary()
    {
        if ($this->id === null) {
            return null;
        }

        return array('id' => $this->id);
    }

    public function get_recover()
    {
        global $time;

        if ($this->id === null || $this->recover_time <= $time) {
            return null;
        }

        return substr(hash_hmac('crc32b', $this->recover_time, $this->id), 0, 8);
    }

    public function get_template($external)
    {
        return $this->is_uniform || $external === null ? $this->template : $external;
    }

    public function get_token($expire)
    {
        $fields = array($this->id, $expire);
        $message = $this->get_message($expire);

        switch ($this->mechanism) {
            case 'sha256':
            case 'sha512':
                $unique = uniqid();

                $fields[] = $unique;
                $fields[] = hash_hmac($this->mechanism, $message, $unique);

                break;

            default:
                return null;
        }

        return base64_encode(gzdeflate(implode(':', $fields)));
    }

    public function render()
    {
        return array(
            'create_time' => $this->create_time,
            'id' => $this->id,
            'language' => $this->language,
            'login' => $this->login,
            'pulse_time' => $this->pulse_time,
            'template' => $this->template
        );
    }

    public function save($sql, &$alert)
    {
        $email_conflict = self::get_by_email($sql, $this->email);
        $email_length = strlen($this->email);

        $login_conflict = self::get_by_login($sql, $this->login);
        $login_length = strlen($this->login);

        if (!preg_match('/^[-._%+0-9A-Za-z]+@[-.0-9A-Za-z]+$/', $this->email)) {
            $alert = 'email-format';
        } elseif ($email_conflict !== null && $email_conflict->id !== $this->id) {
            $alert = 'email-conflict';
        } elseif ($email_length < 1 || $email_length > 128) {
            $alert = 'email-length';
        } elseif (!preg_match('/^[0-9A-Za-z]*[-|^0-9A-Za-z_ ][0-9A-Za-z]*$/', $this->login)) {
            $alert = 'login-format';
        } elseif ($login_conflict !== null && $login_conflict->id !== $this->id) {
            $alert = 'login-conflict';
        } elseif ($login_length < 2 || $login_length > 64) {
            $alert = 'login-length';
        } else {
            return parent::save($sql, $alert);
        }

        return false;
    }

    public function set_primary($key)
    {
        $this->id = $key;
    }

    public function set_secret($algorithm, $input)
    {
        $fields = array($algorithm);

        switch ($algorithm) {
            case 'sha256':
            case 'sha512':
                $unique = uniqid();

                $fields[] = $unique;
                $fields[] = hash_hmac($algorithm, $input, $unique);

                break;

            default:
                return false;
        }

        $this->secret = implode(':', $fields);

        return true;
    }

    protected function export()
    {
        $options = '';

        foreach ($this->options as $key => $value) {
            if ($value === null) {
                continue;
            }

            $options .= ';' . str_replace(array('=', '\\'), array('\\=', '\\\\'), $key) . '=' . str_replace(array(';', '\\'), array('\\;', '\\\\'), $value);
        }

        return array(
            'create_time' => $this->create_time,
            'email' => trim($this->email),
            'id' => $this->id,
            'is_active' => $this->is_active,
            'is_admin' => $this->is_admin,
            'is_disabled' => $this->is_disabled,
            'is_favorite' => $this->is_favorite,
            'is_uniform' => $this->is_uniform,
            'language' => trim($this->language),
            'login' => trim($this->login),
            'mechanism' => $this->mechanism,
            'options' => (string)substr($options, 1),
            'pulse_time' => $this->pulse_time,
            'recover_time' => $this->recover_time,
            'secret' => $this->secret,
            'template' => trim($this->template)
        );
    }

    private function get_message($expire)
    {
        return $this->secret . ':' . $expire;
    }
}

User::$schema = new \RedMap\Schema('account_user', array(
    'create_time' => null,
    'email' => null,
    'id' => null,
    'is_active' => null,
    'is_admin' => null,
    'is_disabled' => null,
    'is_favorite' => null,
    'is_uniform' => null,
    'language' => null,
    'login' => null,
    'mechanism' => null,
    'options' => null,
    'pulse_time' => null,
    'recover_time' => null,
    'secret' => null,
    'template' => null
));
