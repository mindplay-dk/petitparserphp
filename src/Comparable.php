<?php

namespace petitparser;

interface Comparable
{
    /**
     * @param mixed $other
     *
     * @return bool
     */
    public function isEqualTo($other);
}
