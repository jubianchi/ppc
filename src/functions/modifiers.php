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
 * Returns the text consumed by the parser instead of its value.
 *
 * @return \Closure(\Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)): (\Closure(string, int, bool=): (Success<string>|Failure|Incomplete))
 */
function consumed(): \Closure
{
    return static fn (\Closure $parser): \Closure => captured($parser);
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
    return static function (string $input, int $offset, bool $partial = false) use ($parser, $mapper) {
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

    return static function (string $input, int $offset, bool $partial = false) use ($parser, $expected) {
        $result = $parser($input, $offset, $partial);

        if ($result instanceof Failure) {
            return $result->offset === $offset ? new Failure($expected, $offset) : $result;
        }

        if ($result instanceof Incomplete) {
            return $result;
        }

        return $result->hint?->offset === $offset ? new Success($result->value, $result->offset, new Failure($expected, $offset)) : $result;
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
    return static function (string $input, int $offset, bool $partial = false) use ($parser) {
        $result = $parser($input, $offset, $partial);

        return $result instanceof Success ? new Success(substr($input, $offset, $result->offset - $offset), $result->offset, $result->hint) : $result;
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
    return static function (string $input, int $offset, bool $partial = false) use ($parser, $name, $write) {
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
