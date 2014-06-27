<?php

namespace petitparser;

use Exception;
use ErrorException;

require __DIR__ . DIRECTORY_SEPARATOR . __NAMESPACE__ . DIRECTORY_SEPARATOR . '_functions.php';

spl_autoload_register(
    function ($name) {
        include_once __DIR__ . DIRECTORY_SEPARATOR . strtr($name, '\\', DIRECTORY_SEPARATOR) . '.php';
    }
);

set_error_handler(
    function ($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
);

@ini_set('xdebug.max_nesting_level', '1000');

/**
 * @param string   $name
 * @param callable $fn
 */
function group($name, $fn)
{
    echo "=== GROUP: $name ===\n\n";

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
        : "no exception was thron";

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
    $result = $parser->parse(Buffer::fromUTF8($input));

    check($result->isSuccess, true, 'is success');
    check($result->isFailure, false, 'is not failure');
    check($result->value, $expected);

    if ($position === null) {
        $position = length($input);
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
    $result = $parser->parse(Buffer::fromUTF8($input));

    check($result->isFailure, true, "is failure");
    check($result->isSuccess, false, "is not success");
    check($result->position, $position, "position is $position");

    if ($message !== null) {
        check($result->message, $message, "message is: " . var_export($message, true));
    }
}

// TEST:

header('Content-type: text/plain');

class PluggableCompositeParser extends CompositeParser
{
    /**
     * @var callable
     */
    protected $_function;

    /**
     * @param callable $function
     */
    public function __construct($function)
    {
        $this->_function = $function;

        parent::__construct();
    }

    public function initialize()
    {
        return call_user_func($this->_function, $this);
    }
}

group(
    'parsers',
    function () {
        test(
            'basics',
            function () {
                $parser = char('a');
                expectSuccess($parser, 'a', 'a', 1);
            }
        );

        test(
            'and()',
            function () {
                $parser = char('a')->and_();
                expectSuccess($parser, 'a', 'a', 0);
                expectFailure($parser, 'b', 0, 'a expected');
                expectFailure($parser, '');
            }
        );

        test(
            'or() of two',
            function () {
                $parser = char('a')->or_(char('b'));
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'b', 'b');
                expectFailure($parser, 'c');
                expectFailure($parser, '');
            }
        );

        test(
            'or() of three',
            function () {
                $parser = char('a')->or_(char('b'))->or_(char('c'));
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'b', 'b');
                expectSuccess($parser, 'c', 'c');
                expectFailure($parser, 'd');
                expectFailure($parser, '');
            }
        );

        test(
            'end()',
            function () {
                $parser = char('a')->end_();
                expectFailure($parser, '', 0, 'a expected');
                expectSuccess($parser, 'a', 'a');
                expectFailure($parser, 'aa', 1, 'end of input expected');
            }
        );

        test(
            'epsilon()',
            function () {
                $parser = epsilon();
                expectSuccess($parser, '', null);
                expectSuccess($parser, 'a', null, 0);
            }
        );

        test(
            'failure()',
            function () {
                $parser = failure('failure');
                expectFailure($parser, '', 0, 'failure');
                expectFailure($parser, 'a', 0, 'failure');
            }
        );
        test(
            'flatten()',
            function () {
                $parser = digit()->plus()->flatten();
                expectFailure($parser, '');
                expectFailure($parser, 'a');
                expectSuccess($parser, '1', '1');
                expectSuccess($parser, '12', '12');
                expectSuccess($parser, '123', '123');
                expectSuccess($parser, '1234', '1234');
            }
        );

        test(
            'token()',
            function () {
                $parser = digit()->plus()->token();
                expectFailure($parser, '');
                expectFailure($parser, 'a');
                /** @var Token $token */
                $token = $parser->parse('123')->value;
                check($token->value, array('1', '2', '3'));
                check($token->buffer->string, '123');
                check($token->start, 0);
                check($token->stop, 3);
                check($token->input, '123');
                check($token->length, 3);
                check($token->line, 1);
                check($token->column, 1);
                check((string)$token, 'Token[1:1]: [1, 2, 3]');
            }
        );

        test(
            'map()',
            function () {
                $parser = digit()->map(
                    function ($each) {
                        return ord($each) - ord('0');
                    }
                );
                expectSuccess($parser, '1', 1);
                expectSuccess($parser, '4', 4);
                expectSuccess($parser, '9', 9);
                expectFailure($parser, '');
                expectFailure($parser, 'a');
            }
        );

        test(
            'pick(1)',
            function () {
                $parser = digit()->seq(letter())->pick(1);
                expectSuccess($parser, '1a', 'a');
                expectSuccess($parser, '2b', 'b');
                expectFailure($parser, '');
                expectFailure($parser, '1', 1, 'letter expected');
                expectFailure($parser, '12', 1, 'letter expected');
            }
        );

        test(
            'pick(-1)',
            function () {
                $parser = digit()->seq(letter())->pick(- 1);
                expectSuccess($parser, '1a', 'a');
                expectSuccess($parser, '2b', 'b');
                expectFailure($parser, '');
                expectFailure($parser, '1', 1, 'letter expected');
                expectFailure($parser, '12', 1, 'letter expected');
            }
        );

        test(
            'permute([1, 0])',
            function () {
                $parser = digit()->seq(letter())->permute(array(1, 0));
                expectSuccess($parser, '1a', array('a', '1'));
                expectSuccess($parser, '2b', array('b', '2'));
                expectFailure($parser, '');
                expectFailure($parser, '1', 1, 'letter expected');
                expectFailure($parser, '12', 1, 'letter expected');
            }
        );

        test(
            'permute([-1, 0])',
            function () {
                $parser = digit()->seq(letter())->permute(array(- 1, 0));
                expectSuccess($parser, '1a', array('a', '1'));
                expectSuccess($parser, '2b', array('b', '2'));
                expectFailure($parser, '');
                expectFailure($parser, '1', 1, 'letter expected');
                expectFailure($parser, '12', 1, 'letter expected');
            }
        );

        test(
            'not()',
            function () {
                $parser = char('a')->not_('not a expected');
                expectFailure($parser, 'a', 0, 'not a expected');
                expectSuccess($parser, 'b', null, 0);
                expectSuccess($parser, '', null);
            }
        );

        test(
            'neg()',
            function () {
                $parser = digit()->neg('no digit expected');
                expectFailure($parser, '1', 0, 'no digit expected');
                expectFailure($parser, '9', 0, 'no digit expected');
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, ' ', ' ');
                expectFailure($parser, '', 0, 'input expected');
            }
        );

        test(
            'optional()',
            function () {
                $parser = char('a')->optional();
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'b', null, 0);
                expectSuccess($parser, '', null);
            }
        );

        test(
            'plus()',
            function () {
                $parser = char('a')->plus();
                expectFailure($parser, '', 0, 'a expected');
                expectSuccess($parser, 'a', array('a'));
                expectSuccess($parser, 'aa', array('a', 'a'));
                expectSuccess($parser, 'aaa', array('a', 'a', 'a'));
            }
        );

        test(
            'plusGreedy()',
            function () {
                $parser = word()->plusGreedy(digit());
                expectFailure($parser, '', 0, 'letter or digit expected');
                expectFailure($parser, 'a', 1, 'digit expected');
                expectFailure($parser, 'ab', 1, 'digit expected');
                expectFailure($parser, '1', 1, 'digit expected');
                expectSuccess($parser, 'a1', array('a'), 1);
                expectSuccess($parser, 'ab1', array('a', 'b'), 2);
                expectSuccess($parser, 'abc1', array('a', 'b', 'c'), 3);
                expectSuccess($parser, '12', array('1'), 1);
                expectSuccess($parser, 'a12', array('a', '1'), 2);
                expectSuccess($parser, 'ab12', array('a', 'b', '1'), 3);
                expectSuccess($parser, 'abc12', array('a', 'b', 'c', '1'), 4);
                expectSuccess($parser, '123', array('1', '2'), 2);
                expectSuccess($parser, 'a123', array('a', '1', '2'), 3);
                expectSuccess($parser, 'ab123', array('a', 'b', '1', '2'), 4);
                expectSuccess($parser, 'abc123', array('a', 'b', 'c', '1', '2'), 5);
            }
        );
        test(
            'plusLazy()',
            function () {
                $parser = word()->plusLazy(digit());
                expectFailure($parser, '');
                expectFailure($parser, 'a', 1, 'digit expected');
                expectFailure($parser, 'ab', 2, 'digit expected');
                expectFailure($parser, '1', 1, 'digit expected');
                expectSuccess($parser, 'a1', array('a'), 1);
                expectSuccess($parser, 'ab1', array('a', 'b'), 2);
                expectSuccess($parser, 'abc1', array('a', 'b', 'c'), 3);
                expectSuccess($parser, '12', array('1'), 1);
                expectSuccess($parser, 'a12', array('a'), 1);
                expectSuccess($parser, 'ab12', array('a', 'b'), 2);
                expectSuccess($parser, 'abc12', array('a', 'b', 'c'), 3);
                expectSuccess($parser, '123', array('1'), 1);
                expectSuccess($parser, 'a123', array('a'), 1);
                expectSuccess($parser, 'ab123', array('a', 'b'), 2);
                expectSuccess($parser, 'abc123', array('a', 'b', 'c'), 3);
            }
        );

        test(
            'times()',
            function () {
                $parser = char('a')->times(2);
                expectFailure($parser, '', 0, 'a expected');
                expectFailure($parser, 'a', 1, 'a expected');
                expectSuccess($parser, 'aa', array('a', 'a'));
                expectSuccess($parser, 'aaa', array('a', 'a'), 2);
            }
        );

        test(
            'repeat()',
            function () {
                $parser = char('a')->repeat(2, 3);
                expectFailure($parser, '', 0, 'a expected');
                expectFailure($parser, 'a', 1, 'a expected');
                expectSuccess($parser, 'aa', array('a', 'a'));
                expectSuccess($parser, 'aaa', array('a', 'a', 'a'));
                expectSuccess($parser, 'aaaa', array('a', 'a', 'a'), 3);
            }
        );

        test(
            'repeat() unbounded',
            function () {
                $input = array_fill(0, 99, 'a');
                $parser = char('a')->repeat(2, Parser::UNBOUNDED);
                expectSuccess($parser, implode($input), $input);
            }
        );

        test(
            'repeatGreedy()',
            function () {
                $parser = word()->repeatGreedy(digit(), 2, 4);
                expectFailure($parser, '', 0, 'letter or digit expected');
                expectFailure($parser, 'a', 1, 'letter or digit expected');
                expectFailure($parser, 'ab', 2, 'digit expected');
                expectFailure($parser, 'abc', 2, 'digit expected');
                expectFailure($parser, 'abcd', 2, 'digit expected');
                expectFailure($parser, 'abcde', 2, 'digit expected');
                expectFailure($parser, '1', 1, 'letter or digit expected');
                expectFailure($parser, 'a1', 2, 'digit expected');
                expectSuccess($parser, 'ab1', array('a', 'b'), 2);
                expectSuccess($parser, 'abc1', array('a', 'b', 'c'), 3);
                expectSuccess($parser, 'abcd1', array('a', 'b', 'c', 'd'), 4);
                expectFailure($parser, 'abcde1', 2, 'digit expected');
                expectFailure($parser, '12', 2, 'digit expected');
                expectSuccess($parser, 'a12', array('a', '1'), 2);
                expectSuccess($parser, 'ab12', array('a', 'b', '1'), 3);
                expectSuccess($parser, 'abc12', array('a', 'b', 'c', '1'), 4);
                expectSuccess($parser, 'abcd12', array('a', 'b', 'c', 'd'), 4);
                expectFailure($parser, 'abcde12', 2, 'digit expected');
                expectSuccess($parser, '123', array('1', '2'), 2);
                expectSuccess($parser, 'a123', array('a', '1', '2'), 3);
                expectSuccess($parser, 'ab123', array('a', 'b', '1', '2'), 4);
                expectSuccess($parser, 'abc123', array('a', 'b', 'c', '1'), 4);
                expectSuccess($parser, 'abcd123', array('a', 'b', 'c', 'd'), 4);
                expectFailure($parser, 'abcde123', 2, 'digit expected');
            }
        );

        test(
            'repeatGreedy() unbounded',
            function () {
                $inputLetter = array_fill(0, 66, 'a');
                $inputDigit = array_fill(0, 66, '1');
                $parser = word()->repeatGreedy(digit(), 2, Parser::UNBOUNDED);
                expectSuccess($parser, implode($inputLetter) . '1', $inputLetter, length($inputLetter));
                expectSuccess($parser, implode($inputDigit) . '1', $inputDigit, length($inputDigit));
            }
        );

        test(
            'repeatLazy()',
            function () {
                $parser = word()->repeatLazy(digit(), 2, 4);
                expectFailure($parser, '', 0, 'letter or digit expected');
                expectFailure($parser, 'a', 1, 'letter or digit expected');
                expectFailure($parser, 'ab', 2, 'digit expected');
                expectFailure($parser, 'abc', 3, 'digit expected');
                expectFailure($parser, 'abcd', 4, 'digit expected');
                expectFailure($parser, 'abcde', 4, 'digit expected');
                expectFailure($parser, '1', 1, 'letter or digit expected');
                expectFailure($parser, 'a1', 2, 'digit expected');
                expectSuccess($parser, 'ab1', array('a', 'b'), 2);
                expectSuccess($parser, 'abc1', array('a', 'b', 'c'), 3);
                expectSuccess($parser, 'abcd1', array('a', 'b', 'c', 'd'), 4);
                expectFailure($parser, 'abcde1', 4, 'digit expected');
                expectFailure($parser, '12', 2, 'digit expected');
                expectSuccess($parser, 'a12', array('a', '1'), 2);
                expectSuccess($parser, 'ab12', array('a', 'b'), 2);
                expectSuccess($parser, 'abc12', array('a', 'b', 'c'), 3);
                expectSuccess($parser, 'abcd12', array('a', 'b', 'c', 'd'), 4);
                expectFailure($parser, 'abcde12', 4, 'digit expected');
                expectSuccess($parser, '123', array('1', '2'), 2);
                expectSuccess($parser, 'a123', array('a', '1'), 2);
                expectSuccess($parser, 'ab123', array('a', 'b'), 2);
                expectSuccess($parser, 'abc123', array('a', 'b', 'c'), 3);
                expectSuccess($parser, 'abcd123', array('a', 'b', 'c', 'd'), 4);
                expectFailure($parser, 'abcde123', 4, 'digit expected');
            }
        );

        // TODO debug this test - goes into an infinite loop
