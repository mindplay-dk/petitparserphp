<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * An immutable parse context.
 *
 * @property-read Buffer $buffer The buffer we are working on.
 * @property-read int $position The current position in the buffer.
 * @property-read bool $isSuccess Returns [true] if this result indicates a parse success.
 * @property-read bool $isFailure Returns [true] if this result indicates a parse failure.
 */
class Context extends Accessors
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
     * @see $buffer
     * @ignore
     */
    protected function get_buffer()
    {
        return $this->_buffer;
    }

    /**
     * @see $position
     * @ignore
     */
    protected function get_position()
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
        return Token::positionString($this->buffer, $this->position);
    }
}
