<?php

namespace yN\Engine\Text;

defined('YARONET') or die;

\Glay\using('yN\\Engine\\Network\\URL', './engine/network/url.php');

class MarkupContext
{
    public $logger;
    public $nb_media;
    public $nb_newline;
    public $router;
    public $topic_id;
    public $user;

    public function __construct($router, $logger, $user, $topic_id)
    {
        $this->logger = $logger;
        $this->nb_media = 0;
        $this->nb_newline = 0;
        $this->router = $router;
        $this->topic_id = $topic_id;
        $this->user = $user;
    }
}

class Markup
{
    public static function blank()
    {
        return self::convert('bbcode-inline', '', self::context());
    }

    public static function context($router = null, $logger = null, $user = null, $topic_id = null)
    {
        return new MarkupContext($router, $logger, $user, $topic_id);
    }

    public static function convert($name, $plain, $context)
    {
        if ($plain === null) {
            return null;
        }

        return self::get_converter($name)->convert(trim(str_replace("\r", '', $plain)), $context);
    }

    public static function render($name, $token, $context)
    {
        if ($token === null) {
            return null;
        }

        return self::get_renderer($name)->render($token, $context);
    }

    public static function revert($name, $token, $context)
    {
        if ($token === null) {
            return null;
        }

        return self::get_converter($name)->revert($token, $context);
    }

    private static function get_converter($name)
    {
        static $converters;
        static $names;

        if (!isset($converters)) {
            $converters = array();
        }

        if (!isset($converters[$name])) {
            if (!isset($names)) {
                $names = array_flip(array('bbcode-block', 'bbcode-inline', 'fc', 'topic'));
            }

            if (!isset($names[$name])) {
                throw new \Exception('unknown converter "' . $name . '"');
            }

            $encoder = self::get_encoder();
            $scanner = new \Amato\PregScanner();

            require './engine/text/markup/syntax/' . $name . '.php';

            $converters[$name] = new \Amato\TagConverter($encoder, $scanner, $syntax);
        }

        return $converters[$name];
    }

    private static function get_encoder()
    {
        static $encoder;

        if (!isset($encoder)) {
            if (!function_exists('Amato\autoload')) {
                require './library/amato/amato.php';

                \Amato\autoload();
            }

            $encoder = new \Amato\CompactEncoder();
        }

        return $encoder;
    }

    private static function get_renderer($name)
    {
        static $renderers;
        static $names;

        if (!isset($renderers)) {
            $renderers = array();
        }

        if (!isset($renderers[$name])) {
            if (!isset($names)) {
                $charset = mb_internal_encoding();
                $names = array(
                    'html' => function ($s) use ($charset) {
                        return htmlspecialchars($s, ENT_COMPAT, $charset);
                    },
                    'text' => function ($s) {
                        return $s;
                    }
                );
            }

            if (!isset($names[$name])) {
                throw new \Exception('unknown renderer "' . $name . '"');
            }

            $encoder = self::get_encoder();

            require './engine/text/markup/format/' . $name . '.php';

            $renderers[$name] = new \Amato\FormatRenderer($encoder, $format, $names[$name]);
        }

        return $renderers[$name];
    }
}
