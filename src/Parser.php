<?php

namespace petitparser;

/**
 * Abstract base class of all parsers.
 *
 * @property-read Parser[] $children Returns a list of directly referenced parsers.
 */
abstract class Parser extends Accessors
{
    /**
     * @type int unbounded upper limit
     */
    const UNBOUNDED = -1;

    /**
     * Primitive method doing the actual parsing.
     *
     * The method is overridden in concrete subclasses to implement the
     * parser specific logic. The methods takes a parse [context] and
     * returns the resulting context, which is either a [Success] or
     * [Failure] context.
     *
     * @param Context $context
     *
     * @return Result
     */
    abstract public function parseOn(Context $context);

    /**
     * Returns the parse result of the [input] buffer.
     *
     * The implementation creates a default parse context on the input and calls
     * the internal parsing logic of the receiving parser.
     *
     * For example, [:letter().plus().parse('abc'):] results in an instance of
     * [Success], where [Result.position] is [:3:] and [Success.value] is
     * [:[a, b, c]:].
     *
     * Similarly, [:letter().plus().parse('123'):] results in an instance of
     * [Failure], where [Result.position] is [:0:] and [Failure.message] is
     * ['letter expected'].
     *
     * @param Buffer|string $input
     *
     * @return Result
     */
    public function parse($input)
    {
        return $this->parseOn(new Context(is_string($input) ? Buffer::fromUTF8($input) : $input, 0));
    }

    /**
     * @see parse()
     *
     * @param string $string UTF-8 encoded string to parse
     *
     * @return Result
     */
    public function parseUTF8($string)
    {
        return $this->parse(Buffer::fromUTF8($string));
    }

    /**
     * @see parse()
     *
     * @param string $string ISO-8859-1 encoded string to parse
     *
     * @return Result
     */
    public function parseISO($string)
    {
        return $this->parse(Buffer::fromISO($string));
    }

    /**
     * Tests if the [input] can be successfully parsed.
     *
     * For example, [:letter().plus().accept('abc'):] returns [:true:], and
     * [:letter().plus().accept('123'):] returns [:false:].
     *
     * @param Buffer $input
     *
     * @return bool
     */
    public function accept(Buffer $input)
    {
        return $this->parse($input)->isSuccess;
    }

    /**
     * Returns a list of all successful overlapping parses of the [input].
     *
     * For example, [:letter().plus().matches('abc de'):] results in the list
     * [:[['a', 'b', 'c'], ['b', 'c'], ['c'], ['d', 'e'], ['e']]:]. See
     * [Parser.matchesSkipping] to retrieve non-overlapping parse results.
     *
     * @param Buffer $input
     *
     * @return Result[]
     */
    public function matches(Buffer $input)
    {
        $list = array();

        $this->and_()
            ->map(
                function ($each) use (&$list) {
                    $list[] = $each;
                }
            )
            ->seq(any())
            ->or_(any())
            ->star()
            ->parse($input);

        return $list;
    }

    /**
     * Returns a list of all successful non-overlapping parses of the input.
     *
     * For example, [:letter().plus().matchesSkipping('abc de'):] results in the
     * list [:[['a', 'b', 'c'], ['d', 'e']]:]. See [Parser.matches] to retrieve
     * overlapping parse results.
     *
     * @param Buffer $input
     *
     * @return Result[]
     */
    public function matchesSkipping(Buffer $input)
    {
        $list = array();

        $this
            ->map(
                function ($each) use (&$list) {
                    $list[] = $each;
                }
            )
            ->or_(any())
            ->star()
            ->parse($input);

        return $list;
    }

    /**
     * Returns new parser that accepts the receiver, if possible. The resulting
     * parser returns the result of the receiver, or [:null:] if not applicable.
     * The returned value can be provided as an optional argument [otherwise].
     *
     * For example, the parser [:letter().optional():] accepts a letter as input
     * and returns that letter. When given something else the parser succeeds as
     * well, does not consume anything and returns [:null:].
     *
     * @param mixed $otherwise
     *
     * @return Parser
     */
    public function optional($otherwise = null)
    {
        return new OptionalParser($this, $otherwise);
    }

