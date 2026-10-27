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
 * @param \Closure(string, int): (Success<mixed>|Failure) $first
 * @param \Closure(string, int): (Success<mixed>|Failure) ...$parsers
 *
 * @return \Closure(string, int): (Success<list<mixed>>|Failure)
 */
function seq(\Closure $first, \Closure ...$parsers): \Closure
{
    array_unshift($parsers, $first);

    return static function (string $input, int $offset) use ($parsers): Success|Failure {
        $values = [];

        foreach ($parsers as $parser) {
            $result = $parser($input, $offset);

            if ($result instanceof Failure) {
                return $result;
            }

            $values[] = $result->value;
            $offset = $result->offset;
        }

        return new Success($values, $offset);
    };
}

/**
 * When every parser fails, returns the failure that went the furthest in the input.
 *
 * @template T
 *
 * @param \Closure(string, int): (Success<T>|Failure) $first
 * @param \Closure(string, int): (Success<T>|Failure) ...$parsers
 *
 * @return \Closure(string, int): (Success<T>|Failure)
 */
function alt(\Closure $first, \Closure ...$parsers): \Closure
{
    array_unshift($parsers, $first);

    return static function (string $input, int $offset) use ($parsers): Success|Failure {
        $failure = null;

        foreach ($parsers as $parser) {
            $result = $parser($input, $offset);

            if ($result instanceof Success) {
                return $result;
            }

            $failure = null === $failure ? $result : furthest($failure, $result);
        }

        return $failure;
    };
}

/**
 * @internal
 */
function furthest(Failure $first, Failure $second): Failure
{
    return match ($first->offset <=> $second->offset) {
        1 => $first,
        -1 => $second,
        0 => new Failure([...$first->expected, ...$second->expected], $first->offset),
    };
}