//        test('repeatLazy() unbounded', function() {
//          $input = array_fill(0, 100000, 'a');
//          $parser = word()->repeatLazy(digit(), 2, Parser::UNBOUNDED);
//          expectSuccess($parser, implode($input) . '1111', $input, length($input));
//        });

        test(
            'separatedBy()',
            function () {
                $parser = char('a')->separatedBy(char('b'));
                expectFailure($parser, '', 0, 'a expected');
                expectSuccess($parser, 'a', array('a'));
                expectSuccess($parser, 'ab', array('a'), 1);
                expectSuccess($parser, 'aba', array('a', 'b', 'a'));
                expectSuccess($parser, 'abab', array('a', 'b', 'a'), 3);
                expectSuccess($parser, 'ababa', array('a', 'b', 'a', 'b', 'a'));
                expectSuccess($parser, 'ababab', array('a', 'b', 'a', 'b', 'a'), 5);
            }
        );

        test(
            'separatedBy() without separators',
            function () {
                $parser = char('a')->separatedBy(
                    char('b'),
                    false # $includeSeparators
                );
                expectFailure($parser, '', 0, 'a expected');
                expectSuccess($parser, 'a', array('a'));
                expectSuccess($parser, 'ab', array('a'), 1);
                expectSuccess($parser, 'aba', array('a', 'a'));
                expectSuccess($parser, 'abab', array('a', 'a'), 3);
                expectSuccess($parser, 'ababa', array('a', 'a', 'a'));
                expectSuccess($parser, 'ababab', array('a', 'a', 'a'), 5);
            }
        );

        test(
            'separatedBy() separator at end',
            function () {
                $parser = char('a')->separatedBy(
                    char('b'),
                    true, # $includeSeparators
                    true # $optionalSeparatorAtEnd
                );
                expectFailure($parser, '', 0, 'a expected');
                expectSuccess($parser, 'a', array('a'));
                expectSuccess($parser, 'ab', array('a', 'b'));
                expectSuccess($parser, 'aba', array('a', 'b', 'a'));
                expectSuccess($parser, 'abab', array('a', 'b', 'a', 'b'));
                expectSuccess($parser, 'ababa', array('a', 'b', 'a', 'b', 'a'));
                expectSuccess($parser, 'ababab', array('a', 'b', 'a', 'b', 'a', 'b'));
            }
        );

        test(
            'separatedBy() without separators & separator at end',
            function () {
                $parser = char('a')->separatedBy(
                    char('b'),
                    false, # $includeSeparators
                    true # $optionalSeparatorAtEnd
                );
                expectFailure($parser, '', 0, 'a expected');
                expectSuccess($parser, 'a', array('a'));
                expectSuccess($parser, 'ab', array('a'));
                expectSuccess($parser, 'aba', array('a', 'a'));
                expectSuccess($parser, 'abab', array('a', 'a'));
                expectSuccess($parser, 'ababa', array('a', 'a', 'a'));
                expectSuccess($parser, 'ababab', array('a', 'a', 'a'));
            }
        );

        test(
            'seq() of two',
            function () {
                $parser = char('a')->seq(char('b'));
                expectSuccess($parser, 'ab', array('a', 'b'));
                expectFailure($parser, '');
                expectFailure($parser, 'x');
                expectFailure($parser, 'a', 1);
                expectFailure($parser, 'ax', 1);
            }
        );

        test(
            'seq() of three',
            function () {
                $parser = char('a')->seq(char('b'))->seq(char('c'));
                expectSuccess($parser, 'abc', array('a', 'b', 'c'));
                expectFailure($parser, '');
                expectFailure($parser, 'x');
                expectFailure($parser, 'a', 1);
                expectFailure($parser, 'ax', 1);
                expectFailure($parser, 'ab', 2);
                expectFailure($parser, 'abx', 2);
            }
        );

        test(
            'star()',
            function () {
                $parser = char('a')->star();
                expectSuccess($parser, '', array());
                expectSuccess($parser, 'a', array('a'));
                expectSuccess($parser, 'aa', array('a', 'a'));
                expectSuccess($parser, 'aaa', array('a', 'a', 'a'));
            }
        );

        test(
            'starGreedy()',
            function () {
                $parser = word()->starGreedy(digit());
                expectFailure($parser, '', 0, 'digit expected');
                expectFailure($parser, 'a', 0, 'digit expected');
                expectFailure($parser, 'ab', 0, 'digit expected');
                expectSuccess($parser, '1', array(), 0);
                expectSuccess($parser, 'a1', array('a'), 1);
                expectSuccess($parser, 'ab1', array('a', 'b'), 2);
                expectSuccess($parser, 'abc1', array('a', 'b', 'c'), 3);
                expectSuccess($parser, '12', array('1'), 1);
                expectSuccess($parser, 'a12', array('a', '1'), 2);
                expectSuccess($parser, 'ab12', array('a', 'b', '1'), 3);
                expectSuccess($parser, 'abc12', array('a', 'b', 'c', '1'), 4);
                expectSuccess($parser, '123', array('1', '2'), 2);
                expectSuccess($parser, 'a123', array('a', '1', '2'), 3);
                expectSuccess($parser, 'ab123', array('a', 'b', '1', '2'), 4);
                expectSuccess($parser, 'abc123', array('a', 'b', 'c', '1', '2'), 5);
            }
        );

        test(
            'starLazy()',
            function () {
                $parser = word()->starLazy(digit());
                expectFailure($parser, '');
                expectFailure($parser, 'a', 1, 'digit expected');
                expectFailure($parser, 'ab', 2, 'digit expected');
                expectSuccess($parser, '1', array(), 0);
                expectSuccess($parser, 'a1', array('a'), 1);
                expectSuccess($parser, 'ab1', array('a', 'b'), 2);
                expectSuccess($parser, 'abc1', array('a', 'b', 'c'), 3);
                expectSuccess($parser, '12', array(), 0);
                expectSuccess($parser, 'a12', array('a'), 1);
                expectSuccess($parser, 'ab12', array('a', 'b'), 2);
                expectSuccess($parser, 'abc12', array('a', 'b', 'c'), 3);
                expectSuccess($parser, '123', array(), 0);
                expectSuccess($parser, 'a123', array('a'), 1);
                expectSuccess($parser, 'ab123', array('a', 'b'), 2);
                expectSuccess($parser, 'abc123', array('a', 'b', 'c'), 3);
            }
        );

        test(
            'trim()',
            function () {
                $parser = char('a')->trim();
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, ' a', 'a');
                expectSuccess($parser, 'a ', 'a');
                expectSuccess($parser, ' a ', 'a');
                expectSuccess($parser, '  a', 'a');
                expectSuccess($parser, 'a  ', 'a');
                expectSuccess($parser, '  a  ', 'a');
                expectFailure($parser, '', 0, 'a expected');
                expectFailure($parser, 'b', 0, 'a expected');
                expectFailure($parser, ' b', 1, 'a expected');
                expectFailure($parser, '  b', 2, 'a expected');
            }
        );

        test(
            'trim() custom',
            function () {
                $parser = char('a')->trim(char('*'));
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, '*a', 'a');
                expectSuccess($parser, 'a*', 'a');
                expectSuccess($parser, '*a*', 'a');
                expectSuccess($parser, '**a', 'a');
                expectSuccess($parser, 'a**', 'a');
                expectSuccess($parser, '**a**', 'a');
                expectFailure($parser, '', 0, 'a expected');
                expectFailure($parser, 'b', 0, 'a expected');
                expectFailure($parser, '*b', 1, 'a expected');
                expectFailure($parser, '**b', 2, 'a expected');
            }
        );

        test(
            'undefined()',
            function () {
                $parser = undefined();
                expectFailure($parser, '', 0, 'undefined parser');
                expectFailure($parser, 'a', 0, 'undefined parser');
                $parser->set(char('a'));
                expectSuccess($parser, 'a', 'a');
            }
        );

        test(
            'setable()',
            function () {
                $parser = char('a')->setable();
                expectSuccess($parser, 'a', 'a');
                expectFailure($parser, 'b', 0, 'a expected');
                expectFailure($parser, '');
            }
        );
    }
);

