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

use function jubianchi\PPC\digit;

/**
 * @internal
 */
#[Small]
final class DigitTest extends TestCase
{
    #[Test]
    #[TestWith(['0'])]
    #[TestWith(['5'])]
    #[TestWith(['9'])]
    public function consumesOneDigit(string $digit): void
    {
        self::assertEquals(new Success($digit, 2), digit()('_'.$digit.'_', 1));
    }

    #[Test]
    #[TestWith(['a'])]
    #[TestWith(['٣'])]
    #[TestWith(['_'])]
    public function failsOnAnythingButAnAsciiDigit(string $char): void
    {
        self::assertEquals(new Failure(['digit'], 0), digit()($char, 0));
    }

    #[Test]
    public function failsAtTheEndOfACompleteInput(): void
    {
        self::assertEquals(new Failure(['digit'], 1), digit()('1', 1));
    }

    #[Test]
    public function isIncompleteAtTheEndOfAPartialInput(): void
    {
        self::assertEquals(new Incomplete(1), digit()('1', 1, true));
    }

    #[Test]
    public function decidesOnTheFirstCharOfAPartialInput(): void
    {
        self::assertEquals(new Success('1', 1), digit()('12345', 0, true));
    }
}
