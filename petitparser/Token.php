<?php

namespace petitparser;

/**
 * A token represents a parsed part of the input stream. The token holds
 * the parsed input, the input buffer, and the start and stop position
 * in the input buffer.
 *
 * @property-read mixed $value Returns the parsed value.
 * @property-read mixed $buffer Returns the input buffer.
 * @property-read int $start Returns the start position in the input buffer.
 * @property-read int $stop Returns the stop position in the input buffer.
 * @property-read int $length Returns the length of the token.
 * @property-read int $line Returns the line number of the token.
 * @property-read int $column Returns the column number of this token.
 * @property-read string $input The consumed input of the token.
 */
class Token extends Accessors implements Comparable
{
    /**
     * @var mixed
     */
    private $_value;

    /**
     * @var string
     */
    private $_buffer;

    /**
     * @var int
     */
    private $_start;

    /**
     * @var int
     */
    private $_stop;

    /**
     * @param mixed $value
     * @param string $buffer
     * @param int $start
     * @param int $stop
     */
    public function __construct($value, $buffer, $start, $stop)
    {
        $this->_value = $value;
        $this->_buffer = $buffer;
        $this->_start = $start;
        $this->_stop = $stop;
    }

    /**
     * @return Parser Returns a parser for that detects newlines platform independently.
     */
    public static function newLineParser()
    {
        static $parser = null;

        if ($parser === null) {
            $parser = char("\n")->or_(char("\r")->seq(char("\n")->optional()));
        }

        return $parser;
    }

    /**
     * @param mixed $buffer
     * @param int $position
     * @return array [int $line, int $column]
     */
    public static function lineAndColumnOf($buffer, $position)
    {
        $line = 1;
        $offset = 0;

        foreach (Token::newLineParser()->token()->matchesSkipping($buffer) as $token) {
            /** @var Token $token */
            if ($position < $token->stop) {
                return array($line, $position - $offset + 1);
            }
            $line += 1;
            $offset = $token->stop;
        }

        return array($line, $position - $offset + 1);
    }

    /**
     * @param mixed $other
     *
     * @return bool
     */
    public function equals($other)
    {
        return $other instanceof Token
            && $this->_value === $other->_value
            && $this->_start === $other->_start
            && $this->_stop === $other->_stop;
    }

    /**
     * @see $value
     * @ignore
     */
    protected function get_value()
    {
        return $this->_value;
    }

    /**
     * @see $buffer
     * @ignore
     */
    protected function get_buffer()
    {
        return $this->_buffer;
    }

    /**
     * @see $start
     * @ignore
     */
    protected function get_start()
    {
        return $this->_start;
    }

    /**
     * @see $stop
     * @ignore
     */
    protected function get_stop()
    {
        return $this->_stop;
    }

    /**
     * @see $length
     * @ignore
     */
    protected function get_length()
    {
        return $this->_stop - $this->_start;
    }

    /**
     * @see $line
     * @ignore
     */
    protected function get_line()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($line, $column) = Token::lineAndColumnOf($this->_buffer, $this->_start);

        return $line;
    }

    /**
     * @see $column
     * @ignore
     */
    protected function get_column()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($line, $column) = Token::lineAndColumnOf($this->_buffer, $this->_start);

        return $column;
    }

    /**
     * @see $input
     * @ignore
     */
    protected function get_input()
    {
        return is_string($this->buffer)
            ? mb_substr($this->buffer, $this->start, $this->stop - $this->start)
            : array_slice($this->buffer, $this->start, $this->stop - $this->start);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'Token[' . self::positionString($this->buffer, $this->start) . ']: [' . implode(', ', $this->value) . ']';
    }

    /**
     * @param mixed $buffer
     * @param int $position
     *
     * @return string
     */
    public static function positionString($buffer, $position)
    {
        if (is_string($buffer)) {
            $lineAndColumn = Token::lineAndColumnOf($buffer, $position);
            return "{$lineAndColumn[0]}:{$lineAndColumn[1]}";
        } else {
            return "{$position}";
        }
    }
}
