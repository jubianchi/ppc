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

use function jubianchi\PPC\Combinators\seq;
use jubianchi\PPC\Parser\Result\Failure;
use jubianchi\PPC\Parser\Result\Success;
use function jubianchi\PPC\Parsers\char;
use jubianchi\PPC\Stream\Char;
use PHPUnit\Framework\TestCase;

/**
 * @small
 *
 * @internal
 */
final class SeqTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function bothMatch(): void
    {
        $stream = new Char('abc');
        $first = char('a');
        $second = char('b');
        $parser = seq($first, $second);

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertIsArray($result->result());
        self::assertEquals(['a', 'b'], $result->result());

        self::assertEquals(2, $stream->tell());
        self::assertEquals('c', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function onlyFirstMatch(): void
    {
        $stream = new Char('abc');
        $first = char('a');
        $second = char('c');
        $parser = seq($first, $second);

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

    /**
     * @test
     * @small
     */
    public function noMatch(): void
    {
        $stream = new Char('abc');
        $first = char('b');
        $second = char('c');
        $parser = seq($first, $second);

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
