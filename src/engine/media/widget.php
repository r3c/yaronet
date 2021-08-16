<?php

namespace yN\Engine\Media;

defined('YARONET') or die;

class Widget
{
    public static $mime_extract_null;
    public static $mime_matchers;
    public static $url_extract_first_match;
    public static $url_matchers;

    public static function detect($url, $logger)
    {
        // Parse URL and query string
        $components = parse_url($url);

        if (isset($components['query'])) {
            parse_str($components['query'], $query);
        } else {
            $query = array();
        }

        // Try detecting type of widget by URL
        foreach (self::$url_matchers as $type => $params) {
            list($pattern, $extractor) = $params;

            // Match URL using regular expression and store capture match
            if (!preg_match('@^https?://' . str_replace('@', '\\@', $pattern) . '@', $url, $match)) {
                continue;
            }

            // Invoke custom extractor to extract code from match and query string
            list($success, $code) = $extractor($logger, $match, $query);

            // Widget type if successfully detected
            if ($success) {
                return new Widget($url, $type, $code);
            }
        }

        // Try detecting type of widget by MIME type
        $http = new \Glay\Network\HTTP();
        $response = $http->query('GET', $url);
        $mime = strtolower($response->header('Content-Type', ''));

        foreach (self::$mime_matchers as $type => $params) {
            list($pattern, $extractor) = $params;

            if (!preg_match('@^' . str_replace('@', '\\@', $pattern) . '\\s*(?:;|$)@i', $mime)) {
                continue;
            }

            // Invoke custom extractor to extract code from response, if any
            list($success, $code) = $extractor($logger, $response);

            // Widget type if successfully detected
            if ($success) {
                return new Widget($url, $type, $code);
            }
        }

        // Could not detect widget type
        return null;
    }

    public static function html_escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, mb_internal_encoding());
    }

    public static function html_link($url)
    {
        return '<a href="' . self::html_escape($url) . '" rel="nofollow">' . self::html_escape($url) . '</a>';
    }

    public function __construct($url, $type, $code)
    {
        $this->code = $code;
        $this->type = $type;
        $this->url = $url;
    }

    public function html($preview = null)
    {
        if (isset(self::$mime_matchers[$this->type])) {
            return self::$mime_matchers[$this->type][2]($this->url, $this->code, $preview);
        } elseif (isset(self::$url_matchers[$this->type])) {
            return self::$url_matchers[$this->type][2]($this->url, $this->code, $preview);
        }

        return self::html_escape($this->url);
    }
}

Widget::$mime_extract_null = function () {
    return array(true, null);
};

