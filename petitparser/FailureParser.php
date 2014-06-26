<?php

namespace petitparser;

class FailureParser extends Parser
{
    /**
     * @var string
     */
    protected $_message;

    /**
     * @param string $message
     */
    public function __construct($message)
    {
        $this->_message = $message;
    }

    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        return $context->failure($this->_message);
    }

    public function __toString()
    {
        return parent::__toString() . '[' . $this->_message . ']';
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new FailureParser($this->_message);
    }

    /**
     * @param Parser $other
     *
     * @return bool
     */
    public function equalProperties(Parser $other)
    {
        return parent::equalProperties($other)
            && $other instanceof self
            && $this->_message === $other->_message;
    }
}
