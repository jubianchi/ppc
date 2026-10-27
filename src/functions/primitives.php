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
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Partial<string>)
 */
function char(string $char): \Closure
{
    if (1 !== mb_strlen($char)) {
        throw new \InvalidArgumentException(\sprintf('Expected a single char, got %s', quote($char)));
    }

    return word($char);
}

/**
 * When more input may come, asks for it while the input ends with a prefix of the word.
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Partial<string>)
 */
function word(string $word): \Closure
{
    if ('' === $word) {
        throw new \InvalidArgumentException('Expected a non-empty word');
    }

    $length = \strlen($word);
    $expected = [quote($word)];

    return static function (string $input, int $offset, bool $partial = false) use ($word, $length, $expected): Success|Failure|Partial {
        if (0 === substr_compare($input, $word, $offset, $length)) {
            return new Success($word, $offset + $length);
        }

        if ($partial && \strlen($input) - $offset < $length && str_starts_with($word, substr($input, $offset))) {
            return retry(\Closure::getCurrent(), $input, $offset);
        }

        return new Failure($expected, $offset);
    };
}

/**
 * The pattern is anchored at the offset with the A modifier: PCRE never searches past it.
 *
 * PCRE cannot tell whether more input would change its result. When more input may come, the result is trusted only
 * once $lookahead bytes follow the match, or the offset when it fails: raise it for patterns which may need more bytes
 * to decide. PCRE errors, such as a UTF-8 char cut by the end of the input, also wait for more input.
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Partial<string>)
 */
function regex(string $pattern, int $lookahead = 64): \Closure
{
    $anchored = $pattern.'A';

    if (false === @preg_match($anchored, '')) {
        throw new \InvalidArgumentException(\sprintf('Invalid pattern %s: %s', $pattern, preg_last_error_msg()));
    }

    if ($lookahead < 1) {
        throw new \InvalidArgumentException(\sprintf('Expected a positive lookahead, got %d', $lookahead));
    }

    $expected = [$pattern];

    return static function (string $input, int $offset, bool $partial = false) use ($anchored, $lookahead, $expected): Success|Failure|Partial {
        $matched = preg_match($anchored, $input, $matches, 0, $offset);

        if (1 === $matched) {
            $end = $offset + \strlen($matches[0]);

            return $partial && \strlen($input) - $end < $lookahead
                ? retry(\Closure::getCurrent(), $input, $offset)
                : new Success($matches[0], $end);
        }

        return $partial && (false === $matched || \strlen($input) - $offset < $lookahead)
            ? retry(\Closure::getCurrent(), $input, $offset)
            : new Failure($expected, $offset);
    };
}

/**
 * Consumes one UTF-8 char, or a single byte when the input is not valid UTF-8 at the offset.
 *
 * @return \Closure(string, int, bool=): (Success<string>|Failure|Partial<string>)
 */
function any(): \Closure
{
    return static function (string $input, int $offset, bool $partial = false): Success|Failure|Partial {
        if (!isset($input[$offset])) {
            return $partial ? retry(\Closure::getCurrent(), $input, $offset) : new Failure(['any char'], $offset);
        }

        $byte = \ord($input[$offset]);
        $length = match (true) {
            $byte >= 0xF0 => 4,
            $byte >= 0xE0 => 3,
            $byte >= 0xC0 => 2,
            default => 1,
        };

        if ($partial && \strlen($input) - $offset < $length) {
            return retry(\Closure::getCurrent(), $input, $offset);
        }

        $char = substr($input, $offset, $length);

        if (!mb_check_encoding($char, 'UTF-8')) {
            $char = $input[$offset];
        }

        return new Success($char, $offset + \strlen($char));
    };
}

/**
 * When more input may come, waits for the end of the stream.
 *
 * @return \Closure(string, int, bool=): (Success<null>|Failure|Partial<null>)
 */
function eos(): \Closure
{
    return static function (string $input, int $offset, bool $partial = false): Success|Failure|Partial {
        if ($offset < \strlen($input)) {
            return new Failure(['end of input'], $offset);
        }

        return $partial ? retry(\Closure::getCurrent(), $input, $offset) : new Success(null, $offset);
    };
}

/**
 * Runs the parser again at the same offset once more input is available.
 *
 * @internal
 *
 * @template T
 *
 * @param \Closure(string, int, bool): (Success<T>|Failure|Partial<T>) $parser
 *
 * @return Partial<T>
 */
function retry(\Closure $parser, string $input, int $offset): Partial
{
    return new Partial($input, static fn (string $input, bool $partial): Success|Failure|Partial => $parser($input, $offset, $partial));
}

/**
 * @internal
 */
function quote(string $value): string
{
    return json_encode($value, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_THROW_ON_ERROR);
}
