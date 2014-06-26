<?php

namespace petitparser;

class LowercaseCharMatcher extends CharMatcher
{
    public function match($value)
    {
        return $value >= 97 && $value <= 122;
    }
}
