<?php

namespace yN\Engine\Service;

defined('YARONET') or die;

class ReCaptchaAPI
{
    public function __construct()
    {
        $this->site_key = config('engine.service.recaptcha.site-key', null);
        $this->site_secret = config('engine.service.recaptcha.site-secret', null);
    }

    public function check($code)
    {
        global $address;

        if (!$this->enable()) {
            return true;
        }

        $http = new \Glay\Network\HTTP();

        $response = $http->query('POST', 'https://www.google.com/recaptcha/api/siteverify', array(
            'remoteip' => $address->string,
            'response' => $code,
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
        if ($this->site_key === null || $this->site_secret === null) {
            return null;
        }

        return '<div class="g-recaptcha" data-sitekey="' . $this->site_key . '"></div>';
    }

    public function js()
    {
        if ($this->site_key === null || $this->site_secret === null) {
            return null;
        }

        return 'https://www.google.com/recaptcha/api.js';
    }
}
