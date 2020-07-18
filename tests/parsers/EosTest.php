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
use function jubianchi\PPC\Parsers\eos;
use jubianchi\PPC\Stream;
use PHPUnit\Framework\TestCase;

final class EosTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function match(): void
    {
        $stream = new Stream('');
        $parser = eos();

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertNull($result->result());

        self::assertEquals(0, $stream->key());
        self::assertEquals(Stream::EOS, $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function noMatch(): void
    {
        $stream = new Stream('abc');
        $parser = eos();

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('eos: Expected "jubianchi\PPC\Stream::EOS", got "a" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->key());
        self::assertEquals('a', $stream->current());
    }
}
