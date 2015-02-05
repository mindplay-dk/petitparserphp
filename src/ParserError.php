<?php

namespace petitparser;

/**
 * An exception raised in case of a parse error.
 */
class ParserError extends Error
{
    /**
     * @var Failure
     */
    private $_failure;

    /**
     * @param Failure $failure
     */
    public function __construct(Failure $failure)
    {
        $this->_failure = $failure;

        parent::__construct($this->__toString());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->_failure->message} at {$this->_failure->toPositionString()}";
    }
}
