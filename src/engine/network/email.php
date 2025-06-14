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
        $from = config('engine.network.smtp.from', $from_default);
        $reply_to = config('engine.network.smtp.reply-to', null);

        $smtp = new \Glay\Network\SMTP();
        $smtp->from($from, 'yAronet');

        if ($reply_to !== null) {
            $smtp->reply_to($reply_to, 'yAronet');
        }

        foreach ($recipients as $address => $name) {
            $smtp->add_to($address, $name);
        }

        if ($smtp->send($subject, $body)) {
            return true;
        }

        // Disabled as Infomaniak host isn't configured for sending e-mails
        //$this->logger->log(\yN\Engine\Diagnostic\Logger::LEVEL_MEDIUM, 'system', 'Email', 'Can\'t send email to: ' . implode(', ', array_keys($recipients)));

        return false;
    }
}
