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
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>) $first
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>) ...$parsers
 *
 * @return \Closure(string, int, bool=): (Success<list<mixed>>|Failure|Partial<list<mixed>>)
 */
function seq(\Closure $first, \Closure ...$parsers): \Closure
{
    $parsers = [$first, ...array_values($parsers)];

    return static function (string $input, int $offset, bool $partial = false) use ($parsers): Success|Failure|Partial {
        $values = [];
        $hint = null;

        foreach ($parsers as $parser) {
            $result = $parser($input, $offset, $partial);

            if (!$result instanceof Success) {
                return $result instanceof Failure ? furthest($hint, $result) : sequence($parsers, \count($values), $result, $values, $hint);
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
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) $first
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) ...$parsers
 *
 * @return \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)
 */
function alt(\Closure $first, \Closure ...$parsers): \Closure
{
    $parsers = [$first, ...array_values($parsers)];

    return static function (string $input, int $offset, bool $partial = false) use ($parsers): Success|Failure|Partial {
        $failure = null;

        foreach ($parsers as $index => $parser) {
            $result = $parser($input, $offset, $partial);

            if ($result instanceof Success) {
                return null === $failure ? $result : new Success($result->value, $result->offset, furthest($failure, $result->hint));
            }

            if (!$result instanceof Failure) {
                return alternative($parsers, $index, $result, $offset, $failure);
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
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) $parser
 * @param D                                                             $default
 *
 * @return \Closure(string, int, bool=): (Success<T|D>|Partial<T|D>)
 */
function opt(\Closure $parser, mixed $default = null): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($parser, $default): Success|Partial {
        $result = $parser($input, $offset, $partial);

        if ($result instanceof Success) {
            return $result;
        }

        return $result instanceof Failure
            ? new Success($default, $offset, $result)
            : then($result, static fn (Success|Failure $result): Success => $result instanceof Success ? $result : new Success($default, $offset, $result));
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
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) $parser
 *
 * @return \Closure(string, int, bool=): (Success<list<T>>|Partial<list<T>>)
 */
function many(\Closure $parser): \Closure
{
    return static fn (string $input, int $offset, bool $partial = false): Success|Partial => repeating($parser, $input, $offset, $partial, [], null, null);
}

/**
 * Matches the parser one or more times.
 *
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) $parser
 *
 * @return \Closure(string, int, bool=): (Success<non-empty-list<T>>|Failure|Partial<non-empty-list<T>>)
 */
function many1(\Closure $parser): \Closure
{
    $many = many($parser);

    return static function (string $input, int $offset, bool $partial = false) use ($parser, $many): Success|Failure|Partial {
        $first = $parser($input, $offset, $partial);

        if ($first instanceof Partial) {
            return then($first, static fn (Success|Failure $first, string $input, bool $partial): Success|Failure|Partial => $first instanceof Failure ? $first : prepend($first, $many($input, $first->offset, $partial)));
        }

        return $first instanceof Failure ? $first : prepend($first, $many($input, $first->offset, $partial));
    };
}

/**
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) $parser
 *
 * @return \Closure(string, int, bool=): (Success<list<T>>|Failure|Partial<list<T>>)
 */
function repeat(int $times, \Closure $parser): \Closure
{
    if ($times < 0) {
        throw new \InvalidArgumentException(\sprintf('Expected a positive count, got %d', $times));
    }

    $parsers = array_fill(0, $times, $parser);

    return static function (string $input, int $offset, bool $partial = false) use ($parsers): Success|Failure|Partial {
        $values = [];
        $hint = null;

        foreach ($parsers as $parser) {
            $result = $parser($input, $offset, $partial);

            if (!$result instanceof Success) {
                return $result instanceof Failure ? furthest($hint, $result) : sequence($parsers, \count($values), $result, $values, $hint);
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
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>) $separator
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)         $parser
 *
 * @return \Closure(string, int, bool=): (Success<list<T>>|Partial<list<T>>)
 */
function separated(\Closure $separator, \Closure $parser): \Closure
{
    return static function (string $input, int $offset, bool $partial = false) use ($separator, $parser): Success|Partial {
        $first = $parser($input, $offset, $partial);

        if ($first instanceof Partial) {
            return then($first, static fn (Success|Failure $first, string $input, bool $partial): Success|Partial => $first instanceof Failure
                ? new Success([], $offset, $first)
                : separating($separator, $parser, $input, $first->offset, $partial, [$first->value], $first->hint, null, null, null));
        }

        return $first instanceof Failure
            ? new Success([], $offset, $first)
            : separating($separator, $parser, $input, $first->offset, $partial, [$first->value], $first->hint, null, null, null);
    };
}

/**
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>)  $before
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)          $parser
 * @param ?\Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>) $after  defaults to $before
 *
 * @return \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)
 */
function enclosed(\Closure $before, \Closure $parser, ?\Closure $after = null): \Closure
{
    $after ??= $before;
    $parsers = [$before, $parser, $after];

    return static function (string $input, int $offset, bool $partial = false) use ($before, $parser, $after, $parsers): Success|Failure|Partial {
        $opening = $before($input, $offset, $partial);

        if (!$opening instanceof Success) {
            return $opening instanceof Failure ? $opening : middle(sequence($parsers, 0, $opening, [], null));
        }

        $result = $parser($input, $opening->offset, $partial);

        if (!$result instanceof Success) {
            return $result instanceof Failure
                ? furthest($opening->hint, $result)
                : middle(sequence($parsers, 1, $result, [$opening->value], $opening->hint));
        }

        $hint = furthest($opening->hint, $result->hint);
        $closing = $after($input, $result->offset, $partial);

        if (!$closing instanceof Success) {
            return $closing instanceof Failure
                ? furthest($hint, $closing)
                : middle(sequence($parsers, 2, $closing, [$opening->value, $result->value], $hint));
        }

        return new Success($result->value, $closing->offset, furthest($hint, $closing->hint));
    };
}

/**
 * Consumes one char when the parser does not match at the offset.
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>) $parser
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Partial<string>)
 */
function not(\Closure $parser): \Closure
{
    $any = any();

    return static function (string $input, int $offset, bool $partial = false) use ($parser, $any): Success|Failure|Partial {
        $result = $parser($input, $offset, $partial);

        if ($result instanceof Success) {
            return new Failure(['anything but '.quote(substr($input, $offset, $result->offset - $offset))], $offset);
        }

        if ($result instanceof Failure) {
            return $any($input, $offset, $partial);
        }

        return then($result, static fn (Success|Failure $result, string $input, bool $partial): Success|Failure|Partial => $result instanceof Success
            ? new Failure(['anything but '.quote(substr($input, $offset, $result->offset - $offset))], $offset)
            : $any($input, $offset, $partial));
    };
}

/**
 * Defines a recursive parser: the definition receives the parser being defined,
 * e.g. recursive(fn ($nested) => alt(char('x'), enclosed(char('('), $nested, char(')')))).
 *
 * @template T
 *
 * @param \Closure(\Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)): (\Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)) $definition
 *
 * @return \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)
 */
function recursive(\Closure $definition): \Closure
{
    /** @var ?\Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) $parser */
    $parser = null;
    $self = static function (string $input, int $offset, bool $partial = false) use (&$parser): Success|Failure|Partial {
        \assert($parser instanceof \Closure);

        return $parser($input, $offset, $partial);
    };

    return $parser = $definition($self);
}

/**
 * Returns a Partial handing the result of the pending one to $next once it is decided.
 *
 * Combinators keep their fast path free of resumption logic: when a parser asks for more input, they hand their
 * state over to one of the functions below, which continue where they stopped.
 *
 * @internal
 *
 * @template T
 * @template U
 *
 * @param Partial<T>                                                                  $pending
 * @param \Closure(Success<T>|Failure, string, bool): (Success<U>|Failure|Partial<U>) $next
 *
 * @return Partial<U>
 */
function then(Partial $pending, \Closure $next): Partial
{
    return new Partial($pending->input, static function (string $input, bool $partial) use ($pending, $next): Success|Failure|Partial {
        $result = ($pending->continuation)($input, $partial);

        return $result instanceof Partial ? then($result, $next) : $next($result, $input, $partial);
    });
}

/**
 * Continues seq() once the parser at $index is decided.
 *
 * @internal
 *
 * @template T
 *
 * @param list<\Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)> $parsers
 * @param Partial<T>                                                          $pending
 * @param list<T>                                                             $values
 *
 * @return Partial<list<T>>
 */
function sequence(array $parsers, int $index, Partial $pending, array $values, ?Failure $hint): Partial
{
    return then($pending, static function (Success|Failure $result, string $input, bool $partial) use ($parsers, $index, $values, $hint): Success|Failure|Partial {
        $count = \count($parsers);

        while (true) {
            if ($result instanceof Failure) {
                return furthest($hint, $result);
            }

            $values[] = $result->value;
            $hint = furthest($hint, $result->hint);

            if (++$index === $count) {
                return new Success($values, $result->offset, $hint);
            }

            $result = $parsers[$index]($input, $result->offset, $partial);

            if ($result instanceof Partial) {
                return sequence($parsers, $index, $result, $values, $hint);
            }
        }
    });
}

/**
 * Continues alt() once the parser at $index is decided.
 *
 * @internal
 *
 * @template T
 *
 * @param list<\Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)> $parsers
 * @param Partial<T>                                                          $pending
 *
 * @return Partial<T>
 */
function alternative(array $parsers, int $index, Partial $pending, int $offset, ?Failure $failure): Partial
{
    return then($pending, static function (Success|Failure $result, string $input, bool $partial) use ($parsers, $index, $offset, $failure): Success|Failure|Partial {
        $count = \count($parsers);

        while (true) {
            if ($result instanceof Success) {
                return null === $failure ? $result : new Success($result->value, $result->offset, furthest($failure, $result->hint));
            }

            $failure = furthest($failure, $result);

            if (++$index === $count) {
                return $failure;
            }

            $result = $parsers[$index]($input, $offset, $partial);

            if ($result instanceof Partial) {
                return alternative($parsers, $index, $result, $offset, $failure);
            }
        }
    });
}

/**
 * The loop of many(), resumed with the result of the parser at $offset once it is decided.
 *
 * @internal
 *
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) $parser
 * @param list<T>                                                       $values
 * @param Success<T>|Failure|null                                       $result
 *
 * @return Success<list<T>>|Partial<list<T>>
 */
function repeating(\Closure $parser, string $input, int $offset, bool $partial, array $values, ?Failure $hint, Success|Failure|null $result): Success|Partial
{
    while (true) {
        $result ??= $parser($input, $offset, $partial);

        if (!$result instanceof Success) {
            return $result instanceof Failure
                ? new Success($values, $offset, furthest($hint, $result))
                : then($result, static fn (Success|Failure $result, string $input, bool $partial): Success|Partial => repeating($parser, $input, $offset, $partial, $values, $hint, $result));
        }

        if ($result->offset === $offset) {
            throw new \LogicException(\sprintf('Parser succeeded without consuming input at offset %d', $offset));
        }

        $values[] = $result->value;
        $offset = $result->offset;
        $hint = furthest($hint, $result->hint);
        $result = null;
    }
}

/**
 * The loop of separated() after its first item, resumed with the result of the separator at $offset, or of the
 * item following the $next separator, once it is decided.
 *
 * @internal
 *
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>) $separator
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>)         $parser
 * @param list<T>                                                               $values
 * @param ?Success<mixed>                                                       $next      the separator preceding the item
 * @param Success<mixed>|Failure|null                                           $separated
 * @param Success<T>|Failure|null                                               $result
 *
 * @return Success<list<T>>|Partial<list<T>>
 */
function separating(\Closure $separator, \Closure $parser, string $input, int $offset, bool $partial, array $values, ?Failure $hint, ?Success $next, Success|Failure|null $separated, Success|Failure|null $result): Success|Partial
{
    while (true) {
        if (null === $next) {
            $separated ??= $separator($input, $offset, $partial);

            if (!$separated instanceof Success) {
                return $separated instanceof Failure
                    ? new Success($values, $offset, furthest($hint, $separated))
                    : then($separated, static fn (Success|Failure $separated, string $input, bool $partial): Success|Partial => separating($separator, $parser, $input, $offset, $partial, $values, $hint, null, $separated, null));
            }

            $next = $separated;
            $separated = null;
        }

        $result ??= $parser($input, $next->offset, $partial);

        if (!$result instanceof Success) {
            return $result instanceof Failure
                ? new Success($values, $offset, furthest($hint, furthest($next->hint, $result)))
                : then($result, static fn (Success|Failure $result, string $input, bool $partial): Success|Partial => separating($separator, $parser, $input, $offset, $partial, $values, $hint, $next, null, $result));
        }

        if ($result->offset === $offset) {
            throw new \LogicException(\sprintf('Separator and parser succeeded without consuming input at offset %d', $offset));
        }

        $values[] = $result->value;
        $offset = $result->offset;
        $hint = furthest($hint, furthest($next->hint, $result->hint));
        $next = $result = null;
    }
}

/**
 * Keeps the value of the enclosed parser once enclosed() is decided.
 *
 * @internal
 *
 * @param Partial<list<mixed>> $pending
 *
 * @return Partial<mixed>
 */
function middle(Partial $pending): Partial
{
    return then($pending, static fn (Success|Failure $result): Success|Failure => $result instanceof Success ? new Success($result->value[1], $result->offset, $result->hint) : $result);
}

/**
 * @internal
 *
 * @template T
 *
 * @param Success<T>                        $first
 * @param Success<list<T>>|Partial<list<T>> $rest
 *
 * @return Success<non-empty-list<T>>|Partial<non-empty-list<T>>
 */
function prepend(Success $first, Success|Partial $rest): Success|Partial
{
    if ($rest instanceof Partial) {
        return then($rest, static fn (Success|Failure $rest): Success|Failure|Partial => $rest instanceof Failure ? $rest : prepend($first, $rest));
    }

    return new Success([$first->value, ...$rest->value], $rest->offset, furthest($first->hint, $rest->hint));
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
