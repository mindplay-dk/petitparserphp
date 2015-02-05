<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * A parser that performs a transformation with a given function on the
 * successful parse result of the delegate.
 */
class ActionParser extends DelegateParser
{
    /**
     * @var callable
     */
    protected $_function;

    /**
     * @param Parser $parser
     * @param callable $function
     */
    public function __construct(Parser $parser, $function)
    {
        parent::__construct($parser);

        $this->_function = $function;
    }

    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        $result = $this->_delegate->parseOn($context);

        if ($result->isSuccess) {
            return $result->success(call_user_func($this->_function, $result->value));
        } else {
            return $result;
        }
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new ActionParser($this->_delegate, $this->_function);
    }

    /**
     * @param Parser $other
     *
     * @return bool
     */
    public function hasEqualProperties(Parser $other)
    {
        return parent::hasEqualProperties($other)
            && $other instanceof self
            && $this->_function === $other->_function;
    }
}
