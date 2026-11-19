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
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function char(string $char): \Closure
{
    if (1 !== mb_strlen($char)) {
        throw new \InvalidArgumentException(\sprintf('Expected a single char, got %s', quote($char)));
    }

    return word($char);
}

/**
 * On a partial input, the word is incomplete when the input ends with its beginning.
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function word(string $word): \Closure
{
    if ('' === $word) {
        throw new \InvalidArgumentException('Expected a non-empty word');
    }

    $length = \strlen($word);
    $chars = mb_strlen($word);
    $expected = [quote($word)];

    return static function (string $input, int $offset, bool $partial = false) use ($word, $length, $chars, $expected): Result {
        if (0 === substr_compare($input, $word, $offset, $length)) {
            return new Success($word, $offset + $length);
        }

        if ($partial) {
            $available = \strlen($input) - $offset;
            $missing = $length - $available;

            if ($missing > 0 && 0 === substr_compare($input, $word, $offset, $available)) {
                return new Incomplete($missing);
            }
        }

        return new Failure($expected, $offset, $chars);
    };
}

/**
 * The pattern is anchored at the offset with the A modifier: PCRE never searches past it.
 *
 * PHP does not expose PCRE partial matching: on a partial input, the result is only trusted when the input extends at
 * least $window bytes past the end of the match, or past the offset on failure. Patterns looking further than that,
 * or needing more bytes to fail, must be given a larger window. A matching error, such as a UTF-8 char cut by the end
 * of a partial input with the u modifier, is incomplete too.
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function regex(string $pattern, int $window = 64): \Closure
{
    $anchored = $pattern.'A';

    if (false === @preg_match($anchored, '')) {
        throw new \InvalidArgumentException(\sprintf('Invalid pattern %s: %s', $pattern, preg_last_error_msg()));
    }

    if ($window < 1) {
        throw new \InvalidArgumentException(\sprintf('Expected a positive window, got %d', $window));
    }

    $expected = [$pattern];

    return static function (string $input, int $offset, bool $partial = false) use ($anchored, $window, $expected): Result {
        $matched = preg_match($anchored, $input, $matches, 0, $offset);

        if (1 === $matched) {
            $end = $offset + \strlen($matches[0]);

            return $partial && \strlen($input) - $end < $window ? new Incomplete() : new Success($matches[0], $end);
        }

        return $partial && (false === $matched || \strlen($input) - $offset < $window) ? new Incomplete() : new Failure($expected, $offset);
    };
}

/**
 * Consumes one UTF-8 char matching the predicate.
 *
 * @param \Closure(string): bool $predicate
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function satisfy(\Closure $predicate): \Closure
{
    return satisfying($predicate, ['matching char']);
}

/**
 * Consumes one ASCII digit, from 0 to 9.
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function digit(): \Closure
{
    return satisfying(ctype_digit(...), ['digit']);
}

/**
 * Consumes one Unicode letter (\p{L}), or one ASCII letter, from a to z in either case.
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function letter(bool $ascii = false): \Closure
{
    if ($ascii) {
        return satisfying(ctype_alpha(...), ['ASCII letter']);
    }

    return satisfying(static fn (string $char): bool => $char < "\x80" ? ctype_alpha($char) : 1 === preg_match('/^\p{L}$/u', $char), ['letter']);
}

/**
 * Consumes one Unicode whitespace (\p{White_Space}), or one ASCII whitespace: space, \t, \n, \r, \f or \v.
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function space(bool $ascii = false): \Closure
{
    if ($ascii) {
        return satisfying(ctype_space(...), ['ASCII space']);
    }

    return satisfying(static fn (string $char): bool => $char < "\x80" ? ctype_space($char) : 1 === preg_match('/^\p{White_Space}$/u', $char), ['space']);
}

/**
 * @internal
 *
 * @param \Closure(string): bool $predicate
 * @param list<string>           $expected
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function satisfying(\Closure $predicate, array $expected): \Closure
{
    $any = any();

    return static function (string $input, int $offset, bool $partial = false) use ($predicate, $any, $expected): Result {
        if (isset($input[$offset]) && $input[$offset] < "\x80") {
            return $predicate($input[$offset]) ? new Success($input[$offset], $offset + 1) : new Failure($expected, $offset);
        }

        $char = $any($input, $offset, $partial);

        if ($char instanceof Incomplete || ($char instanceof Success && $predicate($char->value))) {
            return $char;
        }

        return new Failure($expected, $offset);
    };
}

/**
 * Consumes the chars matching the predicate, at least $min of them.
 *
 * Unlike regex(), it knows when it reaches the end of a partial input: it is incomplete only then, whatever the length
 * of the match.
 *
 * @param \Closure(string): bool $predicate
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function takeWhile(\Closure $predicate, int $min = 0): \Closure
{
    if ($min < 0) {
        throw new \InvalidArgumentException(\sprintf('Expected a non-negative minimum, got %d', $min));
    }

    $span = spanning($predicate, true);
    $expected = ['matching char'];

    return static function (string $input, int $offset, bool $partial = false) use ($span, $min, $expected): Result {
        $spanned = $span($input, $offset, $partial);

        if ($spanned instanceof Incomplete) {
            return $spanned;
        }

        [$position, $count] = $spanned;

        return $count < $min ? new Failure($expected, $position) : new Success(substr($input, $offset, $position - $offset), $position);
    };
}

/**
 * Consumes everything up to the delimiter, or up to the first char matching the predicate, which is left to the next
 * parser.
 *
 * Unlike regex(), it is incomplete exactly when a partial input does not contain the delimiter, whatever the length of
 * the match.
 *
 * @param string|\Closure(string): bool $delimiter
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function takeUntil(string|\Closure $delimiter): \Closure
{
    if ($delimiter instanceof \Closure) {
        $span = spanning($delimiter, false);
        $expected = ['delimiter char'];

        return static function (string $input, int $offset, bool $partial = false) use ($span, $expected): Result {
            $spanned = $span($input, $offset, $partial);

            if ($spanned instanceof Incomplete) {
                return $spanned;
            }

            [$position] = $spanned;

            return $position >= \strlen($input) ? new Failure($expected, $position) : new Success(substr($input, $offset, $position - $offset), $position);
        };
    }

    if ('' === $delimiter) {
        throw new \InvalidArgumentException('Expected a non-empty delimiter');
    }

    $expected = [quote($delimiter)];
    $length = mb_strlen($delimiter);

    return static function (string $input, int $offset, bool $partial = false) use ($delimiter, $expected, $length): Result {
        $end = \strlen($input);
        $position = $offset <= $end ? strpos($input, $delimiter, $offset) : false;

        if (false !== $position) {
            return new Success(substr($input, $offset, $position - $offset), $position);
        }

        return $partial ? new Incomplete() : new Failure($expected, max($offset, $end), $length);
    };
}

/**
 * Moves past the chars for which the predicate returns $while.
 *
 * @internal
 *
 * @param \Closure(string): bool $predicate
 *
 * @return \Closure(string, int, bool): (array{int, int}|Incomplete) the offset of the first other char, and the number of chars moved past
 */
