<?php

namespace yN\Entity\Security;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Cost extends \yN\Entity\Model
{
    const AMOUNT_LIMIT = 30;

    const MODEL_COST = 0;

    const TIME_WINDOW = 60;

    public static $schema;

    /*
    ** Check if given action consuming `amount` load units is acceptable for an
    ** IP address. Total load amount per address is stored in two values:
    ** - active amount is the sum of consumed units within current timeframe
    ** - decay amount is the same for previous timeframe with linear scaling
    ** When current timeframe is expired active amount is scaled according to
    ** elapsed time, copied to decay and reset to zero.
    **
    **  decay    active
    ** [--------|--------]
    **          ^- time  ^- active_expire
    **
    ** To get estimated consumed load units:
    **      |---x----| = decay * (active_expire - time) / window + active
    **               ^ time
    */
    public static function accept($sql, $amount)
    {
        global $address;
        global $time;

        if (!$address->is_public()) {
            return true;
        }

        $cost = self::entry_get_one($sql, array('address' => $address->string)) ?? new self();
        $cost->address = $address->string;

        $delta = $cost->active_expire - $time;

        // Current time window hasn't expired yet, increment active load amount
        if ($delta > 0) {
            $cost->active_amount += $amount;

            $sum = $cost->active_amount + $cost->decay_amount * $delta / self::TIME_WINDOW;
        }

        // Otherwise apply decay on active load amount and reset active window
        else {
            $decay = $cost->active_amount * max($delta + self::TIME_WINDOW, 0) / self::TIME_WINDOW;
            $sum = $amount + $decay;

            $cost->active_amount = $amount;
            $cost->active_expire = $time + self::TIME_WINDOW;
            $cost->decay_amount = $decay;
        }

        // Save updated entry and return acceptance result as a boolean value
        $sql->insert(self::$schema, $cost->export(), \RedMap\Engine::INSERT_REPLACE);

        return $sum <= self::AMOUNT_LIMIT;
    }

    public static function clean($sql)
    {
        global $time;

        return
            $sql->delete(self::$schema, array('active_expire|lt' => $time - self::TIME_WINDOW)) !== null &&
            // FIXME: [sql-memory] reclaim memory
            // $sql->wash (self::$schema) !== null
            $sql->client->execute('ALTER TABLE `' . self::$schema->table . '` ENGINE=MEMORY') !== null;
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->active_amount = (int)$row[$ns . 'active_amount'];
            $this->active_expire = (int)$row[$ns . 'active_expire'];
            $this->address = (string)$row[$ns . 'address'];
            $this->decay_amount = (int)$row[$ns . 'decay_amount'];
        } else {
            $this->active_amount = 0;
            $this->active_expire = $time + self::TIME_WINDOW;
            $this->address = '';
            $this->decay_amount = 0;
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
            'active_amount'	=> $this->active_amount,
            'active_expire'	=> $this->active_expire,
            'address'		=> $this->address,
            'decay_amount'	=> $this->decay_amount
        );
    }
}

Cost::$schema = new \RedMap\Schema('security_cost', array(
    'active_amount'	=> null,
    'active_expire'	=> null,
    'address'		=> null,
    'decay_amount'	=> null
));
