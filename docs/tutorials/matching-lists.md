# Matching lists

In this short tutorial, you will learn how to easily parse a separated list of items.

The goal is to highlight the composability of parsers and how it can help you build powerful things that you can easily
change to handle more cases.

## The list

For this tutorial, let's take a list which looks like JavaScript arrays: `[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]`. For the sake
of simplicity here we will only consider a list of single-digits from 0 to 9.

### The simplest form

For the first example, let's try to parse the list as is, with the [`separated`](/reference.md#separated) combinator:

```php
<?php

use function jubianchi\PPC\{char, eos, opt, parse, regex, separated, seq};

$open = char('[');
$close = char(']');
$comma = char(',');
$space = char(' ');
$digit = regex('/[0-9]/');

$separator = seq($comma, opt($space));
$list = seq($open, separated($separator, $digit), $close, eos());

[, $digits] = parse($list, '[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]');

assert($digits === ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']);
```

### Allowing trailing separator

Now let's try to handle trailing separators, just like JavaScript arrays. Our parser will have to allow things like
`[0, 1, 2, 3, ]`. Since `separated` does not consume a trailing separator, an optional one can follow it:

```phps
$list = seq($open, separated($separator, $digit), opt($separator), $close, eos());
```

We are done!

### Allowing empty values

What about empty values? JavaScript allow to write things like `[0, 1, 2,, 3]`:

```phps
$list = seq($open, separated($separator, opt($digit)), $close, eos());
```

Missing values are `null`, the default value of [`opt`](/reference.md#opt). A trailing separator is now followed by an
empty value: the optional trailing separator is not needed anymore. Done! Easy, right?

## Wrapping everything together

```php
<?php

use function jubianchi\PPC\{char, enclosed, eos, map, opt, parse, regex, separated, seq, terminated};

$open = char('[');
$close = char(']');
$comma = char(',');
$space = char(' ');
$digit = regex('/[0-9]/') |> map(intval(...));

$separator = seq($comma, opt($space));
$list = terminated(enclosed($open, separated($separator, opt($digit)), $close), eos());

assert(parse($list, '[0, 1, 2, , 4, 5,, 7, 8, 9,]') === [0, 1, 2, null, 4, 5, null, 7, 8, 9, null]);
```