group(
    'characters',
    function () {
        test(
            'char()',
            function () {
                $parser = char('a');
                expectSuccess($parser, 'a', 'a');
                expectFailure($parser, 'b', 0, 'a expected');
                expectFailure($parser, '');
            }
        );

        test(
            'digit()',
            function () {
                $parser = digit();
                expectSuccess($parser, '1', '1');
                expectSuccess($parser, '9', '9');
                expectFailure($parser, 'a', 0, 'digit expected');
                expectFailure($parser, '');
            }
        );

        test(
            'letter()',
            function () {
                $parser = letter();
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'X', 'X');
                expectFailure($parser, '0', 0, 'letter expected');
                expectFailure($parser, '');
            }
        );

        test(
            'lowercase()',
            function () {
                $parser = lowercase();
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'z', 'z');
                expectFailure($parser, 'A', 0, 'lowercase letter expected');
                expectFailure($parser, '0', 0, 'lowercase letter expected');
                expectFailure($parser, '');
            }
        );

        test(
            'pattern() with single',
            function () {
                $parser = pattern('abc');
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'b', 'b');
                expectSuccess($parser, 'c', 'c');
                expectFailure($parser, 'd', 0, '[abc] expected');
                expectFailure($parser, '');
            }
        );

        test(
            'pattern() with range',
            function () {
                $parser = pattern('a-c');
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'b', 'b');
                expectSuccess($parser, 'c', 'c');
                expectFailure($parser, 'd', 0, '[a-c] expected');
                expectFailure($parser, '');
            }
        );

        test(
            'pattern() with composed',
            function () {
                $parser = pattern('ac-df-');
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'c', 'c');
                expectSuccess($parser, 'd', 'd');
                expectSuccess($parser, 'f', 'f');
                expectSuccess($parser, '-', '-');
                expectFailure($parser, 'b', 0, '[ac-df-] expected');
                expectFailure($parser, 'e', 0, '[ac-df-] expected');
                expectFailure($parser, 'g', 0, '[ac-df-] expected');
                expectFailure($parser, '');
            }
        );

        test(
            'pattern() with negated single',
            function () {
                $parser = pattern('^a');
                expectSuccess($parser, 'b', 'b');
                expectFailure($parser, 'a', 0, '[^a] expected');
                expectFailure($parser, '');
            }
        );

        test(
            'pattern() with negated range',
            function () {
                $parser = pattern('^a-c');
                expectSuccess($parser, 'd', 'd');
                expectFailure($parser, 'a', 0, '[^a-c] expected');
                expectFailure($parser, 'b', 0, '[^a-c] expected');
                expectFailure($parser, 'c', 0, '[^a-c] expected');
                expectFailure($parser, '');
            }
        );

        test(
            'range()',
            function () {
                $parser = range('e', 'o');
                expectSuccess($parser, 'e', 'e');
                expectSuccess($parser, 'i', 'i');
                expectSuccess($parser, 'o', 'o');
                expectFailure($parser, 'p', 0, 'e..o expected');
                expectFailure($parser, 'd', 0, 'e..o expected');
                expectFailure($parser, '');
            }
        );

        test(
            'uppercase()',
            function () {
                $parser = uppercase();
                expectSuccess($parser, 'A', 'A');
                expectSuccess($parser, 'Z', 'Z');
                expectFailure($parser, 'a', 0, 'uppercase letter expected');
                expectFailure($parser, '0', 0, 'uppercase letter expected');
                expectFailure($parser, '');
            }
        );

        test(
            'whitespace()',
            function () {
                $parser = whitespace();
                expectSuccess($parser, ' ', ' ');
                expectSuccess($parser, "\t", "\t");
                expectSuccess($parser, "\r", "\r");
                expectSuccess($parser, "\f", "\f");
                expectFailure($parser, 'z', 0, 'whitespace expected');
                expectFailure($parser, '');
            }
        );

        test(
            'word()',
            function () {
                $parser = word();
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'z', 'z');
                expectSuccess($parser, 'A', 'A');
                expectSuccess($parser, 'Z', 'Z');
                expectSuccess($parser, '0', '0');
                expectSuccess($parser, '9', '9');
                expectSuccess($parser, '_', '_');
                expectFailure($parser, '-', 0, 'letter or digit expected');
                expectFailure($parser, '');
            }
        );
    }
);

