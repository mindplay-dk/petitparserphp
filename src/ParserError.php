<?php

namespace petitparser;

use RuntimeException;

/**
 * An exception raised in case of a parse error.
 *
 * @property-read Failure $failure
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
     * @ignore
     */
    public function __get($name)
    {
        if ($name === 'failure') {
            return $this->_failure;
        } else {
            throw new RuntimeException("undefined property {$name}");
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->_failure->getMessage()} at {$this->_failure->toPositionString()}";
    }
}
