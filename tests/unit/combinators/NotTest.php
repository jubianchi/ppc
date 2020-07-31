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

namespace jubianchi\PPC\Tests\Combinators;

use function jubianchi\PPC\Combinators\not;
use jubianchi\PPC\Parser\Result\Failure;
use jubianchi\PPC\Parser\Result\Success;
use function jubianchi\PPC\Parsers\char;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use PHPUnit\Framework\TestCase;

/**
 * @small
 *
 * @internal
 */
final class NotTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function noMatch(): void
    {
        $stream = new Stream('abc');
        $parser = not(char('b'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertThat($result->result(), self::isInstanceOf(Slice::class));
        self::assertEquals('a', $result->result());

        self::assertEquals(1, $stream->key());
        self::assertEquals('b', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function match(): void
    {
        $stream = new Stream('abc');
        $parser = not(char('a'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('not: Expected "char(a)" not to match, got "a" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->key());
        self::assertEquals('a', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function matchMultiple(): void
    {
        $stream = new Stream('bc');
        $parser = not(char('a'), char('b'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('not: Expected "char(b)" not to match, got "b" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->key());
        self::assertEquals('b', $stream->current());
    }
}
