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
 * @return \Closure(string, int): (Success<string>|Failure)
 */
function char(string $char): \Closure
{
    if (1 !== mb_strlen($char)) {
        throw new \InvalidArgumentException(\sprintf('Expected a single char, got %s', quote($char)));
    }

    return word($char);
}

/**
 * @return \Closure(string, int): (Success<string>|Failure)
 */
function word(string $word): \Closure
{
    if ('' === $word) {
        throw new \InvalidArgumentException('Expected a non-empty word');
    }

    $length = \strlen($word);
    $expected = [quote($word)];

    return static fn (string $input, int $offset): Success|Failure => 0 === substr_compare($input, $word, $offset, $length)
        ? new Success($word, $offset + $length)
        : new Failure($expected, $offset);
}

/**
 * The pattern is anchored at the offset with the A modifier: PCRE never searches past it.
 *
 * @return \Closure(string, int): (Success<string>|Failure)
 */
function regex(string $pattern): \Closure
{
    $anchored = $pattern.'A';

    if (false === @preg_match($anchored, '')) {
        throw new \InvalidArgumentException(\sprintf('Invalid pattern %s: %s', $pattern, preg_last_error_msg()));
    }

    $expected = [$pattern];

    return static fn (string $input, int $offset): Success|Failure => 1 === preg_match($anchored, $input, $matches, 0, $offset)
        ? new Success($matches[0], $offset + \strlen($matches[0]))
        : new Failure($expected, $offset);
}

/**
 * Consumes one UTF-8 char, or a single byte when the input is not valid UTF-8 at the offset.
 *
 * @return \Closure(string, int): (Success<string>|Failure)
 */
function any(): \Closure
{
    return static function (string $input, int $offset): Success|Failure {
        if (!isset($input[$offset])) {
            return new Failure(['any char'], $offset);
        }

        $byte = \ord($input[$offset]);
        $char = substr($input, $offset, match (true) {
            $byte >= 0xF0 => 4,
            $byte >= 0xE0 => 3,
            $byte >= 0xC0 => 2,
            default => 1,
        });

        if (!mb_check_encoding($char, 'UTF-8')) {
            $char = $input[$offset];
        }

        return new Success($char, $offset + \strlen($char));
    };
}

/**
 * @return \Closure(string, int): (Success<null>|Failure)
 */
function eos(): \Closure
{
    return static fn (string $input, int $offset): Success|Failure => $offset >= \strlen($input)
        ? new Success(null, $offset)
        : new Failure(['end of input'], $offset);
}

/**
 * @internal
 */
function quote(string $value): string
{
    return json_encode($value, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_THROW_ON_ERROR);
}
