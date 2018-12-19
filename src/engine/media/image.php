<?php

namespace yN\Engine\Media;

defined('YARONET') or die;

class Image
{
    /*
    ** Load image from binary data.
    */
    public static function create_from_binary($data)
    {
        // Try to read as an image
        $handle = @imagecreatefromstring($data);

        if ($handle !== false) {
            return new Image($data, $handle, imagesx($handle), imagesy($handle));
        }

        // Try to read as an SVG file
        $xml = @simplexml_load_string($data);

        if ($xml !== false && isset($xml['height']) && is_numeric($xml['height']) && isset($xml['width']) && is_numeric($xml['width'])) {
            return new Image($data, null, (int)$xml['width'], (int)$xml['height']);
        }

        // Image format not detected
        return null;
    }

    /*
    ** Load image from URL.
    */
    public static function create_from_url($url)
    {
        $http = new \Glay\Network\HTTP();
        $response = $http->query('GET', $url);

        if (!preg_match('@^image/@', $response->header('Content-Type', ''))) {
            return null;
        }

        return self::create_from_binary($response->data);
    }

    private function __construct($data, $handle, $x, $y)
    {
        $this->data = $data;
        $this->handle = $handle;
        $this->x = $x;
        $this->y = $y;
    }

    /*
    ** Resample image if size exceeds allowed maximum.
    */
    public function clamp($size)
    {
        if ($this->handle === null || ($this->x <= $size && $this->y <= $size)) {
            return;
        }

        $clamp = min($size / $this->x, $size / $this->y, 1);
        $x = (int)($this->x * $clamp);
        $y = (int)($this->y * $clamp);

        $handle = imagecreatetruecolor($x, $y);

        imagealphablending($handle, false);
        imagecopyresampled($handle, $this->handle, 0, 0, 0, 0, $x, $y, $this->x, $this->y);
        imagedestroy($this->handle);

        $this->handle = $handle;
        $this->x = $x;
        $this->y = $y;
    }

    /*
    ** Render image as PNG binary buffer.
    */
    public function create_png()
    {
        if ($this->handle === null) {
            return null;
        }

        ob_start();

        imagesavealpha($this->handle, true);
        imagepng($this->handle);

        return ob_get_clean();
    }

    /*
    ** Free allocated resources.
    */
    public function free()
    {
        if ($this->handle === null) {
            return;
        }

        imagedestroy($this->handle);

        $this->handle = null;
    }
}
