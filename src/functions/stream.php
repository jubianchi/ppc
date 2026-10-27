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
 * Parses items one after the other from a stream read in chunks, e.g. the records of a large file or of a socket.
 *
 * Only the item being parsed is kept in memory: the bytes of an item are dropped once it is yielded. The stream must
 * be blocking: a read returning nothing before the end of the stream is retried.
 *
 * @template T
 *
 * @param \Closure(string, int, bool=): (Success<T>|Failure|Partial<T>) $item
 * @param resource                                                      $source
 * @param positive-int                                                  $chunk  the number of bytes read at once
 *
 * @return \Generator<int, T>
 *
 * @throws SyntaxError
 */
function stream(\Closure $item, $source, int $chunk = 8192): \Generator
{
    $buffer = '';
    $consumed = 0;
    $line = $column = 1;

    while (true) {
        if ('' === $buffer) {
            $buffer = read($source, $chunk);

            if ('' === $buffer && feof($source)) {
                return;
            }
        }

        $result = settle($item($buffer, 0, !feof($source)), $source, $chunk, $buffer);

        if ($result instanceof Failure) {
            throw new SyntaxError(new Failure($result->expected, $consumed + $result->offset), explain($result, $buffer, $line, $column));
        }

        if (0 === $result->offset) {
            throw new \LogicException(\sprintf('Item parser succeeded without consuming input at offset %d', $consumed));
        }

        yield $result->value;

        [$line, $column] = advance(substr($buffer, 0, $result->offset), $line, $column);
        $consumed += $result->offset;
        $buffer = substr($buffer, $result->offset);
    }
}

/**
 * Feeds the parser with chunks of the stream, appended to the buffer, until it is decided.
 *
 * @internal
 *
 * @template T
 *
 * @param Success<T>|Failure|Partial<T> $result
 * @param resource                      $source
 * @param positive-int                  $chunk
 *
 * @return Success<T>|Failure
 */
function settle(Success|Failure|Partial $result, $source, int $chunk, string &$buffer): Success|Failure
{
    while (true) {
        if (!$result instanceof Partial) {
            return $result;
        }

        $buffer .= read($source, $chunk);
        $result = ($result->continuation)($buffer, !feof($source));
    }
}

/**
 * @internal
 *
 * @param resource     $source
 * @param positive-int $chunk
 */
function read($source, int $chunk): string
{
    $data = fread($source, $chunk);

    if (false === $data) {
        throw new \RuntimeException('Could not read from the stream');
    }

    return $data;
}
