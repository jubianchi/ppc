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
 * Parses the items of a source one after the other, reading it by chunks: only the items being parsed are kept in
 * memory, not the whole source.
 *
 * The item parser runs on a partial input until the end of the source. When it is incomplete, the consumed bytes are
 * dropped, more bytes are read, twice as many as the previous time, and the item is parsed again from its start.
 *
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Incomplete) $item
 * @param resource                                                      $source
 *
 * @return \Generator<int, T>
 *
 * @throws SyntaxError
 */
function stream(\Closure $item, $source, int $chunk = 8192): \Generator
{
    if ($chunk < 1) {
        throw new \InvalidArgumentException(\sprintf('Expected a positive chunk size, got %d', $chunk));
    }

    $buffer = '';
    $offset = 0;
    $end = false;
    $size = $chunk;
    $dropped = 0;
    $line = 1;
    $column = 1;

    while (!$end || $offset < \strlen($buffer)) {
        $result = $item($buffer, $offset, !$end);

        if ($result instanceof Incomplete) {
            if ($end) {
                throw new \LogicException('Item parser returned Incomplete on a complete input');
            }

            [$line, $column] = advance(substr($buffer, 0, $offset), $line, $column);
            $dropped += $offset;
            $buffer = substr($buffer, $offset);
            $offset = 0;

            $read = fread($source, max($size, $result->needed ?? 0));

            if (false === $read) {
                throw new \RuntimeException('Unable to read from the source');
            }

            $buffer .= $read;
            $end = feof($source);
            $size *= 2;

            continue;
        }

        if ($result instanceof Failure) {
            throw new SyntaxError(new Failure($result->expected, $dropped + $result->offset), explain($result, $buffer, $line, $column));
        }

        if ($result->offset === $offset) {
            throw new \LogicException(\sprintf('Item parser succeeded without consuming input at offset %d', $dropped + $offset));
        }

        yield $result->value;

        $offset = $result->offset;
        $size = $chunk;
    }
}
