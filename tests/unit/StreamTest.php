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

namespace jubianchi\PPC\Tests;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use jubianchi\PPC\SyntaxError;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\alt;
use function jubianchi\PPC\char;
use function jubianchi\PPC\eos;
use function jubianchi\PPC\map;
use function jubianchi\PPC\opt;
use function jubianchi\PPC\regex;
use function jubianchi\PPC\seq;
use function jubianchi\PPC\stream;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Medium]
final class StreamTest extends TestCase
{
    #[Test]
    public function yieldsEachItemOfASourceReadInSmallChunks(): void
    {
        $items = stream(self::line(), self::source("a\nbb\nccc\n"), 2);

        self::assertSame(['a', 'bb', 'ccc'], iterator_to_array($items, false));
    }

    #[Test]
    public function readsMultiByteCharsSplitAcrossChunks(): void
    {
        $items = stream(self::line(), self::source("été\n日本\n"), 1);

        self::assertSame(['été', '日本'], iterator_to_array($items, false));
    }

    #[Test]
    public function parsesTheLastItemAtTheEndOfTheSource(): void
    {
        $item = seq(regex('/\p{L}+/u', 1), alt(char("\n"), eos())) |> map(static fn (array $values): mixed => $values[0]);

        self::assertSame(['a', 'b'], iterator_to_array(stream($item, self::source("a\nb"), 1), false));
    }

    #[Test]
    public function yieldsNothingFromAnEmptySource(): void
    {
        self::assertSame([], iterator_to_array(stream(self::line(), self::source('')), false));
    }

    #[Test]
    public function reportsSyntaxErrorsAtTheirAbsolutePosition(): void
    {
        $error = null;

        try {
            iterator_to_array(stream(self::line(), self::source("aé\nbb\ncç1\n"), 2));
        } catch (SyntaxError $error) {
        }

        self::assertInstanceOf(SyntaxError::class, $error);
        self::assertSame('Expected "\n", got "1" at line 3, column 3', $error->getMessage());
        self::assertEquals(new Failure(['"\n"'], 10), $error->failure);
    }

    #[Test]
    public function rejectsAnItemConsumingNothing(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Item parser succeeded without consuming input at offset 0');

        iterator_to_array(stream(opt(char('a')), self::source('b')));
    }

    #[Test]
    public function keepsTheBufferBoundedByTheLargestItem(): void
    {
        $source = self::source('');
        $count = 20_000;

        for ($id = 0; $id < $count; ++$id) {
            fwrite($source, \sprintf("{\"id\":%d,\"name\":\"%s\"}\n", $id, str_repeat('x', $id % 50)));
        }

        rewind($source);

        $record = seq(word('{"id":'), regex('/[0-9]+/'), word(',"name":"'), regex('/[a-z]*/'), word("\"}\n"))
            |> map(static fn (array $values): mixed => $values[1]);

        $largest = 0;
        $probe = static function (string $input, int $offset, bool $partial = false) use ($record, &$largest): Success|Failure|Incomplete {
            $largest = max($largest, \strlen($input));

            return $record($input, $offset, $partial);
        };

        $ids = iterator_to_array(stream($probe, $source, 1024), false);

        self::assertGreaterThan(900_000, ftell($source));
        self::assertSame(array_map(strval(...), range(0, $count - 1)), $ids);
        self::assertLessThan(4096, $largest);
    }

    /**
     * @return \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)
     */
    private static function line(): \Closure
    {
        return seq(regex('/\p{L}+/u', 1), char("\n")) |> map(static fn (array $values): mixed => $values[0]);
    }

    /**
     * @return resource
     */
    private static function source(string $content)
    {
        $source = fopen('php://memory', 'w+');
        self::assertIsResource($source);
        fwrite($source, $content);
        rewind($source);

        return $source;
    }
}
