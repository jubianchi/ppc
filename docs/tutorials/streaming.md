# Streaming big inputs

In this tutorial, you will learn how to parse inputs too big to be loaded in memory, like large files or network
streams, with the [`stream`](/reference.md#streaming) function.

As an example, we will parse an access log where each line holds an HTTP method, a path and a status code:

```
GET /index.html 200
POST /api/users 201
DELETE /api/users/42 404
```

## The parser

Let's start with a parser for a single line. The [`label`](/reference.md#label) modifier gives readable error messages,
[`consumed`](/reference.md#consumed) returns the whole path at once, and [`map`](/reference.md#map) shapes the values:

```phps
<?php

use function jubianchi\PPC\{alt, char, consumed, label, map, regex, seq, word};

$method = alt(word('GET'), word('POST'), word('DELETE')) |> label('HTTP method');
$path = seq(char('/'), regex('/[^ ]*/')) |> consumed() |> label('path');
$status = regex('/[1-5][0-9]{2}/') |> label('status code') |> map(intval(...));

$entry = seq($method, char(' '), $path, char(' '), $status, char("\n"))
    |> map(static fn (array $parts): array => [
        'method' => $parts[0],
        'path' => $parts[2],
        'status' => $parts[4],
    ]);
```

## Loading the whole input

With [`parse`](/reference.md#parse), the log has to be loaded in memory, and all the entries are returned at once:

```phps
use function jubianchi\PPC\{eos, many, parse, terminated};

$entries = parse(terminated(many($entry), eos()), file_get_contents('access.log'));
```

This is fine for small inputs, but the memory used grows with the size of the log: the input, and every entry.

## Streaming the input

With `stream`, the log is read by chunks and each entry is yielded as soon as it is parsed. Only the entry being parsed
is kept in memory, whatever the size of the log:

```phps
use function jubianchi\PPC\stream;

foreach (stream($entry, fopen('access.log', 'r')) as ['method' => $method, 'path' => $path, 'status' => $status]) {
    // ...
}
```

The parser is the same: when a chunk ends in the middle of an entry, the primitives return an `Incomplete` result
instead of failing, and `stream` reads more bytes before parsing the entry again.

Errors are reported at their position in the whole input, not in the chunk being parsed:

```phps
// SyntaxError: Expected HTTP method, got "P" at line 2, column 1
```

## Wrapping everything together

Here, the log is written in memory and read in tiny chunks, so that entries are cut many times:

```php
<?php

use jubianchi\PPC\SyntaxError;

use function jubianchi\PPC\{alt, char, consumed, eos, label, many, map, parse, regex, seq, stream, terminated, word};

$method = alt(word('GET'), word('POST'), word('DELETE')) |> label('HTTP method');
$path = seq(char('/'), regex('/[^ ]*/')) |> consumed() |> label('path');
$status = regex('/[1-5][0-9]{2}/') |> label('status code') |> map(intval(...));

$entry = seq($method, char(' '), $path, char(' '), $status, char("\n"))
    |> map(static fn (array $parts): array => [
        'method' => $parts[0],
        'path' => $parts[2],
        'status' => $parts[4],
    ]);

$log = "GET /index.html 200\nPOST /api/users 201\nDELETE /api/users/42 404\n";
$expected = [
    ['method' => 'GET', 'path' => '/index.html', 'status' => 200],
    ['method' => 'POST', 'path' => '/api/users', 'status' => 201],
    ['method' => 'DELETE', 'path' => '/api/users/42', 'status' => 404],
];

assert(parse(terminated(many($entry), eos()), $log) === $expected);

$source = fopen('php://memory', 'w+');
fwrite($source, $log);
rewind($source);

assert(iterator_to_array(stream($entry, $source, chunk: 4)) === $expected);

$source = fopen('php://memory', 'w+');
fwrite($source, "GET /index.html 200\nPUT /x 200\n");
rewind($source);

try {
    iterator_to_array(stream($entry, $source, chunk: 4));
} catch (SyntaxError $error) {
    assert($error->getMessage() === 'Expected HTTP method, got "P" at line 2, column 1');
}
```

Streaming has one limit to keep in mind: PHP does not expose partial matching for regular expressions, so a `regex` only
trusts its result when enough bytes follow it. Read the [reference](/reference.md#streaming) to learn how to tune it.
