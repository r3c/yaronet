<?php

namespace yN\Entity\Survey;

defined('YARONET') or die;

\Glay\using('yN\\Entity\\Model', './entity/model.php');

class Poll extends \yN\Entity\Model
{
    const MODEL_COST = 2;

    const RANK_MAX = 20;

    const TYPE_MULTIPLE = 1;
    const TYPE_SINGLE = 0;

    public static $schema;
    public static $schema_cache = null;

    public static function create($sql, $question, $type, $choices, &$alert)
    {
        if (count($choices) < 2 || count($choices) > self::RANK_MAX) {
            $alert = 'choices-length';

            return null;
        }

        $poll = new self();
        $poll->question = (string)$question;
        $poll->type = (int)$type;

        if (!$poll->save($sql, $alert)) {
            return null;
        }

        foreach ($choices as $choice) {
            $poll_choice = new PollChoice();
            $poll_choice->poll_id = $poll->id;
            $poll_choice->text = (string)$choice;

            if (!$poll_choice->save($sql, $alert)) {
                return null;
            }
        }

        return $poll;
    }

    public static function get_by_identifier($sql, $poll_id, $user_id = null)
    {
        $relations = array('choice' => null);

        if ($user_id !== null) {
            $relations['vote'] = array('!user' => (int)$user_id);
        }

        $rows = $sql->select(self::$schema, array('id' => (int)$poll_id, '+' => $relations));

        if (count($rows) < 1) {
            return null;
        }

        $poll = new self($sql, $rows[0]);

        foreach ($rows as $row) {
            $poll->choices[] = new PollChoice($sql, $row, 'choice__');
        }

        return $poll;
    }

    public static function submit($sql, $poll_id, $user_id, $ranks)
    {
        if (
            $sql->insert(PollVote::$schema, array('poll' => (int)$poll_id, 'user' => (int)$user_id), \RedMap\Engine::INSERT_REPLACE) === null ||
            $sql->update(self::$schema, array('votes' => new \RedMap\Increment(1)), array('id' => (int)$poll_id)) === null
        ) {
            return false;
        }

        $success = true;

        foreach (array_splice($ranks, 0, self::RANK_MAX) as $rank) {
            $success = $sql->update(PollChoice::$schema, array('score' => new \RedMap\Increment(1)), array('poll' => (int)$poll_id, 'rank' => (int)$rank)) !== null && $success;
        }

        return $success;
    }

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->id = (int)$row[$ns . 'id'];
            $this->question = $row[$ns . 'question'];
            $this->type = (int)$row[$ns . 'type'];
            $this->vote = isset($row[$ns . 'vote__poll']);
            $this->votes = (int)$row[$ns . 'votes'];
        } else {
            $this->id = null;
            $this->question = '';
            $this->type = self::TYPE_SINGLE;
            $this->vote = false;
            $this->votes = 0;
        }

        $this->choices = array();
    }

    public function get_primary()
    {
        if ($this->id === null) {
            return null;
        }

        return array('id' => $this->id);
    }

    public function save($sql, &$alert)
    {
        $question_length = strlen($this->question);

        if ($question_length < 1 || $question_length > 512) {
            $alert = 'question-length';
        } elseif ($this->type !== self::TYPE_MULTIPLE && $this->type !== self::TYPE_SINGLE) {
            $alert = 'type-invalid';
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
            'id'		=> $this->id,
            'question'	=> $this->question,
            'type'		=> $this->type,
            'votes'		=> $this->votes
        );
    }
}

class PollChoice extends \yN\Entity\Model
{
    const MODEL_COST = 1;

    public static $schema;
    public static $schema_cache = null;

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->poll = isset($row[$ns . 'poll__id']) ? new Poll($sql, $row, $ns . 'poll__') : null;
            $this->poll_id = (int)$row[$ns . 'poll'];
            $this->rank = (int)$row[$ns . 'rank'];
            $this->score = (int)$row[$ns . 'score'];
            $this->text = $row[$ns . 'text'];
        } else {
            $this->poll = null;
            $this->poll_id = null;
            $this->rank = null;
            $this->score = 0;
            $this->text = '';
        }
    }

    public function get_primary()
    {
        if ($this->poll_id === null || $this->rank === null) {
            return null;
        }

        return array('poll' => $this->poll_id, 'rank' => $this->rank);
    }

    public function save($sql, &$alert)
    {
        $text_length = strlen($this->text);

        if ($this->poll_id === null) {
            $alert = 'poll-null';
        } elseif ($text_length < 1 || $text_length > 512) {
            $alert = 'text-length';
        } else {
            return parent::save($sql, $alert);
        }

        return false;
    }

    public function set_primary($key)
    {
        $this->rank = $key;
    }

    protected function export()
    {
        return array(
            'poll'	=> $this->poll_id,
            'rank'	=> $this->rank,
            'score'	=> $this->score,
            'text'	=> $this->text
        );
    }
}

class PollVote
{
    public static $schema;

    public function __construct($sql = null, $row = null, $ns = '')
    {
        global $time;

        if ($row !== null) {
            $this->poll = isset($row[$ns . 'poll__id']) ? new Poll($sql, $row, $ns . 'poll__') : null;
            $this->poll_id = (int)$row[$ns . 'poll'];
            $this->user = isset($row[$ns . 'user__id']) ? new \yN\Entity\Account\User($sql, $row, $ns . 'user__') : null;
            $this->user_id = (int)$row[$ns . 'user'];
        } else {
            $this->poll = null;
            $this->poll_id = null;
            $this->user = null;
            $this->user_id = null;
        }
    }
}

PollChoice::$schema = new \RedMap\Schema('survey_poll_choice', array(
    'poll'	=> null,
    'rank'	=> null,
    'score'	=> null,
    'text'	=> null
));

PollVote::$schema = new \RedMap\Schema('survey_poll_vote', array(
    'poll'	=> null,
    'user'	=> null
));

Poll::$schema = new \RedMap\Schema(
    'survey_poll',
    array(
        'id'		=> null,
        'question'	=> null,
        'type'		=> null,
        'votes'		=> null
    ),
    '__',
    array(
        'choice'	=> array(PollChoice::$schema, \RedMap\Schema::LINK_OPTIONAL, array('id' => 'poll')),
        'vote'		=> array(PollVote::$schema, \RedMap\Schema::LINK_OPTIONAL, array('id' => 'poll', '!user' => 'user'))
    )
);