    /**
     * Returns a parser that accepts the receiver zero or more times. The
     * resulting parser returns a list of the parse results of the receiver.
     *
     * This is a greedy and blind implementation that tries to consume as much
     * input as possible and that does not consider what comes afterwards.
     *
     * For example, the parser [:letter().star():] accepts the empty string or
     * any sequence of letters and returns a possibly empty list of the parsed
     * letters.
     *
     * @return Parser
     */
    public function star()
    {
        return $this->repeat(0, self::UNBOUNDED);
    }

    /**
     * Returns a parser that parses the receiver zero or more times until it
     * reaches a [limit]. This is a greedy non-blind implementation of the
     * [Parser.star] operator. The [limit] is not consumed.
     *
     * @param Parser $limit
     *
     * @return Parser
     */
    public function starGreedy(Parser $limit)
    {
        return $this->repeatGreedy($limit, 0, self::UNBOUNDED);
    }

    /**
     * Returns a parser that parses the receiver zero or more times until it
     * reaches a [limit]. This is a lazy non-blind implementation of the
     * [Parser.star] operator. The [limit] is not consumed.
     *
     * @param Parser $limit
     *
     * @return Parser
     */
    public function starLazy(Parser $limit)
    {
        return $this->repeatLazy($limit, 0, self::UNBOUNDED);
    }

    /**
     * Returns a parser that accepts the receiver one or more times. The
     * resulting parser returns a list of the parse results of the receiver.
     *
     * This is a greedy and blind implementation that tries to consume as much
     * input as possible and that does not consider what comes afterwards.
     *
     * For example, the parser [:letter().plus():] accepts any sequence of
     * letters and returns a list of the parsed letters.
     *
     * @return Parser
     */
    public function plus()
    {
        return $this->repeat(1, self::UNBOUNDED);
    }

    /**
     * Returns a parser that parses the receiver one or more times until it
     * reaches [limit]. This is a greedy non-blind implementation of the
     * [Parser.plus] operator. The [limit] is not consumed.
     *
     * @param Parser $limit
     *
     * @return Parser
     */
    public function plusGreedy(Parser $limit)
    {
        return $this->repeatGreedy($limit, 1, self::UNBOUNDED);
    }

    /**
     * Returns a parser that parses the receiver zero or more times until it
     * reaches a [limit]. This is a lazy non-blind implementation of the
     * [Parser.plus] operator. The [limit] is not consumed.
     *
     * @param Parser $limit
     *
     * @return Parser
     */
    public function plusLazy(Parser $limit)
    {
        return $this->repeatLazy($limit, 1, self::UNBOUNDED);
    }

    /**
     * Returns a parser that accepts the receiver between [min] and [max] times.
     * The resulting parser returns a list of the parse results of the receiver.
     *
     * This is a greedy and blind implementation that tries to consume as much
     * input as possible and that does not consider what comes afterwards.
     *
     * For example, the parser [:letter().repeat(2, 4):] accepts a sequence of
     * two, three, or four letters and returns the accepted letters as a list.
     *
     * @param int $min
     * @param int $max
     *
     * @return Parser
     */
    public function repeat($min, $max)
    {
        return new PossessiveRepeatingParser($this, $min, $max);
    }

    /**
     * Returns a parser that parses the receiver at least [min] and at most [max]
     * times until it reaches a [limit]. This is a greedy non-blind implementation of
     * the [Parser.repeat] operator. The [limit] is not consumed.
     *
     * @param Parser $limit
     * @param int    $min
     * @param int    $max
     *
     * @return Parser
     */
    public function repeatGreedy(Parser $limit, $min, $max)
    {
        return new GreedyRepeatingParser($this, $limit, $min, $max);
    }

