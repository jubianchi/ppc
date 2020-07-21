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

use function jubianchi\PPC\Combinators\alt;
use function jubianchi\PPC\Combinators\many;
use jubianchi\PPC\Parser\Result\Failure;
use jubianchi\PPC\Parser\Result\Success;
use function jubianchi\PPC\Parsers\char;
use function jubianchi\PPC\Parsers\word;
use jubianchi\PPC\Stream;
use PHPUnit\Framework\TestCase;

final class ManyTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function match(): void
    {
        $stream = new Stream('abc');
        $parser = many(alt(char('a'), char('b')));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertIsArray($result->result());
        self::assertEquals(['a', 'b'], $result->result());

        self::assertEquals(2, $stream->key());
        self::assertEquals('c', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function wordMatch(): void
    {
        $stream = new Stream('abbabac');
        $parser = many(alt(word('ab'), char('b')));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertIsArray($result->result());
        self::assertEquals(['ab', 'b', 'ab'], $result->result());

        self::assertEquals(5, $stream->key());
        self::assertEquals('a', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function noMatch(): void
    {
        $stream = new Stream('abc');
        $parser = many(char('c'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('char: Expected "c", got "a" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->key());
        self::assertEquals('a', $stream->current());
    }
}
