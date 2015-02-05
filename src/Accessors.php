<?php

namespace petitparser;

use RuntimeException;

/**
 * Abstract base-class for classes with get/set-accessor methods.
 */
abstract class Accessors
{
    /**
     * @param $name
     *
     * @return mixed
     * @throws RuntimeException
     *
     * @ignore
     */
    public function __get($name)
    {
        $method = "get_$name";

        if (false === method_exists($this, $method)) {
            $class = get_class($this);

            throw new RuntimeException("undefined property {$class}::{$name} or accessor {$class}::{$method}()");
        }

        return $this->$method();
    }

    /**
     * @param $name
     * @param $value
     *
     * @throws RuntimeException
     *
     * @ignore
     */
    public function __set($name, $value)
    {
        $method = "set_$name";

        if (false === method_exists($this, $method)) {
            $class = get_class($this);

            throw new RuntimeException("undefined property {$class}::{$name} or accessor {$class}::{$method}()");
        }

        $this->$method($value);
    }
}
