<?php

namespace petitparser;

class UppercaseCharacterPredicate extends CharacterPredicate
{
    /**
     * @param int $value
     *
     * @return bool
     */
    public function test($value)
    {
        return $value >= 65 && $value <= 90;
    }
}
