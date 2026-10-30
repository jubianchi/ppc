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
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $first
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) ...$parsers
 *
 * @return \Closure(string, int, bool=): (Success<list<mixed>>|Failure|Incomplete)
 */
function seq(\Closure $first, \Closure ...$parsers): \Closure
{
    array_unshift($parsers, $first);

    return static function (string $input, int $offset, bool $partial = false) use ($parsers): Result {
        $values = [];
        $hint = null;

        foreach ($parsers as $parser) {
            $result = $parser($input, $offset, $partial);

            if (!$result instanceof Success) {
                return $result instanceof Failure ? furthest($hint, $result) : $result;
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
 * An incomplete parser is returned as is: the next ones are not tried.
 *
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete) $first
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete) ...$parsers
 *
 * @return \Closure(string, int, bool=): (Success<T>|Failure|Incomplete)
 */
function alt(\Closure $first, \Closure ...$parsers): \Closure
{
    array_unshift($parsers, $first);

    return static function (string $input, int $offset, bool $partial = false) use ($parsers): Result {
        $failure = null;

        foreach ($parsers as $parser) {
            $result = $parser($input, $offset, $partial);

            if ($result instanceof Success) {
                return null === $failure ? $result : new Success($result->value, $result->offset, furthest($failure, $result->hint));
            }

            if ($result instanceof Incomplete) {
                return $result;
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
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete) $parser
 * @param D                                                             $default
 *
 * @return \Closure(string, int, bool=): (Success<T|D>|Incomplete)
 */
function opt(\Closure $parser, mixed $default = null): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser, $default): Result {
        $result = $parser($input, $offset, $partial);

        return $result instanceof Failure ? new Success($default, $offset, $result) : $result;
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
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete) $parser
 *
 * @return \Closure(string, int, bool=): (Success<list<T>>|Incomplete)
 */
function many(\Closure $parser): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser): Result {
        $values = [];
        $hint = null;

        while (true) {
            $result = $parser($input, $offset, $partial);

            if (!$result instanceof Success) {
                return $result instanceof Failure ? new Success($values, $offset, furthest($hint, $result)) : $result;
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
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete) $parser
 *
 * @return \Closure(string, int, bool=): (Success<non-empty-list<T>>|Failure|Incomplete)
 */
function many1(\Closure $parser): \Closure
{
    $many = many($parser);

    return static function (string $input, int $offset, bool $partial = false) use ($parser, $many): Result {
        $first = $parser($input, $offset, $partial);

        if (!$first instanceof Success) {
            return $first;
        }

        $rest = $many($input, $first->offset, $partial);

        if ($rest instanceof Incomplete) {
            return $rest;
        }

        return new Success([$first->value, ...$rest->value], $rest->offset, furthest($first->hint, $rest->hint));
    };
}

/**
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete) $parser
 *
 * @return \Closure(string, int, bool=): (Success<list<T>>|Failure|Incomplete)
 */
function repeat(int $times, \Closure $parser): \Closure
{
    if ($times < 0) {
        throw new \InvalidArgumentException(\sprintf('Expected a positive count, got %d', $times));
    }

    return static function (string $input, int $offset, bool $partial = false) use ($times, $parser): Result {
        $values = [];
        $hint = null;

        for ($i = 0; $i < $times; ++$i) {
            $result = $parser($input, $offset, $partial);

            if (!$result instanceof Success) {
                return $result instanceof Failure ? furthest($hint, $result) : $result;
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
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $separator
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete)     $parser
 *
 * @return \Closure(string, int, bool=): (Success<list<T>>|Incomplete)
 */
function separated(\Closure $separator, \Closure $parser): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($separator, $parser): Result {
        $result = $parser($input, $offset, $partial);

        if (!$result instanceof Success) {
            return $result instanceof Failure ? new Success([], $offset, $result) : $result;
        }

        $values = [$result->value];
        $offset = $result->offset;
        $hint = $result->hint;

        while (true) {
            $next = $separator($input, $offset, $partial);

            if (!$next instanceof Success) {
                return $next instanceof Failure ? new Success($values, $offset, furthest($hint, $next)) : $next;
            }

            $result = $parser($input, $next->offset, $partial);

            if (!$result instanceof Success) {
                return $result instanceof Failure ? new Success($values, $offset, furthest($hint, furthest($next->hint, $result))) : $result;
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
 * Matches one or more operands separated by operators, folded from the left with the functions returned by the
 * operators: a | b | c is (a | b) | c. A trailing operator is not consumed.
 *
 * The returned parser throws a LogicException when the operator and the operand both succeed
 * without consuming input, which would otherwise loop forever.
 *
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete)                 $operand
 * @param \Closure(string, int, bool=): (Success<\Closure(T, T): T>|Failure|Incomplete) $operator
 *
 * @return \Closure(string, int, bool=): (Success<T>|Failure|Incomplete)
 */
function chainLeft(\Closure $operand, \Closure $operator): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($operand, $operator): Result {
        $result = $operand($input, $offset, $partial);

        if (!$result instanceof Success) {
            return $result;
        }

        $value = $result->value;
        $offset = $result->offset;
        $hint = $result->hint;

        while (true) {
            $next = $operator($input, $offset, $partial);

            if (!$next instanceof Success) {
                return $next instanceof Failure ? new Success($value, $offset, furthest($hint, $next)) : $next;
            }

            $result = $operand($input, $next->offset, $partial);

            if (!$result instanceof Success) {
                return $result instanceof Failure ? new Success($value, $offset, furthest($hint, furthest($next->hint, $result))) : $result;
            }

            if ($result->offset === $offset) {
                throw new \LogicException(\sprintf('Operator and operand succeeded without consuming input at offset %d', $offset));
            }

            $value = ($next->value)($value, $result->value);
            $offset = $result->offset;
            $hint = furthest($hint, furthest($next->hint, $result->hint));
        }
    };
}

/**
 * Matches one or more operands separated by operators, folded from the right with the functions returned by the
 * operators: a -> b -> c is a -> (b -> c). A trailing operator is not consumed.
 *
 * The returned parser throws a LogicException when the operator and the operand both succeed
 * without consuming input, which would otherwise loop forever.
 *
 * @template U
 *
 * @param \Closure(string, int, bool=): (Success<U>|Failure|Incomplete)                 $operand
 * @param \Closure(string, int, bool=): (Success<\Closure(U, U): U>|Failure|Incomplete) $operator
 *
 * @return \Closure(string, int, bool=): (Success<U>|Failure|Incomplete)
 */
function chainRight(\Closure $operand, \Closure $operator): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($operand, $operator): Result {
        $result = $operand($input, $offset, $partial);

        if (!$result instanceof Success) {
            return $result;
        }

        $pending = [];
        $value = $result->value;
        $offset = $result->offset;
        $hint = $result->hint;

        while (true) {
            $next = $operator($input, $offset, $partial);

            if (!$next instanceof Success) {
                if ($next instanceof Incomplete) {
                    return $next;
                }

                $hint = furthest($hint, $next);

                break;
            }

            $result = $operand($input, $next->offset, $partial);

            if (!$result instanceof Success) {
                if ($result instanceof Incomplete) {
                    return $result;
                }

                $hint = furthest($hint, furthest($next->hint, $result));

                break;
            }

            if ($result->offset === $offset) {
                throw new \LogicException(\sprintf('Operator and operand succeeded without consuming input at offset %d', $offset));
            }

            $pending[] = [$value, $next->value];
            $value = $result->value;
            $offset = $result->offset;
            $hint = furthest($hint, furthest($next->hint, $result->hint));
        }

        while ([] !== $pending) {
            [$left, $apply] = array_pop($pending);
            $value = $apply($left, $value);
        }

        return new Success($value, $offset, $hint);
    };
}

/**
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $before
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete)     $parser
 *
 * @return \Closure(string, int, bool=): (Success<T>|Failure|Incomplete)
 */
function preceded(\Closure $before, \Closure $parser): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($before, $parser): Result {
        $prefix = $before($input, $offset, $partial);

        if (!$prefix instanceof Success) {
            return $prefix;
        }

        $result = $parser($input, $prefix->offset, $partial);

        if (!$result instanceof Success) {
            return $result instanceof Failure ? furthest($prefix->hint, $result) : $result;
        }

        return new Success($result->value, $result->offset, furthest($prefix->hint, $result->hint));
    };
}

/**
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)  $before
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete)      $parser
 * @param ?\Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $after  defaults to $before
 *
 * @return \Closure(string, int, bool=): (Success<T>|Failure|Incomplete)
 */
function enclosed(\Closure $before, \Closure $parser, ?\Closure $after = null): \Closure
{
    $after ??= $before;

    return static function (string $input, int $offset, bool $partial = false) use ($before, $parser, $after): Result {
        $opening = $before($input, $offset, $partial);

        if (!$opening instanceof Success) {
            return $opening;
        }

        $result = $parser($input, $opening->offset, $partial);

        if (!$result instanceof Success) {
            return $result instanceof Failure ? furthest($opening->hint, $result) : $result;
        }

        $hint = furthest($opening->hint, $result->hint);
        $closing = $after($input, $result->offset, $partial);

        if (!$closing instanceof Success) {
            return $closing instanceof Failure ? furthest($hint, $closing) : $closing;
        }

        return new Success($result->value, $closing->offset, furthest($hint, $closing->hint));
    };
}

/**
 * Consumes one char when the parser does not match at the offset.
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $parser
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function not(\Closure $parser): \Closure
{
    $any = any();

    return static function (string $input, int $offset, bool $partial = false) use ($parser, $any): Result {
        $result = $parser($input, $offset, $partial);

        if ($result instanceof Success) {
            return new Failure(['anything but '.quote(substr($input, $offset, $result->offset - $offset))], $offset);
        }

        return $result instanceof Failure ? $any($input, $offset, $partial) : $result;
    };
}

/**
 * Defines a recursive parser: the definition receives the parser being defined,
 * e.g. recursive(fn ($nested) => alt(char('x'), enclosed(char('('), $nested, char(')')))).
 *
 * The returned parser throws a LogicException when it calls itself at the same offset of the same input: parsers are
 * pure, so such a left recursion would never end.
 *
 * @template T
 *
 * @param \Closure(\Closure(string, int, bool=): (Success<T>|Failure|Incomplete)): (\Closure(string, int, bool=): (Success<T>|Failure|Incomplete)) $definition
 *
 * @return \Closure(string, int, bool=): (Success<T>|Failure|Incomplete)
 */
function recursive(\Closure $definition): \Closure
{
    /** @var ?\Closure(string, int, bool=): (Success<T>|Failure|Incomplete) $parser */
    $parser = null;
    /** @var array<int, string> $running */
    $running = [];
    $self = static function (string $input, int $offset, bool $partial = false) use (&$parser, &$running): Result {
        \assert($parser instanceof \Closure);

        $previous = $running[$offset] ?? null;

        if ($previous === $input) {
            throw new \LogicException(\sprintf('Left recursion detected at offset %d: the recursive parser calls itself without consuming input, use chainLeft() for left-associative operators', $offset));
        }

        $running[$offset] = $input;

        try {
            return $parser($input, $offset, $partial);
        } finally {
            if (null === $previous) {
                unset($running[$offset]);
            } else {
                $running[$offset] = $previous;
            }
        }
    };

    return $parser = $definition($self);
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
