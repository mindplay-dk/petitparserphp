<?php

namespace petitparser;

/**
 * Internal abstract character matcher class.
 */
abstract class CharMatcher
{
    /**
     * @param int $value
     *
     * @return bool
     */
    abstract public function match($value);
}
