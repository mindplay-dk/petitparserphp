<?php

namespace petitparser;

/**
 * A parser that answers a substring or sublist of the range its delegate
 * parses.
 */
class FlattenParser extends DelegateParser
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
            $output = is_string($context->buffer)
                ? mb_substr($context->buffer, $context->position, $result->position - $context->position)
                : array_slice($context->buffer, $context->position, $result->position - $context->position);

            return $result->success($output);
        } else {
            return $result;
        }
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new FlattenParser($this->_delegate);
    }
}
