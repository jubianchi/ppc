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

namespace jubianchi\PPC;

/**
 * @template T
 * @template U
 *
 * @param \Closure(T): U $mapper
 *
 * @return \Closure(\Closure(string, int, bool=): (Success<T>|Failure|Incomplete)): (\Closure(string, int, bool=): (Success<U>|Failure|Incomplete))
 */
function map(\Closure $mapper): \Closure
{
    return static fn (\Closure $parser): \Closure => mapped($parser, $mapper);
}

/**
 * Replaces the expectations of a failure that did not go past the offset the parser started at.
 *
 * @return \Closure(\Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)): (\Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete))
 */
function label(string $label): \Closure
{
    return static fn (\Closure $parser): \Closure => labelled($parser, $label);
}

/**
 * Matches the parser or nothing: when the parser fails, succeeds with the default without consuming input.
 *
 * @template D
 *
 * @param D $default
 *
 * @return \Closure<T>(\Closure(string, int, bool=): (Success<T>|Failure|Incomplete)): (\Closure(string, int, bool=): (Success<T|D>|Incomplete))
 */
function optional(mixed $default = null): \Closure
{
    return static fn (\Closure $parser): \Closure => defaulted($parser, $default);
}

/**
 * Returns the text consumed by the parser instead of its value.
 *
 * @return \Closure(\Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)): (\Closure(string, int, bool=): (Success<string>|Failure|Incomplete))
 */
function capture(): \Closure
{
    return static fn (\Closure $parser): \Closure => captured($parser);
}

/**
 * Looks ahead: succeeds with the value of the parser, without consuming input.
 *
 * @return \Closure<T>(\Closure(string, int, bool=): (Success<T>|Failure|Incomplete)): (\Closure(string, int, bool=): (Success<T>|Failure|Incomplete))
 */
function peek(): \Closure
{
    return static fn (\Closure $parser): \Closure => peeked($parser);
}

/**
 * Negative lookahead: succeeds with null when the parser fails, fails when it succeeds, never consuming input.
 *
 * @return \Closure(\Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)): (\Closure(string, int, bool=): (Success<null>|Failure|Incomplete))
 */
function negate(): \Closure
{
    return static fn (\Closure $parser): \Closure => negated($parser);
}

/**
 * Traces each run of the parser, e.g. debug('value', error_log(...)).
 *
 * @param \Closure(string): mixed $write
 *
 * @return \Closure(\Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)): (\Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete))
 */
function debug(string $name, \Closure $write): \Closure
{
    return static fn (\Closure $parser): \Closure => traced($parser, $name, $write);
}

/**
 * @internal
 *
 * @template T
 * @template U
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete) $parser
 * @param \Closure(T): U                                                $mapper
 *
 * @return \Closure(string, int, bool=): (Success<U>|Failure|Incomplete)
 */
function mapped(\Closure $parser, \Closure $mapper): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser, $mapper): Result {
        $result = $parser($input, $offset, $partial);

        return $result instanceof Success ? new Success($mapper($result->value), $result->offset, $result->hint) : $result;
    };
}

/**
 * @internal
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $parser
 *
 * @return \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)
 */
function labelled(\Closure $parser, string $label): \Closure
{
    $expected = [$label];

    return static function (string $input, int $offset, bool $partial = false) use ($parser, $expected): Result {
        $result = $parser($input, $offset, $partial);

        if ($result instanceof Failure) {
            return $result->offset === $offset ? new Failure($expected, $offset, $result->length) : $result;
        }

        if ($result instanceof Incomplete) {
            return $result;
        }

        return $result->hint?->offset === $offset ? new Success($result->value, $result->offset, new Failure($expected, $offset, $result->hint->length)) : $result;
    };
}

/**
 * @internal
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $parser
 *
 * @return \Closure(string, int, bool=): (Success<mixed>|Incomplete)
 */
function defaulted(\Closure $parser, mixed $default): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser, $default): Result {
        $result = $parser($input, $offset, $partial);

        return $result instanceof Failure ? new Success($default, $offset, $result) : $result;
    };
}

/**
 * @internal
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $parser
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function captured(\Closure $parser): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser): Result {
        $result = $parser($input, $offset, $partial);

        return $result instanceof Success ? new Success(substr($input, $offset, $result->offset - $offset), $result->offset, $result->hint) : $result;
    };
}

/**
 * @internal
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $parser
 *
 * @return \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)
 */
function peeked(\Closure $parser): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser): Result {
        $result = $parser($input, $offset, $partial);

        return $result instanceof Success ? new Success($result->value, $offset, $result->hint) : $result;
    };
}

/**
 * @internal
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $parser
 *
 * @return \Closure(string, int, bool=): (Success<null>|Failure|Incomplete)
 */
function negated(\Closure $parser): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser): Result {
        $result = $parser($input, $offset, $partial);

        return match (true) {
            $result instanceof Success => new Failure(['anything but '.quote(substr($input, $offset, $result->offset - $offset))], $offset),
            $result instanceof Failure => new Success(null, $offset),
            default => $result,
        };
    };
}

/**
 * @internal
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $parser
 * @param \Closure(string): mixed                                           $write
 *
 * @return \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)
 */
function traced(\Closure $parser, string $name, \Closure $write): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser, $name, $write): Result {
        $write(\sprintf('%s: start at %s', $name, position($input, $offset)));

        $result = $parser($input, $offset, $partial);

        $write(match (true) {
            $result instanceof Success => \sprintf('%s: matched %s up to %s', $name, quote(substr($input, $offset, $result->offset - $offset)), position($input, $result->offset)),
            $result instanceof Failure => \sprintf('%s: %s', $name, explain($result, $input)),
            default => \sprintf('%s: incomplete input%s', $name, null === $result->needed ? '' : \sprintf(', %d more bytes needed', $result->needed)),
        });

        return $result;
    };
}
