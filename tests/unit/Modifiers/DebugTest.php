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

namespace jubianchi\PPC\Tests\Modifiers;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\char;
use function jubianchi\PPC\debug;
use function jubianchi\PPC\many;
use function jubianchi\PPC\seq;

/**
 * @internal
 */
#[Small]
final class DebugTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $lines = [];

    #[Test]
    public function tracesASuccess(): void
    {
        $parser = seq(char('a'), many(char('é'))) |> debug('word', $this->write(...));

        self::assertEquals(new Success(['a', ['é', 'é']], 7, new Failure(['"é"'], 7)), $parser("\nxaééb", 2));
        self::assertSame(
            [
                'word: start at line 2, column 2',
                'word: matched "aéé" up to line 2, column 5',
            ],
            $this->lines,
        );
    }

    #[Test]
    public function tracesAFailure(): void
    {
        $parser = seq(char('a'), char('b')) |> debug('pair', $this->write(...));

        self::assertEquals(new Failure(['"b"'], 1), $parser('ac', 0));
        self::assertSame(
            [
                'pair: start at line 1, column 1',
                'pair: Expected "b", got "c" at line 1, column 2',
            ],
            $this->lines,
        );
    }

    #[Test]
    public function tracesNestedParsersInOrder(): void
    {
        $parser = seq(char('a') |> debug('inner', $this->write(...))) |> debug('outer', $this->write(...));

        $parser('a', 0);

        self::assertSame(
            [
                'outer: start at line 1, column 1',
                'inner: start at line 1, column 1',
                'inner: matched "a" up to line 1, column 2',
                'outer: matched "a" up to line 1, column 2',
            ],
            $this->lines,
        );
    }

    #[Test]
    public function tracesAnIncompleteInput(): void
    {
        $parser = (static fn (): Incomplete => new Incomplete(3)) |> debug('word', $this->write(...));

        self::assertEquals(new Incomplete(3), $parser('a', 1, true));
        self::assertSame(
            [
                'word: start at line 1, column 2',
                'word: incomplete input, 3 more bytes needed',
            ],
            $this->lines,
        );
    }

    private function write(string $line): void
    {
        $this->lines[] = $line;
    }
}
