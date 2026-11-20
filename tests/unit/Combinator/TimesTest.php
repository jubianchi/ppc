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
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\char;
use function jubianchi\PPC\many;
use function jubianchi\PPC\peek;
use function jubianchi\PPC\seq;
use function jubianchi\PPC\times;

/**
 * @internal
 */
#[Small]
final class TimesTest extends TestCase
{
    #[Test]
    public function matchesExactlyTheGivenNumberOfTimes(): void
    {
        self::assertEquals(new Success(['a', 'a'], 2), times(char('a'), 2)('aaa', 0));
    }

    #[Test]
    public function matchesNothingZeroTimes(): void
    {
        self::assertEquals(new Success([], 0), times(char('a'), 0)('a', 0));
    }

    #[Test]
    public function stopsAtTheMaximumWithoutConsumingMore(): void
    {
        self::assertEquals(new Success(['a', 'a', 'a'], 3), times(char('a'), 1, 3)('aaaa', 0));
    }

    #[Test]
    public function matchesBetweenTheMinimumAndTheMaximum(): void
    {
        self::assertEquals(new Success(['a', 'a'], 2, new Failure(['"a"'], 2)), times(char('a'), 1, 3)('aab', 0));
    }

    #[Test]
    public function failsBelowTheMinimum(): void
    {
        self::assertEquals(new Failure(['"a"'], 2), times(char('a'), 3, 5)('aab', 0));
    }

    #[Test]
    public function mergesTheFailureWithTheHintOfThePreviousMatches(): void
    {
        self::assertEquals(new Failure(['"a"', '"b"'], 3), times(seq(many(char('a')), char('b')), 2)('abac', 0));
    }

    #[Test]
    public function keepsTheHintOfTheLastMatch(): void
    {
        self::assertEquals(new Success([['a']], 1, new Failure(['"a"'], 1)), times(many(char('a')), 1)('ab', 0));
    }

    #[Test]
    public function allowsAParserNotConsumingInput(): void
    {
        self::assertEquals(new Success(['a', 'a'], 0), times(char('a') |> peek(), 2)('a', 0));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputEndsBeforeTheMaximum(): void
    {
        self::assertEquals(new Incomplete(1), times(char('a'), 1, 3)('aa', 0, true));
    }

    #[Test]
    public function doesNotAskForMoreInputAtTheMaximum(): void
    {
        self::assertEquals(new Success(['a', 'a'], 2), times(char('a'), 2)('aa', 0, true));
    }

    #[Test]
    public function rejectsANegativeMinimum(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Expected a non-negative minimum, got -1');

        times(char('a'), -1);
    }

    #[Test]
    public function rejectsAMaximumBelowTheMinimum(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Expected a maximum of at least 3, got 2');

        times(char('a'), 3, 2);
    }
}
