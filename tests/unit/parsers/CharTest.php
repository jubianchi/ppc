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

namespace jubianchi\PPC\Tests\Parsers;

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
final class CharTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function match(): void
    {
        $stream = new Stream('abc');
        $parser = char('a');

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
    public function noMatch(): void
    {
        $stream = new Stream('abc');
        $parser = char('b');

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('char: Expected "b", got "a" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->key());
        self::assertEquals('a', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function noMatchWithLabel(): void
    {
        $stream = new Stream('abc');
        $parser = char('b')->label('test parser');

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('test parser•char: Expected "b", got "a" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->key());
        self::assertEquals('a', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function eos(): void
    {
        $stream = new Stream('');
        $parser = char('a');

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('char: Expected "a", got "jubianchi\PPC\Stream::EOS" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->key());
        self::assertEquals(Stream::EOS, $stream->current());
    }
}
