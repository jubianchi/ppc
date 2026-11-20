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

use function jubianchi\PPC\atLeast;
use function jubianchi\PPC\char;
use function jubianchi\PPC\peek;

/**
 * @internal
 */
#[Small]
final class AtLeastTest extends TestCase
{
    #[Test]
    public function matchesAsManyTimesAsPossible(): void
    {
        self::assertEquals(new Success(['a', 'a', 'a'], 3, new Failure(['"a"'], 3)), atLeast(char('a'), 1)('aaab', 0));
    }

    #[Test]
    public function matchesNothingWithAZeroMinimum(): void
    {
        self::assertEquals(new Success([], 0, new Failure(['"a"'], 0)), atLeast(char('a'), 0)('b', 0));
    }

    #[Test]
    public function failsBelowTheMinimum(): void
    {
        self::assertEquals(new Failure(['"a"'], 1), atLeast(char('a'), 2)('ab', 0));
    }

    #[Test]
    public function isIncompleteAtTheEndOfAPartialInput(): void
    {
        self::assertEquals(new Incomplete(1), atLeast(char('a'), 1)('aa', 0, true));
    }

    #[Test]
    public function throwsWhenTheParserDoesNotConsumeInput(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Parser succeeded without consuming input at offset 0');

        atLeast(char('a') |> peek(), 1)('a', 0);
    }

    #[Test]
    public function rejectsANegativeMinimum(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Expected a non-negative minimum, got -1');

        atLeast(char('a'), -1);
    }
}