group(
    'predicates',
    function () {
        test(
            'any()',
            function () {
                $parser = any();
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'b', 'b');
                expectFailure($parser, '', 0, 'input expected');
            }
        );

        test(
            'anyIn()',
            function () {
                $parser = anyIn(array('a', 'b'));
                expectSuccess($parser, 'a', 'a');
                expectSuccess($parser, 'b', 'b');
                expectFailure($parser, 'c');
                expectFailure($parser, '');
            }
        );

        test(
            'string()',
            function () {
                $parser = string('foo');
                expectSuccess($parser, 'foo', 'foo');
                expectFailure($parser, '');
                expectFailure($parser, 'f');
                expectFailure($parser, 'fo');
                expectFailure($parser, 'Foo');
            }
        );

        test(
            'stringIgnoreCase()',
            function () {
                $parser = stringIgnoreCase('foo');
                expectSuccess($parser, 'foo', 'foo');
                expectSuccess($parser, 'FOO', 'FOO');
                expectSuccess($parser, 'fOo', 'fOo');
                expectFailure($parser, '');
                expectFailure($parser, 'f');
                expectFailure($parser, 'Fo');
            }
        );
    }
);

group(
    'token',
    function () {
        $parser = any()
            ->map(
                function ($value) {
                    return ord($value);
                }
            )
            ->token()
            ->star();

        $buffer = "1\r12\r\n123\n1234";
        $result = $parser->parse($buffer)->value;

        test(
            'value',
            function () use ($result) {
                check(
                    array_map(
                        function ($token) {
                            return $token->value;
                        },
                        $result
                    ),
                    array(49, 13, 49, 50, 13, 10, 49, 50, 51, 10, 49, 50, 51, 52)
                );
            }
        );

        test(
            'buffer',
            function () use ($result, $buffer) {
                check(
                    array_map(
                        function ($token) {
                            return $token->buffer->string;
                        },
                        $result
                    ),
                    array_fill(0, length($buffer), $buffer)
                );
            }
        );

        test(
            'start',
            function () use ($result) {
                check(
                    array_map(
                        function ($token) {
                            return $token->start;
                        },
                        $result
                    ),
                    array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13)
                );
            }
        );

        test(
            'stop',
            function () use ($result) {
                check(
                    array_map(
                        function ($token) {
                            return $token->stop;
                        },
                        $result
                    ),
                    array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14)
                );
            }
        );

        test(
            'length',
            function () use ($result) {
                check(
                    array_map(
                        function ($token) {
                            return $token->length;
                        },
                        $result
                    ),
                    array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
                );
            }
        );

        test(
            'line',
            function () use ($result) {
                check(
                    array_map(
                        function ($token) {
                            return $token->line;
                        },
                        $result
                    ),
                    array(1, 1, 2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4)
                );
            }
        );

        test(
            'column',
            function () use ($result) {
                check(
                    array_map(
                        function (Token $token) {
                            return $token->column;
                        },
                        $result
                    ),
                    array(1, 2, 1, 2, 3, 4, 1, 2, 3, 4, 1, 2, 3, 4)
                );
            }
        );

        test(
            'input',
            function () use ($result) {
                check(
                    array_map(
                        function ($token) {
                            return $token->input;
                        },
                        $result
                    ),
                    array('1', "\r", '1', '2', "\r", "\n", '1', '2', '3', "\n", '1', '2', '3', '4')
                );
            }
        );

        test(
            'unique',
            function () use ($result) {
                $dupes = 0;
                foreach ($result as $ai => $a) {
                    foreach ($result as $bi => $b) {
                        if ($ai !== $bi && $a === $b) {
                            $dupes += 1;
                        }
                    }
                }
                check($dupes, 0, 'result contains no duplicate values');
            }
        );
    }
);

group(
    'parsing',
    function () {
        test(
            'parse()',
            function () {
                $parser = char('a');
                check($parser->parse('a')->isSuccess, true);
                check($parser->parse('b')->isSuccess, false);
            }
        );

        test(
            'accept()',
            function () {
                $parser = char('a');
                check($parser->accept(Buffer::fromUTF8('a')), true);
                check($parser->accept(Buffer::fromUTF8('b')), false);
            }
        );

        test(
            'matches()',
            function () {
                $parser = digit()->seq(digit())->flatten();
                check($parser->matches(Buffer::fromUTF8('a123b45')), array('12', '23', '45'));
            }
        );

        test(
            'matchesSkipping()',
            function () {
                $parser = digit()->seq(digit())->flatten();
                check($parser->matchesSkipping(Buffer::fromUTF8('a123b45')), array('12', '45'));
            }
        );
    }
);

