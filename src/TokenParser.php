<?php

namespace petitparser;

/**
 * A parser that answers a token of the result its delegate parses.
 */
class TokenParser extends DelegateParser
{
    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        $result = $this->_delegate->parseOn($context);

        if ($result->isSuccess) {
            $token = new Token($result->value, $context->buffer, $context->position, $result->position);

            return $result->success($token);
        } else {
            return $result;
        }
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new TokenParser($this->_delegate);
    }
}
