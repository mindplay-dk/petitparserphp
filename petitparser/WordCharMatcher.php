<?php

namespace petitparser;

class WordCharMatcher extends CharMatcher
{
    /**
     * @param int|string $value
     *
     * @return bool
     */
    public function match($value)
    {
        if (is_int($value)) {
            return ($value >= 65 && $value <= 90)
                || ($value >= 97 && $value <= 122)
                || ($value >= 48 && $value <= 57)
                || ($value === 95);
        }

        return false;
    }
}
