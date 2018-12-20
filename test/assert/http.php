<?php

require '../src/library/glay/network/uri.php';
require './library/simplehtmldom/simple_html_dom.php';

class HTTP
{
    public static function assert($path, $post = array(), $no_cookies = false, $follow_redirect = false)
    {
        return new self($path, $post, $no_cookies, $follow_redirect);
    }

    public function __construct($path, $post, $no_cookies, $follow_redirect)
    {
        global $config;
        global $config_test;

        test_case((count($post) > 0 ? 'POST' : 'GET') . ' /' . $path);

        $handle = curl_init();
        $url = new Glay\Network\URI(rtrim($config['engine.network.route.page'], '/') . '/' . $path);

        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, $follow_redirect);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_URL, Glay\Network\URI::create($config_test['url'])->combine($url));
        curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        $cookies = $no_cookies ? array() : self::cookie();

        if (count($cookies) > 0) {
            $curl_cookie = '';

            foreach ($cookies as $name => $value) {
                $curl_cookie .= ($curl_cookie ? ';' : '') . rawurlencode($name) . '=' . rawurlencode($value);
            }

            curl_setopt($handle, CURLOPT_COOKIE, $curl_cookie);
        }

        if (count($post) > 0) {
            $curl_post = '';

            foreach ($post as $name => $value) {
                $curl_post .= ($curl_post ? '&' : '') . rawurlencode($name) . '=' . rawurlencode($value);
            }

            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $curl_post);
        }

        $origin = microtime(true);
        $output = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        test_metric('http.time', microtime(true) - $origin);

        curl_close($handle);

        test_step('HTTP ' . $code);

        if ($output !== false) {
            test_dump($output);
            test_metric('http.bytes', strlen($output));

            $offset = strpos($output, "\r\n\r\n");

            if ($offset === false) {
                $offset = strlen($output);
            }

            $data = (string)substr($output, $offset + 4);
            $headers = array();

            if (preg_match_all('/^[[:blank:]]*([^[:blank:]:]+)[[:blank:]]*:[[:blank:]]*(.*)$/m', (string)substr($output, 0, $offset), $matches, PREG_SET_ORDER) !== false) {
                foreach ($matches as $match) {
                    $headers[strtolower($match[1])][] = trim($match[2]);
                }
            }

            if (isset($headers['set-cookie'])) {
                foreach ($headers['set-cookie'] as $cookie) {
                    if (preg_match('/([^=]+)=([^;]+)/', $cookie, $pair) !== 1) {
                        continue;
                    }

                    self::cookie(rawurldecode($pair[1]), rawurldecode($pair[2]));

                    test_step('Set cookie \'' . rawurldecode($pair[1]) . '\'');
                }
            }
        } else {
            $code = 0;
            $data = '';
            $headers = array();
        }

        $this->http = array(
            'data' => $data,
            'code' => $code,
            'headers' => $headers
        );
    }

    public function is_forbidden()
    {
        assert($this->http['code'] === 403, 'response code must be 403 (forbidden)');

        return $this;
    }

    public function is_not_found()
    {
        assert($this->http['code'] === 404, 'response code must be 404 (not found)');

        return $this;
    }

    public function is_success()
    {
        assert($this->http['code'] >= 200 && $this->http['code'] < 300, 'response code must be 2xx (success)');

        return $this;
    }

    public function matches_html($path, $pattern, &$capture = null, $last = false)
    {
        $html = new simple_html_dom();
        $html->load($this->http['data']);

        switch (substr($pattern, 0, 1)) {
            case '+':
                $expected = true;
                $pattern = substr($pattern, 1);

                break;

            case '-':
                $expected = false;
                $pattern = substr($pattern, 1);

                break;

            default:
                $expected = true;

                break;
        }

        $elements = $html->find($path);
        $found = false;

        foreach ($elements as $element) {
            if (preg_match($pattern, $element->outertext, $match) === 1) {
                $capture = $match;
                $found = true;

                if (!$last) {
                    break;
                }
            }
        }

        assert($found === $expected, ($expected ? 'one' : 'no') . ' element \'' . $path . '\' must match \'' . $pattern . '\'');

        test_step('HTML OK');

        return $this;
    }

    public function matches_json($path, $pattern)
    {
        $json = json_decode($this->http['data'], true);

        assert($json !== false, 'response body must be valid JSON');

        $node = $json;

        if ($path !== '') {
            $keys = explode('.', $path);

            foreach ($keys as $index => $key) {
                assert(isset($node[$key]), 'node \'' . implode('.', array_slice($keys, 0, $index + 1)) . '\' must exist');

                $node = $node[$key];
            }
        }

        assert(is_scalar($node), 'node \'' . $path . '\' must be a scalar value');
        assert(preg_match($pattern, $node) === 1, 'node \'' . $path . '\' must match \'' . $pattern . '\'');

        test_step('JSON OK');

        return $this;
    }

    public function matches_text($pattern)
    {
        assert(preg_match($pattern, $this->http['data']), 'text must match \'' . $pattern . '\'');

        test_step('Text OK');

        return $this;
    }

    public function redirects_to($pattern, &$match = null)
    {
        assert($this->http['code'] >= 300 && $this->http['code'] < 400, 'response code is \'' . $this->http['code'] . '\' but should be 3xx');
        assert(isset($this->http['headers']['location'][0]), 'location header is missing');

        $location = $this->http['headers']['location'][0];

        assert(preg_match($pattern, $location, $match) === 1, 'location header \'' . $location . '\' doesn\'t match \'' . $pattern . '\'');

        test_step('Redirects to \'' . $location . '\'');

        return $this;
    }

    private static function cookie($name = null, $value = null)
    {
        static $cookies;

        if (!isset($cookies)) {
            $cookies = array();
        }

        if ($name !== null) {
            if ($value !== null) {
                $cookies[$name] = $value;
            } else {
                unset($cookies[$name]);
            }
        }

        return $cookies;
    }
}
