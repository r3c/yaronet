<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Forum extends \yN\Entity\Model
{
    const MODEL_COST = 5;

    public static $schema;
    public static $schema_cache = null;

    public static function get_by_alias($sql, $alias)
    {
        return self::entry_get_one($sql, array('alias' => (string)$alias));
    }

    public static function get_by_identifier($sql, $forum_id)
    {
        return self::entry_get_one($sql, array('id' => (int)$forum_id));
    }

    public static function get_by_name($sql, $name)
    {
        return self::entry_get_one($sql, array('name' => trim($name)));
    }

    public static function get_by_name_match($sql, $name)
    {
        $name = str_replace(array('+', '-', '(', ')', '~', '*', '"'), '', (string)$name);

        if (mb_strlen($name) < 1) {
            return array();
        }

        return self::entry_get_all($sql, array('is_hidden' => 0, 'name|mb' => $name . '*'), array('name' => true), 10);
    }

    public static function get_random($sql, $count)
    {
        return self::entry_get_all($sql, array('is_hidden' => 0), array('$random' => true), (int)$count);
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        if ($row !== null) {
            $this->alias = $row[$ns . 'alias'];
            $this->description = $row[$ns . 'description'];
            $this->header = $row[$ns . 'header'];
            $this->icon_tag = (int)$row[$ns . 'icon_tag'];
            $this->id = (int)$row[$ns . 'id'];
            $this->is_hidden = (int)$row[$ns . 'is_hidden'] !== 0;
            $this->is_illustrated = (int)$row[$ns . 'is_illustrated'] !== 0;
            $this->name = $row[$ns . 'name'];
            $this->preface = $row[$ns . 'preface'];
            $this->template = $row[$ns . 'template'];
        } else {
            $this->alias = null;
            $this->description = '';
            $this->header = null;
            $this->icon_tag = 0;
            $this->id = null;
            $this->is_hidden = false;
            $this->is_illustrated = false;
            $this->name = '';
            $this->preface = null;
            $this->template = null;
        }
    }

    public function allow_edit($access)
    {
        return $access->can_change;
    }

    public function convert_header($plain, $router, $logger)
    {
        $this->header = \yN\Engine\Text\Markup::convert('bbcode-block', $plain, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function convert_preface($plain, $router, $logger)
    {
        $this->preface = \yN\Engine\Text\Markup::convert('bbcode-block', $plain, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function get_primary()
    {
        if ($this->id === null) {
            return null;
        }

        return array('id' => $this->id);
    }

    public function render_header($format, $router, $logger)
    {
        return \yN\Engine\Text\Markup::render($format, $this->header, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function render_preface($format, $router, $logger)
    {
        return \yN\Engine\Text\Markup::render($format, $this->preface, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function revert()
    {
        return array(
            'alias'				=> $this->alias,
            'description'		=> $this->description,
            'header'			=> $this->revert_header(),
            'id'				=> $this->id,
            'is_hidden'			=> $this->is_hidden,
            'is_illustrated'	=> $this->is_illustrated,
            'name'				=> $this->name,
            'preface'			=> $this->revert_preface(),
            'template'			=> $this->template
        );
    }

    public function revert_header()
    {
        return \yN\Engine\Text\Markup::revert('bbcode-block', $this->header, \yN\Engine\Text\Markup::context());
    }

    public function revert_preface()
    {
        return \yN\Engine\Text\Markup::revert('bbcode-block', $this->preface, \yN\Engine\Text\Markup::context());
    }

    public function save($sql, &$alert)
    {
        $alias_conflict = $this->alias !== null ? self::get_by_alias($sql, $this->alias) : null;
        $alias_length = strlen((string)$this->alias);
        $blank_length = strlen(\yN\Engine\Text\Markup::blank());
        $description_length = strlen($this->description);
        $header_length = strlen((string)$this->header);
        $name_confict = self::get_by_name($sql, $this->name);
        $name_length = strlen($this->name);
        $preface_length = strlen((string)$this->preface);

        if ($this->alias !== null && preg_match('/^[-[:alnum:]]*[[:alpha:]][-[:alnum:]]*$/', $this->alias) !== 1) {
            $alert = 'alias-format';
        } elseif ($alias_conflict !== null && $alias_conflict->id !== $this->id) {
            $alert = 'alias-conflict';
        } elseif ($this->alias !== null && ($alias_length < 2 || $alias_length > 20)) {
            $alert = 'alias-length';
        } elseif ($description_length < 2 || $description_length > 256) {
            $alert = 'description-length';
        } elseif ($this->header !== null && ($header_length < $blank_length + 1 || $header_length > 32767)) {
            $alert = 'header-length';
        } elseif ($name_confict !== null && $name_confict->id !== $this->id) {
            $alert = 'name-conflict';
        } elseif ($name_length < 2 || $name_length > 128) {
            $alert = 'name-length';
        } elseif ($this->preface !== null && ($preface_length < $blank_length + 1 || $preface_length > 32767)) {
            $alert = 'preface-length';
        } else {
            return parent::save($sql, $alert);
        }

        return false;
    }

    public function set_primary($key)
    {
        $this->id = $key;
    }

    protected function export()
    {
        return array(
            'alias'				=> $this->alias !== null ? trim($this->alias) : null,
            'description'		=> trim($this->description),
            'header'			=> $this->header,
            'icon_tag'			=> $this->icon_tag,
            'id'				=> $this->id,
            'is_hidden'			=> $this->is_hidden,
            'is_illustrated'	=> $this->is_illustrated,
            'name'				=> trim($this->name),
            'preface'			=> $this->preface,
            'template'			=> $this->template !== null ? trim($this->template) : null
        );
    }
}

Forum::$schema = new \RedMap\Schema(
    'board_forum',
    array(
        '$random'			=> array(\RedMap\Schema::FIELD_INTERNAL, 'rand()'),
        'alias'				=> null,
        'description'		=> null,
        'header'			=> null,
        'icon_tag'			=> null,
        'id'				=> null,
        'is_hidden'			=> null,
        'is_illustrated'	=> null,
        'name'				=> null,
        'preface'			=> null,
        'template'			=> null
    )
);
