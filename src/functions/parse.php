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
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete) $parser
 *
 * @return T
 *
 * @throws SyntaxError
 */
function parse(\Closure $parser, string $input): mixed
{
    $value = tryParse($parser, $input);

    if ($value instanceof SyntaxError) {
        throw $value;
    }

    return $value;
}

/**
 * Like parse(), but returns the SyntaxError instead of throwing it.
 *
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete) $parser
 *
 * @return T|SyntaxError
 */
function tryParse(\Closure $parser, string $input): mixed
{
    $result = $parser($input, 0, false);

    if ($result instanceof Failure) {
        return new SyntaxError($result, locate($input, $result->offset), explain($result, $input));
    }

    if ($result instanceof Incomplete) {
        throw new \LogicException('Parser returned Incomplete on a complete input');
    }

    return $result->value;
}

/**
 * The input starts at the given line and column.
 *
 * @internal
 *
 * @param positive-int $line
 * @param positive-int $column
 */
function explain(Failure $failure, string $input, int $line = 1, int $column = 1): string
{
    $expected = array_values(array_unique($failure->expected));
    $last = array_pop($expected);
    $expected = [] === $expected ? $last : implode(', ', $expected).' or '.$last;

    $any = any();
    $offset = $failure->offset;
    $actual = '';

    for ($count = 0; $count < $failure->length; ++$count) {
        $char = $any($input, $offset);

        if (!$char instanceof Success) {
            break;
        }

        $actual .= $char->value;
        $offset = $char->offset;
    }

    $actual = '' === $actual ? 'end of input' : quote($actual);

    return \sprintf('Expected %s, got %s at %s', $expected, $actual, position($input, $failure->offset, $line, $column));
}

/**
 * The input starts at the given line and column.
 *
 * @internal
 *
 * @param positive-int $line
 * @param positive-int $column
 */
function position(string $input, int $offset, int $line = 1, int $column = 1): string
{
    $position = locate($input, $offset, $line, $column);

    return \sprintf('line %d, column %d', $position->line, $position->column);
}

/**
 * The input starts at the given line and column.
 *
 * @internal
 *
 * @param positive-int $line
 * @param positive-int $column
 */
function locate(string $input, int $offset, int $line = 1, int $column = 1): Position
{
    return new Position(...advance(substr($input, 0, $offset), $line, $column));
}

/**
 * Returns the line and column right after the text, which starts at the given line and column.
 *
 * @internal
 *
 * @param positive-int $line
 * @param positive-int $column
 *
 * @return array{positive-int, positive-int}
 */
function advance(string $text, int $line, int $column): array
{
    $lineStart = strrpos($text, "\n");

    return false === $lineStart
        ? [$line, $column + mb_strlen($text)]
        : [$line + substr_count($text, "\n"), 1 + mb_strlen(substr($text, $lineStart + 1))];
}
