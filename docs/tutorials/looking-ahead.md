# Looking ahead

In this tutorial, you will learn how to make a parser depend on what comes next, without consuming it, with the
[`negate`](/reference.md#negate) and [`peek`](/reference.md#peek) modifiers.

## Keywords and identifiers

Let's split a line of a tiny language into words, each one being either the `if` keyword or a variable:

```phps
<?php

use function jubianchi\PPC\{alt, char, eos, parse, regex, separated, terminated, word};

$variable = regex('/[a-z]\w*/');
$words = terminated(separated(char(' '), alt(word('if'), $variable)), eos());

parse($words, 'if x');   // ['if', 'x']
parse($words, 'iffy x');
// SyntaxError: Expected " " or end of input, got "f" at line 1, column 3
```

`iffy` is a valid variable, but `word('if')` matches its first two letters: `alt` keeps this first success, and the
next parser fails on the rest of the variable.

## Negative lookahead

A keyword is a word **not followed** by another letter. The `negate` modifier turns a parser into a check: it succeeds
when the parser fails, and fails when it succeeds, without consuming input:

```php
<?php

use function jubianchi\PPC\{alt, char, eos, negate, parse, regex, separated, terminated, word};

$keyword = static fn (string $word): \Closure => terminated(word($word), regex('/\w/') |> negate());

$variable = regex('/[a-z]\w*/');
$words = terminated(separated(char(' '), alt($keyword('if'), $variable)), eos());

assert(parse($words, 'if x') === ['if', 'x']);
assert(parse($words, 'iffy x') === ['iffy', 'x']);
```

Now `$keyword('if')` fails on `iffy`, and `alt` tries the variable.

?> [`anyBut`](/reference.md#anybut) looks similar, but it consumes a char when its parser fails: it matches "any char
but", while `negate` only checks what comes next.

## Positive lookahead

The `peek` modifier does the opposite: it succeeds with the value of its parser, but gives back the input the parser
consumed. Use it when a parser must only match before something which belongs to another parser.

Here, a percentage is a number followed by `%`, but the `%` is left to the next parser:

```php
<?php

use jubianchi\PPC\SyntaxError;

use function jubianchi\PPC\{char, map, parse, peek, regex, seq, terminated};

$number = regex('/[0-9]+/') |> map(intval(...));
$percentage = terminated($number, char('%') |> peek());

assert(parse(seq($percentage, char('%')), '42%') === [42, '%']);

try {
    parse(seq($percentage, char('%')), '42');
} catch (SyntaxError $error) {
    assert($error->getMessage() === 'Expected "%", got end of input at line 1, column 3');
}
```

Both modifiers run their parser once, at the offset they are given, and never read more input than their parser: they
can be used in [streaming](/tutorials/streaming.md) like any other parser.
