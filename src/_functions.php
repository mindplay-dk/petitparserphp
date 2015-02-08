<?php

namespace petitparser;

use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * @param mixed $value
 *
 * @throws \RuntimeException
 * @return int
 */
function length($value)
{
    if (is_string($value)) {
        return mb_strlen($value); // TODO QA
    }

    if (is_array($value)) {
        return count($value);
    }

    if (is_object($value) && (method_exists($value, 'get_length') || property_exists($value, 'length'))) {
        return $value->length;
    }

    throw new RuntimeException("unable to obtain length of given value");
}

/**
 * Internal method to convert an element to a character code.
 *
 * @param int|string $element single character encoded as UTF-8; or a 32-bit Unicode character code
 *
 * @return int 32-bit Unicode character code
 *
 * @throws InvalidArgumentException
 */
function toCharCode($element)
{
    if (is_int($element)) {
        return $element;
    }

    if (is_string($element) && mb_strlen($element) === 1) {
        $element = mb_convert_encoding($element, 'UTF-32');

        if (PHP_INT_SIZE <= 4) {
            list(, $h, $l) = unpack('n*', $element);
            return ($l + ($h * 0x010000));
        } else {
            list(, $int) = unpack('N', $element);
            return $int;
        }
    }

    throw new InvalidArgumentException("'$element' is not a character");
}

/**
 * Internal method to convert a unicode character code to a string
 *
 * @param int $ord Unicode character code
 * @param string|null $encoding encoding (or NULL to use mb_internal_encoding())
 *
 * @return string
 */
function fromCharCode($ord, $encoding = null) {
    return mb_convert_encoding(pack("N", $ord), $encoding ?: mb_internal_encoding(), 'UCS-4BE');
}

/**
 * Returns a parser that accepts a specific character only.
 *
 * @param int|string $element
 * @param string $message
 *
 * @return Parser
 */
function char($element, $message = null)
{
    return new CharacterParser(
        new SingleCharacterPredicate($element),
        $message ? : "\"{$element}\" expected");
}

/**
 * Returns a parser that accepts any digit character.
 *
 * @param string $message
 *
 * @return Parser
 */
function digit($message = null)
{
    static $digitCharMatcher = null;

    if ($digitCharMatcher === null) {
        $digitCharMatcher = new DigitCharacterPredicate();
    }

    return new CharacterParser(
      $digitCharMatcher,
      $message ?: 'digit expected');
}

/**
 * Returns a parser that accepts any letter character.
 *
 * @param $message
 *
 * @return Parser
 */
function letter($message = null)
{
    static $letterCharMatcher = null;

    if ($letterCharMatcher === null) {
        $letterCharMatcher = new LetterCharacterPredicate();
    }

    return new CharacterParser(
        $letterCharMatcher,
        $message ?: 'letter expected');
}

/**
 * Returns a parser that accepts any lowercase character.
 *
 * @param string $message
 *
 * @return Parser
 */
function lowercase($message = null)
{
    static $lowercaseCharMatcher = null;

    if ($lowercaseCharMatcher === null) {
        $lowercaseCharMatcher = new LowercaseCharacterPredicate();
    }

    return new CharacterParser(
        $lowercaseCharMatcher,
        $message ?: 'lowercase letter expected');
}

/**
 * Returns a parser that accepts the given character class pattern.
 *
 * @param string $element
 * @param string $message
 *
 * @return Parser
 */
function pattern($element, $message = null)
{
    static $patternParser = null;

    if ($patternParser === null) {
        $single = any()->map(
            function ($each) {
                return new RangeCharacterPredicate($each, $each);
            }
        );

        $multiple = any()->seq(char('-'))->seq(any())->map(
            function ($each) {
                return new RangeCharacterPredicate($each[0], $each[2]);
            }
        );

        $positive = $multiple->or_($single)->plus()->map(
            function ($each) {
                return _optimizedRanges($each);
            }
        );

        $patternParser = char('^')->optional()->seq($positive)->map(
            function ($each) {
                return $each[0] === null ? $each[1] : new NotCharacterPredicate($each[1]);
            }
        );
    }

    return new CharacterParser(
        $patternParser->parse($element)->value,
        $message ?: "[{$element}] expected");
}

