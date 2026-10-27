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
use function jubianchi\PPC\many1;
use function jubianchi\PPC\opt;
use function jubianchi\PPC\separated;

/**
 * @internal
 */
#[Small]
final class SeparatedTest extends TestCase
{
    #[Test]
    public function collectsTheValuesWithoutTheSeparators(): void
    {
        self::assertEquals(
            new Success(['a', 'a', 'a'], 5, new Failure(['","'], 5)),
            separated(char(','), char('a'))('a,a,a]', 0),
        );
    }

    #[Test]
    public function succeedsWithoutAnyMatch(): void
    {
        self::assertEquals(new Success([], 0, new Failure(['"a"'], 0)), separated(char(','), char('a'))(']', 0));
    }

    #[Test]
    public function stopsBeforeATrailingSeparatorAndKeepsTheFailureAfterItAsHint(): void
    {
        self::assertEquals(
            new Success(['a', 'a'], 3, new Failure(['"a"'], 4)),
            separated(char(','), char('a'))('a,a,]', 0),
        );
    }

    #[Test]
    public function keepsTheHintsOfTheSeparatorAndTheParser(): void
    {
        self::assertEquals(
            new Success([['a']], 1, new Failure(['"a"', '","'], 1)),
            separated(char(','), many1(char('a')))('a]', 0),
        );
    }

    #[Test]
    public function rejectsASeparatorAndAParserThatSucceedWithoutConsuming(): void
    {
        $this->expectException(\LogicException::class);

        separated(opt(char(',')), opt(char('a')))(']', 0);
    }
}
