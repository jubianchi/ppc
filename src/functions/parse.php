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
    $result = $parser($input, 0, false);

    if ($result instanceof Failure) {
        throw new SyntaxError($result, explain($result, $input));
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
 */
function explain(Failure $failure, string $input, int $line = 1, int $column = 1): string
{
    $expected = array_values(array_unique($failure->expected));
    $last = array_pop($expected);
    $expected = [] === $expected ? $last : implode(', ', $expected).' or '.$last;

    $actual = any()($input, $failure->offset);
    $actual = $actual instanceof Success ? quote($actual->value) : 'end of input';

    return \sprintf('Expected %s, got %s at %s', $expected, $actual, position($input, $failure->offset, $line, $column));
}

/**
 * The input starts at the given line and column.
 *
 * @internal
 */
function position(string $input, int $offset, int $line = 1, int $column = 1): string
{
    [$line, $column] = advance(substr($input, 0, $offset), $line, $column);

    return \sprintf('line %d, column %d', $line, $column);
}

/**
 * Returns the line and column right after the text, which starts at the given line and column.
 *
 * @internal
 *
 * @return array{int, int}
 */
function advance(string $text, int $line, int $column): array
{
    $lineStart = strrpos($text, "\n");

    return false === $lineStart
        ? [$line, $column + mb_strlen($text)]
        : [$line + substr_count($text, "\n"), 1 + mb_strlen(substr($text, $lineStart + 1))];
}
