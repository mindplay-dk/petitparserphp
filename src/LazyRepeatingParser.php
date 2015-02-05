<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * A lazy repeating parser, commonly seen in regular expression implementations. It
 * limits its consumption to meet the 'limit' condition as early as possible.
 */
class LazyRepeatingParser extends LimitedRepeatingParser
{
    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        $current = $context;
        $elements = array();

        while (count($elements) < $this->_min) {
            $result = $this->_delegate->parseOn($current);

            if ($result->isFailure) {
                return $result;
            }

            $elements[] = $result->value;
            $current = $result;
        }

        while (true) {
            $limit = $this->_limit->parseOn($current);

            if ($limit->isSuccess) {
                return $current->success($elements);
            } else {
                if ($this->_max != Parser::UNBOUNDED && length($elements) >= $this->_max) {
                    break;
                }

                $result = $this->_delegate->parseOn($current);

                if ($result->isFailure) {
                    break;
                }

                $elements[] = $result->value;

                $current = $result;
            }
        }

        /** @noinspection PhpUndefinedVariableInspection */
        return $limit;
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new LazyRepeatingParser($this->_delegate, $this->_limit, $this->_min, $this->_max);
    }
}
