<?php

namespace petitparser;

use ErrorException;
use Exception;
use PHP_CodeCoverage;
use PHP_CodeCoverage_Exception;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/_functions.php';

set_error_handler(
    function ($errno, $errstr, $errfile, $errline) {
        if ($errno === E_NOTICE && strpos($errstr, 'fb_enable_code_coverage') !== false) {
            echo "# NOTICE: $errstr\n";
            return; // TODO QA: suppress strict error-handling for notice under HHVM with code-coverage
        }

        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
);

@ini_set('xdebug.max_nesting_level', '1000');

/**
 * @param string|null   $name
 * @param callable|null $fn
 * @return string
 */
function group($name = null, $fn = null)
{
    static $current;

    echo "=== GROUP: $name ===\n\n";

    if ($name !== null) {
        $current = $name;
        call_user_func($fn);
    }

    return $current;
}

/**
 * @param string   $name     test description
 * @param callable $function test implementation
 */
function test($name, $function)
{
    echo "\n--- TEST: $name\n\n";

    try {
        coverage('[' . group() . '] ' . $name);
        call_user_func($function);
        coverage();
    } catch (Exception $e) {
        $type = get_class($e);
        $message = $e->getMessage();
        $stack = $e->getTraceAsString();

        echo "\n*** TEST FAILED ***\n\nEXCEPTION {$type} : {$message}\n{$stack}\n";

        status(false);
    }
}

/**
 * @param bool   $result result of assertion
 * @param string $text   description of assertion
 * @param mixed  $value  optional value (displays on failure)
 */
function ok($result, $text, $value = null)
{
    if ($result === true) {
        echo "- PASS: $text\n";
    } else {
        echo "# FAIL: $text" . ($value === null ? '' : ' (' . var_export($value, true) . ')') . "\n";
        status(false);
    }
}

/**
 * @param mixed  $value    tested value
 * @param mixed  $expected expected value
 * @param string $text     description of comparison
 */
function check($value, $expected, $text = 'check')
{
    $check = $value === $expected;

    ok(
        $check,
        "{$text} (got " . format($value, !$check)
        . ($check === false ? (" expected " . format($expected)) : '') . ")"
    );
}

/**
 * @param mixed $value
 * @param bool $verbose
 *
 * @return string
 */
function format($value, $verbose = false)
{
    if (!$verbose && is_array($value)) {
        return 'array[' . count($value) . ']';
    }

    return print_r($value, true);
}

/**
 * @param bool|null $status test status
 * @return int number of failures
 */
function status($status = null) {
    static $failures = 0;

    if ($status === false) {
        $failures += 1;
    }

    return $failures;
}

/**
 * @param float $value    tested value
 * @param float $expected expected value
 * @param string $text    description of comparison
 */
function checkNum($value, $expected, $text = 'checkNum')
{
    $check = abs($value - $expected) < 0.000001;

    ok(
        $check,
        "{$text} (got " . $value
        . ($check === false ? (" expected " . $expected) : '') . ")"
    );
}

/**
 * @param string   $exception Exception type name
 * @param string   $when      description of assertion
 * @param callable $function  function expected to throw
 */
function throws($exception, $when, $function)
{
    try {
        call_user_func($function);
    } catch (Exception $e) {
        if ($e instanceof $exception) {
            ok(true, "throws $exception $when");
            return;
        }
    }

    $why = isset($e)
        ? "unexpected exception " . get_class($e)
        : "no exception was thrown";

    ok(false, "throws $exception $when - $why");
}

/**
 * @param Parser $parser
 * @param mixed  $input
 * @param mixed  $expected
 * @param int    $position
 */
function expectSuccess(Parser $parser, $input, $expected, $position = null)
{
    $buffer = Buffer::fromUTF8($input);

    $result = $parser->parse($buffer);

    check($result->isSuccess, true, 'is success');
    check($result->isFailure, false, 'is not failure');
    check($result->value, $expected);

    if ($position === null) {
        $position = $buffer->length;
    }

    check($result->position, $position, "position is $position");
}

/**
 * @param Parser $parser
 * @param mixed  $input
 * @param int    $position
 * @param string $message
 */
function expectFailure(Parser $parser, $input, $position = 0, $message = null)
{
    $buffer = Buffer::fromUTF8($input);

    $result = $parser->parse($buffer);

    check($result->isFailure, true, "is failure");
    check($result->isSuccess, false, "is not success");
    check($result->position, $position, "position is $position");

    if ($message !== null) {
        check($result->message, $message, "message is: " . var_export($message, true));
    }
}

/**
 * @param string|null $text description (to start coverage); or null (to stop coverage)
 * @return PHP_CodeCoverage|null
 */
function coverage($text = null)
{
    static $coverage = null;
    static $running = false;

    if ($coverage === false) {
        return null; // code coverage unavailable
    }

    if ($coverage === null) {
        try {
            $coverage = new PHP_CodeCoverage;
        } catch (PHP_CodeCoverage_Exception $e) {
            echo "# Notice: no code coverage run-time available\n";
            $coverage = false;
            return null;
        }
    }

    if (is_string($text)) {
        $coverage->start($text);
        $running = true;
    } else {
        if ($running) {
            $coverage->stop();
            $running = false;
        }
    }

    return $coverage;
}
