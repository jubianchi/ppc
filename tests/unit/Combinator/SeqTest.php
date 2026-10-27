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
use function jubianchi\PPC\opt;
use function jubianchi\PPC\seq;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class SeqTest extends TestCase
{
    #[Test]
    public function chainsParsersAndCollectsTheirValues(): void
    {
        self::assertEquals(
            new Success(['a', 'bc', 'd'], 5),
            seq(char('a'), word('bc'), char('d'))('_abcd_', 1),
        );
    }

    #[Test]
    public function returnsTheFirstFailure(): void
    {
        self::assertEquals(
            new Failure(['"c"'], 1),
            seq(char('a'), char('c'), char('d'))('abd', 0),
        );
    }

    #[Test]
    public function mergesTheFailureWithTheHintOfThePreviousParsers(): void
    {
        self::assertEquals(
            new Failure(['"a"', '"b"'], 1),
            seq(many(char('a')), char('b'))('ac', 0),
        );
    }

    #[Test]
    public function returnsAHintThatWentFurtherThanTheFailure(): void
    {
        self::assertEquals(
            new Failure(['"b"'], 2),
            seq(opt(seq(char('a'), char('a'), char('b'))), char('c'))('aax', 0),
        );
    }

    #[Test]
    public function keepsTheFurthestHint(): void
    {
        self::assertEquals(
            new Success([['a'], null], 1, new Failure(['"a"', '"b"'], 1)),
            seq(many(char('a')), opt(char('b')))('ac', 0),
        );
    }
}
