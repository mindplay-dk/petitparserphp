<?php

namespace petitparser;

/**
 * Internal abstract character matcher class.
 */
abstract class CharacterPredicate
{
    /**
     * @param int $value 32-bit Unicode character code
     *
     * @return bool
     */
    abstract public function test($value);
}
