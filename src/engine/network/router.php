<?php

namespace yN\Engine\Network;

defined('YARONET') or die;

class Router
{
    public static function cache_directory()
    {
        return config('engine.network.route.cache', './storage/cache/route');
    }

    public static function create()
    {
        require './library/queros/queros.php';

        $cache = self::cache_directory();

        return new \Queros\Router('route.php', $cache !== null ? $cache . '/queros.php' : null);
    }
}