Widget::$mime_matchers = array(
    'audio' => array(
        'audio/(?:aac|aacp|flac|ogg|vnd.wave|wav|wave|webm)',
        Widget::$mime_extract_null,
        function ($url) {
            $link = Widget::html_link($url);
            $src = Widget::html_escape($url);

            return
                '<audio onerror="$(this).replaceWith($(this).children());" src="' . $src . '" preload="none" controls>' .
                    $link .
                '</audio>';
        }
    ),
    'audio.mp3' => array(
        'audio/(?:mp3|mpeg)',
        Widget::$mime_extract_null,
        function ($url) {
            \Glay\using('yN\\Engine\\Network\\URL', './engine/network/url.php');

            $player = Widget::html_escape(\yN\Engine\Network\URL::to_static() . 'flash/dewplayer-rect.swf');
            $src = Widget::html_escape($url);

            return
                '<audio onerror="$(this).replaceWith($(this).children());" src="' . $src . '" preload="none" controls>' .
                    '<object type="application/x-shockwave-flash" data="' . $player . '" width="240" height="20">' .
                        '<param name="movie" value="' . $player . '" />' .
                        '<param name="flashvars" value="mp3=' . Widget::html_escape(rawurlencode($url)) . '" />' .
                        Widget::html_link($url) .
                    '</object>' .
                '</audio>';
        }
    ),
    'html.og' => array(
        'text/html',
        function ($logger, $response) {
            \Glay\using('yN\\Engine\\Media\\Image', './engine/media/image.php');

            // Fail when "head" HTML tag is either missing or too large
            if (!preg_match('@<\\s*head[^<>]*>(.*)<\\s*/\\s*head\\s*>@is', $response->data, $match) || strlen($match[1]) > 512 * 1024) {
                return array(false, null);
            }

            $headers = $match[1];

            // Match supported OpenGraph properties from HTML page headers
            $encoding = mb_internal_encoding();
            $properties = array();

            foreach (array('description', 'image', 'site_name', 'title') as $name) {
                if (preg_match('@<meta[^<>]+property\\s*=\\s*[\'"]og:' . preg_quote($name, '@') . '[\'"][^<>]+content\\s*=\\s*(?:"([^"]*)"|\'([^\']*)\')[^<>]*>@', $headers, $match)) {
                    $properties[$name] = html_entity_decode((isset($match[1]) ? $match[1] : '') ?: (isset($match[3]) ? $match[3] : ''), ENT_QUOTES, $encoding);
                }
            }

            // Ignore image if unspecified, invalid or too large for a thumbnail
            $image = isset($properties['image']) ? Image::create_from_url($properties['image']) : null;

            if ($image === null || $image->x > 1024 || $image->y > 1024) {
                unset($properties['image']);
            }

            if ($image !== null) {
                $image->free();
            }

            // Remove empty or too long properties
            $properties = array_filter($properties, function ($value) {
                return strlen($value) > 0 && strlen($value) < 1024;
            });

            return array(count($properties) > 0, json_encode($properties));
        },
        function ($url, $code) {
            $properties = json_decode($code, true);

            if (!is_array($properties)) {
                return '';
            }

            $base = \Glay\Network\URI::create($url);

            return
                '<div class="opengraph">' .
                    (isset($properties['image']) ? '<img src="' . Widget::html_escape($base->combine($properties['image'])) . '" />' : '') .
                    '<div>' .
                        '<a href="' . $url . '" rel="nofollow">' . Widget::html_escape(isset($properties['title']) ? $properties['title'] : $url) . '</a>' .
                        '<span class="source">' . Widget::html_escape(isset($properties['site_name']) ? $properties['site_name'] : $base->host) . '</span>' .
                        (isset($properties['description']) ? '<span class="description">' . Widget::html_escape($properties['description']) . '</span>' : '') .
                    '</div>' .
                '</div>';
        }
    ),
    'image' => array(
        'image/(?:jpeg|gif|png|svg+xml|webp)',
        function ($logger, $response) {
            \Glay\using('yN\\Engine\\Media\\Image', './engine/media/image.php');

            // Ignore image if invalid or too large for an inline media
            $image = Image::create_from_binary($response->data);

            if ($image === null || $image->x > 4096 || $image->y > 4096) {
                return array(false, null);
            }

            $image->free();

            return array(true, (int)$image->x . ':' . (int)$image->y);
        },
        function ($url, $code, $preview) {
            $image = Widget::html_escape($url);
            $params = explode(':', $code, 2);
            $title = Widget::html_escape(basename($url));

            if (is_numeric($preview)) {
                $thumb = $image;
                $zoom = round(max(min((int)$preview, 500), 5) * 0.01, 2);
            } elseif ($preview !== null && $preview !== false) {
                $thumb = Widget::html_escape($preview);
                $zoom = 1;
            } else {
                $thumb = $image;
                $zoom = 1;
            }

            if (count($params) > 1 && $params[0] !== '' && $params[1] !== '' && $preview !== false) {
                $x = max((int)$params[0] * $zoom, 8);
                $y = max((int)$params[1] * $zoom, 8);

                $clamp = min(800 / $x, 500 / $y, 1);
                $size = 'width="' . Widget::html_escape((int)($x * $clamp)) . '" height="' . Widget::html_escape((int)($y * $clamp)) . '" ';
            } else {
                $clamp = 1;
                $size = '';
            }

            if ($clamp < 1 || $zoom < 1 || $image !== $thumb) {
                return '<a class="zoom" href="' . $image . '" title="' . $title . '" target="_blank" rel="nofollow" data-fancybox-group="zoom" data-fancybox-type="image"><img class="custom" src="' . $thumb . '" alt="' . $title . '" ' . $size . '/></a>';
            }

            return '<img class="custom" src="' . $image . '" alt="' . $title . '" ' . $size . '/>';
        }
    ),
    'video' => array(
        'video/(?:mp4|mpeg|ogg|webm)',
        Widget::$mime_extract_null,
        function ($url) {
            return
                '<video onerror="$(this).replaceWith($(this).children());" src="' . Widget::html_escape($url) . '" controls>' .
                    Widget::html_link($url) .
                '</video>';
        }
    ),
    'video.flv' => array(
        'video/x-flv',
        Widget::$mime_extract_null,
        function ($url) {
            \Glay\using('yN\\Engine\\Network\\URL', './engine/network/url.php');

            $player = Widget::html_escape(\yN\Engine\Network\URL::to_static() . 'flash/player_flv_maxi.swf');

            return
                '<object type="application/x-shockwave-flash" data="' . $player . '" width="640" height="385">' .
                    '<param name="movie" value="' . $player . '" />' .
                    '<param name="flashvars" value="flv=' . Widget::html_escape(rawurlencode($url)) . '" />' .
                    Widget::html_link($url) .
                '</object>';
        }
    )
);

