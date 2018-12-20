<?php

namespace yN\Engine\Text;

defined('YARONET') or die;

\Glay\using('yN\\Engine\\Media\\Image', './engine/media/image.php');

class Input
{
    private $fields;
    private $files;

    public function __construct(&$fields, &$files)
    {
        $this->fields =& $fields;
        $this->files =& $files;
    }

    public function ensure($name, $value)
    {
        if (!isset($this->fields[$name])) {
            $this->fields[$name] = $value;
        }
    }

    public function get_array($name, &$value)
    {
        $defined = isset($this->fields[$name]) && is_array($this->fields[$name]);
        $value = $defined ? (array)$this->fields[$name] : array();

        return $defined;
    }

    public function get_boolean($name, &$value)
    {
        $defined = isset($this->fields[$name]);
        $value = $defined && (int)$this->fields[$name] !== 0;

        return $defined;
    }

    /*
    ** Read image from known input fields, if provided.
    ** $prefix: input fields prefix (will read from $prefix-file and $prefix-url)
    ** &image: output image or null on error
    ** &name: output image name or null on error
    ** return: true if input fields were recognized or false otherwise
    */
    public function get_image($prefix, &$image, &$name)
    {
        // Receive from file upload
        if (isset($this->files[$prefix . '-file']) && $this->files[$prefix . '-file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file =& $this->files[$prefix . '-file'];
            $name = (string)pathinfo($file['name'], PATHINFO_FILENAME);

            if ($file['error'] === 0) {
                $image = \yN\Engine\Media\Image::create_from_binary(file_get_contents($file['tmp_name']));

                unlink($file['tmp_name']);
            } else {
                $image = null;
            }

            return true;
        }

        // Download from URL
        if (isset($this->fields[$prefix . '-url']) && $this->fields[$prefix . '-url'] !== '') {
            $url = (string)$this->fields[$prefix . '-url'];

            $image = \yN\Engine\Media\Image::create_from_url($url);
            $name = (string)pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_FILENAME);

            return true;
        }

        // Input was not recognized
        return false;
    }

    public function get_number($name, &$value)
    {
        $defined = isset($this->fields[$name]) && is_numeric($this->fields[$name]);
        $value = $defined ? (int)$this->fields[$name] : 0;

        return $defined;
    }

    public function get_string($name, &$value)
    {
        $defined = isset($this->fields[$name]);
        $value = $defined ? (string)$this->fields[$name] : '';

        return $defined;
    }
}
