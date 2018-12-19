<?php

namespace yN\Engine\Diagnostic;

defined('YARONET') or die;

class Debug
{
    public static function error($message, $verbose)
    {
        echo '
<div style="margin: 8px; background: #F0C0C0; border: 3px solid #F0A0A0; border-radius: 8px; font: normal normal normal 12px tahoma;">
	<div style="padding: 1px 4px; margin: 4px; font-weight: bold;">' . $message . '</div>
	<div style="padding: 1px 4px; margin: 4px; white-space: pre-wrap;">' . $verbose . '</div>
</div>';
    }

    public static function tick($label)
    {
        global $microtime;
        static $labels;
        static $last;

        $now = microtime(true);

        if (!isset($labels)) {
            register_shutdown_function(function () use (&$labels) {
                echo '
<ul style="padding: 4px 24px; margin: 8px; background: #C0C0F0; border: 3px solid #A0A0F0; border-radius: 8px; font: normal normal normal 12px tahoma;">';

                foreach ($labels as $label) {
                    echo '
	<li>' . $label[0] . ': ' . round($label[1] * 1000) . 'ms (+ ' . round($label[2] * 1000) . 'ms)</li>';
                }

                echo '
</ul>';
            });

            $labels = array();
            $last = $microtime;
        }

        $labels[] = array($label, $now - $microtime, $now - $last);
        $last = $now;
    }
}
