<?php

ini_set('max_execution_time', 300);

$batch = isset($_GET['b']) ? (int)$_GET['b'] : 10000;
$limit = time() + (int)(ini_get('max_execution_time') * 0.75);

define('YARONET', 'tool');

require '../../src/config.php';

function update($json)
{
    if (!headers_sent()) {
        header('Cache-Control: no-cache');
        header('Content-Type: application/json');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Pragma: no-cache');
    }

    echo json_encode($json);
}

function update_done($lines, $scanned, $touched, $entity, $range)
{
    $result = array('batch' => array('lines' => $lines, 'scanned' => $scanned, 'touched' => $touched));

    if ($entity !== null && $range !== null) {
        $result['next'] = array('entity' => $entity, 'range' => $range);
    }

    update($result);
}

function update_fail($reason)
{
    update(array('error' => $reason));
}

foreach (array('engine.system.encoding.charset', 'engine.system.locale.name', 'engine.network.route.static') as $key) {
    isset($config[$key]) or die('fatal: missing configuration value "' . $key . '"');
}

ini_set('error_reporting', 32759);
mb_internal_encoding($config['engine.system.encoding.charset']) or die('fatal: invalid configuration');
setlocale(LC_ALL, $config['engine.system.locale.name']) or die('fatal: invalid configuration');

// System include
require '../../src/engine/diagnostic/logger.php';
require '../../src/library/glay/glay.php';
require '../../src/library/redmap/redmap.php';

// Markup update helper
function markup_transcode_value($context, &$token, &$lines)
{
    global $sql;
    static $encoder;

    if ($token === null) {
        return false;
    }

    if (!isset($encoder)) {
        if (!function_exists('Amato\autoload')) {
            require '../../src/library/amato/amato.php';

            Amato\autoload();
        }

        $encoder = new Amato\CompactEncoder();
    }

    $pair = $encoder->decode($token);

    if ($pair === null) {
        return false;
    }

    list($plain, $groups) = $pair;

    $save = false;

    for ($i = 0; $i < count($groups); ++$i) {
        $id =& $groups[$i][0];
        $markers =& $groups[$i][1];

        switch ($id) {
            case 'e':
                foreach ($markers as &$marker) {
                    if (isset($marker[1]['c']) && $marker[1]['c'] !== '') {
                        $lines[] = $context . ': ' . $marker[1]['c'];
                        $marker[1]['c'] = '';
                        $save = true;
                    }
                }

                break;
        }
    }

    if (!$save) {
        return false;
    }

    $token = $encoder->encode($plain, $groups);

    return true;
}

function markup_update_fields($key, $columns)
{
    Glay\using('yN\\Engine\\Text\\Markup', '../../src/engine/text/markup.php');

    return function (&$row, &$lines) use ($key, $columns) {
        $errors = array();
        $save = false;

        foreach ($columns as $column) {
            $token = $row[$column];

            if (!markup_transcode_value($row[$key], $token, $lines)) {
                continue;
            }

            $row[$column] = $token;

            $save = true;
        }

        foreach ($errors as $error) {
            $lines[] = $row[$key] . ': ' . $error;
        }

        return $save;
    };
}

// Entities to update
$entities = array(
//    array('account_memo', array('user'), '', markup_update_fields('user', array('text'))),
//    array('account_message', array('id'), '', markup_update_fields('id', array('text'))),
//    array('account_user', array('id'), '', function (&$row, &$lines) {
//    }),
//    array('board_block', array('forum', 'rank'), '', markup_update_fields('forum', array('text'))),
//    array('board_forum', array('id'), '', markup_update_fields('id', array('header', 'preface'))),
//    array('board_post', array('id'), '', markup_update_fields('id', array('text'))),
//    array('board_profile', array('user'), '', markup_update_fields('user', array('signature'))),
//    array('board_section', array('id'), '', markup_update_fields('id', array('header'))),
//    array('board_topic', array('id'), '', markup_update_fields('id', array('name'))),
//    array('chat_shout', array('id'), '', markup_update_fields('id', array('text'))),
//    array('help_page', array('label', 'language'), '', markup_update_fields('label', array('text'))),
);

