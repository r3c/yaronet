<?php

namespace yN\Engine\Service;

defined('YARONET') or die;

class TwitterAPI
{
    public function __construct($logger)
    {
        $this->consumer_key = config('engine.service.twitter.consumer-key', null);
        $this->consumer_secret = config('engine.service.twitter.consumer-secret', null);
        $this->logger = $logger;
        $this->token_key = config('engine.service.twitter.token-key', null);
        $this->token_secret = config('engine.service.twitter.token-secret', null);
    }

    public function get_tweet($id, $charset)
    {
        $json = $this->query('GET', 'https://api.twitter.com/1.1/statuses/oembed.json', array('id' => $id, 'lang' => 'en'));

        if ($json !== null && isset($json['html'])) {
            return mb_convert_encoding($json['html'], $charset, 'UTF-8');
        }

        $this->logger->log(\yN\Engine\Diagnostic\Logger::LEVEL_NOTICE, 'system', 'TwitterAPI', 'Received "' . (isset($json['errors'][0]['message']) ? $json['errors'][0]['message'] : 'Undefined error') . '" reply from Twitter for tweet ' . $id);

        return null;
    }

    private function query($method, $endpoint, $get = array(), $post = array())
    {
        global $time;

        if ($this->consumer_key === null || $this->consumer_secret === null || $this->token_key === null || $this->token_secret === null) {
            return null;
        }

        $oauth = array(
            'oauth_consumer_key'		=> $this->consumer_key,
            'oauth_nonce'				=> uniqid(),
            'oauth_signature_method'	=> 'HMAC-SHA1',
            'oauth_timestamp'			=> $time,
            'oauth_token'				=> $this->token_key,
            'oauth_version'				=> '1.0'
        );

        $oauth_signature_parameters = array_merge($oauth, $get);

        ksort($oauth_signature_parameters);

        $oauth_signature_data = strtoupper($method) . '&' . rawurlencode($endpoint) . '&' . rawurlencode(implode('&', array_map(function ($key, $value) {
            return rawurlencode($key) . '=' . rawurlencode($value) . '';
        }, array_keys($oauth_signature_parameters), $oauth_signature_parameters)));
        $oauth_signature_key = rawurlencode($this->consumer_secret) . '&' . rawurlencode($this->token_secret);

        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $oauth_signature_data, $oauth_signature_key, true));

        $http = new \Glay\Network\HTTP();
        $http->header('Authorization', 'OAuth ' . implode(', ', array_map(function ($key, $value) {
            return rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }, array_keys($oauth), $oauth)));

        $response = $http->query('GET', $endpoint . '?' . implode('&', array_map(function ($key, $value) {
            return rawurlencode($key) . '=' . rawurlencode($value) . '';
        }, array_keys($get), $get)));

        return json_decode($response->data, true);
    }
}
