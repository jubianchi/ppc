# Matching lists

In this short tutorial, you will learn how to easily parse a separated list of items.

The goal is to highlight the composability of parsers and how it can help you build powerful things that you can easily 
change to handle more cases.

## The list

For this tutorial, let's take a list which looks like JavaScript arrays: `[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]`. For the sake
of simplicity here we will only consider a list of single-digits from 0 to 9.

### The simplest form

For the first example, let's try to parse the list as is:

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Stream\Char;
use function jubianchi\PPC\Combinators\{opt, separated, seq};
use function jubianchi\PPC\Parsers\{char, eos, regex};

$open = char('[');
$close = char(']');
$comma = char(',');
$space = char(' ');
$digit = regex('/[0-9]/');

$separator = seq($comma, opt($space));
$list = seq($open, separated($separator, $digit), $close, eos());

$stream = new Char('[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]');
$result = $list($stream);

assert($result instanceof Result\Success);
```

### Allowing trailing separator

Now let's try to handle trailing separators, just like JavaScript arrays. Our parser will have to allow things like
`[0, 1, 2, 3, ]`:

```phps
$list = seq($open, seq(separated($separator, $digit), opt($separator)), $close, eos());
```

We are done!

### Allowing empty values

What about empty values? JavaScript allow to write things like `[0, 1, 2,, 3 ]`:

```phps
$list = seq($open, separated($separator, opt($digit)), $close, eos());
```

Done! Easy, right?

## Wrapping everything together

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Stream\Char;
use function jubianchi\PPC\Combinators\{opt, separated, seq};
use function jubianchi\PPC\Parsers\{char, eos, regex};

$open = char('[');
$close = char(']');
$comma = char(',');
$space = char(' ');
$digit = regex('/[0-9]/');

$separator = seq($comma, opt($space));
$list = seq($open, seq(separated($separator, opt($digit)), opt($separator)), $close, eos());

$stream = new Char('[0, 1, 2, , 4, 5,, 7, 8, 9,]');
$result = $list($stream);

assert($result instanceof Result\Success);
```

This tutorial did not cover shaping the result: this is left as an exercice for you.
