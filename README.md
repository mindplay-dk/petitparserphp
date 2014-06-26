PetitParserPHP
==============

Lukas Renggli's PetitParser ported to PHP by Rasmus Schultz.


Status and scope of this port
-----------------------------

- Most of the core and all core unit tests have been ported and are passing.

- Fully type-hinted with php-doc, passing all inspections in PhpStorm.

- Actual grammars and parsers (XML, Dart, Lisp, etc.) have not been ported.

- The port is up-to-date with the following version of PetitParserDart:

  https://github.com/renggli/PetitParserDart/commit/626f2c95c8157d8e80ef101b5c80cf6b7beef183


Contributions
-------------

Code adheres to PSR-2.

One class/interface per file.

Grammars and parsers belong in separate projects/packages, not in this one.
