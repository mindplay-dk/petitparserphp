<?php

namespace petitparser;

/**
 * A parser that succeeds only at the end of the input.
 */
class EndOfInputParser extends DelegateParser
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

        if ($result->isFailure() || $result->getPosition() == $result->getBuffer()->getLength()) {
            return $result;
        }

        return $result->failure($this->_message, $result->getPosition());
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
        return new EndOfInputParser($this->_delegate, $this->_message);
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