Widget::$url_extract_first_match = function ($logger, $match) {
    return array(true, $match[1]);
};

Widget::$url_matchers = array(
    'embed.dailymotion' => array(
        'www\\.dailymotion\\.com/video/([0-9A-Za-z]{1,16})',
        Widget::$url_extract_first_match,
        function ($url, $code) {
            return '<iframe frameborder="0" width="480" height="270" src="//www.dailymotion.com/embed/video/' . Widget::html_escape(rawurlencode($code)) . '" allowfullscreen></iframe>';
        }
    ),
    'embed.kickstarter' => array(
        'www\\.kickstarter\\.com/projects/([^/?#]+/[^/?#]+)(?:\\?|#|$)',
        Widget::$url_extract_first_match,
        function ($url, $code) {
            return '<iframe frameborder="0" height="380" scrolling="no" src="//www.kickstarter.com/projects/' . Widget::html_escape($code) . '/widget/card.html" width="220" allowfullscreen></iframe>';
        }
    ),
    'embed.jsfiddle' => array(
        'jsfiddle.net/([^/?#]+(?:/[^/?#]+)?)(?:/|\\?|#|$)',
        Widget::$url_extract_first_match,
        function ($url, $code) {
            return '<script async src="//jsfiddle.net/' . Widget::html_escape($code) . '/embed/"></script>';
        }
    ),
    'embed.soundcloud' => array(
        'soundcloud\\.com/([^?#]{1,128})',
        function ($logger, $match, $query) {
            if (preg_match('/^[0-9]{1,64}$/', $match[1])) {
                return array(true, $match[1]);
            }

            \Glay\using('yN\\Engine\\Service\\SoundCloudAPI', './engine/service/soundcloud.php');

            $soundcloud = new \yN\Engine\Service\SoundCloudAPI($logger, '90a48efd048eff2bc77d7fa9a958abf6', '6c45c7ece98bee6ea4aa366cb1f9bcfa');
            $url = $soundcloud->resolve('https://soundcloud.com/' . $match[1]);

            if ($url !== null && preg_match('@/tracks/([0-9]{1,64})@', $url, $track)) {
                return array(true, $track[1]);
            }

            return array(false, null);
        },
        function ($url, $code) {
            return '<iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F' . Widget::html_escape(rawurlencode($code)) . '" allowfullscreen></iframe>';
        }
    ),
    'embed.twitter' => array(
        '(?:mobile\\.)?twitter\\.com/[0-9A-Z_a-z]{1,64}/status/([0-9]{1,64})',
        function ($logger, $match, $query) {
            \Glay\using('yN\\Engine\\Service\\TwitterAPI', './engine/service/twitter.php');

            $twitter = new \yN\Engine\Service\TwitterAPI($logger);
            $code = $twitter->get_tweet($match[1], mb_internal_encoding());

            return array($code !== null, $code);
        },
        function ($url, $code) {
            return $code;
        }
    ),
    'embed.vimeo' => array(
        'vimeo\\.com/([0-9]{1,64})',
        Widget::$url_extract_first_match,
        function ($url, $code) {
            return '<iframe src="//player.vimeo.com/video/' . Widget::html_escape(rawurlencode($code)) . '" width="640" height="385" frameborder="0" allowfullscreen></iframe>';
        }
    ),
    'embed.youtube' => array(
        '(?:www\\.youtube\\.com/watch\\?|youtu\\.be/([-0-9A-Za-z_]{1,64}))',
        function ($logger, $match, $query) {
            $start = isset($query['t']) && is_numeric($query['t']) ? (int)$query['t'] : 0;
            $video = isset($query['v']) ? (string)substr($query['v'], 0, 64) : (isset($match[1]) ? $match[1] : null);

            return array($video !== null, $video . ($start > 0 ? ':' . $start : ''));
        },
        function ($url, $code) {
            if (!preg_match('/^([-0-9A-Za-z_]{1,64})(?::([0-9]{1,8}))?$/', $code, $match)) {
                return Widget::html_link($url);
            }

            return '<iframe class="youtube-player" type="text/html" width="640" height="390" src="//www.youtube.com/embed/' . Widget::html_escape(rawurlencode($match[1])) . '?fs=1&amp;rel=0' . (isset($match[2]) ? '&amp;start=' . Widget::html_escape(rawurlencode($match[2])) : '') . '&amp;theme=light" frameborder="0" allowfullscreen></iframe>';
        }
    )
);
