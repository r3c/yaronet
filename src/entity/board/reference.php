<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Board\\Post', './entity/board/post.php');
\Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');
\Glay\using('yN\\Engine\\Text\\Markup', './engine/text/markup.php');

class Reference extends \yN\Entity\Model
{
    const MODEL_COST = 0;

    public static $schema;
    public static $schema_cache = null;

    public static function delete_by_post($sql, $post_id)
    {
        $id = (int)$post_id;

        // Delete topics that will become empty after deleting given references
        // Note: invalidation of parent sections is missing
        // FIXME: replace with RedMap equivalent [sql-hardcode]
        if ($sql->client->execute('DELETE t FROM `board_topic` t JOIN `board_reference` r1 ON r1.`topic` = `t`.`id` AND r1.`post` = ? WHERE NOT EXISTS (SELECT 1 FROM `board_reference` r2 WHERE r2.`topic` = t.`id` AND r2.`post` <> ?)', array($id, $id)) !== null)
            return false;

        return $sql->delete(self::$schema, array('post' => $id)) !== null;
    }

    public static function delete_by_topic($sql, $topic_id)
    {
        $id = (int)$topic_id;

        // Delete posts that will become empty after deleting given references
        // FIXME: replace with RedMap equivalent [sql-hardcode]
        if ($sql->client->execute('DELETE p FROM `board_post` p JOIN `board_reference` r1 ON r1.`post` = `p`.`id` AND r1.`topic` = ? WHERE NOT EXISTS (SELECT 1 FROM `board_reference` r2 WHERE r2.`post` = p.`id` AND r2.`topic` <> ?)', array($id, $id)) !== null)
            return false;

        return $sql->delete(self::$schema, array('topic' => $id)) !== null;
    }

    public static function get_by_position($sql, $topic_id, $position, $profile_id)
    {
        return self::entry_get_one($sql, array(
            'position' => (int)$position,
            'topic' => (int)$topic_id,
            '+' => array(
                'post' => array(
                    '+' => array(
                        'create_profile' => null,
                        'edit_profile' => null,
                        'ignore' => array('!profile' => (int)$profile_id)
                    )
                ),
                'topic' => array(
                    '+' => array(
                        'section' => array(
                            '+' => array('forum' => null)
                        )
                    )
                )
            )
        ));
    }

    public static function get_by_positions($sql, $topic_id, $positions, $limit)
    {
        $filters = array('~' => 'or');

        foreach ($positions as $position) {
            $filters[] = array('position' => (int)$position);
        }

        return self::entry_get_all($sql, array('topic' => (int)$topic_id, $filters), array('position' => true), $limit);
    }

    public static function get_by_topic($sql, $topic_id, $page, $profile_id, $recall = 0)
    {
        $start = $page * Topic::PAGE_SIZE + 1;
        $count = Topic::PAGE_SIZE;

        if ($recall > 0 && $start > $recall + 1) {
            $start -= $recall;
            $count += $recall;
        }

        return self::entry_get_all(
            $sql,
            array(
                'position|ge' => $start,
                'position|lt' => $start + $count,
                'topic' => (int)$topic_id,
                '+' => array(
                    'post' => array(
                        '+' => array(
                            'create_profile' => null,
                            'edit_profile' => null,
                            'ignore' => array('!profile' => (int)$profile_id)
                        )
                    ),
                    'topic' => null
                )
            ),
            array('position' => true),
            $count
        );
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->position = (int)$row[$ns . 'position'];
            $this->post = isset($row[$ns . 'post__id']) ? new Post($sql, $row, $ns . 'post__') : null;
            $this->post_id = (int)$row[$ns . 'post'];
            $this->topic = isset($row[$ns . 'topic__id']) ? new Topic($sql, $row, $ns . 'topic__') : null;
            $this->topic_id = (int)$row[$ns . 'topic'];
        } else {
            $this->position = null;
            $this->post = null;
            $this->post_id = null;
            $this->topic = null;
            $this->topic_id = null;
        }
    }

    public function get_primary()
    {
        if ($this->topic_id === null || $this->position === null) {
            return null;
        }

        return array('topic' => $this->topic_id, 'position' => $this->position);
    }

    public function save($sql, &$alert)
    {
        if ($this->post_id === null) {
            $alert = 'post-null';
        } elseif ($this->topic_id === null) {
            $alert = 'topic-null';
        } else {
            return parent::save($sql, $alert);
        }

        return false;
    }

    public function set_primary($key)
    {
        $this->position = $key;
    }

    protected function export()
    {
        return array(
            'position' => $this->position,
            'post' => $this->post_id,
            'topic' => $this->topic_id
        );
    }

    protected function on_touch($sql, $exists)
    {
        Topic::invalidate($sql, $this->topic_id);
    }
}

Reference::$schema = new \RedMap\Schema(
    'board_reference',
    array(
        'position' => null,
        'post' => null,
        'topic' => null
    ),
    '__',
    array(
        'post' => array(function () {
            return Post::$schema;
        }, 0, array('post' => 'id')),
        'post_index' => array(function () {
            return Post::$schema_index;
        }, 0, array('post' => 'post')),
        'topic' => array(function () {
            return Topic::$schema;
        }, 0, array('topic' => 'id'))
    )
);
