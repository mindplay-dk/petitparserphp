<?php

namespace petitparser;

class EpsilonParser extends Parser
{
    /**
     * @var mixed
     */
    protected $_result;

    /**
     * @param mixed $result
     */
    public function __construct($result)
    {
        $this->_result = $result;
    }

    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        return $context->success($this->_result);
    }

    /**
     * @inheritdoc
     */
    public function copy()
    {
        return new EpsilonParser($this->_result);
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
            && $this->_result == $other->_result;
    }
}
