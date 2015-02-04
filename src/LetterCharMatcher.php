<?php

namespace petitparser;

class LetterCharMatcher extends CharMatcher
{
    public function match($value)
    {
        return ($value >= 65 && $value <= 90)
            || ($value >= 97 && $value <= 122);
    }
}
