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

use function jubianchi\PPC\Combinators\repeat;
use jubianchi\PPC\Parser\Result\Failure;
use jubianchi\PPC\Parser\Result\Success;
use function jubianchi\PPC\Parsers\regex;
use jubianchi\PPC\Stream;
use PHPUnit\Framework\TestCase;

/**
 * @small
 *
 * @internal
 */
final class RepeatTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function match(): void
    {
        $stream = new Stream('abc');
        $parser = repeat(2, regex('/[a-c]/'));

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
    public function noMatch(): void
    {
        $stream = new Stream('def');
        $parser = repeat(2, regex('/[a-c]/'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('regex: Expected "/[a-c]/", got "d" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->key());
        self::assertEquals('d', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function notEnoughMatch(): void
    {
        $stream = new Stream('abc');
        $parser = repeat(4, regex('/[a-c]/'));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('regex: Expected "/[a-c]/", got "jubianchi\\PPC\\Stream::EOS" at line 1 offset 3', $failure->getMessage());
        }

        self::assertEquals(0, $stream->key());
        self::assertEquals('a', $stream->current());
    }
}
