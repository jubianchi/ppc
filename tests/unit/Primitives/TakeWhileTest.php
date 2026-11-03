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

use function jubianchi\PPC\takeWhile;

/**
 * @internal
 */
#[Small]
final class TakeWhileTest extends TestCase
{
    #[Test]
    public function consumesTheCharsMatchingThePredicate(): void
    {
        self::assertEquals(new Success('123', 4), takeWhile(ctype_digit(...))('_123a', 1));
    }

    #[Test]
    public function passesMultiByteCharsToThePredicate(): void
    {
        $chars = [];
        $parser = takeWhile(static function (string $char) use (&$chars): bool {
            $chars[] = $char;

            return ' ' !== $char;
        });

        self::assertEquals(new Success('été', 5), $parser('été 1', 0));
        self::assertSame(['é', 't', 'é', ' '], $chars);
    }

    #[Test]
    public function succeedsWithoutConsumingInputWhenNoCharMatches(): void
    {
        self::assertEquals(new Success('', 0), takeWhile(ctype_digit(...))('a', 0));
    }

    #[Test]
    public function stopsAtTheEndOfACompleteInput(): void
    {
        self::assertEquals(new Success('123', 3), takeWhile(ctype_digit(...))('123', 0));
    }

    #[Test]
    public function failsAtTheFirstNonMatchingCharBeforeTheMinimum(): void
    {
        self::assertEquals(new Failure(['matching char'], 2), takeWhile(ctype_digit(...), 3)('12a', 0));
    }

    #[Test]
    public function failsAtTheEndOfACompleteInputBeforeTheMinimum(): void
    {
        self::assertEquals(new Failure(['matching char'], 2), takeWhile(ctype_digit(...), 3)('12', 0));
    }

    #[Test]
    public function decidesOnAPartialInputAtTheFirstNonMatchingChar(): void
    {
        self::assertEquals(new Success('12', 2), takeWhile(ctype_digit(...))('12a', 0, true));
    }

    #[Test]
    public function isIncompleteAtTheEndOfAPartialInput(): void
    {
        self::assertEquals(new Incomplete(1), takeWhile(ctype_digit(...))('123', 0, true));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputEndsInsideAMultiByteChar(): void
    {
        self::assertEquals(new Incomplete(1), takeWhile(static fn (string $char): bool => true)("a\xC3", 0, true));
    }

    #[Test]
    public function rejectsANegativeMinimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-negative minimum, got -1');

        takeWhile(ctype_digit(...), -1);
    }
}
