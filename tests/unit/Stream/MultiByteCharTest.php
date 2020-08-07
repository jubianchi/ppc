<?php
/**
 * This file is part of PPC.
 *
 * Copyright © 2020 Julien Bianchi <contact@jubianchi.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace jubianchi\PPC\Tests\Stream;

use Exception;
use jubianchi\PPC\Stream;
use jubianchi\PPC\Stream\MultiByteChar;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

/**
 * @small
 *
 * @internal
 */
final class MultiByteCharTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function emptyStream(): void
    {
        $stream = new MultiByteChar('');

        self::assertTrue($stream->eos());
        self::assertEquals(0, $stream->offset());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());
    }

    /**
     * @test
     * @small
     */
    public function nonEmptyStream(): void
    {
        $stream = new MultiByteChar('a😁c');

        self::assertFalse($stream->eos());
        self::assertEquals(0, $stream->offset());
        self::assertEquals('a', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());
    }

    /**
     * @test
     * @small
     */
    public function consumeStream(): void
    {
        $stream = new MultiByteChar('a😁c');

        self::assertEquals('a', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());

        $stream->consume();

        self::assertEquals('😁', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());
    }

    /**
     * @test
     * @small
     */
    public function consumeStreamWithNewLine(): void
    {
        $stream = new MultiByteChar("a\n😁c");

        self::assertEquals('a', $stream->current());
        self::assertEquals(0, $stream->offset());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());

        $stream->consume();

        self::assertEquals("\n", $stream->current());
        self::assertEquals(1, $stream->offset());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());

        $stream->consume();

        self::assertEquals('😁', $stream->current());
        self::assertEquals(2, $stream->offset());
        self::assertEquals(['line' => 2, 'column' => 0], $stream->position());
    }

    /**
     * @test
     * @small
     */
    public function consumeStreamAfterEnd(): void
    {
        $stream = new MultiByteChar("a\n");

        self::assertEquals('a', $stream->current());
        self::assertEquals(0, $stream->offset());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());

        $stream->consume();

        self::assertEquals("\n", $stream->current());
        self::assertEquals(1, $stream->offset());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());

        $stream->consume();

        self::assertEquals(Stream::EOS, $stream->current());
        self::assertEquals(2, $stream->offset());
        self::assertEquals(['line' => 2, 'column' => 0], $stream->position());

        try {
            $stream->consume();

            self::fail();
        } catch (Exception $exception) {
            self::assertInstanceOf(OutOfBoundsException::class, $exception);
        }
    }

    /**
     * @test
     * @small
     */
    public function cutSliceFromStream(): void
    {
        $stream = new MultiByteChar('a😁c');

        self::assertEquals('a😁c', $stream->cut(0));
        self::assertEquals('a', $stream->cut(0, 1));
        self::assertEquals('😁c', $stream->cut(1));
        self::assertEquals('😁', $stream->cut(1, 1));

        try {
            $stream->cut(0, 10);

            self::fail();
        } catch (Exception $exception) {
            self::assertInstanceOf(OutOfBoundsException::class, $exception);
        }

        try {
            $stream->cut(-1);

            self::fail();
        } catch (Exception $exception) {
            self::assertInstanceOf(OutOfBoundsException::class, $exception);
        }
    }

    /**
     * @test
     * @small
     */
    public function seekBeforeStart(): void
    {
        $stream = new MultiByteChar('a😁c');

        try {
            $stream->seek(-1);

            self::fail();
        } catch (Exception $exception) {
            self::assertInstanceOf(OutOfBoundsException::class, $exception);
        }
    }

    /**
     * @test
     * @small
     */
    public function seekAfterEnd(): void
    {
        $stream = new MultiByteChar('a😁c');

        try {
            $stream->seek(4);

            self::fail();
        } catch (Exception $exception) {
            self::assertInstanceOf(OutOfBoundsException::class, $exception);
        }
    }

    /**
     * @test
     * @small
     */
    public function seekEnd(): void
    {
        $stream = new MultiByteChar('a😁c');

        $stream->seek(3);

        self::assertEquals(Stream::EOS, $stream->current());
        self::assertEquals(3, $stream->offset());
        self::assertEquals(['line' => 1, 'column' => 3], $stream->position());
    }

    /**
     * @test
     * @small
     */
    public function seekInStream(): void
    {
        $stream = new MultiByteChar('a😁c');

        self::assertEquals('a', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());

        $stream->seek(1);

        self::assertEquals('😁', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());

        $stream->seek(2);

        self::assertEquals('c', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 2], $stream->position());

        $stream->seek(1);

        self::assertEquals('😁', $stream->current());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());
    }

    /**
     * @test
     * @small
     */
    public function seekInStreamWithNewLine(): void
    {
        $stream = new MultiByteChar("a\n😁c\ndefg");

        self::assertEquals('a', $stream->current());
        self::assertEquals(0, $stream->offset());
        self::assertEquals(['line' => 1, 'column' => 0], $stream->position());

        $stream->seek(1);

        self::assertEquals("\n", $stream->current());
        self::assertEquals(1, $stream->offset());
        self::assertEquals(['line' => 1, 'column' => 1], $stream->position());

        $stream->seek(3);

        self::assertEquals('c', $stream->current());
        self::assertEquals(3, $stream->offset());
        self::assertEquals(['line' => 2, 'column' => 1], $stream->position());

        $stream->seek(7);

        self::assertEquals('f', $stream->current());
        self::assertEquals(7, $stream->offset());
        self::assertEquals(['line' => 3, 'column' => 2], $stream->position());

        $stream->seek(3);

        self::assertEquals('c', $stream->current());
        self::assertEquals(3, $stream->offset());
        self::assertEquals(['line' => 2, 'column' => 1], $stream->position());
    }
}
