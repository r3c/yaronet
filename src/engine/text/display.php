<?php

namespace yN\Engine\Text;

defined('YARONET') or die;

\Glay\using('yN\\Engine\\Network\\URL', './engine/network/url.php');

class Display
{
    public static $internals = array(
        'frame',
        'json',
        'rss'
    );

    public static $options = array(
        'html.kyanite',
        'html.amethyst',
        'html.azurite',
        'html.chlorite',
        'html.citrine',
        'html.magnetite',
        'html.obsidian',
        'html.pvg',
        'html.rgc',
        'html.tifr'
    );

    public static function _captcha_enable()
    {
        \Glay\using('yN\\Engine\\Service\\ReCaptchaAPI', './engine/service/recaptcha.php');

        $captcha = new \yN\Engine\Service\ReCaptchaAPI();

        return $captcha->enable();
    }

    public static function _captcha_input()
    {
        \Glay\using('yN\\Engine\\Service\\ReCaptchaAPI', './engine/service/recaptcha.php');

        $captcha = new \yN\Engine\Service\ReCaptchaAPI();

        return $captcha->input();
    }

    public static function _captcha_js()
    {
        \Glay\using('yN\\Engine\\Service\\ReCaptchaAPI', './engine/service/recaptcha.php');

        $captcha = new \yN\Engine\Service\ReCaptchaAPI();

        return $captcha->js();
    }

    public static function _html($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, mb_internal_encoding());
    }

    public static function _json($data)
    {
        \Glay\using('yN\\Engine\\Text\\JSON', './engine/text/json.php');

        return \yN\Engine\Text\JSON::encode($data);
    }

    public static function _jstr($string)
    {
        return str_replace(array('\\', '\'', '"'), array('\\\\', '\\\'', '\\"'), $string);
    }

    public static function _tra($key, $params = array())
    {
        return self::$static_i18n->format($key, $params);
    }

    public static function _url($name, $parameters = array(), $anchor = null)
    {
        return self::$static_router->url($name, $parameters, $anchor);
    }

    public static function _url_absolute($relative)
    {
        return (string)\Glay\Network\URI::here()->combine($relative);
    }

    public static function _xml($name, $data)
    {
        \Glay\using('yN\\Engine\\Text\\XML', './engine/text/xml.php');

        return \yN\Engine\Text\XML::encode($name, $data);
    }

    public static function cache_directory()
    {
        return config('engine.text.display.cache', './storage/cache/template');
    }

    public static function default_template()
    {
        return config('engine.text.display.template', 'html.kyanite');
    }

    public static function is_internal($template)
    {
        static $internals;

        if (!isset($internals)) {
            $internals = array_flip(self::$internals);
        }

        return isset($internals[$template]);
    }

    public static function is_option($template)
    {
        static $options;

        if (!isset($options)) {
            $options = array_flip(self::$options);
        }

        return isset($options[$template]);
    }

    // FIXME: state variables used by injected constants [deval-inject]
    private static $static_i18n;
    private static $static_router;

    private $language;
    private $logger;
    private $router;
    private $sql;
    private $suffix;
    private $template;
    private $user;

    public function __construct($sql, $logger, $router, $template, $language, $user)
    {
        require './library/deval/deval.php';

        $this->language = $language;
        $this->logger = $logger;
        $this->router = $router;
        $this->sql = $sql;
        $this->template = $template;
        $this->user = $user;
    }

    public function render($file, $location, $variables = array(), $external = null, $failover = false)
    {
        global $address;
        global $microtime;
        global $time;

        // Register activity and check hit quota limit
        list($activities, $last_location) = \yN\Entity\Account\Activity::pulse($this->sql, $address->string, $this->user, $location);

        // Use language and/or template override if any or fallback to standard
        $language = $this->language ?: $this->user->language;
        $template = $this->template ?: $this->user->get_template($external);

        // Fail on undefined template or switch to default if failover was enabled
        if (!self::is_internal($template) && !self::is_option($template)) {
            if (!$failover) {
                throw new \Queros\Failure(404, 'Requested template doesn\'t exist.');
            }

            $template = self::default_template();
        }

        // Extract layout and theme from template
        if (preg_match('/^([0-9A-Za-z]+)(?:\\.([0-9A-Za-z]+))?$/', $template, $match) !== 1) {
            throw new \Queros\Failure(404, 'Requested template is invalid.');
        }

        $layout = (string)$match[1];
        $theme = isset($match[2]) ? (string)$match[2] : '';

        // Check path to requested template file or throw exception
        $path = config('engine.text.display.source', './resource/template') . '/' . $layout . '/' . $file;

        if (!is_file($path)) {
            throw new \Queros\Failure(404, 'Requested file doesn\'t exist for given template.');
        }

        self::$static_i18n = new Internationalization($language);
        self::$static_router = $this->router;

        // Initialize renderer
        $setup = new \Deval\Setup();
        $setup->plain_text_processor = 'deindent,collapse';

        $renderer_cache = self::cache_directory();
        $renderer = $renderer_cache !== null ? new \Deval\CacheRenderer($path, $renderer_cache, $setup) : new \Deval\FileRenderer($path, $setup);

        $self = get_class();
        $static = \yN\Engine\Network\URL::to_static();

        $renderer->inject(\Deval\Builtin::deval());
        $renderer->inject(array(
            'captcha' => array(
                'input' => array($self, '_captcha_input'),
                'js' => array($self, '_captcha_js')
            ),
            'encoding' => mb_internal_encoding(),
            'html' => array($self, '_html'),
            'json' => array($self, '_json'),
            'jstr' => array($self, '_jstr'),
            'language' => $language,
            'logo'   => config('engine.text.display.logo', '<img class="default-mascot" src="' . $static . 'image/mascot.png" /> <a class="default-name" href="{home}"></a>'),
            'static' => array(
                'global' => $static,
                'layout' => $static . 'layout/' . $layout . '/',
                'theme' => $static . 'layout/' . $layout . '/theme/' . $theme . '/'
            ),
            'template' => $template,
            'tra' => array($self, '_tra'),
            'url' => array($self, '_url'),
            'url_absolute' => array($self, '_url_absolute'),
            'use_less' => config('engine.text.display.use-less', false),
            'xml' => array($self, '_xml')
        ));

        $defaults = array(
            'activities' => $activities,
            'feature' => array(
                'captcha' => array($self, '_captcha_enable')
            ),
            'get_message' => function () {
                return $this->user->id !== null ? \yN\Entity\Account\Message::check($this->sql, $this->user->id) : null;
            },
            'header' => function ($type, $value) {
                header($type . ': ' . $value);
            },
            'location' => $last_location,
            'logger' => $this->logger,
            'microtime' => $microtime,
            'request' => $_REQUEST,
            'router' => $this->router,
            'time' => $time,
            'user' => $this->user
        );

        return $renderer->render($defaults + $variables);
    }
}
