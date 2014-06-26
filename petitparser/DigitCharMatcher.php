<?php

namespace petitparser;

class DigitCharMatcher extends CharMatcher
{
    /**
     * @param int $value
     *
     * @return bool
     */
    public function match($value)
    {
        return $value >= 48 && $value <= 57;
    }
}
