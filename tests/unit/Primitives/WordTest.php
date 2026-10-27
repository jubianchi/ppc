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

use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class WordTest extends TestCase
{
    #[Test]
    public function matchesTheWordAtTheOffset(): void
    {
        self::assertEquals(new Success('null', 5), word('null')('[null]', 1));
    }

    #[Test]
    public function matchesAMultiByteWordAndAdvancesByItsByteLength(): void
    {
        self::assertEquals(new Success('café', 5), word('café')('café!', 0));
    }

    #[Test]
    public function failsOnAnotherWord(): void
    {
        self::assertEquals(new Failure(['"null"'], 1), word('null')('[true]', 1));
    }

    #[Test]
    public function failsWhenTheInputEndsInsideTheWord(): void
    {
        self::assertEquals(new Failure(['"null"'], 1), word('null')('[nul', 1));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputEndsInsideTheWord(): void
    {
        self::assertEquals(new Incomplete(1), word('null')('[nul', 1, true));
        self::assertEquals(new Incomplete(4), word('null')('[', 1, true));
    }

    #[Test]
    public function failsOnAnotherWordOfAPartialInput(): void
    {
        self::assertEquals(new Failure(['"null"'], 1), word('null')('[nux', 1, true));
    }

    #[Test]
    public function matchesTheWordAtTheEndOfAPartialInput(): void
    {
        self::assertEquals(new Success('null', 5), word('null')('[null', 1, true));
    }

    #[Test]
    public function rejectsAnEmptyWord(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        word('');
    }
}
