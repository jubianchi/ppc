<?php
/**
 * This file is part of PPC.
 *
 * Copyright © 2020 Julien Bianchi <contact@jubianchi.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace jubianchi\PPC\Parsers;

use function jubianchi\PPC\Combinators\alt;
use function jubianchi\PPC\Combinators\enclosed;
use function jubianchi\PPC\Combinators\many;
use function jubianchi\PPC\Combinators\not;
use function jubianchi\PPC\Combinators\opt;
use function jubianchi\PPC\Combinators\recurse;
use function jubianchi\PPC\Combinators\separated;
use function jubianchi\PPC\Combinators\seq;
use function jubianchi\PPC\Mappers\concat;
use function jubianchi\PPC\Mappers\first;
use function jubianchi\PPC\Mappers\otherwise;
use function jubianchi\PPC\Mappers\php;
use function jubianchi\PPC\Mappers\skip;
use function jubianchi\PPC\Mappers\structure;
use function jubianchi\PPC\Mappers\value;
use jubianchi\PPC\Parser;
use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Parser\Result\Success;

function json(): Parser
{
    $space = regex('/\s/')->label('space');
    $spaces = opt(many($space))->label('spaces')->map(skip())->stringify(fn (string $label): string => $label);
    $comma = seq($spaces, char(','), $spaces)->label('comma')->map(skip())->stringify(fn (string $label): string => $label);
    $colon = seq($spaces, char(':'), $spaces)->label('colon')->map(skip())->stringify(fn (string $label): string => $label);

    $string = enclosed(
        char('"'),
        opt(
            many(
                alt(
                    char('\\'),
                    word('\"'),
                    not(char('"'))
                ),
            )->map(concat()->then(php('stripcslashes'))),
        )->map(otherwise('')),
    )
        ->label('string');

    $boolean = alt(
        word('true')->map(value(true)),
        word('false')->map(value(false)),
    )
        ->label('boolean');

    $null = word('null')
        ->map(fn (Result $result) => new Success(null))
        ->label('null');

    $numeric = alt(
        char('0'),
        seq(
            regex('/[1-9]/'),
            opt(many(regex('/[0-9]/'))),
            opt(
                seq(
                    char('.'),
                    regex('/[0-9]/'),
                    opt(many(regex('/[0-9]/'))),
                )
            )
        )
    )
        ->label('numeric');

    $pair = seq(
        recurse($string),
        $colon,
        recurse($value),
    )
        ->label('pair')
        ->map(structure(['key', 'value']));

    $members = separated($comma, $pair)
        ->label('members');

    $object = enclosed(
        seq(char('{'), $spaces),
        opt($members),
        seq($spaces, char('}')),
    )
        ->label('object')
        ->map(function (Result $result): Result {
            $object = new \StdClass();

            foreach ($result->result() as $pair) {
                ['key' => $key, 'value' => $value] = $pair;

                $object->{$key} = $value;
            }

            return new Success($object);
        });

    $items = separated(
        seq($spaces, $comma = char(','), $spaces)->stringify(fn (): string => (string) $comma),
        recurse($value)
    )
        ->label('items');

    $array = enclosed(
        seq($open = char('['), $spaces)->stringify(fn (): string => (string) $open),
        opt($items),
        seq($spaces, $close = char(']'))->stringify(fn (): string => (string) $close),
    )
        ->label('array');

    $value = alt($object, $array, $string, $boolean, $null, $numeric)
        ->label('value')
        ->stringify(fn (string $label): string => $label);

    return seq(
        $value,
        $spaces,
        eos()->map(skip()),
    )
        ->label('json')
        ->stringify(fn (string $label): string => $label)
        ->map(first());
}