group(
    'examples',
    function () {
        $IDENTIFIER = letter()->seq(word()->star())->flatten();

        $NUMBER = char('-')->optional()->seq(digit()->plus())
            ->seq(char('.')->seq(digit()->plus())->optional())->flatten();

        $STRING = char('"')
            ->seq(char('"')->neg()->star())->seq(char('"'))->flatten();

        $KEYWORD = string('return')
            ->seq(whitespace()->plus()->flatten())
            ->seq($IDENTIFIER->or_($NUMBER)->or_($STRING))
            ->map(
                function ($list) {
                    return $list[count($list) - 1];
                }
            );

        $JAVADOC = string('/**')
            ->seq(string('*/')->neg()->star())
            ->seq(string('*/'))
            ->flatten();

        test(
            'valid identifier',
            function () use ($IDENTIFIER) {
                expectSuccess($IDENTIFIER, 'a', 'a');
                expectSuccess($IDENTIFIER, 'a1', 'a1');
                expectSuccess($IDENTIFIER, 'a12', 'a12');
                expectSuccess($IDENTIFIER, 'ab', 'ab');
                expectSuccess($IDENTIFIER, 'a1b', 'a1b');
            }
        );

        test(
            'incomplete identifier',
            function () use ($IDENTIFIER) {
                expectSuccess($IDENTIFIER, 'a=', 'a', 1);
                expectSuccess($IDENTIFIER, 'a1-', 'a1', 2);
                expectSuccess($IDENTIFIER, 'a12+', 'a12', 3);
                expectSuccess($IDENTIFIER, 'ab ', 'ab', 2);
            }
        );

        test(
            'invalid identifier',
            function () use ($IDENTIFIER) {
                expectFailure($IDENTIFIER, '', 0, 'letter expected');
                expectFailure($IDENTIFIER, '1', 0, 'letter expected');
                expectFailure($IDENTIFIER, '1a', 0, 'letter expected');
            }
        );

        test(
            'positive number',
            function () use ($NUMBER) {
                expectSuccess($NUMBER, '1', '1');
                expectSuccess($NUMBER, '12', '12');
                expectSuccess($NUMBER, '12.3', '12.3');
                expectSuccess($NUMBER, '12.34', '12.34');
            }
        );

        test(
            'negative number',
            function () use ($NUMBER) {
                expectSuccess($NUMBER, '-1', '-1');
                expectSuccess($NUMBER, '-12', '-12');
                expectSuccess($NUMBER, '-12.3', '-12.3');
                expectSuccess($NUMBER, '-12.34', '-12.34');
            }
        );

        test(
            'incomplete number',
            function () use ($NUMBER) {
                expectSuccess($NUMBER, '1..', '1', 1);
                expectSuccess($NUMBER, '12-', '12', 2);
                expectSuccess($NUMBER, '12.3.', '12.3', 4);
                expectSuccess($NUMBER, '12.34.', '12.34', 5);
            }
        );

        test(
            'invalid number',
            function () use ($NUMBER) {
                expectFailure($NUMBER, '', 0, 'digit expected');
                expectFailure($NUMBER, '-', 1, 'digit expected');
                expectFailure($NUMBER, '-x', 1, 'digit expected');
                expectFailure($NUMBER, '.', 0, 'digit expected');
                expectFailure($NUMBER, '.1', 0, 'digit expected');
            }
        );

        test(
            'valid string',
            function () use ($STRING) {
                expectSuccess($STRING, '""', '""');
                expectSuccess($STRING, '"a"', '"a"');
                expectSuccess($STRING, '"ab"', '"ab"');
                expectSuccess($STRING, '"abc"', '"abc"');
            }
        );

        test(
            'incomplete string',
            function () use ($STRING) {
                expectSuccess($STRING, '""x', '""', 2);
                expectSuccess($STRING, '"a"x', '"a"', 3);
                expectSuccess($STRING, '"ab"x', '"ab"', 4);
                expectSuccess($STRING, '"abc"x', '"abc"', 5);
            }
        );

        test(
            'invalid string',
            function () use ($STRING) {
                expectFailure($STRING, '"', 1, '" expected');
                expectFailure($STRING, '"a', 2, '" expected');
                expectFailure($STRING, '"ab', 3, '" expected');
                expectFailure($STRING, 'a"', 0, '" expected');
                expectFailure($STRING, 'ab"', 0, '" expected');
            }
        );

        test(
            'return statement',
            function () use ($KEYWORD) {
                expectSuccess($KEYWORD, 'return f', 'f');
                expectSuccess($KEYWORD, 'return  f', 'f');
                expectSuccess($KEYWORD, 'return foo', 'foo');
                expectSuccess($KEYWORD, 'return    foo', 'foo');
                expectSuccess($KEYWORD, 'return 1', '1');
                expectSuccess($KEYWORD, 'return  1', '1');
                expectSuccess($KEYWORD, 'return -2.3', '-2.3');
                expectSuccess($KEYWORD, 'return    -2.3', '-2.3');
                expectSuccess($KEYWORD, 'return "a"', '"a"');
                expectSuccess($KEYWORD, 'return  "a"', '"a"');
            }
        );

        test(
            'invalid statement',
            function () use ($KEYWORD) {
                expectFailure($KEYWORD, 'retur f', 0, 'return expected');
                expectFailure($KEYWORD, 'return1', 6, 'whitespace expected');
                expectFailure($KEYWORD, 'return  _', 8, '" expected');
            }
        );

        test(
            'javadoc',
            function () use ($JAVADOC) {
                expectSuccess($JAVADOC, '/** foo */', '/** foo */');
                expectSuccess($JAVADOC, '/** * * */', '/** * * */');
            }
        );
    }
);

group(
    'regressions',
    function () {
        test(
            'flatten()->trim()',
            function () {
                $parser = word()->plus()->flatten()->trim();
                expectSuccess($parser, 'ab1', 'ab1');
                expectSuccess($parser, ' ab1 ', 'ab1');
                expectSuccess($parser, '  ab1  ', 'ab1');
            }
        );

        test(
            'trim()->flatten()',
            function () {
                $parser = word()->plus()->trim()->flatten();
                expectSuccess($parser, 'ab1', 'ab1');
                expectSuccess($parser, ' ab1 ', ' ab1 ');
                expectSuccess($parser, '  ab1  ', '  ab1  ');
            }
        );
    }
);

group(
    'reflection',
    function () {
        test(
            'iterator single',
            function () {
                $parser1 = lowercase();
                $parsers = iterator_to_array(allParser($parser1));
                check($parsers, array($parser1));
            }
        );

        test(
            'iterator nested',
            function () {
                $parser3 = lowercase();
                $parser2 = $parser3->star();
                $parser1 = $parser2->flatten();
                $parsers = iterator_to_array(allParser($parser1));
                check($parsers, array($parser1, $parser2, $parser3));
            }
        );

        test(
            'iterator branched',
            function () {
                $parser3 = lowercase();
                $parser2 = uppercase();
                $parser1 = $parser2->seq($parser3);
                $parsers = iterator_to_array(allParser($parser1));
                check($parsers, array($parser1, $parser3, $parser2));
            }
        );

        test(
            'iterator duplicated',
            function () {
                $parser2 = uppercase();
                $parser1 = $parser2->seq($parser2);
                $parsers = iterator_to_array(allParser($parser1));
                check($parsers, array($parser1, $parser2));
            }
        );

        test(
            'iterator knot',
            function () {
                $parser1 = undefined();
                $parser1->set($parser1);
                $parsers = iterator_to_array(allParser($parser1));
                check($parsers, array($parser1));
            }
        );

        test(
            'iterator looping',
            function () {
                $parser1 = undefined();
                $parser2 = undefined();
                $parser3 = undefined();
                $parser1->set($parser2);
                $parser2->set($parser3);
                $parser3->set($parser1);
                $parsers = iterator_to_array(allParser($parser1));
                check($parsers, array($parser1, $parser2, $parser3));
            }
        );

        test(
            'iterator basic',
            function () {
                $lower = lowercase();
                $iterator = allParser($lower)->getIterator();
                check($iterator->current(), null);
                $iterator->rewind();
                check($iterator->valid(), true);
                check($iterator->current(), $lower);
                check($iterator->current(), $lower);
                $iterator->next();
                check($iterator->valid(), false);
                check($iterator->current(), null);
                $iterator->next();
                check($iterator->valid(), false);
            }
        );

        test(
            'transform copy',
            function () {
                $lower = lowercase();
                $parser = $lower->setable();
                $transformed = transformParser(
                    $parser,
                    function ($parser) {
                        return $parser;
                    }
                );
                check($transformed->equals($parser), true);
            }
        );

// TODO debug this test, which goes into an infinite loop
//    test('transform root', function () {
//      $input = lowercase();
//      $source = lowercase();
//      $target = uppercase();
//      $output = transformParser($input, function (Parser $parser) use ($source, $target) {
//        return $source->equals($parser) ? $target : $parser;
//      });
//      check($input->match($output), false);
//      check($output->match($target), true);
//    });

        test(
            'transform delegate',
            function () {
                $input = lowercase()->setable();
                $source = lowercase();
                $target = uppercase();
                $output = transformParser(
                    $input,
                    function ($parser) use ($source, $target) {
                        return $source->equals($parser) ? $target : $parser;
                    }
                );
                check($input->equals($output), false);
                check($output->equals($target->setable()), true);
            }
        );

        test(
            'transform double reference',
            function () {
                $lower = lowercase();
                $input = $lower->seq($lower);
                $source = lowercase();
                $target = uppercase();
                $output = transformParser(
                    $input,
                    function ($parser) use ($source, $target) {
                        return $source->equals($parser) ? $target : $parser;
                    }
                );
                check($input->equals($output), false);
                check($output->equals($target->seq($target)), true);
                check($output->children[0], $output->children[count($output->children) - 1]);
            }
        );

        test(
            'remove setables',
            function () {
                $input = lowercase()->setable();
                $output = removeSetables($input);
                check($output->equals(lowercase()), true);
            }
        );

        test(
            'remove nested setables',
            function () {
                $input = lowercase()->setable()->star();
                $output = removeSetables($input);
                check($output->equals(lowercase()->star()), true);
            }
        );

        test(
            'remove double setables',
            function () {
                $input = lowercase()->setable()->setable();
                $output = removeSetables($input);
                check($output->equals(lowercase()), true);
            }
        );

        group(
            'copying and matching',
            function () {
                $verify = function ($title, Parser $parser) {
                    test(
                        $title,
                        function () use ($parser) {
                            $copy = $parser->copy();
                            check(get_class($copy), get_class($parser));
                            check($copy->children, $parser->children, 'same children');
                            check($copy->equals($copy), true);
                            check($parser->equals($parser), true);
                            check($copy !== $parser, true);
                            check($copy->equals($parser), true);
                            check($parser->equals($copy), true);
                        }
                    );
                };

                $verify('and()', digit()->and_());
                $verify('char()', char('a'));
                $verify('digit()', digit());
                $verify('end()', digit()->end_());
                $verify('epsilon()', epsilon());
                $verify('failure()', failure());
                $verify('flatten()', digit()->flatten());
                $verify(
                    'map()',
                    digit()->map(
                        function ($a) {
                            return $a;
                        }
                    )
                );
                $verify('not()', digit()->not_());
                $verify('optional()', digit()->optional());
                $verify('or()', digit()->or_(word()));
                $verify('plus()', digit()->plus());
                $verify('plusGreedy()', digit()->plusGreedy(word()));
                $verify('plusLazy()', digit()->plusLazy(word()));
                $verify('repeat()', digit()->repeat(2, 3));
                $verify('repeatGreedy()', digit()->repeatGreedy(word(), 2, 3));
                $verify('repeatLazy()', digit()->repeatLazy(word(), 2, 3));
                $verify('seq()', digit()->seq(word()));
                $verify('setable()', digit()->setable());
                $verify('star()', digit()->star());
                $verify('starGreedy()', digit()->starGreedy(word()));
                $verify('starLazy()', digit()->starLazy(word()));
                $verify('string()', string('ab'));
                $verify('times()', digit()->times(2));
                $verify('token()', digit()->token());
                $verify('trim()', digit()->trim());
                $verify('undefined()', undefined());
            }
        );
    }
);

