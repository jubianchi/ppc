# Reference

> [!WARNING]
> This is the documentation for the `master` branch.
> Things may change or not be complete.
>
> Head over to the [latest stable version](/1.0.0/reference.md) for an up-to-date documentation.

## Parsers

<!-- panels:start -->

<!-- div:title-panel -->

### any

```phps
function any(): Parser<Slice>
```

<!-- div:left-panel -->

The `any` parser matches any character and consumes it from the stream.

It will always return a `Success` result containing a `Slice` holding the character unless it reaches the end of the
stream.

If the end of the stream is reached, the parser will return a `Failure` result which can be turned into an exception
when the wrapped value is accessed.

<!-- div:right-panel -->

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Parsers\any;

$stream = new Stream('a');
$parser = any();
$result = $parser($stream);

assert($result instanceof Result\Success);
assert($result->result() instanceof Slice);
assert((string) $result->result() === 'a');

assert($parser($stream) instanceof Result\Failure);
```

<!-- div:title-panel -->

### char

```phps
function char(string $char): Parser<Slice>
```

<!-- div:left-panel -->

The `char` parser matches the given character and consumes it from the stream.

If the expected character is found, the parser will return a `Success` result containing a `Slice` holding the 
character.

If the expected character is not found, the parser will return a `Failure` result which can be turned into an exception
when the wrapped value is accessed.

<!-- div:right-panel -->

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Parsers\char;

$stream = new Stream('abc');
$success = char('a');
$failure = char('c');
$result = $success($stream);

assert($result instanceof Result\Success);
assert($result->result() instanceof Slice);
assert((string) $result->result() === 'a');

assert($failure($stream) instanceof Result\Failure);
```

<!-- div:title-panel -->

### eos

```phps
function eos(): Parser<null>
```
<!-- div:left-panel -->

The `eos` parser matches the end of the stream.

It will always return a `Failure`  unless it reaches the end of the stream, in which case it will return a `Success` 
result.

> [!TIP]
> Read the [Consuming until the end of the stream](/tutorials/consuming-until-the-end-of-the-stream.md) tutorial for an 
> example use case.

<!-- div:right-panel -->

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Parsers\{char, eos};

$stream = new Stream('a');
$char = char('a');
$eos = eos();

assert($eos($stream) instanceof Result\Failure);

$char($stream);

assert($eos($stream) instanceof Result\Success);
```


<!-- div:title-panel -->

### regex

```phps
function regex(string $pattern): Parser<Slice>
```

<!-- div:left-panel -->

The `regex` parser matches a single character matching the given regular expression.

If the current character matches the regular expression, the parser will return a `Success` result containing a 
`Slice` holding the character.

If the expected character does not match, the parser will return a `Failure` result which can be turned into an 
exception when the wrapped value is accessed.

<!-- div:right-panel -->

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Parsers\regex;

$stream = new Stream('abc');
$parser = regex('/[a-z]/');
$result = $parser($stream);

assert($result instanceof Result\Success);
assert($result->result() instanceof Slice);
assert((string) $result->result() === 'a');
```

<!-- div:left-panel -->

> [!NOTE]
> Be carefull when using this parser: 
> * whatever the regular expression is, it will only consume a **single** character;
> * if the regular expression allow for `[0, n]` match, the parser will **always succeed**;
> * if the regular expression tries to match more than one character, the parser will **always fail**.

<!-- div:right-panel -->

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Parsers\regex;

$stream = new Stream('123');
$parser = regex('/[a-z]*/');
$result = $parser($stream);

assert($result instanceof Result\Success);
assert($result->result() instanceof Slice);
assert((string) $result->result() === '1');
```

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Parsers\regex;

$stream = new Stream('abc');
$parser = regex('/[a-z]+c/');
$result = $parser($stream);

assert($result instanceof Result\Failure);
```

<!-- div:title-panel -->

### word

```phps
function word(string $word): Parser<Slice>
```

<!-- div:left-panel -->

The `word` parser matches the given _word_ and consumes it from the stream.

If the expected _word_ is found, the parser will return a `Success` result containing a `Slice` holding the 
_word_.

