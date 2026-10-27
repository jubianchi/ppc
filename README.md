# PPC

> A parser combinator library for PHP

Simple to use & extend • Fast & lightweight • Reliable

## Introduction

PPC stands for **P**HP **P**arser **C**ombinator. What an obvious name for such a library!

As its name tells us, PPC is just another parser combinator library with a clear goal: make writing efficient parsers a
breeze. Writing parser with PPC does not require you to know how parser combinators works internally nor it requires you
to learn a complex object-oriented API.

PPC is a set of functions which you will love to compose to build complex parsers! Parsers are plain closures: they
never copy the input, and backtracking costs nothing.

## Installation

PPC requires PHP `8.5` and the [Multibyte String](https://www.php.net/manual/en/book.mbstring.php) (`mbstring`)
extension.

```bash
composer require "jubianchi/ppc" "dev-master"
```

## Example parser

Here is a quick example demonstrating how easy it is to write a parser:

```php
<?php

use jubianchi\PPC\SyntaxError;

use function jubianchi\PPC\{char, eos, map, opt, parse, regex, separated, seq};

$digit = regex('/[0-9]/') |> map(intval(...));
$separator = seq(char(','), opt(char(' ')));
$list = seq(char('['), separated($separator, $digit), char(']'), eos())
    |> map(static fn (array $values): array => $values[1]);

assert(parse($list, '[0, 1, 2]') === [0, 1, 2]);

try {
    parse($list, '[0, 1 2]');
} catch (SyntaxError $error) {
    assert($error->getMessage() === 'Expected "," or "]", got " " at line 1, column 6');
}
```

Easy, right? Be sure to read the [documentation](https://jubianchi.github.io/ppc) to understand how it works.
