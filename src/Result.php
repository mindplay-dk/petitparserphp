<?php

namespace petitparser;

/**
 * An immutable parse result.
 */
abstract class Result extends Context
{
    /**
     * @param Buffer $buffer
     * @param int   $position
     */
    public function __construct(Buffer $buffer, $position)
    {
        parent::__construct($buffer, $position);
    }

    /**
     * @return bool TRUE, if this result indicates a parse success.
     */
    public function isSuccess()
    {
        return false;
    }

    /**
     * @return bool TRUE, if this result indicates a parse failure.
     */
    public function isFailure()
    {
        return false;
    }

    /**
     * @return mixed the parse result of the current context.
     */
    abstract public function getValue();

    /**
     * @return string the parse message of the current context.
     */
    abstract public function getMessage();
}
