<?php

namespace petitparser;

/**
 * An immutable parse result in case of a successful parse.
 */
class Success extends Result
{
    /**
     * @var mixed
     */
    private $_value;

    /**
     * @param mixed $buffer
     * @param int $position
     * @param mixed $value
     */
    public function __construct($buffer, $position, $value)
    {
        $this->_value = $value;

        parent::__construct($buffer, $position);
    }

    /**
     * @see $isSuccess
     * @ignore
     */
    protected function get_isSuccess()
    {
        return true;
    }

    /**
     * @see $value
     * @ignore
     */
    protected function get_value()
    {
        return $this->_value;
    }

    /**
     * @see $message
     * @ignore
     */
    protected function get_message()
    {
        return null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "Success[{$this->toPositionString()}]: {$this->_value}";
    }
}
