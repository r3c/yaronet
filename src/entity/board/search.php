<?php

namespace yN\Entity\Board;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Account\\User', './entity/account/user.php');
\Glay\using('yN\\Entity\\Board\\Forum', './entity/board/forum.php');
\Glay\using('yN\\Entity\\Board\\Profile', './entity/board/profile.php');
\Glay\using('yN\\Entity\\Board\\Reference', './entity/board/reference.php');
\Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');
\Glay\using('yN\\Entity\\Model', './entity/model.php');

class Search extends \yN\Entity\Model
{
    const EXPIRE_DURATION = 86400 * 30; // 30 days

    const MODEL_COST = 1;

    const QUERY_LENGTH_MIN = 4;

    const RESULT_COUNT = 300;
    const RESULT_PAGE = 30;

    public static $schema;
    public static $schema_cache = null;

    public static function clean($sql)
    {
        global $time;

        // FIXME: replace with RedMap equivalent [sql-hardcode]
        // FIXME: reclaim memory [sql-memory]
        return
            $sql->client->execute('DELETE s, sr FROM `' . self::$schema->table . '` s JOIN `' . SearchResult::$schema->table . '` sr ON s.id = sr.search WHERE s.time < ?', array($time - self::EXPIRE_DURATION)) !== null &&
            $sql->client->execute('OPTIMIZE TABLE `' . self::$schema->table . '`') !== null &&
            $sql->client->execute('OPTIMIZE TABLE `' . SearchResult::$schema->table . '`') !== null;
    }

    public static function execute($sql, $query, $profile_id, $forum_id, $filter_profile_id, &$search, &$alert)
    {
        $query = self::sanitize_query($query);

        if (mb_strlen($query) < Search::QUERY_LENGTH_MIN) {
            $alert = 'query-length';

            return false;
        }

        $search = new self();
        $search->forum_id = (int)$forum_id;
        $search->profile_id = (int)$profile_id;
        $search->query = $query;

        if (!$search->save($sql, $alert)) {
            return false;
        }

        if (!SearchResult::execute($sql, $search->id, $query, $forum_id, $filter_profile_id)) {
            $alert = 'sql';

            return false;
        }

        return true;
    }

    public static function get_by_identifier($sql, $search_id)
    {
        return self::entry_get_one($sql, array(
            '+' => array('forum' => null),
            'id' => (int)$search_id
        ));
    }

    public static function get_results($sql, $search_id, $profile_id, $from)
    {
        return SearchResult::get_by_search($sql, $search_id, $profile_id, $from);
    }

    public static function sanitize_query($query)
    {
        $length = self::QUERY_LENGTH_MIN;
        $query = mb_strtolower($query);
        $query = iconv(mb_internal_encoding(), 'ASCII//TRANSLIT', $query);
        $query = preg_replace('/[^-\s0-9A-Za-z]+/', '', $query);
        $query = preg_replace('/\s{2,}/', ' ', $query);
        $query = preg_replace('/^ | $/', '', $query);
        $query = array_filter(explode(' ', $query), function ($word) use ($length) {
            return mb_strlen($word) >= $length;
        });

        $query = implode(' ', $query);

        return $query;
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->id = (int)$row[$ns . 'id'];
            $this->forum = isset($row[$ns . 'forum__id']) ? new Forum($sql, $row, $ns . 'forum__') : null;
            $this->forum_id = (int)$row[$ns . 'forum'];
            $this->profile = isset($row[$ns . 'profile__user']) ? new Profile($sql, $row, $ns . 'profile__') : null;
            $this->profile_id = (int)$row[$ns . 'profile'];
            $this->query = $row[$ns . 'query'];
            $this->time = (int)$row[$ns . 'time'];
        } else {
            $this->id = null;
            $this->forum = null;
            $this->forum_id = null;
            $this->profile = null;
            $this->profile_id = null;
            $this->query = '';
            $this->time = $time;
        }
    }

    public function get_primary()
    {
        if ($this->id === null) {
            return null;
        }

        return array('id' => $this->id);
    }

    public function set_primary($key)
    {
        $this->id = $key;
    }

    protected function export()
    {
        return array(
            'id' => $this->id,
            'forum' => $this->forum_id,
            'profile' => $this->profile_id,
            'query' => $this->query,
            'time' => $this->time
        );
    }
}

class SearchResult extends \yN\Entity\Model
{
    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public static function execute($sql, $search_id, $query, $forum_id, $profile_id)
    {
        $filters = array(
            '+' => array(
                'post_index' => array(
                    'text|mb' => $query
                ),
                'topic' => array(
                    '+' => array(
                        'section' => array('forum' => (int)$forum_id)
                    )
                )
            )
        );

        if ($profile_id !== null) {
            $filters['+']['post_index']['create_profile'] = (int)$profile_id;
        }

        return $sql->source(
            self::$schema,
            array(
                'position' => array(\RedMap\Engine::SOURCE_COLUMN, 'position'),
                'search' => array(\RedMap\Engine::SOURCE_VALUE, (int)$search_id),
                'topic' => array(\RedMap\Engine::SOURCE_COLUMN, 'topic')
            ),
            \RedMap\Engine::INSERT_APPEND,
            Reference::$schema,
            $filters,
            array(),
            Search::RESULT_COUNT
        ) !== null;
    }

    public static function get_by_search($sql, $search_id, $profile_id, $from)
    {
        return self::entry_get_all(
            $sql,
            array(
                '+' => array(
                    'reference' => array(
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
                                    'permission' => array('!profile' => (int)$profile_id),
                                    'section' => array(
                                        '+' => array(
                                            'permission' => array('!profile' => (int)$profile_id)
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                'search' => (int)$search_id
            ),
            array('topic' => false, 'position' => true),
            Search::RESULT_PAGE + 1,
            $from
        );
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        if ($row !== null) {
            $this->position = (int)$row[$ns . 'position'];
            $this->reference = isset($row[$ns . 'reference__position']) ? new Reference($sql, $row, $ns . 'reference__') : null;
            $this->search = isset($row[$ns . 'search__id']) ? new Search($sql, $row, $ns . 'search__') : null;
            $this->search_id = (int)$row[$ns . 'search'];
            $this->topic = isset($row[$ns . 'topic__id']) ? new Topic($sql, $row, $ns . 'topic__') : null;
            $this->topic_id = (int)$row[$ns . 'topic'];
        } else {
            $this->position = null;
            $this->reference = null;
            $this->search = null;
            $this->search_id = null;
            $this->topic = null;
            $this->topic_id = null;
        }
    }

    public function get_primary()
    {
        throw new \Exception();
    }

    public function set_primary($key)
    {
        throw new \Exception();
    }

    protected function export()
    {
        throw new \Exception();
    }
}

Search::$schema = new \RedMap\Schema(
    'board_search',
    array(
        'forum' => null,
        'id' => null,
        'profile' => null,
        'query' => null,
        'time' => null
    ),
    '__',
    array(
        'forum' => array(function () {
            return Forum::$schema;
        }, 0, array('forum' => 'id')),
        'profile' => array(function () {
            return Profile::$schema;
        }, 0, array('profile' => 'user'))
    )
);

SearchResult::$schema = new \RedMap\Schema(
    'board_search_result',
    array(
        'position' => null,
        'search' => null,
        'topic' => null
    ),
    '__',
    array(
        'reference' => array(function () {
            return Reference::$schema;
        }, 0, array('position' => 'position', 'topic' => 'topic')),
        'search' => array(Search::$schema, 0, array('search' => 'id')),
        'topic' => array(function () {
            return Topic::$schema;
        }, 0, array('topic' => 'id'))
    )
);
