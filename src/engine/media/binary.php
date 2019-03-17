<?php

namespace yN\Engine\Media;

defined('YARONET') or die;

class Binary
{
    const BROWSE_LENGTH = 30;
    const ERROR_INVALID = 0;
    const ERROR_MISSING = 1;
    const MAGIC_LENGTH = 8;
    const NAME_CHARACTERS = '-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';
    const NAME_FALLBACK = '_';

    public static function escape($pattern)
    {
        return str_replace(array('*', '?', '[', ']'), array('\\*', '\\?', '\\[', '\\]'), $pattern);
    }

    public static function browse($pattern)
    {
        $store = self::get_store() . '/';
        $names = glob($store . basename($pattern));

        // Function `glob` can return null even if specification says otherwise, e.g. `glob("\x00")`
        if ($names === false || $names === null) {
            return array();
        }

        $length = strlen($store);

        return array_map(function ($name) use ($length) {
            return substr($name, $length);
        }, array_slice($names, 0, self::BROWSE_LENGTH));
    }

    public static function check($name)
    {
        return is_file(self::get_filepath($name));
    }

    public static function delete($name)
    {
        @unlink(self::get_filepath($name));
    }

    public static function put($name, $data)
    {
        return
            self::detect_mime_type(substr($data, 0, self::MAGIC_LENGTH)) !== null &&
            file_put_contents(self::get_filepath($name), $data, LOCK_EX) !== false;
    }

    public static function read($name, $expire)
    {
        global $time;

        $path = self::get_filepath($name);

        if (!is_file($path)) {
            return self::ERROR_MISSING;
        }

        $hash = sha1($name . '/' . filemtime($path));

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $hash) {
            header('HTTP/1.1 304 Not Modified');
            header('Cache-Control: max-age=' . $expire);
            header('Expires: ' . gmdate('D, d M Y H:i:s', $time + $expire) . ' GMT');

            exit;
        }

        $file = fopen($path, 'rb');
        $type = self::detect_mime_type(fread($file, self::MAGIC_LENGTH));

        if ($type === null) {
            return self::ERROR_INVALID;
        }

        header('Cache-Control: max-age=' . $expire);
        header('Content-Length: ' . filesize($path));
        header('Content-Type: ' . $type);
        header('Etag: ' . $hash);
        header('Expires: ' . gmdate('D, d M Y H:i:s', $time + $expire) . ' GMT');

        fseek($file, 0);
        fpassthru($file);

        exit;
    }

    private static function detect_mime_type($data)
    {
        static $types;

        if (!isset($types)) {
            $types = array(
                'GIF87a' => 'image/gif',
                'GIF89a' => 'image/gif',
                "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" => 'image/png',
                "\xFF\xD8" => 'image/jpeg'
            );
        }

        foreach ($types as $magic => $type) {
            if (substr($data, 0, strlen($magic)) === $magic) {
                return $type;
            }
        }

        return null;
    }

    private static function get_filepath($name)
    {
        return self::get_store() . '/' . preg_replace('/[^' . preg_quote(self::NAME_CHARACTERS, '/') . ']/', self::NAME_FALLBACK, $name);
    }

    private static function get_store()
    {
        return config('engine.media.binary.store', './storage/media/binary');
    }
}
