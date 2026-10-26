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

use function jubianchi\PPC\Combinators\enclosed;
use function jubianchi\PPC\Parsers\any;
use function jubianchi\PPC\Parsers\char;

/**
 * @internal
 */
#[Small]
final class EnclosedTest extends TestCase
{
    #[Test]
    public function sameDelimiterMatch(): void
    {
        $stream = new Char('aba');
        $parser = enclosed(char('a'), any());

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertThat($result->result(), self::isInstanceOf(Slice::class));
        self::assertEquals('b', (string) $result->result());

        self::assertEquals(3, $stream->tell());
        self::assertEquals(Char::EOS, $stream->current());
    }

    #[Test]
    public function differentDelimitersMatch(): void
    {
        $stream = new Char('abc');
        $parser = enclosed(char('a'), any(), char('c'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertThat($result->result(), self::isInstanceOf(Slice::class));
        self::assertEquals('b', (string) $result->result());

        self::assertEquals(3, $stream->tell());
        self::assertEquals(Char::EOS, $stream->current());
    }

    #[Test]
    public function sameDelimiterNoMatch(): void
    {
        $stream = new Char('abc');
        $parser = enclosed(char('a'), any());

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('char: Expected "a", got "c" at line 1 offset 2', $failure->getMessage());
        }

        self::assertEquals(0, $stream->tell());
        self::assertEquals('a', $stream->current());
    }

    #[Test]
    public function differentDelimitersNoMatch(): void
    {
        $stream = new Char('abc');
        $parser = enclosed(char('a'), any(), char('b'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('char: Expected "b", got "c" at line 1 offset 2', $failure->getMessage());
        }

        self::assertEquals(0, $stream->tell());
        self::assertEquals('a', $stream->current());
    }

    #[Test]
    public function startDelimiterNoMatch(): void
    {
        $stream = new Char('abc');
        $parser = enclosed(char('c'), any());

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('char: Expected "c", got "a" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->tell());
        self::assertEquals('a', $stream->current());
    }

    #[Test]
    public function enclosedNoMatch(): void
    {
        $stream = new Char('abc');
        $parser = enclosed(char('a'), char('c'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('char: Expected "c", got "b" at line 1 offset 1', $failure->getMessage());
        }

        self::assertEquals(0, $stream->tell());
        self::assertEquals('a', $stream->current());
    }
}
