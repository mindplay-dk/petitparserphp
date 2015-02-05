<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * A builder that allows the simple definition of expression grammars with
 * prefix, postfix, and left- and right-associative infix operators.
 *
 * The following code creates the empty expression builder:
 *
 *     var builder = new ExpressionBuilder();
 *
 * Then we define the operator-groups in descending precedence. The highest
 * precedence have the literal numbers themselves:
 *
 *     builder.group()
 *       ..primitive(digit().plus()
 *         .seq(char('.').seq(digit().plus()).optional())
 *         .flatten().trim().map((a) => double.parse(a)));
 *
 * Then come the normal arithmetic operators. Note, that the action blocks receive
 * both, the terms and the parsed operator in the order they appear in the parsed
 * input.
 *
 *     // negation is a prefix operator
 *     builder.group()
 *       ..prefix(char('-').trim(), (op, a) => -a);
 *
 *     // power is right-associative
 *     builder.group()
 *       ..right(char('^').trim(), (a, op, b) => math.pow(a, b));
 *
 *     // multiplication and addition is left-associative
 *     builder.group()
 *       ..left(char('*').trim(), (a, op, b) => a * b)
 *       ..left(char('/').trim(), (a, op, b) => a / b);
 *     builder.group()
 *       ..left(char('+').trim(), (a, op, b) => a + b)
 *       ..left(char('-').trim(), (a, op, b) => a - b);
 *
 * Finally we can build the parser:
 *
 *     var parser = builder.build();
 *
 * After executing the above code we get an efficient parser that correctly
 * evaluates expressions like:
 *
 *     parser.parse('-8');      // -8
 *     parser.parse('1+2*3');   // 7
 *     parser.parse('1*2+3');   // 5
 *     parser.parse('8/4/2');   // 2
 *     parser.parse('2^2^3');   // 256
 */
class ExpressionBuilder
{
    /**
     * @var ExpressionGroup[]
     */
    protected $_groups = array();

    /**
     * Creates a new group of operators that share the same priority.
     *
     * @param callable|null optional closure of the form: void function(ExpressionGroup $group)
     *
     * @return ExpressionGroup
     */
    public function group($function = null)
    {
        $group = new ExpressionGroup();

        if ($function !== null) {
            call_user_func($function, $group);
        }

        $this->_groups[] = $group;

        return $group;
    }

    /**
     * Builds the expression parser.
     */
    public function build()
    {
        $parser = failure('Highest priority group should define a primitive parser.');

        foreach ($this->_groups as $group) {
            $parser = $group->_build($parser);
        }

        return $parser;
    }
}
