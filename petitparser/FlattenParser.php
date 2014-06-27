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
            $output = $context->buffer->slice($context->position, $result->position);

            return $result->success($output->string);
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
