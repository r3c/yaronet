<?php

function test_case($name)
{
    global $_test_verbose;

    $_test_verbose = null;

    echo "\n- " . $name;

    ob_flush();
    flush();
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

    assert_options(ASSERT_BAIL, true);
    assert_options(ASSERT_CALLBACK, function ($file, $line, $assertion, $description) {
        global $_test_verbose;

        test_step('Failed: ' . $description);

        if (isset($_test_verbose)) {
            echo "\n$_test_verbose";
        }
    });

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

function test_verbose($dump)
{
    global $_test_verbose;

    $_test_verbose = $dump;
}
