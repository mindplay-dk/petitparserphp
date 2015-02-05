<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * The not-predicate, a parser that succeeds whenever its delegate does not,
 * but consumes no input [Parr 1994, 1995].
 */
class NotParser extends DelegateParser
{
    /**
     * @var string
     */
    protected $_message;

    /**
     * @param Parser $parser
     * @param string $message
     */
    public function __construct(Parser $parser, $message)
    {
        parent::__construct($parser);

        $this->_message = $message;
    }

    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        $result = $this->_delegate->parseOn($context);

        if ($result->isFailure) {
            return $context->success(null);
        } else {
            return $context->failure($this->_message);
        }
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
        return new NotParser($this->_delegate, $this->_message);
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
            && $this->_message === $other->_message;
    }
}
