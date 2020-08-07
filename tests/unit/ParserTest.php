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

namespace jubianchi\PPC\Tests;

use Exception;
use jubianchi\PPC\Parser;
use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Parser\Result\Success;
use function jubianchi\PPC\Parsers\any;
use jubianchi\PPC\Stream;
use jubianchi\PPC\Stream\Char;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @small
 *
 * @internal
 */
final class ParserTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function parserThrows(): void
    {
        $stream = new Char('abc');
        $exception = new Exception('random exception');
        $parser = new Parser('test parser', function () use ($exception): Parser\Result { throw $exception; });

        try {
            $parser($stream);

            self::fail();
        } catch (RuntimeException $thrown) {
            self::assertSame($exception, $thrown->getPrevious());
            self::assertEquals('test parser: '.$exception->getMessage(), $thrown->getMessage());
        }
    }

    /**
     * @test
     * @small
     */
    public function map(): void
    {
        $stream = new Char('abc');
        $parser = (new Parser('test parser', function (Stream $stream): Parser\Result {
            $result = new Success($stream->current());

            $stream->consume();

            return $result;
        }))->map(fn (Result $result): Result => new Success(strtoupper($result->result())));

        $result = $parser($stream);

        self::assertThat($result, self::isInstanceOf(Success::class));
        self::assertEquals('A', $result->result());

        self::assertEquals(1, $stream->offset());
        self::assertEquals('b', $stream->current());
    }

    /**
     * @test
     * @small
     */
    public function label(): void
    {
        $parser = any();
        $labeled = $parser->label('test parser');

        self::assertNotSame($parser, $labeled);
        self::assertEquals('test parser•any', (string) $labeled);
    }

    /**
     * @test
     * @small
     */
    public function stringify(): void
    {
        $parser = any();
        $stringified = $parser->stringify(fn ($label) => 'foobar');

        self::assertNotSame($parser, $stringified);
        self::assertEquals('foobar', (string) $stringified);
    }
}
