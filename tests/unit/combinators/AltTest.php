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

namespace jubianchi\PPC\Tests\Combinators;

use jubianchi\PPC\Parser\Result\Failure;
use jubianchi\PPC\Parser\Result\Success;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream\Char;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\Combinators\alt;
use function jubianchi\PPC\Parsers\char;

/**
 * @internal
 */
#[Small]
final class AltTest extends TestCase
{
    #[Test]
    public function firstMatch(): void
    {
        $stream = new Char('abc');
        $first = char('a');
        $second = char('b');
        $parser = alt($first, $second);

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertThat($result->result(), self::isInstanceOf(Slice::class));
        self::assertEquals('a', (string) $result->result());

        self::assertEquals(1, $stream->tell());
        self::assertEquals('b', $stream->current());
    }

    #[Test]
    public function secondMatch(): void
    {
        $stream = new Char('abc');
        $first = char('b');
        $second = char('a');
        $parser = alt($first, $second);

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertThat($result->result(), self::isInstanceOf(Slice::class));
        self::assertEquals('a', (string) $result->result());

        self::assertEquals(1, $stream->tell());
        self::assertEquals('b', $stream->current());
    }

    #[Test]
    public function noneMatch(): void
    {
        $stream = new Char('abc');
        $first = char('b');
        $second = char('c');
        $parser = alt($first, $second);

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('char: Expected "b", got "a" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->tell());
        self::assertEquals('a', $stream->current());
    }
}
