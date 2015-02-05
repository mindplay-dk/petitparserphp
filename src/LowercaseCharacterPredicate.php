<?php

declare(encoding='UTF-8');

namespace petitparser;

class LowercaseCharacterPredicate extends CharacterPredicate
{
    public function test($value)
    {
        return $value >= 97 && $value <= 122;
    }
}
