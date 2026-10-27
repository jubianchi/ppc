# Your first parser

In this tutorial, you will learn how to build a simple parser yet allowing us to cover many core concepts.

The example parser you will build will be able to parse a date and time in the
[ISO8601](https://en.wikipedia.org/wiki/ISO_8601) format. To do so, you will use some [primitives](/reference.md#primitives),
[combinators](/reference.md#combinators) and [modifiers](/reference.md#modifiers).

Building a parser is usually done through some standard steps:

* Reading about what you are trying to parse;
* Identifying _tokens_;
* Defining parser for these _tokens_;
* Combining the parsers;
* Shaping the result;

We'll cover each of these steps in this tutorial. Let's go!

## ISO8601

The very first step of the process of building a parser is about documenting yourself on what you are trying to parse.
If you are building a parser for a format you are creating, things will be difficult because you will have to think
about the format and how to parse it at the same time. Hopefully, there is a standard format for dates and times.

The ISO8601 defines a standard calendar dates format. It is structured as follow:
* The year is written using four numeric characters (`YYYY`). It should be zero-padded;
* The month is written using two numeric characters (`MM`). It should be zero-padded;
* The day is written using two numeric characters (`DD`). It should be zero-padded;
* The date is written `YYYY-MM-DD`.

The ISO8601 also defines a standard extended time format. It is structured as follow:
* The hour is written using two numeric characters (`hh`). It should be zero-padded;
* The minute is written using two numeric characters (`mm`). It should be zero-padded;
* The second is written using two numeric characters (`ss`). It should be zero-padded;
* The time is written `hh:mm:ss`.

Finally, the ISO8601 defines a time zone designator. It is either:
* A `Z` character appended if the time is in UTC;
* An offset from UTC added after a `+` or `-` sign. It is structured as follow:
  * The hour is written using two numeric characters (`hh`). It should be zero-padded;
  * The minute is written using two numeric characters (`mm`). It should be zero-padded;
  * The offset is written `hh:mm`.

The date and time are combined together using a `T` character.

The first step is done, you now know exactly how a ISO8601 compliant date is structured and written. We can now start
writing the required parser.

## The parser

### Defining tokens

As you may have noticed in the previous paragraph, there are many parts that should be declared: the year, month, day,
hour, minute and second. The separators (`-`, `+`, `:`, `T` and `Z`) may also be declared. Let's call those parts
_tokens_.

```phps
<?php

use function jubianchi\PPC\{char, regex};

$year = regex('/[0-9]{4}/');
$month = regex('/[0-9]{2}/');
$day = regex('/[0-9]{2}/');
$hour = regex('/[0-9]{2}/');
$minute = regex('/[0-9]{2}/');
$second = regex('/[0-9]{2}/');
$dash = char('-');
$colon = char(':');
$t = char('T');
$z = char('Z');
```

Each number is matched by a single [`regex`](/reference.md#regex): matching a whole chunk at once is much faster than
combining parsers matching one digit at a time.

Ok, you now have all the _tokens_ you need but there is one thing we can make better: the same definition is repeated 5
times! Since parsers are plain values, let's reuse them:

```phps
$twoDigits = regex('/[0-9]{2}/');

$year = regex('/[0-9]{4}/');
$month = $twoDigits;
$day = $twoDigits;
$hour = $twoDigits;
$minute = $twoDigits;
$second = $twoDigits;
```

Before you continue, there is an interesting thing to note here: as soon as you have your first parsers defined, you can
start testing. In fact, each parser can work on its own:

```phps
use jubianchi\PPC\Failure;
use jubianchi\PPC\Success;

assert($year('2020', 0) == new Success('2020', 4));
assert($year('20', 0) == new Failure(['/[0-9]{4}/'], 0));
```

Testing your parsers as you write them will help you ensure they are correct before you start combining them. It's
easier to reason about small units (think about unit tests).

### Combining the parsers

Now you have defined the _tokens_, let's try to combine them to produce an effective parser. The goal is to build a
parser able to match a series of these _tokens_. To do that you can use the [`seq`](/reference.md#seq) combinator and
run it with [`parse`](/reference.md#parse):

```phps
use function jubianchi\PPC\{parse, seq};

$dateTime = seq($year, $dash, $month, $dash, $day, $t, $hour, $colon, $minute, $colon, $second);

var_dump(parse($dateTime, '2020-07-21T17:35:00'));
```

Great, our parser seems to be working well! Its value is the list of the values of each _token_:

```
['2020', '-', '07', '-', '21', 'T', '17', ':', '35', ':', '00']
```

When the input is not valid, `parse` throws a `SyntaxError` telling what went wrong:

```phps
parse($dateTime, '2020-07-21 17:35:00');
// SyntaxError: Expected "T", got " " at line 1, column 11
```

### Shaping the result

The list of strings is not really convenient: you probably want numbers, and you do not care about separators. This is
what the [`map`](/reference.md#map) modifier is for. Modifiers are meant to be applied with the pipe operator:

```phps
use function jubianchi\PPC\map;

$twoDigits = regex('/[0-9]{2}/') |> map(intval(...));
$year = regex('/[0-9]{4}/') |> map(intval(...));
```

The whole sequence can be mapped the same way, to turn its list of values into something more self-descriptive:

```phps
$dateTime = seq($year, $dash, $month, $dash, $day, $t, $hour, $colon, $minute, $colon, $second)
    |> map(static fn (array $parts): array => [
        'year' => $parts[0],
        'month' => $parts[2],
        'day' => $parts[4],
        'hour' => $parts[6],
        'minute' => $parts[8],
        'second' => $parts[10],
    ]);
```

## Wrapping everything together

Now that you went through the process of writing a parser, let's wrap everything together and look at the code:

```php
<?php

use function jubianchi\PPC\{char, map, parse, regex, seq};

$twoDigits = regex('/[0-9]{2}/') |> map(intval(...));

$year = regex('/[0-9]{4}/') |> map(intval(...));
$month = $twoDigits;
$day = $twoDigits;
$hour = $twoDigits;
$minute = $twoDigits;
$second = $twoDigits;
$dash = char('-');
$colon = char(':');
$t = char('T');

$dateTime = seq($year, $dash, $month, $dash, $day, $t, $hour, $colon, $minute, $colon, $second)
    |> map(static fn (array $parts): array => [
        'year' => $parts[0],
        'month' => $parts[2],
        'day' => $parts[4],
        'hour' => $parts[6],
        'minute' => $parts[8],
        'second' => $parts[10],
    ]);

assert(parse($dateTime, '2020-07-21T17:35:00') === [
    'year' => 2020,
    'month' => 7,
    'day' => 21,
    'hour' => 17,
    'minute' => 35,
    'second' => 0,
]);
```

As you may have seen this parser is not capable of parsing the time zone designator described in ISO8601: this is left
as an exercise for you.
