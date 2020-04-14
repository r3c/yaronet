<?php

$microtime = microtime(true);
$time = time();

define('YARONET', '2.0.0.0');

function config($key, $value)
{
    static $config;

    if (!(isset($config) || @include './config.php')) {
        die('No configuration file was found, please read <a href="https://github.com/r3c/yaronet/blob/master/INSTALL.md">INSTALL.md</a> to setup your yAronet instance.');
    }

    if (array_key_exists($key, $config)) {
        return $config[$key];
    }

    return $value;
}

// Exit if site is disabled
$lock = config('engine.system.lock.source', null);

if ($lock !== null && !in_array($_SERVER['REMOTE_ADDR'], explode(' ', config('engine.system.lock.bypass', '')))) {
    die(file_get_contents($lock));
}

// Apply global configuration settings
mb_internal_encoding(config('engine.system.encoding.charset', 'utf-8')) or die('Invalid configuration value for "engine.system.encoding.charset" option.');
setlocale(LC_ALL, config('engine.system.locale.name', 'en_US.utf8')) or die('Invalid configuration value for "engine.system.locale.name" option.');

// Import and configure system libraries
require('./library/glay/glay.php');

Glay\Network\HTTP::$default_connect_timeout = config('engine.network.http.connect-timeout', 5000);
Glay\Network\HTTP::$default_proxy = config('engine.network.http.proxy', null);
Glay\Network\HTTP::$default_size_max = config('engine.network.http.size-max', 2 * 1024 * 1024);
Glay\Network\HTTP::$default_timeout = config('engine.network.http.timeout', 15000);
Glay\Network\HTTP::$default_useragent = config('engine.network.http.user-agent', 'Mozilla/5.0 (compatible; yAronet; +http://www.yaronet.com/)');

require('./library/redmap/redmap.php');
require('./engine/diagnostic/logger.php');
require('./engine/network/router.php');
require('./engine/text/display.php');
require('./engine/text/internationalization.php');
require('./engine/text/input.php');

// Retrieve remote IP address
$address = Glay\Network\IPAddress::remote();

// Create events logger and connect to error handler
$logger_format = config('engine.diagnostic.logger.format', '[{time}][{url}][{address}][{context}] {title}: {message}');
$logger_level = config('engine.diagnostic.logger.level', yN\Engine\Diagnostic\Logger::LEVEL_MEDIUM);
$logger_path = config('engine.diagnostic.logger.path', './storage/log/{label}_{date}_{level}.log');

$logger = new yN\Engine\Diagnostic\Logger($logger_path, $logger_format, $logger_level);
$logger->context(0);

register_shutdown_function(function () use ($logger) {
    $error = error_get_last();

    if ($error !== null && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)) !== 0) {
        $logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SEVERE, 'system', $error['file'] . ':' . $error['line'], $error['message']);
    }
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logger) {
    // Log error if not silenced
    if (error_reporting() !== 0) {
        if (($errno & ~(E_DEPRECATED | E_NOTICE | E_USER_NOTICE | E_STRICT)) === 0) {
            $level = yN\Engine\Diagnostic\Logger::LEVEL_NOTICE;
        } elseif (($errno & ~(E_WARNING | E_USER_WARNING)) === 0) {
            $level = yN\Engine\Diagnostic\Logger::LEVEL_MEDIUM;
        } else {
            $level = yN\Engine\Diagnostic\Logger::LEVEL_SEVERE;
        }

        $logger->log($level, 'system', $errfile . ':' . $errline, $errstr);
    }

    // Continue with default error handling
    return false;
});

// Include mandatory entities
require('./entity/account/activity.php');
require('./entity/account/message.php');

// Create HTTP request router
$router = yN\Engine\Network\Router::create();

// Resolve route
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = $router->match($_SERVER['REQUEST_METHOD'], $path, $_GET);

try {
    // Establish SQL connection
    $sql_connection = config('engine.network.sql.connection', 'mysqli://yaronet@localhost/yaronet?charset=utf8mb4');
    $sql = RedMap\open($sql_connection, function ($error, $query) use ($logger) {
        $logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SEVERE, 'system', $error, $query);
    });

    if (!$sql->connect()) {
        throw new Exception('can\'t connect to database');
    }

    // Retrieve user information
    if (isset($_COOKIE[yN\Entity\Account\User::COOKIE_NAME])) {
        $user = yN\Entity\Account\User::authenticate_token($sql, (string)$_COOKIE[yN\Entity\Account\User::COOKIE_NAME]) ?: new yN\Entity\Account\User();
    } else {
        $user = new yN\Entity\Account\User();
    }

    $logger->context((int)$user->id);

    // Set up display using given language and/or template overrides
    if ($request !== null) {
        $language = $request->get_or_default('_language');
        $template = $request->get_or_default('_template');

        $router->stick(array(
            '_language' => yN\Engine\Text\Internationalization::is_valid($language) ? $language : null,
            '_template' => yN\Engine\Text\Display::is_internal($template) ? $template : null
        ));
    } else {
        $language = null;
        $template = null;
    }

    $display = new yN\Engine\Text\Display($sql, $logger, $router, $template, $language, $user);

    // Remove trailing slash if request is valid without it or fail otherwise
    if ($request === null) {
        $fallback = rtrim($path, '/');
        $reply = $fallback !== $path && $router->match($_SERVER['REQUEST_METHOD'], $fallback, $_GET) !== null ?
            Glay\Network\HTTP::go($fallback, Glay\Network\HTTP::REDIRECT_PERMANENT) :
            Glay\Network\HTTP::code(404);
    }

    // Forbid access to disabled users
    elseif ($user->is_disabled) {
        $reply = Glay\Network\HTTP::code(403);
    }

    // Process request
    else {
        // Build input data accessor
        $input = new yN\Engine\Text\Input($_REQUEST, $_FILES);

        try {
            $reply = $request->invoke($logger, $sql, $display, $input, $user);
        } catch (Queros\Failure $failure) {
            $reply = Glay\Network\HTTP::code($failure->http_code);
        }
    }

    // Override contents for known errors
    if ($reply->code >= 400) {
        $reply = Glay\Network\HTTP::code($reply->code, $display->render('error.deval', 'error', array(
            'code' => $reply->code
        ), null, true));
    }

    // Send request reply to standard output
    $reply->send();
} catch (Exception $exception) {
    // Rethrow exception if no "website is down" page is configured
    $failure = config('engine.system.failure.source', './resource/failure.html');

    if ($failure === null) {
        throw $exception;
    }

    $logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SEVERE, 'system', 'exception', (string)$exception);

    // Send error reply to standard output
    $error = Glay\Network\HTTP::code(500, file_get_contents($failure));
    $error->send();
}

// Log request
$logger->log(yN\Engine\Diagnostic\Logger::LEVEL_SYSTEM, 'hit', 'time', (microtime(true) - $microtime) . ' s');
