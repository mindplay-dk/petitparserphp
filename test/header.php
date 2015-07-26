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
 *
 * @return void
 */
function group($name = null, $fn = null)
{
    echo "\n=== GROUP: $name ===\n";

    call_user_func($fn);
}

/**
 * @param string   $name     test description
 * @param callable $function test implementation
 */
function test($name, $function)
{
    echo "\n--- TEST: $name\n\n";

    try {
        call_user_func($function);
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
 * @param string $why    description of assertion
 * @param mixed  $value  optional value (displays on failure)
 */
function ok($result, $why = null, $value = null)
{
    if ($result === true) {
        echo "- PASS: " . ($why === null ? 'OK' : $why) . ($value === null ? '' : ' (' . format($value) . ')') . "\n";
    } else {
        echo "# FAIL: " . ($why === null ? 'ERROR' : $why) . ($value === null ? '' : ' - ' . format($value,
                    true)) . "\n";
        status(false);
    }
}

/**
 * @param mixed  $value    value
 * @param mixed  $expected expected value
 * @param string $why      description of assertion
 */
function check($value, $expected, $why = null)
{
    $result = $value === $expected;

    $info = $result
        ? format($value)
        : "expected: " . format($expected, true) . ", got: " . format($value, true);

    ok($result, ($why === null ? $info : "$why ($info)"));
}

/**
 * @param mixed $value
 * @param bool  $verbose
 *
 * @return string
 */
function format($value, $verbose = false)
{
    if ($value instanceof Exception) {
        return get_class($value)
        . ($verbose ? ": \"" . $value->getMessage() . "\"" : '');
    }

    if (!$verbose && is_array($value)) {
        return 'array[' . count($value) . ']';
    }

    if (is_bool($value)) {
        return $value ? 'TRUE' : 'FALSE';
    }

    if (is_object($value) && !$verbose) {
        return get_class($value);
    }

    if (is_scalar($value) && $verbose) {
        return print_r($value, true) . ' [' . gettype($value) . ']';
    }

    return print_r($value, true);
}

/**
 * @param bool|null $status test status
 * @return int number of failures
 */
function status($status = null)
{
    static $failures = 0;

    if ($status === false) {
        $failures += 1;
    }

    return $failures;
}

/**
 * @param float  $value    tested value
 * @param float  $expected expected value
 * @param string $text     description of comparison
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
 * @param string   $exception_type Exception type name
 * @param string   $why            description of assertion
 * @param callable $function       function expected to throw
 */
function throws($exception_type, $why, $function)
{
    try {
        call_user_func($function);
    } catch (Exception $e) {
        if ($e instanceof $exception_type) {
            ok(true, $why, $e);
            return;
        } else {
            $actual_type = get_class($e);
            ok(false, "$why (expected $exception_type but $actual_type was thrown)");
            return;
        }
    }

    ok(false, "$why (expected exception $exception_type was NOT thrown)");
}

/**
 * @param Parser        $parser
 * @param Buffer|string $input
 * @param mixed         $expected
 * @param int           $position
 */
function expectSuccess(Parser $parser, $input, $expected, $position = null)
{
    $buffer = is_string($input) ? Buffer::create($input) : $input;

    $result = $parser->parse($buffer);

    check($result->isSuccess(), true, 'is success');
    check($result->isFailure(), false, 'is not failure');
    check($result->getValue(), $expected);

    if ($position === null) {
        $position = $buffer->getLength();
    }

    check($result->getPosition(), $position, "position is $position");
}

/**
 * @param Parser        $parser
 * @param Buffer|string $input
 * @param int           $position
 * @param string        $message
 */
function expectFailure(Parser $parser, $input, $position = 0, $message = null)
{
    $buffer = is_string($input) ? Buffer::create($input) : $input;

    $result = $parser->parse($buffer);

    check($result->isFailure(), true, "is failure");
    check($result->isSuccess(), false, "is not success");
    check($result->getPosition(), $position, "position is $position");

    if ($message !== null) {
        check($result->getMessage(), $message, "message is: " . var_export($message, true));
    }
}

/**
 * @return PHP_CodeCoverage|null code coverage service, if available
 */
function coverage()
{
    static $coverage = null;

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

    return $coverage;
}
