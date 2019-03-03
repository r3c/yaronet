<?php

require '../src/library/redmap/redmap.php';

class SQL
{
    public static function query()
    {
        global $config;
        static $sql;

        if (!isset($sql)) {
            $sql = RedMap\open($config['engine.network.sql.connection']);

            assert($sql->connect(), 'sql connection must succeed');
        }

        return $sql;
    }

    public static function value($query, $params = array())
    {
        test_verbose("Query:\n" . $query . "\n\Parameters:\n" . var_export($params, true));

        $origin = microtime(true);
        $sql = self::query();
        $rows = $sql->client->select($query, $params);

        assert(count($rows) === 1, 'sql query must return exactly one row');

        $keys = array_keys($rows[0]);

        assert(count($keys) === 1, 'sql row must contain exactly one column');

        $value = $rows[0][$keys[0]];

        assert($value !== null, 'sql query must return a non-null scalar value');

        test_metric('sql.time', microtime(true) - $origin);

        return $value;
    }
}
