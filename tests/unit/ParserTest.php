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

namespace jubianchi\PPC\Tests;

use jubianchi\PPC\Parser;
use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Parser\Result\Success;
use jubianchi\PPC\Stream;
use jubianchi\PPC\Stream\Char;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\Parsers\any;

/**
 * @internal
 */
#[Small]
final class ParserTest extends TestCase
{
    #[Test]
    public function parserThrows(): void
    {
        $stream = new Char('abc');
        $exception = new \Exception('random exception');
        $parser = new Parser('test parser', function () use ($exception): Result { throw $exception; });

        try {
            $parser($stream);

            self::fail();
        } catch (\RuntimeException $thrown) {
            self::assertSame($exception, $thrown->getPrevious());
            self::assertEquals('test parser: '.$exception->getMessage(), $thrown->getMessage());
        }
    }

    #[Test]
    public function map(): void
    {
        $stream = new Char('abc');
        $parser = new Parser('test parser', function (Stream $stream): Result {
            $result = new Success($stream->current());

            $stream->consume();

            return $result;
        })->map(fn (Result $result): Result => new Success(strtoupper($result->result())));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertEquals('A', $result->result());

        self::assertEquals(1, $stream->tell());
        self::assertEquals('b', $stream->current());
    }

    #[Test]
    public function label(): void
    {
        $parser = any();
        $labeled = $parser->label('test parser');

        self::assertNotSame($parser, $labeled);
        self::assertEquals('test parser•any', (string) $labeled);
    }

    #[Test]
    public function stringify(): void
    {
        $parser = any();
        $stringified = $parser->stringify(fn ($label) => 'foobar');

        self::assertNotSame($parser, $stringified);
        self::assertEquals('foobar', (string) $stringified);
    }
}
