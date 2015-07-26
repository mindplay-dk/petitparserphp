<?php

namespace petitparser;

/**
 * An immutable parse context.
 */
class Context
{
    /**
     * @var Buffer
     */
    private $_buffer;

    /**
     * @var int
     */
    private $_position;

    /**
     * @param Buffer $buffer
     * @param int $position
     */
    public function __construct(Buffer $buffer, $position)
    {
        $this->_buffer = $buffer;
        $this->_position = $position;
    }

    /**
     * @return Buffer the buffer we are working on.
     */
    public function getBuffer()
    {
        return $this->_buffer;
    }

    /**
     * @return int the current position in the buffer.
     */
    public function getPosition()
    {
        return $this->_position;
    }

    /**
     * Returns a result indicating a parse success.
     *
     * @param mixed $result
     * @param int $position
     *
     * @return Success
     */
    public function success($result, $position=null)
    {
        return new Success($this->_buffer, $position === null ? $this->_position : $position, $result);
    }

    /**
     * Returns a result indicating a parse failure.
     *
     * @param string $message
     * @param int $position
     *
     * @return Failure
     */
    public function failure($message, $position=null)
    {
        return new Failure($this->_buffer, $position === null ? $this->_position : $position, $message);
    }

    /**
     * @return string human readable description of the current context
     */
    public function __toString()
    {
        return "Context[{$this->toPositionString()}]";
    }

    /**
     * @return string line:column if the input is a string, otherwise the position.
     */
    public function toPositionString()
    {
        return Token::positionString($this->getBuffer(), $this->getPosition());
    }
}