/**
 * Returns a parser that accepts any character in the range between [start] and [stop].
 *
 * @param int    $start
 * @param int    $stop
 * @param string $message
 *
 * @return Parser
 */
function range($start, $stop, $message = null)
{
    return new CharacterParser(
        new RangeCharacterPredicate($start, $stop),
        $message ?: "{$start}..{$stop} expected");
}

/**
 * Returns a parser that accepts any uppercase character.
 *
 * @param string $message
 *
 * @return Parser
 */
function uppercase($message = null)
{
    static $uppercaseCharMatcher = null;

    if ($uppercaseCharMatcher === null) {
        $uppercaseCharMatcher = new UppercaseCharacterPredicate();
    }

    return new CharacterParser(
        $uppercaseCharMatcher,
        $message ?: 'uppercase letter expected');
}

/**
 * Returns a parser that accepts any whitespace character.
 *
 * @param string $message
 *
 * @return Parser
 */
function whitespace($message = null)
{
    static $whitespaceCharMatcher = null;

    if ($whitespaceCharMatcher === null) {
        $whitespaceCharMatcher = new WhitespaceCharacterPredicate();
    }

    return new CharacterParser(
        $whitespaceCharMatcher,
        $message ?: 'whitespace expected');
}

/**
 * Returns a parser that accepts any word character.
 *
 * @param string $message
 *
 * @return Parser
 */
function word($message = null)
{
    static $wordCharMatcher = null;

    if ($wordCharMatcher === null) {
        $wordCharMatcher = new WordCharacterPredicate();
    }

    return new CharacterParser(
        $wordCharMatcher,
        $message ?: 'letter or digit expected');
}

/**
 * Returns a parser that consumes nothing and succeeds.
 *
 * For example, [:char('a').or(epsilon()):] is equivalent to
 * [:char('a').optional():].
 */
function epsilon($result = null)
{
    return new EpsilonParser($result);
}

/**
 * Returns a parser that consumes nothing and fails.
 *
 * For example, [:failure():] always fails, no matter what input it is given.
 *
 * @param string $message
 *
 * @return Parser
 */
function failure($message = 'unable to parse')
{
    return new FailureParser($message);
}

/**
 * Returns a parser that is not defined, but that can be set at a later
 * point in time.
 *
 * For example, the following code sets up a parser that points to itself
 * and that accepts a sequence of a's ended with the letter b.
 *
 *     var p = undefined();
 *     p.set(char('a').seq(p).or(char('b')));
 *
 * @param string $message
 *
 * @return SettableParser
 */
function undefined($message = 'undefined parser')
{
    return failure($message)->settable();
}

/**
 * Returns a parser that accepts any input element.
 *
 * For example, [:any():] succeeds and consumes any given letter. It only
 * fails for an empty input.
 *
 * @param string $message
 *
 * @return Parser
 */
function any($message = null)
{
    return new AnyParser($message ?: 'input expected');
}

/**
 * Returns a parser that accepts any of the [elements].
 *
 * For example, [:anyIn('ab'):] succeeds and consumes either the letter
 * [:'a':] or the letter [:'b':]. For any other input the parser fails.
 *
 * @param array|string $elements
 * @param string $message
 *
 * @return Parser
 */
function anyIn($elements, $message = null)
{
    if (is_string($elements)) {
        $elements = Buffer::create($elements)->toArray();
    }

    return predicate(
        1,
        function ($each) use ($elements) {
            return array_search($each, $elements) !== false;
        },
        $message ? : 'any of ' . implode(', ', $elements) . ' expected'
    );
}

/**
 * Returns a parser that accepts any of the specified characters.
 *
 * @param string $string
 * @param string $message
 *
 * @return Parser
 */
function anyOf($string, $message = null)
{
    return new CharacterParser(_optimizedString($string), $message ?: "any of \"{$string}\" expected");
}

/**
 * Returns a parser that accepts none of the specified characters.
 *
 * @param string $string
 * @param string $message
 *
 * @return Parser
 */
