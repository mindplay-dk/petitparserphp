<?php

namespace petitparser;

/**
 * helper class to associate operators and actions
 *
 * @ignore
 */
class ExpressionResult
{
    /**
     * @var mixed
     */
    public $operator;

    /**
     * @var callable
     */
    public $action;

    /**
     * @param mixed $operator
     * @param callable $action
     */
    public function __construct($operator, $action)
    {
        $this->operator = $operator;
        $this->action = $action;
    }
}
