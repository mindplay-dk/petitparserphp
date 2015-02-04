<?php

namespace petitparser;

class WordCharMatcher extends CharMatcher
{
    /**
     * @param int $value
     *
     * @return bool
     */
    public function match($value)
    {
        return ($value >= 65 && $value <= 90)
            || ($value >= 97 && $value <= 122)
            || ($value >= 48 && $value <= 57)
            || ($value === 95);
    }
}
