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

use function jubianchi\PPC\space;

/**
 * @internal
 */
#[Small]
final class SpaceTest extends TestCase
{
    #[Test]
    #[TestWith([' '])]
    #[TestWith(["\t"])]
    #[TestWith(["\n"])]
    #[TestWith(["\r"])]
    #[TestWith(["\f"])]
    #[TestWith(["\v"])]
    #[TestWith(["\u{A0}"])]
    #[TestWith(["\u{3000}"])]
    #[TestWith(["\u{2028}"])]
    public function consumesOneUnicodeWhitespace(string $space): void
    {
        self::assertEquals(new Success($space, 1 + \strlen($space)), space()('_'.$space.'_', 1));
    }

    #[Test]
    #[TestWith([' '])]
    #[TestWith(["\t"])]
    #[TestWith(["\n"])]
    #[TestWith(["\r"])]
    #[TestWith(["\f"])]
    #[TestWith(["\v"])]
    public function consumesOneAsciiWhitespace(string $space): void
    {
        self::assertEquals(new Success($space, 2), space(ascii: true)('_'.$space.'_', 1));
    }

    #[Test]
    #[TestWith(['a'])]
    #[TestWith(["\u{200B}"])]
    #[TestWith(['é'])]
    public function failsOnAnythingButAWhitespace(string $char): void
    {
        self::assertEquals(new Failure(['space'], 0), space()($char, 0));
    }

    #[Test]
    #[TestWith(["\u{A0}"])]
    #[TestWith(["\u{3000}"])]
    #[TestWith(['a'])]
    public function failsOnAnythingButAnAsciiWhitespaceInAsciiMode(string $char): void
    {
        self::assertEquals(new Failure(['ASCII space'], 0), space(ascii: true)($char, 0));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputEndsInsideAMultiByteChar(): void
    {
        self::assertEquals(new Incomplete(2), space()("\xE3", 0, true));
    }
}
