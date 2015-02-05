<?php

namespace petitparser;

class WordCharacterPredicate extends CharacterPredicate
{
    /**
     * @param int $value
     *
     * @return bool
     */
    public function test($value)
    {
        return ($value >= 65 && $value <= 90)
            || ($value >= 97 && $value <= 122)
            || ($value >= 48 && $value <= 57)
            || ($value === 95);
    }
}
