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
        $hint = null;

        foreach ($parsers as $parser) {
            $result = $parser($input, $offset);

            if ($result instanceof Failure) {
                return furthest($hint, $result);
            }

            $values[] = $result->value;
            $offset = $result->offset;
            $hint = furthest($hint, $result->hint);
        }

        return new Success($values, $offset, $hint);
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
                return null === $failure ? $result : new Success($result->value, $result->offset, furthest($failure, $result->hint));
            }

            $failure = furthest($failure, $result);
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

        return $result instanceof Success ? $result : new Success($default, $offset, $result);
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
        $hint = null;

        while (true) {
            $result = $parser($input, $offset);

            if ($result instanceof Failure) {
                return new Success($values, $offset, furthest($hint, $result));
            }

            if ($result->offset === $offset) {
                throw new \LogicException(\sprintf('Parser succeeded without consuming input at offset %d', $offset));
            }

            $values[] = $result->value;
            $offset = $result->offset;
            $hint = furthest($hint, $result->hint);
        }
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

        return new Success([$first->value, ...$rest->value], $rest->offset, furthest($first->hint, $rest->hint));
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
        $hint = null;

        for ($i = 0; $i < $times; ++$i) {
            $result = $parser($input, $offset);

            if ($result instanceof Failure) {
                return furthest($hint, $result);
            }

            $values[] = $result->value;
            $offset = $result->offset;
            $hint = furthest($hint, $result->hint);
        }

        return new Success($values, $offset, $hint);
    };
}

/**
 * Matches the parser zero or more times, separated by the separator whose values are discarded.
 * A trailing separator is not consumed.
 *
 * The returned parser throws a LogicException when the separator and the parser both succeed
 * without consuming input, which would otherwise loop forever.
 *
 * @template T
 *
 * @param \Closure(string, int): (Success<mixed>|Failure) $separator
 * @param \Closure(string, int): (Success<T>|Failure)     $parser
 *
 * @return \Closure(string, int): Success<list<T>>
 */
function separated(\Closure $separator, \Closure $parser): \Closure
{
    return static function (string $input, int $offset) use ($separator, $parser): Success {
        $result = $parser($input, $offset);

        if ($result instanceof Failure) {
            return new Success([], $offset, $result);
        }

        $values = [$result->value];
        $offset = $result->offset;
        $hint = $result->hint;

        while (true) {
            $next = $separator($input, $offset);

            if ($next instanceof Failure) {
                return new Success($values, $offset, furthest($hint, $next));
            }

            $result = $parser($input, $next->offset);

            if ($result instanceof Failure) {
                return new Success($values, $offset, furthest($hint, furthest($next->hint, $result)));
            }

            if ($result->offset === $offset) {
                throw new \LogicException(\sprintf('Separator and parser succeeded without consuming input at offset %d', $offset));
            }

            $values[] = $result->value;
            $offset = $result->offset;
            $hint = furthest($hint, furthest($next->hint, $result->hint));
        }
    };
}

/**
 * @template T
 *
 * @param \Closure(string, int): (Success<mixed>|Failure)  $before
 * @param \Closure(string, int): (Success<T>|Failure)      $parser
 * @param ?\Closure(string, int): (Success<mixed>|Failure) $after  defaults to $before
 *
 * @return \Closure(string, int): (Success<T>|Failure)
 */
function enclosed(\Closure $before, \Closure $parser, ?\Closure $after = null): \Closure
{
    $after ??= $before;

    return static function (string $input, int $offset) use ($before, $parser, $after): Success|Failure {
        $opening = $before($input, $offset);

        if ($opening instanceof Failure) {
            return $opening;
        }

        $result = $parser($input, $opening->offset);

        if ($result instanceof Failure) {
            return furthest($opening->hint, $result);
        }

        $hint = furthest($opening->hint, $result->hint);
        $closing = $after($input, $result->offset);

        if ($closing instanceof Failure) {
            return furthest($hint, $closing);
        }

        return new Success($result->value, $closing->offset, furthest($hint, $closing->hint));
    };
}

/**
 * Consumes one char when the parser does not match at the offset.
 *
 * @param \Closure(string, int): (Success<mixed>|Failure) $parser
 *
 * @return \Closure(string, int): (Success<string>|Failure)
 */
function not(\Closure $parser): \Closure
{
    $any = any();

    return static function (string $input, int $offset) use ($parser, $any): Success|Failure {
        $result = $parser($input, $offset);

        if ($result instanceof Success) {
            return new Failure(['anything but '.quote(substr($input, $offset, $result->offset - $offset))], $offset);
        }

        return $any($input, $offset);
    };
}

/**
 * Builds the parser on its first use, which allows recursive grammars:
 * lazy(function () use (&$value) { return $value; }).
 *
 * @template T
 *
 * @param \Closure(): (\Closure(string, int): (Success<T>|Failure)) $factory
 *
 * @return \Closure(string, int): (Success<T>|Failure)
 */
function lazy(\Closure $factory): \Closure
{
    $parser = null;

    return static function (string $input, int $offset) use ($factory, &$parser): Success|Failure {
        $parser ??= $factory();

        return $parser($input, $offset);
    };
}

/**
 * Returns the failure that went the furthest, merging the expectations of failures at the same offset.
 *
 * @internal
 *
 * @return ($second is null ? ($first is null ? null : Failure) : Failure)
 */
function furthest(?Failure $first, ?Failure $second): ?Failure
{
    if (null === $first || null === $second) {
        return $first ?? $second;
    }

    return match ($first->offset <=> $second->offset) {
        1 => $first,
        -1 => $second,
        0 => new Failure([...$first->expected, ...$second->expected], $first->offset),
    };
}
