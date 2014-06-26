<?php

namespace petitparser;

class UppercaseCharMatcher extends CharMatcher
{
    /**
     * @param int|string $value
     *
     * @return bool
     */
    public function match($value)
    {
        return is_int($value) && $value >= 65 && $value <= 90;
    }
}
