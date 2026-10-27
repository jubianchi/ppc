# Consuming the whole input

In this tutorial, you will learn how to ensure your parser is able to consume an input until its end.

Sometimes it is important to make sure the parser consumes the whole input. Let's take a look at the
[previous tutorial](/tutorials/your-first-parser.md) example where you built a working date and time parser. This parser
is able to parse a ISO8601 compliant date until its second component. It does not know how to handle the time zone part.

What you want here is to be sure that the parser consumed the whole input or failed.

Let's go!

## The parser

Here is the parser we ended up with, without the mapping of its value:

```phps
<?php

use function jubianchi\PPC\{char, map, parse, regex, seq};

$twoDigits = regex('/[0-9]{2}/') |> map(intval(...));
$fourDigits = regex('/[0-9]{4}/') |> map(intval(...));

$dateTime = seq($fourDigits, char('-'), $twoDigits, char('-'), $twoDigits, char('T'), $twoDigits, char(':'), $twoDigits, char(':'), $twoDigits);
```

As you may know, a ISO8601 compliant date may end with a `Z` meaning the date and time are UTC based. Let's see if the
parser takes care of this:

```phps
parse($dateTime, '2020-07-21T17:35:00Z');
```

No error: the parser stops after the seconds and simply ignores the `Z`. In other words, it did not reach the end of the
input.

### EOS

PPC provides a dedicated parser, [`eos`](/reference.md#eos), which goal is to match the end of the input. Fixing our
date and time parser should be easy:

```phps
use function jubianchi\PPC\eos;

$complete = seq($dateTime, eos());
```

EZPZ, right?

Let's see if the parser is now failing correctly:

```phps
parse($complete, '2020-07-21T17:35:00Z');
// SyntaxError: Expected end of input, got "Z" at line 1, column 20
```

As explained in the introduction of this tutorial, sometimes it is important to ensure the parser consumes the whole
input.

In the current example, this lets you know your parser, despite it's working, is not correct: it does not know how to
handle some part of the date and time string thus it can't reach the end of the input.

## Wrapping everything together

Here is the full code of the parser which is now able to handle the `Z` character and reach the end of the input. The
values of `Z` and `eos` are dropped by the final mapping.

```php
<?php

use jubianchi\PPC\SyntaxError;

use function jubianchi\PPC\{char, eos, map, parse, regex, seq};

$twoDigits = regex('/[0-9]{2}/') |> map(intval(...));
$fourDigits = regex('/[0-9]{4}/') |> map(intval(...));

$dateTime = seq($fourDigits, char('-'), $twoDigits, char('-'), $twoDigits, char('T'), $twoDigits, char(':'), $twoDigits, char(':'), $twoDigits, char('Z'), eos())
    |> map(static fn (array $parts): array => [
        'year' => $parts[0],
        'month' => $parts[2],
        'day' => $parts[4],
        'hour' => $parts[6],
        'minute' => $parts[8],
        'second' => $parts[10],
    ]);

assert(parse($dateTime, '2020-07-21T17:35:00Z')['year'] === 2020);

try {
    parse($dateTime, '2020-07-21T17:35:00Z+');
} catch (SyntaxError $error) {
    assert($error->getMessage() === 'Expected end of input, got "+" at line 1, column 21');
}
```

Again, this parser is not capable of parsing the time offset described in ISO8601: this is left as an exercise for you.
