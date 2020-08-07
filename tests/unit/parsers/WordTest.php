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
use function jubianchi\PPC\Parsers\word;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream\Char;
use PHPUnit\Framework\TestCase;

/**
 * @small
 *
 * @internal
 */
final class WordTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function match(): void
    {
        $stream = new Char('abc');
        $parser = word('ab');

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertThat($result->result(), self::isInstanceOf(Slice::class));
        self::assertEquals('ab', $result->result());

        self::assertEquals(2, $stream->offset());
        self::assertEquals('c', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function noMatch(): void
    {
        $stream = new Char('abc');
        $parser = word('bc');

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('word: Expected "bc", got "ab" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->offset());
        self::assertEquals('a', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function noMatchWithLabel(): void
    {
        $stream = new Char('abc');
        $parser = word('bc')->label('test parser');

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('test parser•word: Expected "bc", got "ab" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->offset());
        self::assertEquals('a', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function eos(): void
    {
        $stream = new Char('');
        $parser = word('abc');

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('word: Expected "abc", got "jubianchi\PPC\Stream::EOS" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->offset());
        self::assertEquals(Char::EOS, $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function partialEos(): void
    {
        $stream = new Char('ab');
        $parser = word('abc');

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('word: Expected "abc", got "ab . jubianchi\PPC\Stream::EOS" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->offset());
        self::assertEquals('a', $stream->current());
    }
}
