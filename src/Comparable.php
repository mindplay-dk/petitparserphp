<?php

declare(encoding='UTF-8');

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
