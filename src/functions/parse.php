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
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) $parser
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

    if ($result instanceof Partial) {
        throw new \LogicException('Parser asked for more input although the input is complete');
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

    return \sprintf('Expected %s, got %s at %s', $expected, $actual, position($input, $failure->offset));
}

/**
 * @internal
 */
function position(string $input, int $offset): string
{
    $before = substr($input, 0, $offset);
    $lineStart = strrpos($before, "\n");
    $line = 1 + substr_count($before, "\n");
    $column = 1 + mb_strlen(false === $lineStart ? $before : substr($before, $lineStart + 1));

    return \sprintf('line %d, column %d', $line, $column);
}
