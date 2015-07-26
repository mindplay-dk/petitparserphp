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

        if ($result->isSuccess()) {
            $output = $context->getBuffer()->slice($context->getPosition(), $result->getPosition());

            return $result->success($output->getString());
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
