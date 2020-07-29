# PPC

> A parser combinator library for PHP

Simple to use & extend • Fast & lightweight • Reliable

## Introduction

PPC stands for **P**HP **P**arser **C**ombinator. What an obvious name for such a library!

As its name tells us, PPC is just another parser combinator library with a clear goal: make writing efficient parsers a 
breeze. Writing parser with PPC does not require you to know how parser combinators works internally nor it requires you 
to learn a complex object-oriented API.

PPC is a set of functions which you will love to compose to build complex parsers!   

## Installation

PPC requires you to have at least PHP `7.4.0` and the [Multibyte String](https://www.php.net/manual/en/book.mbstring.php) 
(`mbstring`) extension enabled. You may want to check if your setup is correct using the following script: 

```bash
#!/usr/bin/env bash

echo "PHP version: $(php -v | head -n1 | grep -qE '7.([4-9]|1[0-9]).(0|[1-9][0-9]*)' && echo '✅' || echo '❌')"
echo "Multibyte String extension: $(php -m | grep -qE 'mbstring' && echo '✅' || echo '❌')"
```

Once everything is correct, choose the installation method that best feets your needs:

### Composer (CLI)

```bash
composer require "jubianchi/ppc" "dev-master"
```

### Composer (JSON)

```json
{
    "require": {
        "jubianchi/ppc": "dev-master"
    }
}
```

### Git

```bash
git clone "https://github.com/jubianchi/ppc.git"
git checkout "master"
```

## Example parser

Here is a quick example demonstrating how easy it is to write a parser:

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\{opt, separated, seq};
use function jubianchi\PPC\Parsers\{char, eos, regex};

$separator = seq(char(','), opt(char(' ')));
$list = seq(
    char('['), 
    seq(
        separated(
            $separator, 
            opt(regex('/[0-9]/'))
        ), 
        opt($separator)
    ), 
    char(']'), 
    eos()
);

$stream = new Stream('[0, 1, 2, , 4, 5,, 7, 8, 9,]');
$result = $list($stream);

assert($result instanceof Result\Success);
```

Easy, right? Be sure to read the [documentation](https://jubianchi.github.io/ppc) to undrstand how it works.
