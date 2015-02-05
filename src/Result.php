<?php

namespace petitparser;

/**
 * An immutable parse result.
 *
 * @property-read mixed  $value   Returns the parse result of the current context.
 * @property-read string $message Returns the parse message of the current context.
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
     * @see $isSuccess
     * @ignore
     */
    protected function get_isSuccess()
    {
        return false;
    }

    /**
     * @see $isFailure
     * @ignore
     */
    protected function get_isFailure()
    {
        return false;
    }

    /**
     * @see $value
     * @return mixed
     * @ignore
     */
    abstract protected function get_value();

    /**
     * @see $message
     * @return string
     * @ignore
     */
    abstract protected function get_message();
}
