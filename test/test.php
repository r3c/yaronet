<?php

function test_case($name)
{
    global $_test_dump;

    $_test_dump = null;

    echo "\n- " . $name;

    ob_flush();
    flush();
}

function test_dump($dump)
{
    global $_test_dump;

    $_test_dump = $dump;
}

function test_metric($name, $value)
{
    global $_test_metrics;

    if (!isset($_test_metrics[$name])) {
        $_test_metrics[$name] = array();
    }

    $_test_metrics[$name][] = $value;
}

function test_start()
{
    global $_test_metrics;

    $_test_metrics = array();

    $path = 'assert.dump';

    assert_options(ASSERT_BAIL, true);
    assert_options(ASSERT_CALLBACK, function () use ($path) {
        global $_test_dump;

        if (isset($_test_dump)) {
            test_step('Wrote dump to "' . $path . '".');

            file_put_contents($path, $_test_dump);
        }
    });

    if (file_exists($path)) {
        unlink($path);
    }

    ini_set('max_execution_time', 1000);

    header('Content-Type: text/plain; charset=utf-8');

    echo "test suite start...";

    ob_flush();
    flush();
}

function test_step($text)
{
    echo ' [' . $text . ']';

    ob_flush();
    flush();
}

function test_stop()
{
    global $_test_metrics;

    echo "\ntest suite stop.";

    foreach ($_test_metrics as $name => $values) {
        echo "\n- [$name] avg = " . (array_sum($values) / count($values)) . ", sum = " . array_sum($values);
    }

    ob_flush();
    flush();
}
