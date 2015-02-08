<?php

namespace petitparser;

/**
 * Example JSON parser.
 */
class JsonParser extends CompositeParser
{
    protected function initialize()
    {
        $this->defineGrammar();
        $this->defineActions();
    }

    static $escapeTable = array(
        "\\" => "\\",
        "/"  => "/",
        '"'  => '"',
        "b"  => "\b",
        "f"  => "\f",
        "n"  => "\n",
        "r"  => "\r",
        "t"  => "\t",
    );

    private function defineGrammar()
    {
        $this->def('start', $this->ref('value')->end_());

        $this->def('array',
            char('[')
                ->trim()
                ->seq($this->ref('elements')->optional())
                ->seq(char(']')->trim()));

        $this->def('elements',
            $this->ref('value')->separatedBy(char(',')->trim(), false));

        $this->def('members',
            $this->ref('pair')->separatedBy(char(',')->trim(), false));

        $this->def('object',
            char('{')
                ->trim()
                ->seq($this->ref('members')->optional())
                ->seq(char('}')->trim()));

        $this->def('pair',
            $this->ref('stringToken')
                ->seq(char(':')->trim())
                ->seq($this->ref('value')));

        $this->def('value',
            $this->ref('stringToken')
                ->or_($this->ref('numberToken'))
                ->or_($this->ref('object'))
                ->or_($this->ref('array'))
                ->or_($this->ref('trueToken'))
                ->or_($this->ref('falseToken'))
                ->or_($this->ref('nullToken')));

        $this->def('trueToken', string('true')->flatten()->trim());
        $this->def('falseToken', string('false')->flatten()->trim());
        $this->def('nullToken', string('null')->flatten()->trim());
        $this->def('stringToken', $this->ref('stringPrimitive')->flatten()->trim());
        $this->def('numberToken', $this->ref('numberPrimitive')->flatten()->trim());

        $this->def('characterPrimitive',
            $this->ref('characterEscape')
                ->or_($this->ref('characterOctal'))
                ->or_($this->ref('characterNormal')));

        $this->def('characterEscape',
            char("\\")->seq(anyIn(array_keys(JsonParser::$escapeTable))));

        $this->def('characterNormal',
            anyIn("\"\\")->neg());

        $this->def('characterOctal',
            string("\\u")->seq(pattern("0-9A-Fa-f")->times(4)->flatten()));

        $this->def('numberPrimitive',
            char('-')->optional()
                ->seq(char('0')->or_(digit()->plus()))
                ->seq(char('.')->seq(digit()->plus())->optional())
                ->seq(anyIn('eE')->seq(anyIn('-+')->optional())->seq(digit()->plus())->optional()));

        $this->def('stringPrimitive',
            char('"')
                ->seq($this->ref('characterPrimitive')->star())
                ->seq(char('"')));
    }

    private function defineActions()
    {
        $this->action(
            'array',
            function ($each) {
                return $each[1] !== null ? $each[1] : array();
            }
        );

        $this->action(
            'object',
            function ($each) {
                $result = array();
                if ($each[1] !== null) {
                    foreach ($each[1] as $element) {
                        $result[$element[0]] = $element[2];
                    }
                }
                return $result;
            }
        );

        $this->action(
            'trueToken',
            function ($each) {
                return true;
            }
        );

        $this->action(
            'falseToken',
            function ($each) {
                return false;
            }
        );

        $this->action(
            'nullToken',
            function ($each) {
                return null;
            }
        );

        $self = $this;

        $this->redef(
            'stringToken',
            function (Parser $parser) use ($self) {
                return $self->ref('stringPrimitive')->trim();
            }
        );

        $this->action(
            'numberToken',
            function ($each) {
                $float = floatval($each);
                $int = intval($float);

                if (($float == $int) && (false === strpos($each, '.'))) {
                    return $int;
                } else {
                    return $float;
                }
            }
        );

        $this->action(
            'stringPrimitive',
            function ($each) {
                return implode('', $each[1]);
            }
        );

        $this->action(
            'characterEscape',
            function ($each) {
                return JsonParser::$escapeTable[$each[1]];
            }
        );

        $this->action(
            'characterOctal',
            function ($each) {
                throw new \Exception('Support for octal characters not implemented');
            }
        );
    }
}
