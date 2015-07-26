<?php

namespace petitparser;

/**
 * A greedy repeating parser, commonly seen in regular expression implementations. It
 * aggressively consumes as much input as possible and then backtracks to meet the
 * 'limit' condition.
 */
class GreedyRepeatingParser extends LimitedRepeatingParser
{
    public function parseOn(Context $context)
    {
        $current = $context;
        $elements = array();

        while (count($elements) < $this->_min) {
            $result = $this->_delegate->parseOn($current);

            if ($result->isFailure()) {
                return $result;
            }

            $elements[] = $result->getValue();
            $current = $result;
        }

        /** @var Result[] $contexts */
        $contexts = array($current);

        while ($this->_max === Parser::UNBOUNDED || count($elements) < $this->_max) {
            $result = $this->_delegate->parseOn($current);

            if ($result->isFailure()) {
                break;
            }

            $elements[] = $result->getValue();
            $contexts[] = $result;
            $current = $result;
        }

        while (true) {
            $last_context = $contexts[count($contexts)-1];

            $limit = $this->_limit->parseOn($last_context);

            if ($limit->isSuccess()) {
                return $last_context->success($elements);
            }

            if (count($elements) === 0) {
                break;
            }

            array_pop($contexts);
            array_pop($elements);

            if (count($contexts) === 0) {
                break;
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
        return new GreedyRepeatingParser($this->_delegate, $this->_limit, $this->_min, $this->_max);
    }
}
