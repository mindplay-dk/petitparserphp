<?php

namespace petitparser;

/**
 * A token represents a parsed part of the input stream. The token holds
 * the parsed input, the input buffer, and the start and stop position
 * in the input buffer.
 */
class Token implements Comparable
{
    /**
     * @var mixed
     */
    private $_value;

    /**
     * @var Buffer
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
     * @param Buffer $buffer
     * @param int $start
     * @param int $stop
     */
    public function __construct($value, Buffer $buffer, $start, $stop)
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
     * @param Buffer $buffer
     * @param int $position
     * @return array [int $line, int $column]
     */
    public static function lineAndColumnOf(Buffer $buffer, $position)
    {
        $line = 1;
        $offset = 0;

        foreach (Token::newLineParser()->token()->matchesSkipping($buffer) as $token) {
            /** @var Token $token */
            if ($position < $token->getStop()) {
                return array($line, $position - $offset + 1);
            }
            $line += 1;
            $offset = $token->getStop();
        }

        return array($line, $position - $offset + 1);
    }

    /**
     * @param mixed $other
     *
     * @return bool
     */
    public function isEqualTo($other)
    {
        return $other instanceof Token
            && $this->_value === $other->_value
            && $this->_start === $other->_start
            && $this->_stop === $other->_stop;
    }

    /**
     * @return mixed the parsed value.
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @return Buffer the input buffer.
     */
    public function getBuffer()
    {
        return $this->_buffer;
    }

    /**
     * @return int the start position in the input buffer.
     */
    public function getStart()
    {
        return $this->_start;
    }

    /**
     * @return int the stop position in the input buffer.
     */
    public function getStop()
    {
        return $this->_stop;
    }

    /**
     * @return int the length of the token.
     */
    public function getLength()
    {
        return $this->_stop - $this->_start;
    }

    /**
     * @return int the line number of the token.
     */
    public function getLine()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($line, $column) = Token::lineAndColumnOf($this->_buffer, $this->_start);

        return $line;
    }

    /**
     * @return int the column number of this token.
     */
    public function getColumn()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($line, $column) = Token::lineAndColumnOf($this->_buffer, $this->_start);

        return $column;
    }

    /**
     * @return string the consumed input of the token.
     */
    public function getInput()
    {
        return $this->getBuffer()->slice($this->getStart(), $this->getStop())->getString();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'Token[' . self::positionString($this->getBuffer(), $this->getStart()) . ']: [' . implode(', ', $this->getValue()) . ']';
    }

    /**
     * @param Buffer $buffer
     * @param int $position
     *
     * @return string
     */
    public static function positionString(Buffer $buffer, $position)
    {
        $lineAndColumn = Token::lineAndColumnOf($buffer, $position);

        return "{$lineAndColumn[0]}:{$lineAndColumn[1]}";
    }
}
