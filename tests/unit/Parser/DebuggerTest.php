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
use jubianchi\PPC\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DebuggerTest extends TestCase
{
    /**
     * @test
     * @small
     */
    public function info(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo(__FUNCTION__),
                $this->identicalTo(['ops' => 0])
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
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo(__FUNCTION__),
                $this->identicalTo(['ops' => 0])
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
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('> parser'),
                $this->identicalTo(['line' => 1, 'column' => 0, 'ops' => 0])
            );

        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects($this->atLeastOnce())->method('__toString');

        $stream = $this->createMock(Stream::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects($this->atLeastOnce())->method('position');

        $debugger = new Debugger($logger);

        $debugger->enter($parser, $stream);
    }

    /**
     * @test
     * @small
     */
    public function enterIncreasesPadding(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                [
                    $this->equalTo('> parser'),
                    $this->identicalTo(['line' => 1, 'column' => 0, 'ops' => 0]),
                ],
                [
                    $this->equalTo('  > child parser'),
                    $this->identicalTo(['line' => 1, 'column' => 0, 'ops' => 0]),
                ],
                [
                    $this->equalTo('    > grand child parser'),
                    $this->identicalTo(['line' => 1, 'column' => 0, 'ops' => 0]),
                ],
            );

        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects($this->atLeastOnce())->method('__toString');

        $childParser = $this->createMock(Parser::class);
        $childParser->method('__toString')->willReturn('child parser');
        $childParser->expects($this->atLeastOnce())->method('__toString');

        $grandChildParser = $this->createMock(Parser::class);
        $grandChildParser->method('__toString')->willReturn('grand child parser');
        $grandChildParser->expects($this->atLeastOnce())->method('__toString');

        $stream = $this->createMock(Stream::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects($this->atLeastOnce())->method('position');

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
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('< parser'),
                $this->identicalTo(['line' => 1, 'column' => 0, 'ops' => 1])
            );

        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects($this->atLeastOnce())->method('__toString');

        $stream = $this->createMock(Stream::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects($this->atLeastOnce())->method('position');

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
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    $this->equalTo('> parser'),
                    $this->identicalTo(['line' => 1, 'column' => 0, 'ops' => 0]),
                ],
                [
                    $this->equalTo('< parser'),
                    $this->callback(function (array $subject): bool {
                        $this->assertEquals(1, $subject['line']);
                        $this->assertEquals(0, $subject['column']);
                        $this->assertEqualsWithDelta(0.05, $subject['duration'], 0.01);
                        $this->assertEquals(1, $subject['ops']);

                        return true;
                    }),
                ],
            );

        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects($this->atLeastOnce())->method('__toString');

        $stream = $this->createMock(Stream::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects($this->atLeastOnce())->method('position');

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
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('< parser'),
                $this->identicalTo(['line' => 1, 'column' => 0, 'consumed' => 'a', 'ops' => 1])
            );

        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects($this->once())->method('__toString');

        $stream = $this->createMock(Stream::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects($this->once())->method('position');

        $slice = $this->createMock(Slice::class);
        $slice->method('__toString')->willReturn('a');
        $slice->expects($this->once())->method('__toString');

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
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('< parser'),
                $this->identicalTo(['line' => 1, 'column' => 0, 'ops' => 1])
            );

        $parser = $this->createMock(Parser::class);
        $parser->method('__toString')->willReturn('parser');
        $parser->expects($this->once())->method('__toString');

        $stream = $this->createMock(Stream::class);
        $stream->method('position')->willReturn(['line' => 1, 'column' => 0]);
        $stream->expects($this->once())->method('position');

        $result = $this->createMock(Parser\Result\Failure::class);

        $debugger = new Debugger($logger);

        $debugger->exit($parser, $stream, $result);
    }
}