If the expected _word_ is not found, the parser will return a `Failure` result which can be turned into an exception
when the wrapped value is accessed.

> [!NOTE]
> Here, a _word_ is a set of consecutive characters: they can be letter, numbers or anything.

<!-- div:right-panel -->

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Parsers\word;

$stream = new Stream('ab3d_');
$success = word('ab3');
$failure = word('b_');
$result = $success($stream);

assert($result instanceof Result\Success);
assert($result->result() instanceof Slice);
assert((string) $result->result() === 'ab3');

assert($failure($stream) instanceof Result\Failure);
```

<!-- panels:end -->

## Combinators

<!-- panels:start -->

<!-- div:title-panel -->

### alt

```phps
function alt(Parser $first, Parser $second, Parser ...$parsers): Parser
```

<!-- div:left-panel -->

The `alt` combinator executes each parser one by one and stops at the first successful one.

If any of the given parsers matches, the combinator will return the `Success` result it got from the successful parser.

If none of the given parsers succeeds, the combinator will return the first `Failure` result it got.

<!-- div:right-panel -->

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\alt;
use function jubianchi\PPC\Parsers\char;

$stream = new Stream('abc');
$success = alt(char('a'), char('b'));
$failure = alt(char('d'), char('1'));
$result = $success($stream);

assert($result instanceof Result\Success);
assert($result->result() instanceof Slice);
assert((string) $result->result() === 'a');

assert($failure($stream) instanceof Result\Failure);
```

<!-- div:title-panel -->

### enclosed

```phps
function enclosed(Parser $before, Parser<T> $parser, ?Parser $after = null): Parser<T>
```

<!-- div:left-panel -->

The `enclosed` combinator tries to match a parser which is preceded and followed by other parsers.

If all of the given parsers matches, the combinator will return the `Success` result it got from the second one, which 
is the parser matching the enclosed value

If one of the given parsers fails, the combinator will return its `Failure` result.

The main goal of this combinator is to ease the process of matching enclosed values. See the example snippets to see 
the differences of using — With — (or not using — Without) the `enclosed` combinator.

> [!TIP]
> Read the [Matching enclosed values]() tutorial for an example use case.

<!-- div:right-panel -->

<!-- tabs:start -->

### ** With **

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\enclosed;
use function jubianchi\PPC\Parsers\char;

$stream = new Stream('-a-');
$success = enclosed(char('-'), char('a'));
$failure = enclosed(char('"'), char('a'));
$result = $success($stream);

assert($result instanceof Result\Success);
assert($result->result() instanceof Slice);
assert((string) $result->result() === 'a');

assert($failure($stream) instanceof Result\Failure);
```

### ** Without **

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\seq;
use function jubianchi\PPC\Mappers\{first, skip};
use function jubianchi\PPC\Parsers\char;

$stream = new Stream('-a-');
$success = seq(
    char('-')->map(skip()), 
    char('a'), 
    char('-')->map(skip())
)->map(first());
$result = $success($stream);

assert($result instanceof Result\Success);
assert($result->result() instanceof Slice);
assert((string) $result->result() === 'a');
```

