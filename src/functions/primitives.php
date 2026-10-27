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
    $expected = [quote($word)];

    return static function (string $input, int $offset, bool $partial = false) use ($word, $length, $expected): Result {
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

        return new Failure($expected, $offset);
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
