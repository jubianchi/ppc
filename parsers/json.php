<?php

/**
 * This file is part of PPC.
 *
 * Copyright © 2026 Julien Bianchi <contact@jubianchi.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace jubianchi\PPC\Parsers;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Partial;
use jubianchi\PPC\Success;

use function jubianchi\PPC\alt;
use function jubianchi\PPC\char;
use function jubianchi\PPC\enclosed;
use function jubianchi\PPC\eos;
use function jubianchi\PPC\label;
use function jubianchi\PPC\many;
use function jubianchi\PPC\map;
use function jubianchi\PPC\recursive;
use function jubianchi\PPC\regex;
use function jubianchi\PPC\separated;
use function jubianchi\PPC\seq;
use function jubianchi\PPC\word;

/**
 * Parses a JSON document (RFC 8259) into the same values as json_decode().
 *
 * @return \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>)
 */
function json(): \Closure
{
    $whitespace = regex('/[ \t\n\r]*/');
    $token = static fn (\Closure $parser): \Closure => seq($parser, $whitespace) |> map(static fn (array $values): mixed => $values[0]);

    $string = enclosed(
        char('"'),
        many(alt(
            regex('/[^"\\\\\x00-\x1F]+/') |> label('character'),
            regex('/\\\\(?:["\\\\\/bfnrt]|u[0-9a-fA-F]{4}(?:\\\\u[0-9a-fA-F]{4})*)/') |> label('escape sequence') |> map(unescape(...)),
        )) |> map(implode(...)),
    ) |> label('string');

    $number = regex('/-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/')
        |> label('number')
        |> map(static fn (string $number): int|float => false === strpbrk($number, '.eE') && false !== ($integer = filter_var($number, \FILTER_VALIDATE_INT))
            ? $integer
            : (float) $number);

    $value = recursive(static function (\Closure $value) use ($token, $string, $number): \Closure {
        $pair = seq($string |> $token, char(':') |> $token, $value);
        $object = enclosed(char('{') |> $token, separated(char(',') |> $token, $pair), char('}'))
            |> map(static function (array $pairs): \stdClass {
                $object = new \stdClass();

                /** @var list<array{string, string, mixed}> $pairs */
                foreach ($pairs as [$key, , $member]) {
                    $object->{$key} = $member;
                }

                return $object;
            });

        $array = enclosed(char('[') |> $token, separated(char(',') |> $token, $value), char(']'));

        return alt(
            $object,
            $array,
            $string,
            $number,
            word('true') |> map(static fn (): bool => true),
            word('false') |> map(static fn (): bool => false),
            word('null') |> map(static fn (): null => null),
        ) |> label('value') |> $token;
    });

    return seq($whitespace, $value, eos()) |> map(static fn (array $values): mixed => $values[1]);
}

/**
 * @internal
 */
function unescape(string $escape): string
{
    return match ($escape[1]) {
        'b' => "\x08",
        'f' => "\f",
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
        'u' => mb_convert_encoding((string) hex2bin(str_replace('\u', '', $escape)), 'UTF-8', 'UTF-16BE'),
        default => $escape[1],
    };
}
