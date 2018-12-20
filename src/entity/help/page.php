<?php

namespace yN\Entity\Help;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Page extends \yN\Entity\Model
{
    const MODEL_COST = 2;

    public static $schema;
    public static $schema_cache = null;

    public static function get_by_label($sql, $label, $language)
    {
        return self::entry_get_one($sql, array('label' => trim($label), 'language' => trim($language)));
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        if ($row !== null) {
            $this->label = $row[$ns . 'label'];
            $this->language = $row[$ns . 'language'];
            $this->name = $row[$ns . 'name'];
            $this->text = $row[$ns . 'text'];
        } else {
            $this->label = null;
            $this->language = null;
            $this->name = '';
            $this->text = \yN\Engine\Text\Markup::blank();
        }
    }

    public function convert_text($plain, $router, $logger)
    {
        $this->text = \yN\Engine\Text\Markup::convert('bbcode-block', $plain, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function get_primary()
    {
        if ($this->label === null || $this->language === null) {
            return null;
        }

        return array('label' => $this->label, 'language' => $this->language);
    }

    public function render_text($format, $router, $logger)
    {
        return \yN\Engine\Text\Markup::render($format, $this->text, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function revert_text()
    {
        return \yN\Engine\Text\Markup::revert('bbcode-block', $this->text, \yN\Engine\Text\Markup::context());
    }

    public function save($sql, &$alert)
    {
        $blank_length = strlen(\yN\Engine\Text\Markup::blank());
        $label_length = strlen((string)$this->label);
        $language_length = strlen((string)$this->language);
        $name_length = strlen($this->name);
        $text_length = strlen($this->text);

        if ($this->name === '' && $this->revert_text() === '') {
            return $this->delete($sql, $alert);
        } elseif ($this->label === null) {
            $alert = 'label-null';
        } elseif ($label_length < 1 || $label_length > 32) {
            $alert = 'label-length';
        } elseif ($this->language === null) {
            $alert = 'language-null';
        } elseif ($language_length < 1 || $language_length > 8) {
            $alert = 'language-length';
        } elseif ($name_length < 1 || $name_length > 256) {
            $alert = 'name-length';
        } elseif ($text_length < $blank_length + 1 || $text_length > 32767) {
            $alert = 'text-length';
        } else {
            return parent::save($sql, $alert);
        }

        return false;
    }

    public function set_primary($key)
    {
        throw new \Exception();
    }

    protected function export()
    {
        return array(
            'label' => trim($this->label),
            'language' => trim($this->language),
            'name' => $this->name,
            'text' => $this->text
        );
    }
}

Page::$schema = new \RedMap\Schema('help_page', array(
    'label' => null,
    'language' => null,
    'name' => null,
    'text' => null
));
