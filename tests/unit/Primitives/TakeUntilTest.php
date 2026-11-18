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

namespace jubianchi\PPC\Tests\Primitives;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\takeUntil;

/**
 * @internal
 */
#[Small]
final class TakeUntilTest extends TestCase
{
    #[Test]
    public function consumesEverythingUpToTheDelimiter(): void
    {
        self::assertEquals(new Success(' été ', 9), takeUntil('*/')('/* été */', 2));
    }

    #[Test]
    public function succeedsWithoutConsumingInputAtTheDelimiter(): void
    {
        self::assertEquals(new Success('', 0), takeUntil('*/')('*/', 0));
    }

    #[Test]
    public function failsAtTheEndOfACompleteInputWithoutTheDelimiter(): void
    {
        self::assertEquals(new Failure(['"*/"'], 10, 2), takeUntil('*/')('/* comment', 2));
    }

    #[Test]
    public function failsAtAnOffsetPastTheEndOfTheInput(): void
    {
        self::assertEquals(new Failure(['"*/"'], 5, 2), takeUntil('*/')('ab', 5));
    }

    #[Test]
    public function findsTheDelimiterInAPartialInput(): void
    {
        self::assertEquals(new Success('a', 1), takeUntil('*/')('a*/b', 0, true));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputHasNoDelimiter(): void
    {
        self::assertEquals(new Incomplete(), takeUntil('*/')('abc*', 0, true));
    }

    #[Test]
    public function consumesEverythingUpToTheFirstCharMatchingThePredicate(): void
    {
        self::assertEquals(new Success('été', 6), takeUntil(self::isQuote(...))('"été"', 1));
    }

    #[Test]
    public function succeedsWithoutConsumingInputAtACharMatchingThePredicate(): void
    {
        self::assertEquals(new Success('', 0), takeUntil(self::isQuote(...))('"', 0));
    }

    #[Test]
    public function failsAtTheEndOfACompleteInputWithoutACharMatchingThePredicate(): void
    {
        self::assertEquals(new Failure(['matching char'], 4), takeUntil(self::isQuote(...))('"abc', 1));
    }

    #[Test]
    public function findsACharMatchingThePredicateInAPartialInput(): void
    {
        self::assertEquals(new Success('a', 1), takeUntil(self::isQuote(...))('a"b', 0, true));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputHasNoCharMatchingThePredicate(): void
    {
        self::assertEquals(new Incomplete(1), takeUntil(self::isQuote(...))('abc', 0, true));
    }

    #[Test]
    public function rejectsAnEmptyDelimiter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty delimiter');

        takeUntil('');
    }

    private static function isQuote(string $char): bool
    {
        return '"' === $char;
    }
}
