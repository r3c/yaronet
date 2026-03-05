<?php

namespace yN\Engine\Service;

defined('YARONET') or die;

class SoundCloudAPI
{
    public function __construct($logger)
    {
        $this->client_id = config('engine.service.soundcloud.client-id', null);
        $this->client_secret = config('engine.service.soundcloud.client-secret', null);
        $this->logger = $logger;
    }

    public function resolve($url)
    {
        if ($this->client_id === null || $this->client_secret === null) {
            return null;
        }

        $token = $this->call(
            'secure.soundcloud.com',
            'oauth/token',
            array(
                'Accept' => 'application/json; charset=utf-8',
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'grant_type=client_credentials'
        );

        if ($token === null || !isset($token['access_token'])) {
            $this->logger->log(\yN\Engine\Diagnostic\Logger::LEVEL_MEDIUM, 'system', 'SoundCloudAPI', 'Could not get OAuth access token:' . var_export($token, true));

            return null;
        }

        $resolved = $this->call(
            'api.soundcloud.com',
            'resolve?url=' . rawurlencode($url),
            array('Authorization' => 'OAuth ' . $token['access_token']),
            null
        );

        if ($resolved === null || !isset($resolved['location'])) {
            $this->logger->log(\yN\Engine\Diagnostic\Logger::LEVEL_NOTICE, 'system', 'SoundCloudAPI', 'Could not resolve URL "' . $url . '" into track');

            return null;
        }

        return $resolved['location'];
    }

    private function call($domain, $endpoint, $headers, $body)
    {
        $http = new \Glay\Network\HTTP();

        foreach ($headers as $key => $value) {
            $http->header($key, $value);
        }

        $response = $http->query('POST', 'https://' . $domain . '/' . $endpoint, $body);

        return json_decode($response->data, true);
    }
}
