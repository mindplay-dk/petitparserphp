<?php

declare(encoding='UTF-8');

namespace petitparser;

class DigitCharacterPredicate extends CharacterPredicate
{
    /**
     * @param int $value
     *
     * @return bool
     */
    public function test($value)
    {
        return $value >= 48 && $value <= 57;
    }
}
