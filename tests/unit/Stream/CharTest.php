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

namespace jubianchi\PPC\Tests\Stream;

use jubianchi\PPC\Stream;
use jubianchi\PPC\Stream\Char;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Small]
final class CharTest extends TestCase
{
    #[Test]
    public function emptyStream(): void
    {
        $stream = new Char('');

        self::assertTrue($stream->eos());
        self::assertEquals(0, $stream->tell());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());
    }

    #[Test]
    public function nonEmptyStream(): void
    {
        $stream = new Char('abc');

        self::assertFalse($stream->eos());
        self::assertEquals(0, $stream->tell());
        self::assertEquals('a', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());
    }

    #[Test]
    public function consumeStream(): void
    {
        $stream = new Char('abc');

        self::assertEquals('a', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());

        $stream->consume();

        self::assertEquals('b', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());
    }

    #[Test]
    public function consumeStreamWithNewLine(): void
    {
        $stream = new Char("a\nbc");

        self::assertEquals('a', $stream->current());
        self::assertEquals(0, $stream->tell());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());

        $stream->consume();

        self::assertEquals("\n", $stream->current());
        self::assertEquals(1, $stream->tell());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());

        $stream->consume();

        self::assertEquals('b', $stream->current());
        self::assertEquals(2, $stream->tell());
        self::assertEquals(['line' => 2, 'column' => 0], $stream->position());
    }

    #[Test]
    public function consumeStreamAfterEnd(): void
    {
        $stream = new Char("a\n");

        self::assertEquals('a', $stream->current());
        self::assertEquals(0, $stream->tell());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());

        $stream->consume();

        self::assertEquals("\n", $stream->current());
        self::assertEquals(1, $stream->tell());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());

        $stream->consume();

        self::assertEquals(Stream::EOS, $stream->current());
        self::assertEquals(2, $stream->tell());
        self::assertEquals(['line' => 2, 'column' => 0], $stream->position());

        try {
            $stream->consume();

            self::fail();
        } catch (\Exception $exception) {
            self::assertInstanceOf(\OutOfBoundsException::class, $exception);
        }
    }

    #[Test]
    public function cutSliceFromStream(): void
    {
        $stream = new Char('abc');

        self::assertEquals('abc', $stream->cut(0));
        self::assertEquals('a', $stream->cut(0, 1));
        self::assertEquals('bc', $stream->cut(1));
        self::assertEquals('b', $stream->cut(1, 1));

        try {
            $stream->cut(0, 10);

            self::fail();
        } catch (\Exception $exception) {
            self::assertInstanceOf(\OutOfBoundsException::class, $exception);
        }

        try {
            $stream->cut(-1);

            self::fail();
        } catch (\Exception $exception) {
            self::assertInstanceOf(\OutOfBoundsException::class, $exception);
        }
    }

    #[Test]
    public function seekBeforeStart(): void
    {
        $stream = new Char('abc');

        try {
            $stream->seek(-1);

            self::fail();
        } catch (\Exception $exception) {
            self::assertInstanceOf(\OutOfBoundsException::class, $exception);
        }
    }

    #[Test]
    public function seekAfterEnd(): void
    {
        $stream = new Char('abc');

        try {
            $stream->seek(4);

            self::fail();
        } catch (\Exception $exception) {
            self::assertInstanceOf(\OutOfBoundsException::class, $exception);
        }
    }

    #[Test]
    public function seekEnd(): void
    {
        $stream = new Char('abc');

        $stream->seek(3);

        self::assertEquals(Stream::EOS, $stream->current());
        self::assertEquals(3, $stream->tell());
        self::assertEquals(['line' => 1, 'column' => 3], $stream->position());
    }

    #[Test]
    public function seekInStream(): void
    {
        $stream = new Char('abc');

        self::assertEquals('a', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());

        $stream->seek(1);

        self::assertEquals('b', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());

        $stream->seek(2);

        self::assertEquals('c', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 2], $stream->position());

        $stream->seek(1);

        self::assertEquals('b', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());
    }

    #[Test]
    public function seekInStreamWithNewLine(): void
    {
        $stream = new Char("a\nbc\ndefg");

        self::assertEquals('a', $stream->current());
        self::assertEquals(0, $stream->tell());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());

        $stream->seek(1);

        self::assertEquals("\n", $stream->current());
        self::assertEquals(1, $stream->tell());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());

        $stream->seek(3);

        self::assertEquals('c', $stream->current());
        self::assertEquals(3, $stream->tell());
        self::assertEquals(['line' => 2, 'column' => 1], $stream->position());

        $stream->seek(7);

        self::assertEquals('f', $stream->current());
        self::assertEquals(7, $stream->tell());
        self::assertEquals(['line' => 3, 'column' => 2], $stream->position());

        $stream->seek(3);

        self::assertEquals('c', $stream->current());
        self::assertEquals(3, $stream->tell());
        self::assertEquals(['line' => 2, 'column' => 1], $stream->position());
    }
}
