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
}
