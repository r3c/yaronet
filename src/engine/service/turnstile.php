<?php

namespace yN\Engine\Service;

defined('YARONET') or die;

class TurnstileAPI
{
    private $site_key;
    private $site_secret;

    public function __construct()
    {
        $this->site_key = config('engine.service.turnstile.site-key', null);
        $this->site_secret = config('engine.service.turnstile.site-secret', null);
    }

    public function check($input)
    {
        global $address;

        $http = new \Glay\Network\HTTP();
        $input->get_string('cf-turnstile-response', $token);

        $response = $http->query('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
            'remoteip' => $address->string,
            'response' => $token,
            'secret' => $this->site_secret
        ));

        $json = json_decode($response->data, true);

        return $json !== null && isset($json['success']) && $json['success'];
    }

    public function enable()
    {
        global $address;

        return $this->site_key !== null && $this->site_secret !== null && $address->is_public();
    }

    public function input()
    {
        return '<div class="cf-turnstile" data-sitekey="' . $this->site_key . '"></div>';
    }

    public function js()
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    }
}
