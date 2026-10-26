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

use function jubianchi\PPC\Combinators\not;
use function jubianchi\PPC\Parsers\char;

/**
 * @internal
 */
#[Small]
final class NotTest extends TestCase
{
    #[Test]
    public function noMatch(): void
    {
        $stream = new Char('abc');
        $parser = not(char('b'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertThat($result->result(), self::isInstanceOf(Slice::class));
        self::assertEquals('a', $result->result());

        self::assertEquals(1, $stream->tell());
        self::assertEquals('b', $stream->current());
    }

    #[Test]
    public function match(): void
    {
        $stream = new Char('abc');
        $parser = not(char('a'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('not: Expected "char(a)" not to match, got "a" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->tell());
        self::assertEquals('a', $stream->current());
    }

    #[Test]
    public function matchMultiple(): void
    {
        $stream = new Char('bc');
        $parser = not(char('a'), char('b'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('not: Expected "char(b)" not to match, got "b" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->tell());
        self::assertEquals('b', $stream->current());
    }
}