    /**
     * Returns a parser that parses the receiver at least [min] and at most [max]
     * times until it reaches a [limit]. This is a lazy non-blind implementation of
     * the [Parser.repeat] operator. The [limit] is not consumed.
     *
     * @param Parser $limit
     * @param int    $min
     * @param int    $max
     *
     * @return Parser
     */
    public function repeatLazy(Parser $limit, $min, $max)
    {
        return new LazyRepeatingParser($this, $limit, $min, $max);
    }

    /**
     * Returns a parser that accepts the receiver exactly [count] times. The
     * resulting parser returns a list of the parse results of the receiver.
     *
     * For example, the parser [:letter().times(2):] accepts two letters and
     * returns a list of the two parsed letters.
     *
     * @param int $count
     *
     * @return Parser
     */
    public function times($count)
    {
        return $this->repeat($count, $count);
    }

    /**
     * Returns a parser that accepts the receiver followed by [other]. The
     * resulting parser returns a list of the parse result of the receiver
     * followed by the parse result of [other]. Calling this method on an
     * existing sequence code not nest this sequence into a new one, but
     * instead augments the existing sequence with [other].
     *
     * For example, the parser [:letter().seq(digit()).seq(letter()):] accepts a
     * letter followed by a digit and another letter. The parse result of the
     * input string [:'a1b':] is the list [:['a', '1', 'b']:].
     *
     * @param Parser $other
     *
     * @return Parser
     */
    public function seq(Parser $other)
    {
        return new SequenceParser(array($this, $other));
    }

    /**
     * Returns a parser that accepts the receiver or [other]. The resulting
     * parser returns the parse result of the receiver, if the receiver fails
     * it returns the parse result of [other] (exclusive ordered choice).
     *
     * For example, the parser [:letter().or(digit()):] accepts a letter or a
     * digit. An example where the order matters is the following choice between
     * overlapping parsers: [:letter().or(char('a')):]. In the example the parser
     * [:char('a'):] will never be activated, because the input is always consumed
     * [:letter():]. This can be problematic if the author intended to attach a
     * production action to [:char('a'):].
     *
     * TODO find a better name for this method
     *
     * @param Parser $other
     *
     * @return Parser
     */
    public function or_(Parser $other)
    {
        return new ChoiceParser(array($this, $other));
    }

    /**
     * Returns a parser (logical and-predicate) that succeeds whenever the
     * receiver does, but never consumes input.
     *
     * For example, the parser [:char('_').and().seq(identifier):] accepts
     * identifiers that start with an underscore character. Since the predicate
     * does not consume accepted input, the parser [:identifier:] is given the
     * ability to process the complete identifier.
     *
     * @return AndParser
     */
    public function and_()
    {
        return new AndParser($this);
    }

    /**
     * Returns a parser (logical not-predicate) that succeeds whenever the
     * receiver fails, but never consumes input.
     *
     * For example, the parser [:char('_').not().seq(identifier):] accepts
     * identifiers that do not start with an underscore character. If the parser
     * [:char('_'):] accepts the input, the negation and subsequently the
     * complete parser fails. Otherwise the parser [:identifier:] is given the
     * ability to process the complete identifier.
     *
     * @param string $message
     *
     * @return Parser
     */
    public function not_($message = null)
    {
        return new NotParser($this, $message);
    }

    /**
     * Returns a parser that consumes any input token (character), but the
     * receiver.
     *
     * For example, the parser [:letter().neg():] accepts any input but a letter.
     * The parser fails for inputs like [:'a':] or [:'Z':], but succeeds for
     * input like [:'1':], [:'_':] or [:'$':].
     *
     * @param string $message
     *
     * @return Parser
     */
    public function neg($message = null)
    {
        return $this->not_($message)->seq(any())->pick(1);
    }

    /**
     * Returns a parser that discards the result of the receiver, and returns
     * a sub-string of the consumed range in the string/list being parsed.
     *
     * For example, the parser [:letter().plus().flatten():] returns [:'abc':]
     * for the input [:'abc':]. In contrast, the parser [:letter().plus():] would
     * return [:['a', 'b', 'c']:] for the same input instead.
     *
     * @return Parser
     */
    public function flatten()
    {
        return new FlattenParser($this);
    }

