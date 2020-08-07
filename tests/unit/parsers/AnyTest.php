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
use function jubianchi\PPC\Parsers\any;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream\Char;
use PHPUnit\Framework\TestCase;

/**
 * @small
 *
 * @internal
 */
final class AnyTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function match(): void
    {
        $stream = new Char('abc');
        $parser = any();

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertThat($result->result(), self::isInstanceOf(Slice::class));
        self::assertEquals('a', (string) $result->result());

        self::assertEquals(1, $stream->tell());
        self::assertEquals('b', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function eos(): void
    {
        $stream = new Char('');
        $parser = any();

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Failure::class));

        try {
            $result->result();

            self::fail();
        } catch (Failure $failure) {
            self::assertEquals('any: Expected "any", got "jubianchi\PPC\Stream::EOS" at line 1 offset 0', $failure->getMessage());
        }

        self::assertEquals(0, $stream->tell());
        self::assertEquals(Char::EOS, $stream->current());
    }
}
