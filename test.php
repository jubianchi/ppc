<?php

require_once __DIR__ . '/vendor/autoload.php';

use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Stream;
use function jubianchi\PPC\Combinators\{opt, separated, seq};
use function jubianchi\PPC\Parsers\{char, eos, regex};

$separator = seq(char(','), opt(char(' ')));
$list = seq(
    char('['),
    seq(
        separated(
            $separator,
            opt(regex('/[0-9]/'))
        ),
        opt($separator)
    ),
    char(']'),
    eos()
);

$stream = new Stream('[0, 1, 2, , 4, 5,, 7, 8, 9,]');
$result = $list($stream);

assert($result instanceof Result\Success);