group(
    'composite',
    function () {
        test(
            'start',
            function () {
                $parser = new PluggableCompositeParser(function (CompositeParser $self) {
                    return $self->def('start', char('a'));
                });
                expectSuccess($parser, 'a', 'a', 1);
                expectFailure($parser, 'b', 0, 'a expected');
                expectFailure($parser, '');
            }
        );

        test(
            'circular',
            function () {
                $parser = new PluggableCompositeParser(function (CompositeParser $self) {
                    $self->def('start', $self->ref('loop')->or_(char('b')));
                    $self->def('loop', char('a')->seq($self->ref('start')));
                });
                check($parser->accept(Buffer::fromUTF8('b')), true);
                check($parser->accept(Buffer::fromUTF8('ab')), true);
                check($parser->accept(Buffer::fromUTF8('aab')), true);
                check($parser->accept(Buffer::fromUTF8('aaab')), true);
            }
        );

        test(
            'redefine parser',
            function () {
                $parser = new PluggableCompositeParser(function (CompositeParser $self) {
                    $self->def('start', char('b'));
                    $self->redef('start', char('a'));
                });

                expectSuccess($parser, 'a', 'a', 1);
                expectFailure($parser, 'b', 0, 'a expected');
                expectFailure($parser, '');
            }
        );

        test(
            'redefine function',
            function () {
                $parser = new PluggableCompositeParser(function (CompositeParser $self) {
                    $b = char('b');
                    $self->def('start', $b);
                    $self->redef(
                        'start',
                        function ($old) use ($b) {
                            check($b, $old);
                            return char('a');
                        }
                    );
                });

                expectSuccess($parser, 'a', 'a', 1);
                expectFailure($parser, 'b', 0, 'a expected');
                expectFailure($parser, '');
            }
        );

        test(
            'define completed',
            function () {
                $parser = new PluggableCompositeParser(function (CompositeParser $self) {
                    $self->def('start', char('a'));
                });

                throws(
                    'petitparser\CompletedParserError',
                    'def()',
                    function () use ($parser) {
                        $parser->def('other', char('b'));
                    }
                );

                throws(
                    'petitparser\CompletedParserError',
                    'redef()',
                    function () use ($parser) {
                        $parser->redef('start', char('b'));
                    }
                );

                throws(
                    'petitparser\CompletedParserError',
                    'action()',
                    function () use ($parser) {
                        $parser->action(
                            'start',
                            function ($each) {
                                return $each;
                            }
                        );
                    }
                );
            }
        );

        test(
            'reference completed',
            function () {
                $parsers = array(
                    'start' => char('a'),
                    'for_b' => char('b'),
                    'for_c' => char('c'),
                );
                $parser = new PluggableCompositeParser(function (CompositeParser $self) use ($parsers) {
                    foreach (array_keys($parsers) as $key) {
                        $self->def($key, $parsers[$key]);
                    }
                });
                foreach (array_keys($parsers) as $key) {
                    check($parsers[$key], $parser->ref($key));
                }
            }
        );

        test(
            'reference unknown',
            function () {
                $parser = new PluggableCompositeParser(function (CompositeParser $self) {
                    $self->def('start', char('a'));
                });
                throws(
                    'petitparser\UndefinedProductionError',
                    'ref()',
                    function () use ($parser) {
                        $parser->ref('star1');
                    }
                );
            }
        );

        test(
            'duplicated start',
            function () {
                new PluggableCompositeParser(function (CompositeParser $self) {
                    $self->def('start', char('a'));
                    throws(
                        'petitparser\RedefinedProductionError',
                        'def()',
                        function () use ($self) {
                            $self->def('start', char('b'));
                        }
                    );
                });
            }
        );

        test(
            'undefined start',
            function () {
                throws(
                    'petitparser\UndefinedProductionError',
                    'initialize()',
                    function () {
                        return new PluggableCompositeParser(function ($self) {
                        });
                    }
                );
            }
        );

        test(
            'undefined redef',
            function () {
                new PluggableCompositeParser(function (CompositeParser $self) {
                    $self->def('start', char('a'));
                    throws(
                        'petitparser\UndefinedProductionError',
                        'redef()',
                        function () use ($self) {
                            $self->redef('star1', char('b'));
                        }
                    );
                });
            }
        );

        test(
            'example (lambda)',
            function () {
                $parser = new PluggableCompositeParser(function (CompositeParser $self) {
                    $self->def('start', $self->ref('expression')->end_());
                    $self->def('variable', letter()->seq(word()->star())->flatten()->trim());
                    $self->def(
                        'expression',
                        $self->ref('variable')
                            ->or_($self->ref('abstraction'))
                            ->or_($self->ref('application'))
                    );
                    $self->def(
                        'abstraction',
                        char('\\')->trim()
                            ->seq($self->ref('variable'))
                            ->seq(char('.')->trim())
                            ->seq($self->ref('expression'))
                    );
                    $self->def(
                        'application',
                        char('(')->trim()
                            ->seq($self->ref('expression'))
                            ->seq($self->ref('expression'))
                            ->seq(char(')')->trim())
                    );
                });
                check($parser->accept(Buffer::fromUTF8('x')), true);
                check($parser->accept(Buffer::fromUTF8('xy')), true);
                check($parser->accept(Buffer::fromUTF8('x12')), true);
                check($parser->accept(Buffer::fromUTF8("\\x.y")), true);
                check($parser->accept(Buffer::fromUTF8("\\x.\\y.z")), true);
                check($parser->accept(Buffer::fromUTF8('(x x)')), true);
                check($parser->accept(Buffer::fromUTF8('(x y)')), true);
                check($parser->accept(Buffer::fromUTF8('(x (y z))')), true);
                check($parser->accept(Buffer::fromUTF8('((x y) z)')), true);
            }
        );

        test(
            'example (expression)',
            function () {
                $parser = new PluggableCompositeParser(function (CompositeParser $self) {
                    $self->def('start', $self->ref('terms')->end_());
                    $self->def(
                        'terms',
                        $self->ref('addition')
                            ->or_($self->ref('factors'))
                    );
                    $self->def(
                        'addition',
                        $self->ref('factors')
                            ->separatedBy(char('+')->or_(char('-'))->trim())
                    );
                    $self->def(
                        'factors',
                        $self->ref('multiplication')
                            ->or_($self->ref('power'))
                    );
                    $self->def(
                        'multiplication',
                        $self->ref('power')
                            ->separatedBy(char('*')->or_(char('/'))->trim())
                    );
                    $self->def(
                        'power',
                        $self->ref('primary')
                            ->separatedBy(char('^')->trim())
                    );
                    $self->def(
                        'primary',
                        $self->ref('number')
                            ->or_($self->ref('parentheses'))
                    );
                    $self->def(
                        'number',
                        char('-')->optional()
                            ->seq(digit()->plus())
                            ->seq(char('.')->seq(digit()->plus())->optional())
                            ->flatten()->trim()
                    );
                    $self->def(
                        'parentheses',
                        char('(')->trim()
                            ->seq($self->ref('terms'))
                            ->seq(char(')')->trim())
                    );
                });
                check($parser->accept(Buffer::fromISO('1')), true);
                check($parser->accept(Buffer::fromISO('12')), true);
                check($parser->accept(Buffer::fromISO('1.23')), true);
                check($parser->accept(Buffer::fromISO('-12.3')), true);
                check($parser->accept(Buffer::fromISO('1 + 2')), true);
                check($parser->accept(Buffer::fromISO('1 + 2 + 3')), true);
                check($parser->accept(Buffer::fromISO('1 - 2')), true);
                check($parser->accept(Buffer::fromISO('1 - 2 - 3')), true);
                check($parser->accept(Buffer::fromISO('1 * 2')), true);
                check($parser->accept(Buffer::fromISO('1 * 2 * 3')), true);
                check($parser->accept(Buffer::fromISO('1 / 2')), true);
                check($parser->accept(Buffer::fromISO('1 / 2 / 3')), true);
                check($parser->accept(Buffer::fromISO('1 ^ 2')), true);
                check($parser->accept(Buffer::fromISO('1 ^ 2 ^ 3')), true);
                check($parser->accept(Buffer::fromISO('1 + (2 * 3)')), true);
                check($parser->accept(Buffer::fromISO('(1 + 2) * 3')), true);
            }
        );
    }
);

