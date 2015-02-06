PetitParserPHP
==============

Lukas Renggli's PetitParser ported to PHP by Rasmus Schultz.

[![Build Status](https://travis-ci.org/mindplay-dk/petitparserphp.png)](https://travis-ci.org/mindplay-dk/petitparserphp)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mindplay-dk/petitparserphp/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/petitparserphp/?branch=master)

[![Code Coverage](https://scrutinizer-ci.com/g/mindplay-dk/petitparserphp/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/petitparserphp/?branch=master)


Status and scope of this port
-----------------------------

- API (method-names, etc.) is *unstable and subject to change* pending the first tagged release.

- Most of the core and all core unit tests have been ported and are passing.

- Fully type-hinted with php-doc, passing all inspections in PhpStorm.

- Actual grammars and parsers (XML, Dart, Lisp, etc.) have not been ported, and isn't planned.

- "definition.dart" cannot be directly ported due to different language mechanics.

- The port is up-to-date with the following version of PetitParserDart:

  https://github.com/renggli/PetitParserDart/commit/626f2c95c8157d8e80ef101b5c80cf6b7beef183

- Work in progress targeting the following version:

  https://github.com/renggli/PetitParserDart/commit/3ba18393343977780a155277310dddfa2c3caa4d


Contributions
-------------

Code adheres to PSR-2.

One class/interface per file.

Grammars and parsers belong in separate projects/packages, not in this one.
