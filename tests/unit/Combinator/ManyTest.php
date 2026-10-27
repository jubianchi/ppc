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

namespace jubianchi\PPC\Tests\Combinator;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\char;
use function jubianchi\PPC\many;
use function jubianchi\PPC\many1;
use function jubianchi\PPC\opt;
use function jubianchi\PPC\seq;

/**
 * @internal
 */
#[Small]
final class ManyTest extends TestCase
{
    #[Test]
    public function collectsEveryMatch(): void
    {
        self::assertEquals(new Success(['a', 'a', 'a'], 3), many(char('a'))('aaab', 0));
    }

    #[Test]
    public function succeedsWithoutAnyMatch(): void
    {
        self::assertEquals(new Success([], 0), many(char('a'))('b', 0));
    }

    #[Test]
    public function stopsBeforeAPartialMatch(): void
    {
        self::assertEquals(new Success([['a', 'b']], 2), many(seq(char('a'), char('b')))('abac', 0));
    }

    #[Test]
    public function rejectsAParserThatSucceedsWithoutConsuming(): void
    {
        $this->expectException(\LogicException::class);

        many(opt(char('a')))('b', 0);
    }

    #[Test]
    public function many1CollectsEveryMatch(): void
    {
        self::assertEquals(new Success(['a', 'a'], 2), many1(char('a'))('aab', 0));
    }

    #[Test]
    public function many1FailsWithoutAnyMatch(): void
    {
        self::assertEquals(new Failure(['"a"'], 0), many1(char('a'))('b', 0));
    }
}