group(
    'expression',
    function () {
        $root = failure()->setable();

        $builder = new ExpressionBuilder();

        $builder->group(
            function (ExpressionGroup $group) use ($root) {
                $group->primitive(
                    char('(')->trim()
                        ->seq($root)
                        ->seq(char(')')->trim())
                        ->pick(1)
                );

                $group->primitive(
                    char('(')->trim()
                        ->seq($root)
                        ->seq(char(')')->trim())
                        ->pick(1)
                );

                $group->primitive(
                    digit()->plus()
                        ->seq(char('.')->seq(digit()->plus())->optional())
                        ->flatten()->trim()->map('floatval')
                );
            }
        );

        $builder->group()->prefix(
            char('-')->trim(),
            function ($op, $a) {
                return - $a;
            }
        );

        $builder->group()->prefix(
            char('-')->trim(),
            function ($op, $a) {
                return - $a;
            }
        );

        $builder->group(
            function (ExpressionGroup $group) {
                $group->postfix(
                    string('++')->trim(),
                    function ($a, $op) {
                        return $a + 1;
                    }
                );

                $group->postfix(
                    string('--')->trim(),
                    function ($a, $op) {
                        return $a - 1;
                    }
                );
            }
        );

        $builder->group()->right(
            char('^')->trim(),
            function ($a, $op, $b) {
                return pow($a, $b);
            }
        );

        $builder->group(
            function (ExpressionGroup $group) {
                $group->left(
                    char('*')->trim(),
                    function ($a, $op, $b) {
                        return $a * $b;
                    }
                );

                $group->left(
                    char('/')->trim(),
                    function ($a, $op, $b) {
                        return $a / $b;
                    }
                );
            }
        );

        $builder->group(
            function (ExpressionGroup $group) {
                $group->left(
                    char('+')->trim(),
                    function ($a, $op, $b) {
                        return $a + $b;
                    }
                );

                $group->left(
                    char('-')->trim(),
                    function ($a, $op, $b) {
                        return $a - $b;
                    }
                );
            }
        );

        $root->set($builder->build());

        $parser = $root->end_();

        test(
            'number',
            function () use ($parser) {
                checkNum($parser->parse('0')->value, 0.0);
                checkNum($parser->parse('0.0')->value, 0.0);
                checkNum($parser->parse('1')->value, 1.0);
                checkNum($parser->parse('1.2')->value, 1.2);
                checkNum($parser->parse('34')->value, 34);
                checkNum($parser->parse('34.7')->value, 34.7);
                checkNum($parser->parse('56.78')->value, 56.78);
            }
        );

        test(
            'negative number',
            function () use ($parser) {
                checkNum($parser->parse('-1')->value, - 1.0);
                checkNum($parser->parse('-1.2')->value, - 1.2);
            }
        );

        test(
            'add',
            function () use ($parser) {
                checkNum($parser->parse('1 + 2')->value, 3.0);
                checkNum($parser->parse('2 + 1')->value, 3.0);
                checkNum($parser->parse('1 + 2.3')->value, 3.3);
                checkNum($parser->parse('2.3 + 1')->value, 3.3);
                checkNum($parser->parse('1 + -2')->value, - 1.0);
                checkNum($parser->parse('-2 + 1')->value, - 1.0);
            }
        );

        test(
            'add many',
            function () use ($parser) {
                checkNum($parser->parse('1')->value, 1.0);
                checkNum($parser->parse('1 + 2')->value, 3.0);
                checkNum($parser->parse('1 + 2 + 3')->value, 6.0);
                checkNum($parser->parse('1 + 2 + 3 + 4')->value, 10.0);
                checkNum($parser->parse('1 + 2 + 3 + 4 + 5')->value, 15.0);
            }
        );

        test(
            'sub',
            function () use ($parser) {
                checkNum($parser->parse('1 - 2')->value, - 1);
                checkNum($parser->parse('1.2 - 1.2')->value, 0);
                checkNum($parser->parse('1 - -2')->value, 3);
                checkNum($parser->parse('-1 - -2')->value, 1);
            }
        );
        
        test(
            'sub many',
            function () use ($parser) {
                checkNum($parser->parse('1')->value, 1);
                checkNum($parser->parse('1 - 2')->value, - 1);
                checkNum($parser->parse('1 - 2 - 3')->value, - 4);
                checkNum($parser->parse('1 - 2 - 3 - 4')->value, - 8);
                checkNum($parser->parse('1 - 2 - 3 - 4 - 5')->value, - 13);
            }
        );

        test(
            'mul',
            function () use ($parser) {
                checkNum($parser->parse('2 * 3')->value, 6);
                checkNum($parser->parse('2 * -4')->value, - 8);
            }
        );

        test(
            'mul many',
            function () use ($parser) {
                checkNum($parser->parse('1 * 2')->value, 2);
                checkNum($parser->parse('1 * 2 * 3')->value, 6);
                checkNum($parser->parse('1 * 2 * 3 * 4')->value, 24);
                checkNum($parser->parse('1 * 2 * 3 * 4 * 5')->value, 120);
            }
        );

        test(
            'div',
            function () use ($parser) {
                checkNum($parser->parse('12 / 3')->value, 4);
                checkNum($parser->parse('-16 / -4')->value, 4);
            }
        );

        test(
            'div many',
            function () use ($parser) {
                checkNum($parser->parse('100 / 2')->value, 50);
                checkNum($parser->parse('100 / 2 / 2')->value, 25);
                checkNum($parser->parse('100 / 2 / 2 / 5')->value, 5);
                checkNum($parser->parse('100 / 2 / 2 / 5 / 5')->value, 1);
            }
        );

        test(
            'pow',
            function () use ($parser) {
                checkNum($parser->parse('2 ^ 3')->value, 8);
                checkNum($parser->parse('-2 ^ 3')->value, -8);
                checkNum($parser->parse('-2 ^ -3')->value, -0.125);
            }
        );

        test(
            'pow many',
            function () use ($parser) {
                checkNum($parser->parse('4 ^ 3')->value, 64);
                checkNum($parser->parse('4 ^ 3 ^ 2')->value, 262144);
                checkNum($parser->parse('4 ^ 3 ^ 2 ^ 1')->value, 262144);
                checkNum($parser->parse('4 ^ 3 ^ 2 ^ 1 ^ 0')->value, 262144);
            }
        );

        test(
            'parens',
            function () use ($parser) {
                checkNum($parser->parse('(1)')->value, 1);
                checkNum($parser->parse('(1 + 2)')->value, 3);
                checkNum($parser->parse('((1))')->value, 1);
                checkNum($parser->parse('((1 + 2))')->value, 3);
                checkNum($parser->parse('2 * (3 + 4)')->value, 14);
                checkNum($parser->parse('(2 + 3) * 4')->value, 20);
                checkNum($parser->parse('6 / (2 + 4)')->value, 1);
                checkNum($parser->parse('(2 + 6) / 2')->value, 4);
            }
        );

        test(
            'priority',
            function () use ($parser) {
                checkNum($parser->parse('2 * 3 + 4')->value, 10);
                checkNum($parser->parse('2 + 3 * 4')->value, 14);
                checkNum($parser->parse('6 / 3 + 4')->value, 6);
                checkNum($parser->parse('2 + 6 / 2')->value, 5);
            }
        );

        test(
            'postfix add',
            function () use ($parser) {
                checkNum($parser->parse('0++')->value, 1);
                checkNum($parser->parse('0++++')->value, 2);
                checkNum($parser->parse('0++++++')->value, 3);
                checkNum($parser->parse('0+++1')->value, 2);
                checkNum($parser->parse('0+++++1')->value, 3);
                checkNum($parser->parse('0+++++++1')->value, 4);
            }
        );

        test(
            'postfix sub',
            function () use ($parser) {
                checkNum($parser->parse('1--')->value, 0);
                checkNum($parser->parse('2----')->value, 0);
                checkNum($parser->parse('3------')->value, 0);
                checkNum($parser->parse('2---1')->value, 0);
                checkNum($parser->parse('3-----1')->value, 0);
                checkNum($parser->parse('4-------1')->value, 0);
            }
        );

        test(
            'prefix negate',
            function () use ($parser) {
                checkNum($parser->parse('1')->value, 1);
                checkNum($parser->parse('-1')->value, - 1);
                checkNum($parser->parse('--1')->value, 1);
                checkNum($parser->parse('---1')->value, - 1);
            }
        );
    }
);

