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
 * @template T
 * @template D
 *
 * @param \Closure(string, int): (Success<T>|Failure) $parser
 * @param D                                           $default
 *
 * @return \Closure(string, int): Success<T|D>
 */
function opt(\Closure $parser, mixed $default = null): \Closure
{
    return static function (string $input, int $offset) use ($parser, $default): Success {
        $result = $parser($input, $offset);

        return $result instanceof Success ? $result : new Success($default, $offset);
    };
}

/**
 * Matches the parser zero or more times.
 *
 * The returned parser throws a LogicException when the parser succeeds without consuming input,
 * which would otherwise loop forever.
 *
 * @template T
 *
 * @param \Closure(string, int): (Success<T>|Failure) $parser
 *
 * @return \Closure(string, int): Success<list<T>>
 */
function many(\Closure $parser): \Closure
{
    return static function (string $input, int $offset) use ($parser): Success {
        $values = [];

        while (($result = $parser($input, $offset)) instanceof Success) {
            if ($result->offset === $offset) {
                throw new \LogicException(\sprintf('Parser succeeded without consuming input at offset %d', $offset));
            }

            $values[] = $result->value;
            $offset = $result->offset;
        }

        return new Success($values, $offset);
    };
}

/**
 * Matches the parser one or more times.
 *
 * @template T
 *
 * @param \Closure(string, int): (Success<T>|Failure) $parser
 *
 * @return \Closure(string, int): (Success<non-empty-list<T>>|Failure)
 */
function many1(\Closure $parser): \Closure
{
    $many = many($parser);

    return static function (string $input, int $offset) use ($parser, $many): Success|Failure {
        $first = $parser($input, $offset);

        if ($first instanceof Failure) {
            return $first;
        }

        $rest = $many($input, $first->offset);

        return new Success([$first->value, ...$rest->value], $rest->offset);
    };
}

/**
 * @template T
 *
 * @param \Closure(string, int): (Success<T>|Failure) $parser
 *
 * @return \Closure(string, int): (Success<list<T>>|Failure)
 */
function repeat(int $times, \Closure $parser): \Closure
{
    if ($times < 0) {
        throw new \InvalidArgumentException(\sprintf('Expected a positive count, got %d', $times));
    }

    return static function (string $input, int $offset) use ($times, $parser): Success|Failure {
        $values = [];

        for ($i = 0; $i < $times; ++$i) {
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
