## Consuming until the end of the stream

In this tutorial, you will learn how to ensure your parser is able to consume an input until its end.

Sometimes it is important to make sure the parser consumes the whole input. Let's take a look at the 
[previous tutorial](/tutorials/your-first-parser.md) example where you built a working date and time parser. This parser 
is able to parser a ISO8601 compliant date until its second component. It does not know how to handle the timezone part.

What you want here is to be sure that the parser consumed the whole input or produced a `Failure`.   

Let's go!

### The parser

```php
<?php

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
```

As you may know, a ISO8601 compliant date ends with a `Z` meaning the date and time are UTC based. Let's see if the 
parser takes care of this:

```phps
<?php

use jubianchi\PPC\Parser\Result;

$stream = new Stream('2020-07-21T17:35:00Z');
$result = $dateTime($stream);

assert($result instanceof Result\Success);
var_dump($stream->valid()); // true
```

The stream is still valid after the parser consumed it: this means there is characters left to consume or, in other 
words, the parser did not reach the end of the stream (EOS). 

### EOS

PPC provides a dedicated parser, [`eos`](/reference.md#eos) which goal is to match the end of the stream. Fixing our date and time parser should be 
easy:

```phps
<?php

use function jubianchi\PPC\Parsers\eos;
use function jubianchi\PPC\Mappers\skip;

$eos = eos()->map(skip());

$dateTime = seq($year, $dash, $month, $dash, $day, $t, $hour, $colon, $minute, $colon, $second, $eos)
    ->map(structure(['year', 'month', 'day', 'hour', 'minute', 'second']));
```

EZPZ, right?

Let's see if the parser is now failing correctly:

```phps
<?php

use jubianchi\PPC\Parser\Result;

$stream = new Stream('2020-07-21T17:35:00Z');
$result = $dateTime($stream);

assert($result instanceof Result\Failure);
```

As explained in the introduction of this tutorial, sometimes it is important to ensure the parser consumes the whole 
stream.

In the current example, this let's you know your parser, despite it's working, is not correct: it does not know how to 
handle some part of the date and time string thus it can't reach the end of the stream.

### Wrapping everything together

Here is the full code of the parser which is now able to handle the `Z` character and reach the end of the stream.

```php
<?php

use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\{repeat, seq};
use function jubianchi\PPC\Mappers\{concat, skip, structure};
use function jubianchi\PPC\Parsers\{regex, char, eos};

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
$eos = eos()->map(skip());

$dateTime = seq($year, $dash, $month, $dash, $day, $t, $hour, $colon, $minute, $colon, $second, $z, $eos)
    ->map(structure(['year', 'month', 'day', 'hour', 'minute', 'second']));

$result = $dateTime(new Stream('2020-07-21T17:35:00Z'));
var_dump($result->result());
```

Again, this parser is not capable of parsing the time offset described in ISO8601: this is left as an exercice for you.
