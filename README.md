PetitParserPHP
==============

Lukas Renggli's PetitParser ported to PHP by Rasmus Schultz.

[![Build Status](https://travis-ci.org/mindplay-dk/petitparserphp.png)](https://travis-ci.org/mindplay-dk/petitparserphp)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mindplay-dk/petitparserphp/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/petitparserphp/?branch=master)

[![Code Coverage](https://scrutinizer-ci.com/g/mindplay-dk/petitparserphp/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/petitparserphp/?branch=master)


Status of this port
-------------------

- The port is up-to-date with release 1.3.4 of dart-petitparser.

- The core has been ported, and all core unit tests have been ported and are passing.

- The previous version of the JSON parser has been ported along with the tests. (newer version
  is based on GrammarParser which has not been ported, because it uses language mechanics that
  aren't applicable to PHP.)

- **API (method-names, etc.) are still subject to change** pending the first tagged release.


Scope of this port
------------------

- Other grammars and parsers (XML, Dart, Lisp, etc.) have not been ported, and isn't planned.

- "definition.dart" cannot be directly ported due to different language mechanics.


Contributions
-------------

Code adheres to PSR-1, PSR-2 and PSR-4.

Grammars and parsers belong in separate projects/packages, not in this one.

Source code is fully type-hinted with php-doc, passing all inspections in Php Storm.
