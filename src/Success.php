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
     * @param Buffer $buffer
     * @param int $position
     * @param mixed $value
     */
    public function __construct(Buffer $buffer, $position, $value)
    {
        $this->_value = $value;

        parent::__construct($buffer, $position);
    }

    /**
     * @inheritdoc
     */
    public function isSuccess()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
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
