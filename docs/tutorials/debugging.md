# Debugging

In this tutorial, you will learn how to use the [`debug`](/reference.md#debug) combinator to get a clear view of what's 
happening in your parsers.

AS an example, we will use a snippet from the [Your first parser](/tutorials/your-first-parser.md) tutorial.

## The example

In the previous previous tutorial we wrote a date and time parser. One of the first steps was to identify what we called
_tokens_ ans then try to refactor the definitions to write less code. We started with:

```php
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

And reduced this snippet to:

```php
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

At that point we said it was not necessary to refactor the code more. You could have been tempted to write:

```php
<?php

use function jubianchi\PPC\Combinators\repeat;
use function jubianchi\PPC\Parsers\regex;

$twoDigits = repeat(2, regex('/[0-9]/'));
$fourDigits = repeat(2, $twoDigits);

$year = $fourDigits;
$month = $twoDigits;
$day = $twoDigits;
$hour = $twoDigits;
$minute = $twoDigits;
$second = $twoDigits;
```

But this may not be the best way to write this parser. Let's use the `debug` combinator to understand why.

## Debugging

To illustrate the power of the `debug` combinator, let's compare the two snippets, the good one and the "bad" one:

<!-- panels:start -->

<!-- div:left-panel -->

### Good

<!-- tabs:start -->

### ** Parser **

```php
<?php

use jubianchi\PPC\Stream\Char;
use function jubianchi\PPC\Combinators\{debug, repeat};
use function jubianchi\PPC\Parsers\regex;

$twoDigits = repeat(2, regex('/[0-9]/'));
$fourDigits = repeat(4, regex('/[0-9]/'));

$parser = debug($fourDigits);
$parser(new Char('2020'));
```

### ** Output **

```
[info]   	> repeat(4, regex(/[0-9]/)) {"line":1,"column":0,"ops":0}
[info]   	  > regex(/[0-9]/) {"line":1,"column":0,"ops":0}
[info]   	  < regex(/[0-9]/) {"line":1,"column":1,"consumed":"2","ops":1,"duration":0.000674}
[info]   	  > regex(/[0-9]/) {"line":1,"column":1,"ops":1}
[info]   	  < regex(/[0-9]/) {"line":1,"column":2,"consumed":"0","ops":2,"duration":7.0e-6}
[info]   	  > regex(/[0-9]/) {"line":1,"column":2,"ops":2}
[info]   	  < regex(/[0-9]/) {"line":1,"column":3,"consumed":"2","ops":3,"duration":4.0e-6}
[info]   	  > regex(/[0-9]/) {"line":1,"column":3,"ops":3}
[info]   	  < regex(/[0-9]/) {"line":1,"column":4,"consumed":"0","ops":4,"duration":3.0e-6}
[info]   	< repeat(4, regex(/[0-9]/)) {"line":1,"column":4,"ops":5,"duration":0.000845}
```

<!-- tabs:end -->

<!-- div:right-panel -->

### Bad

<!-- tabs:start -->

### ** Parser **

```php
<?php

use jubianchi\PPC\Stream\Char;
use function jubianchi\PPC\Combinators\{debug, repeat};
use function jubianchi\PPC\Parsers\regex;

$twoDigits = repeat(2, regex('/[0-9]/'));
$fourDigits = repeat(2, $twoDigits);

$parser = debug($fourDigits);
$parser(new Char('2020'));
```

### ** Output **

```
[info]   	> repeat(2, repeat(2, regex(/[0-9]/))) {"line":1,"column":0,"ops":0}
[info]   	  > repeat(2, regex(/[0-9]/)) {"line":1,"column":0,"ops":0}
[info]   	    > regex(/[0-9]/) {"line":1,"column":0,"ops":0}
[info]   	    < regex(/[0-9]/) {"line":1,"column":1,"consumed":"2","ops":1,"duration":0.000298}
[info]   	    > regex(/[0-9]/) {"line":1,"column":1,"ops":1}
[info]   	    < regex(/[0-9]/) {"line":1,"column":2,"consumed":"0","ops":2,"duration":4.0e-6}
[info]   	  < repeat(2, regex(/[0-9]/)) {"line":1,"column":2,"ops":3,"duration":0.000371}
[info]   	  > repeat(2, regex(/[0-9]/)) {"line":1,"column":2,"ops":3}
[info]   	    > regex(/[0-9]/) {"line":1,"column":2,"ops":3}
[info]   	    < regex(/[0-9]/) {"line":1,"column":3,"consumed":"2","ops":4,"duration":3.0e-6}
[info]   	    > regex(/[0-9]/) {"line":1,"column":3,"ops":4}
[info]   	    < regex(/[0-9]/) {"line":1,"column":4,"consumed":"0","ops":5,"duration":2.0e-6}
[info]   	  < repeat(2, regex(/[0-9]/)) {"line":1,"column":4,"ops":6,"duration":5.7e-5}
[info]   	< repeat(2, repeat(2, regex(/[0-9]/))) {"line":1,"column":4,"ops":7,"duration":0.00048}
```

<!-- tabs:end -->

<!-- panels:end -->

Before diving into the results, let's see how the debugger's output should be read:
* The first element is the log level, it can be `info` or `error`;
* The second element is either a `>` character, meaning we entered a parser or combinator, or a `<` meaning we exited 
  from a parser or combinator;
* The third element is the parser or combinator label;
* The last element is the context, it contains:
  * The actual position in the stream in `line`s (starting at `1`) and `column`s (starting at `0`);
  * The `ops` count which is the actual number of parsers or combinators that have been executed;
  * The `duration` which is the time it took for the the parser to execute in seconds;
  * In case of a parser or combinator returning a `Slice`, the `consumed` characters.
    
### Reading the actual result

Now that we know how to read the debugger output, what can we see in our previous examples?

With the second snippet, the "bad" one, wa have more `ops` than with the good snippet. In our example, we do not see
the impact of such a difference because we are parsing a tiny input but trust me, with large inputs, 2 less `ops` can 
make a real difference!

Also, we see that with the good snippet, the output is flat compared to the "bad" one where we have two levels of 
[`repeat`](/reference.md#repeat). This can also make a difference when you will have to debug complex parsers.

Talking about complex parsers, you may think that the actual output produced by the debugger might not be very 
comfortable. Let's see what we can do about that. 

### Prettifying the output

In our example, we use the `$twoDigits` and `$fourDigits` parsers as our base building blocks for 6 other parsers. When
running the debugger against such a parser the output will always tell you we entered the `repeat` and `regex` parsers 
and combinators. But how do you know exactly which one was executed? Is it `$year` or `$month`?

To make this easier, each parser can be given a label which will be used by the debugger:

```php
<?php

use function jubianchi\PPC\Combinators\repeat;
use function jubianchi\PPC\Parsers\regex;

$twoDigits = repeat(2, regex('/[0-9]/'));
$fourDigits = repeat(4, regex('/[0-9]/'));

$year = $fourDigits->label('year');
$month = $twoDigits->label('month');
$day = $twoDigits->label('day');
$hour = $twoDigits->label('hour');
$minute = $twoDigits->label('minute');
$second = $twoDigits->label('second');

```

We should now have a very explicit output:

```
...
[info]   	  > year•repeat(4, regex(/[0-9]/)) {"line":1,"column":0,"ops":0}
[info]   	    > regex(/[0-9]/) {"line":1,"column":0,"ops":0}
[info]   	    < regex(/[0-9]/) {"line":1,"column":1,"duration":0.003302,"consumed":"2","ops":1}
[info]   	    > regex(/[0-9]/) {"line":1,"column":1,"ops":1}
[info]   	    < regex(/[0-9]/) {"line":1,"column":2,"duration":4.0e-6,"consumed":"0","ops":2}
[info]   	    > regex(/[0-9]/) {"line":1,"column":2,"ops":2}
[info]   	    < regex(/[0-9]/) {"line":1,"column":3,"duration":2.0e-6,"consumed":"2","ops":3}
[info]   	    > regex(/[0-9]/) {"line":1,"column":3,"ops":3}
[info]   	    < regex(/[0-9]/) {"line":1,"column":4,"duration":2.0e-6,"consumed":"0","ops":4}
[info]   	  < year•repeat(4, regex(/[0-9]/)) {"line":1,"column":4,"duration":0.003373,"ops":5}
[info]   	  > char(-) {"line":1,"column":4,"ops":5}
[info]   	  < char(-) {"line":1,"column":5,"duration":6.0e-6,"consumed":"-","ops":6}
[info]   	  > month•repeat(2, regex(/[0-9]/)) {"line":1,"column":5,"ops":6}
[info]   	    > regex(/[0-9]/) {"line":1,"column":5,"ops":6}
[info]   	    < regex(/[0-9]/) {"line":1,"column":6,"duration":4.0e-6,"consumed":"0","ops":7}
[info]   	    > regex(/[0-9]/) {"line":1,"column":6,"ops":7}
[info]   	    < regex(/[0-9]/) {"line":1,"column":7,"duration":2.0e-6,"consumed":"7","ops":8}
[info]   	  < month•repeat(2, regex(/[0-9]/)) {"line":1,"column":7,"duration":2.3e-5,"ops":9}
...
```

See how the label changed? Now, for each output line we have the parser's label. 
