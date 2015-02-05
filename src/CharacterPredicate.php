<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * Abstract character predicate class.
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
