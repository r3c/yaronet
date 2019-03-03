<?php

namespace yN\Engine\Text;

defined('YARONET') or die;

class Internationalization
{
    public static $languages = array('fr', 'en');

    private $language;
    private $locale;

    public static function cache_directory()
    {
        return config('engine.text.i18n.cache', './storage/cache/language');
    }

    public static function default_language()
    {
        return config('engine.text.i18n.language', 'en');
    }

    public static function is_valid($language)
    {
        static $languages;

        if (!isset($languages)) {
            $languages = array_flip(self::$languages);
        }

        return isset($languages[$language]);
    }

    public function __construct($language)
    {
        if (!class_exists('Losp\\Locale')) {
            require './library/losp/losp.php';
        }

        if (!self::is_valid($language)) {
            $language = self::default_language();
        }

        $locale_cache = self::cache_directory();
        $locale_source = config('engine.text.i18n.source', './resource/language');

        $locale = new \Losp\Locale(mb_internal_encoding(), $language, $locale_source, $locale_cache !== null ? $locale_cache . '/' . $language . '.php' : null);
        $locale->assign('lag', function ($window, $from, $to = null) {
            global $time;

            if ($to === null) {
                $to = $time;
            }

            return (int)(($to + date('Z', $to)) / $window) - (int)(($from + date('Z', $from)) / $window);
        });

        $this->language = $language;
        $this->losp = $locale;
    }

    public function format($key, $params = array())
    {
        return $this->losp->format($key, $params);
    }

    public function get_language()
    {
        return $this->language;
    }
}
