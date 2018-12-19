<?php

namespace yN\Engine\Network;

defined('YARONET') or die;

class Email
{
    private $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function send($subject, $body, $recipients)
    {
        $from_default = isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : 'no-reply@' . $_SERVER['SERVER_NAME'];

        $smtp = new \Glay\Network\SMTP();
        $smtp->from(config('engine.network.smtp.from', $from_default), 'yAronet');

        foreach ($recipients as $address => $name) {
            $smtp->add_to($address, $name);
        }

        if ($smtp->send($subject, $body)) {
            return true;
        }

        $this->logger->log(\yN\Engine\Diagnostic\Logger::LEVEL_MEDIUM, 'system', 'Email', 'Can\'t send email to: ' . implode(', ', array_keys($recipients)));

        return false;
    }
}