> [!NOTE]
> In this example, we want you to focus on verbosity and readability. We use mappers here, either ignore them or jump to
> [their documentation](#mappers) if you want to more.


<!-- tabs:end -->

<!-- div:title-panel -->

### many

```phps
function many(Parser<T> $parser): Parser<array<T>>
```

<!-- div:left-panel -->

The `many` combinator tries to match a given parser one or several times, stopping at the first failure.

If the given parsers matches at least one time, the combinator will return a `Success` containing an `array` holding 
each result.

If the given parsers never matches, the combinator will return the first `Failure` result it encountered.

> [!TIP]
> Read the [Matching lists]() tutorial for an example use case.

<!-- div:right-panel -->

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\many;
use function jubianchi\PPC\Parsers\regex;

$stream = new Stream('abc');
$success = many(regex('/a|b/'));
$failure = many(regex('/[d-z]/'));
$result = $success($stream);

assert($result instanceof Result\Success);
assert(is_array($result->result()));
assert((string) $result->result()[0] === 'a');
assert((string) $result->result()[1] === 'b');

assert($failure($stream) instanceof Result\Failure);
```

<!-- div:title-panel -->

### not

```phps
function not(Parser $parser): Parser<Slice>
```

<!-- div:left-panel -->

The `not` combinator will be successful when the given parser fails.

In such case, it will return a `Success` result containing eqaul to the result of a [`any`](#any) parser.

If the given parser succeeds, the `not` combinator will return a `Failure` result.

> [!TIP]
> The `not` parser may be used to do negative lookaheads.
>
> Read the [Looking ahead]() tutorial for an example use case.

<!-- div:right-panel -->

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\not;
use function jubianchi\PPC\Parsers\char;

$stream = new Stream('abc');
$success = not(char('b'));
$failure = not(char('b'));
$result = $success($stream);

assert($result instanceof Result\Success);
assert($result->result() instanceof Slice);
assert((string) $result->result() === 'a');

assert($failure($stream) instanceof Result\Failure);
```

<!-- div:title-panel -->

### opt

```phps
function opt(Parser<T> $parser): Parser<?T>
```

<!-- div:left-panel -->

The `opt` combinator will be always be successful.

When the given parser succeeds, the `opt` combinator will return its `Success` result.

If the given parser fails, the `opt` combinator will return a `Success` result holding a null value.

<!-- div:right-panel -->

```php
<?php

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\opt;
use function jubianchi\PPC\Parsers\char;

$stream = new Stream('abc');
$success = opt(char('a'));
$failure = opt(char('c'));
$result = $success($stream);

assert($result instanceof Result\Success);
assert($result->result() instanceof Slice);
assert((string) $result->result() === 'a');

$result = $success($stream);
assert($result instanceof Result\Success);
assert($result->result() === null);
```

<!-- div:title-panel -->

### separated

```phps
function separated(Parser $separator, Parser $parser<T>): Parser<array<T>>
```

> [!TIP]
> Read the [Matching lists](/tutorials/matching-lists.md) tutorial for an example use case.

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

!> **TODO** write this snippet

<!-- div:title-panel -->

### seq

```phps
function seq(Parser $first, Parser $second, Parser ...$parsers): Parser<array>
```

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

!> **TODO** write this snippet

<!-- panels:end -->

### Special combinators

<!-- panels:start -->

<!-- div:title-panel -->

#### recurse

```phps
function recurse(?Parser<T> &$parser): Parser<T>
```

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

!> **TODO** write this snippet

<!-- div:title-panel -->

#### debug

```phps
function debug(Parser<T> &$parser): Parser<T>
```

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

!> **TODO** write this snippet

<!-- panels:end -->

## Mappers

<!-- panels:start -->
<!-- div:title-panel -->

### otherwise

```phps
function otherwise(mixed $value): Mapper
```

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

!> **TODO** write this snippet

<!-- div:title-panel -->

### concat

```phps
function concat(): Mapper
```

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

!> **TODO** write this snippet

<!-- div:title-panel -->

### structure

```phps
function structure(array<int, array-key> $mappings): Mapper
```

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

!> **TODO** write this snippet

<!-- div:title-panel -->

### php

```phps
function php(string $name): Mapper
```

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

!> **TODO** write this snippet

<!-- div:title-panel -->

### skip

```phps
function skip(): Mapper
```

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

!> **TODO** write this snippet

<!-- div:title-panel -->

### nth / first / last

```phps
function nth(int $nth): Mapper
function first(): Mapper
function last(): Mapper
```

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

<!-- tabs:start -->

### ** nth **

!> **TODO** write this snippet

### ** first **

!> **TODO** write this snippet

### ** last **

!> **TODO** write this snippet

<!-- tabs:end -->

<!-- div:title-panel -->

### value

```phps
function value(mixed $value): Mapper
```

<!-- div:left-panel -->

!> **TODO** write this documentation

<!-- div:right-panel -->

!> **TODO** write this snippet

<!-- panels:end -->
