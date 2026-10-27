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

use function jubianchi\PPC\regex;

/**
 * @internal
 */
#[Small]
final class RegexTest extends TestCase
{
    #[Test]
    public function consumesTheWholeMatchAtTheOffset(): void
    {
        self::assertEquals(new Success('123', 5), regex('/[0-9]+/')('ab123c', 2));
    }

    #[Test]
    public function doesNotSearchPastTheOffset(): void
    {
        self::assertEquals(new Failure(['/[0-9]+/'], 0), regex('/[0-9]+/')('ab123', 0));
    }

    #[Test]
    public function keepsThePatternModifiers(): void
    {
        self::assertEquals(new Success('café', 5), regex('/\p{L}+/u')('café!', 0));
    }

    #[Test]
    public function failsAtTheEndOfInput(): void
    {
        self::assertEquals(new Failure(['/[0-9]/'], 3), regex('/[0-9]/')('abc', 3));
    }

    #[Test]
    public function rejectsAnInvalidPattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        regex('/[0-9/');
    }

    #[Test]
    public function rejectsAnEmptyWindow(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        regex('/[0-9]/', 0);
    }

    #[Test]
    public function trustsAMatchFollowedByAWindowOfPartialInput(): void
    {
        self::assertEquals(new Success('12', 2), regex('/[0-9]+/', 2)('12ab', 0, true));
    }

    #[Test]
    public function isIncompleteWhenAMatchIsNotFollowedByAWindowOfPartialInput(): void
    {
        self::assertEquals(new Incomplete(), regex('/[0-9]+/', 2)('12a', 0, true));
        self::assertEquals(new Incomplete(), regex('/[0-9]+/')('12', 0, true));
    }

    #[Test]
    public function trustsAFailureOnAWindowOfPartialInput(): void
    {
        self::assertEquals(new Failure(['/[0-9]{4}/'], 0), regex('/[0-9]{4}/', 4)('12ab', 0, true));
    }

    #[Test]
    public function isIncompleteWhenAFailureHasLessThanAWindowOfPartialInput(): void
    {
        self::assertEquals(new Incomplete(), regex('/[0-9]{4}/', 4)('123', 0, true));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputEndsInsideAMultiByteChar(): void
    {
        self::assertEquals(new Incomplete(), regex('/\p{L}+/u', 1)("\u{e9}\xC3", 0, true));
    }

    #[Test]
    public function failsOnInvalidUtf8InACompleteInput(): void
    {
        self::assertEquals(new Failure(['/\p{L}+/u'], 0), regex('/\p{L}+/u', 1)("\u{e9}\xC3", 0));
    }

    #[Test]
    public function ignoresTheWindowOnACompleteInput(): void
    {
        self::assertEquals(new Success('12', 2), regex('/[0-9]+/')('12', 0));
        self::assertEquals(new Failure(['/[0-9]{4}/'], 0), regex('/[0-9]{4}/')('123', 0));
    }
}
