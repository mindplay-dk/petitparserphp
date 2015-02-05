<?php

namespace petitparser;

/**
 * An immutable parse result in case of a failed parse.
 */
class Failure extends Result
{
    /**
     * @var string
     */
    private $_message;

    /**
     * @param Buffer $buffer
     * @param int $position
     * @param string $message
     */
    public function __construct(Buffer $buffer, $position, $message)
    {
        parent::__construct($buffer, $position);
        $this->_message = $message;
    }

    /**
     * @see $isFailure
     * @ignore
     */
    protected function get_isFailure()
    {
        return true;
    }

    /**
     * @see $value
     * @ignore
     */
    protected function get_value()
    {
        throw new ParserError($this);
    }

    /**
     * @see $message
     * @ignore
     */
    protected function get_message()
    {
        return $this->_message;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "Failure[{$this->toPositionString()}]: {$this->_message}";
    }
}
