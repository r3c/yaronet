<?php

namespace yN\Engine\Text;

defined('YARONET') or die;

class JSON
{
    public static function convert($data)
    {
        if (is_array($data)) {
            array_walk($data, function (&$value) {
                $value = JSON::convert($value);
            });

            return array_filter($data, function ($value) {
                return $value !== null;
            });
        }

        if (is_bool($data) || is_numeric($data) || is_null($data)) {
            return $data;
        }

        return mb_convert_encoding((string)$data, 'UTF-8');
    }

    public static function encode($data)
    {
        return json_encode(self::convert($data));
    }
}
