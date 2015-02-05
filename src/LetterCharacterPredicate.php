<?php

namespace petitparser;

class LetterCharacterPredicate extends CharacterPredicate
{
    public function test($value)
    {
        return ($value >= 65 && $value <= 90)
            || ($value >= 97 && $value <= 122);
    }
}
