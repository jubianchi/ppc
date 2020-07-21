## Your first parser

In this tutorial, you will learn how to build a simple parser yet allowing us to cover many core concepts.

The example parser you will build will be able to parse a date and time in the 
[ISO8601](https://en.wikipedia.org/wiki/ISO_8601) format. To do so, you will use some [parsers](/reference.md#parsers), 
[combinators](/reference.md#combinators) and [mappers](/reference.md#mappers).

Building a parser is usually done through some standard steps:

* Reading about what you are trying to parse;
* Identifying _tokens_;
* Defining parser for these _tokens_;
* Combining the parsers;
* Shaping the result;

We'll cover each of these steps in this tutorial. Let's go!

### ISO8601

The very first step of the process of building a parser is about documenting yourself on what you are trying to parse. 
If you are building a parser for a format you are creating, things will be difficult because you will have to think 
about the format and how to parse it at the same time. Hopefully, there is a standard format for dates and times. 

The ISO8601 defines a standard calendar dates format. It is structured as follow:
* The year is written using four numeric characters (`YYYY`). It should be zero-padded;
* The month is written using two numeric characters (`MM`). It should be zero-padded;
* The day is written using two numeric characters (`DD`). It should be zero-padded;
* The date is written `YYYY-MM-DD`.

The ISO8601 also defines a standard extended time format. It is structued as follow:
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

The first step is done, you now know exactly how a ISO8601 compliant date is structured and written. We can now start writing
the required parser.

### The parser

#### Defining tokens

As you may have noticed in the previous paragraph, there are many parts that should be declared: the year, month, day, hour, 
minute and second. The separators (`-`, `+`, `:`, `T` and `Z`) may also be declared. Let's call those parts _tokens_.

```phps
<?php

use function jubianchi\PPC\Combinators\repeat;
use function jubianchi\PPC\Parsers\{regex, char};

// Tokens
$year = repeat(4, regex('/[0-9]/'));
$month = repeat(2, regex('/[0-9]/'));
$day = repeat(2, regex('/[0-9]/'));
$hour = repeat(2, regex('/[0-9]/'));
$minute = repeat(2, regex('/[0-9]/'));
$second = repeat(2, regex('/[0-9]/'));
$dash = char('-');
$colon = char(':');
$t = char('T');
$z = char('Z');
```

Ok, you now have all the _tokens_ you need but there is one thing we can make better: the exact same definition is 
repeated 5 times! Let's remove this:

```phps
<?php

use function jubianchi\PPC\Combinators\repeat;
use function jubianchi\PPC\Parsers\regex;

$twoDigits = repeat(2, regex('/[0-9]/'));
$fourDigits = repeat(4, regex('/[0-9]/'));

$year = $fourDigits;
$month = $twoDigits;
$day = $twoDigits;
$hour = $twoDigits;
$minute = $twoDigits;
$second = $twoDigits;
```

The repeated code has been deleted, you now have two base parsers to reuse. We define a bunch of variable just to ease 
reading the code in the future.

> [!WARNING]
> You may be tempted to also change the `$fourDigits` parser to something like `repeat(2, $twoDigits)` but this is a bad
> idea.
>
> Do not fall into premature optimization: the optimization topic will be covered in another tutorial.

Before you continue, there is an interesting thing to note here: as soon as you have your first parsers defined, you can 
start testing. In fact, each parser can work on its own:

```phps
assert($year(new Stream('2020')) instanceof Result\Success);
assert($year(new Stream('20')) instanceof Result\Failure);
```

Testing your parsers as you write them will help you ensure they are correct before you start combining them. It's 
easier to reason about small units (think about unit tests). 

#### Combining the parsers

Now you have defined the _tokens_, let's try to combine them to produce an effective parser. The goal is to build a 
parser able to match a series of these _tokens_. To do that you can use the [`seq`](/reference.md#seq) combinator: 

````phps
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\seq;

$dateTime = seq(
    $year,
    $dash,
    $month,
    $dash,
    $day,
    $t,
    $hour,
    $colon,
    $minute,
    $colon,
    $second
);

$result = $dateTime(new Stream('2020-07-21T17:35:00Z'));
assert($result instanceof Result\Success);
````

Great, our parser seems to be working well! Now let's try to read the result you get from parsing a sample date and 
time. This will print something ugly, a tree of `Result`s and `Slice`s. Let's look at a representation of the output:

<!-- panels:start -->

<!-- div:left-panel -->

```phps
$result = $dateTime(new Stream('2020-07-21T17:35:00Z'));

var_dump($result);
```

<!-- div:right-panel -->

```
Success<array> {
    array<int, Result> {
        Success<array> {
            Success<Slice(2)>
            Success<Slice(0)>
            Success<Slice(2)>
            Success<Slice(0)>
        }
        Success<Slice(-)>
        Success<array> {
            Success<Slice(0)>
            Success<Slice(7)>
        }
        Success<Slice(-)>
        Success<array> {
            Success<Slice(2)>
            Success<Slice(1)>
        }
        ...
    }
}
```

<!-- panels:end -->

This result is hardly readable and moreover hard to use. We'll need to transform parsers' results into something better, 
this is what mappers are made for.

#### Shaping the result

Let's take for example the year parser: its results contains an array of four `Slice`s. What if you were able to reduce
this number to only one? Better, what if you could directly get a string out of the `Slice`s?

```phps
<?php

use function jubianchi\PPC\Combinators\repeat;
use function jubianchi\PPC\Mappers\concat;
use function jubianchi\PPC\Parsers\regex;

$fourDigits = repeat(4, regex('/[0-9]/'))->map(concat());
$year = $fourDigits;
```

Basically, the [`concat`](/reference.md#concat) will turn a `Result` containg an array of `Slice`s to a `Result` 
containing a single string:

<!-- panels:start -->

<!-- div:left-panel -->

```
Success<array> {
    array<int, Slice> {
        Slice(2)
        Slice(0)
        Slice(2)
        Slice(0)
    }
}
```

<!-- div:right-panel -->

```
Success<string> {
    "2020"
}
```

<!-- panels:end -->

Way better, there is still room for improvment! Results for the several separators (`-`, `:`, ...) are not really 
useful. Let's exclude them. This is what the [`skip`](/reference.md#skip) mapper is for:

```phps
use function jubianchi\PPC\Mappers\skip;
use function jubianchi\PPC\Parsers\char;

$dash = char('-')->map(skip());
$colon = char(':')->map(skip());
$t = char('T')->map(skip());
$z = char('Z')->map(skip());
```

We should now be able to read the raw result easily:

<!-- panels:start -->

<!-- div:left-panel -->

```phps
$result = $dateTime(new Stream('2020-07-21T17:35:00Z'));

var_dump($result);
```

<!-- div:right-panel -->

```
Success<array> {
    array<int, string> {
        "2020"
        "07"
        "21"
        "17"
        "35"
        "00"
    }
}
```

<!-- panels:end -->

One last thing you can do is make the resulting array a bit more self-descriptive. Let's turn the `array<int, string>` 
into a `array<string, string>` with keys being the names of the date and time parts:

<!-- panels:start -->

<!-- div:left-panel -->

```phps
<?php

use function jubianchi\PPC\Combinators\seq;
use function jubianchi\PPC\Mappers\structure;

$dateTimeStructured = $dateTime->map(\jubianchi\PPC\Mappers\structure([
    'year',
    'month',
    'day',
    'hour',
    'minute',
    'second',
]));

$result = $dateTimeStructured(new Stream('2020-07-21T17:35:00Z'));

var_dump($result->result());
```

<!-- div:right-panel -->

```
array<string, string> {
    "year" => 2020"
    "month" => "07"
    "day" => "21"
    "hour" => "17"
    "minute" => "35"
    "second" => "00"
}
```

<!-- panels:end -->

### Wrapping everything together

Now that you went through the process of writing a parser, let's wrap everything together and look at the code:

```php
<?php

use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\{repeat, seq};
use function jubianchi\PPC\Mappers\{concat, skip, structure};
use function jubianchi\PPC\Parsers\{regex, char};

$twoDigits = repeat(2, regex('/[0-9]/'))->map(concat());
$fourDigits = repeat(4, regex('/[0-9]/'))->map(concat());

$year = $fourDigits;
$month = $twoDigits;
$day = $twoDigits;
$hour = $twoDigits;
$minute = $twoDigits;
$second = $twoDigits;
$dash = char('-')->map(skip());
$colon = char(':')->map(skip());
$t = char('T')->map(skip());
$z = char('Z')->map(skip());

$dateTime = seq($year, $dash, $month, $dash, $day, $t, $hour, $colon, $minute, $colon, $second)
    ->map(structure(['year', 'month', 'day', 'hour', 'minute', 'second']));

$result = $dateTime(new Stream('2020-07-21T17:35:00Z'));
var_dump($result->result());
```

As you may have seen this parser is not capable of parsing the time offset described in ISO8601: this is left as an 
exercice for you.
