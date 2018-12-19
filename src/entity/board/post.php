<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Account\\User', './entity/account/user.php');
\Glay\using('yN\\Entity\\Board\\Ignore', './entity/board/ignore.php');
\Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');
\Glay\using('yN\\Entity\\Board\\Reference', './entity/board/reference.php');
\Glay\using('yN\\Entity\\Board\\Search', './entity/board/search.php');
\Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Post extends \yN\Entity\Model
{
    const MODEL_COST = 2;

    const STATE_DEFAULT = 0;
    const STATE_EMPHASIZED = 1;
    const STATE_HIDDEN = 2;

    public static $schema;
    public static $schema_cache = null;
    public static $schema_index;

    public static function get_by_profile($sql, $profile_id, $limit)
    {
        return self::entry_get_all(
            $sql,
            array(
                'create_profile'	=> (int)$profile_id,
                '+'					=> array(
                    'reference'	=> array(
                        '+'	=> array('topic' => null)
                    )
                )
            ),
            array(),
            $limit
        );
    }

    public static function set_state_by_profile($sql, $profile_id, $state)
    {
        return $sql->update(self::$schema, array('state' => $state), array('create_profile' => (int)$profile_id)) !== null;
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->caution = $row[$ns . 'caution'] !== null ? $row[$ns . 'caution'] : null;
            $this->create_profile = isset($row[$ns . 'create_profile__user']) ? new Profile($sql, $row, $ns . 'create_profile__') : null;
            $this->create_profile_id = (int)$row[$ns . 'create_profile'];
            $this->create_time = (int)$row[$ns . 'create_time'];
            $this->edit_profile = isset($row[$ns . 'edit_profile__user']) ? new Profile($sql, $row, $ns . 'edit_profile__') : null;
            $this->edit_profile_id = $row[$ns . 'edit_profile'] !== null ? (int)$row[$ns . 'edit_profile'] : null;
            $this->edit_time = $row[$ns . 'edit_time'] !== null ? (int)$row[$ns . 'edit_time'] : null;
            $this->id = (int)$row[$ns . 'id'];
            $this->ignore = isset($row[$ns . 'ignore__profile']);
            $this->reference = isset($row[$ns . 'reference__topic']) ? new Reference($sql, $row, $ns . 'reference__') : null;
            $this->state = (int)$row[$ns . 'state'];
            $this->text = $row[$ns . 'text'];
        } else {
            $this->caution = null;
            $this->create_profile = null;
            $this->create_profile_id = null;
            $this->create_time = $time;
            $this->edit_profile = null;
            $this->edit_profile_id = null;
            $this->edit_time = null;
            $this->id = null;
            $this->ignore = false;
            $this->reference = null;
            $this->state = self::STATE_DEFAULT;
            $this->text = \yN\Engine\Text\Markup::blank();
        }
    }

    public function allow_edit($access, $profile_id)
    {
        return $access->can_change || $this->create_profile_id === $profile_id;
    }

    public function allow_view($access, $profile_id)
    {
        return $access->can_read || $this->create_profile_id === $profile_id;
    }

    public function convert_text($plain, $router, $logger, $topic_id)
    {
        $user = $this->create_profile !== null ? $this->create_profile->user : null;

        $this->text = \yN\Engine\Text\Markup::convert('bbcode-block', $plain, \yN\Engine\Text\Markup::context($router, $logger, $user, $topic_id));
    }

    public function get_primary()
    {
        if ($this->id === null) {
            return null;
        }

        return array('id' => $this->id);
    }

    public function render_text($format, $router, $logger, $topic_id)
    {
        $user = $this->create_profile !== null ? $this->create_profile->user : null;

        return \yN\Engine\Text\Markup::render($format, $this->text, \yN\Engine\Text\Markup::context($router, $logger, $user, $topic_id));
    }

    public function revert()
    {
        return array(
            'caution'		=> $this->caution,
            'create_time'	=> $this->create_time,
            'edit_time'		=> $this->edit_time,
            'id'			=> $this->id,
            'state'			=> $this->state,
            'text'			=> $this->revert_text()
        );
    }

    public function revert_text()
    {
        return \yN\Engine\Text\Markup::revert('bbcode-block', $this->text, \yN\Engine\Text\Markup::context());
    }

    public function save($sql, &$alert)
    {
        $blank_length = strlen(\yN\Engine\Text\Markup::blank());
        $caution_length = strlen((string)$this->caution);
        $text_length = strlen($this->text);

        if ($this->create_profile_id === null) {
            $alert = 'create-profile-null';
        } elseif ($this->caution !== null && ($caution_length < 1 || $caution_length > 256)) {
            $alert = 'caution-length';
        } elseif ($this->state !== self::STATE_DEFAULT && $this->state !== self::STATE_EMPHASIZED && $this->state !== self::STATE_HIDDEN) {
            $alert = 'state-invalid';
        } elseif ($text_length < $blank_length + 1 || $text_length > 32767) {
            $alert = 'text-length';
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
            'caution'			=> $this->caution,
            'create_profile'	=> $this->create_profile_id,
            'create_time'		=> $this->create_time,
            'edit_profile'		=> $this->edit_profile_id,
            'edit_time'			=> $this->edit_time,
            'id'				=> $this->id,
            'state'				=> $this->state,
            'text'				=> $this->text
        );
    }

    protected function on_touch($sql, $exists)
    {
        $text = Search::sanitize_query($this->render_text('text', null, null, null));

        if ($exists && $text !== '') {
            $sql->insert(
                self::$schema_index,
                array(
                    'create_profile'	=> $this->create_profile_id,
                    'post'				=> $this->id,
                    'text'				=> $text
                ),
                \RedMap\Engine::INSERT_REPLACE
            );
        } else {
            $sql->delete(self::$schema_index, array(
                'post'	=> $this->id
            ));
        }

        if (!$exists) {
            Reference::delete_by_post($sql, $this->id);
        }

        Profile::invalidate($sql, $this->create_profile_id);
    }
}

Post::$schema = new \RedMap\Schema(
    'board_post',
    array(
        'caution'			=> null,
        'create_profile'	=> null,
        'create_time'		=> null,
        'edit_profile'		=> null,
        'edit_time'			=> null,
        'id'				=> null,
        'state'				=> null,
        'text'				=> null
    ),
    '__',
    array(
        'create_profile'	=> array(function () {
            return Profile::$schema;
        }, 0, array('create_profile' => 'user')),
        'edit_profile'		=> array(function () {
            return Profile::$schema;
        }, \RedMap\Schema::LINK_OPTIONAL, array('edit_profile' => 'user')),
        'ignore'			=> array(function () {
            return Ignore::$schema;
        }, \RedMap\Schema::LINK_OPTIONAL, array('create_profile' => 'target', '!profile' => 'profile')),
        'reference'			=> array(function () {
            return Reference::$schema;
        }, 0, array('id' => 'post'))
    )
);

Post::$schema_index = new \RedMap\Schema(
    'board_post_index',
    array(
        'create_profile'	=> null,
        'post'				=> null,
        'text'				=> null
    ),
    '__',
    array(
        'create_profile'	=> array(function () {
            return Profile::$schema;
        }, 0, array('create_profile' => 'user')),
        'post'				=> array(Post::$schema, 0, array('post' => 'id'))
    )
);
