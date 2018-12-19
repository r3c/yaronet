<?php

namespace yN\Engine\Diagnostic;

defined('YARONET') or die;

class Logger
{
    const FORMAT_TIME = 'Ymd';

    const LEVEL_SYSTEM = 0;
    const LEVEL_NOTICE = 1;
    const LEVEL_MEDIUM = 2;
    const LEVEL_SEVERE = 3;

    public function __construct($path, $format, $level)
    {
        $this->context = '';
        $this->format = $format;
        $this->level = $level;
        $this->path = $path;
    }

    public function clean($expire)
    {
        global $time;

        $directory = dirname($this->path);
        $pattern = '/^' . preg_replace('/\\\\{[a-z]+\\\\}/', '.*', preg_quote(basename($this->path), '/')) . '$/';

        foreach (scandir($directory) as $name) {
            $path = $directory . '/' . $name;

            if (is_file($path) && preg_match($pattern, $name) && $time - filemtime($path) > $expire) {
                unlink($path);
            }
        }
    }

    public function context($context)
    {
        $this->context = (string)$context;
    }

    public function log($level, $label, $title, $message)
    {
        global $address;
        global $time;

        if ($level >= $this->level) {
            \Glay\using('yN\\Engine\\Diagnostic\\Debug', './engine/diagnostic/debug.php');

            Debug::error($title, $message);
        }

        $params = array(
            'address'	=> $address->string,
            'context'	=> $this->context,
            'date'		=> date(self::FORMAT_TIME, $time),
            'label'		=> $label,
            'level'		=> $level,
            'message'	=> $message,
            'time'		=> $time,
            'title'		=> $title,
            'url'		=> $_SERVER['REQUEST_URI']
        );

        $macro = function ($matches) use ($params) {
            return isset($params[$matches[1]]) ? $params[$matches[1]] : $matches[0];
        };

        $path = preg_replace_callback('/\\{([a-z]+)\\}/', $macro, $this->path);
        $file = fopen($path, 'ab');

        if ($file !== false) {
            $line = preg_replace_callback('/\\{([a-z]+)\\}/', $macro, $this->format);

            fwrite($file, $line . "\n");
            fclose($file);
        }
    }
}
