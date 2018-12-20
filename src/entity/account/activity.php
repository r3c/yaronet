<?php

namespace yN\Entity\Account;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Account\\User', './entity/account/user.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');

class Activity extends \yN\Entity\Model
{
    const EXPIRE_DURATION = 300;

    const HIT_GRACE = 5;
    const HIT_RATIO = 2;

    const MODEL_COST = 0;

    public static $schema;

    public static function browse($sql)
    {
        global $time;

        return self::entry_get_all(
            $sql,
            array('expire_time|gt' => $time, '+' => array('user' => null)),
            array('pulse_time' => true)
        );
    }

    public static function clean($sql)
    {
        global $time;

        return
            $sql->delete(self::$schema, array('expire_time|le' => $time)) !== null &&
            // FIXME: [sql-memory] reclaim memory
            // $sql->wash (self::$schema) !== null;
            $sql->client->execute('ALTER TABLE `' . self::$schema->table . '` ENGINE=MEMORY') !== null;
    }

    public static function leave($sql, $address)
    {
        return $sql->delete(self::$schema, array('address' => (string)$address)) !== null;
    }

    public static function pulse($sql, $address, $user, $location = null)
    {
        global $time;

        $activities = array();
        $prepend = false;
        $self = null;
        $uniques = array();

        // Get current non-expired activities
        $candidates = self::entry_get_all(
            $sql,
            array('expire_time|gt' => $time, '+' => array('user' => null)),
            array('pulse_time' => false)
        );

        foreach ($candidates as $candidate) {
            // Keep reference to current user if found in activities
            if ($candidate->address === $address) {
                $self = $candidate;

                // Update location if provided and reset pulse time
                if ($location !== null) {
                    $self->location = $location;
                    $self->pulse_time = $time;

                    $prepend = true;

                    continue;
                }
            }

            // Ensure we don't add same user id twice in activities
            if ($candidate->user !== null) {
                if (isset($uniques[$candidate->user->id])) {
                    continue;
                }

                $uniques[$candidate->user->id] = count($activities);
            }

            $activities[] = $candidate;
        }

        // Initialize current user if not found in activities
        if ($self === null) {
            $group = isset($_SERVER['HTTP_USER_AGENT']) ? self::get_group($_SERVER['HTTP_USER_AGENT']) : '';
            $prepend = true;

            $self = new Activity();
            $self->address = $address;
            $self->create_time = $time;
            $self->group = $group;
            $self->location = $location ?: '';
            $self->pulse_time = $time;
        }

        // Prepend current user to activities if just added or updated
        if ($prepend) {
            // Remove previous duplicate from activities if any
            if ($self->user !== null && isset($uniques[$self->user->id])) {
                array_splice($activities, $uniques[$self->user->id], 1);
            }

            array_unshift($activities, $self);
        }

        // Update current user and save to database
        $self->expire_time = $time + self::EXPIRE_DURATION;
        $self->user = $user->id !== null ? $user : null;
        $self->user_id = $user->id;

        $sql->insert(self::$schema, $self->export(), \RedMap\Engine::INSERT_REPLACE);

        $elapsed = $time - $self->create_time;

        return array($activities, $self->location);
    }

    private static function get_group($user_agent)
    {
        $groups = array(
            '#compatible; (bingbot|Googlebot|OrangeBot|Yandex)#' => 'bot'
        );

        foreach ($groups as $pattern => $group) {
            if (preg_match($pattern, $user_agent)) {
                return $group;
            }
        }

        return '';
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        if ($row !== null) {
            $this->address = $row[$ns . 'address'];
            $this->create_time = (int)$row[$ns . 'create_time'];
            $this->expire_time = (int)$row[$ns . 'expire_time'];
            $this->group = $row[$ns . 'group'];
            $this->location = $row[$ns . 'location'];
            $this->pulse_time = (int)$row[$ns . 'pulse_time'];
            $this->user = isset($row['user__id']) ? new User($sql, $row, 'user__') : null;
            $this->user_id = $row['user'] !== null ? (int)$row['user'] : null;
        } else {
            $this->address = '';
            $this->create_time = 0;
            $this->expire_time = 0;
            $this->group = '';
            $this->location = '';
            $this->pulse_time = 0;
            $this->user = null;
            $this->user_id = null;
        }
    }

    public function get_primary()
    {
        return array('address' => $this->address);
    }

    public function set_primary($key)
    {
        throw new \Exception();
    }

    protected function export()
    {
        return array(
            'address' => $this->address,
            'create_time' => $this->create_time,
            'expire_time' => $this->expire_time,
            'group' => $this->group,
            'location' => $this->location,
            'pulse_time' => $this->pulse_time,
            'user' => $this->user_id
        );
    }
}

Activity::$schema = new \RedMap\Schema(
    'account_activity',
    array(
        'address' => null,
        'create_time' => null,
        'expire_time' => null,
        'group' => null,
        'location' => null,
        'pulse_time' => null,
        'user' => null
    ),
    '__',
    array(
        'user' => array(function () {
            return User::$schema;
        }, \RedMap\Schema::LINK_OPTIONAL, array('user' => 'id'))
    )
);
