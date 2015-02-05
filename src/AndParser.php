<?php

namespace petitparser;

/**
 * The and-predicate, a parser that succeeds whenever its delegate does, but
 * does not consume the input stream [Parr 1994, 1995].
 */
class AndParser extends DelegateParser
{
    public function __construct(Parser $parser)
    {
        parent::__construct($parser);
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
            return $context->success($result->value);
        } else {
            return $result;
        }
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new AndParser($this->_delegate);
    }
}
