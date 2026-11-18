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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\satisfy;

/**
 * @internal
 */
#[Small]
final class SatisfyTest extends TestCase
{
    #[Test]
    #[TestWith(['a', 2])]
    #[TestWith(['é', 3])]
    #[TestWith(['🎉', 5])]
    public function consumesOneCharMatchingThePredicate(string $char, int $offset): void
    {
        self::assertEquals(new Success($char, $offset), satisfy(self::isNotUnderscore(...))('_'.$char.'_', 1));
    }

    #[Test]
    public function passesTheWholeCharToThePredicate(): void
    {
        $chars = [];

        satisfy(static function (string $char) use (&$chars): bool {
            $chars[] = $char;

            return true;
        })('日本', 0);

        self::assertSame(['日'], $chars);
    }

    #[Test]
    public function failsOnACharNotMatchingThePredicate(): void
    {
        self::assertEquals(new Failure(['matching char'], 1), satisfy(self::isNotUnderscore(...))('a_', 1));
    }

    #[Test]
    public function failsAtTheEndOfACompleteInput(): void
    {
        self::assertEquals(new Failure(['matching char'], 1), satisfy(self::isNotUnderscore(...))('a', 1));
    }

    #[Test]
    public function isIncompleteAtTheEndOfAPartialInput(): void
    {
        self::assertEquals(new Incomplete(1), satisfy(self::isNotUnderscore(...))('a', 1, true));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputEndsInsideAMultiByteChar(): void
    {
        self::assertEquals(new Incomplete(2), satisfy(self::isNotUnderscore(...))("\xE6", 0, true));
    }

    #[Test]
    public function decidesOnTheFirstCharOfAPartialInput(): void
    {
        self::assertEquals(new Success('1', 1), satisfy(ctype_digit(...))('12345', 0, true));
    }

    private static function isNotUnderscore(string $char): bool
    {
        return '_' !== $char;
    }
}