    /**
     * Returns a parser that returns a [Token]. The token carries the parsed
     * values of the receiver [Token.value], as well as the consumed range from
     * [Token.start] to [Token.stop] of the string/list being parsed.
     *
     * For example, the parser [:letter().plus().token():] returns the token
     * [:Token[start: 0, stop: 3, value: abc]:] for the input [:'abc':].
     *
     * @return Parser
     */
    public function token()
    {
        return new TokenParser($this);
    }

    /**
     * Returns a parser that consumes input before and after the receiver. The
     * optional argument [trimmer] is a parser that consumes the excess input. By
     * default [:whitespace():] is used.
     *
     * For example, the parser [:letter().plus().trim():] returns [:['a', 'b']:]
     * for the input [:' ab\n':] and consumes the complete input string.
     *
     * @param Parser $trimmer
     *
     * @return Parser
     */
    public function trim(Parser $trimmer = null)
    {
        return new TrimmingParser($this, $trimmer ?: whitespace());
    }

    /**
     * Returns a parser that succeeds only if the receiver consumes the complete
     * input, otherwise return a failure with the optional [message].
     *
     * For example, the parser [:letter().end():] succeeds on the input [:'a':]
     * and fails on [:'ab':]. In contrast the parser [:letter():] alone would
     * succeed on both inputs, but not consume everything for the second input.
     *
     * @param string $message
     *
     * @return Parser
     */
    public function end_($message = 'end of input expected')
    {
        return new EndOfInputParser($this, $message);
    }

    /**
     * Returns a parser that points to the receiver, but can be changed to point
     * to something else at a later point in time.
     *
     * For example, the parser [:letter().setable():] behaves exactly the same
     * as [:letter():], but it can be replaced with another parser using
     * [SetableParser.set].
     *
     * @return SetableParser
     */
    public function setable()
    {
        return new SetableParser($this);
    }

    /**
     * Returns a parser that evaluates [function] as action handler on success
     * of the receiver.
     *
     * For example, the parser [:digit().map((char) => int.parse(char)):] returns
     * the number [:1:] for the input string [:'1':].
     *
     * @param callable $function
     *
     * @return Parser
     */
    public function map($function)
    {
        return new ActionParser($this, $function);
    }

    /**
     * Returns a parser that transform a successful parse result by returning
     * the element at [index] of a list. A negative index can be used to access
     * the elements from the back of the list.
     *
     * For example, the parser [:letter().star().pick(-1):] returns the last
     * letter parsed. For the input [:'abc':] it returns [:'c':].
     *
     * @param int $index
     *
     * @return Parser
     */
    public function pick($index)
    {
        return $this
            ->map(
                function ($list) use ($index) {
                    return $list[$index < 0 ? count($list) + $index : $index];
                }
            );
    }

    /**
     * Returns a parser that transforms a successful parse result by returning
     * the permuted elements at [indexes] of a list. Negative indexes can be
     * used to access the elements from the back of the list.
     *
     * For example, the parser [:letter().star().permute([0, -1]):] returns the
     * first and last letter parsed. For the input [:'abc':] it returns
     * [:['a', 'c']:].
     *
     * @param int[] $indexes
     *
     * @return Parser
     */
    public function permute($indexes)
    {
        return $this
            ->map(
                function ($list) use ($indexes) {
                    return array_map(
                        function ($index) use ($list) {
                            return $list[$index < 0 ? count($list) + $index : $index];
                        },
                        $indexes
                    );
                }
            );
    }

