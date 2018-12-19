<?php

namespace yN\Engine\Service;

defined('YARONET') or die;

class SoundCloudAPI
{
    public function __construct($logger, $client_id, $client_secret)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->logger = $logger;
    }

    public function resolve($url)
    {
        $json = $this->call('resolve', array('url' => $url));

        if ($json === null || !isset($json['location'])) {
            $this->logger->log(\yN\Engine\Diagnostic\Logger::LEVEL_NOTICE, 'system', 'SoundCloudAPI', 'Could not resolve URL "' . $url . '" into track');

            return null;
        }

        return $json['location'];
    }

    private function call($method, $params = array())
    {
        $params['client_id'] = $this->client_id;
        $query = '';

        foreach ($params as $key => $value) {
            if ($query !== '') {
                $query .= '&';
            } else {
                $query .= '?';
            }

            $query .= rawurlencode($key) . '=' . rawurlencode($value);
        }

        $http = new \Glay\Network\HTTP();
        $response = $http->query('GET', 'http://api.soundcloud.com/' . $method . '.json' . $query);

        return json_decode($response->data, true);
    }
}
