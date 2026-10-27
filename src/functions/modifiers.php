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
 * @return \Closure(\Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)): (\Closure(string, int, bool=): (Success<U>|Failure|Partial<U>))
 */
function map(\Closure $mapper): \Closure
{
    return static fn (\Closure $parser): \Closure => mapped($parser, $mapper);
}

/**
 * Replaces the expectations of a failure that did not go past the offset the parser started at.
 *
 * @return \Closure(\Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>)): (\Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>))
 */
function label(string $label): \Closure
{
    return static fn (\Closure $parser): \Closure => labelled($parser, $label);
}

/**
 * Returns the text consumed by the parser instead of its value.
 *
 * @return \Closure(\Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>)): (\Closure(string, int, bool=): (Success<string>|Failure|Partial<string>))
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
 * @return \Closure(\Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>)): (\Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>))
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
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) $parser
 * @param \Closure(T): U                                                $mapper
 *
 * @return \Closure(string, int, bool=): (Success<U>|Failure|Partial<U>)
 */
function mapped(\Closure $parser, \Closure $mapper): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser, $mapper): Success|Failure|Partial {
        $result = $parser($input, $offset, $partial);

        if ($result instanceof Success) {
            return new Success($mapper($result->value), $result->offset, $result->hint);
        }

        return $result instanceof Failure
            ? $result
            : then($result, static fn (Success|Failure $result): Success|Failure => $result instanceof Success ? new Success($mapper($result->value), $result->offset, $result->hint) : $result);
    };
}

/**
 * @internal
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>) $parser
 *
 * @return \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>)
 */
function labelled(\Closure $parser, string $label): \Closure
{
    $expected = [$label];

    return static function (string $input, int $offset, bool $partial = false) use ($parser, $expected): Success|Failure|Partial {
        $result = $parser($input, $offset, $partial);

        if ($result instanceof Success) {
            return $result->hint?->offset === $offset ? new Success($result->value, $result->offset, new Failure($expected, $offset)) : $result;
        }

        if ($result instanceof Failure) {
            return $result->offset === $offset ? new Failure($expected, $offset) : $result;
        }

        return then($result, static fn (Success|Failure $result): Success|Failure => relabelled($result, $offset, $expected));
    };
}

/**
 * @internal
 *
 * @template T
 *
 * @param Success<T>|Failure $result
 * @param list<string>       $expected
 *
 * @return Success<T>|Failure
 */
function relabelled(Success|Failure $result, int $offset, array $expected): Success|Failure
{
    if ($result instanceof Failure) {
        return $result->offset === $offset ? new Failure($expected, $offset) : $result;
    }

    return $result->hint?->offset === $offset ? new Success($result->value, $result->offset, new Failure($expected, $offset)) : $result;
}

/**
 * @internal
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>) $parser
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Partial<string>)
 */
function captured(\Closure $parser): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser): Success|Failure|Partial {
        $result = $parser($input, $offset, $partial);

        if ($result instanceof Success) {
            return new Success(substr($input, $offset, $result->offset - $offset), $result->offset, $result->hint);
        }

        return $result instanceof Failure
            ? $result
            : then($result, static fn (Success|Failure $result, string $input): Success|Failure => $result instanceof Success
                ? new Success(substr($input, $offset, $result->offset - $offset), $result->offset, $result->hint)
                : $result);
    };
}

/**
 * @internal
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>) $parser
 * @param \Closure(string): mixed                                               $write
 *
 * @return \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>)
 */
function traced(\Closure $parser, string $name, \Closure $write): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser, $name, $write): Success|Failure|Partial {
        $write(\sprintf('%s: start at %s', $name, position($input, $offset)));

        $result = $parser($input, $offset, $partial);

        if ($result instanceof Partial) {
            return then($result, static fn (Success|Failure $result, string $input): Success|Failure => trace($result, $name, $write, $input, $offset));
        }

        return trace($result, $name, $write, $input, $offset);
    };
}

/**
 * @internal
 *
 * @template T
 *
 * @param Success<T>|Failure      $result
 * @param \Closure(string): mixed $write
 *
 * @return Success<T>|Failure
 */
function trace(Success|Failure $result, string $name, \Closure $write, string $input, int $offset): Success|Failure
{
    $write($result instanceof Success
        ? \sprintf('%s: matched %s up to %s', $name, quote(substr($input, $offset, $result->offset - $offset)), position($input, $result->offset))
        : \sprintf('%s: %s', $name, explain($result, $input)));

    return $result;
}
