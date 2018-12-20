<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
\Glay\using('yN\\Entity\\Board\\Section', './entity/board/section.php');
\Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Block extends \yN\Entity\Model
{
    const FRESH_TIME = 15 * 86400;

    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public static function get_by_forum($sql, $forum_id, $profile_id)
    {
        $entities = array();
        $rows = $sql->select(
            self::$schema,
            array(
                'forum' => (int)$forum_id,
                '+' => array(
                    'section' => $profile_id !== null
                        ? array('+' => array(
                            'permission' => array('!profile' => (int)$profile_id),
                            'read' => array('!profile' => (int)$profile_id),
                            'subscription' => array('!profile' => (int)$profile_id)
                        ))
                        : null
                )
            ),
            array('rank' => true)
        );

        foreach ($rows as $row) {
            $entities[] = new self($sql, $row);
        }

        return $entities;
    }

    public static function get_by_forum__rank($sql, $forum_id, $rank)
    {
        return self::entry_get_one($sql, array(
            '+' => array('forum' => null, 'section' => array('+' => array('forum' => null))),
            'forum' => (int)$forum_id,
            'rank' => $rank
        ));
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->forum = isset($row[$ns . 'forum__id']) ? new Forum($sql, $row, $ns . 'forum__') : null;
            $this->forum_id = (int)$row[$ns . 'forum'];
            $this->rank = (int)$row[$ns . 'rank'];
            $this->section = isset($row[$ns . 'section__id']) ? new Section($sql, $row, $ns . 'section__') : null;
            $this->section_id = $row[$ns . 'section'] !== null ? (int)$row[$ns . 'section'] : null;
            $this->text = $row[$ns . 'text'];
        } else {
            $this->forum = null;
            $this->forum_id = null;
            $this->rank = null;
            $this->section = null;
            $this->section_id = null;
            $this->text = null;
        }
    }

    public function convert_text($plain, $router, $logger)
    {
        $this->text = \yN\Engine\Text\Markup::convert('bbcode-block', $plain, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function get_primary()
    {
        if ($this->forum_id === null) {
            return null;
        }

        return array('forum' => $this->forum_id, 'rank' => $this->rank);
    }

    /*
    ** Move current block relative to other blocks from the same forum. This
    ** operation is performed by deleting the block, moving other blocks within
    ** same forum and re-creating it. Existing instances to any other block
    ** from this forum must be considered as invalid after this method is
    ** called.
    ** $sql: sql engine
    ** $rank: new blank rank
    ** &alert: output alert label
    ** return: success status
    */
    public function move($sql, $rank, &$alert)
    {
        if ($this->forum_id === null) {
            $alert = 'forum-null';

            return false;
        }

        if ($this->rank === $rank) {
            return true;
        }

        if (!$this->delete($sql, $alert)) {
            return false;
        }

        // FIXME: replace with RedMap equivalent [sql-hardcode]
        $result = $this->rank < $rank ?
            $sql->client->execute('UPDATE `board_block` SET `rank` = `rank` - 1 WHERE `forum` = ? AND `rank` > ? AND `rank` <= ? ORDER BY `rank` ASC', array($this->forum_id, $this->rank, $rank)) :
            $sql->client->execute('UPDATE `board_block` SET `rank` = `rank` + 1 WHERE `forum` = ? AND `rank` >= ? AND `rank` < ? ORDER BY `rank` DESC', array($this->forum_id, $rank, $this->rank));

        if ($result === null) {
            // FIXME: forum blocks can be inconsistent if this manual rollback query fails [sql-transaction]
            $this->save($sql, $alert);

            $alert = 'sql';

            return false;
        }

        $this->rank = $rank;

        return true;
    }

    public function render_text($format, $router, $logger)
    {
        return \yN\Engine\Text\Markup::render($format, $this->text, \yN\Engine\Text\Markup::context($router, $logger));
    }

    public function revert()
    {
        return array(
            'forum' => $this->forum_id,
            'rank' => $this->rank,
            'section' => $this->section_id,
            'text' => $this->revert_text()
        );
    }

    public function revert_text()
    {
        return \yN\Engine\Text\Markup::revert('bbcode-block', $this->text, \yN\Engine\Text\Markup::context());
    }

    public function save($sql, &$alert)
    {
        $blank_length = strlen(\yN\Engine\Text\Markup::blank());
        $text_length = strlen((string)$this->text);

        if ($this->forum_id === null) {
            $alert = 'forum-null';
        } elseif ($this->text !== null && ($text_length < $blank_length + 1 || $text_length > 32768)) {
            $alert = 'text-length';
        } else {
            return parent::save($sql, $alert);
        }

        return false;
    }

    public function set_primary($key)
    {
    }

    protected function export()
    {
        return array(
            'forum' => $this->forum_id,
            'rank' => $this->rank,
            'section' => $this->section_id,
            'text' => $this->text
        );
    }
}

Block::$schema = new \RedMap\Schema(
    'board_block',
    array(
        'forum' => null,
        'rank' => null,
        'section' => null,
        'text' => null
    ),
    '__',
    array(
        'forum' => array(function () {
            return Forum::$schema;
        }, 0, array('forum' => 'id')),
        'section' => array(function () {
            return Section::$schema;
        }, \RedMap\Schema::LINK_OPTIONAL, array('section' => 'id'))
    )
);
