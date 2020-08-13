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

namespace jubianchi\PPC\Tests\Parser;

use jubianchi\PPC\Parser;
use jubianchi\PPC\Parser\Debugger;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream\Char;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @small
 *
 * @internal
 */
final class DebuggerTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function info(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::equalTo(__FUNCTION__),
                self::identicalTo(['ops' => 0])
            );

        $debugger = new Debugger($logger);

        $debugger->info(__FUNCTION__);
    }

    /**
     * @test
     * @small
     */
    public function error(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                self::equalTo(__FUNCTION__),
                self::identicalTo(['ops' => 0])
            );

        $debugger = new Debugger($logger);

        $debugger->error(__FUNCTION__);
    }

    /**
     * @test
     * @small
     */
    public function enter(): void
    {
        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects(self::atLeastOnce())->method('__toString');

        $stream = $this->createMock(Char::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects(self::atLeastOnce())->method('position');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::equalTo('> parser'),
                self::identicalTo(['line' => 1, 'column' => 0, 'stream' => get_class($stream).'#'.spl_object_id($stream), 'ops' => 0])
            );

        $debugger = new Debugger($logger);

        $debugger->enter($parser, $stream);
    }

    /**
     * @test
     * @small
     */
    public function enterIncreasesPadding(): void
    {
        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects(self::atLeastOnce())->method('__toString');

        $childParser = $this->createMock(Parser::class);
        $childParser->method('__toString')->willReturn('child parser');
        $childParser->expects(self::atLeastOnce())->method('__toString');

        $grandChildParser = $this->createMock(Parser::class);
        $grandChildParser->method('__toString')->willReturn('grand child parser');
        $grandChildParser->expects(self::atLeastOnce())->method('__toString');

        $stream = $this->createMock(Char::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects(self::atLeastOnce())->method('position');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::exactly(3))
            ->method('info')
            ->withConsecutive(
                [
                    self::equalTo('> parser'),
                    self::identicalTo(['line' => 1, 'column' => 0, 'stream' => get_class($stream).'#'.spl_object_id($stream), 'ops' => 0]),
                ],
                [
                    self::equalTo('  > child parser'),
                    self::identicalTo(['line' => 1, 'column' => 0, 'stream' => get_class($stream).'#'.spl_object_id($stream), 'ops' => 0]),
                ],
                [
                    self::equalTo('    > grand child parser'),
                    self::identicalTo(['line' => 1, 'column' => 0, 'stream' => get_class($stream).'#'.spl_object_id($stream), 'ops' => 0]),
                ],
            );

        $debugger = new Debugger($logger);
        $debugger->enter($parser, $stream);

        $debugger->enter($childParser, $stream);
        $debugger->enter($grandChildParser, $stream);
    }

    /**
     * @test
     * @small
     */
    public function exit(): void
    {
        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects(self::atLeastOnce())->method('__toString');

        $stream = $this->createMock(Char::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects(self::atLeastOnce())->method('position');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::equalTo('< parser'),
                self::identicalTo(['line' => 1, 'column' => 0, 'stream' => get_class($stream).'#'.spl_object_id($stream), 'ops' => 1])
            );

        $result = $this->createMock(Parser\Result\Success::class);

        $debugger = new Debugger($logger);

        $debugger->exit($parser, $stream, $result);
    }

    /**
     * @test
     * @small
     */
    public function exitDuration(): void
    {
        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects(self::atLeastOnce())->method('__toString');

        $stream = $this->createMock(Char::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects(self::atLeastOnce())->method('position');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    self::equalTo('> parser'),
                    self::identicalTo(['line' => 1, 'column' => 0, 'stream' => get_class($stream).'#'.spl_object_id($stream), 'ops' => 0]),
                ],
                [
                    self::equalTo('< parser'),
                    self::callback(function (array $subject): bool {
                        self::assertEquals(1, $subject['line']);
                        self::assertEquals(0, $subject['column']);
                        self::assertEqualsWithDelta(0.05, $subject['duration'], 0.01);
                        self::assertEquals(1, $subject['ops']);

                        return true;
                    }),
                ],
            );

        $result = $this->createMock(Parser\Result\Success::class);

        $debugger = new Debugger($logger);

        $debugger->enter($parser, $stream);

        usleep(50000);

        $debugger->exit($parser, $stream, $result);
    }

    /**
     * @test
     * @small
     */
    public function exitConsumed(): void
    {
        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects(self::once())->method('__toString');

        $stream = $this->createMock(Char::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects(self::once())->method('position');

        $slice = $this->createMock(Slice::class);
        $slice->method('__toString')->willReturn('a');
        $slice->expects(self::once())->method('__toString');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::equalTo('< parser'),
                self::identicalTo(['line' => 1, 'column' => 0, 'stream' => get_class($stream).'#'.spl_object_id($stream), 'consumed' => 'a', 'ops' => 1])
            );

        $result = $this->createMock(Parser\Result\Success::class);
        $result->method('result')->willReturn($slice);

        $debugger = new Debugger($logger);

        $debugger->exit($parser, $stream, $result);
    }

    /**
     * @test
     * @small
     */
    public function exitFailure(): void
    {
        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects(self::once())->method('__toString');

        $stream = $this->createMock(Char::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects(self::once())->method('position');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                self::equalTo('< parser'),
                self::identicalTo(['line' => 1, 'column' => 0, 'stream' => get_class($stream).'#'.spl_object_id($stream), 'ops' => 1])
            );

        $result = $this->createMock(Parser\Result\Failure::class);

        $debugger = new Debugger($logger);

        $debugger->exit($parser, $stream, $result);
    }
}
