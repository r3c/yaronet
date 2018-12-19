<?php

namespace yN\Entity;

defined('YARONET') or die;

abstract class Model
{
    protected static function cache_check($sql, &$row, $ns, $keys)
    {
        foreach ($keys as $key) {
            if ($row[$ns . 'cache__' . $key] !== null) {
                return;
            }

            $primary[$key] = $row[$ns . $key];
        }

        $cache = self::cache_mount($sql, $primary);

        if ($cache === null) {
            return;
        }

        foreach ($cache as $name => $value) {
            $row[$ns . 'cache__' . $name] = $value;
        }
    }

    protected static function cache_delete($sql, $primary)
    {
        return $sql->delete(static::$schema_cache, $primary) !== null;
    }

    protected static function cache_mount($sql, $primary)
    {
        $rows = $sql->select(static::$schema_load, $primary);

        if (count($rows) < 1) {
            return null;
        }

        $cache = $rows[0];

        static::on_cache($cache);

        $sql->insert(
            static::$schema_cache,
            array_intersect_key($cache, static::$schema_cache->fields),
            \RedMap\Engine::INSERT_REPLACE
        );

        return $cache;
    }

    protected static function entry_get_all($sql, $filters = array(), $orders = array(), $count = null, $offset = null)
    {
        $entities = array();
        $rows = $sql->select(static::$schema, $filters, $orders, $count, $offset);

        foreach ($rows as $row) {
            $entities[] = new static ($sql, $row);
        }

        return $entities;
    }

    protected static function entry_get_one($sql, $filters = array(), $orders = array(), $count = null, $offset = null)
    {
        $rows = $sql->select(static::$schema, $filters, $orders, $count, $offset);

        if (count($rows) < 1) {
            return null;
        }

        return new static ($sql, $rows[0]);
    }

    protected static function on_cache(&$cache)
    {
    }

    public function delete($sql, &$alert)
    {
        $primary = $this->get_primary();

        if ($primary === null) {
            $alert = 'sql';

            return false;
        }

        if ($sql->delete(static::$schema, $primary) === null) {
            $alert = 'sql';

            return false;
        }

        if (static::$schema_cache !== null) {
            self::cache_delete($sql, $primary);
        }

        $this->on_touch($sql, false);

        return true;
    }

    abstract public function get_primary();

    public function save($sql, &$alert)
    {
        // Ensure current action is acceptable from remote IP address given its cost
        \Glay\using('yN\\Entity\\Security\\Cost', './entity/security/cost.php');

        if (static::MODEL_COST > 0 && !\yN\Entity\Security\Cost::accept($sql, static::MODEL_COST)) {
            $alert = 'cost';

            return false;
        }

        // Insert or update to database
        $key = $sql->insert(static::$schema, $this->export(), \RedMap\Engine::INSERT_UPSERT);

        if ($key === null) {
            $alert = 'sql';

            return false;
        }

        // Set primary key using returned auto-generated one if empty
        if ($this->get_primary() === null) {
            $this->set_primary($key);
        }

        // Prepare cache for current entity and raise invalidation event
        if (static::$schema_cache !== null) {
            self::cache_mount($sql, $this->get_primary());
        }

        $this->on_touch($sql, true);

        return true;
    }

    abstract public function set_primary($key);

    abstract protected function export();

    protected function on_touch($sql, $exists)
    {
    }
}
