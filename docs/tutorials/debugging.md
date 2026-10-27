# Debugging

In this tutorial, you will learn how to use the [`debug`](/reference.md#debug) modifier to get a clear view of what's
happening in your parsers, and the [`label`](/reference.md#label) modifier to get clearer error messages.

As an example, we will use a snippet from the [Your first parser](/tutorials/your-first-parser.md) tutorial.

## The example

Let's take the date part of the date and time parser and feed it with a date which is not zero-padded:

```phps
<?php

use function jubianchi\PPC\{char, parse, regex, seq};

$twoDigits = regex('/[0-9]{2}/');
$date = seq(regex('/[0-9]{4}/'), char('-'), $twoDigits, char('-'), $twoDigits);

parse($date, '2020-7-21');
// SyntaxError: Expected /[0-9]{2}/, got "7" at line 1, column 6
```

The error message tells us where the parser failed, but not which part of the date it was trying to match: the month
and the day use the same parser.

## Tracing parsers

The `debug` modifier traces each run of the parser it wraps: where it starts, then what it matched or why it failed.
It takes a name, used to prefix each line, and the function receiving the lines. Any function accepting a string will
do, such as `error_log(...)`:

<!-- tabs:start -->

### ** Parser **

```php
<?php

use jubianchi\PPC\SyntaxError;

use function jubianchi\PPC\{char, debug, parse, regex, seq};

$write = static function (string $line): void {
    echo $line, PHP_EOL;
};

$twoDigits = regex('/[0-9]{2}/');
$date = seq(
    regex('/[0-9]{4}/') |> debug('year', $write),
    char('-'),
    $twoDigits |> debug('month', $write),
    char('-'),
    $twoDigits |> debug('day', $write),
) |> debug('date', $write);

try {
    parse($date, '2020-7-21');
} catch (SyntaxError) {
}
```

### ** Output **

```
date: start at line 1, column 1
year: start at line 1, column 1
year: matched "2020" up to line 1, column 5
month: start at line 1, column 6
month: Expected /[0-9]{2}/, got "7" at line 1, column 6
date: Expected /[0-9]{2}/, got "7" at line 1, column 6
```

<!-- tabs:end -->

The trace shows the parsers in the order they run, nested parsers appearing between the start and the end of their
parent: we now know the month is the culprit.

`debug` returns the parser unchanged: you can add it anywhere while investigating, and remove it once you are done.

## Labelling parsers

Regular expressions do not make the best error messages. The `label` modifier replaces what a parser expects in error
messages, as long as it fails without consuming anything:

```php
<?php

use jubianchi\PPC\SyntaxError;

use function jubianchi\PPC\{char, label, parse, regex, seq};

$twoDigits = regex('/[0-9]{2}/');
$date = seq(
    regex('/[0-9]{4}/') |> label('year'),
    char('-'),
    $twoDigits |> label('month'),
    char('-'),
    $twoDigits |> label('day'),
);

try {
    parse($date, '2020-7-21');
} catch (SyntaxError $error) {
    assert($error->getMessage() === 'Expected month, got "7" at line 1, column 6');
}
```

Labels are kept in the final error message: they make your parsers easier to debug, and their errors easier to read for
your users.
