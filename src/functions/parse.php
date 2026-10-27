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
 * @param \Closure(string, int): (Success<T>|Failure) $parser
 *
 * @return T
 *
 * @throws SyntaxError
 */
function parse(\Closure $parser, string $input): mixed
{
    $result = $parser($input, 0);

    if ($result instanceof Failure) {
        throw new SyntaxError($result, explain($result, $input));
    }

    return $result->value;
}

/**
 * @internal
 */
function explain(Failure $failure, string $input): string
{
    $expected = array_values(array_unique($failure->expected));
    $last = array_pop($expected);
    $expected = [] === $expected ? $last : implode(', ', $expected).' or '.$last;

    $actual = any()($input, $failure->offset);
    $actual = $actual instanceof Success ? quote($actual->value) : 'end of input';

    $before = substr($input, 0, $failure->offset);
    $lineStart = strrpos($before, "\n");
    $line = 1 + substr_count($before, "\n");
    $column = 1 + mb_strlen(false === $lineStart ? $before : substr($before, $lineStart + 1));

    return \sprintf('Expected %s, got %s at line %d, column %d', $expected, $actual, $line, $column);
}