    /**
     * Returns a parser that consumes the receiver one or more times separated
     * by the [separator] parser. The resulting parser returns a flat list of
     * the parse results of the receiver interleaved with the parse result of the
     * separator parser.
     *
     * If the optional argument [includeSeparators] is set to [:false:], then the
     * separators are not included in the parse result. If the optional argument
     * [optionalSeparatorAtEnd] is set to [:true:] the parser also accepts an
     * optional separator at the end.
     *
     * For example, the parser [:digit().separatedBy(char('-')):] returns a parser
     * that consumes input like [:'1-2-3':] and returns a list of the elements and
     * separators: [:['1', '-', '2', '-', '3']:].
     *
     * @param Parser $separator
     * @param bool   $includeSeparators
     * @param bool   $optionalSeparatorAtEnd
     *
     * @return Parser
     */
    public function separatedBy(Parser $separator, $includeSeparators = true, $optionalSeparatorAtEnd = false)
    {
        $repeater = new SequenceParser(array($separator, $this));
        $repeater = $repeater->star();

        $parser = new SequenceParser($optionalSeparatorAtEnd
            ? array($this, $repeater, $separator->optional($separator))
            : array($this, $repeater));

        return $parser->map(
            function ($list) use ($includeSeparators, $optionalSeparatorAtEnd, $separator) {
                $result = array();
                $result[] = $list[0];

                foreach ($list[1] as $tuple) {
                    if ($includeSeparators) {
                        $result[] = $tuple[0];
                    }
                    $result[] = $tuple[1];
                }
                if ($includeSeparators && $optionalSeparatorAtEnd && $list[2] !== $separator) {
                    $result[] = $list[2];
                }
                return $result;
            }
        );
    }

    /**
     * Returns a shallow copy of the receiver.
     *
     * @return Parser
     */
    abstract public function copy();

    /**
     * Recusively tests for the equality of two parsers.
     *
     * The code can automatically deals with recursive parsers and parsers that
     * refer to other parsers. This code is supposed to be overridden by parsers
     * that add other state.
     *
     * @param Parser   $other
     * @param Parser[] $seen
     *
     * @return bool
     */
    public function equals(Parser $other, $seen = array())
    {
        if ($seen === null) {
          $seen = array();
        }

        if ($this === $other || in_array($this, $seen, true)) {
          return true;
        }

        $seen[] = $this;

        return get_class($this) === get_class($other)
            && $this->equalProperties($other)
            && $this->equalChildren($other, $seen);
    }

    /**
     * Compare the properties of two parsers. Normally this method should not be
     * called directly, instead use [Parser#equals].
     *
     * Override this method in all subclasses that add new state.
     *
     * @param Parser $other
     *
     * @return bool
     */
    public function equalProperties(
        /** @noinspection PhpUnusedParameterInspection */
        Parser $other)
    {
        return true;
    }

    /**
     * @param Parser   $other
     * @param Parser[] $seen
     *
     * @return bool
     */
    protected function equalChildren(Parser $other, $seen = array())
    {
        $thisChildren = $this->children;
        $otherChildren = $other->children;

        if (length($thisChildren) !== length($otherChildren)) {
            return false;
        }

        for ($i = 0; $i < length($thisChildren); $i++) {
            if (! $thisChildren[$i]->equals($otherChildren[$i], $seen)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a list of directly referenced parsers.
     *
     * For example, [:letter().children:] returns the empty collection [:[]:],
     * because the letter parser is a primitive or leaf parser that does not
     * depend or call any other parser.
     *
     * In contrast, [:letter().or(digit()).children:] returns a collection
     * containing both the [:letter():] and [:digit():] parser.
     *
     * @see $children
     *
     * @return Parser[]
     */
    protected function get_children()
    {
        return array();
    }

    /**
     * Changes the receiver by replacing [source] with [target]. Does nothing
     * if [source] does not exist in [Parser.children].
     *
     * The following example creates a letter parser and then defines a parser
     * called [:example:] that accepts one or more letters. Eventually the parser
     * [:example:] is modified by replacing the [:letter:] parser with a new
     * parser that accepts a digit. The resulting [:example:] parser accepts one
     * or more digits.
     *
     *     $letter = $this->letter();
     *     $example = $letter->plus();
     *     $example->replace($letter, $this->digit());
     *
     * @param Parser $source
     * @param Parser $target
     *
     * @return void
     */
    public function replace(Parser $source, Parser $target)
    {
        // no children, nothing to do
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return get_class($this);
    }
}
