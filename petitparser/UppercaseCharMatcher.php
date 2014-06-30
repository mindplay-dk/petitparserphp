<?php

namespace petitparser;

class UppercaseCharMatcher extends CharMatcher
{
    /**
     * @param int $value
     *
     * @return bool
     */
    public function match($value)
    {
        return $value >= 65 && $value <= 90;
    }
}
