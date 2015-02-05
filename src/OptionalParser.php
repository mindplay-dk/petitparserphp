<?php

namespace petitparser;

/**
 * A parser that optionally parsers its delegate, or answers nil.
 */
class OptionalParser extends DelegateParser
{
    /**
     * @var mixed
     */
    protected $_otherwise;

    /**
     * @param Parser $parser
     * @param mixed $otherwise
     */
    public function __construct(Parser $parser, $otherwise)
    {
        parent::__construct($parser);

        $this->_otherwise = $otherwise;
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
            return $result;
        } else {
            return $context->success($this->_otherwise);
        }
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new OptionalParser($this->_delegate, $this->_otherwise);
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
            && $this->_otherwise === $other->_otherwise;
    }
}