function noneOf($string, $message = null)
{
    return new CharacterParser(
        new NotCharacterPredicate(_optimizedString($string)),
        $message ?: "none of \"{$string}\" expected");
}

/**
 * @param string $string
 *
 * @return CharacterPredicate
 */
function _optimizedString($string)
{
    $ranges = array();

    $buffer = Buffer::create($string);

    for ($offset=0; $offset<$buffer->length; $offset++) {
        $value = $buffer->charCodeAt($offset);

        $ranges[] = new RangeCharacterPredicate($value, $value);
    }

    return _optimizedRanges($ranges);
}

/**
 * @param RangeCharacterPredicate[] $ranges
 *
 * @return CharacterPredicate
 */
function _optimizedRanges($ranges)
{
    // 1. sort the ranges:

    $sortedRanges = $ranges;

    usort($sortedRanges, function ($first, $second) {
        return $first->start != $second->start
            ? $first->start - $second->start
            : $first->stop - $second->stop;
    });

    // 2. merge adjacent or overlapping ranges:

    $mergedRanges = array();

    foreach ($sortedRanges as $thisRange) {
        if (count($mergedRanges) === 0) {
            $mergedRanges[] = $thisRange;
        } else {
            $lastRange = $mergedRanges[count($mergedRanges) - 1];

            if ($lastRange->stop + 1 >= $thisRange->start) {
                $characterRange = new RangeCharacterPredicate($lastRange->start, $thisRange->stop);
                $mergedRanges[count($mergedRanges) - 1] = $characterRange;
            } else {
                $mergedRanges[] = $thisRange;
            }
        }
    }

    // 3. build the best resulting predicates:

    if (count($mergedRanges) === 1) {
        return $mergedRanges[0]->start === $mergedRanges[0]->stop
            ? new SingleCharacterPredicate($mergedRanges[0]->start)
            : $mergedRanges[0];
    } else {
        return new RangesCharacterPredicate(
            count($mergedRanges),
            array_map(function (RangeCharacterPredicate $range) {
                return $range->start;
            }, $mergedRanges),
            array_map(function (RangeCharacterPredicate $range) {
                return $range->stop;
            }, $mergedRanges)
        );
    }
}

/**
 * Returns a parser that accepts the string [element].
 *
 * For example, [:string('foo'):] succeeds and consumes the input string
 * [:'foo':]. Fails for any other input.
 *
 * @param string $element
 * @param string $message
 *
 * @return Parser
 */
function string($element, $message = null)
{
    return predicate(
        mb_strlen($element), // TODO QA
        function ($each) use ($element) {
            return $element === $each;
        },
        $message ? : "{$element} expected"
    );
}

/**
 * Returns a parser that accepts the string [element] ignoring the case.
 *
 * For example, [:stringIgnoreCase('foo'):] succeeds and consumes the input
 * string [:'Foo':] or [:'FOO':]. Fails for any other input.
 *
 * @param string $element
 * @param string $message
 *
 * @return Parser
 */
function stringIgnoreCase($element, $message = null)
{
    $lowerElement = mb_convert_case($element, MB_CASE_LOWER);

    return predicate(
        mb_strlen($element),
        function ($each) use ($lowerElement) {
            return $lowerElement === mb_convert_case($each, MB_CASE_LOWER);
        },
        $message ? : "{$element} expected"
    );
}

/**
 * A generic predicate function returning [true] or [false] for a given
 * [input] argument.
 *
 * TODO add typedef when supported by php-doc
 */
//typedef bool Predicate(input);

/**
 * Returns a parser that reads input of the specified [length], accepts
 * it if the [predicate] matches, or fails with the given [message].
 *
 * @param int       $length
 * @param callable $predicate function($value) : bool
 * @param string    $message
 *
 * @return Parser
 */
function predicate($length, $predicate, $message)
{
    return new PredicateParser($length, $predicate, $message);
}

/**
 * Returns a lazy iterable over all parsers reachable from a [root]. Do
 * not modify the grammar while iterating over it, otherwise you might
 * get unexpected results.
 *
 * @param Parser $root
 *
 * @return ParserIterable|Parser[]
 */