group(
    'tutorial',
    function () {
        test(
            'simple grammar',
            function () {
                $id = letter()->seq(letter()->or_(digit())->star());
                $id1 = $id->parse('yeah');
                $id2 = $id->parse('f12');

                check($id1->value, array('y', array('e', 'a', 'h')));
                check($id2->value, array('f', array('1', '2')));

                $id3 = $id->parse('123');

                check($id3->message, 'letter expected');
                check($id3->position, 0);
                check($id->accept(Buffer::fromISO('foo')), true);
                check($id->accept(Buffer::fromISO('123')), false);
            }
        );

        test(
            'different parsers',
            function () {
                $id = letter()->seq(word()->star())->flatten();
                $matches = $id->matchesSkipping(Buffer::fromISO('foo 123 bar4'));

                check($matches, array('foo', 'bar4'));
            }
        );

        test(
            'complicated grammar',
            function () {
                $number = digit()->plus()->flatten()->trim()->map('intval');
                $term = undefined();
                $prod = undefined();
                $prim = undefined();

                $term->set(
                    $prod->seq(char('+')->trim())->seq($term)->map(
                        function ($values) {
                            return $values[0] + $values[2];
                        }
                    )->or_($prod)
                );

                $prod->set(
                    $prim->seq(char('*')->trim())->seq($prod)->map(
                        function ($values) {
                            return $values[0] * $values[2];
                        }
                    )->or_($prim)
                );

                $prim->set(
                    char('(')->trim()->seq($term)->seq(char(')'))->map(
                        function ($values) {
                            return $values[1];
                        }
                    )->or_($number)
                );

                $start = $term->end_();

                check($start->parse('1 + 2 * 3')->value, 7);
                check($start->parse('(1 + 2) * 3')->value, 9);
            }
        );

        test(
            'composite grammar',
            function () {
                $parser = new PluggableCompositeParser(function (CompositeParser $self) {
                    $self->def('start', $self->ref('list')->end_());
                    $self->def('list', $self->ref('element')->separatedBy(char(','), false));
                    $self->def('element', digit()->plus()->flatten());
                });
                check($parser->parse('1,23,456')->value, array('1', '23', '456'));
            }
        );

        test(
            'composite parser',
            function () {
                $parser = new PluggableCompositeParser(function (CompositeParser $self) {
                    $self->def('start', $self->ref('list')->end_());
                    $self->def('list', $self->ref('element')->separatedBy(char(','), false));
                    $self->def('element', digit()->plus()->flatten());
                    $self->action('element', 'intval');
                });
                check(array(1, 23, 456), $parser->parse('1,23,456')->value);
            }
        );
    }
);

group('php',
    function () {
        test(
            'ISO string buffer',
            function () {
                $str = "\xC2\xA1Hola!"; // 7 bytes

                $buffer = Buffer::fromISO($str);

                check($buffer->string, $str);
                check($buffer->length, 7);
                check($buffer->encoding, 'ISO-8859-1');

                check($buffer->charAt(0), "\xC2");
                check($buffer->charAt(6), '!');

                check($buffer->charCodeAt(0), 0xC2);
                check($buffer->charCodeAt(6), ord('!'));
            }
        );

        test(
            'utf-8 string buffer',
            function () {
                $pi = "\xCF\x80"; // UTF-8 greek Pi http://www.fileformat.info/info/unicode/char/03c0/index.htm
                $str = "\xC2\xA1Hola{$pi}"; // 8 bytes (6 characters in UTF-8)

                $buffer = Buffer::fromUTF8($str);

                check($buffer->string, $str);
                check($buffer->length, 6); // 6 characters
                check($buffer->encoding, 'UTF-8');

                check($buffer->charAt(0), "\xC2\xA1"); // two-byte UTF-8 character
                check($buffer->charAt(5), $pi);

                check($buffer->charCodeAt(0), 0xA1); // UTF-32 encoding is one byte
                check($buffer->charCodeAt(5), 0x03C0); // UTF-32 encoding of Pi is two bytes
            }
        );

        test(
            'buffer slices',
            function () {
                $str = "\xCF\x802345678";

                $buffer = Buffer::fromUTF8($str);

                check($buffer->slice(0)->string, $str);
                check($buffer->slice(1)->string, "2345678");
                check($buffer->slice(2)->string, "345678");
                check($buffer->slice(3)->string, "45678");

                check($buffer->slice(0,1)->string, "\xCF\x80");
                check($buffer->slice(0,1)->length, 1);
                check($buffer->slice(0,2)->string, "\xCF\x802");
                check($buffer->slice(0,2)->length, 2);
                check($buffer->slice(0,3)->string, "\xCF\x8023");
                check($buffer->slice(0,8)->string, "\xCF\x802345678");

                check($buffer->slice(3,6)->string, "456");
                check($buffer->slice(3,6)->slice(1)->string, "56");
                check($buffer->slice(3,6)->slice(0)->string, "456");
                check($buffer->slice(3,6)->slice(1,2)->string, "5");
                check($buffer->slice(3,6)->slice(1,3)->string, "56");
                check($buffer->slice(3,6)->slice(1,4)->slice(0,3)->length, 3);
                check($buffer->slice(3,6)->slice(1,4)->slice(0,3)->string, "567");

                check($buffer->slice(3,6)->slice(1,3)->charAt(0), "5");
                check($buffer->slice(3,6)->slice(1,3)->charCodeAt(0), ord("5"));
                check($buffer->slice(3,6)->slice(1,3)->charAt(1), "6");
                check($buffer->slice(3,6)->slice(1,3)->charCodeAt(1), ord("6"));
            }
        );

        test(
            'memory',
            function () {
                $buffer = Buffer::fromISO(str_repeat('0123456789', 1024)); // 10KB per buffer

                $baseline = memory_get_usage();

                $num_buffers = 100;

                $buffers = array();

                for ($i=0; $i<$num_buffers; $i++) {
                    $buffers[$i] = $buffer;
                    $buffer = $buffer->slice(0);
                }

                $used = memory_get_usage() - $baseline;

                $maximum = $buffer->length * 4 * $num_buffers; // 4 bytes per character

                $percent = 100 * ($used / $maximum);

                check($percent < 5, true, 'maximum memory usage of ' . number_format($percent, 2) .  '% of buffers');
            }
        );
    }
);

exit(status());