function spanning(\Closure $predicate, bool $while): \Closure
{
    $any = any();

    return static function (string $input, int $offset, bool $partial) use ($predicate, $while, $any): array|Incomplete {
        $position = $offset;
        $count = 0;

        while (true) {
            if (isset($input[$position]) && $input[$position] < "\x80") {
                if ($while === !$predicate($input[$position])) {
                    break;
                }

                ++$position;
                ++$count;

                continue;
            }

            $char = $any($input, $position, $partial);

            if ($char instanceof Incomplete) {
                return $char;
            }

            if ($char instanceof Failure || $while === !$predicate($char->value)) {
                break;
            }

            $position = $char->offset;
            ++$count;
        }

        return [$position, $count];
    };
}

/**
 * Consumes one UTF-8 char, or a single byte when the input is not valid UTF-8 at the offset.
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
 */
function any(): \Closure
{
    return static function (string $input, int $offset, bool $partial = false): Result {
        if (!isset($input[$offset])) {
            return $partial ? new Incomplete(1) : new Failure(['any char'], $offset);
        }

        $byte = \ord($input[$offset]);
        $length = match (true) {
            $byte >= 0xF0 => 4,
            $byte >= 0xE0 => 3,
            $byte >= 0xC0 => 2,
            default => 1,
        };
        $char = substr($input, $offset, $length);

        if (!mb_check_encoding($char, 'UTF-8')) {
            $missing = $length - \strlen($char);

            if ($partial && $missing > 0 && 1 === preg_match('/^[\x80-\xBF]*$/', substr($char, 1))) {
                return new Incomplete($missing);
            }

            $char = $input[$offset];
        }

        return new Success($char, $offset + \strlen($char));
    };
}

/**
 * @return \Closure(string, int, bool=): (Success<null>|Failure|Incomplete)
 */
function eos(): \Closure
{
    return static function (string $input, int $offset, bool $partial = false): Result {
        if ($offset < \strlen($input)) {
            return new Failure(['end of input'], $offset);
        }

        return $partial ? new Incomplete() : new Success(null, $offset);
    };
}

/**
 * @internal
 */
function quote(string $value): string
{
    return json_encode($value, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_THROW_ON_ERROR);
}