function allParser(Parser $root)
{
    return new ParserIterable($root);
}

/**
 * Transforms all parsers reachable from [parser] with the given [handler].
 * The identity function returns a copy of the the incoming parser.
 *
 * The implementation first creates a copy of each parser reachable in the
 * input grammar; then the resulting grammar is traversed until all references
 * to old parsers are replaced with the transformed ones.
 *
 * @param Parser $parser
 * @param Closure $handler function (Parser $parser): Parser
 *
 * @return Parser
 */
function transformParser(Parser $parser, Closure $handler)
{
    $mapping = array();

    foreach (allParser($parser) as $each) {
        $mapping[spl_object_hash($each)] = $handler($each->copy());
    }

    $seen = array_values($mapping);
    $todo = array_values($mapping);

    while (count($todo)) {
        /** @var Parser $parent */
        $parent = array_pop($todo);

        foreach ($parent->children as $child) {
            if (isset($mapping[spl_object_hash($child)])) {
                $parent->replace($child, $mapping[spl_object_hash($child)]);
            } else if (! in_array($child, $seen, true)) {
                $seen[] = $child;
                $todo[] = $child;
            }
        }
    }

    return $mapping[spl_object_hash($parser)];
}

/**
 * Returns a copy of the given Parser with all settable parsers removed.
 *
 * @param Parser $parser
 *
 * @return Parser
 */
function removeSettables(Parser $parser)
{
    return transformParser(
        $parser,
        function (Parser $each) {
            while ($each instanceof SettableParser) {
                $each = $each->children[0];
            }
            return $each;
        }
    );
}

/**
 * @param Parser $parser
 *
 * @return Parser
 */
function removeDuplicates(Parser $parser)
{
    $uniques = array();

    return transformParser(
        $parser,
        function (Parser $source) use (&$uniques) {
            foreach ($uniques as $each) {
                if ($source !== $each && $source->isEqualTo($each)) {
                    $target = $each;
                    break;
                }
            }

            if (! isset($target)) {
                if (! in_array($source, $uniques, true)) {
                    $uniques[] = $source;
                }
                return $source;
            } else {
                return $target;
            }
        }
    );
}

// TODO implement these functions

///**
// * Adds debug handlers to each parser reachable from [root].
// */
//Parser debug(Parser root) {
//  var level = 0;
//  return transformParser(root, (parser) {
//    return new _ContinuationParser(parser, (context, continuation) {
//      print('${_repeat(level, '  ')}${parser}');
//      level++;
//      var result = continuation(context);
//      level--;
//      print('${_repeat(level, '  ')}${result}');
//      return result;
//     });
//  });
//}
//
//String _repeat(int count, String value) {
//  var result = new StringBuffer();
//  for (var i = 0; i < count; i++) {
//    result.write(value);
//  }
//  return result.toString();
//}
//
///**
// * Adds progress handlers to each parser reachable from [root].
// */
//Parser progress(Parser root) {
//  return transformParser(root, (parser) {
//    return new _ContinuationParser(parser, (context, continuation) {
//      print('${_repeat(context.position, '*')} $parser');
//      return continuation(context);
//    });
//  });
//}
//
///**
// * Adds profiling handlers to each parser reachable from [root].
// */
//Parser profile(Parser root) {
//  var count = new Map();
//  var watch = new Map();
//  var parsers = new List();
//  return new _ContinuationParser(transformParser(root, (parser) {
//    parsers.add(parser);
//    return new _ContinuationParser(parser, (context, continuation) {
//      count[parser]++;
//      watch[parser].start();
//      var result = continuation(context);
//      watch[parser].stop();
//      return result;
//     });
//  }), (context, continuation) {
//    parsers.forEach((parser) {
//      count[parser] = 0;
//      watch[parser] = new Stopwatch();
//    });
//    var result = continuation(context);
//    parsers.forEach((parser) {
//      print('${count[parser]}\t'
//        '${watch[parser].elapsedMicroseconds}\t'
//        '${parser}');
//    });
//    return result;
//  });
//}
