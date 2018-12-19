<?php

namespace yN\Engine\Network;

defined('YARONET') or die;

class URL
{
    public static function sanitize_path($path)
    {
        $path = mb_strtolower($path);
        $path = iconv(mb_internal_encoding(), 'ASCII//TRANSLIT', $path);
        $path = preg_replace('/[^-\s0-9A-Za-z]+/', '', $path);
        $path = preg_replace('/[-\s]+/', '-', $path);
        $path = preg_replace('/^[-0-9]+|-$/', '', $path);

        return $path;
    }

    public static function to_page()
    {
        static $url;

        if (!isset($url)) {
            $url = rtrim(config('engine.network.route.page', '/'), '/') . '/';
        }

        return $url;
    }

    public static function to_static()
    {
        static $url;

        if (!isset($url)) {
            $url = rtrim(config('engine.network.route.static', '/static'), '/') . '/';
        }

        return $url;
    }
}