/* Display main layout */
if (!isset($_GET['e'])) {
    ?>
<html>
	<head>
		<meta charset="UTF-8" />
		<script type="text/javascript" src="<?php echo $config['engine.network.route.static']; ?>/library/jquery/jquery-3.1.1.js"></script>
	</head>
	<body style="font: normal normal normal 10px verdana;">
		<script type="text/javascript">
			var go = function (entity, range)
			{
				var li;
				var ul;

				li = $('<li>').html ('<img src="<?php echo $config['engine.network.route.static']; ?>/layout/html/glyph/10/spin.gif" />');
				ul = $('#log').prepend (li);

				$.ajax ('?', {data: {e: entity, r: range}})
					.done (function (result)
					{
						if (result.batch !== undefined)
						{
							li.text ('Range ' + (range ? '#' + entity + ' @ ' + range : '<begin>') + ' to ' + (result.next ? '#' + result.next.entity + ' @ ' + result.next.range : '<end>') + ': ' + result.batch.scanned + ' scanned, ' + result.batch.touched + ' touched');

							if (result.batch.lines.length > 0)
							{
								li.append ($('<ul>').append ($.map (result.batch.lines, function (v)
								{
									return $('<li>').text (v);
								})));
							}

							if (result.next !== undefined)
								go (result.next.entity, result.next.range);
							else
								ul.prepend ($('<li>').text ('All done!'));
						}
						else
							li.text (result.error !== undefined ? result.error : 'Error!');
					})
					.fail (function ()
					{
						li.text ('Error!');
					});
			}
		</script>
		<a href="#" onclick="go(0); $(this).remove(); return false;">Start</a>
		<ul id="log">
			<li>Update entities:
				<ul>
					<?php
                        foreach ($entities as $i => $entity) {
                            echo '<li>' . htmlentities($i) . ': ' . htmlentities($entity[0]) . '</li>';
                        } ?>
				</ul>
			</li>
		</ul>
	</body>
</html>
<?php

    die;
}

/* Processing */
$sql = RedMap\open($config['engine.network.sql.connection'], function ($error, $query) {
    die($error);
});

if (!$sql->connect()) {
    update_fail('can\'t connect to database');

    die;
}

/* Update loop */
$lines = array();
$reset = true;
$scanned = 0;
$touched = 0;

for ($quota = $batch; $quota > 0 && time() < $limit; $quota -= count($rows)) {
    if ($reset) {
        if (!isset($entity)) {
            $entity = (int)$_GET['e'];
            $range = isset($_GET['r']) ? explode('.', $_GET['r']) : array();
        } else {
            $range = array();

            ++$entity;
        }

        if ($entity < 0 || $entity >= count($entities)) {
            break;
        }

        list($table, $keys, $filter, $callback) = $entities[$entity];

        $reset = false;
    }

    // Select entities from "range + 1" to "range + quota"
    $order = '';
    $values = array();
    $where = '';

    for ($i = 0; $i < count($keys); ++$i) {
        if ($order !== '') {
            $order .= ', ';
        }

        $order .= '`' . $keys[$i] . '`';

        if ($where !== '') {
            $where .= ' OR ';
        }

        $where .= '(';

        for ($j = 0; $j < $i; ++$j) {
            $values[] = $j < count($range) ? $range[$j] : 0;
            $where .= '`' . $keys[$j] . '` = ? AND ';
        }

        $values[] = $i < count($range) ? $range[$i] : 0;
        $where .= '`' . $keys[$i] . '` > ?)';
    }

    $values[] = $quota;
    $rows = $sql->client->select('SELECT * FROM `' . $table . '` WHERE (' . $where . ')' . ($filter ? ' AND (' . $filter . ')' : '') . ' ORDER BY ' . $order . ' LIMIT ?', $values);

    if ($rows === null) {
        update_fail('sql select fail for entity #' . $entity . ', range ' . implode('.', $range));

        die;
    }

    $scanned += count($rows);

    // Process entities
    foreach ($rows as $row) {
        if ($callback($row, $lines)) {
            $fields = '';
            $values = array();

            foreach ($row as $name => $value) {
                if (in_array($name, $keys)) {
                    continue;
                }

                if ($fields !== '') {
                    $fields .= ', ';
                }

                $fields .= '`' . $name . '` = ?';
                $values[] = $value;
            }

            $where = '';

            foreach ($keys as $key) {
                if ($where !== '') {
                    $where .= ' AND ';
                }

                $values[] = $row[$key];
                $where .= '`' . $key . '` = ?';
            }

            if ($sql->client->execute('UPDATE `' . $table . '` SET ' . $fields . ' WHERE ' . $where, $values) === null) {
                update_fail('sql update fail for entity #' . $entity . ', range ' . implode('.', $range));

                die;
            }

            ++$touched;
        }

        foreach ($keys as $i => $key) {
            $range[$i] = $row[$key];
        }

        // Force exit if time limit exceeded
        if (time() >= $limit) {
            break;
        }
    }

    // Switch to next entity if finished
    if (count($rows) < $quota) {
        $reset = true;
    }
}

if ($entity < count($entities)) {
    update_done($lines, $scanned, $touched, $entity, implode('.', $range));
} else {
    update_done($lines, $scanned, $touched, null, null);
}

?>