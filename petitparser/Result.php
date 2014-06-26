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
     * @param mixed $buffer
     * @param int   $position
     */
    public function __construct($buffer, $position)
    {
        parent::__construct($buffer, $position);
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
