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

use function jubianchi\PPC\char;

/**
 * @internal
 */
#[Small]
final class CharTest extends TestCase
{
    #[Test]
    public function matchesTheCharAtTheOffset(): void
    {
        self::assertEquals(new Success('b', 2), char('b')('abc', 1));
    }

    #[Test]
    public function failsOnAnotherChar(): void
    {
        self::assertEquals(new Failure(['"b"'], 0), char('b')('abc', 0));
    }

    #[Test]
    public function failsAtTheEndOfInput(): void
    {
        self::assertEquals(new Failure(['"b"'], 3), char('b')('abc', 3));
    }

    #[Test]
    public function matchesAMultiByteCharAndAdvancesByItsByteLength(): void
    {
        self::assertEquals(new Success('é', 3), char('é')('aé', 1));
    }

    #[Test]
    public function doesNotMatchAPartialMultiByteChar(): void
    {
        self::assertEquals(new Failure(['"é"'], 0), char('é')("\xC3", 0));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputEndsInsideTheChar(): void
    {
        self::assertEquals(new Incomplete(1), char('é')("\xC3", 0, true));
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith(['ab'])]
    #[TestWith(['éa'])]
    public function rejectsAnythingButASingleChar(string $char): void
    {
        $this->expectException(\InvalidArgumentException::class);

        char($char);
    }
}
